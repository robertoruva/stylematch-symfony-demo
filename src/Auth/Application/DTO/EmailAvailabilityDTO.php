<?php

namespace App\Auth\Application\DTO;

final class EmailAvailabilityDTO
{
    public function __construct(
        public readonly string $email,
        public readonly bool $available,
        public readonly ?string $reason = null
    ) {
    }
}
