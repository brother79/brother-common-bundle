<?php

/*
 * This file is part of sonata-project.
 *
 * (c) 2010 Thomas Rabaix
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Twig;

use Brother\CommonBundle\AppDebug;
use Sonata\Cache\Invalidation\Recorder;

abstract class TwigTemplate14 extends \Sonata\CacheBundle\Twig\TwigTemplate14
{
    /**
     * @param  mixed  $object
     * @param  string $item
     * @param  array  $arguments
     * @param  string $type
     * @param  bool   $isDefinedTest
     * @param  bool   $ignoreStrictCheck
     *
     * @return mixed
     */
    protected function getAttribute($object, $item, array $arguments = array(), $type = \Twig_TemplateInterface::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        try {
            return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest);
        } catch (\Exception $e) {
            return null;//            AppDebug::_dx($e->getMessage());
        }
    }
}
