<?php

namespace App\Entity;

use App\Repository\UploadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: UploadRepository::class)]
class Upload
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $uploadedOn;

    /**
     * @var int|null
     */
    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    /**
     * @var string
     */
    #[ORM\Column(length: 1000)]
    private string $ciUploadId;

    /**
     * @var string
     */
    #[ORM\Column(length: 1000)]
    private string $repositoryName;

    /**
     * @var string
     */
    #[ORM\Column(length: 1000)]
    private string $commitName;

    /**
     * @var array
     */
    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $files = [];

    /**
     * @param string $ciUploadId
     * @param string $repositoryName
     * @param string $commitName
     * @param array $files
     */
    public function __construct(string $ciUploadId, string $repositoryName, string $commitName, array $files)
    {
        $this->uploadedOn = New DateTime();
        $this->ciUploadId = $ciUploadId;
        $this->repositoryName = $repositoryName;
        $this->commitName = $commitName;
        $this->files = $files;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getUploadedOn(): DateTime
    {
        return $this->uploadedOn;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     * @return $this
     */
    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getCiUploadId(): string
    {
        return $this->ciUploadId;
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
}