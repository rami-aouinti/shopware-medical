<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/custom/plugins/MedicalInventory/src',
        __DIR__ . '/custom/plugins/MedicalInventory/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        // Keep the Shopware file-header convention used throughout this project.
        'linebreak_after_opening_tag' => false,
        'declare_strict_types' => true,
        'single_line_empty_body' => true,
    ])
    ->setFinder($finder);
