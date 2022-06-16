# ERDiagramXMLExportBundle

## General Information

This Pimcore-Bundle will create an XML File to represent the Entity Relationship of Pimcore Classes, 
ObjectBricks and FieldCollections. diagrams.net (formerly draw.io) or yEd.

You can open Diagrams.net graphs with the [diagrams.net](https://diagrams.net/) editor to adjust the layout to your liking.

For yEd graphs, you can use the [yEd Graph Editor](https://www.yworks.com/products/yed/download).
After opening the file you should use the `Layout` Tab in the Graph Editor to arrange the rendered Graphics.

#### Diagrams.net
1. Turning DataObjects (blue), ObjectBricks (orange), and FieldCollections (yellow) and their attributes into ER Nodes
2. Recognising Localized Fields, marked as `(localized)`, and Blocks, indented with `>`
3. Turning Relations into `one to one`, `one to many`, or `many to many` ER edges - from the field where they are set to the corresponding node

#### yEd
1. Turning DataObjects (blue), ObjectBricks (orange), and FieldCollections (yellow) and their attributes into ER Nodes
2. Turning Relations into `one to one`, `one to many`, or `many to many` ER edges from node to node
3. Automatic layout through the yEd editor

### Limitations

#### Diagrams.net
1. Localized Fields will need to be reordered if there are multiple blocks
(limited by the way pimcore outputs the data)
2. Classification stores are not included yet
3. The output won't be arranged

#### yEd
1. LocalizedFields, Blocks and ClassificationStores are not included yet
2. Relations form within Object Bricks and FieldCollections are not included yet
3. Field where a relation is set do not appear in the attribute list
4. Fields are not separated 


## Usage
```shell
bin/console basilicom:create-er-diagram-xml-export <format: diagrams | yed> [<filename>]
```

The generated file will be saved in `/var/tmp/` as `PimcoreClassDiagram.xml` if no
file name is provided.

### Capabilities


## Setup

#### Pimcore Configuration 
Enable the Bundle in `/config/bundles.php` 

```php
return [
   Basilicom\ERDiagramXMLExportBundle\ERDiagramXMLExportBundle::class => ['all' => true],
];
```

And register it in `/src/Kernel.php` 

```php
use Basilicom\ERDiagramXMLExportBundle\ERDiagramXMLExportBundle;

class Kernel extends PimcoreKernel
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

## Further development Ideas

#### Diagrams.net
- ClassificationStore
  - groups as attributes 
  - keys like block attributes (with >)
- Correct sorting for LocalizedFields

