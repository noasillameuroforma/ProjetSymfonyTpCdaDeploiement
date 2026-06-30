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
            '.env.local ne doit pas être commité. Il doit être généré par GitHub Actions.'
        );
    }

    public function testGitHubWorkflowContainsDeploymentSteps(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('composer install', $workflow);
        $this->assertStringContainsString('php bin/phpunit', $workflow);
        $this->assertStringContainsString('lftp', $workflow);
        $this->assertStringContainsString('mirror -R', $workflow);
        $this->assertStringContainsString('APP_ENV=prod', $workflow);
        $this->assertStringContainsString('/tp_cda/tp_cda_prof', $workflow);
    }

    public function testSymfonyProductionCommandsExistInWorkflow(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('APP_DEBUG=0', $workflow);
        $this->assertStringContainsString('doctrine:migrations:migrate', $workflow);
    }

    public function testWorkflowExcludesSensitiveOrUselessDirectories(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--exclude .git/', $workflow);
        $this->assertStringContainsString('--exclude .github/', $workflow);
        $this->assertStringContainsString('--exclude .env', $workflow);
        $this->assertStringContainsString('--exclude .env.test', $workflow);
        $this->assertStringContainsString('--exclude var/cache/', $workflow);
        $this->assertStringContainsString('--exclude var/log/', $workflow);
        $this->assertStringContainsString('--exclude node_modules/', $workflow);
        $this->assertStringContainsString('--exclude tests/', $workflow);
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