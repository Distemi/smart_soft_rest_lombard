<?php

namespace App\Entity;

use App\Repository\LegalPersonRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalPersonRepository::class)]
#[ORM\Table(name: 'legal_persons')]
class LegalPerson extends Client
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $legalAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $directorFio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $chiefAccountantFio = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kpp = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $ogrn = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $okpo = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $oktmo = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $adChannelId = null;

    public function getLegalAddress(): ?string
    {
        return $this->legalAddress;
    }

    public function setLegalAddress(?string $legalAddress): static
    {
        $this->legalAddress = $legalAddress;

        return $this;
    }

    public function getDirectorFio(): ?string
    {
        return $this->directorFio;
    }

    public function setDirectorFio(?string $directorFio): static
    {
        $this->directorFio = $directorFio;

        return $this;
    }

    public function getChiefAccountantFio(): ?string
    {
        return $this->chiefAccountantFio;
    }

    public function setChiefAccountantFio(?string $chiefAccountantFio): static
    {
        $this->chiefAccountantFio = $chiefAccountantFio;

        return $this;
    }

    public function getKpp(): ?string
    {
        return $this->kpp;
    }

    public function setKpp(?string $kpp): static
    {
        $this->kpp = $kpp;

        return $this;
    }

    public function getOgrn(): ?string
    {
        return $this->ogrn;
    }

    public function setOgrn(?string $ogrn): static
    {
        $this->ogrn = $ogrn;

        return $this;
    }

    public function getOkpo(): ?string
    {
        return $this->okpo;
    }

    public function setOkpo(?string $okpo): static
    {
        $this->okpo = $okpo;

        return $this;
    }

    public function getOktmo(): ?string
    {
        return $this->oktmo;
    }

    public function setOktmo(?string $oktmo): static
    {
        $this->oktmo = $oktmo;

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

    public function getFullName(): string
    {
        return $this->getName() ?? '';
    }
}
