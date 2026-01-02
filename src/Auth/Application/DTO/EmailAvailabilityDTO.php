<?php

namespace App\Auth\Application\DTO;

final class EmailAvailabilityDTO
{
    public function __construct(
        public readonly bool $available
    ) {
    }
}
