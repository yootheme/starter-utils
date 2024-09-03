<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'create:wordpressUpdate', description: 'Create the WordPress update file')]
class CreateWordpressUpdateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $zip = new \ZipArchive();
        $files = glob(Path::join(getcwd(), 'dist', '*-wp-*.zip'));

        if (empty($files)) {
            $output->writeln('<error>Could not find Wordpress package in dist folder.</error>');

            return Command::FAILURE;
        }

        $metadata = parse_ini_file(getcwd() . '/.env');

        $update = [
            'slug' => $metadata['NAME'],
            'version' => $metadata['VERSION'],
            'package' => "{$metadata['DOWNLOADURL']}/{$metadata['NAME']}-wp-{$metadata['VERSION']}.zip",
        ];

        $fs = new Filesystem();
        $fs->dumpFile(
            Path::join(getcwd(), 'dist', 'update.json'),
            json_encode($update, JSON_PRETTY_PRINT),
        );

        return Command::SUCCESS;
    }
}
