<?php

namespace App\Auth\Application\Message;

interface MessageBusInterface
{
    public function publish(MessageInterface $message, string $queue): void;
}
