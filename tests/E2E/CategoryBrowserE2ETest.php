<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class CategoryBrowserE2ETest extends PantherTestCase
{
    public function testCreateCategoryFromBrowser(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/category/new');

        $this->assertSelectorExists('form');

        $form = $crawler->selectButton('Save')->form([
            'category[name]' => 'Catégorie E2E',
            'category[slug]' => 'categorie-e2e-'.uniqid(),
        ]);

        $client->submit($form);

        $client->waitFor('body');

        $this->assertStringContainsString('/category', $client->getCurrentURL());
        $this->assertSelectorExists('body');
    }
}