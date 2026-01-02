<?php

namespace App\Auth\Application\Message;

final class UserRegisteredMessage implements MessageInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $occurredAt
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'occurredAt' => $this->occurredAt,
        ];
    }

    /**
     * @param array{
     *      userId: string,
     *      name: string,
     *      email: string,
     *      occurredAt: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['userId'],
            name: $data['name'],
            email: $data['email'],
            occurredAt: $data['occurredAt']
        );
    }
}
