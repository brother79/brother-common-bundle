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

use Brother\CommonBundle\Twig\CacheExtension\CacheProviderInterface;
use Brother\CommonBundle\Twig\CacheExtension\CacheStrategyInterface;
use Brother\CommonBundle\Twig\CacheExtension\Exception\InvalidCacheLifetimeException;
use Brother\CommonBundle\AppDebug;

/**
 * Strategy for caching with a pre-defined lifetime.
 *
 * The value passed to the strategy is the lifetime of the cache block in
 * seconds.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class LifetimeCacheStrategy implements CacheStrategyInterface
{
    const PREFIX='v49_';
    private $cache;

    /**
     * @param CacheProviderInterface $cache
     */
    public function __construct(CacheProviderInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchBlock($key)
    {
        return $this->cache->fetch($key['key']);
    }

    /**
     * {@inheritDoc}
     */
    public function generateKey($annotation, $value)
    {
        if (is_array($value)) {
            $lifetime = $value['lifetime'];
            $key = self::PREFIX . $annotation . '_' . $value['key'];
        } else {
            if (! is_numeric($value)) {
            throw new InvalidCacheLifetimeException($value);
        }
            $lifetime = $value;
            $key = self::PREFIX . $annotation;
        }

        return [
            'lifetime' => $lifetime,
            'key' => '__LCS__' . $key,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function saveBlock($key, $block)
    {
        return $this->cache->save($key['key'], $block, $key['lifetime']);
    }
}
