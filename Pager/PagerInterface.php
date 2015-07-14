<?php

namespace Brother\CommonBundle\Pager;

/**
 * Interface to be implemented by the pager.
 */
interface PagerInterface
{
    /**
     * Returns paginated list
     *
     * @param mixed     $queryBuilder
     * @param integer   $offset
     * @param integer   $limit
     */
    public function getList($queryBuilder, $offset, $limit);

    /**
     * Returns pagination links
     *
     * @return string
     */
    public function getHtml();
}
