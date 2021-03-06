<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 12.06.14
 * Time: 16:42
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\Model\MongoDB;

use Brother\CommonBundle\AppDebug;
use MongoCursor;
use MongoDB\Collection;

class MongoCollection {
    /**
     * @var BaseRepository
     */
    private $repository;
    /**
     * @var Collection
     */
    private $collection = null;
    private $query = [];
    private $sort = null;
    private $options = null;
    private $limit = 10000;
    private $offset = 0;
    private $countLimit = 0;

    /**
     * @param       $repository BaseRepository
     * @param array $query
     * @param array $sort
     * @param array $options
     */
    function __construct($repository, $query = [], $sort = null, $options = []) {
        $this->repository = $repository;
        $this->collection = $repository->getCollection();
        $this->query = $query;
        $this->sort = $this->fixSort($sort);
        $this->options = $options;
    }

    private function fixSort($sort) {
        foreach ($sort as $k => $v) {
            if ($v == '-1') {
                $sort[$k] = -1;
            }
            if ($v == '1') {
                $sort[$k] = 1;
            }
        }
        return $sort;
    }

    /**
     * @return int
     */
    public function getCount() {
//        if ($this->countLimit) {
//            $r = $this->offset +
//            $this->collection->getMongoCollection()->count(
//                $this->query, $this->countLimit, $this->offset
//            );
//            AppDebug::_dx([$r, $this->offset]);
//            if ($r) return $r;
//        }
        return $this->repository->countByCache(
            $this->query, [
                'key' => $this->getOption('key_main') . '_count1',
                'lifetime_main' => $this->getOption('lifetime_main'),
                'controlled' => false
            ]
        );
//        return $this->collection->count($this->query);
    }

    /**
     *
     */
    public function getItems() {
        if ($this->getOption('key_main') || $this->getOption('key_default')) {
            $lastIdValue = $this->getOption('last_id_value');
            $limit = $lastIdValue ? $this->limit + 12 : $this->limit;
            $r = $this->repository->findByCache(
                $this->query, $this->sort, $limit, $this->offset, [
                    'key' => $this->getOption('key_main'),
                    'lifetime_main' => $this->getOption('lifetime_main'),
                    'controlled' => false
                ]
            );
            if ($lastIdValue) {
                foreach ($r as $k => $item) {
                    /** @var DocumentInterface $item */
                    if ($item->getLastIdValue() > $lastIdValue) {
                        unset($r[$k]);
                    }
                }
                if (count($r) < $this->limit) {
                    $r = $this->repository->findByCache(
                        $this->query, $this->sort, $limit + 100, $this->offset, [
                            'key' => $this->getOption('key_main'),
                            'lifetime_main' => $this->getOption('lifetime_main'),
                            'controlled' => false
                        ]
                    );
                    foreach ($r as $k => $item) {
                        /** @var DocumentInterface $item */
                        if ($item->getLastIdValue() > $lastIdValue && count($r) > $this->limit) {
                            unset($r[$k]);
                        }
                    }
                }
                $r = array_slice($r, 0, $this->limit);
            }
            return $r;
        }
        return $this->repository->loadFromCursor($this->find());
    }

    public function getOption($name, $default = null) {
        if (empty($this->options[$name])) {
            return null;
        }
        return $this->options[$name];
    }

    /**
     * @return array
     */
    public function find() {
        $options = [];
        if ($this->sort) {
            $options['sort'] = $this->sort;
        }
        if (!empty($this->options['hint'])) {
            $options['hint'] = $this->options['hint'];
        }
        $options['skip'] = $this->offset;
        $options['limit'] = $this->limit;
        return $this->collection->find($this->query, $options)->toArray();
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit) {
        $this->limit = $limit;
    }

    /**
     * @param int $start
     */
    public function setOffset($start) {
        $this->offset = $start;
    }

    /**
     * @return array|null
     */
    public function getSort() {
        return $this->sort;
    }

    /**
     * @param array|null $sort
     */
    public function setSort($sort) {
        $this->sort = $sort;
    }

    /**
     * @return array
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @param array $query
     */
    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * @return int
     */
    public function getCountLimit() {
        return $this->countLimit;
    }

    /**
     * @param int $countLimit
     */
    public function setCountLimit($countLimit) {
        $this->countLimit = $countLimit;
    }

}