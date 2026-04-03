<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
#[ORM\Table(name: 'currencies')]
class Currency
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 3)]
    private string $code;

    #[ORM\Column(type: 'string', length: 64)]
    private string $name;

    #[ORM\OneToMany(targetEntity: PawnTicket::class, mappedBy: 'currency')]
    private Collection $pawnTickets;

    #[ORM\OneToMany(targetEntity: PawnGood::class, mappedBy: 'currency')]
    private Collection $pawnGoods;

    public function __construct()
    {
        $this->pawnTickets = new ArrayCollection();
        $this->pawnGoods = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));

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

    public function getPawnTickets(): Collection
    {
        return $this->pawnTickets;
    }

    public function getPawnGoods(): Collection
    {
        return $this->pawnGoods;
    }
}
