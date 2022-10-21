<?php

namespace Brother\CommonBundle\Cache;

use Brother\CommonBundle\AppDebug;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Predis\ClientInterface;

class BrotherCacheProvider extends CacheProvider
{

    const SEMAFOR_STARTING = 'semafor_starting';
    const SEMAFOR_IN_PROGRESS = 'semafor_in_progress';
    const SEMAFOR_FINISHED = 'semafor_finish';

    /**
     * @var ClientInterface|\Predis\Client
     */
    private $client;

    /**
     * @param ClientInterface $client
     *
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $tt = microtime(true);
        $result = $this->client->get($id);
        if (null === $result) {
            AppDebug::addTime(__METHOD__, microtime(true) - $tt, $id);
            return false;
        }
        try {
            AppDebug::addTime(__METHOD__, microtime(true) - $tt, $id);
            return @unserialize($result);
        } catch (\Exception $e) {
            AppDebug::_dx([$id => $result, $e->getTraceAsString()], $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedItems = call_user_func_array(array($this->client, 'mget'), $keys);

        return array_map('unserialize', array_filter(array_combine($keys, $fetchedItems)));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
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
    protected function doContains($id)
    {
        return $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $t = microtime(true);
        $data = serialize($data);
        if (strlen($data) > 9000000) {
            AppDebug::_dx([strlen($data), $id, $data, $lifeTime]);
        }
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }
        AppDebug::addTime(__METHOD__ . ':' . preg_replace('/:.*/', '', $id), microtime(true) - $t);
        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
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
    protected function doFlush()
    {
        $response = $this->client->flushdb();

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->client->info();

        return [
            Cache::STATS_HITS => $info['Stats']['keyspace_hits'],
            Cache::STATS_MISSES => $info['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE => false
        ];
    }

    public function keys($pattern)
    {
        return $this->client->keys($pattern);
    }

    private function escape($id)
    {
        return str_replace(['[', ']'], ['\\[', '\\]'], $id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys)
    {
        if (empty($keys)) {
            return [];
        }

        // note: the array_combine() is in place to keep an association between our $keys and the $namespacedKeys
        $namespacedKeys = array_combine($keys, $keys);
        $items = $this->doFetchMultiple($namespacedKeys);
        $foundItems = [];

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
    public function saveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $namespacedKeysAndValues = [];
        foreach ($keysAndValues as $key => $value) {
            $namespacedKeysAndValues[$key] = $value;
        }

        return $this->doSaveMultiple($namespacedKeysAndValues, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->doContains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->doDelete($id);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    private static function getKey(array $params): string
    {
        return 'semafor:' . md5(json_encode($params));
    }

    /**
     * @param string $key
     * @param string $name
     *
     * @return bool
     */
    public function hasSemafor(string $key, string $name): bool
    {
        $key = self::getKey(['key' => $key, 'semafor' => $name]);
        return $this->fetch($key) ? true : false;
    }

    /**
     * Возвращаем значение семафора
     *
     * @param string $name
     *
     * @return string
     */
    public function getSemafor(string $key, string $name): string
    {
        $key = $this->getKey(['key' => $key, 'semafor' => $name]);
        return $this->fetch($key);
    }

    /**
     * Шаг 1. Отправили запрос на старт процесса
     *
     * @return bool
     */
    public function isStarting(string $key): bool
    {
        return $this->hasSemafor($key, self::SEMAFOR_STARTING);
    }


    public function isFinished(string $key): bool
    {
        $finished = $this->getSemafor($key, self::SEMAFOR_FINISHED);
        return $finished == 'fail' || $finished == 'success';
    }

    /**
     * Шаг 2. Запустился фоновый процесс
     * Истина если есть семафор на старт или уже семафор на процесс
     *
     * @return bool
     */
    public function inProgress(string $key): bool
    {
        return $this->hasSemafor($key, self::SEMAFOR_IN_PROGRESS);
    }

    /**
     * @param string $key
     * @param string $name
     * @param int $ttl
     * @param bool $value
     */
    public function setSemafor(string $key, string $name, int $ttl, $value = true): void
    {
        $key = $this->getKey(['key' => $key, 'semafor' => $name]);
        $this->save($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @param string $name
     */
    public function removeSemafor(string $key, string $name): void
    {
        $key = $this->getKey(['key' => $key, 'semafor' => $name]);
        $this->delete($key);
    }

    public function getSemaforStatus(string $key)
    {
        return ($this->isStarting($key) ? 's' : '') . ($this->inProgress($key) ? 'p' : '') . ($this->isFinished($key) ? 'f' : '');
    }

}
