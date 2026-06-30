<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->repository = static::getContainer()
            ->get(CategoryRepository::class);
    }

    public function testSaveAndFindCategory(): void
    {
        $category = new Category();
        $category
            ->setName('Repository Category')
            ->setSlug('repository-category-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $foundCategory = $this->repository->find($category->getId());

        $this->assertNotNull($foundCategory);
        $this->assertSame($category->getName(), $foundCategory->getName());
        $this->assertSame($category->getSlug(), $foundCategory->getSlug());
    }

    public function testFindAllCategories(): void
    {
        $category = new Category();
        $category
            ->setName('Repository Category All')
            ->setSlug('repository-category-all-'.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $categories = $this->repository->findAll();

        $this->assertNotEmpty($categories);
        $this->assertContainsOnlyInstancesOf(Category::class, $categories);
    }
}