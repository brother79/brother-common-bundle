<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 12.06.14
 * Time: 16:42
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\MongoDB;

use MongoCursor;

class MongoCollection
{
    /**
     * @var BaseRepository
     */
    private $repository;
    /**
     * @var \MongoCollection|\Doctrine\MongoDB\Collection
     */
    private $collection = null;
    private $query = array();
    private $sort = null;
    private $options = null;
    private $limit = 10000;
    private $offset = 0;
    private $countLimit = 0;

    /**
     * @param $repository BaseRepository
     * @param array $query
     * @param array $sort
     * @param array $options
     */
    function __construct($repository, $query = array(), $sort = null, $options = array())
    {
        $this->repository = $repository;
        $this->collection = $repository->getCollection();
        $this->query = $query;
        $this->sort = $this->fixSort($sort);
        $this->options = $options;
    }

    public function getOption($name, $default=null)
    {
        if (empty($this->options[$name])) {
            return null;
        }
        return $this->options[$name];
    }

    /**
     * @return MongoCursor
     */
    public function find()
    {
        $c = $this->collection->find($this->query);
        /* @var $c MongoCursor */
        if (!empty($this->options['hint'])) {
            $c->hint($this->options['hint']);
        }
        if ($this->sort) {
            $c->sort($this->sort);
        }
        $c->skip($this->offset)->limit($this->limit);
        return $c;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->countLimit) {
            return $this->offset + $this->collection->getMongoCollection()->count($this->query, $this->countLimit, $this->offset);
        }
        return $this->collection->count($this->query);
    }

    /**
     *
     */
    public function getItems()
    {
        if ($this->getOption('key_main') || $this->getOption('key_default')) {
            return $this->repository->findByCache($this->query, $this->sort, $this->limit, $this->offset, array(
                'key' => $this->getOption('key_main'),
                'lifetime_main' => $this->getOption('lifetime_main'),
            ));
        }
        return $this->repository->loadFromCursor($this->find());
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param int $start
     */
    public function setOffset($start)
    {
        $this->offset = $start;
    }

    /**
     * @return array|null
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param array|null $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @param array $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getCountLimit()
    {
        return $this->countLimit;
    }

    /**
     * @param int $countLimit
     */
    public function setCountLimit($countLimit)
    {
        $this->countLimit = $countLimit;
    }

    private function fixSort($sort)
    {
        foreach($sort as $k => $v) {
            if ($v == '-1') {
                $sort[$k]= -1;
            }
            if ($v == '1') {
                $sort[$k]= 1;
            }
        }
        return $sort;
    }

}