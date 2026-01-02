<?php

namespace App\Tests\Unit\Auth\Application\Command\LogoutUser;

use App\Auth\Application\Command\LogoutUser\LogoutUserCommand;
use App\Auth\Application\Command\LogoutUser\LogoutUserHandler;
use App\Auth\Domain\Service\TokenRevokerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogoutUserHandlerTest extends TestCase
{
    #[Test]
    public function testItLogsOutUser(): void
    {
        $tokenRevoker = $this->createMock(TokenRevokerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tokenValue = '1|abcd1234';

        $tokenRevoker->expects($this->once())
            ->method('revokeToken')
            ->with($this->callback(function ($tokenString) {
                return '1|abcd1234' === $tokenString->value();
            }));

        $handler = new LogoutUserHandler($tokenRevoker, $logger);

        $command = new LogoutUserCommand($tokenValue);

        $handler($command);
    }
}
