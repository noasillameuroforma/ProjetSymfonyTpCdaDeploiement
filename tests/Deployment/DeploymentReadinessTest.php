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

    public function testEnvFileIsNotCommittedWithProductionSecrets(): void
    {
        $root = dirname(__DIR__, 2);

        $envPath = $root.'/.env.local';

        $this->assertFileDoesNotExist(
            $envPath,
            '.env.local ne doit pas être commité dans le projet. Il doit être généré par GitHub Actions.'
        );
    }

    public function testGitHubWorkflowContainsDeploymentSteps(): void
    {
        $root = dirname(__DIR__, 2);

        $workflow = file_get_contents($root.'/.github/workflows/deploy.yml');

        $this->assertStringContainsString('composer install', $workflow);
        $this->assertStringContainsString('php bin/phpunit', $workflow);
        $this->assertStringContainsString('lftp', $workflow);
        $this->assertStringContainsString('mirror -R', $workflow);
        $this->assertStringContainsString('APP_ENV=prod', $workflow);
    }

    public function testSymfonyProductionCacheCommandExistsInWorkflow(): void
    {
        $root = dirname(__DIR__, 2);

        $workflow = file_get_contents($root.'/.github/workflows/deploy.yml');

        $this->assertStringContainsString('cache:clear', $workflow);
        $this->assertStringContainsString('APP_DEBUG=0', $workflow);
    }

    public function testWorkflowExcludesSensitiveOrUselessDirectories(): void
    {
        $root = dirname(__DIR__, 2);

        $workflow = file_get_contents($root.'/.github/workflows/deploy.yml');

        $this->assertStringContainsString('--exclude .git/', $workflow);
        $this->assertStringContainsString('--exclude .github/', $workflow);
        $this->assertStringContainsString('--exclude var/cache/', $workflow);
        $this->assertStringContainsString('--exclude var/log/', $workflow);
        $this->assertStringContainsString('--exclude node_modules/', $workflow);
    }

    public function testComposerLockIsUpToDateWithComposerJson(): void
    {
        $root = dirname(__DIR__, 2);

        $composerJson = filemtime($root.'/composer.json');
        $composerLock = filemtime($root.'/composer.lock');

        $this->assertGreaterThanOrEqual(
            $composerJson,
            $composerLock,
            'composer.lock semble plus ancien que composer.json. Lance composer update ou composer install puis commit composer.lock.'
        );
    }
}