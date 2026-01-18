<?php


namespace App\Auth\Application\Query\GetCurrentUser;

final readonly class GetCurrentUserQuery
{
    public function __construct(
        public string $userId
    ) {}
}
