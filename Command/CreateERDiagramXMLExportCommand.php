<?php


namespace ERDiagramXMLExportBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateERDiagramXMLExportCommand extends Command
{
    protected static $defaultName = 'basilicom:create-er-diagram-xml-export';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Provides an yEd-XML Output to visualize ER of Pimcore Classes')
        ;
    }

}