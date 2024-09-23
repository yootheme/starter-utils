<?php

// namespace

/**
 * Element transform and update functions.
 *
 * @see https://yootheme.com/support/yootheme-pro/joomla/developers-elements#transforms-and-updates
 */

return [
    // Define transforms for the element node
    'transforms' => [
        // The function is executed before the template is rendered
        'render' => function ($node, array $params) {
            // Element object (stdClass)
            $node->type; // Type name (string)
            $node->props; // Field properties (array)
            $node->children; // All children (array)

            // Parameter array
            $params['path']; // All parent elements (array)
            $params['parent']; // Parent element (stdClass)
            $params['builder']; // Builder instance (YOOtheme\Builder)
            $params['type']; // Element definition (YOOtheme\Builder\ElementType)
        },
    ],
    // Define updates for the element node
    'updates' => [
        // The function is executed if the YOOtheme Pro version, when the element was saved, is smaller than the current version.
        /*'1.18.0' => function ($node, array $params) {},*/
    ],
];
