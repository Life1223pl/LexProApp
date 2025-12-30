<?php

namespace App\Entity;

use App\Entity\Embeddable\Adres;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Osoba
{
    public const PLEC_M = 'M';
    public const PLEC_K = 'K';
    public const PLEC_NIEZNANA = 'N';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Dane podstawowe
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $imie = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $drugieImie = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $nazwisko = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $nazwiskoRodowe = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $imieOjca = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $imieMatki = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $nazwiskoRodoweMatki = null;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $pesel = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $numerDokumentu = null; // dowód/paszport, gdy brak PESEL

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataUrodzenia = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $miejsceUrodzenia = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $plec = null; // M/K/N

    // Obywatelstwo
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $obywatelstwoGl = 'Polska';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $obywatelstwoDodatkowe = null;

    // Kontakt
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telefon = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    // Adresy
    #[ORM\Embedded(class: Adres::class, columnPrefix: 'zam_')]
    private Adres $adresZamieszkania;

    #[ORM\Embedded(class: Adres::class, columnPrefix: 'zameld_')]
    private Adres $adresZameldowania;

    #[ORM\Embedded(class: Adres::class, columnPrefix: 'kor_')]
    private Adres $adresKorespondencyjny;

    // Pozostałe (użyteczne w protokołach)
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $wyksztalcenie = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $stanCywilny = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $zawod = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $miejscePracy = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $stanowisko = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notatki = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, PostepowanieOsoba>
     */
    #[ORM\OneToMany(mappedBy: 'osoba', targetEntity: PostepowanieOsoba::class, orphanRemoval: true)]
    private Collection $udzialy;

    public function __construct()
    {
        $this->adresZamieszkania = new Adres();
        $this->adresZameldowania = new Adres();
        $this->adresKorespondencyjny = new Adres();

        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->udzialy = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- get/set (najważniejsze) ---
    public function getId(): ?int { return $this->id; }

    public function getImie(): ?string { return $this->imie; }
    public function setImie(?string $imie): static { $this->imie = $imie; return $this; }

    public function getDrugieImie(): ?string { return $this->drugieImie; }
    public function setDrugieImie(?string $drugieImie): static { $this->drugieImie = $drugieImie; return $this; }

    public function getNazwisko(): ?string { return $this->nazwisko; }
    public function setNazwisko(?string $nazwisko): static { $this->nazwisko = $nazwisko; return $this; }

    public function getNazwiskoRodowe(): ?string { return $this->nazwiskoRodowe; }
    public function setNazwiskoRodowe(?string $nazwiskoRodowe): static { $this->nazwiskoRodowe = $nazwiskoRodowe; return $this; }

    public function getPesel(): ?string { return $this->pesel; }
    public function setPesel(?string $pesel): static { $this->pesel = $pesel; return $this; }

    public function getNumerDokumentu(): ?string { return $this->numerDokumentu; }
    public function setNumerDokumentu(?string $numerDokumentu): static { $this->numerDokumentu = $numerDokumentu; return $this; }

    public function getDataUrodzenia(): ?\DateTimeInterface { return $this->dataUrodzenia; }
    public function setDataUrodzenia(?\DateTimeInterface $dataUrodzenia): static { $this->dataUrodzenia = $dataUrodzenia; return $this; }

    public function getMiejsceUrodzenia(): ?string { return $this->miejsceUrodzenia; }
    public function setMiejsceUrodzenia(?string $miejsceUrodzenia): static { $this->miejsceUrodzenia = $miejsceUrodzenia; return $this; }

    public function getPlec(): ?string { return $this->plec; }
    public function setPlec(?string $plec): static { $this->plec = $plec; return $this; }

    public function getObywatelstwoGl(): ?string { return $this->obywatelstwoGl; }
    public function setObywatelstwoGl(?string $obywatelstwoGl): static { $this->obywatelstwoGl = $obywatelstwoGl; return $this; }

    public function getObywatelstwoDodatkowe(): ?string { return $this->obywatelstwoDodatkowe; }
    public function setObywatelstwoDodatkowe(?string $obywatelstwoDodatkowe): static { $this->obywatelstwoDodatkowe = $obywatelstwoDodatkowe; return $this; }

    public function getTelefon(): ?string { return $this->telefon; }
    public function setTelefon(?string $telefon): static { $this->telefon = $telefon; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getAdresZamieszkania(): Adres { return $this->adresZamieszkania; }
    public function setAdresZamieszkania(Adres $a): static { $this->adresZamieszkania = $a; return $this; }

    public function getAdresZameldowania(): Adres { return $this->adresZameldowania; }
    public function setAdresZameldowania(Adres $a): static { $this->adresZameldowania = $a; return $this; }

    public function getAdresKorespondencyjny(): Adres { return $this->adresKorespondencyjny; }
    public function setAdresKorespondencyjny(Adres $a): static { $this->adresKorespondencyjny = $a; return $this; }

    public function getNotatki(): ?string { return $this->notatki; }
    public function setNotatki(?string $notatki): static { $this->notatki = $notatki; return $this; }

    public function __toString(): string
    {
        $full = trim(($this->imie ?? '').' '.($this->nazwisko ?? ''));
        if ($full !== '') return $full;
        return 'Osoba #'.($this->id ?? 'new');
    }
    public function getImieOjca(): ?string
    {
        return $this->imieOjca;
    }

    public function setImieOjca(?string $imieOjca): static
    {
        $this->imieOjca = $imieOjca;
        return $this;
    }

    public function getImieMatki(): ?string
    {
        return $this->imieMatki;
    }

    public function setImieMatki(?string $imieMatki): static
    {
        $this->imieMatki = $imieMatki;
        return $this;
    }

    public function getNazwiskoRodoweMatki(): ?string
    {
        return $this->nazwiskoRodoweMatki;
    }

    public function setNazwiskoRodoweMatki(?string $nazwiskoRodoweMatki): static
    {
        $this->nazwiskoRodoweMatki = $nazwiskoRodoweMatki;
        return $this;
    }

    public function getWyksztalcenie(): ?string
    {
        return $this->wyksztalcenie;
    }

    public function setWyksztalcenie(?string $wyksztalcenie): static
    {
        $this->wyksztalcenie = $wyksztalcenie;
        return $this;
    }

    public function getStanCywilny(): ?string
    {
        return $this->stanCywilny;
    }

    public function setStanCywilny(?string $stanCywilny): static
    {
        $this->stanCywilny = $stanCywilny;
        return $this;
    }

    public function getZawod(): ?string
    {
        return $this->zawod;
    }

    public function setZawod(?string $zawod): static
    {
        $this->zawod = $zawod;
        return $this;
    }

    public function getMiejscePracy(): ?string
    {
        return $this->miejscePracy;
    }

    public function setMiejscePracy(?string $miejscePracy): static
    {
        $this->miejscePracy = $miejscePracy;
        return $this;
    }

    public function getStanowisko(): ?string
    {
        return $this->stanowisko;
    }

    public function setStanowisko(?string $stanowisko): static
    {
        $this->stanowisko = $stanowisko;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, PostepowanieOsoba>
     */
    public function getUdzialy(): Collection
    {
        return $this->udzialy;
    }

    public function addUdzialy(PostepowanieOsoba $udzialy): static
    {
        if (!$this->udzialy->contains($udzialy)) {
            $this->udzialy->add($udzialy);
            $udzialy->setOsoba($this);
        }

        return $this;
    }

    public function removeUdzialy(PostepowanieOsoba $udzialy): static
    {
        if ($this->udzialy->removeElement($udzialy)) {
            // set the owning side to null (unless already changed)
            if ($udzialy->getOsoba() === $this) {
                $udzialy->setOsoba(null);
            }
        }

        return $this;
    }

}
