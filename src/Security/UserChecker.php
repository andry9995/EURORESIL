<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // Rien à faire avant l'authentification
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getEmailVerifiedAt() === null) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n’est pas encore activé. Veuillez vérifier votre boîte mail.'
            );
        }
    }
}