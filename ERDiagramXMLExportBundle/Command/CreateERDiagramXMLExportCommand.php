<?php

namespace Basilicom\ERDiagramXMLExportBundle\Command;

use Basilicom\ERDiagramXMLExportBundle\DependencyInjection\DiagramsPimcoreDefinitionsRepository;
use Basilicom\ERDiagramXMLExportBundle\DependencyInjection\DiagramsXmlGenerator;
use Basilicom\ERDiagramXMLExportBundle\DependencyInjection\YedPimcoreDefinitionsRepository;
use Basilicom\ERDiagramXMLExportBundle\DependencyInjection\YedXmlGenerator;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Console\AbstractCommand;

class CreateERDiagramXMLExportCommand extends AbstractCommand
{
    protected static $defaultName = 'basilicom:create-er-diagram-xml-export';

    protected function configure()
    {
        $this->setDescription('Provides an ER-Diagram of Pimcore Classes in XML format for diagrams.net or yEd');
        $this->addArgument('format', InputArgument::REQUIRED, 'diagrams | yed');
        $this->addArgument('filename', InputArgument::OPTIONAL, 'Provide a filename');
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputType = strtolower($input->getArgument('format'));
        switch ($outputType) {
            case 'diagrams':
                $pimcoreDefinitionsRepository = new DiagramsPimcoreDefinitionsRepository();
                $xmlGenerator = new DiagramsXmlGenerator(
                    $pimcoreDefinitionsRepository->getClassDefinitionData(),
                    $pimcoreDefinitionsRepository->getFieldCollectionsData(),
                    $pimcoreDefinitionsRepository->getObjectBricksData(),
                );
                break;

            case 'yed':
                $pimcoreDefinitionsRepository = new YedPimcoreDefinitionsRepository();
                $xmlGenerator = new YedXmlGenerator(
                    $pimcoreDefinitionsRepository->getClassDefinitionData(),
                    $pimcoreDefinitionsRepository->getFieldCollectionsData(),
                    $pimcoreDefinitionsRepository->getObjectBricksData(),
                );
                break;

            default:
                throw new Exception('Invalid format: "' . $outputType . '". Valid: diagrams | yed');
        }

        $xml = $xmlGenerator->generate();
        $this->writeToFile($input->getArgument('filename') ?: '', $xml);

        return 0;
    }

    /**
     * @throws Exception
     */
    public function writeToFile(string $fileName, string $fileContent): void
    {
        $dirname = dirname(__DIR__, 5) . '/var/tmp/';

        if (!is_dir($dirname)) {
            $dirname = './var/tmp/';
        }

        if (!is_dir($dirname)) {
            throw new Exception('"' . $dirname . '" generate path does not exist!');
        }

        if (empty($fileName)) {
            $file = $dirname . 'PimcoreClassDiagram.xml';
        } else {
            $file = $dirname . $fileName . '.xml';
        }

        file_put_contents($file, $fileContent);
    }
}
