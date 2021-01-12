<?php


namespace ERDiagramXMLExportBundle\Command;

use Spatie\ArrayToXml\ArrayToXml;

class GraphMLWriter
{

    private $classDefinitions = [];
    private $fieldCollections = [];
    private $xmlOutput = '';
    private $actualNodeId = 0;
    private $actualEdgeId = 0;

    /**
     * GraphMLWriter constructor.
     *
     * @param array $classDefinitions
     * @param array $fieldCollections
     */
    public function __construct(array $classDefinitions, array $fieldCollections)
    {
        $this->classDefinitions = $classDefinitions;
        $this->fieldCollections = $fieldCollections;
    }

    public function output()
    {
        // dump($this->data);
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

            // Wenn die Klasse Relations hat
            $relatedClasses = $classDefinition['relatedClasses'];

            if (!empty($relatedClasses)) {
                $parentClass = $classDefinition['name'];

                foreach ($relatedClasses as $class) {
                    foreach ($class as $relationType => $className) {
                        $this->createEdge($parentClass, $className);
                    }
                }
            }
        }
        foreach ($this->fieldCollections as $fieldCollection) {
            $this->createNode($fieldCollection, true);
        }
    }


    private function createNode(array $entry, bool $isFieldCollection = false )
    {
        $className = $entry['name'];
        $fillColor = '#E8EEF7';
        if ($isFieldCollection) {
            $fillColor = '#f7f0e8';
        }


        /*
         * Sadly i cant use Spatie\ArrayToXml\ArrayToXml here because it's not possible to set an array for the _value
         * Element
         * see: https://github.com/spatie/array-to-xml/issues/75#issuecomment-413726065
         */
        $nodeContent = '<node id="%s">
          <data key="nodegraphics">
            <y:GenericNode configuration="com.yworks.entityRelationship.big_entity">
              <y:Geometry height="120.0" width="160.0" />
              <y:Fill color="#E8EEF7" color2="#B7C9E3" transparent="false"/>
              <y:NodeLabel alignment="center" autoSizePolicy="content" backgroundColor="%s" configuration="com.yworks.entityRelationship.label.name"  horizontalTextPosition="center" modelName="internal" modelPosition="t" textColor="#000000" verticalTextPosition="bottom" visible="true"  >%s</y:NodeLabel>
              %s
            </y:GenericNode>
          </data>
        </node>';

        $nodeContent = sprintf($nodeContent, $className, $fillColor, $className, $this->createAttributes($entry));

        $this->xmlOutput .= $nodeContent;

        $this->actualNodeId += 1;
    }

    private function createAttributes($entry): string
    {
        $fields = $entry['fields'];
        $attributesString = '';

        if (!empty($fields)) {
            foreach ($fields as $field) {
                foreach ($field as $fieldname => $fieldtype) {
                    $attributesString .= $fieldname . ': ' . $fieldtype .PHP_EOL;
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
          '_value' => $attributesString
      ];

      $arrayToXml = new ArrayToXml($attributes, $rootElement);

      return $arrayToXml->dropXmlDeclaration()->prettify()->toXml();
    }

    private function createEdge($source, $target)
    {
        $edgeContent = "
        <edge id='%s' source='%s' target='%s'>
            <data key='edgegraphics'>
                 <y:PolyLineEdge></y:PolyLineEdge>
            </data>
        </edge>";

        $edgeContent = sprintf($edgeContent, $this->actualEdgeId, $source, $target);
        $this->actualEdgeId += 1;

        $this->xmlOutput .= $edgeContent;
    }


    /** BASTODO Ordentlich bauen */
    private function writeToFile()
    {

        $file = __DIR__ . '/' . 'output.graphml';
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