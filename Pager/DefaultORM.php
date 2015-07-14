<?php

namespace Brother\CommonBundle\Pager;

/**
 * Default ORM Pager.
 */

class DefaultORM extends DefaultPager
{
    /**
     * Get Paginated list
     *
     * @var \Doctrine\ORM\QueryBuilder      $queryBuilder
     * @var integer                         $offset         query offset
     * @var integer                         $limit          query limit
     *
     * @return string
     */
    public function getList($queryBuilder, $offset, $limit)
    {
        return $queryBuilder->getQuery()->getResult();
    }
}
