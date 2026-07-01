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
            '.env.local ne doit pas être commité. Le workflow génère un .env de production.'
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

    public function testWorkflowCreatesTestEnvironment(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Créer le fichier .env.test', $workflow);
        $this->assertStringContainsString('APP_ENV=test', $workflow);
        $this->assertStringContainsString('sqlite:///%kernel.project_dir%/var/test.db', $workflow);
        $this->assertStringContainsString('doctrine:schema:update --force --env=test', $workflow);
    }

    public function testWorkflowRunsMainTestSuites(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('tests/Api', $workflow);
        $this->assertStringContainsString('tests/Auth', $workflow);
        $this->assertStringContainsString('tests/Controller', $workflow);
        $this->assertStringContainsString('tests/Deployment', $workflow);
        $this->assertStringContainsString('tests/Functional', $workflow);
        $this->assertStringContainsString('tests/Repository', $workflow);
        $this->assertStringContainsString('tests/Unit', $workflow);
    }

    public function testWorkflowBuildsDatabaseUrlFromDbSecrets(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Construire DATABASE_URL Symfony pour IONOS', $workflow);
        $this->assertStringContainsString('DB_HOST', $workflow);
        $this->assertStringContainsString('DB_PORT', $workflow);
        $this->assertStringContainsString('DB_NAME', $workflow);
        $this->assertStringContainsString('DB_USER', $workflow);
        $this->assertStringContainsString('DB_PASSWORD', $workflow);
        $this->assertStringContainsString('rawurlencode', $workflow);
        $this->assertStringContainsString('SYMFONY_PASSWORD', $workflow);
        $this->assertStringContainsString('DATABASE_URL=$DATABASE_URL_VALUE', $workflow);
    }

    public function testWorkflowCreatesSymfonyProductionEnvFile(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Créer le fichier .env Symfony de production', $workflow);
        $this->assertStringContainsString('APP_ENV=prod', $workflow);
        $this->assertStringContainsString('APP_DEBUG=0', $workflow);
        $this->assertStringContainsString('APP_SECRET', $workflow);
        $this->assertStringContainsString('DEFAULT_URI', $workflow);
        $this->assertStringContainsString('echo "DATABASE_URL=\"$DATABASE_URL\"" >> .env', $workflow);
    }

    public function testWorkflowDoesNotRunDoctrineMigrationsFromGitHubActions(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringNotContainsString('doctrine:migrations:migrate', $workflow);
        $this->assertStringNotContainsString('doctrine:migrations:status', $workflow);
    }

    public function testWorkflowDoesNotLoadFixturesFromGitHubActions(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringNotContainsString('doctrine:fixtures:load', $workflow);
        $this->assertStringNotContainsString('fixtures:load', $workflow);
    }

    public function testWorkflowDoesNotRequireDirectMysqlConnectionFromGitHubActions(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringNotContainsString('mysqladmin', $workflow);
        $this->assertStringNotContainsString('mysql -h', $workflow);
        $this->assertStringNotContainsString('getent hosts "$DB_HOST"', $workflow);
    }

    public function testWorkflowInstallsProductionDependenciesWithoutComposerScripts(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--no-dev', $workflow);
        $this->assertStringContainsString('--optimize-autoloader', $workflow);
        $this->assertStringContainsString('--no-scripts', $workflow);
    }

    public function testWorkflowOptimizesSftpUpload(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--only-newer', $workflow);
        $this->assertStringContainsString('--parallel=10', $workflow);
    }

    public function testWorkflowSupportsFirstDeploymentWithVendorUpload(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('UPLOAD_VENDOR', $workflow);
        $this->assertStringContainsString("secrets.UPLOAD_VENDOR == 'true'", $workflow);
        $this->assertStringContainsString('Envoyer sur IONOS en SFTP avec vendor', $workflow);
        $this->assertStringContainsString('premier déploiement avec vendor/', $workflow);
    }

    public function testWorkflowSupportsFastDeploymentWithoutVendorUpload(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString("secrets.UPLOAD_VENDOR != 'true'", $workflow);
        $this->assertStringContainsString('Envoyer sur IONOS en SFTP sans vendor', $workflow);
        $this->assertStringContainsString('déploiement rapide sans vendor/', $workflow);
        $this->assertStringContainsString('--exclude vendor/', $workflow);
    }

    public function testWorkflowDoesNotUploadDevelopmentOrTestFiles(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('--exclude .git/', $workflow);
        $this->assertStringContainsString('--exclude .github/', $workflow);
        $this->assertStringContainsString('--exclude .env.test', $workflow);
        $this->assertStringContainsString('--exclude .env.local', $workflow);
        $this->assertStringContainsString('--exclude .env.local.php', $workflow);
        $this->assertStringContainsString('--exclude .env.dev', $workflow);
        $this->assertStringContainsString('--exclude var/cache/', $workflow);
        $this->assertStringContainsString('--exclude var/log/', $workflow);
        $this->assertStringContainsString('--exclude var/test.db', $workflow);
        $this->assertStringContainsString('--exclude node_modules/', $workflow);
        $this->assertStringContainsString('--exclude tests/', $workflow);
        $this->assertStringContainsString('--exclude .phpunit.cache/', $workflow);
        $this->assertStringContainsString('--exclude phpunit.xml.dist', $workflow);
        $this->assertStringContainsString('--exclude phpunit.dist.xml', $workflow);
    }

    public function testProductionEnvFileIsUploadedToServer(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringNotContainsString('--exclude .env ', $workflow);
        $this->assertStringNotContainsString('--exclude .env \\', $workflow);
    }

    public function testWorkflowDoesNotFailDeploymentWhenWebsiteReturnsError(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('curl -I --max-time 20 "$APP_URL" || true', $workflow);
        $this->assertStringContainsString('curl --max-time 20 "$APP_URL/api" || true', $workflow);
    }

    public function testWorkflowChecksWebsiteAfterDeployment(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Vérifier que le site répond après déploiement', $workflow);
        $this->assertStringContainsString('APP_URL', $workflow);
    }

    public function testWorkflowChecksApiAfterDeployment(): void
    {
        $workflow = $this->getWorkflowContent();

        $this->assertStringContainsString('Vérifier que l\'API répond après déploiement', $workflow);
        $this->assertStringContainsString('$APP_URL/api', $workflow);
        $this->assertStringContainsString('$APP_URL/api/products', $workflow);
        $this->assertStringContainsString('$APP_URL/api/categories', $workflow);
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