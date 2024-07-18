<?php

namespace YOOtheme\Starter;

class StringHelper
{
    public static function placeholder(string $data, array $vars): string
    {
        $replace = [];

        foreach ($vars as $key => $value) {
            $replace["{{ {$key} }}"] = $value;
        }

        return str_replace(array_keys($replace), array_values($replace), $data);
    }
}
