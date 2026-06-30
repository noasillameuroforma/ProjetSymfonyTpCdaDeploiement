<?php

namespace App\Tests\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    public function testRegisterPageIsSuccessful(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('[name="registration_form[FullName]"]');
        $this->assertSelectorExists('[name="registration_form[email]"]');
        $this->assertSelectorExists('[name="registration_form[plainPassword]"]');
        $this->assertSelectorExists('[name="registration_form[agreeTerms]"]');
    }

    public function testRegisterCreatesNewUser(): void
    {
        $email = 'register'.uniqid().'@example.com';

        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'registration_form[FullName]' => 'User Register Test',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        $this->assertNotNull($user);
        $this->assertSame($email, $user->getEmail());
        $this->assertSame('User Register Test', $user->getFullName());
        $this->assertNotSame('password123', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRegisterWithInvalidDataDoesNotCreateUser(): void
    {
        $email = 'invalid-register'.uniqid().'@example.com';

        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'registration_form[FullName]' => '',
            'registration_form[email]' => '',
            'registration_form[plainPassword]' => '',
            'registration_form[agreeTerms]' => false,
        ]);

        $this->client->submit($form);

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        $this->assertNull($user);
        $this->assertSelectorExists('form');
    }

    public function testRegisterWithDuplicateEmailDoesNotCreateSecondUser(): void
    {
        $email = 'duplicate-register'.uniqid().'@example.com';

        $this->createUserDirectly($email);

        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'registration_form[FullName]' => 'Duplicate User',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->client->submit($form);

        $users = $this->userRepository->findBy([
            'email' => $email,
        ]);

        $this->assertCount(1, $users);
    }

    private function createUserDirectly(string $email): void
    {
        $userClass = $this->userRepository->getClassName();
        $user = new $userClass();

        $user
            ->setEmail($email)
            ->setFullName('Existing User')
            ->setPassword('hashed_password')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}