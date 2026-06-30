<?php

namespace App\Tests\Unit;

use App\Entity\Order;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testOrderGettersAndSetters(): void
    {
        $user = new User();
        $user
            ->setEmail('client@example.com')
            ->setFullName('Client Test')
            ->setPassword('hashed_password');

        $createdAt = new \DateTimeImmutable('2026-06-30 20:00:00');

        $order = new Order();

        $order
            ->setCreatedAt($createdAt)
            ->setStatus('pending')
            ->setTotal('29.99')
            ->setUser($user);

        $this->assertNull($order->getId());
        $this->assertSame($createdAt, $order->getCreatedAt());
        $this->assertSame('pending', $order->getStatus());
        $this->assertSame('29.99', $order->getTotal());
        $this->assertSame($user, $order->getUser());
    }
}