<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use YOOtheme\Starter\StringHelper as Str;

#[AsCommand(name: 'create:element', description: 'Create an element')]
class CreateElementCommand extends Command
{
    protected string $stubs = __DIR__ . '/stubs';

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED);
        $this->addArgument('module', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $cwd = getcwd();

        $fn = [$this->getHelper('question'), 'ask'];
        $ask = $this->partial($fn, $input, $output);

        $name = $input->getArgument('name');
        if ($module = $input->getArgument('module')) {
            $module = "modules/$module";
        } else {
            if ($modules = glob(Path::join($cwd, 'modules', '*'), GLOB_ONLYDIR)) {
                $module = $ask(
                    new ChoiceQuestion(
                        'Select module: (defaults to first)',
                        array_map(fn($module) => basename($module), $modules),
                        0,
                    ),
                );
            } else {
                throw new \RuntimeException(
                    'Create a module first with command `composer create:module`',
                );
            }

            $module = "modules/$module";
        }

        $finders = [
            $name => (new Finder())->in("{$this->stubs}/single-element"),
        ];

        if ($ask(new ConfirmationQuestion('Create multiple items element? [y/N] ', false))) {
            $finders = [
                $name => (new Finder())->in("{$this->stubs}/multiple-element"),
                "{$name}_item" => (new Finder())->in("{$this->stubs}/multiple-element_item"),
            ];
        }

        $variables = [
            'NAME' => $name,
            'TITLE' => $ask(new Question('Enter element title: ', $name)),
        ];

        foreach ($finders as $name => $finder) {
            $path = Path::join($cwd, $module, 'elements', $name);

            if (file_exists($path)) {
                throw new \RuntimeException("Element '{$path}' already exists");
            }

            foreach ($finder->files() as $file) {
                $fs->dumpFile(
                    "{$path}/{$file->getRelativePathname()}",
                    Str::placeholder($file->getContents(), $variables),
                );
            }
        }

        $output->writeln('Element created successfully.');

        return Command::SUCCESS;
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
