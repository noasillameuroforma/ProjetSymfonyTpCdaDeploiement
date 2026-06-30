<?php

namespace App\Tests\Api;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testGetOrdersCollection(): void
    {
        $this->client->request('GET', '/api/orders');

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneOrder(): void
    {
        $order = $this->createOrder();

        $this->client->request('GET', '/api/orders/'.$order->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($order->getStatus(), $data['status']);
        $this->assertSame($order->getTotal(), $data['total']);
    }

    public function testCreateOrder(): void
    {
        $user = $this->createUser();

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'createdAt' => '2026-06-30T20:00:00+00:00',
                'status' => 'pending',
                'total' => '59.99',
                'user' => '/api/users/'.$user->getId(),
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('pending', $data['status']);
        $this->assertSame('59.99', $data['total']);
    }

    public function testUpdateOrder(): void
    {
        $order = $this->createOrder();

        $this->client->request(
            'PATCH',
            '/api/orders/'.$order->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'status' => 'paid',
                'total' => '89.99',
            ])
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('paid', $data['status']);
        $this->assertSame('89.99', $data['total']);
    }

    public function testDeleteOrder(): void
    {
        $order = $this->createOrder();

        $this->client->request('DELETE', '/api/orders/'.$order->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testOrderNotFound(): void
    {
        $this->client->request('GET', '/api/orders/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    private function createUser(): User
    {
        $user = new User();

        $user
            ->setEmail('api-order-user'.uniqid().'@example.com')
            ->setFullName('API Order User')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createOrder(): Order
    {
        $order = new Order();

        $order
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('pending')
            ->setTotal('49.99')
            ->setUser($this->createUser());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}