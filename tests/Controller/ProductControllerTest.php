<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testProductIndexPageIsSuccessful(): void
    {
        $this->client->request('GET', '/product');

        $this->assertResponseIsSuccessful();
    }

    public function testProductNewPageIsSuccessful(): void
    {
        $this->client->request('GET', '/product/new');

        $this->assertResponseIsSuccessful();
    }

    public function testProductShowPageIsSuccessful(): void
    {
        $product = $this->createProduct();

        $this->client->request('GET', '/product/'.$product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $product->getName());
    }

    public function testProductEditPageIsSuccessful(): void
    {
        $product = $this->createProduct();

        $this->client->request('GET', '/product/'.$product->getId().'/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateProductWithPostRequest(): void
    {
        $category = $this->createCategory();

        $crawler = $this->client->request('GET', '/product/new');

        $token = $crawler->filter('input[name="product[_token]"]')->count()
            ? $crawler->filter('input[name="product[_token]"]')->attr('value')
            : null;

        $formData = [
            'product' => [
                'name' => 'Product Created Test',
                'slug' => 'product-created-test-'.uniqid(),
                'price' => '14.99',
                'description' => 'Description test produit',
                'category' => $category->getId(),
            ],
        ];

        if ($token) {
            $formData['product']['_token'] = $token;
        }

        $this->client->request('POST', '/product/new', $formData);

        $this->assertResponseRedirects('/product', 303);
    }

    public function testDeleteProduct(): void
    {
        $product = $this->createProduct();

        $crawler = $this->client->request('GET', '/product/'.$product->getId());

        $token = $crawler->filter('input[name="_token"]')->count()
            ? $crawler->filter('input[name="_token"]')->attr('value')
            : static::getContainer()->get('security.csrf.token_manager')->getToken('delete'.$product->getId())->getValue();

        $this->client->request('POST', '/product/'.$product->getId(), [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/product', 303);
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category
            ->setName('Test Category '.uniqid())
            ->setSlug('test-category-product-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(): Product
    {
        $category = $this->createCategory();

        $product = new Product();
        $product
            ->setName('Test Product '.uniqid())
            ->setSlug('test-product-'.uniqid())
            ->setPrice('19.99')
            ->setDescription('Description test')
            ->setCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}