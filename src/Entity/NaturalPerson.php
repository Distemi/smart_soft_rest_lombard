<?php

namespace App\Entity;

use App\Repository\NaturalPersonRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NaturalPersonRepository::class)]
#[ORM\Table(name: 'natural_persons')]
class NaturalPerson extends Client
{
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $birthDate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $actualAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $placeOfBirth = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $photoLink = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nationality = null;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $snils = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $warningMessage = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $loyaltyCardNumber = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $loyaltyCardDiscount = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonuses = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $adChannelId = null;

    public function getBirthDate(): ?DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
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

    public function getActualAddress(): ?string
    {
        return $this->actualAddress;
    }

    public function setActualAddress(?string $actualAddress): static
    {
        $this->actualAddress = $actualAddress;

        return $this;
    }

    public function getPlaceOfBirth(): ?string
    {
        return $this->placeOfBirth;
    }

    public function setPlaceOfBirth(?string $placeOfBirth): static
    {
        $this->placeOfBirth = $placeOfBirth;

        return $this;
    }

    public function getPhotoLink(): ?string
    {
        return $this->photoLink['full_size'] ?? $this->photoLink['preview'] ?? null;
    }

    public function setPhotoLink(?array $photoLink): static
    {
        $this->photoLink = $photoLink;

        return $this;
    }

    public function getPhotoLinks(): ?array
    {
        return $this->photoLink;
    }

    public function getNationality(): ?int
    {
        return $this->nationality;
    }

    public function setNationality(?int $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getSnils(): ?string
    {
        return $this->snils;
    }

    public function setSnils(?string $snils): static
    {
        $this->snils = $snils;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    public function getWarningMessage(): ?string
    {
        return $this->warningMessage;
    }

    public function setWarningMessage(?string $warningMessage): static
    {
        $this->warningMessage = $warningMessage;

        return $this;
    }

    public function getLoyaltyCardNumber(): ?string
    {
        return $this->loyaltyCardNumber;
    }

    public function setLoyaltyCardNumber(?string $loyaltyCardNumber): static
    {
        $this->loyaltyCardNumber = $loyaltyCardNumber;

        return $this;
    }

    public function getLoyaltyCardDiscount(): ?array
    {
        return $this->loyaltyCardDiscount;
    }

    public function setLoyaltyCardDiscount(?array $loyaltyCardDiscount): static
    {
        $this->loyaltyCardDiscount = $loyaltyCardDiscount;

        return $this;
    }

    public function getBonuses(): int
    {
        return $this->bonuses;
    }

    public function setBonuses(int $bonuses): static
    {
        $this->bonuses = $bonuses;

        return $this;
    }

    public function getAdChannelId(): ?int
    {
        return $this->adChannelId;
    }

    public function setAdChannelId(?int $adChannelId): static
    {
        $this->adChannelId = $adChannelId;

        return $this;
    }

}
