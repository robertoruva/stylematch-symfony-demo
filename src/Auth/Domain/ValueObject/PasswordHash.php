<?php

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\InvalidPasswordException;

final class PasswordHash
{
    private function __construct(private string $hash)
    {
    }

    public static function fromPlainText(string $password): self
    {
        if (strlen($password) < 8) {
            throw InvalidPasswordException::tooShort();
        }

        return new self(password_hash($password, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        if (!self::isValidHash($hash)) {
            throw InvalidPasswordException::invalidHash();
        }

        return new self($hash);
    }

    public function matches(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hash);
    }

    public function value(): string
    {
        return $this->hash;
    }

    private static function isValidHash(string $hash): bool
    {
        $info = password_get_info($hash);

        return 'bcrypt' === $info['algoName'];
    }
}
