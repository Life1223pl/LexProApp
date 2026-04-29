<?php

namespace App\Entity;

use App\Repository\PracownikRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: PracownikRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Istnieje już konto z tym adresem email')]
class Pracownik implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $imie = null;

    #[ORM\Column(length: 255)]
    private ?string $nazwisko = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $stopien = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $funkcja = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'podwladni')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $przelozony = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $miejsceZatrudnienia = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'przelozony')]
    private Collection $podwladni;

    #[ORM\OneToMany(targetEntity: Postepowanie::class, mappedBy: 'prowadzacy')]
    private Collection $postepowaniaProwadzone;

    #[ORM\OneToMany(targetEntity: Postepowanie::class, mappedBy: 'approvedBy')]
    private Collection $approvedAt;

    #[ORM\OneToMany(targetEntity: PostepowaniePracownik::class, mappedBy: 'pracownik')]
    private Collection $przypisaniaDoPostepowan;

    public function __construct()
    {
        $this->podwladni = new ArrayCollection();
        $this->postepowaniaProwadzone = new ArrayCollection();
        $this->approvedAt = new ArrayCollection();
        $this->przypisaniaDoPostepowan = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function eraseCredentials(): void
    {
    }

    public function getImie(): ?string
    {
        return $this->imie;
    }

    public function setImie(string $imie): static
    {
        $this->imie = $imie;
        return $this;
    }

    public function getNazwisko(): ?string
    {
        return $this->nazwisko;
    }

    public function setNazwisko(string $nazwisko): static
    {
        $this->nazwisko = $nazwisko;
        return $this;
    }

    public function getStopien(): ?string
    {
        return $this->stopien;
    }

    public function setStopien(?string $stopien): static
    {
        $this->stopien = $stopien;
        return $this;
    }

    public function getFunkcja(): ?string
    {
        return $this->funkcja;
    }

    public function setFunkcja(?string $funkcja): static
    {
        $this->funkcja = $funkcja;
        return $this;
    }

    // 🔥 KLUCZOWE METODY (FIX)
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getPrzelozony(): ?self
    {
        return $this->przelozony;
    }

    public function setPrzelozony(?self $przelozony): static
    {
        $this->przelozony = $przelozony;
        return $this;
    }

    public function getPodwladni(): Collection
    {
        return $this->podwladni;
    }

    public function getPostepowaniaProwadzone(): Collection
    {
        return $this->postepowaniaProwadzone;
    }

    public function getApprovedAt(): Collection
    {
        return $this->approvedAt;
    }

    public function getPrzypisaniaDoPostepowan(): Collection
    {
        return $this->przypisaniaDoPostepowan;
    }

    public function getMiejsceZatrudnienia(): ?string
    {
        return $this->miejsceZatrudnienia;
    }

    public function setMiejsceZatrudnienia(?string $miejsceZatrudnienia): static
    {
        $this->miejsceZatrudnienia = $miejsceZatrudnienia;
        return $this;
    }

    public function __toString(): string
    {
        $full = trim(($this->imie ?? '') . ' ' . ($this->nazwisko ?? ''));
        return $full !== '' ? $full . ' (' . $this->email . ')' : (string)$this->email;
    }
}
