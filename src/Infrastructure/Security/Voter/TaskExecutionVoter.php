<?php

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\TaskExecution;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, TaskExecution> */
final class TaskExecutionVoter extends Voter
{
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof TaskExecution;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /* @var TaskExecution $subject */
        if ($user->hasRole(UserRole::ADMIN) || $user->hasRole(UserRole::TEACHER)) {
            return true;
        }

        if (self::VIEW === $attribute && $user->hasRole(UserRole::STUDENT)) {
            return (string) $subject->getUser()->getId() === (string) $user->getId();
        }

        return false;
    }
}
