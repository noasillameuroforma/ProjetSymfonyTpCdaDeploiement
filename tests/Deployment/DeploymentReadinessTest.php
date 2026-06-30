<?php

namespace App\Tests\Deployment;

use PHPUnit\Framework\TestCase;

class DeploymentReadinessTest extends TestCase
{
    public function testRequiredDeploymentFilesExist(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileExists($root.'/composer.json');
        $this->assertFileExists($root.'/composer.lock');
        $this->assertFileExists($root.'/bin/console');
        $this->assertFileExists($root.'/public/index.php');
        $this->assertFileExists($root.'/.github/workflows/deploy.yml');
    }

    public function testPublicDirectoryExists(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertDirectoryExists($root.'/public');
        $this->assertFileExists($root.'/public/index.php');
    }

    public function testEnvLocalIsNotCommitted(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileDoesNotExist(
            $root.'/.env.local',
            '.env.local ne doit pas être commité. Le workflow utilise un .env généré par GitHub Actions.'
        );
    }

    public function testGitHubWorkflowContainsMainDeploymentSteps(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('composer install', $workflow);
        $this->assertStringContainsString('php bin/phpunit', $workflow);
        $this->assertStringContainsString('lftp', $workflow);
        $this->assertStringContainsString('mirror -R', $workflow);
        $this->assertStringContainsString('/tp_cda/tp_cda_prof', $workflow);
    }

    public function testWorkflowDebugsDatabaseUrlSecret(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Debug DATABASE_URL', $workflow);
        $this->assertStringContainsString('Longueur DATABASE_URL', $workflow);
        $this->assertStringContainsString('DATABASE_URL brute masquée par GitHub', $workflow);
        $this->assertStringContainsString('parse_url', $workflow);
        $this->assertStringContainsString('Host MySQL utilisé', $workflow);
    }

    public function testWorkflowCreatesSymfonyProductionEnvFile(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('APP_ENV=prod', $workflow);
        $this->assertStringContainsString('APP_DEBUG=0', $workflow);
        $this->assertStringContainsString('APP_SECRET', $workflow);
        $this->assertStringContainsString('DEFAULT_URI', $workflow);
        $this->assertStringContainsString('DATABASE_URL', $workflow);
        $this->assertStringContainsString('echo "DATABASE_URL=\"$DATABASE_URL\"" >> .env', $workflow);
        $this->assertStringContainsString('> .env', $workflow);
    }

    public function testWorkflowRunsDoctrineMigrations(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('doctrine:migrations:migrate', $workflow);
        $this->assertStringContainsString('APP_ENV=prod APP_DEBUG=0 DATABASE_URL="$DATABASE_URL"', $workflow);
    }

    public function testWorkflowClearsSymfonyProductionCache(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('cache:clear', $workflow);
        $this->assertStringContainsString('APP_ENV=prod APP_DEBUG=0 DATABASE_URL="$DATABASE_URL"', $workflow);
    }

    public function testWorkflowInstallsProductionDependenciesWithoutComposerScripts(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--no-dev', $workflow);
        $this->assertStringContainsString('--optimize-autoloader', $workflow);
        $this->assertStringContainsString('--no-scripts', $workflow);
    }

    public function testWorkflowExcludesSensitiveOrUselessDirectories(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--exclude .git/', $workflow);
        $this->assertStringContainsString('--exclude .github/', $workflow);
        $this->assertStringContainsString('--exclude .env.test', $workflow);
        $this->assertStringContainsString('--exclude .env.local', $workflow);
        $this->assertStringContainsString('--exclude var/cache/', $workflow);
        $this->assertStringContainsString('--exclude var/log/', $workflow);
        $this->assertStringContainsString('--exclude node_modules/', $workflow);
        $this->assertStringContainsString('--exclude tests/', $workflow);
    }

    public function testProductionEnvFileIsUploadedToServer(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringNotContainsString('--exclude .env ', $workflow);
        $this->assertStringNotContainsString('--exclude .env \\', $workflow);
    }

    public function testComposerLockExistsAndIsReadable(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileExists($root.'/composer.lock');

        $composerLock = json_decode(file_get_contents($root.'/composer.lock'), true);

        $this->assertIsArray($composerLock);
        $this->assertArrayHasKey('packages', $composerLock);
        $this->assertArrayHasKey('packages-dev', $composerLock);
    }

    private function getWorkflowContent(): string
    {
        $root = dirname(__DIR__, 2);
        $workflowPath = $root.'/.github/workflows/deploy.yml';

        $this->assertFileExists($workflowPath);

        return file_get_contents($workflowPath);
    }
}