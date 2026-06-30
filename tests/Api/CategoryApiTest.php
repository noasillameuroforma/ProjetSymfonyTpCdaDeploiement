<?php

namespace App\Tests\Api;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testGetCategoriesCollection(): void
    {
        $this->client->request('GET', '/api/categories');

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneCategory(): void
    {
        $category = $this->createCategory();

        $this->client->request('GET', '/api/categories/'.$category->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($category->getName(), $data['name']);
        $this->assertSame($category->getSlug(), $data['slug']);
    }

    public function testCreateCategory(): void
    {
        $slug = 'api-created-category-'.uniqid();

        $this->client->request(
            'POST',
            '/api/categories',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'name' => 'API Created Category',
                'slug' => $slug,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('API Created Category', $data['name']);
        $this->assertSame($slug, $data['slug']);
    }

    public function testUpdateCategory(): void
    {
        $category = $this->createCategory();

        $this->client->request(
            'PATCH',
            '/api/categories/'.$category->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'name' => 'Updated Category',
            ])
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Updated Category', $data['name']);
    }

    public function testDeleteCategory(): void
    {
        $category = $this->createCategory();

        $this->client->request('DELETE', '/api/categories/'.$category->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCategoryNotFound(): void
    {
        $this->client->request('GET', '/api/categories/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    private function createCategory(): Category
    {
        $category = new Category();

        $category
            ->setName('API Category '.uniqid())
            ->setSlug('api-category-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}