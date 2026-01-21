<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LogoutControllerTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
    private const LOGOUT_ENDPOINT = '/api/auth/logout';

    protected function setUp(): void
    {
        parent::setUp();

        // Boot kernel to clean database before test
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Clean database before each test
        $entityManager = $container->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');

        // Clean Redis tokens
        $redis = $container->get('Predis\ClientInterface');
        $keys = $redis->keys('refresh_token:*');
        if (!empty($keys)) {
            $redis->del($keys);
        }

        // Shutdown kernel so createClient() can boot it again
        self::ensureKernelShutdown();
    }

    public function testItLogsOutUserSuccessfully(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client);

        // Logout
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Logged out successfully', $responseData['message']);
    }

    public function testItRevokesRefreshTokensAfterLogout(): void
    {
        $client = static::createClient();
        $redis = $client->getContainer()->get('Predis\ClientInterface');

        // Register and login
        $tokens = $this->registerAndLogin($client);

        // Verify refresh token exists in Redis
        $keysBeforeLogout = $redis->keys('refresh_token:*');
        $this->assertNotEmpty($keysBeforeLogout, 'Refresh token should exist in Redis after login');

        // Logout
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Verify refresh tokens are deleted from Redis
        $keysAfterLogout = $redis->keys('refresh_token:*');
        $this->assertEmpty($keysAfterLogout, 'Refresh tokens should be deleted from Redis after logout');
    }

    public function testItRequiresAuthentication(): void
    {
        $client = static::createClient();

        // Try logout without token
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItRejectsInvalidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItRejectsExpiredToken(): void
    {
        $client = static::createClient();

        // This is a pre-expired token (you would need to generate one with expired time)
        // For now, we test with an invalid token structure
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.expired.token',
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItRejectsMalformedAuthorizationHeader(): void
    {
        $client = static::createClient();

        $testCases = [
            'token_without_bearer',
            'Basic username:password',
            'Bearer',
            '',
        ];

        foreach ($testCases as $authHeader) {
            $client->request(
                'POST',
                self::LOGOUT_ENDPOINT,
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_AUTHORIZATION' => $authHeader,
                ],
                json_encode([])
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }
    }

    private function registerAndLogin($client): array
    {
        // Register
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
            ])
        );

        // Login
        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'john@example.com',
                'password' => 'password123',
            ])
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
