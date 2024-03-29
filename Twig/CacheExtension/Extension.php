<?php

/*
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Twig\CacheExtension;

use Twig\Extension\AbstractExtension;

/**
 * Extension for caching template blocks with twig.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class Extension extends AbstractExtension
{
    private $cacheStrategy;

    /**
     * @param CacheStrategyInterface $cacheStrategy
     */
    public function __construct(CacheStrategyInterface $cacheStrategy)
    {
        $this->cacheStrategy = $cacheStrategy;
    }

    /**
     * @return CacheStrategyInterface
     */
    public function getCacheStrategy()
    {
        return $this->cacheStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        if (version_compare(\Twig_Environment::VERSION, '1.26.0', '>=')) {
            return get_class($this);
        }
        return 'asm89_cache';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            new TokenParser\Cache(),
        );
    }
}
