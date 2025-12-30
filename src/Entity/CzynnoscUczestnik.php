<?php

namespace App\Entity;

use App\Repository\CzynnoscUczestnikRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CzynnoscUczestnikRepository::class)]
#[ORM\Table(name: 'czynnosc_uczestnik')]
#[ORM\Index(columns: ['rola'], name: 'idx_czynnosc_uczestnik_rola')]
class CzynnoscUczestnik
{
    // Najczęstsze role w protokołach (startowo jako stringi)
    public const ROLA_PROWADZACY = 'PROWADZACY';
    public const ROLA_PROTOKOLANT = 'PROTOKOLANT';
    public const ROLA_STENOGRAF = 'STENOGRAF';
    public const ROLA_OBRONCA = 'OBRONCA';
    public const ROLA_BIEGLY = 'BIEGLY';
    public const ROLA_SPECJALISTA = 'SPECJALISTA';
    public const ROLA_TLUMACZ = 'TLUMACZ';
    public const ROLA_OSOBA_PRZYBRANA = 'OSOBA_PRZYBRANA';
    public const ROLA_OSOBA_WSKAZANA = 'OSOBA_WSKAZANA';
    public const ROLA_OPERATOR_REJESTRACJI = 'OPERATOR_REJESTRACJI';
    public const ROLA_INNA = 'INNA';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'uczestnicy')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Czynnosc $czynnosc;

    // Najprostszy wariant: albo pracownik, albo osoba
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pracownik $pracownik = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Osoba $osoba = null;

    #[ORM\Column(length: 40)]
    private string $rola = self::ROLA_INNA;

    // np. "biegły z zakresu daktyloskopii", "specjalista IT", "obrońca z urzędu"
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $opisRoli = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function getDostepneRole(): array
    {
        return [
            self::ROLA_PROWADZACY,
            self::ROLA_PROTOKOLANT,
            self::ROLA_STENOGRAF,
            self::ROLA_OBRONCA,
            self::ROLA_BIEGLY,
            self::ROLA_SPECJALISTA,
            self::ROLA_TLUMACZ,
            self::ROLA_OSOBA_PRZYBRANA,
            self::ROLA_OSOBA_WSKAZANA,
            self::ROLA_OPERATOR_REJESTRACJI,
            self::ROLA_INNA,
        ];
    }

    public function getId(): ?int { return $this->id; }

    public function getCzynnosc(): Czynnosc { return $this->czynnosc; }
    public function setCzynnosc(Czynnosc $czynnosc): static { $this->czynnosc = $czynnosc; return $this; }

    public function getPracownik(): ?Pracownik { return $this->pracownik; }
    public function setPracownik(?Pracownik $pracownik): static { $this->pracownik = $pracownik; return $this; }

    public function getOsoba(): ?Osoba { return $this->osoba; }
    public function setOsoba(?Osoba $osoba): static { $this->osoba = $osoba; return $this; }

    public function getRola(): string { return $this->rola; }
    public function setRola(string $rola): static { $this->rola = $rola; return $this; }

    public function getOpisRoli(): ?string { return $this->opisRoli; }
    public function setOpisRoli(?string $opisRoli): static { $this->opisRoli = $opisRoli; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function __toString(): string
    {
        $kto = $this->pracownik
            ? (string) $this->pracownik
            : ($this->osoba ? (string) $this->osoba : '—');

        return $this->rola . ': ' . $kto;
    }
    public function isValid(): bool
    {
        return ($this->pracownik !== null) xor ($this->osoba !== null);
    }

}
