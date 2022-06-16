<?php

namespace Basilicom\ERDiagramXMLExportBundle\DependencyInjection;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections as FieldCollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks as ObjectBricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Pimcore\Model\DataObject\Fieldcollection\Definition\Listing as FieldCollectionListing;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;

class YedPimcoreDefinitionsRepository
{
    public function getClassDefinitionData(): array
    {
        $listing = new ClassDefinitionListing();
        $classDefinitions = $listing->load();

        $classDefinitionData = [];

        foreach ($classDefinitions as $classDefinition) {
            $fieldDefinitions = $classDefinition->getFieldDefinitions();

            $data = [
                'id' => $classDefinition->getId(),
                'name' => $classDefinition->getName(),
                'fields' => $this->processFieldDefinitions($fieldDefinitions),
                'relatedClasses' => $this->getRelatedClasses($fieldDefinitions),
                'relatedFieldCollections' => $this->getRelatedFieldCollections($fieldDefinitions),
                'relatedObjectBricks' => $this->getRelatedObjectBricks($fieldDefinitions),
            ];

            $classDefinitionData[] = $data;
        }

        return $classDefinitionData;
    }

    public function getFieldCollectionsData(): array
    {
        $fieldCollectionData = [];

        $fieldCollectionListing = new FieldCollectionListing();
        $fieldCollections = $fieldCollectionListing->load();

        foreach ($fieldCollections as $fieldCollection) {
            $data['fieldCollection'] = [

                'name' => $fieldCollection->getKey(),
                'fields' => $this->processFieldDefinitions($fieldCollection->getFieldDefinitions()),

            ];
            $fieldCollectionData[] = $data;
        }

        return $fieldCollectionData;
    }

    public function getObjectBricksData(): array
    {
        $objectBricksData = [];

        $objectBricksListing = new ObjectBrickListing();
        $objectBricks = $objectBricksListing->load();

        foreach ($objectBricks as $objectBrick) {
            $data['objectBrick'] = [

                'name' => $objectBrick->getKey(),
                'fields' => $this->processFieldDefinitions($objectBrick->getFieldDefinitions()),

            ];
            $objectBricksData[] = $data;
        }

        return $objectBricksData;
    }

    /**
     * @param Data[] $fieldDefinitions
     * @return array
     */
    private function processFieldDefinitions(array $fieldDefinitions): array
    {
        $data = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if (!$fieldDefinition instanceof AbstractRelations) {
                $fields = [
                    $fieldDefinition->getName() => $fieldType,
                ];
                if ($fieldDefinition instanceof FieldCollections) {
                    $allowedTypes = [];

                    foreach ($fieldDefinition->getAllowedTypes() as $allowedType) {
                        $allowedTypes[] = $allowedType;
                    }
                    $fields = [
                        $fieldDefinition->getName() => $allowedTypes,
                    ];
                }
                $data[] = $fields;
            }
        }

        return $data;
    }

    /**
     * @param Data[] $fieldDefinitions
     * @return array
     */
    private function getRelatedClasses(array $fieldDefinitions): array
    {
        $relatedClasses = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if ($fieldDefinition instanceof AbstractRelations) {
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
                foreach ($fieldDefinition->getAllowedTypes() as $allowedType => $name) {
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
                foreach ($fieldDefinition->getAllowedTypes() as $allowedType => $name) {
                    $data[] = $name;
                }
            }
        }

        return $data;
    }
}
