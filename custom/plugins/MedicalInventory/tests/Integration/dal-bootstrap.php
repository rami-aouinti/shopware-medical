<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$projectRoot = dirname(__DIR__, 5);
require_once $projectRoot . '/vendor/autoload.php';
$databaseUrl = getenv('MEDICAL_INVENTORY_TEST_DATABASE_URL');

if (!is_string($databaseUrl) || $databaseUrl === '') {
    throw new RuntimeException('MEDICAL_INVENTORY_TEST_DATABASE_URL is required for DAL integration tests.');
}

$bootstrapper = new TestBootstrapper();
$bootstrapper
    ->setProjectDir($projectRoot)
    ->setDatabaseUrl($databaseUrl)
    ->setForceInstall(true)
    ->addCallingPlugin($projectRoot . '/custom/plugins/MedicalInventory/composer.json');

$bootstrapper->setClassLoader($bootstrapper->getClassLoader());
$bootstrapper->bootstrap();
