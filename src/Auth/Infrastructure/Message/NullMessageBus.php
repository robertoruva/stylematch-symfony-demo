<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Message;

use App\Auth\Application\Message\MessageBusInterface;
use App\Auth\Application\Message\MessageInterface;

final class NullMessageBus implements MessageBusInterface
{
    public function publish(MessageInterface $message, string $queue): void
    {
        // no-op: placeholder until real bus (Symfony Messenger / RabbitMQ)
    }
}
