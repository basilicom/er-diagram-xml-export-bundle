<?php


namespace Basilicom\ERDiagramXMLExportBundle\DependencyInjection;


class DiagramsXmlGenerator
{
    private array $classDefinitions;
    private array $fieldCollections;
    private array $objectBricks;

    private int $currentNodeXPosition = 0;

    private const ATTRIBUTE_HEIGHT = 25;
    private const NODE_WIDTH = 180;
    private const NODE_MARGIN = 20;

    private const RELATION_MANY = 'ERmany';
    private const RELATION_ONE = 'none';

    public const TYPE_CLASS = 'class';
    public const TYPE_OBJECT_BRICK = 'objectbrick';
    public const TYPE_FIELD_COLLECTION = 'fieldcollection';
    public const TYPE_CLASSIFICATION_STORE = 'classificationstore';


    public function __construct(
        array $classDefinitions,
        array $fieldCollections,
        array $objectBricks,
    ) {
        $this->classDefinitions = $classDefinitions;
        $this->fieldCollections = $fieldCollections;
        $this->objectBricks = $objectBricks;
    }

    public function generate(): string
    {
        $outputString = '<mxGraphModel><root><mxCell id="graph-parent" /><mxCell id="graph-sub-parent" parent="graph-parent" />';
        foreach ($this->classDefinitions as $classDefinition) {
            $outputString .= $this->createNodesAndEdges($classDefinition, self::TYPE_CLASS);
        }
        foreach ($this->fieldCollections as $fieldCollection) {
            $outputString .= $this->createNodesAndEdges($fieldCollection, self::TYPE_FIELD_COLLECTION);
        }
        foreach ($this->objectBricks as $objectBrick) {
            $outputString .= $this->createNodesAndEdges($objectBrick, self::TYPE_OBJECT_BRICK);
        }
        $outputString .= '</root></mxGraphModel>';

        return $outputString;
    }

    private function createNodesAndEdges(array $nodeData, string $nodeType): string
    {
        $node = $this->createNode($nodeData['name'], count($nodeData['fields']), $nodeType);
        $attributes = $this->createAttributes($nodeData['name'], $nodeData['fields'], $nodeType);
        $edges = $this->createEdges($nodeData['name'], $nodeData['relationFields'], $nodeType);
        return $node . $attributes . $edges;
    }

    private function createNode(string $nodeName, int $fieldCount, string $nodeType): string
    {
        $style = match ($nodeType) {
            self::TYPE_CLASS => 'fillColor=#dae8fc;strokeColor=#6c8ebf;', // blue
            self::TYPE_FIELD_COLLECTION => 'fillColor=#FFF2CC;strokeColor=#D6B656;', // yellow
            self::TYPE_OBJECT_BRICK => 'fillColor=#FFE6CC;strokeColor=#D79B00;', // orange
            self::TYPE_CLASSIFICATION_STORE => 'fillColor=#f8cecc;strokeColor=#b85450', // red
            default => '',
        };

        $id = $nodeType . '-' . $nodeName;
        $position = $this->currentNodeXPosition;
        $this->currentNodeXPosition += self::NODE_WIDTH + self::NODE_MARGIN;
        $nodeXml = '
        <mxCell id="%s" value="%s" parent="graph-sub-parent" style="%sswimlane;fontStyle=2;align=center;verticalAlign=top;childLayout=stackLayout;horizontal=1;startSize=26;horizontalStack=0;resizeParent=1;resizeLast=0;marginBottom=0;strokeWidth=1;collapsible=0;" vertex="1">
            <mxGeometry x="%s" width="%s" height="%s" as="geometry" />
        </mxCell>';

        return sprintf(
            $nodeXml,
            $id,
            $nodeName,
            $style,
            $position,
            self::NODE_WIDTH,
            ($fieldCount + 1) * self::ATTRIBUTE_HEIGHT,
        );
    }

    private function createAttributes(string $nodeName, array $fieldData, string $nodeType): string
    {
        if (empty($fieldData)) {
            return '';
        }

        $attributeXml = '
        <mxCell id="%s" parent="%s" value="%s" style="text;align=left;verticalAlign=top;spacingLeft=4;spacingRight=4;overflow=hidden;rotatable=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;" vertex="1">
            <mxGeometry y="%s" width="%s" height="%s" as="geometry" />
        </mxCell>';

        $attributesString = '';


        $currentYPosition = 0;
        foreach ($fieldData as $field) {
            $id = $nodeType . '-' . $nodeName . '_attribute-' . $field['name'];
            $parent = $nodeType . '-' . $nodeName;
            $label = $field['name'] . ': ' . $field['type'];
            $currentYPosition += self::ATTRIBUTE_HEIGHT;

            $attributesString .= sprintf(
                $attributeXml,
                $id,
                $parent,
                $label,
                $currentYPosition,
                self::NODE_WIDTH,
                self::ATTRIBUTE_HEIGHT,
            );

        }

        return $attributesString;
    }

    private function createEdge($from, $to, $relationType = '')
    {
        $sourceArrowType = self::RELATION_ONE;
        $targetArrowType = self::RELATION_ONE;

        // str_contains because the relation types look like this 'manyToOneRelation', 'manyToManyObjectRelation'
        if (str_contains(strtolower($relationType), 'manytomany')) {
            $sourceArrowType = self::RELATION_MANY;
            $targetArrowType = self::RELATION_MANY;
        } else {
            if (str_contains(strtolower($relationType), 'onetomany')) {
                $targetArrowType = self::RELATION_MANY;
            } else {
                if (str_contains(strtolower($relationType), 'manytoone')) {
                    $sourceArrowType = self::RELATION_MANY;
                }
            }
        }

        $id = 'edge-from_' . $from . '_edge-to_' . $to;
        $edgeXml = '
        <mxCell id="%s" source="%s" target="%s" parent="graph-sub-parent" style="startArrow=%s;endArrow=%s;strokeWidth=1;edgeStyle=elbowEdgeStyle;elbow=vertical;rounded=0;jumpStyle=gap;" edge="1">
            <mxGeometry as="geometry" />
        </mxCell>';

        return sprintf(
            $edgeXml,
            $id,
            $from,
            $to,
            $sourceArrowType,
            $targetArrowType,
        );
    }

    private function createEdges(string $parentName, array $relationFields, string $parentType): string
    {
        if (empty($relationFields)) {
            return '';
        }

        $edges = '';
        foreach ($relationFields as $relationField) {
            if ($parentName === $relationField['toNode']) {
                continue;
            }

            $from = $parentType . '-' . $parentName . '_attribute-' . $relationField['fromFieldName'];
            $to = $relationField['toType'] . '-' . $relationField['toNode'];
            $edge = $this->createEdge($from, $to, $relationField['relationType']);
            $edges .= $edge;
        }

        return $edges;
    }
}
