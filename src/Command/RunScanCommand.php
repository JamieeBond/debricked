<?php

namespace App\Command;

use App\Entity\Upload;
use App\Factory\TriggerFactory;
use App\Messenger\ScanMessenger;
use App\Model\Trigger;
use App\Traits\BadResponseTrait;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\StatusResponse;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface as HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface as MailerTransportException;

/**
 *  Checks the status on Debricked and triggers actions based on rules.
 */
#[AsCommand(
    name: 'app:run-scan',
    description: 'Check the status of uploaded files.',
)]
class RunScanCommand extends Command
{
    use BadResponseTrait;

    public const ACTION_EMAIL = 'action_email';
    public const OPTION_TRIGGER_IN_PROGRESS = 'trigger_scan_in_progress';
    public const OPTION_TRIGGER_IS_COMPLETE = 'trigger_scan_is_complete';
    public const OPTION_TRIGGER_VUL_GREATER_THAN = 'trigger_vulnerabilities_greater_than';
    public const OPTION_TRIGGER_CVSS_GREATER_THAN = 'trigger_cvss_greater_than';

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var DebrickedApiUtil
     */
    private DebrickedApiUtil $apiUtil;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var TriggerFactory
     */
    private TriggerFactory $triggerFactory;

    /**
     * @var ScanMessenger
     */
    private ScanMessenger $scanMessenger;

    /**
     * @param EntityManagerInterface $em
     * @param DebrickedApiUtil $apiUtil
     * @param SerializerInterface $serializer
     * @param TriggerFactory $triggerFactory
     * @param ScanMessenger $scanMessenger
     */
    public function __construct(EntityManagerInterface $em, DebrickedApiUtil $apiUtil, SerializerInterface $serializer, TriggerFactory $triggerFactory, ScanMessenger $scanMessenger)
    {
        $this->em = $em;
        $this->apiUtil = $apiUtil;
        $this->serializer = $serializer;
        $this->triggerFactory = $triggerFactory;
        $this->scanMessenger = $scanMessenger;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(self::ACTION_EMAIL, 'a', InputOption::VALUE_OPTIONAL, 'Send email when a trigger goes off.', 0)
            ->addOption(self::OPTION_TRIGGER_IN_PROGRESS, 'tsip', InputOption::VALUE_OPTIONAL, Trigger::SCAN_IN_PROGRESS, 0)
            ->addOption(self::OPTION_TRIGGER_IS_COMPLETE, 'tsic', InputOption::VALUE_OPTIONAL, Trigger::SCAN_IS_COMPLETE, 0)
            ->addOption(self::OPTION_TRIGGER_VUL_GREATER_THAN, 'tvgt', InputOption::VALUE_OPTIONAL, Trigger::VULNERABILITIES_GREATER_THAN, null)
            ->addOption(self::OPTION_TRIGGER_CVSS_GREATER_THAN, 'tcgt', InputOption::VALUE_OPTIONAL, Trigger::CVSS_GREATER_THAN, null)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws HttpTransportException
     * @throws MailerTransportException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title($this->getDescription());

        $actionEmail = (bool) $input->getOption('action_email');
        $triggerScanInProgress = (bool) $input->getOption('trigger_scan_in_progress');
        $triggerScanIsComplete = (bool) $input->getOption('trigger_scan_is_complete');
        $triggerVulnerabilitiesGreaterThan = $input->getOption('trigger_vulnerabilities_greater_than');
        $triggerCvssGreaterThan = $input->getOption('trigger_cvss_greater_than');

        $em = $this->em;
        $triggerFactory = $this->triggerFactory;

        $uploads = $em->getRepository(Upload::class)->findAllUnscanned();

        $numOfUploads = count($uploads);

        $io->comment(sprintf(
            'Scanning %s file(s).',
            $numOfUploads
        ));

        if (0 === $numOfUploads) {
            $io->success('Finished, no files needed scanning.');
            return Command::SUCCESS;
        }

        $failures = 0;

        foreach ($uploads as $upload) {
            $triggers = [];
            $url = null;

            $ciUploadId = $upload->getCiUploadId();

            $response = $this->apiUtil->currentFileStatus($ciUploadId);
            $statusCode = $response->getStatusCode();

            // Display errors and skip to the next upload.
            if ($statusCode >= 400) {
                $io->caution($this->getDescriptionFromStatus($statusCode));
                $failures++;
                continue;
            }

            $upload->setStatus($statusCode);

            // If scan is still in progress no further processing is needed.
            if (Response::HTTP_ACCEPTED === $statusCode) {
                if ($triggerScanInProgress) {
                    $triggers[] = $triggerFactory->create(Trigger::SCAN_IN_PROGRESS);
                }
                $io->text(sprintf(
                    'Scan still in progress for %s.',
                    $upload->getCiUploadId()
                ));
            } else {

                // Map json response to model
                $statusResponse = $this->serializer->deserialize(
                    $response->getContent(),
                    StatusResponse::class,
                    'json'
                );

                $url = $statusResponse->getDetailsUrl();

                // Logic for triggers.
                if (Response::HTTP_OK === $statusCode) {
                    if ($triggerScanIsComplete) {
                        $triggers[] = $triggerFactory->create(Trigger::SCAN_IS_COMPLETE);
                    }

                    if ($statusResponse->getVulnerabilitiesFound() > $triggerVulnerabilitiesGreaterThan and
                        null !== $triggerVulnerabilitiesGreaterThan
                    ) {
                        $triggers[] = $triggerFactory->create(
                            Trigger::VULNERABILITIES_GREATER_THAN,
                            $triggerVulnerabilitiesGreaterThan,
                            $statusResponse->getVulnerabilitiesFound()
                        );
                    }

                    if ($statusResponse->getMaxCvss() > $triggerCvssGreaterThan and
                        null !== $triggerCvssGreaterThan
                    ) {
                        $triggers[] = $triggerFactory->create(
                            Trigger::CVSS_GREATER_THAN,
                            $triggerCvssGreaterThan,
                            $statusResponse->getMaxCvss()
                        );
                    }
                }

                $io->text(sprintf(
                    'Scan finished for %s.',
                    $upload->getCiUploadId()
                ));
            }

            if (0 !== count($triggers)) {
                $this->renderTable($output, $triggers);
                // Send email if there are triggers and a email action.
                if ($actionEmail) {
                    $this->scanMessenger->sendTriggeredEmail(
                        $upload,
                        $triggers,
                        $url
                    );
                }
            }
        }

        // If no uploads are successful, then error the command
        if ($numOfUploads === $failures) {
            $io->error('None successful.');
            return Command::FAILURE;
        }

        $io->success('Finished.');
        return Command::SUCCESS;
    }

    /**
     * @param Output $output
     * @param array $triggers
     * @return void
     */
    private function renderTable(Output $output, array $triggers)
    {
        $table = new Table($output);
        $table->setHeaders(['Type', 'Criteria', 'Value']);

        foreach($triggers as $trigger) {
            $table->addRow([
                $trigger->getType(),
                $trigger->getCriteria(),
                $trigger->getValue()
            ]);
        }

        $table->render();
    }
}
