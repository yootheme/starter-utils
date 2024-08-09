<?php

use YOOtheme\Builder;
use YOOtheme\Path;

// includes

return [
    'theme' => [
        // add theme config ...
    ],

    'events' => [
        // add event handlers ...
    ],

    'extend' => [
        // extend container services ...

        Builder::class => function (Builder $builder) {
            $builder->addTypePath(Path::get('./elements/*/element.json'));
        },
    ],
];
