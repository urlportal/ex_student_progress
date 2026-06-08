<?php

namespace App\Application\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class UniqueEmail extends Constraint
{
    public string $message = 'This email is already taken.';
}
