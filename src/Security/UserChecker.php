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
        if (!$user instanceof Pracownik) {
            return;
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Konto zostało dezaktywowane.');
        }

        if (method_exists($user, 'isVerified') && !$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Konto nie zostało jeszcze zatwierdzone przez administratora.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
