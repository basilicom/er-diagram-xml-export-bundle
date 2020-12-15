<?php


namespace ERDiagramXMLExportBundle\Command;

use Pimcore\Model\DataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ERDiagramXMLExportBundle\Command\GraphMLWriter;


class CreateERDiagramXMLExportCommand extends Command
{
    protected static $defaultName = 'basilicom:create-er-diagram-xml-export';

    protected function configure()
    {
        $this->setDescription('Provides an yEd-XML Output to visualize ER of Pimcore Classes');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $result = new GraphMLWriter($this->collectClassDefinitionData());
        $result->output();

        return 0;
    }

    private function collectClassDefinitionData(): array
    {
        $dataObjectsListing = DataObject\Concrete::getList();
        $dataObjectsListing->setObjectTypes(['object']);
        $classDefinitionData = [];

        foreach ($dataObjectsListing as $dataObject) {
            $objectData = $this->exportObject($dataObject, true, true, true);
            array_push($classDefinitionData, $objectData);
        }

        return $classDefinitionData;
    }


    private function exportObject(
        DataObject $object,
        $useRecursion = true,
        $addFields = true,
        $includeVariants = true
    ): array {
        $objectData = [];

        $className = 'Folder';

        if ($object->getType() !== 'folder') {
            /** @var DataObject\ClassDefinition $classDefinition */
            $classDefinition = $object->getClass();
            $className = $classDefinition->getName();

            if ($addFields) {
                $fieldDefinitions = $classDefinition->getFieldDefinitions();
                $this->processFieldDefinitions($fieldDefinitions, $object, $objectData);
            }
        }

        $childDataList = [];

        if ($useRecursion) {
            $children = $object->getChildren();
            foreach ($children as $child) {
                $childData = $this->exportObject($child);
                if (!array_key_exists($childData['_attributes']['class'], $childDataList)) {
                    $childDataList[$childData['_attributes']['class']] = [];
                }
                $childDataList[$childData['_attributes']['class']][] = $childData;
            }
        }

        $variantDataList = [];

        if ($includeVariants) {
            $children = $object->getChildren([DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);
            foreach ($children as $child) {
                $childData = $this->exportObject($child);
                if (!array_key_exists($childData['_attributes']['class'], $variantDataList)) {
                    $variantDataList[$childData['_attributes']['class']] = [];
                }
                $variantDataList[$childData['_attributes']['class']][] = $childData;
            }
        }


        if ($childDataList !== []) {
            $objectData['pc:children'] = $childDataList;
        }

        if ($variantDataList !== []) {
            $objectData['pc:variants'] = $variantDataList;
        }


        $objectData['_attributes'] = [
            'id' => $object->getId(),
            'parentId' => $object->getParentId(),
            'type' => $object->getType(),
            'key' => $object->getKey(),
            'class' => $className,
            'relatedClasses' => $this->getRelatedClasses($object),
            'is-variant-leaf' => (($object->getType() === 'variant') && (count(
                    $variantDataList
                ) === 0) ? 'true' : 'false'),
            'is-object-leaf' => (($object->getType() === 'object') && (count($childDataList) === 0) ? 'true' : 'false'),
        ];

        return $objectData;
    }

    /**
     * If fieldtype is one of type Relation then extract the class name in the classes array of the
     * fieldDefinitions Data
     */
    private function getRelatedClasses($object): array
    {
        $objectVars = $object->getObjectVar('o_class');
        $fieldDefinitions = $objectVars->getFieldDefinitions();

        $relatedClasses = [];

        /** @var DataObject\ClassDefinition\Data $fieldDefinition */
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if (strpos($fieldType, 'Relation') !== false) {
                foreach ($fieldDefinition->getClasses() as $class) {
                    array_push($relatedClasses, [$fieldType => $class['classes']]);
                }
            }
        }

        return $relatedClasses;
    }

    private function processFieldDefinitions($fieldDefinitions, $object, &$objectData): void
    {
        /** @var DataObject\ClassDefinition\Data $fieldDefinition */
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();


            $getterFunction = 'getForType' . ucfirst($fieldType);

            if (method_exists($this, $getterFunction)) {
                $objectData[$fieldName] = $this->$getterFunction($object, $fieldName);
            } elseif ($fieldType === 'localizedfields') {
                $localizedFields = $fieldDefinition->getFieldDefinitions();
                foreach (\Pimcore\Tool::getValidLanguages() as $language) {
                    $this->language = $language;
                    $this->processFieldDefinitions($localizedFields, $object, $objectData[$fieldName][$language]);
                }
                $this->language = null;
            } else {
                $objectData[$fieldName] = [
                    '_attributes' => [
                        'skipped' => 'true',
                        'fieldtype' => $fieldType,
                    ],
                ];
            }
        }
    }
}