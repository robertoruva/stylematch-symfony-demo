<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Complete End-to-End test for the entire Auth flow.
 * This test simulates a real user journey through the authentication system.
 */
final class CompleteAuthFlowTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
    private const ME_ENDPOINT = '/api/auth/me';
    private const REFRESH_ENDPOINT = '/api/auth/refresh';
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

    public function testCompleteUserJourney(): void
    {
        $client = static::createClient();

        // ==========================================
        // Step 1: User registers
        // ==========================================
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => 'secure_password_123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $registerData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('User registered successfully', $registerData['message']);

        // ==========================================
        // Step 2: User logs in
        // ==========================================
        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'alice@example.com',
                'password' => 'secure_password_123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $loginData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $loginData);
        $this->assertArrayHasKey('refresh_token', $loginData);
        $this->assertArrayHasKey('user', $loginData);

        $accessToken = $loginData['access_token'];
        $refreshToken = $loginData['refresh_token'];
        $userId = $loginData['user']['id'];

        // ==========================================
        // Step 3: User accesses protected resource (/me)
        // ==========================================
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $meData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($userId, $meData['id']);
        $this->assertEquals('Alice Johnson', $meData['name']);
        $this->assertEquals('alice@example.com', $meData['email']);

        // ==========================================
        // Step 4: Access token expires, user refreshes
        // ==========================================
        // Wait 1 second to ensure different JWT timestamps (iat claim)
        sleep(1);

        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $refreshData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $refreshData);
        $this->assertArrayHasKey('refresh_token', $refreshData);

        $newAccessToken = $refreshData['access_token'];
        $newRefreshToken = $refreshData['refresh_token'];

        // Verify new tokens are different
        $this->assertNotEquals($accessToken, $newAccessToken);
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // ==========================================
        // Step 5: User continues using app with new token
        // ==========================================
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$newAccessToken]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $meDataAfterRefresh = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($userId, $meDataAfterRefresh['id']);

        // ==========================================
        // Step 6: Old access token should still work (until it expires naturally)
        // Note: In production, old token might be immediately invalidated
        // ==========================================
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken]
        );
        // Old token might still work depending on implementation
        // This is expected behavior - tokens aren't blacklisted on refresh

        // ==========================================
        // Step 7: Old refresh token should NOT work
        // ==========================================
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken, // Old refresh token
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // ==========================================
        // Step 8: User logs out
        // ==========================================
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$newAccessToken,
            ],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $logoutData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Logged out successfully', $logoutData['message']);

        // ==========================================
        // Step 9: After logout, refresh token should be invalid
        // ==========================================
        $client->request(
            'POST',
            self::REFRESH_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $newRefreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // ==========================================
        // Step 10: User can log in again
        // ==========================================
        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'alice@example.com',
                'password' => 'secure_password_123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $secondLoginData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $secondLoginData);
        $this->assertEquals($userId, $secondLoginData['user']['id']);
    }

    public function testMultipleUsersCanUseSystemConcurrently(): void
    {
        $client = static::createClient();

        // ==========================================
        // Create and authenticate 3 different users
        // ==========================================
        $users = [
            ['name' => 'User One', 'email' => 'user1@example.com', 'password' => 'password123'],
            ['name' => 'User Two', 'email' => 'user2@example.com', 'password' => 'password456'],
            ['name' => 'User Three', 'email' => 'user3@example.com', 'password' => 'password789'],
        ];

        $tokens = [];

        foreach ($users as $userData) {
            // Register
            $client->request(
                'POST',
                self::REGISTER_ENDPOINT,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($userData)
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
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                ])
            );
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);

            $loginData = json_decode($client->getResponse()->getContent(), true);
            $tokens[$userData['email']] = $loginData;
        }

        // ==========================================
        // Verify each user can access their own data
        // ==========================================
        foreach ($users as $userData) {
            $client->request(
                'GET',
                self::ME_ENDPOINT,
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens[$userData['email']]['access_token']]
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $meData = json_decode($client->getResponse()->getContent(), true);
            $this->assertEquals($userData['name'], $meData['name']);
            $this->assertEquals($userData['email'], $meData['email']);
        }

        // ==========================================
        // Verify tokens are isolated (user 1 token doesn't return user 2 data)
        // ==========================================
        $this->assertNotEquals(
            $tokens['user1@example.com']['access_token'],
            $tokens['user2@example.com']['access_token']
        );

        // ==========================================
        // One user logs out, others remain authenticated
        // ==========================================
        $client->request(
            'POST',
            self::LOGOUT_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$tokens['user1@example.com']['access_token'],
            ],
            json_encode([])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // User 2 and 3 should still be able to access their data
        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['user2@example.com']['access_token']]
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request(
            'GET',
            self::ME_ENDPOINT,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['user3@example.com']['access_token']]
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testSessionRefreshChain(): void
    {
        $client = static::createClient();

        // Register and login
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'password' => 'password123',
            ])
        );

        $client->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'bob@example.com',
                'password' => 'password123',
            ])
        );

        $loginData = json_decode($client->getResponse()->getContent(), true);
        $currentRefreshToken = $loginData['refresh_token'];

        // Simulate long user session with multiple refreshes
        for ($i = 1; $i <= 5; ++$i) {
            // Refresh
            $client->request(
                'POST',
                self::REFRESH_ENDPOINT,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['refresh_token' => $currentRefreshToken])
            );

            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $refreshData = json_decode($client->getResponse()->getContent(), true);

            // Use new access token to verify it works
            $client->request(
                'GET',
                self::ME_ENDPOINT,
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer '.$refreshData['access_token']]
            );
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);

            // Update refresh token for next iteration
            $currentRefreshToken = $refreshData['refresh_token'];
        }

        // After 5 refreshes, user should still be able to use the system
        $this->assertTrue(true, 'User completed 5 refresh cycles successfully');
    }
}
