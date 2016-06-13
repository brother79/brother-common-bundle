<?php

namespace Brother\CommonBundle\Cache;

use Brother\CommonBundle\AppDebug;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Predis\ClientInterface;

class BrotherCacheProvider extends CacheProvider {

    /**
     * @var ClientInterface|\Predis\Client
     */
    private $client;

    /**
     * @param ClientInterface $client
     *
     * @return \Doctrine\Common\Cache\PredisCache
     */
    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id) {
        $result = $this->client->get($id);
        if (null === $result) {
            return false;
        }

        return unserialize($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys) {
        $fetchedItems = call_user_func_array(array($this->client, 'mget'), $keys);

        return array_map('unserialize', array_filter(array_combine($keys, $fetchedItems)));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0) {
        if ($lifetime) {
            $success = true;

            // Keys have lifetime, use SETEX for each of them
            foreach ($keysAndValues as $key => $value) {
                $response = $this->client->setex($key, $lifetime, serialize($value));

                if ((string)$response != 'OK') {
                    $success = false;
                }
            }

            return $success;
        }

        // No lifetime, use MSET
        $response = $this->client->mset(array_map(function ($value) {
            return serialize($value);
        }, $keysAndValues));

        return (string)$response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id) {
        return $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0) {
//        AppDebug::_dx($id);
        if (is_object($data)) {
            switch (get_class($data)) {
                case 'Sol\NewsBundle\Document\News':
                case 'Sol\NewsBundle\Document\Tag':
                case 'Sol\NewsBundle\Document\Favorite':
                case 'Sol\NewsBundle\Document\RssSource':
                    break;
                default:
                    AppDebug::_dx([$id, get_class($data), $data]);
                    break;
            }
        } elseif (is_array($data)) {
//            AppDebug::_dx([$id, array_keys($data), $data]);
        }
        $data = serialize($data);
        if (strlen($data) > 100000) {
            AppDebug::_dx([$id, $data, $lifeTime]);
        }
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id) {
        if (strpos($id, '*')) {
            $keys = $this->keys($this->escape($id));
            foreach ($keys as $key) {
                $this->client->del($key);
            }
            AppDebug::_dx([$id, $keys]);
        }
        return $this->client->del($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush() {
        $response = $this->client->flushdb();

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats() {
        $info = $this->client->info();

        return array(
            Cache::STATS_HITS => $info['Stats']['keyspace_hits'],
            Cache::STATS_MISSES => $info['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE => false
        );
    }

    public function keys($pattern) {
        return $this->client->keys($pattern);
    }

    private function escape($id) {
        return str_replace(['[', ']'], ['\\[', '\\]'], $id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id) {
        return $this->doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys) {
        if (empty($keys)) {
            return array();
        }

        // note: the array_combine() is in place to keep an association between our $keys and the $namespacedKeys
        $namespacedKeys = array_combine($keys, $keys);
        $items = $this->doFetchMultiple($namespacedKeys);
        $foundItems = array();

        // no internal array function supports this sort of mapping: needs to be iterative
        // this filters and combines keys in one pass
        foreach ($namespacedKeys as $requestedKey => $namespacedKey) {
            if (isset($items[$namespacedKey]) || array_key_exists($namespacedKey, $items)) {
                $foundItems[$requestedKey] = $items[$namespacedKey];
            }
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    public function saveMultiple(array $keysAndValues, $lifetime = 0) {
        $namespacedKeysAndValues = array();
        foreach ($keysAndValues as $key => $value) {
            $namespacedKeysAndValues[$key] = $value;
        }

        return $this->doSaveMultiple($namespacedKeysAndValues, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id) {
        return $this->doContains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0) {
        return $this->doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id) {
        return $this->doDelete($id);
    }
}
