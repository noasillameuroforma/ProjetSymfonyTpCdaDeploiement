<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HttpMethodTest extends WebTestCase
{
    public function testPutCategoryIndexIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('PUT', '/category');
    }

    public function testPutProductIndexIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('PUT', '/product');
    }

    public function testPutOrderIndexIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('PUT', '/order');
    }

    public function testPutOrderItemIndexIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('PUT', '/order_item');
    }

    public function testDeleteCategoryNewIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('DELETE', '/category/new');
    }

    public function testDeleteProductNewIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('DELETE', '/product/new');
    }

    public function testDeleteOrderNewIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('DELETE', '/order/new');
    }

    public function testDeleteOrderItemNewIsNotSuccessful(): void
    {
        $this->assertInvalidMethod('DELETE', '/order_item/new');
    }

    private function assertInvalidMethod(string $method, string $url): void
    {
        $client = static::createClient();

        $client->request($method, $url);

        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [404, 405, 302]
        );
    }
}