<?php
/**
 * Created by PhpStorm.
 * User: Andrey
 * Date: 08.01.2018
 * Time: 10:20
 */

namespace Brother\CommonBundle\Twig;


use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class BundleLoader implements LoaderInterface
{

    /**
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * BundleLoader constructor.
     *
     * @param FilesystemLoader $loader
     */
    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    private function normName($name)
    {
        if (preg_match('/^(\w+)Bundle:(\w+):(\w+)\.html\.twig$/', $name, $m)) {
            return '@' . $m[1] . '/' . $m[2] . '/' . $m[3] . '.html.twig';
        } elseif (preg_match('/^@(\w+\/)*\w+\.html\.twig$/', $name)) {
            return $name;
        }
//        file_put_contents('debug_log.txt', print_r($name, true));
        return $name;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @return Source
     * @throws LoaderError
     */
    public function getSourceContext($name): Source
    {
        return $this->loader->getSourceContext($this->normName($name));
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     *
     * @throws LoaderError
     */
    public function getCacheKey($name): string
    {
        return $this->loader->getCacheKey($this->normName($name));
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws LoaderError
     */
    public function isFresh($name, $time): bool
    {
        return $this->loader->isFresh($this->normName($name), $time);
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists($name)
    {
        return $this->loader->exists($this->normName($name));
    }
}