<?php

namespace App\Domain\Enum;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case TEACHER = 'ROLE_TEACHER';
    case STUDENT = 'ROLE_STUDENT';
}
