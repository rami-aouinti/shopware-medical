<?php declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(sprintf(
        'Composer autoloader not found at "%s". Run composer install/update first.',
        $autoload
    ));
}

require_once $autoload;
