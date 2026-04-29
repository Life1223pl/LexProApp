<?php

namespace App\Security;

use App\Entity\Pracownik;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // sprawdzamy tylko naszych użytkowników
        if (!$user instanceof Pracownik) {
            return;
        }

        // blokada logowania jeśli konto nieaktywne
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Konto oczekuje na zatwierdzenie przez administratora.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // brak dodatkowych sprawdzeń
    }
}
