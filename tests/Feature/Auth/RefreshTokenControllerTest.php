<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RefreshTokenControllerTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
    private const REFRESH_ENDPOINT = '/api/auth/refresh';
    private const ME_ENDPOINT = '/api/auth/me';

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

    public function testItRefreshesTokenSuccessfully(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client);
        $originalAccessToken = $tokens['access_token'];
        $originalRefreshToken = $tokens['refresh_token'];

        // Wait 1 second to ensure different JWT timestamps (iat claim)
        sleep(1);

        // Refresh token
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $originalRefreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);

        // Verify new tokens are different from original
        $this->assertNotEquals($originalAccessToken, $responseData['access_token']);
        $this->assertNotEquals($originalRefreshToken, $responseData['refresh_token']);

        // Verify JWT structure
        $newAccessToken = $responseData['access_token'];
        $parts = explode('.', $newAccessToken);
        $this->assertCount(3, $parts, 'JWT should have 3 parts');
    }

    public function testNewAccessTokenWorksForAuthenticatedRequests(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client);

        // Refresh token
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $tokens['refresh_token'],
            ])
        );

        $refreshData = json_decode($client->getResponse()->getContent(), true);
        $newAccessToken = $refreshData['access_token'];

        // Use new access token to get current user
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$newAccessToken]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $meData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $meData);
        $this->assertArrayHasKey('email', $meData);
    }

    public function testItRejectsInvalidRefreshToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => 'invalid-refresh-token',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
    }

    public function testItRejectsNonExistentRefreshToken(): void
    {
        $client = static::createClient();

        // Use a properly formatted but non-existent UUID
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => '550e8400-e29b-41d4-a716-446655440000',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItRejectsMissingRefreshToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Missing required field: refreshToken', $responseData['message']);
    }

    public function testOldRefreshTokenBecomesInvalidAfterRefresh(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client);
        $oldRefreshToken = $tokens['refresh_token'];

        // Refresh token (first time)
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $oldRefreshToken,
            ])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Try to use old refresh token again
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $oldRefreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItCanRefreshMultipleTimes(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client);
        $currentRefreshToken = $tokens['refresh_token'];

        // Refresh 3 times
        for ($i = 0; $i < 3; ++$i) {
            $client->request(
                'POST',
                self::REFRESH_ENDPOINT,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'refresh_token' => $currentRefreshToken,
                ])
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $refreshData = json_decode($client->getResponse()->getContent(), true);

            // Update current refresh token for next iteration
            $currentRefreshToken = $refreshData['refresh_token'];

            // Verify new access token works
            $client->request(
                'GET',
                self::ME_ENDPOINT,
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer '.$refreshData['access_token']]
            );
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }

    public function testItRejectsInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json{'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testItGeneratesDifferentTokensForDifferentUsers(): void
    {
        $client = static::createClient();

        // Register and login two users
        $user1Tokens = $this->registerAndLogin($client, 'user1@example.com', 'User One');
        $user2Tokens = $this->registerAndLogin($client, 'user2@example.com', 'User Two');

        // Refresh both users' tokens
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['refresh_token' => $user1Tokens['refresh_token']])
        );
        $user1NewTokens = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['refresh_token' => $user2Tokens['refresh_token']])
        );
        $user2NewTokens = json_decode($client->getResponse()->getContent(), true);

        // Verify tokens are different
        $this->assertNotEquals($user1NewTokens['access_token'], $user2NewTokens['access_token']);
        $this->assertNotEquals($user1NewTokens['refresh_token'], $user2NewTokens['refresh_token']);

        // Verify each token returns correct user
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$user1NewTokens['access_token']]
        );
        $user1Data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('user1@example.com', $user1Data['email']);

        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$user2NewTokens['access_token']]
        );
        $user2Data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('user2@example.com', $user2Data['email']);
    }

    private function registerAndLogin($client, string $email = 'john@example.com', string $name = 'John Doe'): array
    {
        // Register
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => $name,
                'email' => $email,
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
                'email' => $email,
                'password' => 'password123',
            ])
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
