<?php

namespace App\Tests\Api;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderItemApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testGetOrderItemsCollection(): void
    {
        $this->client->request('GET', '/api/order_items');

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneOrderItem(): void
    {
        $orderItem = $this->createOrderItem();

        $this->client->request('GET', '/api/order_items/'.$orderItem->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($orderItem->getQuantity(), $data['quantity']);
        $this->assertSame($orderItem->getUnitPrice(), $data['unitPrice']);
        $this->assertSame($orderItem->getLineTotal(), $data['lineTotal']);
    }

    public function testCreateOrderItem(): void
    {
        $order = $this->createOrder();
        $product = $this->createProduct();

        $this->client->request(
            'POST',
            '/api/order_items',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'quantity' => 3,
                'unitPrice' => 1500,
                'lineTotal' => 4500,
                'orderId' => '/api/orders/'.$order->getId(),
                'product' => '/api/products/'.$product->getId(),
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(3, $data['quantity']);
        $this->assertSame(1500, $data['unitPrice']);
        $this->assertSame(4500, $data['lineTotal']);
    }

    public function testUpdateOrderItem(): void
    {
        $orderItem = $this->createOrderItem();

        $this->client->request(
            'PATCH',
            '/api/order_items/'.$orderItem->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'quantity' => 5,
                'lineTotal' => 4995,
            ])
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(5, $data['quantity']);
        $this->assertSame(4995, $data['lineTotal']);
    }

    public function testDeleteOrderItem(): void
    {
        $orderItem = $this->createOrderItem();

        $this->client->request('DELETE', '/api/order_items/'.$orderItem->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testOrderItemNotFound(): void
    {
        $this->client->request('GET', '/api/order_items/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    private function createUser(): User
    {
        $user = new User();

        $user
            ->setEmail('api-order-item-user'.uniqid().'@example.com')
            ->setFullName('API OrderItem User')
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
            ->setTotal('19.98')
            ->setUser($this->createUser());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function createCategory(): Category
    {
        $category = new Category();

        $category
            ->setName('API OrderItem Category '.uniqid())
            ->setSlug('api-order-item-category-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(): Product
    {
        $product = new Product();

        $product
            ->setName('API OrderItem Product '.uniqid())
            ->setSlug('api-order-item-product-'.uniqid())
            ->setPrice('9.99')
            ->setDescription('Produit test API')
            ->setCategory($this->createCategory());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createOrderItem(): OrderItem
    {
        $orderItem = new OrderItem();

        $orderItem
            ->setQuantity(2)
            ->setUnitPrice(999)
            ->setLineTotal(1998)
            ->setOrderId($this->createOrder())
            ->setProduct($this->createProduct());

        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return $orderItem;
    }
}