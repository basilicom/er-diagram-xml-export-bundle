<?php

namespace Basilicom\ERDiagramXMLExportBundle\Command;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections as FieldCollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks as ObjectBricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Pimcore\Model\DataObject\Fieldcollection\Definition\Listing as FieldCollectionListing;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Console\AbstractCommand;
use Basilicom\ERDiagramXMLExportBundle\DependencyInjection\GraphMLWriter;

class CreateERDiagramXMLExportCommand extends AbstractCommand
{
    protected static $defaultName = 'basilicom:create-er-diagram-xml-export';

    protected function configure()
    {
        $this->setDescription('Provides an yEd-XML Output to visualize ER of Pimcore Classes');
        $this->addArgument('filename', InputArgument::OPTIONAL, 'Provide a filename');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $result = new GraphMLWriter(
            $this->getClassDefinitionData(),
            $this->getFieldCollectionsData(),
            $this->getObjectBricksData(),
            $input->getArgument('filename') ?: ''

        );
        $result->output();

        return 0;
    }

    private function getClassDefinitionData(): array
    {
        $listing = new ClassDefinitionListing();
        $classDefinitions = $listing->load();

        $classDefinitionData = [];

        foreach ($classDefinitions as $classDefinition) {
            $fieldDefinitions = $classDefinition->getFieldDefinitions();

            $data = [
                'id'                      => $classDefinition->getId(),
                'name'                    => $classDefinition->getName(),
                'fields'                  => $this->processFieldDefinitions($fieldDefinitions),
                'relatedClasses'          => $this->getRelatedClasses($fieldDefinitions),
                'relatedFieldCollections' => $this->getRelatedFieldCollections($fieldDefinitions),
                'relatedObjectBricks'     => $this->getRelatedObjectBricks($fieldDefinitions),
            ];

            $classDefinitionData[] = $data;
        }

        return $classDefinitionData;
    }

    private function processFieldDefinitions($fieldDefinitions): array
    {
        $data = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fields = [$fieldDefinition->getName() => $fieldDefinition->getFieldtype()];

            if ($fieldDefinition instanceof AbstractRelations) {
                $allowedTypes = array_map(function ($classConfig) use ($fieldDefinition) {
                    if ($fieldDefinition instanceof ManyToOneRelation) {
                        return $classConfig['classes'] ?? '';
                    } else {
                        return ($classConfig['classes'] . '[]') ?? '';
                    }
                }, $fieldDefinition->getClasses());

                $fields = [$fieldDefinition->getName() => !empty($allowedTypes) ? implode(' | ', $allowedTypes) : ''];
            }

            if ($fieldDefinition instanceof FieldCollections) {
                $allowedTypes = [];

                foreach ($fieldDefinition->getAllowedTypes() as $allowedType) {
                    $allowedTypes[] = $allowedType;
                }

                $fields = [$fieldDefinition->getName() => $allowedTypes];
            }

            if ($fieldDefinition instanceof Localizedfields) {
                $localizedFields = $this->processFieldDefinitions($fieldDefinition->getFieldDefinitions());

                foreach ($localizedFields as $localizedField) {
                    array_walk($localizedField, function (&$value) {
                        $value .= ' ⚑';
                    });

                    $data[] = $localizedField;
                }

                continue;
            }

            $data[] = $fields;
        }

        return $data;
    }

    private function getRelatedClasses($fieldDefinitions): array
    {
        $relatedClasses = [];

        /** @var DataObject\ClassDefinition\Data $fieldDefinition */
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if (strpos($fieldType, 'Relation') !== false) {
                foreach ($fieldDefinition->getClasses() as $class) {
                    $relatedClasses[] = [$fieldType => $class['classes']];
                }
            }
        }

        return $relatedClasses;
    }

    private function getRelatedFieldCollections($fieldDefinitions): array
    {
        $data = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition instanceof FieldCollections) {
                foreach ($fieldDefinition->getAllowedTypes() as $name) {
                    $data[] = $name;
                }
            }
        }

        return $data;
    }

    private function getRelatedObjectBricks($fieldDefinitions): array
    {
        $data = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition instanceof ObjectBricks) {
                foreach ($fieldDefinition->getAllowedTypes() as $name) {
                    $data[] = $name;
                }
            }
        }

        return $data;
    }

    private function getFieldCollectionsData(): array
    {
        $fieldCollectionData = [];

        $fieldCollectionListing = new FieldCollectionListing();
        $fieldCollections = $fieldCollectionListing->load();

        foreach ($fieldCollections as $fieldCollection) {
            $data['fieldCollection'] = [
                'name'   => $fieldCollection->getKey(),
                'fields' => $this->processFieldDefinitions($fieldCollection->getFieldDefinitions()),
            ];
            $fieldCollectionData[] = $data;
        }

        return $fieldCollectionData;
    }

    private function getObjectBricksData(): array
    {
        $objectBricksData = [];

        $objectBricksListing = new ObjectBrickListing();
        $objectBricks = $objectBricksListing->load();

        foreach ($objectBricks as $objectBrick) {
            $data['objectBrick'] = [
                'name'   => $objectBrick->getKey(),
                'fields' => $this->processFieldDefinitions($objectBrick->getFieldDefinitions()),
            ];
            $objectBricksData[] = $data;
        }

        return $objectBricksData;
    }
}
