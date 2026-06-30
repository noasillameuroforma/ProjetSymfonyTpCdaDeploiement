<?php

namespace App\Tests\Unit;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testOrderItemGettersAndSetters(): void
    {
        $order = new Order();
        $product = new Product();

        $product
            ->setName('Burger')
            ->setSlug('burger')
            ->setPrice('9.99');

        $orderItem = new OrderItem();

        $orderItem
            ->setQuantity(2)
            ->setUnitPrice(999)
            ->setLineTotal(1998)
            ->setOrderId($order)
            ->setProduct($product);

        $this->assertNull($orderItem->getId());
        $this->assertSame(2, $orderItem->getQuantity());
        $this->assertSame(999, $orderItem->getUnitPrice());
        $this->assertSame(1998, $orderItem->getLineTotal());
        $this->assertSame($order, $orderItem->getOrderId());
        $this->assertSame($product, $orderItem->getProduct());
    }
}