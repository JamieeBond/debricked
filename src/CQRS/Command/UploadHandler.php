<?php

namespace App\CQRS\Command;

use App\CQRS\CommandHandler;
use App\Entity\Upload;
use Doctrine\ORM\EntityManagerInterface;

class UploadHandler implements CommandHandler
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param UploadCommand $command
     * @return void
     */
    public function __invoke(UploadCommand $command): void
    {
        $fileNames = [];

        foreach($command->getFiles() as $file) {
            $fileNames[] = $file->getClientOriginalName();
        };

        $upload = new Upload(
            $command->getRepositoryName(),
            $command->getCommitName(),
            $fileNames
        );

        $em = $this->em;
        $em->persist($upload);
    }
}