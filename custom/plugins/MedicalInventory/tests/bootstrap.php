<?php declare(strict_types=1);

$projectAutoload = dirname(__DIR__, 4) . '/vendor/autoload.php';

if (!is_file($projectAutoload)) {
    throw new RuntimeException(sprintf('Shopware Composer autoloader was not found at "%s".', $projectAutoload));
}

require_once $projectAutoload;
require_once dirname(__DIR__) . '/src/Service/WarehouseStockCalculator.php';
