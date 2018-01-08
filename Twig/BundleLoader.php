<?php
/**
 * Created by PhpStorm.
 * User: Andrey
 * Date: 08.01.2018
 * Time: 10:20
 */

namespace Brother\CommonBundle\Twig;


use Brother\CommonBundle\AppDebug;
use Twig\Loader\FilesystemLoader;
use Twig_Error_Loader;
use Twig_Source;

class BundleLoader implements \Twig_LoaderInterface {

    /**
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * BundleLoader constructor.
     *
     * @param FilesystemLoader $loader
     */
    public function __construct($loader) {
        $this->loader = $loader;
    }

    private function normName($name) {
        if (preg_match('/^(\w+)Bundle:(\w+):(\w+)\.html\.twig$/', $name, $m)) {
            return '@' . $m[1] . '/' . $m[2] . '/' . $m[3] . '.html.twig';
        }
        AppDebug::_dx($name);
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @return Twig_Source
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getSourceContext($name) {
        return $this->loader->getSourceContext($this->normName($name));
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getCacheKey($name) {
        return $this->loader->getCacheKey($this->normName($name));
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function isFresh($name, $time) {
        return $this->loader->isFresh($this->normName($name), $time);
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists($name) {
        return $this->loader->exists($this->normName($name));
    }
}