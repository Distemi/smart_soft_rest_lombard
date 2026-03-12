<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PawnTicket::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'pawn_ticket_number', referencedColumnName: 'ticket_number', nullable: false)]
    #[ORM\JoinColumn(name: 'pawn_ticket_workplace_id', referencedColumnName: 'workplace_id', nullable: false)]
    private ?PawnTicket $pawnTicket = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $paymentType = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPawnTicket(): ?PawnTicket
    {
        return $this->pawnTicket;
    }

    public function setPawnTicket(?PawnTicket $pawnTicket): static
    {
        $this->pawnTicket = $pawnTicket;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentDate(): ?DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): static
    {
        $this->paymentType = $paymentType;
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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
