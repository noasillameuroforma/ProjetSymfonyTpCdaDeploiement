<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);
    }

    public function testCategoryIndexPageIsSuccessful(): void
    {
        $this->client->request('GET', '/category');

        $this->assertResponseIsSuccessful();
    }

    public function testCategoryNewPageIsSuccessful(): void
    {
        $this->client->request('GET', '/category/new');

        $this->assertResponseIsSuccessful();
    }

    public function testCategoryShowPageIsSuccessful(): void
    {
        $category = $this->createCategory();

        $this->client->request('GET', '/category/'.$category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $category->getName());
    }

    public function testCategoryEditPageIsSuccessful(): void
    {
        $category = $this->createCategory();

        $this->client->request('GET', '/category/'.$category->getId().'/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateCategoryWithPostRequest(): void
    {
        $crawler = $this->client->request('GET', '/category/new');

        $token = $crawler->filter('input[name="category[_token]"]')->count()
            ? $crawler->filter('input[name="category[_token]"]')->attr('value')
            : null;

        $formData = [
            'category' => [
                'name' => 'Category Created Test',
                'slug' => 'category-created-test-'.uniqid(),
            ],
        ];

        if ($token) {
            $formData['category']['_token'] = $token;
        }

        $this->client->request('POST', '/category/new', $formData);

        $this->assertResponseRedirects('/category', 303);
    }

    public function testDeleteCategory(): void
    {
        $category = $this->createCategory();

        $crawler = $this->client->request('GET', '/category/'.$category->getId());

        $token = $crawler->filter('input[name="_token"]')->count()
            ? $crawler->filter('input[name="_token"]')->attr('value')
            : static::getContainer()->get('security.csrf.token_manager')->getToken('delete'.$category->getId())->getValue();

        $this->client->request('POST', '/category/'.$category->getId(), [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/category', 303);
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category
            ->setName('Test Category '.uniqid())
            ->setSlug('test-category-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}