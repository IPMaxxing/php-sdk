<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@PER-CS2x0' => true,
        '@PHP8x2Migration' => true,
    ])
    ->setFinder(Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests'])->append([__FILE__]));
