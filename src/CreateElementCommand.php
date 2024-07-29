<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $cwd = getcwd();

        $name = $input->getArgument('name');
        $path = Path::join($cwd, 'elements', $name);

        if (file_exists($path)) {
            throw new \RuntimeException("Element '{$path}' already exists");
        }

        $fn = [$this->getHelper('question'), 'ask'];
        $ask = $this->partial($fn, $input, $output);
        $finder = (new Finder())->in("{$this->stubs}/element");

        $variables = [
            'NAME' => $name,
            'TITLE' => $ask(new Question('Enter element title: ', $name)),
        ];

        foreach ($finder->files() as $file) {
            $fs->dumpFile(
                "{$path}/{$file->getRelativePathname()}",
                Str::placeholder($file->getContents(), $variables),
            );
        }

        $output->writeln('Element created successfully.');

        return Command::SUCCESS;
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
