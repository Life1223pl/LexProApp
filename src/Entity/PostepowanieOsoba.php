<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PostepowanieOsobaRepository;

#[ORM\Entity(repositoryClass: PostepowanieOsobaRepository::class)]
#[ORM\Table(name: 'postepowanie_osoba')]
#[ORM\UniqueConstraint(name: 'uniq_post_osoba_rola', columns: ['postepowanie_id', 'osoba_id', 'rola'])]
class PostepowanieOsoba
{
    public const ROLA_PODEJRZANY = 'PODEJRZANY';
    public const ROLA_SWIADEK = 'SWIADEK';
    public const ROLA_POKRZYWDZONY = 'POKRZYWDZONY';
    public const ROLA_INNA = 'INNA';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'osoby')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Postepowanie $postepowanie;

    #[ORM\ManyToOne(inversedBy: 'udzialy')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Osoba $osoba;

    #[ORM\Column(length: 30)]
    private string $rola;

// specyfikacje dla roli
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $stosunekDoPodejrzanego = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $stosunekDoPokrzywdzonego = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $pouczonyOdpowiedzialnoscKarna = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notatki = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->rola = self::ROLA_INNA;
    }

    public function getId(): ?int { return $this->id; }

    public function getPostepowanie(): Postepowanie { return $this->postepowanie; }
    public function setPostepowanie(Postepowanie $p): static { $this->postepowanie = $p; return $this; }

    public function getOsoba(): Osoba { return $this->osoba; }
    public function setOsoba(Osoba $o): static { $this->osoba = $o; return $this; }

    public function getRola(): string { return $this->rola; }
    public function setRola(string $rola): static { $this->rola = $rola; return $this; }

    public function getStosunekDoPodejrzanego(): ?string { return $this->stosunekDoPodejrzanego; }
    public function setStosunekDoPodejrzanego(?string $v): static { $this->stosunekDoPodejrzanego = $v; return $this; }

    public function getStosunekDoPokrzywdzonego(): ?string { return $this->stosunekDoPokrzywdzonego; }
    public function setStosunekDoPokrzywdzonego(?string $v): static { $this->stosunekDoPokrzywdzonego = $v; return $this; }

    public function isPouczonyOdpowiedzialnoscKarna(): bool { return $this->pouczonyOdpowiedzialnoscKarna; }
    public function setPouczonyOdpowiedzialnoscKarna(bool $v): static { $this->pouczonyOdpowiedzialnoscKarna = $v; return $this; }

    public function getNotatki(): ?string { return $this->notatki; }
    public function setNotatki(?string $n): static { $this->notatki = $n; return $this; }
}

