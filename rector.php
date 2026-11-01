<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/custom/plugins/MedicalInventory/src',
        __DIR__ . '/custom/plugins/MedicalInventory/tests',
    ])
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ]);
