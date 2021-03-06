# ERDiagramXMLExportBundle

### Prerequisites and General Information

This Pimcore-Bundle will create an yEd compliant XML File to represent the Entity Relationship of Pimcore Classes, 
ObjectBricks and FieldCollections.  
Just open the generated file with the yEd Graph Editor, which you can find here:
`https://www.yworks.com/products/yed/download`.  
After opening the file you should use the `Layout` Tab in the Graph Editor to arrange the rendered Graphics according your needs.

### Usage
```
bin/console basilicom::create-er-diagram-xml-export 
bin/console basilicom::create-er-diagram-xml-export <filename>
```

The generated file will be saved in the `\var\tmp\` Folder with filename`output.graphml` .  
You can also provide your own filename.
#### Pimcore Configuration 
Make sure to enable the Bundle in `app/config/bundles.php`, e.g. 

```
return [
   Basilicom\ERDiagramXMLExportBundle\ERDiagramXMLExportBundle::class => ['all' => true],
];
```

and add it to BundleCollection in `AppKernel.php`, e.g. 

```
...
use Basilicom\ERDiagramXMLExportBundle\ERDiagramXMLExportBundle;
...

class AppKernel extends Kernel
{
    /**
     * @param BundleCollection $collection
     */
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        if (class_exists(ERDiagramXMLExportBundle::class)) {
            $collection->addBundle(new ERDiagramXMLExportBundle());
        }
    }
}
```
