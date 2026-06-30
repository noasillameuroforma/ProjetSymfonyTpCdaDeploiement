<?php

namespace App\Tests\Unit;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryGettersAndSetters(): void
    {
        $category = new Category();

        $category
            ->setName('Boissons')
            ->setSlug('boissons');

        $this->assertNull($category->getId());
        $this->assertSame('Boissons', $category->getName());
        $this->assertSame('boissons', $category->getSlug());
    }
}