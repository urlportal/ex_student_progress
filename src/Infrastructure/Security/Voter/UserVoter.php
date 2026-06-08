<?php

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, User> */
final class UserVoter extends Voter
{
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        if ($currentUser->hasRole(UserRole::ADMIN)) {
            return true;
        }

        if (self::DELETE === $attribute) {
            return false;
        }

        /* @var User $subject */
        return (string) $subject->getId() === (string) $currentUser->getId();
    }
}
