<?php

namespace App\Entity;

use App\Enum\PawnTicketStatus;
use App\Repository\PawnTicketRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PawnTicketRepository::class)]
#[ORM\Table(name: 'pawn_tickets')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ticket_external_id', columns: ['external_id'])]
#[ORM\Index(name: 'idx_ticket_status', columns: ['status'])]
#[ORM\Index(name: 'idx_ticket_client_issue_date', columns: ['client_id', 'issue_date'])]
#[ORM\Index(name: 'idx_ticket_workplace_status_due', columns: ['workplace_id', 'status', 'due_date'])]
#[ORM\Index(name: 'idx_ticket_open_issue_date', columns: ['issue_date'], options: ['where' => '(status = ANY (ARRAY[2, 3, 4]))'])]
class PawnTicket
{
    public const array OPEN_STATUSES = [
        PawnTicketStatus::OPEN->value,
        PawnTicketStatus::OVERDUE->value,
        PawnTicketStatus::OVERDUE_READY_FOR_SALE->value,
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 10)]
    private string $ticketNumber;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Workplace::class, inversedBy: 'pawnTickets')]
    #[ORM\JoinColumn(name: 'workplace_id')]
    private Workplace $workplace;

    #[ORM\Column(type: 'integer')]
    private int $externalId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pawnChainId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tariffId = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'pawnTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\Column(type: 'integer', enumType: PawnTicketStatus::class)]
    private PawnTicketStatus $status;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $issueDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $closeDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $pledgeAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $loanAmount = null;

    #[ORM\ManyToOne(targetEntity: Currency::class, inversedBy: 'pawnTickets')]
    #[ORM\JoinColumn(name: 'currency_code', referencedColumnName: 'code', nullable: true)]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $interestRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $paidPercents = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $currentDebt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $pawnTicketDebt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $testOperation = false;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    #[ORM\OneToMany(targetEntity: PawnGood::class, mappedBy: 'pawnTicket', cascade: ['persist'], orphanRemoval: true)]
    private Collection $pawnGoods;

    public function __construct()
    {
        $this->pawnGoods = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->status = PawnTicketStatus::OPEN;
    }

    public function getExternalId(): int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getPawnChainId(): ?int
    {
        return $this->pawnChainId;
    }

    public function setPawnChainId(?int $pawnChainId): static
    {
        $this->pawnChainId = $pawnChainId;

        return $this;
    }

    public function getTariffId(): ?int
    {
        return $this->tariffId;
    }

    public function setTariffId(?int $tariffId): static
    {
        $this->tariffId = $tariffId;

        return $this;
    }

    public function getTicketNumber(): string
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber(string $ticketNumber): static
    {
        $this->ticketNumber = $ticketNumber;
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status->value;
    }

    public function getStatusEnum(): PawnTicketStatus
    {
        return $this->status;
    }

    public function setStatus(int|PawnTicketStatus $status): static
    {
        $this->status = $status instanceof PawnTicketStatus
            ? $status
            : (PawnTicketStatus::tryFrom($status) ?? PawnTicketStatus::OPEN);
        return $this;
    }

    public function getIssueDate(): ?DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(?DateTimeInterface $issueDate): static
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getCloseDate(): ?DateTimeInterface
    {
        return $this->closeDate;
    }

    public function setCloseDate(?DateTimeInterface $closeDate): static
    {
        $this->closeDate = $closeDate;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPledgeAmount(): ?string
    {
        return $this->pledgeAmount;
    }

    public function setPledgeAmount(?string $pledgeAmount): static
    {
        $this->pledgeAmount = $pledgeAmount;
        return $this;
    }

    public function getLoanAmount(): ?string
    {
        return $this->loanAmount;
    }

    public function setLoanAmount(?string $loanAmount): static
    {
        $this->loanAmount = $loanAmount;
        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currency?->getCode();
    }

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(?string $interestRate): static
    {
        $this->interestRate = $interestRate;
        return $this;
    }

    public function getPaidPercents(): ?string
    {
        return $this->paidPercents;
    }

    public function setPaidPercents(?string $paidPercents): static
    {
        $this->paidPercents = $paidPercents;

        return $this;
    }

    public function getCurrentDebt(): ?string
    {
        return $this->currentDebt;
    }

    public function setCurrentDebt(?string $currentDebt): static
    {
        $this->currentDebt = $currentDebt;
        return $this;
    }

    public function getPawnTicketDebt(): ?array
    {
        return $this->pawnTicketDebt;
    }

    public function setPawnTicketDebt(?array $pawnTicketDebt): static
    {
        $this->pawnTicketDebt = $pawnTicketDebt;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function isTestOperation(): bool
    {
        return $this->testOperation;
    }

    public function setTestOperation(bool|int|string|null $testOperation): static
    {
        $this->testOperation = filter_var($testOperation, FILTER_VALIDATE_BOOL);

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getWorkplace(): Workplace
    {
        return $this->workplace;
    }

    public function setWorkplace(Workplace $workplace): static
    {
        $this->workplace = $workplace;
        return $this;
    }

    public function getPawnGoods(): Collection
    {
        return $this->pawnGoods;
    }

    public function addPawnGood(PawnGood $pawnGood): static
    {
        if (!$this->pawnGoods->contains($pawnGood)) {
            $this->pawnGoods->add($pawnGood);
            $pawnGood->setPawnTicket($this);
        }

        return $this;
    }

    public function removePawnGood(PawnGood $pawnGood): static
    {
        if ($this->pawnGoods->removeElement($pawnGood)) {
            if ($pawnGood->getPawnTicket() === $this) {
                $pawnGood->setPawnTicket(null);
            }
        }

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
