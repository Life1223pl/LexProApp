<?php

namespace App\Entity;

use App\Repository\HistoryLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoryLogRepository::class)]
#[ORM\Table(name: 'history_log')]
#[ORM\Index(columns: ['postepowanie_id', 'occurred_at'], name: 'idx_history_postepowanie_time')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'idx_history_entity')]
class HistoryLog
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Postepowanie $postepowanie = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pracownik $user = null;

    #[ORM\Column(length: 10)]
    private string $action;

    #[ORM\Column(length: 255)]
    private string $entityClass;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $changes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPostepowanie(): ?Postepowanie { return $this->postepowanie; }
    public function setPostepowanie(?Postepowanie $postepowanie): static { $this->postepowanie = $postepowanie; return $this; }

    public function getUser(): ?Pracownik { return $this->user; }
    public function setUser(?Pracownik $user): static { $this->user = $user; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }

    public function getEntityClass(): string { return $this->entityClass; }
    public function setEntityClass(string $entityClass): static { $this->entityClass = $entityClass; return $this; }

    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(?int $entityId): static { $this->entityId = $entityId; return $this; }

    public function getChanges(): ?array { return $this->changes; }
    public function setChanges(?array $changes): static { $this->changes = $changes; return $this; }

    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
    public function setOccurredAt(\DateTimeImmutable $occurredAt): static { $this->occurredAt = $occurredAt; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $ip): static { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function getReadableAction(): string
    {
        $short = substr(strrchr($this->entityClass, '\\'), 1);

        return match ($short) {
            'PostepowanieOsoba' => match ($this->action) {
                self::ACTION_CREATE => 'Dodano osobę do postępowania',
                self::ACTION_DELETE => 'Usunięto osobę z postępowania',
                default => 'Zmieniono dane osoby w postępowaniu',
            },
            'Czynnosc' => 'Zmieniono czynność',
            'Postepowanie' => 'Zmieniono postępowanie',
            default => $this->action . ' ' . $short,
        };
    }


}
