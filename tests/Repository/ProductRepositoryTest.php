<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ProductRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->repository = static::getContainer()
            ->get(ProductRepository::class);
    }

    public function testSaveAndFindProduct(): void
    {
        $category = new Category();
        $category
            ->setName('Repository Category Product')
            ->setSlug('repository-category-product-'.uniqid());

        $product = new Product();
        $product
            ->setName('Repository Product')
            ->setSlug('repository-product-'.uniqid())
            ->setPrice('15.99')
            ->setDescription('Produit de test repository')
            ->setCategory($category);

        $this->entityManager->persist($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $foundProduct = $this->repository->find($product->getId());

        $this->assertNotNull($foundProduct);
        $this->assertSame($product->getName(), $foundProduct->getName());
        $this->assertSame($product->getSlug(), $foundProduct->getSlug());
        $this->assertSame($product->getPrice(), $foundProduct->getPrice());
        $this->assertSame($category->getId(), $foundProduct->getCategory()->getId());
    }

    public function testFindAllProducts(): void
    {
        $category = new Category();
        $category
            ->setName('Repository Category Product All')
            ->setSlug('repository-category-product-all-'.uniqid());

        $product = new Product();
        $product
            ->setName('Repository Product All')
            ->setSlug('repository-product-all-'.uniqid())
            ->setPrice('25.99')
            ->setDescription('Produit de test findAll')
            ->setCategory($category);

        $this->entityManager->persist($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $products = $this->repository->findAll();

        $this->assertNotEmpty($products);
        $this->assertContainsOnlyInstancesOf(Product::class, $products);
    }
}




