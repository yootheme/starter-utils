<?php

// namespace

use YOOtheme\Builder;
use YOOtheme\Path;
use YOOtheme\Theme\Styler\StylerConfig;

// includes

return [
    'theme' => [
        // add theme config ...
    ],

    'config' => [
        // add styler config ...
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