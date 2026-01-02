<?php

namespace App\Auth\Application\Message;

interface MessageInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
