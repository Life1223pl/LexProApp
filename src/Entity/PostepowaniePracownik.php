<?php

namespace App\Entity;

use App\Repository\PostepowaniePracownikRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostepowaniePracownikRepository::class)]
#[ORM\Table(name: 'postepowanie_pracownik')]
#[ORM\UniqueConstraint(name: 'uniq_postepowanie_pracownik', columns: ['postepowanie_id', 'pracownik_id'])]
class PostepowaniePracownik
{
    public const ROLA_PROWADZACY = 'PROWADZACY';
    public const ROLA_WSPOLPROWADZACY = 'WSPOLPROWADZACY';
    public const ROLA_WGLAD = 'WGLAD';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, nullable: false)]
    private string $rola = self::ROLA_WSPOLPROWADZACY;

    #[ORM\ManyToOne(inversedBy: 'przypisania')]
    #[ORM\JoinColumn(nullable: false)]
    private Postepowanie $postepowanie;

    #[ORM\ManyToOne(inversedBy: 'przypisaniaDoPostepowan')]
    #[ORM\JoinColumn(nullable: false)]
    private Pracownik $pracownik;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRola(): string
    {
        return $this->rola;
    }

    public function setRola(string $rola): static
    {
        $this->rola = $rola;
        return $this;
    }

    public function getPostepowanie(): Postepowanie
    {
        return $this->postepowanie;
    }

    public function setPostepowanie(Postepowanie $postepowanie): static
    {
        $this->postepowanie = $postepowanie;
        return $this;
    }

    public function getPracownik(): Pracownik
    {
        return $this->pracownik;
    }

    public function setPracownik(Pracownik $pracownik): static
    {
        $this->pracownik = $pracownik;
        return $this;
    }
}
