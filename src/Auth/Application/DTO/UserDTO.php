<?php

namespace App\Auth\Application\DTO;

final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @return array{id: string, name: string, email: string, createdAt: string, updatedAt: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
