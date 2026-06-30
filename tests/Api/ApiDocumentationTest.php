<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocumentationTest extends WebTestCase
{
    public function testApiDocumentationPageIsSuccessful(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api');

        $this->assertResponseIsSuccessful();
    }

    public function testApiJsonLdDocumentationIsSuccessful(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/docs.jsonld');

        $this->assertResponseIsSuccessful();
    }
}