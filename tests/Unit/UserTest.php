<?php

namespace App\Tests\Unit;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserGettersAndSetters(): void
    {
        $user = new User();

        $user
            ->setEmail('test@example.com')
            ->setFullName('Jean Dupont')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_ADMIN']);

        $this->assertNull($user->getId());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
        $this->assertSame('Jean Dupont', $user->getFullName());
        $this->assertSame('hashed_password', $user->getPassword());

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserHasRoleUserByDefault(): void
    {
        $user = new User();

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }
}