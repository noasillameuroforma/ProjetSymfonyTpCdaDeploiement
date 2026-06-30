<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->repository = static::getContainer()
            ->get(UserRepository::class);
    }

    public function testSaveAndFindUser(): void
    {
        $email = 'user'.uniqid().'@example.com';

        $user = new User();
        $user
            ->setEmail($email)
            ->setFullName('User Repository Test')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->find($user->getId());

        $this->assertNotNull($foundUser);
        $this->assertSame($email, $foundUser->getEmail());
        $this->assertSame('User Repository Test', $foundUser->getFullName());
        $this->assertContains('ROLE_USER', $foundUser->getRoles());
    }

    public function testFindOneByEmail(): void
    {
        $email = 'find-email'.uniqid().'@example.com';

        $user = new User();
        $user
            ->setEmail($email)
            ->setFullName('Find Email Test')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy([
            'email' => $email,
        ]);

        $this->assertNotNull($foundUser);
        $this->assertSame($email, $foundUser->getEmail());
        $this->assertContains('ROLE_ADMIN', $foundUser->getRoles());
        $this->assertContains('ROLE_USER', $foundUser->getRoles());
    }
}