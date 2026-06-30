<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class ProductBrowserE2ETest extends PantherTestCase
{
    public function testProductNewPageDisplaysFormInBrowser(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/product/new');

        $client->waitFor('form');

        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="product[name]"]');
        $this->assertSelectorExists('input[name="product[slug]"]');
        $this->assertSelectorExists('input[name="product[price]"]');
        $this->assertSelectorExists('textarea[name="product[description]"]');
        $this->assertSelectorExists('select[name="product[category]"]');
    }
}