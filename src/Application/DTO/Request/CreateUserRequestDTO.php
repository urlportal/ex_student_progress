<?php

namespace App\Application\DTO\Request;

use App\Application\Validator\Constraint\UniqueEmail;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[UniqueEmail]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        #[SerializedName('first_name')]
        public string $firstName,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        #[SerializedName('last_name')]
        public string $lastName,
    ) {
    }
}
