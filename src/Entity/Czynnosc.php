<?php

namespace App\Entity;

use App\Entity\Embeddable\Adres;
use App\Repository\CzynnoscRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CzynnoscRepository::class)]
#[ORM\Table(name: 'czynnosc')]
#[ORM\Index(columns: ['typ'], name: 'idx_czynnosc_typ')]
#[ORM\Index(columns: ['data_start'], name: 'idx_czynnosc_data_start')]
class Czynnosc
{
    // PRZESZUKANIA
    public const TYP_PRZESZUKANIE = 'PRZESZUKANIE'; // lokal/pomieszczenie
    public const TYP_PRZESZUKANIE_OSOBY = 'PRZESZUKANIE_OSOBY';
    public const TYP_PRZESZUKANIE_URZADZENIA = 'PRZESZUKANIE_URZADZENIA';

    //  ZATRZYMANIA
    public const TYP_ZATRZYMANIE_RZECZY = 'ZATRZYMANIE_RZECZY';
    public const TYP_ZATRZYMANIE_OSOBY = 'ZATRZYMANIE_OSOBY';

    //  OGLĘDZINY / MIEJSCE ZDARZENIA
    public const TYP_OGLEDZINY = 'OGLEDZINY';

    // PRZESŁUCHANIA
    public const TYP_PRZESLUCHANIE_SWIADKA = 'PRZESLUCHANIE_SWIADKA';
    public const TYP_PRZESLUCHANIE_PODEJRZANEGO = 'PRZESLUCHANIE_PODEJRZANEGO';

    // INNE CZYNNOŚCI
    public const TYP_KONFRONTACJA = 'KONFRONTACJA';
    public const TYP_POBRANIE_MATERIALU = 'POBRANIE_MATERIALU';
    public const TYP_ZAZNAJOMIENIE_Z_AKTAMI = 'ZAZNAJOMIENIE_Z_AKTAMI';
    public const TYP_TESTER_NARKOTYKOWY = 'TESTER_NARKOTYKOWY';

    public const TYP_ZAWIADOMIENIE = "ZAWIADOMIENIE";

