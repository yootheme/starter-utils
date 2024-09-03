<?php

namespace YOOtheme\Starter;

use Composer\Composer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use YOOtheme\Starter\StringHelper as Str;

#[AsCommand(name: 'create:plugin', description: 'Create a plugin')]
class CreatePluginCommand extends Command
{
    protected string $stubs = __DIR__ . '/stubs';

    protected function configure()
    {
        if (version_compare(Composer::getVersion(), '2.5', '<')) {
            throw new \Exception('Composer version 2.5 or higher is required');
        }

        $this->addArgument('name', InputArgument::OPTIONAL, default: basename(getcwd()));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $cwd = getcwd();

        $name = strtr($input->getArgument('name'), ['-' => '_', ' ' => '_']);

        $fn = [$this->getHelper('question'), 'ask'];
        $ask = $this->partial($fn, $input, $output);
        $finder = (new Finder())
            ->in("{$this->stubs}/plugin")
            ->ignoreDotFiles(false);

        $filemap = [
            '/env' => '/.env',
            '/plugin.xml' => "/{$name}.xml",
            '/plugin.stub' => "/{$name}.php",
        ];

        $variables = [
            'NAME' => $name,
            'PLUGIN' => $name,
            'PLUGIN_CLASS' => 'plgSystem' . ucfirst(strtr($name, ['-' => '', '_' => ''])),
            'TITLE' => $ask(new Question('Enter plugin title: ', $name)),
        ];

        $questions = $variables + [
            'DESCRIPTION' => $ask(new Question('Enter plugin description: ', '')),
            'AUTHOR' => $ask(new Question('Enter author name: ', '')),
            'AUTHOREMAIL' => $ask(new Question('Enter author email: ', '')),
            'AUTHORURL' => $ask(new Question('Enter author url: ', '')),
            'UPDATEURI' => $ask(new Question('Enter update server url: ', '')),
        ];

        $variables['UPDATEHOST'] = parse_url($questions['UPDATEURI'], PHP_URL_HOST);

        foreach ($finder->files() as $file) {
            $fs->dumpFile(
                strtr("{$cwd}/{$file->getRelativePathname()}", $filemap),
                Str::placeholder(
                    $file->getContents(),
                    in_array($file->getBasename(), ['Taskfile.yml', '.env'])
                        ? $questions
                        : $variables,
                ),
            );
        }

        $output->writeln("Plugin '{$name}' created successfully.");

        return Command::SUCCESS;
    }

    protected function partial(callable $func, ...$args): callable
    {
        return fn(...$rest) => $func(...$args, ...$rest);
    }
}
