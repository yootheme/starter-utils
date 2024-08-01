<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
        $finder = (new Finder())
            ->name('bootstrap.php')
            ->in("{$this->stubs}/module");

        if ($ask(new ConfirmationQuestion('Create module assets example? [Y/n] ', true))) {
            $finder->append($finder->name(['AssetsListener.php', 'custom.js', 'custom.css']));
        }

        foreach ($finder->files() as $file) {
            $fs->dumpFile("{$path}/{$file->getRelativePathname()}", $file->getContents());
        }

        $output->writeln('Module created successfully.');

        return Command::SUCCESS;
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
