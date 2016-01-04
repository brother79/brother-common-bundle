<?php

/*
 * This file is part of twig-cache-extension.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Twig\CacheExtension\CacheProvider;

use Brother\CommonBundle\Twig\CacheExtension\CacheProviderInterface;
use Brother\CommonBundle\AppDebug;
use Doctrine\Common\Cache\Cache;

/**
 * Adapter class to use the cache classes provider by Doctrine.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class DoctrineCacheAdapter implements CacheProviderInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($key)
    {
        return $this->cache->fetch($key);
    }

    /**
     * {@inheritDoc}
     */
    public function save($key, $value, $lifetime = 0)
    {
        $this->cache->save($key, $value, $lifetime);
//        AppDebug::_d($this->fetch($key), $key . '-----');
        return $this->cache->save($key, $value, $lifetime);
    }
}
