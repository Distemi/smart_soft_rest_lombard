<?php

namespace App\Entity;

use App\Repository\PawnGoodCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PawnGoodCategoryRepository::class)]
#[ORM\Table(name: 'pawn_good_categories')]
class PawnGoodCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $systemCategory = false;

    #[ORM\OneToMany(targetEntity: PawnGood::class, mappedBy: 'category')]
    private Collection $pawnGoods;

    public function __construct()
    {
        $this->pawnGoods = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function isSystemCategory(): bool
    {
        return $this->systemCategory;
    }

    public function setSystemCategory(bool|int|string|null $systemCategory): static
    {
        $this->systemCategory = filter_var($systemCategory, FILTER_VALIDATE_BOOL);

        return $this;
    }

    public function getPawnGoods(): Collection
    {
        return $this->pawnGoods;
    }
}
