<?php

namespace YOOtheme\Starter\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use YOOtheme\Starter\FilesystemHelper as Fs;
use YOOtheme\Starter\StringHelper as Str;

#[AsCommand(name: 'create:element', description: 'Create a new element')]
class ElementCreateCommand extends Command
{
    protected string $stubs = __DIR__ . '/stubs';

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $path = Fs::getCwd($name);

        if (file_exists($path)) {
            throw new \RuntimeException("Element '{$path}' already exists.");
        }

        $fn = [$this->getHelper('question'), 'ask'];
        $ask = $this->partial($fn, $input, $output);
        $title = $ask(new Question('Enter the element title: ', $name));

        $vars = [
            'NAME' => $name,
            'TITLE' => $title,
        ];

        Fs::copyDir(
            "{$this->stubs}/element",
            $path,
            fn($file) => Str::placeholder(file_get_contents($file), $vars),
        );

        $output->writeln('Element created successfully.');

        return Command::SUCCESS;
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
