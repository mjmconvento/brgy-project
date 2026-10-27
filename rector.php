<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

// PHP 8.5 syntax upgrade only (includes every lower PHP set).
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
    ])
    ->withPhpSets(php85: true);
