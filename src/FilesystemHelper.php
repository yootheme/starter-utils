<?php

namespace YOOtheme\Starter;

class FilesystemHelper
{
    public static function getCwd(string $path = ''): string
    {
        return getcwd() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public static function copyDir(string $from, string $to, callable $filter = null): void
    {
        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        foreach (scandir($from) as $file) {
            $source = "{$from}/{$file}";
            $target = "{$to}/{$file}";

            if (in_array($file, ['.', '..'])) {
                continue;
            }

            if (is_dir($source)) {
                static::copyDir($source, $target, $filter);
            } elseif (is_callable($filter)) {
                file_put_contents($target, $filter($source));
            } else {
                copy($source, $target);
            }
        }
    }
}
