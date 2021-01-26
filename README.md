# ERDiagramXMLExportBundle

### Installation
Check `Resources/config/services.yaml` for example configuration.

### Usage
```
bin/console basilicom::create-er-diagram-xml-export 
bin/console basilicom::create-er-diagram-xml-export <filename>
```

If you use the Command without providing an output-filename the resulting xml-Data will be saved in `output.graphml` 
#### Symfony 4.x Configuration 
Make sure to enable the Bundle in `app/config/bundles.php`, e.g. 

```
return [
   Basilicom\ERDiagramXMLExportBundle\ERDiagramXMLExportBundle::class => ['all' => true],
];
```

#### Symfony 3.x Configuration
Same as above, but the bundle must be added to BundleCollection in `AppKernel.php`, e.g. 

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
