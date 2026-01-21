<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LoginControllerTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';

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

    public function testItLogsInUserSuccessfully(): void
    {
        $client = static::createClient();

        // Register user first
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
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

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

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertArrayHasKey('user', $responseData);

        // Verify JWT token structure
        $accessToken = $responseData['access_token'];
        $this->assertNotEmpty($accessToken);
        $parts = explode('.', $accessToken);
        $this->assertCount(3, $parts, 'JWT should have 3 parts');

        // Verify refresh token
        $refreshToken = $responseData['refresh_token'];
        $this->assertNotEmpty($refreshToken);

        // Verify user data
        $this->assertArrayHasKey('id', $responseData['user']);
        $this->assertArrayHasKey('name', $responseData['user']);
        $this->assertArrayHasKey('email', $responseData['user']);
        $this->assertEquals('John Doe', $responseData['user']['name']);
        $this->assertEquals('john@example.com', $responseData['user']['email']);
    }

    public function testItRejectsInvalidCredentials(): void
    {
        $client = static::createClient();

        // Register user
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

        // Try login with wrong password
        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'john@example.com',
                'password' => 'wrongpassword',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Invalid credentials', $responseData['message']);
    }

    public function testItRejectsNonExistentUser(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'password123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
    }

    public function testItRejectsMissingFields(): void
    {
        $client = static::createClient();

        $testCases = [
            ['password' => 'password123'], // Missing email
            ['email' => 'test@example.com'], // Missing password
            [], // Missing all
        ];

        foreach ($testCases as $invalidData) {
            $client->request(
                'POST',
                self::LOGIN_ENDPOINT,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($invalidData)
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            $responseData = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertStringContainsString('Missing required fields', $responseData['message']);
        }
    }

    public function testItGeneratesUniqueTokensForDifferentLogins(): void
    {
        $client = static::createClient();

        // Register user
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

        // First login
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
        $firstLoginData = json_decode($client->getResponse()->getContent(), true);

        // Wait 1 second to ensure different JWT timestamps (iat claim)
        sleep(1);

        // Second login
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
        $secondLoginData = json_decode($client->getResponse()->getContent(), true);

        // Tokens should be different
        $this->assertNotEquals($firstLoginData['access_token'], $secondLoginData['access_token']);
        $this->assertNotEquals($firstLoginData['refresh_token'], $secondLoginData['refresh_token']);
    }

    public function testItRejectsInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json{'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
