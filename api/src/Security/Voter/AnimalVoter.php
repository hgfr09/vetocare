<?php

namespace App\Security\Voter;

use App\Entity\Animal;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class AnimalVoter extends Voter
{
    public const DELETE = 'ANIMAL_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DELETE]) && $subject instanceof \App\Entity\Animal;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        assert($subject instanceof Animal);

        switch ($attribute) {
            case self::DELETE:
                return  $user->getUserIdentifier() === $subject->getVeterinarian()?->getUserIdentifier();
        }

        return false;
    }
}
