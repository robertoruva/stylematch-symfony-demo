<?php

namespace App\Tests\Feature\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegisterControllerTest extends WebTestCase
{
    private const REGISTER_ENDPOINT = '/api/auth/register';

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

        // Shutdown kernel so createClient() can boot it again
        self::ensureKernelShutdown();
    }

    public function testItRegistersUserSuccessfully(): void
    {
        $client = static::createClient();

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
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('User registered successfully', $responseData['message']);
    }

    public function testItRejectsDuplicateEmail(): void
    {
        $client = static::createClient();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // First registration
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Duplicate registration
        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already exists', $responseData['message']);
    }

    public function testItRejectsMissingFields(): void
    {
        $client = static::createClient();

        $testCases = [
            ['email' => 'test@example.com', 'password' => 'password123'], // Missing name
            ['name' => 'John Doe', 'password' => 'password123'], // Missing email
            ['name' => 'John Doe', 'email' => 'test@example.com'], // Missing password
            [], // Missing all
        ];

        foreach ($testCases as $invalidData) {
            $client->request(
                'POST',
                self::REGISTER_ENDPOINT,
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

    public function testItRejectsInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function testItRejectsEmptyPassword(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function testItRejectsInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::REGISTER_ENDPOINT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json{'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
