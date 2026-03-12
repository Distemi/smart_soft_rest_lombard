<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['external_id'], name: 'idx_client_external_id')]
#[ORM\Index(columns: ['surname', 'name', 'patronymic'], name: 'idx_client_fullname')]
class Client implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $externalId = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $surname = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $patronymic = null;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $passportSeries = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $passportNumber = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt;

    #[ORM\OneToMany(targetEntity: PawnTicket::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $pawnTickets;

    public function __construct()
    {
        $this->pawnTickets = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): static
    {
        $this->patronymic = $patronymic;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassportSeries(): ?string
    {
        return $this->passportSeries;
    }

    public function setPassportSeries(?string $passportSeries): static
    {
        $this->passportSeries = $passportSeries;
        return $this;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function setPassportNumber(?string $passportNumber): static
    {
        $this->passportNumber = $passportNumber;
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

    public function getPawnTickets(): Collection
    {
        return $this->pawnTickets;
    }

    public function addPawnTicket(PawnTicket $pawnTicket): static
    {
        if (!$this->pawnTickets->contains($pawnTicket)) {
            $this->pawnTickets->add($pawnTicket);
            $pawnTicket->setClient($this);
        }

        return $this;
    }

    public function removePawnTicket(PawnTicket $pawnTicket): static
    {
        $this->pawnTickets->removeElement($pawnTicket);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_CLIENT'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getFullName(): string
    {
        $parts = [$this->surname, $this->name];
        if ($this->patronymic) {
            $parts[] = $this->patronymic;
        }
        return implode(' ', $parts);
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
