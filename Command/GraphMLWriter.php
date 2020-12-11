<?php


namespace ERDiagramXMLExportBundle\Command;


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

        foreach ($this->data as $entries) {
           foreach ($entries as $entry) {

               $relatedClasses = $entry['relatedClasses'];

               // Wenn die Klasse Relations hat
               if (!empty($relatedClasses)){
                   dump($entry['class']);
                   dump($relatedClasses);
                        $parentClass = $entry['class'];


                        $this->createNode($parentClass);

                        foreach ($relatedClasses as $class) {
                            foreach ($class as $relationType => $className) {
                                dump($className);
                                //Node erstellen
                                $this->createNode($className);
                                $this->createEdge($parentClass, $className);
                            }
                        }


               }
               //Bastodo: Auch Klassen abbilden, die keine Relations haben
           }
        }

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

    private function createNode($className)
    {
        $nodeContent = "<node id='%s'>
                <data key='nodegraphics'>
                    <y:ShapeNode>
              
                <y:NodeLabel > %s </y:NodeLabel>
              <y:Shape type='roundrectangle'/>
            </y:ShapeNode>
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