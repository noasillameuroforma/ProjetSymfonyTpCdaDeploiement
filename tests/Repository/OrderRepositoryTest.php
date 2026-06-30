<?php

namespace App\Tests\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->repository = static::getContainer()
            ->get(OrderRepository::class);
    }

    public function testSaveAndFindOrder(): void
    {
        $user = $this->createUser();

        $order = new Order();
        $order
            ->setCreatedAt(new \DateTimeImmutable('2026-06-30 12:00:00'))
            ->setStatus('pending')
            ->setTotal('49.99')
            ->setUser($user);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $foundOrder = $this->repository->find($order->getId());

        $this->assertNotNull($foundOrder);
        $this->assertSame('pending', $foundOrder->getStatus());
        $this->assertSame('49.99', $foundOrder->getTotal());
        $this->assertSame($user->getId(), $foundOrder->getUser()->getId());
    }

    public function testFindOrdersByStatus(): void
    {
        $user = $this->createUser();

        $order = new Order();
        $order
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('paid')
            ->setTotal('79.99')
            ->setUser($user);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $orders = $this->repository->findBy([
            'status' => 'paid',
        ]);

        $this->assertNotEmpty($orders);
        $this->assertContainsOnlyInstancesOf(Order::class, $orders);
    }

    private function createUser(): User
    {
        $user = new User();
        $user
            ->setEmail('order-repository'.uniqid().'@example.com')
            ->setFullName('Order Repository User')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
