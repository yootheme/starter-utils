<?php

use YOOtheme\Builder;
use YOOtheme\Path;

return [
    // Set theme configuration values
    'theme' => [],

    // Register event handlers
    'events' => [],

    // Load builder elements
    'extend' => [
        Builder::class => function (Builder $builder) {
            $builder->addTypePath(Path::get('./elements/*/element.json'));
        },
    ],
];
