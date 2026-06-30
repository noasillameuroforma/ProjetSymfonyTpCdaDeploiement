<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testOrderIndexPageIsSuccessful(): void
    {
        $this->client->request('GET', '/order');

        $this->assertResponseIsSuccessful();
    }

    public function testOrderNewPageIsSuccessful(): void
    {
        $this->client->request('GET', '/order/new');

        $this->assertResponseIsSuccessful();
    }

    public function testOrderShowPageIsSuccessful(): void
    {
        $order = $this->createOrder();

        $this->client->request('GET', '/order/'.$order->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $order->getStatus());
    }

    public function testOrderEditPageIsSuccessful(): void
    {
        $order = $this->createOrder();

        $this->client->request('GET', '/order/'.$order->getId().'/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteOrder(): void
    {
        $order = $this->createOrder();

        $crawler = $this->client->request('GET', '/order/'.$order->getId());

        $token = $crawler->filter('input[name="_token"]')->count()
            ? $crawler->filter('input[name="_token"]')->attr('value')
            : static::getContainer()->get('security.csrf.token_manager')->getToken('delete'.$order->getId())->getValue();

        $this->client->request('POST', '/order/'.$order->getId(), [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/order', 303);
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
        $user = $this->createUser();

        $order = new Order();
        $order
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('pending')
            ->setTotal('29.99')
            ->setUser($user);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}