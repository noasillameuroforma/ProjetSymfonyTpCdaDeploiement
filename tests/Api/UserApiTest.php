<?php

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testGetUsersCollection(): void
    {
        $this->client->request('GET', '/api/users');

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneUser(): void
    {
        $user = $this->createUser();

        $this->client->request('GET', '/api/users/'.$user->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($user->getEmail(), $data['email']);
    }

    public function testDeleteUser(): void
    {
        $user = $this->createUser();

        $this->client->request('DELETE', '/api/users/'.$user->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testUserNotFound(): void
    {
        $this->client->request('GET', '/api/users/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateUserWithoutFullNameReturnsServerError(): void
    {
        $email = 'api-created-user'.uniqid().'@example.com';

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'email' => $email,
                'roles' => ['ROLE_USER'],
                'password' => 'hashed_password',
            ])
        );

        $this->assertResponseStatusCodeSame(500);
    }

    private function createUser(): User
    {
        $user = new User();

        $user
            ->setEmail('api-user'.uniqid().'@example.com')
            ->setFullName('API User')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}