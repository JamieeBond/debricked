<?php

namespace App\CQRS\Command;

use App\CQRS\CommandHandler;
use App\Entity\Upload;
use App\HttpClient\AuthenticatedHttpClient;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class UploadHandler implements CommandHandler
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    private DebrickedApiUtil $apiUtil;


    public function __construct(EntityManagerInterface $em, DebrickedApiUtil $apiUtil)
    {
        $this->em = $em;
        $this->apiUtil = $apiUtil;
    }

    /**
     * @param UploadCommand $command
     * @return void
     */
    public function __invoke(UploadCommand $command): void
    {
        $repositoryName = $command->getRepositoryName();
        $commitName = $command->getCommitName();

        $fileNames = [];
        $ciUploadId = null;

        foreach($command->getFiles() as $file) {
            $fileNames[] = $file->getClientOriginalName();
            $ciUploadId = $this->apiUtil->uploadDependencyFile(
                $repositoryName,
                $commitName,
                $file->getPathname(),
                $ciUploadId
            );

        }

        $this->apiUtil->concludeFileUpload(
            $ciUploadId
        );

        $upload = new Upload(
            $ciUploadId,
            $command->getRepositoryName(),
            $command->getCommitName(),
            $fileNames
        );

        $this->em->persist($upload);
    }
}