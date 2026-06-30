<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderItemRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderItemRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->repository = static::getContainer()
            ->get(OrderItemRepository::class);
    }

    public function testSaveAndFindOrderItem(): void
    {
        $orderItem = $this->createOrderItem();

        $foundOrderItem = $this->repository->find($orderItem->getId());

        $this->assertNotNull($foundOrderItem);
        $this->assertSame(2, $foundOrderItem->getQuantity());
        $this->assertSame(999, $foundOrderItem->getUnitPrice());
        $this->assertSame(1998, $foundOrderItem->getLineTotal());
        $this->assertNotNull($foundOrderItem->getOrderId());
        $this->assertNotNull($foundOrderItem->getProduct());
    }

    public function testFindOrderItemsByQuantity(): void
    {
        $this->createOrderItem();

        $orderItems = $this->repository->findBy([
            'quantity' => 2,
        ]);

        $this->assertNotEmpty($orderItems);
        $this->assertContainsOnlyInstancesOf(OrderItem::class, $orderItems);
    }

    private function createOrderItem(): OrderItem
    {
        $user = new User();
        $user
            ->setEmail('order-item-repository'.uniqid().'@example.com')
            ->setFullName('Order Item Repository User')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $order = new Order();
        $order
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('pending')
            ->setTotal('19.98')
            ->setUser($user);

        $category = new Category();
        $category
            ->setName('Repository OrderItem Category')
            ->setSlug('repository-order-item-category-'.uniqid());

        $product = new Product();
        $product
            ->setName('Repository OrderItem Product')
            ->setSlug('repository-order-item-product-'.uniqid())
            ->setPrice('9.99')
            ->setDescription('Produit test')
            ->setCategory($category);

        $orderItem = new OrderItem();
        $orderItem
            ->setQuantity(2)
            ->setUnitPrice(999)
            ->setLineTotal(1998)
            ->setOrderId($order)
            ->setProduct($product);

        $this->entityManager->persist($user);
        $this->entityManager->persist($order);
        $this->entityManager->persist($category);
        $this->entityManager->persist($product);
        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return $orderItem;
    }
}