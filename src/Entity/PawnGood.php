<?php

namespace App\Entity;

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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $estimatedValue;

    #[ORM\Column(type: 'string', length: 12)]
    private string $goodType;

    #[ORM\ManyToOne(targetEntity: PawnGoodCategory::class, inversedBy: 'pawnGoods')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    private PawnGoodCategory $category;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
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
