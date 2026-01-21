<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MeControllerTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
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

    public function testItReturnsCurrentUserSuccessfully(): void
    {
        $client = static::createClient();

        // Register and login
        $tokens = $this->registerAndLogin($client, 'John Doe', 'john@example.com', 'password123');

        // Get current user
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token']]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertEquals('John Doe', $responseData['name']);
        $this->assertEquals('john@example.com', $responseData['email']);
        $this->assertArrayNotHasKey('password', $responseData, 'Password should not be included in response');
    }

    public function testItRequiresAuthentication(): void
    {
        $client = static::createClient();

        // Try to get current user without token
        $client->request('GET', self::ME_ENDPOINT);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItRejectsInvalidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.here']
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
                'GET',
                self::ME_ENDPOINT,
                [],
                [],
                ['HTTP_AUTHORIZATION' => $authHeader]
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }
    }

    public function testItReturnsCorrectUserForDifferentTokens(): void
    {
        $client = static::createClient();

        // Register and login two different users
        $user1Tokens = $this->registerAndLogin($client, 'User One', 'user1@example.com', 'password123');
        $user2Tokens = $this->registerAndLogin($client, 'User Two', 'user2@example.com', 'password456');

        // Get user 1 data
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $user1Tokens['access_token']]
        );
        $user1Data = json_decode($client->getResponse()->getContent(), true);

        // Get user 2 data
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $user2Tokens['access_token']]
        );
        $user2Data = json_decode($client->getResponse()->getContent(), true);

        // Verify each token returns the correct user
        $this->assertEquals('User One', $user1Data['name']);
        $this->assertEquals('user1@example.com', $user1Data['email']);

        $this->assertEquals('User Two', $user2Data['name']);
        $this->assertEquals('user2@example.com', $user2Data['email']);

        $this->assertNotEquals($user1Data['id'], $user2Data['id']);
    }

    public function testItHandlesExpiredToken(): void
    {
        $client = static::createClient();

        // Use a malformed/expired token
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.expired.token']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testItWorksImmediatelyAfterLogin(): void
    {
        $client = static::createClient();

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
        $loginData = json_decode($client->getResponse()->getContent(), true);

        // Immediately get user info
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $loginData['access_token']]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $meData = json_decode($client->getResponse()->getContent(), true);

        // Verify data consistency between login and /me
        $this->assertEquals($loginData['user']['id'], $meData['id']);
        $this->assertEquals($loginData['user']['name'], $meData['name']);
        $this->assertEquals($loginData['user']['email'], $meData['email']);
    }

    private function registerAndLogin($client, string $name, string $email, string $password): array
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
                'password' => $password,
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
                'password' => $password,
            ])
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
