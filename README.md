# Information
# Installation
## Get the bundle

Let Composer download and install the bundle by running
```sh
php composer.phar require brother/config-bundle:dev-master
```
in a shell.
## Enable the bundle
```php
// in app/AppKernel.php
public function registerBundles() {
	$bundles = array(
		// ...
            new Brother\CommonBundle\BrotherCommonBundle(),
	);
	// ...
}
```
## enable error notifier

```php
// in app/AppKernel.php
 protected function initializeContainer()
    {
        parent::initializeContainer(); 

        \Brother\CommonBundle\AppDebug::setContainer($this->getContainer());
    }
```

## enable error twig cache

```php
// in app/AppKernel.php
 protected function initializeContainer()
    {
        parent::initializeContainer(); 

        $m = $this->getContainer()->get('doctrine_mongodb')->getManager();
        /* @var $m DocumentManager */
        $cache = $m->getConfiguration()->getMetadataCacheImpl();
        $cacheProvider = new DoctrineCacheAdapter($cache);
        $lifetimeCacheStrategy = new LifetimeCacheStrategy($cacheProvider);
        $cacheExtension = new CacheExtension($lifetimeCacheStrategy);
        $twig = $this->getContainer()->get('twig');
        $twig->addExtension($cacheExtension);

    }
```

# Usage



