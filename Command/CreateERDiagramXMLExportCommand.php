<?php


namespace ERDiagramXMLExportBundle\Command;

use Pimcore\Model\DataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing as FieldCollectionListing;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections as FieldCollections;
class CreateERDiagramXMLExportCommand extends Command
{
    protected static $defaultName = 'basilicom:create-er-diagram-xml-export';

    protected function configure()
    {
        $this->setDescription('Provides an yEd-XML Output to visualize ER of Pimcore Classes');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $result = new GraphMLWriter($this->getClassDefinitionData(), $this->getFieldCollectionsData());
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
                'id' => $classDefinition->getId(),
                'name' => $classDefinition->getName(),
                'fields' => $this->processFieldDefinitions($fieldDefinitions),
                'relatedClasses' => $this->getRelatedClasses($fieldDefinitions),

            ];

            array_push($classDefinitionData, $data);
        }


        return $classDefinitionData;

    }


    private function getRelatedClasses($fieldDefinitions): array
    {
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

    private function processFieldDefinitions($fieldDefinitions): array
    {
        $data = [];

        /** @var DataObject\ClassDefinition\Data $fieldDefinition */
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldType = $fieldDefinition->getFieldtype();

            if (strpos($fieldType, 'Relation') == false) {
                $fields = [
                    $fieldDefinition->getName() => $fieldType
                ];
                if ($fieldDefinition instanceof FieldCollections) {

                    $allowedTypes = [];

                    foreach ($fieldDefinition->getAllowedTypes() as $allowedType) {
                        array_push($allowedTypes, $allowedType);
                    }
                    $fields = [
                        $fieldDefinition->getName() => $allowedTypes
                    ];
                }

                array_push($data, $fields);

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

    //      dump($fieldCollection);
          $data = [

              'name' => $fieldCollection->getKey(),
              'fields' => $this->processFieldDefinitions($fieldCollection->getFieldDefinitions())

          ];
          array_push($fieldCollectionData, $data);

      }

     // dump(($fieldCollectionData));
      return $fieldCollectionData;
    }

    //TODO  ObjectBricks laden (abbilden)
}