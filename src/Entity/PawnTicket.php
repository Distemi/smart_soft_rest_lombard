<?php

namespace App\Entity;

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
#[ORM\Index(name: 'idx_ticket_open_issue_date', columns: ['issue_date'], options: ['where' => 'status IN (2, 3, 4)'])]
class PawnTicket
{
    public const int STATUS_FOR_SALE = 1;
    public const int STATUS_OPEN = 2;
    public const int STATUS_OVERDUE = 3;
    public const int STATUS_OVERDUE_READY_FOR_SALE = 4;
    public const int STATUS_CLOSED = 5;

    public const array OPEN_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_OVERDUE,
        self::STATUS_OVERDUE_READY_FOR_SALE,
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

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'pawnTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\Column(type: 'integer')]
    private int $status;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $issueDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $closeDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $pledgeAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $loanAmount = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $interestRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $currentDebt = null;

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
        $this->status = self::STATUS_OPEN;
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
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
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

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(?string $interestRate): static
    {
        $this->interestRate = $interestRate;
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
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_FOR_SALE => 'На реализации',
            self::STATUS_OPEN => 'Открыт',
            self::STATUS_OVERDUE => 'Просрочен',
            self::STATUS_OVERDUE_READY_FOR_SALE => 'Просрочен (готов к продаже)',
            self::STATUS_CLOSED => 'Закрыт',
            default => 'Неизвестно',
        };
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
