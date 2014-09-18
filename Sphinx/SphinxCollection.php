<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 12.06.14
 * Time: 16:42
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\Sphinx;


use Brother\CommonBundle\AppDebug;
use MongoCursor;
use Sol\NewsBundle\Repository\BaseRepository;

class SphinxCollection
{
    /**
     * @var \IAkumaI\SphinxsearchBundle\Search\Sphinxsearch
     */
    private $sphinx;

    /**
     * @var BaseRepository
     */
    private $repository;
    /**
     * @var \MongoCollection|\Doctrine\MongoDB\Collection
     */
    private $query = array();
    private $sort = null;
    private $options = null;
    private $limit = 10000;
    private $offset = 0;
    private $countLimit = 0;

    /**
     * @var array
     */
    private $indexes;

    private $result = null;

    /**
     * @param $sphinx \IAkumaI\SphinxsearchBundle\Search\Sphinxsearch
     * @param $repository BaseRepository
     * @param array $query
     * @param array $sort
     * @param array $options
     */
    function __construct($repository, $sphinx, $indexes, $query = array(), $sort = null, $options = array())
    {
        $this->sphinx = $sphinx;
        $this->repository = $repository;
        $this->query = $query;
        $this->sort = $sort;
        $this->options = $options;
        $this->indexes = $indexes;
    }

    /**
     * @return MongoCursor
     */
    public function find()
    {
        $this->sphinx->SetLimits($this->offset, $this->limit, 10000, $this->getCountLimit(20000));
        if (isset($this->sort['sortBy'])) {
            $this->sphinx->SetSortMode($this->sort['mode'], $this->sort['sortBy']);
        }

        if (isset($this->query['filterBetweenDates'])) {
            foreach ($this->query['filterBetweenDates'] as $field=>$range) {
                $this->sphinx->setFilterBetweenDates($field,
                    empty($range['$gte']) ? null : $range['$gte'],
                    empty($range['$lte']) ? null : $range['$lte']
                );
            }
            unset($this->query['filterBetweenDates']);
        }


        $query = is_array($this->query) ? implode(' ', $this->query) : $this->query;
        $this->result = $this->sphinx->search($query, $this->indexes);
        return $this->result;
        AppDebug::_d($this->sort);
        AppDebug::_d($this->result);
        AppDebug::_dx($this->query);
        /* @var $c MongoCursor */

        $sphinx = $this->sphinx;


        // Apply sphinx filter
        // updated - is a timestamp-attribute name in sphinx config

        return $sphinx->search($request->query->get('q', ''), array('IndexName'));

    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->result == null) {
            $this->find();
        }
        if (isset($this->result['total_found'])) {
            return $this->result['total_found'];
        }
    }

    /**
     *
     */
    public function getItems()
    {
        if ($this->result == null) {
            $this->find();
        }
        $r = array();
        foreach ($this->result['matches'] as $v) {
            $r[] = $this->repository->find($v['attrs']['_id']);
        }
        return $r;
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
    public function getCountLimit($default=20000)
    {
        return $this->countLimit ? $this->countLimit : $default;
    }

    /**
     * @param int $countLimit
     */
    public function setCountLimit($countLimit)
    {
        $this->countLimit = $countLimit;
    }

}