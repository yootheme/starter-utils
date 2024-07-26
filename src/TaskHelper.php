<?php

namespace YOOtheme\Starter;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use YOOtheme\Starter\StringHelper as Str;
use ZipArchive;

class TaskHelper
{
    /**
     * The absolute path of the root Taskfile directory.
     */
    protected string $dir;

    /**
     * The current working directory task variable.
     */
    protected string $cwd;

    public function __construct(protected array $env)
    {
        $this->dir = $env['ROOT_DIR'] ?? '';
        $this->cwd = $env['TASK_CWD'] ?? '';
    }

    public function copy(string $src, string $dest, string $ignore = ''): void
    {
        $fs = new Filesystem();
        $files = self::glob($this->cwd, $src, $ignore);
        $count = count($files->files());

        foreach ($files as $file) {
            $to = self::resolvePath($dest, $file->getRelativePathname());
            $fs->copy($file->getPathname(), $to, true);
        }

        echo "Copied {$count} file" . ($count !== 1 ? 's' : '') . " to '{$dest}'\n";
    }

    public function remove(string $src, string $ignore = ''): void
    {
        $fs = new Filesystem();
        $files = self::glob($this->cwd, $src, $ignore);
        $count = [0, 0]; // [files, directories]

        foreach ([...$files->files(), ...$files->directories()] as $file) {
            $count[intval($file->isDir())]++;
            $fs->remove($file->getPathname());
        }

        echo "Removed {$count[0]} file" .
            ($count[0] !== 1 ? 's' : '') .
            ", {$count[1]} director" .
            ($count[1] === 1 ? 'y' : 'ies') .
            "\n";
    }

    public function placeholder(string $src, string $replace, string $ignore = ''): void
    {
        $fs = new Filesystem();
        $files = self::glob($this->cwd, $src, $ignore);

        foreach ($files->files() as $file) {
            $fs->dumpFile(
                $file->getPathname(),
                Str::placeholder($file->getContents(), json_decode($replace, true)),
            );
        }
    }

    public function zip(string $src, string $dest, string $ignore = ''): void
    {
        $zip = new ZipArchive();
        $files = self::glob($this->cwd, $src, $ignore);
        $count = count($files->files());

        if (!$zip->open($dest, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new \RuntimeException("Failed to create archive '{$dest}'");
        }

        foreach ($files as $file) {
            $name = $file->getRelativePathname();
            $zip->addFile($file->getPathname(), $name);
            $zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9);
        }

        $zip->close();

        echo "Created '{$dest}' ({$count} file" . ($count !== 1 ? 's' : '') . ")\n";
    }

    public static function run(Event $event): void
    {
        $args = $event->getArguments();
        $task = array_shift($args);

        if (!method_exists(self::class, $task)) {
            throw new \RuntimeException("Task '$task' not found");
        }

        call_user_func_array([new self($_SERVER), $task], $args);
    }

    protected static function glob(string $path, string $src, string $ignore = ''): Finder
    {
        $finder = Finder::create()->in(self::resolvePath($path));

        foreach (array_filter(explode(' ', $src)) as $glob) {
            $finder->path(self::toRegex($glob));
        }

        foreach (array_filter(explode(' ', $ignore)) as $glob) {
            $finder->notPath(self::toRegex($glob));
        }

        return $finder;
    }

    protected static function toRegex(string $path): string
    {
        $path = Path::canonicalize("/{$path}");
        $regex = Glob::toRegex($path, false);

        return str_replace('#^/', '#^', $regex);
    }

    protected static function resolvePath(string ...$paths): string
    {
        $parts = [];

        foreach (array_reverse([getcwd(), ...$paths]) as $path) {
            array_unshift($parts, $path);

            if (Path::isAbsolute($path)) {
                break;
            }
        }

        return ($result = Path::join(...$parts)) !== '/' ? rtrim($result, '/') : $result;
    }
}
