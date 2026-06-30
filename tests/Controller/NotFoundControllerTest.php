<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotFoundControllerTest extends WebTestCase
{
    public function testUnknownPageReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/page-qui-nexiste-pas');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownProductReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/product/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownCategoryReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/category/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownOrderReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/order/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownOrderItemReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/order_item/999999999');

        $this->assertResponseStatusCodeSame(404);
    }
}