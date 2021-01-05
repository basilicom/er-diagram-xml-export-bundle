<?php


namespace ERDiagramXMLExportBundle\Command;


use Pimcore\Model\DataObject;

class GraphMLWriter
{

    private $data = [];
    private $xmlOutput = '';
    private $actualNodeId = 0;
    private $actualEdgeId = 0;

    /**
     * GraphMLWriter constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function output()
    {

      //  dump($this->data);
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
      <graph>
    ";

    }

    private function createNodesAndEdges() {
        foreach ($this->data as $entry) {
            dump($entry);


            // Wenn die Klasse Relations hat
            $relatedClasses = $entry['relatedClasses'];

            if (!empty($relatedClasses)) {
                $parentClass = $entry['name'];

                $this->createNode($parentClass);

                foreach ($relatedClasses as $class) {
                    foreach ($class as $relationType => $className) {
                        //Node erstellen
                        $this->createNode($className);
                        $this->createEdge($parentClass, $className);
                    }
                }
            }
            //Klassen abbilden, die keine Relations haben
            if (empty($relatedClasses) && array_key_exists('class', $entry)) {
                $this->createNode($entry['class']);
            }
        }
    }


    private function createNode($className)
    {

        $nodeContent = "<node id='%s'>
                <data key='nodegraphics'>
                    <y:GenericNode configuration='com.yworks.entityRelationship.small_entity'>
                    <y:Geometry height='40.0' width='100.0'/>
                    <y:Fill color='#E8EEF7' color2='#B7C9E3' transparent='false'/>
                    
                <y:NodeLabel alignment='center' autoSizePolicy='content' verticalTextPosition='bottom' horizontalTextPosition='center'> %s </y:NodeLabel>
              <y:Shape type='roundrectangle'/>
            </y:GenericNode>
          </data>
        </node>
        ";
        $nodeContent = sprintf($nodeContent, $className ,$className);
        $this->xmlOutput .= $nodeContent;

        $this->actualNodeId += 1;
    }

    private function createEdge($source, $target)
    {
        $edgeContent = "
            <edge id='%s' source='%s' target='%s'>
                <data key='edgegraphics'>
                     <y:PolyLineEdge>
          
                     </y:PolyLineEdge>
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