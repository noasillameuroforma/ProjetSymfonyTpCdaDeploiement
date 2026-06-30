<?php

namespace App\Tests\Api;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testGetProductsCollection(): void
    {
        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneProduct(): void
    {
        $product = $this->createProduct();

        $this->client->request('GET', '/api/products/'.$product->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($product->getName(), $data['name']);
        $this->assertSame($product->getSlug(), $data['slug']);
        $this->assertSame($product->getPrice(), $data['price']);
        $this->assertSame($product->getDescription(), $data['description']);
    }

    public function testCreateProduct(): void
    {
        $category = $this->createCategory();

        $slug = 'api-created-product-'.uniqid();

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'name' => 'API Created Product',
                'slug' => $slug,
                'price' => '19.99',
                'description' => 'Produit créé via API',
                'category' => '/api/categories/'.$category->getId(),
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('API Created Product', $data['name']);
        $this->assertSame($slug, $data['slug']);
        $this->assertSame('19.99', $data['price']);
        $this->assertSame('Produit créé via API', $data['description']);
    }

    public function testUpdateProduct(): void
    {
        $product = $this->createProduct();

        $this->client->request(
            'PATCH',
            '/api/products/'.$product->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            json_encode([
                'name' => 'Updated Product',
                'price' => '99.99',
            ])
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Updated Product', $data['name']);
        $this->assertSame('99.99', $data['price']);
    }

    public function testDeleteProduct(): void
    {
        $product = $this->createProduct();

        $this->client->request('DELETE', '/api/products/'.$product->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testProductNotFound(): void
    {
        $this->client->request('GET', '/api/products/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    private function createCategory(): Category
    {
        $category = new Category();

        $category
            ->setName('API Product Category '.uniqid())
            ->setSlug('api-product-category-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(): Product
    {
        $category = $this->createCategory();

        $product = new Product();

        $product
            ->setName('API Product '.uniqid())
            ->setSlug('api-product-'.uniqid())
            ->setPrice('29.99')
            ->setDescription('Description API')
            ->setCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}