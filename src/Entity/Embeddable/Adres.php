<?php

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Adres
{
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ulica = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $nrDomu = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $nrLokalu = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $kodPocztowy = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $miejscowosc = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $kraj = 'Polska';

    public function getUlica(): ?string { return $this->ulica; }
    public function setUlica(?string $ulica): static { $this->ulica = $ulica; return $this; }

    public function getNrDomu(): ?string { return $this->nrDomu; }
    public function setNrDomu(?string $nrDomu): static { $this->nrDomu = $nrDomu; return $this; }

    public function getNrLokalu(): ?string { return $this->nrLokalu; }
    public function setNrLokalu(?string $nrLokalu): static { $this->nrLokalu = $nrLokalu; return $this; }

    public function getKodPocztowy(): ?string { return $this->kodPocztowy; }
    public function setKodPocztowy(?string $kodPocztowy): static { $this->kodPocztowy = $kodPocztowy; return $this; }

    public function getMiejscowosc(): ?string { return $this->miejscowosc; }
    public function setMiejscowosc(?string $miejscowosc): static { $this->miejscowosc = $miejscowosc; return $this; }

    public function getKraj(): ?string { return $this->kraj; }
    public function setKraj(?string $kraj): static { $this->kraj = $kraj; return $this; }

    public function __toString(): string
    {
        $parts = array_filter([
            $this->ulica,
            $this->nrDomu ? (' '.$this->nrDomu) : null,
            $this->nrLokalu ? ('/'.$this->nrLokalu) : null,
            $this->kodPocztowy,
            $this->miejscowosc,
            $this->kraj,
        ]);

        return trim(implode(' ', $parts));
    }
}
