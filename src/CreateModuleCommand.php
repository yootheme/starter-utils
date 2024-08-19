<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'create:module', description: 'Create a module')]
class CreateModuleCommand extends Command
{
    protected string $stubs = __DIR__ . '/stubs';

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $cwd = getcwd();

        $name = $input->getArgument('name');
        $path = Path::join($cwd, 'modules', $name);

        if (file_exists($path)) {
            throw new \RuntimeException("Module '{$path}' already exists");
        }

        $fn = [$this->getHelper('question'), 'ask'];
        $ask = $this->partial($fn, $input, $output);
        $namespace = $ask(new Question('Enter module namespace: '));
        $assets = $ask(new ConfirmationQuestion('Add asset files example? [Y/n] ', true));
        $settings = $ask(new ConfirmationQuestion('Add settings example? [Y/n] ', true));

        $finder = (new Finder())
            ->name('bootstrap.php')
            ->in("{$this->stubs}/module");

        if ($assets) {
            $finder->append($finder->name(['AssetsListener.php', 'custom.js', 'custom.css']));
        }

        if ($settings) {
            $finder->append($finder->name(['SettingsListener.php', 'customizer.json']));
        }

        foreach ($finder->files() as $file) {
            $fs->dumpFile("{$path}/{$file->getRelativePathname()}", $file->getContents());
        }

        // namespace
        $this->replaceInFile(
            "{$path}/bootstrap.php",
            ['#// namespace#'],
            $namespace ? ["namespace {$namespace};"] : [''],
        );

        if ($assets) {
            // add AssetsListener
            $find = ['#// includes#', '#// add event handlers ...#'];
            $replace = [
                "\${0}\ninclude_once __DIR__ . 'src/AssetsListener';",
                "\${0}\n\n        'theme.head' => [
            AssetsListener::class => 'initHead',
        ],",
            ];

            $this->replaceInFile("{$path}/bootstrap.php", $find, $replace);

            // namespace
            $this->replaceInFile(
                "{$path}/src/AssetsListener.php",
                ['#// namespace#'],
                $namespace ? ["namespace {$namespace};"] : [''],
            );
        }

        if ($settings) {
            // add SettingsListener
            $find = ['#// includes#', '#// add event handlers ...#'];
            $replace = [
                "\${0}\ninclude_once __DIR__ . 'src/SettingsListener';",
                "\${0}\n\n        'customizer.init' => [
            SettingsListener::class => 'initCustomizer',
        ],",
            ];

            $this->replaceInFile("{$path}/bootstrap.php", $find, $replace);

            // namespace
            $this->replaceInFile(
                "{$path}/src/SettingsListener.php",
                ['#// namespace#'],
                $namespace ? ["namespace {$namespace};"] : [''],
            );
        }

        $output->writeln('Module created successfully.');

        return Command::SUCCESS;
    }

    protected function replaceInFile(string $file, array $find, array $replace)
    {
        file_put_contents($file, preg_replace($find, $replace, file_get_contents($file)));
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
