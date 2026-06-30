<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class NavigationE2ETest extends PantherTestCase
{
    public function testHomeOrApiPageLoadsInBrowser(): void
{
    $client = static::createPantherClient();

    $client->request('GET', '/');

    $client->waitFor('body');

    $this->assertSelectorExists('body');
}

    public function testProductPageLoadsInBrowser(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/product');

        $this->assertPageTitleContains('');
        $this->assertSelectorExists('body');
        $this->assertStringContainsString('Product', $crawler->filter('body')->text());
    }

    public function testCategoryPageLoadsInBrowser(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/category');

        $this->assertSelectorExists('body');
        $this->assertStringContainsString('Category', $crawler->filter('body')->text());
    }

    public function testOrderPageLoadsInBrowser(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/order');

        $this->assertSelectorExists('body');
        $this->assertStringContainsString('Order', $crawler->filter('body')->text());
    }

    public function testOrderItemPageLoadsInBrowser(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/order_item');

        $this->assertSelectorExists('body');
        $this->assertStringContainsString('OrderItem', $crawler->filter('body')->text());
    }
}