<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'create:joomlaUpdate', description: 'Create the Joomla update server XML')]
class CreateJoomlaUpdateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $zip = new \ZipArchive();
        $files = glob(Path::join(getcwd(), 'dist', '*-j-*.zip'));

        if (empty($files)) {
            $output->writeln('<error>Could not find Joomla package in dist folder.</error>');

            return Command::FAILURE;
        }

        $metadata = $this->getMetadata($files);

        $xml = dom_import_simplexml($this->toXML($metadata))->ownerDocument;
        $xml->formatOutput = true;

        $fs = new Filesystem();
        $fs->dumpFile(Path::join(getcwd(), 'dist', 'update.xml'), $xml->saveXML());

        return Command::SUCCESS;
    }

    protected function getMetadata($files)
    {
        $metadata = parse_ini_file(getcwd() . '/.env');

        $file = array_values(
            array_filter($files, function ($file) use ($metadata) {
                return preg_match("/-j-{$metadata['VERSION']}/", $file);
            }),
        )[0];

        $metadata['SHA256'] = hash_file('sha256', $file);
        $metadata['SHA384'] = hash_file('sha384', $file);
        $metadata['SHA512'] = hash_file('sha512', $file);

        return $metadata;
    }

    protected function toXML($metadata)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><updates/>');

        $xmlUpdate = $xml->addChild('update');

        $keys = [
            'NAME',
            'DESCRIPTION',
            'ELEMENT',
            'TYPE',
            'VERSION',
            'DOWNLOADURL',
            'STABILITY',
            'SHA256',
            'SHA384',
            'SHA512',
            'MAINTAINER',
            'MAINTAINERURL',
            'JOOMLAMINIMUM',
            'PHP_MINIMUM',
        ];

        foreach ($keys as $key) {
            if ('ELEMENT' == $key) {
                $xmlUpdate->addChild('element', $metadata['NAME']);
            } elseif ('DOWNLOADURL' == $key) {
                $downloads = $xmlUpdate->addChild('downloads');
                $download = $downloads->addChild(
                    'downloadurl',
                    htmlentities(
                        "{$metadata['DOWNLOADURL']}/{$metadata['NAME']}-j-{$metadata['VERSION']}.zip",
                    ),
                );
                $download->addAttribute('type', 'full');
                $download->addAttribute('format', 'zip');
            } elseif ('MAINTAINER' == $key) {
                $xmlUpdate->addChild('maintainer', $metadata['AUTHOR']);
                $xmlUpdate->addChild('maintainerurl', $metadata['AUTHORURL']);
            } elseif ('JOOMLAMINIMUM' == $key) {
                $xmlChild = $xmlUpdate->addChild('targetplatform');
                $xmlChild->addAttribute('name', 'joomla');
                $xmlChild->addAttribute('version', $metadata['JOOMLAMINIMUM']);
            } elseif (!is_null($value = $metadata[$key] ?? null)) {
                $xmlUpdate->addChild(strtolower($key), $value);
            }
        }

        return $xml;
    }
}
