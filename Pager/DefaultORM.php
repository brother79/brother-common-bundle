<?php

/*
 * This file is part of the BrotherCommonBundle package.
 *
 * (c) Yos Okusanya <yos.okusanya@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
