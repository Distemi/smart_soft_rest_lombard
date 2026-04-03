<?php

namespace App\Entity;

use App\Enum\PawnGoodStatus;
use App\Repository\PawnGoodRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PawnGoodRepository::class)]
#[ORM\Table(name: 'pawn_goods')]
#[ORM\HasLifecycleCallbacks]
class PawnGood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PawnTicket::class, inversedBy: 'pawnGoods')]
    #[ORM\JoinColumn(name: 'pawn_ticket_number', referencedColumnName: 'ticket_number', nullable: false)]
    #[ORM\JoinColumn(name: 'pawn_ticket_workplace_id', referencedColumnName: 'workplace_id', nullable: false)]
    private ?PawnTicket $pawnTicket = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $externalId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $article = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $estimatedValue;

    #[ORM\ManyToOne(targetEntity: Currency::class, inversedBy: 'pawnGoods')]
    #[ORM\JoinColumn(name: 'currency_code', referencedColumnName: 'code', nullable: true)]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'string', length: 12)]
    private string $goodType;

    #[ORM\ManyToOne(targetEntity: PawnGoodCategory::class, inversedBy: 'pawnGoods')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    private PawnGoodCategory $category;

    #[ORM\ManyToOne(targetEntity: Workplace::class)]
    #[ORM\JoinColumn(name: 'workplace_id', nullable: true)]
    private ?Workplace $workplace = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $storage = null;

    #[ORM\Column(type: 'integer', enumType: PawnGoodStatus::class)]
    private PawnGoodStatus $status;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $testOperation = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $imagesLinks = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $jewelryExtra = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $vehicleExtra = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->status = PawnGoodStatus::UNKNOWN;
    }

    public function getId(): int
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getArticle(): ?int
    {
        return $this->article;
    }

    public function setArticle(?int $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEstimatedValue(): string
    {
        return $this->estimatedValue;
    }

    public function setEstimatedValue(string $estimatedValue): static
    {
        $this->estimatedValue = $estimatedValue;
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

    public function getGoodType(): string
    {
        return $this->goodType;
    }

    public function setGoodType(string $goodType): static
    {
        $this->goodType = $goodType;
        return $this;
    }

    public function getCategory(): PawnGoodCategory
    {
        return $this->category;
    }

    public function setCategory(PawnGoodCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getWorkplace(): ?Workplace
    {
        return $this->workplace;
    }

    public function setWorkplace(?Workplace $workplace): static
    {
        $this->workplace = $workplace;

        return $this;
    }

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function setStorage(?string $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status->value;
    }

    public function getStatusEnum(): PawnGoodStatus
    {
        return $this->status;
    }

    public function setStatus(int|PawnGoodStatus|null $status): static
    {
        $this->status = $status instanceof PawnGoodStatus
            ? $status
            : (PawnGoodStatus::tryFrom((int) $status) ?? PawnGoodStatus::UNKNOWN);

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getImagesLinks(): ?array
    {
        return $this->imagesLinks;
    }

    public function setImagesLinks(?array $imagesLinks): static
    {
        $this->imagesLinks = $imagesLinks;

        return $this;
    }

    public function getJewelryExtra(): ?array
    {
        return $this->jewelryExtra;
    }

    public function setJewelryExtra(?array $jewelryExtra): static
    {
        $this->jewelryExtra = $jewelryExtra;

        return $this;
    }

    public function getVehicleExtra(): ?array
    {
        return $this->vehicleExtra;
    }

    public function setVehicleExtra(?array $vehicleExtra): static
    {
        $this->vehicleExtra = $vehicleExtra;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
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
