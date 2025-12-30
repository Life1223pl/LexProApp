<?php

namespace App\Entity;

use App\Repository\PostepowanieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostepowanieRepository::class)]
class Postepowanie
{
    public const STATUS_WAITING_APPROVAL = 'WAITING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CLOSED = 'CLOSED';

    // Usuwanie przez wniosek
    public const STATUS_WAITING_DELETE_APPROVAL = 'WAITING_DELETE_APPROVAL';
    public const STATUS_DELETE_REJECTED = 'DELETE_REJECTED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numer = null;

    #[ORM\Column(length: 100)]
    private ?string $rodzaj = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dataWszczecia = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'postepowaniaProwadzone')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pracownik $prowadzacy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pracownik $approvedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    //Wniosek o usunięcie
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pracownik $deleteRequestedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deleteRequestedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pracownik $deleteApprovedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deleteApprovedAt = null;

    /**
     * @var Collection<int, PostepowaniePracownik>
     */
    #[ORM\OneToMany(mappedBy: 'postepowanie', targetEntity: PostepowaniePracownik::class, orphanRemoval: true)]
    private Collection $przypisania;

    /**
     * Osoby w postępowaniu (podejrzany/świadek/pokrzywdzony itd.) przez encję łączącą PostepowanieOsoba
     *
     * @var Collection<int, PostepowanieOsoba>
     */
    #[ORM\OneToMany(mappedBy: 'postepowanie', targetEntity: PostepowanieOsoba::class, orphanRemoval: true)]
    private Collection $osoby;

    public function __construct()
    {
        $this->przypisania = new ArrayCollection();
        $this->osoby = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumer(): ?string
    {
        return $this->numer;
    }

    public function setNumer(string $numer): static
    {
        $this->numer = $numer;
        return $this;
    }

    public function getRodzaj(): ?string
    {
        return $this->rodzaj;
    }

    public function setRodzaj(string $rodzaj): static
    {
        $this->rodzaj = $rodzaj;
        return $this;
    }

    public function getDataWszczecia(): ?\DateTimeInterface
    {
        return $this->dataWszczecia;
    }

    public function setDataWszczecia(\DateTimeInterface $dataWszczecia): static
    {
        $this->dataWszczecia = $dataWszczecia;
        return $this;
    }

    public function getDataZakonczenia(): ?\DateTimeInterface
    {
        return $this->dataZakonczenia;
    }

    public function setDataZakonczenia(?\DateTimeInterface $dataZakonczenia): static
    {
        $this->dataZakonczenia = $dataZakonczenia;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getProwadzacy(): ?Pracownik
    {
        return $this->prowadzacy;
    }

    public function setProwadzacy(Pracownik $prowadzacy): static
    {
        $this->prowadzacy = $prowadzacy;
        return $this;
    }

    public function getApprovedBy(): ?Pracownik
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?Pracownik $approvedBy): static
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getDeleteRequestedBy(): ?Pracownik
    {
        return $this->deleteRequestedBy;
    }

    public function setDeleteRequestedBy(?Pracownik $deleteRequestedBy): static
    {
        $this->deleteRequestedBy = $deleteRequestedBy;
        return $this;
    }

    public function getDeleteRequestedAt(): ?\DateTimeImmutable
    {
        return $this->deleteRequestedAt;
    }

    public function setDeleteRequestedAt(?\DateTimeImmutable $deleteRequestedAt): static
    {
        $this->deleteRequestedAt = $deleteRequestedAt;
        return $this;
    }

    public function getDeleteApprovedBy(): ?Pracownik
    {
        return $this->deleteApprovedBy;
    }

    public function setDeleteApprovedBy(?Pracownik $deleteApprovedBy): static
    {
        $this->deleteApprovedBy = $deleteApprovedBy;
        return $this;
    }

    public function getDeleteApprovedAt(): ?\DateTimeImmutable
    {
        return $this->deleteApprovedAt;
    }

    public function setDeleteApprovedAt(?\DateTimeImmutable $deleteApprovedAt): static
    {
        $this->deleteApprovedAt = $deleteApprovedAt;
        return $this;
    }

    /**
     * @return Collection<int, PostepowaniePracownik>
     */
    public function getPrzypisania(): Collection
    {
        return $this->przypisania;
    }

    public function addPrzypisanie(PostepowaniePracownik $przypisanie): static
    {
        if (!$this->przypisania->contains($przypisanie)) {
            $this->przypisania->add($przypisanie);
            $przypisanie->setPostepowanie($this);
        }

        return $this;
    }

    public function removePrzypisanie(PostepowaniePracownik $przypisanie): static
    {
        if ($this->przypisania->removeElement($przypisanie)) {
            // orphanRemoval=true -> rekord PostepowaniePracownik zostanie usunięty
        }

        return $this;
    }

    /**
     * @return Collection<int, PostepowanieOsoba>
     */
    public function getOsoby(): Collection
    {
        return $this->osoby;
    }

    public function addOsoba(PostepowanieOsoba $postepowanieOsoba): static
    {
        if (!$this->osoby->contains($postepowanieOsoba)) {
            $this->osoby->add($postepowanieOsoba);
            $postepowanieOsoba->setPostepowanie($this);
        }

        return $this;
    }

    public function removeOsoba(PostepowanieOsoba $postepowanieOsoba): static
    {
        if ($this->osoby->removeElement($postepowanieOsoba)) {

        }

        return $this;
    }
}