    public const TYP_INNA = 'INNA';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'czynnosci')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Postepowanie $postepowanie;

    #[ORM\Column(length: 60)]
    private string $typ = self::TYP_INNA;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dataStart = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dataKoniec = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $miejsceOpis = null;

    #[ORM\Embedded(class: Adres::class, columnPrefix: 'miejsce_')]
    private Adres $miejsceAdres;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podstawaPrawna = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tresc = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $zalacznikiOpis = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $rejestrowana = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejestracjaOpis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $operatorRejestracjiOpis = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $spisRzeczy = null;

    /**
     * @var Collection<int, CzynnoscUczestnik>
     */
    #[ORM\OneToMany(
        mappedBy: 'czynnosc',
        targetEntity: CzynnoscUczestnik::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $uczestnicy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dane = null;

    #[ORM\ManyToOne(targetEntity: PostepowanieOsoba::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?PostepowanieOsoba $glownaOsoba = null;

    public function __construct()
    {
        $this->miejsceAdres = new Adres();
        $this->uczestnicy = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->dane = [];

    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ===== LISTA DOSTĘPNYCH TYPÓW =====
    public static function getDostepneTypy(): array
    {
        return [
            self::TYP_PRZESZUKANIE,
            self::TYP_PRZESZUKANIE_OSOBY,
            self::TYP_PRZESZUKANIE_URZADZENIA,

            self::TYP_ZATRZYMANIE_RZECZY,
            self::TYP_ZATRZYMANIE_OSOBY,

            self::TYP_OGLEDZINY,

            self::TYP_PRZESLUCHANIE_SWIADKA,
            self::TYP_PRZESLUCHANIE_PODEJRZANEGO,

            self::TYP_KONFRONTACJA,
            self::TYP_POBRANIE_MATERIALU,
            self::TYP_ZAZNAJOMIENIE_Z_AKTAMI,
            self::TYP_TESTER_NARKOTYKOWY,

            self::TYP_ZAWIADOMIENIE,

            self::TYP_INNA,
        ];
    }

    // CZY DLA TEJ CZYNNOŚCI MOŻNA DODAĆ SPIS RZECZY
    public function isSpisRzeczyDozwolony(): bool
    {
        return in_array($this->typ, [
            self::TYP_PRZESZUKANIE,
            self::TYP_PRZESZUKANIE_OSOBY,
            self::TYP_PRZESZUKANIE_URZADZENIA,
            self::TYP_OGLEDZINY,
            self::TYP_ZATRZYMANIE_RZECZY,
        ], true);
    }

    // ===== GETTERY / SETTERY (skrócone) =====

    public function getId(): ?int { return $this->id; }

    public function getPostepowanie(): Postepowanie { return $this->postepowanie; }
    public function setPostepowanie(Postepowanie $p): static { $this->postepowanie = $p; return $this; }

    public function getTyp(): string { return $this->typ; }
    public function setTyp(string $typ): static { $this->typ = $typ; return $this; }

    public function getSpisRzeczy(): ?array { return $this->spisRzeczy; }
    public function setSpisRzeczy(?array $v): static { $this->spisRzeczy = $v; return $this; }

    /**
     * @return Collection<int, CzynnoscUczestnik>
     */
    public function getUczestnicy(): Collection
    {
        return $this->uczestnicy;
    }

    public function setUczestnicy(iterable $uczestnicy): static
    {
        $this->uczestnicy->clear();
        foreach ($uczestnicy as $u) {
            if ($u instanceof CzynnoscUczestnik) {
                $this->addUczestnik($u);
            }
        }
        return $this;
    }

    public function addUczestnik(CzynnoscUczestnik $uczestnik): static
    {
        if (!$this->uczestnicy->contains($uczestnik)) {
            $this->uczestnicy->add($uczestnik);
            $uczestnik->setCzynnosc($this);
        }
        return $this;
    }

    public function removeUczestnik(CzynnoscUczestnik $uczestnik): static
    {
        $this->uczestnicy->removeElement($uczestnik);
        return $this;
    }

    public function __toString(): string
    {
        $data = $this->dataStart?->format('Y-m-d H:i') ?? '';
        return trim(($this->typ ?? 'CZYNNOSC') . ' ' . $data);
    }
    public function getDataStart(): ?\DateTimeImmutable
    {
        return $this->dataStart;
    }

    public function setDataStart(?\DateTimeImmutable $d): static
    {
        $this->dataStart = $d;
        return $this;
    }

    public function getDataKoniec(): ?\DateTimeImmutable
    {
        return $this->dataKoniec;
    }

    public function setDataKoniec(?\DateTimeImmutable $d): static
    {
        $this->dataKoniec = $d;
        return $this;
    }

    public function getMiejsceOpis(): ?string
    {
        return $this->miejsceOpis;
    }

    public function setMiejsceOpis(?string $v): static
    {
        $this->miejsceOpis = $v;
        return $this;
    }

    public function getMiejsceAdres(): Adres
    {
        return $this->miejsceAdres;
    }

    public function setMiejsceAdres(Adres $a): static
    {
        $this->miejsceAdres = $a;
        return $this;
    }

    public function getPodstawaPrawna(): ?string
    {
        return $this->podstawaPrawna;
    }

    public function setPodstawaPrawna(?string $v): static
    {
        $this->podstawaPrawna = $v;
        return $this;
    }

    public function getTresc(): ?string
    {
        return $this->tresc;
    }

    public function setTresc(?string $v): static
    {
        $this->tresc = $v;
        return $this;
    }

    public function getZalacznikiOpis(): ?string
    {
        return $this->zalacznikiOpis;
    }

    public function setZalacznikiOpis(?string $v): static
    {
        $this->zalacznikiOpis = $v;
        return $this;
    }

    public function isRejestrowana(): bool
    {
        return $this->rejestrowana;
    }

    public function setRejestrowana(bool $v): static
    {
        $this->rejestrowana = $v;
        return $this;
    }

    public function getRejestracjaOpis(): ?string
    {
        return $this->rejestracjaOpis;
    }

    public function setRejestracjaOpis(?string $v): static
    {
        $this->rejestracjaOpis = $v;
        return $this;
    }

    public function getOperatorRejestracjiOpis(): ?string
    {
        return $this->operatorRejestracjiOpis;
    }

    public function setOperatorRejestracjiOpis(?string $v): static
    {
        $this->operatorRejestracjiOpis = $v;
        return $this;
    }
    public function getDane(): array
    {
        return $this->dane ?? [];
    }

    public function setDane(?array $dane): static
    {
        $this->dane = $dane;
        return $this;
    }

    public function getDaneValue(string $key, mixed $default = ''): mixed
    {
        $d = $this->getDane();
        return array_key_exists($key, $d) ? $d[$key] : $default;
    }

    public function setDaneValue(string $key, mixed $value): static
    {
        $d = $this->getDane();
        $d[$key] = $value;
        $this->dane = $d;
        return $this;
    }

    public function getGlownaOsoba(): ?PostepowanieOsoba
    {
        return $this->glownaOsoba;
    }
    public function setGlownaOsoba(?PostepowanieOsoba $p): self
    {
        $this->glownaOsoba = $p; return $this;
    }



}
