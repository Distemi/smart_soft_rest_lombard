<?php

namespace App\Entity;

use App\Repository\WorkplaceRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkplaceRepository::class)]
#[ORM\Table(name: 'workplaces')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_workplace_external_id', columns: ['external_id'])]
#[ORM\Index(name: 'idx_workplace_active', columns: ['title', 'city'], options: ['where' => 'is_active = true'])]
class Workplace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', unique: true)]
    private ?int $externalId = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $updatedAt;

    #[ORM\OneToMany(targetEntity: PawnTicket::class, mappedBy: 'workplace')]
    private Collection $pawnTickets;

    public function __construct()
    {
        $this->pawnTickets = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getDisplayTitle(): string
    {
        if (!empty($this->title)) {
            return $this->title;
        }

        if (!empty($this->city)) {
            return $this->city;
        }

        return $this->externalId !== null ? 'Филиал ' . $this->externalId : '';
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    /**
     * @return Collection<int, PawnTicket>
     */
    public function getPawnTickets(): Collection
    {
        return $this->pawnTickets;
    }

    /**
     * Получить уникальных клиентов через залоговые билеты
     * @return array<Client>
     */
    public function getUniqueClients(): array
    {
        $clients = [];
        foreach ($this->pawnTickets as $ticket) {
            $client = $ticket->getClient();
            if ($client && !isset($clients[$client->getId()])) {
                $clients[$client->getId()] = $client;
            }
        }
        return array_values($clients);
    }

    public function __toString(): string
    {
        return $this->getDisplayTitle();
    }
}
