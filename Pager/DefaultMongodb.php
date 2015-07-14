<?php

namespace Brother\CommonBundle\Pager;

/**
 * Default Mongodb ODM Pager.
 */

class DefaultMongodb extends DefaultPager
{
    /**
     * Get Paginated list
     *
     * @var \Doctrine\MongoDB\Query\Builder     $queryBuilder
     * @var integer                             $offset         query offset
     * @var integer                             $limit          query limit
     *
     * @return string
     */
    public function getList($queryBuilder, $offset, $limit)
    {
        return $queryBuilder->getQuery()->execute();
    }
}
