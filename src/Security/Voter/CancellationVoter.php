<?php

namespace App\Security\Voter;

use App\Entity\Cancellation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CancellationVoter extends Voter
{
    public const CAN_CANCELLATION = 'CAN_CANCELLATION';
    public const CAN_VIEW_CANCELLATION = 'CAN_VIEW_CANCELLATION';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::CAN_CANCELLATION,
            self::CAN_VIEW_CANCELLATION,
        ]);
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $token->getUser();

        return match ($attribute) {
            self::CAN_VIEW_CANCELLATION => $this->canView($currentUser, $subject),
            self::CAN_CANCELLATION => $this->canEdit($currentUser, $subject),
            default => $currentUser->getCredits() > 0,
        };

    }

    /**
     * @param User $userCurrent
     * @param User $user
     * @return bool
     */
    private function sameUser(User $userCurrent, User $user) : bool
    {
        return $userCurrent->getId() === $user->getId();
    }

    /**
     * @param User $userCurrent
     * @param Cancellation|null $cancellation
     * @return bool
     */
    private function canView(User $userCurrent, ?Cancellation $cancellation) : bool
    {
        return $cancellation && $this->sameUser($userCurrent, $cancellation->getUser());
    }

    /**
     * @param User $userCurrent
     * @param Cancellation|null $cancellation
     * @return bool
     */
    private function canEdit(User $userCurrent, ?Cancellation $cancellation) : bool
    {
        if ($userCurrent->getCredits() <= 0) {
            return false;
        }

        if ($cancellation !== null) {
            return $cancellation->getUser()->getId() === $userCurrent->getId();
        }

        return true;
    }
}