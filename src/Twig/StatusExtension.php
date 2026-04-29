<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StatusExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_pl', [$this, 'translateStatus']),
        ];
    }

    public function translateStatus(?string $status): string
    {
        return match ($status) {
            'WAITING_APPROVAL' => 'Oczekuje na zatwierdzenie',
            'APPROVED' => 'Zatwierdzone',
            'REJECTED' => 'Odrzucone',
            'CLOSED' => 'Zamknięte',
            'WAITING_DELETE_APPROVAL' => 'Oczekuje na zgodę usunięcia',
            'DELETE_REJECTED' => 'Odrzucono usunięcie',
            default => $status ?? '-',
        };
    }
}
