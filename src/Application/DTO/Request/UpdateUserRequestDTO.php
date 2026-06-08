<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserRequestDTO
{
    public function __construct(
        #[Assert\Email]
        public ?string $email = null,
        #[Assert\Length(min: 6)]
        public ?string $password = null,
        #[Assert\Length(max: 100)]
        #[SerializedName('first_name')]
        public ?string $firstName = null,
        #[Assert\Length(max: 100)]
        #[SerializedName('last_name')]
        public ?string $lastName = null,
    ) {
    }
}
