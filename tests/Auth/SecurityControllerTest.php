<?php

namespace App\Tests\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
    }

    public function testLoginPageIsSuccessful(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginPageContainsUsernameAndPasswordFields(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $this->assertGreaterThan(
            0,
            $crawler->filter('[name="_username"]')->count()
        );

        $this->assertGreaterThan(
            0,
            $crawler->filter('[name="_password"]')->count()
        );
    }

    public function testLoginWithInvalidCredentialsRedirectsBackToLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();

        $formData = [
            '_username' => 'unknown@example.com',
            '_password' => 'wrong-password',
        ];

        if ($crawler->filter('[name="_csrf_token"]')->count() > 0) {
            $formData['_csrf_token'] = $crawler->filter('[name="_csrf_token"]')->attr('value');
        }

        $this->client->submit($form, $formData);

        $this->assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Une mauvaise connexion doit rediriger.'
        );

        $this->client->followRedirect();

        $this->assertStringContainsString('/login', $this->client->getRequest()->getRequestUri());
    }

    public function testLoginWithValidCredentialsRedirects(): void
    {
        $email = 'login'.uniqid().'@example.com';
        $plainPassword = 'password123';

        $this->createUser($email, $plainPassword);

        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();

        $formData = [
            '_username' => $email,
            '_password' => $plainPassword,
        ];

        if ($crawler->filter('[name="_csrf_token"]')->count() > 0) {
            $formData['_csrf_token'] = $crawler->filter('[name="_csrf_token"]')->attr('value');
        }

        $this->client->submit($form, $formData);

        $this->assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Une connexion réussie doit rediriger.'
        );
    }

    public function testLogoutRouteRedirectsOrIsHandledByFirewall(): void
    {
        $email = 'logout'.uniqid().'@example.com';
        $plainPassword = 'password123';

        $this->createUser($email, $plainPassword);

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->filter('form')->form();

        $formData = [
            '_username' => $email,
            '_password' => $plainPassword,
        ];

        if ($crawler->filter('[name="_csrf_token"]')->count() > 0) {
            $formData['_csrf_token'] = $crawler->filter('[name="_csrf_token"]')->attr('value');
        }

        $this->client->submit($form, $formData);

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', '/logout');

        $this->assertTrue(
            $this->client->getResponse()->isRedirect() || $this->client->getResponse()->getStatusCode() === 302,
            'La route /logout doit être interceptée par le firewall.'
        );
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $existingUser = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $user = new User();

        $user
            ->setEmail($email)
            ->setFullName('User Auth Test')
            ->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}