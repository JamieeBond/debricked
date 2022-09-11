<?php

namespace App\Command;

use App\Entity\Upload;
use App\Messenger\ScanMessenger;
use App\Model\Trigger;
use App\Traits\BadResponseTrait;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\StatusResponse;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
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

    private ScanMessenger $scanMessenger;

    /**
     * @param EntityManagerInterface $em
     * @param DebrickedApiUtil $apiUtil
     * @param SerializerInterface $serializer
     * @param ScanMessenger $scanMessenger
     */
    public function __construct(EntityManagerInterface $em, DebrickedApiUtil $apiUtil, SerializerInterface $serializer, ScanMessenger $scanMessenger)
    {
        $this->em = $em;
        $this->apiUtil = $apiUtil;
        $this->serializer = $serializer;
        $this->scanMessenger = $scanMessenger;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption('action_email', 'a', InputOption::VALUE_OPTIONAL, 'Send email when a trigger goes off.', 0)
            ->addOption('trigger_scan_in_progress', 'tsip', InputOption::VALUE_OPTIONAL, Trigger::SCAN_IN_PROGRESS, 0)
            ->addOption('trigger_scan_is_complete', 'tsic', InputOption::VALUE_OPTIONAL, Trigger::SCAN_IS_COMPLETE, 0)
            ->addOption('trigger_vulnerabilities_greater_than', 'tvgt', InputOption::VALUE_OPTIONAL, Trigger::VULNERABILITIES_GREATER_THAN, null)
            ->addOption('trigger_cvss_greater_than', 'tcgt', InputOption::VALUE_OPTIONAL, Trigger::CVSS_GREATER_THAN, null)
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

        $io->comment($this->getDescription());

        $actionEmail = (bool) $input->getOption('action_email');
        $triggerScanInProgress = (bool) $input->getOption('trigger_scan_in_progress');
        $triggerScanIsComplete = (bool) $input->getOption('trigger_scan_is_complete');
        $triggerVulnerabilitiesGreaterThan = $input->getOption('trigger_vulnerabilities_greater_than');
        $triggerCvssGreaterThan = $input->getOption('trigger_cvss_greater_than');

        $em = $this->em;

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
                    $triggers[] = new Trigger(Trigger::SCAN_IN_PROGRESS);
                }
                $io->success(sprintf(
                    'Scan still in progress for %s.',
                    $upload->getCiUploadId()
                ));
            } else {
                // Map json response to model
                $statusResponse = $this->serializer->deserialize(
                    $response->getContent(),
                    StatusResponse::class,
                    'json',
                    [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true]
                );

                // Logic for triggers.
                if (Response::HTTP_OK === $statusCode) {
                    if ($triggerScanIsComplete) {
                        $triggers[] = new Trigger(Trigger::SCAN_IS_COMPLETE);
                    }

                    if ($statusResponse->getVulnerabilitiesFound() > $triggerVulnerabilitiesGreaterThan and
                        null !== $triggerVulnerabilitiesGreaterThan
                    ) {
                        $triggers[] = new Trigger(
                            Trigger::VULNERABILITIES_GREATER_THAN,
                            $triggerVulnerabilitiesGreaterThan,
                            $statusResponse->getVulnerabilitiesFound()
                        );
                    }

                    if ($statusResponse->getMaxCvss() > $triggerCvssGreaterThan and
                        null !== $triggerCvssGreaterThan
                    ) {
                        $triggers[] = new Trigger(
                            Trigger::CVSS_GREATER_THAN,
                            $triggerCvssGreaterThan,
                            $statusResponse->getMaxCvss()
                        );
                    }
                }

                $io->success(sprintf(
                    'Scan finished for %s.',
                    $upload->getCiUploadId()
                ));
            }

            // Send email if there are triggers and a email action.
            if (0 !== count($triggers) and $actionEmail) {
                $this->scanMessenger->sendTriggeredEmail(
                    $upload,
                    $triggers
                );
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
}
