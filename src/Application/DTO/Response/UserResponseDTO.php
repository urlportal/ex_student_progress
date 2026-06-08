<?php

namespace App\Application\DTO\Response;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class UserResponseDTO
{
    public function __construct(
        public string $id,
        public string $email,
        #[SerializedName('first_name')]
        public string $firstName,
        #[SerializedName('last_name')]
        public string $lastName,
        #[SerializedName('created_at')]
        public string $createdAt,
    ) {
    }
}
