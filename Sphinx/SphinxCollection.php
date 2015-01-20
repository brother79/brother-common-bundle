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
        $this->sphinx->ResetFilters();
        $query = $this->query;
        $maxMatches = $this->getOption('max_matches', 1000);
        if ($this->offset + $this->limit>$maxMatches) {
            $maxMatches = $this->offset + $this->limit;
        }
        $this->sphinx->SetLimits($this->offset, $this->limit, $maxMatches, 20000000);//, 10000, 20000000);
        if (isset($this->sort['sortBy'])) {
            $this->sphinx->SetSortMode($this->sort['mode'], $this->sort['sortBy']);
//            $this->sphinx->SetMatchMode(SPH_MATCH_ANY);
        }
        if (isset($query['filterBetweenDates'])) {
            foreach ($query['filterBetweenDates'] as $v) {
                $this->sphinx->setFilterBetweenDates($v['attr'],
                    empty($v['dateStart']) ? null : $v['dateStart'],
                    empty($v['dateEnd']) ? null : $v['dateEnd']
                );
            }
            unset($query['filterBetweenDates']);
        }


        if (isset($query['filter'])) {
            foreach ($query['filter'] as $v) {
                $values = is_array($v['values']) ? $v['values'] : array($v['values']);
                if (count($values) == 0) {
                    $values = array(-1);
                }
                $this->sphinx->setFilter($v['attr'],
                    $values,
                    empty($v['exclude']) ? false : $v['exclude']);
            }
            unset($query['filter']);
        }
        $query = is_array($query) ? implode(' ', $query) : $query;

        if ($query) {
            $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
        }
        $this->result = $this->sphinx->search($query, $this->indexes, false);
        AppDebug::_dx(array($this->query, $this->result));
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
        if (isset($this->result['matches'])) {
            foreach ($this->result['matches'] as $v) {
                $r[] = $this->repository->findById($v['attrs']['_id']);
            }
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
    public function getCountLimit($default = 20000)
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

    private function getOption($name, $default)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

}