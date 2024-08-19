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

        $namespace = new Question('Enter module namespace: ');
        $namespace->setNormalizer(fn($value) => $value ?? '');
        $namespace->setValidator(function ($value) {
            if ('' === trim($value)) {
                throw new \Exception('The namespace cannot be empty');
            }

            return $value;
        });
        $namespace->setMaxAttempts(10);
        $namespace = $ask($namespace);

        $assets = $ask(new ConfirmationQuestion('Add assets files example? [y/N] ', false));
        $settings = $ask(new ConfirmationQuestion('Add settings example? [y/N] ', false));
        $less = $ask(new ConfirmationQuestion('Add custom LESS example? [y/N] ', false));

        $finders = [
            'module' => (new Finder())
                ->name('bootstrap.php')
                ->in("{$this->stubs}/module"),
        ];

        if ($assets) {
            $finders['assets'] = (new Finder())
                ->name(['AssetsListener.php', 'custom.js', 'custom.css'])
                ->in("{$this->stubs}/module");
        }

        if ($settings) {
            $finders['settings'] = (new Finder())
                ->name(['SettingsListener.php', 'customizer.json'])
                ->in("{$this->stubs}/module");
        }

        if ($less) {
            $finders['less'] = (new Finder())
                ->name(['StyleListener.php', 'my-component.less'])
                ->in("{$this->stubs}/module");
        }

        foreach ($finders as $name => $finder) {
            foreach ($finder->files() as $file) {
                $fs->dumpFile("{$path}/{$file->getRelativePathname()}", $file->getContents());
            }
        }

        // namespace
        $this->replaceInFile(
            "{$path}/bootstrap.php",
            ['#// namespace#'],
            ["namespace {$namespace};"],
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
                ["namespace {$namespace};"],
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
                ["namespace {$namespace};"],
            );
        }

        if ($less) {
            // add custom LESS
            $find = [
                '#// includes#',
                '#// add theme config ...#',
                '#// add styler config ...#',
                '#// add event handlers ...#',
            ];
            $replace = [
                "\${0}\ninclude_once __DIR__ . 'src/StyleListener';",
                "\${0}\n\n        'styles' => [
            'components' => [
                'my-component' => Path::get('./assets/less/my-component.less'),
            ],
        ],",
                "StylerConfig::class => __DIR__ . '/config/styler.json',",
                "\${0}\n\n        StylerConfig::class => [
            StyleListener::class => 'config'
        ],",
            ];

            $this->replaceInFile("{$path}/bootstrap.php", $find, $replace);

            // namespace
            $this->replaceInFile(
                "{$path}/src/StyleListener.php",
                ['#// namespace#'],
                ["namespace {$namespace};"],
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
