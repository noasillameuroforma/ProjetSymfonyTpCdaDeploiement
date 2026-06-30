<?php

namespace App\Tests\Unit;

use App\Entity\Category;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductGettersAndSetters(): void
    {
        $category = new Category();
        $category->setName('Plats')->setSlug('plats');

        $product = new Product();

        $product
            ->setName('Pizza Margherita')
            ->setSlug('pizza-margherita')
            ->setPrice('12.99')
            ->setDescription('Pizza tomate mozzarella')
            ->setCategory($category);

        $this->assertNull($product->getId());
        $this->assertSame('Pizza Margherita', $product->getName());
        $this->assertSame('pizza-margherita', $product->getSlug());
        $this->assertSame('12.99', $product->getPrice());
        $this->assertSame('Pizza tomate mozzarella', $product->getDescription());
        $this->assertSame($category, $product->getCategory());
    }
}