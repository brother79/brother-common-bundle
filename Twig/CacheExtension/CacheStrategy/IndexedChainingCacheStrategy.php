<?php

/*
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Twig\CacheExtension\CacheStrategy;

use Brother\CommonBundle\Twig\CacheExtension\CacheStrategyInterface;
use Brother\CommonBundle\AppDebug;

/**
 * Combines several configured cache strategies.
 *
 * Useful for combining for example generational cache strategy with a lifetime
 * cache strategy, but also useful when combining several generational cache
 * strategies which differ on cache lifetime (infinite, 1hr, 5m).
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class IndexedChainingCacheStrategy implements CacheStrategyInterface
{
    private $strategies;

    /**
     * @param array $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchBlock($key)
    {
//        AppDebug::_dx($key);
        return $this->strategies[$key['strategyKey']]->fetchBlock($key['key']);
    }

    /**
     * {@inheritDoc}
     */
    public function generateKey($annotation, $value)
    {
        AppDebug::_dx($value);
        if (! is_array($value) || null === $strategyKey = key($value)) {
            //todo: specialized exception
            throw new \RuntimeException('No strategy key found in value.');
        }

        if (! isset($this->strategies[$strategyKey])) {
            //todo: specialized exception
            throw new \RuntimeException(sprintf('No strategy configured with key "%s".', $strategyKey));
        }

        $key = $this->strategies[$strategyKey]->generateKey($annotation, current($value));

        return array(
            'strategyKey' => $strategyKey,
            'key' => $key,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function saveBlock($key, $block)
    {
        AppDebug::_dx($key);
        return $this->strategies[$key['strategyKey']]->saveBlock($key['key'], $block);
    }
}
