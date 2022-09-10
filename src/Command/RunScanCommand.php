<?php

namespace App\Command;

use App\Entity\Upload;
use App\Messenger\ScanMessenger;
use App\Traits\BadResponseTrait;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use Model\StatusResponse;
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
use \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


/**
 *  Checks the status on Debricked and triggers actions based on rules.
 */
#[AsCommand(
    name: 'app:run-scan',
    description: 'Add a short description for your command',
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
            ->addOption('trigger_scan_in_progress', 'tsip', InputOption::VALUE_OPTIONAL, 'Trigger when a scan is in progress.', 0)
            ->addOption('trigger_scan_is_complete', 'tsic', InputOption::VALUE_OPTIONAL, 'Trigger when a scan is complete.', 0)
            ->addOption('trigger_vulnerabilities_greater_than', 'tvgt', InputOption::VALUE_OPTIONAL, 'Trigger when vulnerabilities are greater than', null)
            ->addOption('trigger_cvss_greater_than', 'tcgt', InputOption::VALUE_OPTIONAL, 'Trigger when CVSS\'s are greater than', null)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $actionEmail = (bool) $input->getOption('action_email');
        $triggerScanInProgress = (bool) $input->getOption('trigger_scan_in_progress');
        $triggerScanIsComplete = (bool) $input->getOption('trigger_scan_is_complete');
        $triggerVulnerabilitiesGreaterThan = $input->getOption('trigger_vulnerabilities_greater_than');
        $triggerCvssGreaterThan = $input->getOption('trigger_vulnerabilities_greater_than');

        $em = $this->em;

        $uploads = $em->getRepository(Upload::class)->findAllUnscanned();

        $numOfUploads = count($uploads);

        $io->comment(sprintf(
            'Scanning %s file(s).',
            $numOfUploads
        ));

        if ($numOfUploads) {
            $io->success('Finished, no files needed scanning.');
            return Command::SUCCESS;
        }

        $failures = 0;

        foreach ($uploads as $upload) {
            $response = $this->apiUtil->currentFileStatus($upload->getCiUploadId());
            $statusCode = $response->getStatusCode();

            // Display errors and skip to the next upload.
            if ($statusCode >= 400) {
                $io->error($this->getDescriptionFromStatus($statusCode));
                $failures++;
                continue;
            }

            $upload->setStatus($statusCode);

            if (Response::HTTP_ACCEPTED === $statusCode) {
                if ($triggerScanInProgress and $actionEmail) {
                    // Action here;
                }
                // Scan in progress still, so skip.
                continue;
            }

            // Map json response to model
            $statusResponse = $this->serializer->deserialize(
                $response->getContent(),
                StatusResponse::class,
                'json',
                [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true]
            );

            // Trigger logic only needed if scan is complete and there is an action.
            if (Response::HTTP_OK === $statusCode and $actionEmail) {
                if ($triggerScanIsComplete) {
                    // Action here;
                }

                if ($triggerVulnerabilitiesGreaterThan > $statusResponse->getVulnerabilitiesFound()) {
                    // Action here;
                }

                if ($triggerCvssGreaterThan > $statusResponse->getMaxCvss()) {
                    // Action here;
                }
            }

            $io->success(sprintf(
                'Scan finished for %s.',
                $upload->getCiUploadId()
            ));

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
