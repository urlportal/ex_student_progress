<?php

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, string> */
final class AggStudentLessonSkillVoter extends Voter
{
    public const string VIEW = 'AGG_STUDENT_LESSON_SKILL_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && \is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRole(UserRole::ADMIN) || $user->hasRole(UserRole::TEACHER)) {
            return true;
        }

        if ($user->hasRole(UserRole::STUDENT)) {
            return $subject === (string) $user->getId();
        }

        return false;
    }
}
