<?php

namespace App\Auth\Application\Query\CheckEmailAvailability;

final class CheckEmailAvailabilityQuery
{
    public function __construct(
        public readonly string $email
    ) {
    }
}
