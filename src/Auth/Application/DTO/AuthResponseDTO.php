<?php

namespace App\Auth\Application\DTO;

final class AuthResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $token
    ) {
    }
}
