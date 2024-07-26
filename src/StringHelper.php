<?php

namespace YOOtheme\Starter;

class StringHelper
{
    public static function placeholder(string $str, array $replace): string
    {
        $callback = fn($matches) => $replace[$matches[1]] ?? $matches[0];

        return preg_replace_callback('/{{\s*(\w+?)\s*}}/', $callback, $str);
    }
}
