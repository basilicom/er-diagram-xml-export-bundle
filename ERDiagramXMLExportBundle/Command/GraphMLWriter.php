<?php


namespace Basilicom\ERDiagramXMLExportBundle\Command;

use Spatie\ArrayToXml\ArrayToXml;

class GraphMLWriter
{
    private array $classDefinitions;
    private array $fieldCollections;
    private array $objectBricks;
    private string $xmlOutput = '';
    private ?string $filename;
    private int $actualEdgeId = 0;
    private int $actualBoxHeight = 0;
    private int $actualBoxWidth = 0;

    /**
     * GraphMLWriter constructor.
     *
     * @param array $classDefinitions
     * @param array $fieldCollections
     * @param array $objectBricks
     * @param ?string $filename
     */
    public function __construct(array $classDefinitions, array $fieldCollections, array $objectBricks, ?string
    $filename = null)
    {
        $this->classDefinitions = $classDefinitions;
        $this->fieldCollections = $fieldCollections;
        $this->objectBricks = $objectBricks;
        $this->filename = $filename;
    }

    public function output()
    {
        $this->createHeader();

        $this->createNodesAndEdges();

        $this->createFooter();
        $this->writeToFile();
    }


    private function createHeader()
    {
        $this->xmlOutput .= "<?xml version='1.0' encoding='UTF-8' standalone='no'?>
    <graphml  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
              xmlns:y='http://www.yworks.com/xml/graphml' 
              xsi:schemaLocation='http://graphml.graphdrawing.org/xmlns http://www.yworks.com/xml/schema/graphml/1.1/ygraphml.xsd'
    >
    <key for='node' id='nodegraphics' yfiles.type='nodegraphics'/>
    <key for='graphml' id='resources' yfiles.type='resources'/>
    <key for='edge' id='edgegraphics' yfiles.type='edgegraphics'/>
      <graph edgedefault='directed' id='G'>
    ";
    }

    private function createNodesAndEdges()
    {
        foreach ($this->classDefinitions as $classDefinition) {
            $this->createNode($classDefinition);

            $relatedClasses = $classDefinition['relatedClasses'];
            $relatedFieldCollections = $classDefinition['relatedFieldCollections'];
            $relatedObjectBricks = $classDefinition['relatedObjectBricks'];

            $parentClass = $classDefinition['name'];

            if (!empty($relatedClasses)) {
                foreach ($relatedClasses as $class) {
                    foreach ($class as $relationType => $className) {
                        $this->createEdge($parentClass, $className, $relationType, $className);
                    }
                }
            }
            if (!empty($relatedFieldCollections)) {
                foreach ($relatedFieldCollections as $relatedFieldCollection => $fieldCollectionName) {
                    $this->createEdge($parentClass, $fieldCollectionName, 'onetomany', $fieldCollectionName);
                }
            }

            if (!empty($relatedObjectBricks)) {
                foreach ($relatedObjectBricks as $relatedObjectBrick => $objectBrickName) {
                    $this->createEdge($parentClass, $objectBrickName, '', $objectBrickName);
                }
            }
        }

        foreach ($this->fieldCollections as $fieldCollectionData) {
            foreach ($fieldCollectionData as $fieldCollection) {
                $this->createNode($fieldCollection, true);
            }
        }

        foreach ($this->objectBricks as $objectBricksData) {
            foreach ($objectBricksData as $objectBrick) {
                $this->createNode($objectBrick, false, true);
            }
        }
    }


    private function createNode(array $entry, bool $isFieldCollection = false, bool $isObjecktBrick = false)
    {
        $className = $entry['name'];
        $fillColor = '#E8EEF7';
        if ($isFieldCollection) {
            $fillColor = '#F7F0E8';
        }
        if ($isObjecktBrick) {
            $fillColor = '#F7E8E8';
        }

        $attributes = $this->createAttributes($entry);

        /*
         * Sadly i cant use Spatie\ArrayToXml\ArrayToXml here because it's not possible to set an array for the _value
         * Element
         * see: https://github.com/spatie/array-to-xml/issues/75#issuecomment-413726065
         */
        $nodeContent = '<node id="%s">
          <data key="nodegraphics">
            <y:GenericNode configuration="com.yworks.entityRelationship.big_entity">
              <y:Geometry height="%d" width="%d" />
              <y:Fill color="#E8EEF7" color2="#B7C9E3" transparent="false"/>
              <y:NodeLabel alignment="center" autoSizePolicy="content" backgroundColor="%s" configuration="com.yworks.entityRelationship.label.name"  horizontalTextPosition="center" modelName="internal" modelPosition="t" textColor="#000000" verticalTextPosition="bottom" visible="true"  >%s</y:NodeLabel>
              %s
            </y:GenericNode>
          </data>
        </node>';


        $nodeContent = sprintf($nodeContent, $className, $this->actualBoxHeight, $this->actualBoxWidth, $fillColor,
                               $className, $attributes);

        $this->xmlOutput .= $nodeContent;
    }

