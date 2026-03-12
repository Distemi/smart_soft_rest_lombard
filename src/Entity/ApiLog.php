<?php

namespace App\Entity;

use App\Repository\ApiLogRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiLogRepository::class)]
#[ORM\Table(name: 'api_logs')]
#[ORM\Index(columns: ['created_at'], name: 'idx_apilog_created')]
class ApiLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $endpoint = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $requestSummary = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $responseSummary = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $statusCode = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getRequestSummary(): ?string
    {
        return $this->requestSummary;
    }

    public function setRequestSummary(?string $requestSummary): static
    {
        $this->requestSummary = $requestSummary;
        return $this;
    }

    public function getResponseSummary(): ?string
    {
        return $this->responseSummary;
    }

    public function setResponseSummary(?string $responseSummary): static
    {
        $this->responseSummary = $responseSummary;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
