<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderItemControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testOrderItemIndexPageIsSuccessful(): void
    {
        $this->client->request('GET', '/order_item');

        $this->assertResponseIsSuccessful();
    }

    public function testOrderItemNewPageIsSuccessful(): void
    {
        $this->client->request('GET', '/order_item/new');

        $this->assertResponseIsSuccessful();
    }

    public function testOrderItemShowPageIsSuccessful(): void
    {
        $orderItem = $this->createOrderItem();

        $this->client->request('GET', '/order_item/'.$orderItem->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', (string) $orderItem->getQuantity());
    }

    public function testOrderItemEditPageIsSuccessful(): void
    {
        $orderItem = $this->createOrderItem();

        $this->client->request('GET', '/order_item/'.$orderItem->getId().'/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteOrderItem(): void
    {
        $orderItem = $this->createOrderItem();

        $crawler = $this->client->request('GET', '/order_item/'.$orderItem->getId());

        $token = $crawler->filter('input[name="_token"]')->count()
            ? $crawler->filter('input[name="_token"]')->attr('value')
            : static::getContainer()->get('security.csrf.token_manager')->getToken('delete'.$orderItem->getId())->getValue();

        $this->client->request('POST', '/order_item/'.$orderItem->getId(), [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/order_item', 303);
    }

    private function createUser(): User
    {
        $user = new User();
        $user
            ->setEmail('client'.uniqid().'@example.com')
            ->setFullName('Client Test')
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

    private function createProduct(): Product
    {
        $category = new Category();
        $category
            ->setName('Test Category '.uniqid())
            ->setSlug('test-category-order-item-'.uniqid());

        $product = new Product();
        $product
            ->setName('Test Product '.uniqid())
            ->setSlug('test-product-order-item-'.uniqid())
            ->setPrice('9.99')
            ->setDescription('Description test')
            ->setCategory($category);

        $this->entityManager->persist($category);
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