    private function createAttributes($entry): string
    {
        $fields = $entry['fields'];
        $attributesString = '';

        if (!empty($fields)) {
            $this->actualBoxHeight = 40 + count($fields) * 30;

            foreach ($fields as $field) {
                foreach ($field as $fieldname => $fieldtype) {
                    if (!is_array($fieldtype)) {
                        $attributesString .= $fieldname . ': ' . $fieldtype . PHP_EOL;

                    }
                    if (is_array($fieldtype)) {
                        $allowedTypes = '';
                        foreach ($fieldtype as $index => $allowedType) {
                            $allowedTypes .= $allowedType . ' | ';
                        }

                        $allowedTypes = substr($allowedTypes, 0, -3);
                        $attributesString .= $fieldname . ': ' . $allowedTypes . PHP_EOL;
                    }
                    $this->actualBoxWidth = 120 + strlen($attributesString);
                }
            }
        }


        $rootElement = [
            'rootElementName' => 'y:NodeLabel',
            '_attributes' => [
                'alignement' => 'left',
                'autoSizePolicy' => 'content',
                'horizontalTextPosition' => 'center',
                'verticalTextPosition' => 'top',
                'visible' => 'true',
                'clipContent' => 'true',
                'hasDetailsColor' => 'false',
                'omitDetails' => 'false',
                'modelName' => 'internal',
                'modelPosition' => 'c',
                'configuration' => 'com.yworks.entityRelationship.label.attributes',
            ],

        ];

        $attributes = [
            '_value' => $attributesString,
        ];

        $arrayToXml = new ArrayToXml($attributes, $rootElement);

        return $arrayToXml->dropXmlDeclaration()->prettify()->toXml();
    }

    private function calculateBoxWidth($attributesString) {

        $newSize = 120 + strlen($attributesString);
        if ($newSize > $this->actualBoxWidth) {
            $this->actualBoxWidth = $newSize;
        }
    }

    private function createEdge($source, $target, $relationType = '', $labelName)
    {
        if (strpos(strtolower($relationType), 'manytomany') !== false) {
            $sourceArrowType = 'crows_foot_many';
            $targetArrowType = 'crows_foot_many';
        }
        if (strpos(strtolower($relationType), 'onetomany') !== false) {
            $sourceArrowType = 'none';
            $targetArrowType = 'crows_foot_many';
        }
        if (strpos(strtolower($relationType), 'manytoone') !== false) {
            $sourceArrowType = 'crows_foot_many';
            $targetArrowType = 'none';
        }
        if ($relationType == '') {
            $sourceArrowType = 'none';
            $targetArrowType = 'none';
        }

        $edgeContent = "
        <edge id='%s' source='%s' target='%s'>
            <data key='edgegraphics'>
                 <y:PolyLineEdge>
                    <y:Arrows source='%s' target='%s'/>
                    <y:EdgeLabel alignment='center' configuration='AutoFlippingLabel' distance='2.0' horizontalTextPosition='center'  modelName='custom' preferredPlacement='center_on_edge' ratio='0.5' verticalTextPosition='bottom' >%s
                        <y:LabelModel>
                            <y:RotatedDiscreteEdgeLabelModel angle='0.0' autoRotationEnabled='true'/>
                        </y:LabelModel>
                        <y:ModelParameter>
                            <y:RotatedDiscreteEdgeLabelModelParameter position='tail'/>
                        </y:ModelParameter>
                        <y:PreferredPlacementDescriptor angle='0.0' angleOffsetOnRightSide='0' angleReference='absolute' angleRotationOnRightSide='co' distance='-1.0' placement='center' side='on_edge' sideReference='relative_to_edge_flow'/>
                    </y:EdgeLabel>
                 </y:PolyLineEdge>
            </data>
        </edge>";

        $edgeContent = sprintf($edgeContent, $this->actualEdgeId, $source, $target, $sourceArrowType,
                               $targetArrowType, $labelName);
        $this->actualEdgeId += 1;

        $this->xmlOutput .= $edgeContent;
    }


    private function writeToFile()
    {
        if (empty($this->filename)) {
            $file = __DIR__ . '/' . 'output.graphml';
        }
        else $file = __DIR__ . '/' . $this->filename . '.graphml';
        file_put_contents($file, $this->xmlOutput);
    }

    private function createFooter()
    {
        $this->xmlOutput .= "
    </graph>
  <data key='resources'>
    <y:Resources/>
  </data>
</graphml>";
    }

}