<?php

namespace App\Auth\Application\Query\GetUserById;

final class CheckEmailAvailabilityQuery
{
    public function __construct(
        public readonly string $email
    ) {
    }
}
