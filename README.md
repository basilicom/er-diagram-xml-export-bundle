# ERDiagramXMLExportBundle

### Prerequisites and General Information

This Pimcore-Bundle will create an yEd compliant XML File to represent the Entity Relationship of Pimcore Classes, 
ObjectBricks and FieldCollections.  
Just open the generated file with the yEd Graph Editor, which you can find here:
`https://www.yworks.com/products/yed/download`.  
After opening the file you should use the `Layout` Tab in the Graph Editor to arrange the rendered Graphics according your needs.

### Installation
```
composer require basilicom/er-diagram-xml-export-bundle
```

### Usage
```
bin/console basilicom::create-er-diagram-xml-export 
bin/console basilicom::create-er-diagram-xml-export <filename>
```

The generated file will be saved in the `var/bundles/ERDiagramXMLExportBundle` folder with filename `pimcore.graphml` or the given `<filename>.graphml`.
