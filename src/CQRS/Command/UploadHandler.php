<?php

namespace App\CQRS\Command;

use App\CQRS\CommandHandler;
use App\Entity\Upload;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;


/**
 * UploadHandler to forward files to debricked and record the upload in the db.
 */
class UploadHandler implements CommandHandler
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var DebrickedApiUtil
     */
    private DebrickedApiUtil $apiUtil;

    /**
     * @param EntityManagerInterface $em
     * @param DebrickedApiUtil $apiUtil
     */
    public function __construct(EntityManagerInterface $em, DebrickedApiUtil $apiUtil)
    {
        $this->em = $em;
        $this->apiUtil = $apiUtil;
    }

    /**
     * @param UploadCommand $command
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __invoke(UploadCommand $command): Upload
    {
        $repositoryName = $command->getRepositoryName();
        $commitName = $command->getCommitName();

        $fileNames = [];
        $firstCiUploadId = null;

        foreach($command->getFiles() as $file) {
            $fileNames[] = $file->getClientOriginalName();
            $ciUploadId = $this->apiUtil->uploadDependencyFile(
                $repositoryName,
                $commitName,
                $file->getPathname(),
                $firstCiUploadId
            );

            // The first ciUploadId is required for the remaining files.
            if (null === $firstCiUploadId) {
                $firstCiUploadId = $ciUploadId;
            }
        }

        // Once all files are uploaded, the files need to be concluded.
        $this->apiUtil->concludeFileUpload(
            $firstCiUploadId
        );

        $upload = new Upload(
            $firstCiUploadId,
            $command->getRepositoryName(),
            $command->getCommitName(),
            $fileNames
        );

        $this->em->persist($upload);

        return $upload;
    }
}