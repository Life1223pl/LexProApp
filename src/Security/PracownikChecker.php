<?php

namespace App\Security;

use App\Entity\Pracownik;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PracownikChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Pracownik) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Konto oczekuje na zatwierdzenie przez administratora.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {

    }
}
