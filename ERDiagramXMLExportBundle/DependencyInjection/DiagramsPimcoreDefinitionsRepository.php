<?php

namespace Basilicom\ERDiagramXMLExportBundle\DependencyInjection;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Pimcore\Model\DataObject\Fieldcollection\Definition\Listing as FieldCollectionListing;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;

class DiagramsPimcoreDefinitionsRepository
{
    public function getClassDefinitionData(): array
    {
        $listing = new ClassDefinitionListing();
        $classDefinitions = $listing->load();

        $classDefinitionData = [];

        foreach ($classDefinitions as $classDefinition) {
            $fieldDefinitions = $classDefinition->getFieldDefinitions();

            $classDefinitionData[] = [
                'name' => $classDefinition->getName(),
                'fields' => $this->processFieldDefinitions($fieldDefinitions, $classDefinition->getName()),
                'relationFields' => $this->getRelationFields($fieldDefinitions),
            ];
        }

        return $classDefinitionData;
    }

    public function getFieldCollectionsData(): array
    {
        $fieldCollectionData = [];

        $fieldCollectionListing = new FieldCollectionListing();
        $fieldCollections = $fieldCollectionListing->load();

        foreach ($fieldCollections as $fieldCollection) {
            $fieldCollectionData[] = [
                'name' => $fieldCollection->getKey(),
                'fields' => $this->processFieldDefinitions($fieldCollection->getFieldDefinitions(),
                    $fieldCollection->getKey()),
                'relationFields' => $this->getRelationFields($fieldCollection->getFieldDefinitions()),
            ];
        }

        return $fieldCollectionData;
    }

    public function getObjectBricksData(): array
    {
        $objectBricksData = [];

        $objectBricksListing = new ObjectBrickListing();
        $objectBricks = $objectBricksListing->load();

        foreach ($objectBricks as $objectBrick) {
            $objectBricksData[] = [
                'name' => $objectBrick->getKey(),
                'fields' => $this->processFieldDefinitions($objectBrick->getFieldDefinitions(), $objectBrick->getKey()),
                'relationFields' => $this->getRelationFields($objectBrick->getFieldDefinitions()),
            ];
        }

        return $objectBricksData;
    }

    /** @var Data[] $fieldDefinitions */
    private function processFieldDefinitions(array $fieldDefinitions, string $className): array
    {
        $fieldData = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            // todo: classification store relations
//            if ($fieldDefinition instanceof Data\Classificationstore) {
//                p_r($fieldDefinition);
//                $fieldDefinition->getStoreId();
//            }

            if (str_contains($fieldType, 'Relation')) {
                $selfRelation = !empty($fieldDefinition->getClasses()) && in_array($className,
                        $fieldDefinition->getClasses()[0]);

                $fieldData[] = [
                    'name' => $fieldDefinition->getName(),
                    'type' => $fieldType . ($selfRelation ? ' (self)' : ''),
                ];
                continue;
            }

            if ($fieldDefinition instanceof FieldCollections) {
                $fieldData[] = [
                    'name' => $fieldDefinition->getName(),
                    'type' => implode(' | ', $fieldDefinition->getAllowedTypes()),
                ];
                continue;
            }

            if ($fieldDefinition instanceof Block) {
                $fieldData[] = [
                    'name' => $fieldDefinition->getName(),
                    'type' => $fieldDefinition->getFieldtype(),
                ];

                /** @var Data $blockField */
                foreach ($fieldDefinition->getChildren() as $blockField) {
                    $fieldData[] = [
                        'name' => '> ' . $blockField->getName(),
                        'type' => $blockField->getFieldtype(),
                    ];
                }
                continue;
            }

            if ($fieldDefinition instanceof Localizedfields) {
                // a correctly sorted order cannot be achieved.
                // per class definition only the first 'localizedfields' can be accessed
                // the first localized fields are always listed under 'children' in that one
                // while every following localizedfields block is listed in 'referencedFields'
                // p_r($fieldDefinition); // <- check it

                $localizedFields = [];
                $localizedFields = array_merge($localizedFields, $fieldDefinition->getChildren());

                /** @var Localizedfields $referencedLocalizedFields */
                $referencedLocalizedFields = $fieldDefinition->getReferencedFields();
                foreach ($referencedLocalizedFields as $referencedLocalizedField) {
                    $localizedFields = array_merge($localizedFields, $referencedLocalizedField->getChildren());
                }

                foreach ($localizedFields as $localizedField) {
                    $fieldData[] = [
                        'name' => $localizedField->getName(),
                        'type' => $localizedField->getFieldType() . ' (localized)',
                    ];
                }
                continue;
            }

            $fieldData[] = [
                'name' => $fieldDefinition->getName(),
                'type' => $fieldType,
            ];
        }
        return $fieldData;
    }

    private function getRelationFields(array $fieldDefinitions): array
    {
        $relationFields = [];

        /** @var Data $fieldDefinition */
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if ($fieldDefinition instanceof AbstractRelations) {
                foreach ($fieldDefinition->getClasses() as $class) {
                    $relationFields[] = [
                        'fromFieldName' => $fieldDefinition->getName(),
                        'toNode' => $class['classes'],
                        'toType' => DiagramsXmlGenerator::TYPE_CLASS,
                        'relationType' => $fieldType,
                    ];
                }
            }

            if ($fieldDefinition instanceof FieldCollections) {
                foreach ($fieldDefinition->getAllowedTypes() as $allowedType) {
                    $relationFields[] = [
                        'fromFieldName' => $fieldDefinition->getName(),
                        'toNode' => $allowedType,
                        'toType' => DiagramsXmlGenerator::TYPE_FIELD_COLLECTION,
                        'relationType' => 'OneToMany',
                    ];
                }

            }

            if ($fieldDefinition instanceof ObjectBricks) {
                foreach ($fieldDefinition->getAllowedTypes() as $brickName) {
                    $relationFields[] = [
                        'fromFieldName' => $fieldDefinition->getName(),
                        'toNode' => $brickName,
                        'toType' => DiagramsXmlGenerator::TYPE_OBJECT_BRICK,
                        'relationType' => 'OneToOne',
                    ];
                }
            }
        }

        return $relationFields;
    }
}
