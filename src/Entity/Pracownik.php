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
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Pracownik implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
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

    // ✅ ZMIANA: bool + domyślnie false, bez NULL
    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;

    // ✅ ZMIANA: bool + domyślnie false, bez NULL
    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'podwladni')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $przelozony = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'przelozony')]
    private Collection $podwladni;

    /**
     * @var Collection<int, Postepowanie>
     */
    #[ORM\OneToMany(targetEntity: Postepowanie::class, mappedBy: 'prowadzacy')]
    private Collection $postepowaniaProwadzone;

    /**
     * @var Collection<int, Postepowanie>
     */
    #[ORM\OneToMany(targetEntity: Postepowanie::class, mappedBy: 'approvedBy')]
    private Collection $approvedAt;

    /**
     * @var Collection<int, PostepowaniePracownik>
     */
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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

    // ✅ ZMIANA: zwraca bool (nie ?bool)
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    // ✅ ZMIANA: zwraca bool (nie ?bool)
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

    /**
     * @return Collection<int, self>
     */
    public function getPodwladni(): Collection
    {
        return $this->podwladni;
    }

    public function addPodwladni(self $podwladni): static
    {
        if (!$this->podwladni->contains($podwladni)) {
            $this->podwladni->add($podwladni);
            $podwladni->setPrzelozony($this);
        }

        return $this;
    }

    public function removePodwladni(self $podwladni): static
    {
        if ($this->podwladni->removeElement($podwladni)) {
            // set the owning side to null (unless already changed)
            if ($podwladni->getPrzelozony() === $this) {
                $podwladni->setPrzelozony(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Postepowanie>
     */
    public function getPostepowaniaProwadzone(): Collection
    {
        return $this->postepowaniaProwadzone;
    }

    public function addPostepowaniaProwadzone(Postepowanie $postepowaniaProwadzone): static
    {
        if (!$this->postepowaniaProwadzone->contains($postepowaniaProwadzone)) {
            $this->postepowaniaProwadzone->add($postepowaniaProwadzone);
            $postepowaniaProwadzone->setProwadzacy($this);
        }

        return $this;
    }

    public function removePostepowaniaProwadzone(Postepowanie $postepowaniaProwadzone): static
    {
        if ($this->postepowaniaProwadzone->removeElement($postepowaniaProwadzone)) {
            // set the owning side to null (unless already changed)
            if ($postepowaniaProwadzone->getProwadzacy() === $this) {
                $postepowaniaProwadzone->setProwadzacy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Postepowanie>
     */
    public function getApprovedAt(): Collection
    {
        return $this->approvedAt;
    }

    public function addApprovedAt(Postepowanie $approvedAt): static
    {
        if (!$this->approvedAt->contains($approvedAt)) {
            $this->approvedAt->add($approvedAt);
            $approvedAt->setApprovedBy($this);
        }

        return $this;
    }

    public function removeApprovedAt(Postepowanie $approvedAt): static
    {
        if ($this->approvedAt->removeElement($approvedAt)) {
            // set the owning side to null (unless already changed)
            if ($approvedAt->getApprovedBy() === $this) {
                $approvedAt->setApprovedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostepowaniePracownik>
     */
    public function getPrzypisaniaDoPostepowan(): Collection
    {
        return $this->przypisaniaDoPostepowan;
    }

    public function addPrzypisaniaDoPostepowan(PostepowaniePracownik $przypisaniaDoPostepowan): static
    {
        if (!$this->przypisaniaDoPostepowan->contains($przypisaniaDoPostepowan)) {
            $this->przypisaniaDoPostepowan->add($przypisaniaDoPostepowan);
            $przypisaniaDoPostepowan->setPracownik($this);
        }

        return $this;
    }

    public function removePrzypisaniaDoPostepowan(PostepowaniePracownik $przypisaniaDoPostepowan): static
    {
        if ($this->przypisaniaDoPostepowan->removeElement($przypisaniaDoPostepowan)) {
            // set the owning side to null (unless already changed)
            if ($przypisaniaDoPostepowan->getPracownik() === $this) {
                $przypisaniaDoPostepowan->setPracownik(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        $imie = $this->imie ?? '';
        $nazwisko = $this->nazwisko ?? '';
        $email = $this->email ?? '';

        $full = trim($imie.' '.$nazwisko);

        return $full !== '' ? $full.' ('.$email.')' : $email;
    }
}
