<?php

namespace App\Application\Validator\Constraint;

use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (null !== $this->userRepository->findByEmail((string) $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
