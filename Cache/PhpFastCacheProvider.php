<?php

namespace Brother\CommentBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use phpFastCache\Core\phpFastCache;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;


class PhpFastCacheProvider extends CacheProvider implements CacheInterface {
    public function __construct() {

        if (extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI, "CGI") === false) {
            $driver = "apc";
        } elseif (extension_loaded('xcache') && function_exists("xcache_get")) {
            $driver = "xcache";
        } else {
            $driver = "auto";
        }

        phpFastCache::setup("storage", $driver);

    }

    public function doDelete($id) {
        __c()->delete($id);
    }

    public function doFlush() {
        __c()->clean();
    }

    public function doGetStats() {
        return __c()->stats();
    }

    public function has($class) {
        return $this->doContains($class);
    }

    public function doContains($id) {
        return __c()->isExisting($id);
    }

    public function read($class) {
        return $this->doFetch($class);
    }

    public function doFetch($id) {
//        $a = __c();

//        $b = $this->doGetStats();

        $value = __c()->get($id);
        if ($value == null) {
            return false;
        }

        return $value;
    }

    public function write(ClassMetadata $metadata) {
        $this->doSave($metadata->getClassName(), $metadata);
    }

    public function doSave($id, $data, $lifeTime = 0) {
        __c()->set($id, $data, ($lifeTime == 0) ? 24 * 3600 : $lifeTime);
    }
}