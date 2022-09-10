<?php

namespace App\CQRS\Command;

use App\CQRS\Command;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * UploadCommand command with validation to validate before uploading to debricked.
 */
class UploadCommand implements Command
{
    /**
     * @var string|null
     */
    private ?string $repositoryName = null;

    /**
     * @var string|null
     */
    private ?string $commitName = null;

    /**
     * @var array|null
     */
    private ?array $files = null;

    /**
     * @param string|null $repositoryName
     * @param string|null $commitName
     * @param array|null $files
     */
    public function __construct(?string $repositoryName, ?string $commitName, ?array $files)
    {
        $this->repositoryName = $repositoryName;
        $this->commitName = $commitName;
        $this->files = $files;
    }

    /**
     * @return string
     */
    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    /**
     * @return string
     */
    public function getCommitName(): string
    {
        return $this->commitName;
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param ClassMetadata $metadata
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('commitName', new Assert\NotBlank());
        $metadata->addPropertyConstraint('repositoryName', new Assert\NotBlank());
        $metadata->addPropertyConstraint('files', new Assert\NotBlank());
        $metadata->addPropertyConstraint('files', new Assert\Count([
            'min' => 1,
        ]));
        $metadata->addPropertyConstraint('files', new Assert\All([
            'constraints' => [
                new Assert\Type([
                    'type' => File::class
                ]),
            ],
        ]));
    }
}