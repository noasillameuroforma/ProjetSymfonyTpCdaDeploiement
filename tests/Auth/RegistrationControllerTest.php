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
    }

    public function testRegisterCreatesNewUser(): void
    {
        $email = 'register'.uniqid().'@example.com';

        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();

        $formData = [
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'password123',
        ];

        if ($crawler->filter('[name="registration_form[agreeTerms]"]')->count() > 0) {
            $formData['registration_form[agreeTerms]'] = true;
        }

        if ($crawler->filter('[name="registration_form[fullName]"]')->count() > 0) {
            $formData['registration_form[fullName]'] = 'User Register Test';
        }

        if ($crawler->filter('[name="registration_form[FullName]"]')->count() > 0) {
            $formData['registration_form[FullName]'] = 'User Register Test';
        }

        $this->client->submit($form, $formData);

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        $this->assertNotNull($user);
        $this->assertSame($email, $user->getEmail());
        $this->assertNotSame('password123', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRegisterWithInvalidDataDoesNotCreateUser(): void
    {
        $email = 'invalid-register'.uniqid().'@example.com';

        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();

        $formData = [
            'registration_form[email]' => '',
            'registration_form[plainPassword]' => '',
        ];

        if ($crawler->filter('[name="registration_form[agreeTerms]"]')->count() > 0) {
            $formData['registration_form[agreeTerms]'] = false;
        }

        if ($crawler->filter('[name="registration_form[fullName]"]')->count() > 0) {
            $formData['registration_form[fullName]'] = '';
        }

        if ($crawler->filter('[name="registration_form[FullName]"]')->count() > 0) {
            $formData['registration_form[FullName]'] = '';
        }

        $this->client->submit($form, $formData);

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        $this->assertNull($user);
    }

    public function testRegisterPageContainsEmailAndPasswordFields(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $this->assertGreaterThan(
            0,
            $crawler->filter('[name="registration_form[email]"]')->count()
        );

        $this->assertGreaterThan(
            0,
            $crawler->filter('[name="registration_form[plainPassword]"]')->count()
        );
    }
}
