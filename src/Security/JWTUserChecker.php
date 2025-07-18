<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Entity\User;

class JWTUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        
        if (!$user->isApiActivated()) {
            throw new CustomUserMessageAccountStatusException('Accès API désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // pas besoin d’implémentation ici
    }
}
