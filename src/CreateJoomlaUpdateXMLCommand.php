<?php

namespace YOOtheme\Starter;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'create:updateXML', description: 'Create the Joomla update server XML')]
class CreateJoomlaUpdateXMLCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $zip = new \ZipArchive();
        $file = glob(Path::join(getcwd(), 'dist', '*-j-*.zip'));

        if (empty($file) || !$zip->open($file[0])) {
            $output->writeln('<error>Could not find Joomla package in dist folder.</error>');

            return Command::FAILURE;
        }

        $metadata = $this->getMetadata($file[0]);

        $xml = dom_import_simplexml($this->toXML($metadata))->ownerDocument;
        $xml->formatOutput = true;

        $fs = new Filesystem();
        $fs->dumpFile(Path::join(getcwd(), 'dist', 'update.xml'), $xml->saveXML());

        return Command::SUCCESS;
    }

    protected function getMetadata($file)
    {
        $metadata = parse_ini_file(getcwd() . '/.env');

        $metadata['TYPE'] = 'plugin';
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
            'TARGETPLATFORM',
            'PHP_MINIMUM',
        ];

        foreach ($keys as $key) {
            if (!is_null($value = $metadata[$key] ?? null)) {
                $xmlUpdate->addChild($key, $value);
            }

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
            } elseif ('TARGETPLATFORM' == $key) {
                $xmlChild = $xmlUpdate->addChild('targetplatform');
                $xmlChild->addAttribute('name', 'joomla');
                $xmlChild->addAttribute('version', $metadata['TARGETPLATFORM']);
            }
        }

        return $xml;
    }
}
