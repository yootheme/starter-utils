<?php

namespace YOOtheme\Starter;

class StringHelper
{
    public static function placeholder(string $str, array $replace): string
    {
        $callback = fn($matches) => $replace[$matches[1]] ?? $matches[0];

        return preg_replace_callback('/{{\s*(\w+?)\s*}}/', $callback, $str);
    }

    public static function replace(string $str, array $replace): string
    {
        $result = str_replace(array_keys($replace), array_values($replace), $str);

        // Remove empty lines
        $lines = explode("\n", $result);
        $lines = array_filter($lines, fn($line) => trim($line) !== '');

        return implode("\n", $lines);
    }
}
