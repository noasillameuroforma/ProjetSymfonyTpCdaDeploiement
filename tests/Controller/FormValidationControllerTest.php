<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormValidationControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCategoryEmptyFormDoesNotRedirect(): void
    {
        $this->client->request('POST', '/category/new', [
            'category' => [
                'name' => '',
                'slug' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testProductEmptyFormDoesNotRedirect(): void
    {
        $this->client->request('POST', '/product/new', [
            'product' => [
                'name' => '',
                'slug' => '',
                'price' => '',
                'description' => '',
                'category' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}