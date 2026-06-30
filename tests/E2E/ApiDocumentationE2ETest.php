<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class ApiDocumentationE2ETest extends PantherTestCase
{
    public function testApiDocumentationLoadsInBrowser(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/api');

        $client->waitFor('body');

        $this->assertSelectorExists('body');
        $this->assertStringContainsString('API', $client->getCrawler()->filter('body')->text());
    }
}