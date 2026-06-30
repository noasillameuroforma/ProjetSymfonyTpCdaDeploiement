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
        $this->assertSelectorExists('input[type="password"]');
    }

    public function testLoginPageContainsIdentifierAndPasswordFields(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $usernameField = $this->findUsernameFieldName($crawler);
        $passwordField = $this->findPasswordFieldName($crawler);

        $this->assertNotNull($usernameField, 'Aucun champ identifiant/email trouvé dans le formulaire login.');
        $this->assertNotNull($passwordField, 'Aucun champ password trouvé dans le formulaire login.');
    }

    public function testLoginWithInvalidCredentialsRedirectsBackToLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $usernameField = $this->findUsernameFieldName($crawler);
        $passwordField = $this->findPasswordFieldName($crawler);

        $this->assertNotNull($usernameField);
        $this->assertNotNull($passwordField);

        $form = $crawler->filter('form')->form();

        $form[$usernameField] = 'unknown@example.com';
        $form[$passwordField] = 'wrong-password';

        $this->client->submit($form);

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

        $usernameField = $this->findUsernameFieldName($crawler);
        $passwordField = $this->findPasswordFieldName($crawler);

        $this->assertNotNull($usernameField);
        $this->assertNotNull($passwordField);

        $form = $crawler->filter('form')->form();

        $form[$usernameField] = $email;
        $form[$passwordField] = $plainPassword;

        $this->client->submit($form);

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

        $this->assertResponseIsSuccessful();

        $usernameField = $this->findUsernameFieldName($crawler);
        $passwordField = $this->findPasswordFieldName($crawler);

        $this->assertNotNull($usernameField);
        $this->assertNotNull($passwordField);

        $form = $crawler->filter('form')->form();

        $form[$usernameField] = $email;
        $form[$passwordField] = $plainPassword;

        $this->client->submit($form);

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

    private function findUsernameFieldName($crawler): ?string
    {
        $possibleNames = [
            '_username',
            'email',
            'username',
            'login',
            'security[email]',
            'login_form[email]',
        ];

        foreach ($possibleNames as $name) {
            if ($crawler->filter(sprintf('[name="%s"]', $name))->count() > 0) {
                return $name;
            }
        }

        $emailInput = $crawler->filter('input[type="email"]');

        if ($emailInput->count() > 0) {
            return $emailInput->first()->attr('name');
        }

        $textInput = $crawler->filter('input[type="text"]');

        if ($textInput->count() > 0) {
            return $textInput->first()->attr('name');
        }

        return null;
    }

    private function findPasswordFieldName($crawler): ?string
    {
        $passwordInput = $crawler->filter('input[type="password"]');

        if ($passwordInput->count() > 0) {
            return $passwordInput->first()->attr('name');
        }

        return null;
    }
}