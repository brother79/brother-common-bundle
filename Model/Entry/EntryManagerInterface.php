<?php

/*
 * This file is part of the BrotherPageBundle package.
 *
 * (c) Yos Okusanya <yos.okusanya@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Brother\CommonBundle\Model\Entry;

/**
 * Interface to be implemented by the page manager.
 */
interface EntryManagerInterface
{
    /**
     * @param string $id
     *
     * @return EntryInterface
     */
    public function findOneById($id);

    /**
     * Finds a page entry by the given criteria
     *
     * @param array $criteria
     *
     * @return EntryInterface
     */
    public function findOneBy(array $criteria);

    /**
     * Finds page entries by the given criteria
     *
     * @param array $criteria
     *
     * @return array of EntryInterface
     */
    public function findBy(array $criteria, array $orderBy = null);

    /**
     * Creates an empty page entry instance
     *
     * @param integer $id
     *
     * @return EntryInterface
     */
    public function createEntry($id = null);

    /**
     * Saves a page entry
     *
     * @param EntryInterface $entry
     */
    public function save(EntryInterface $entry);

    /**
     * Returns the page fully qualified class name
     *
     * @return string
     */
    public function getClass();

    /**
     * Deletes a page entry
     *
     * @param EntryInterface $entry
     */
    public function remove(EntryInterface $entry);

    /**
     * Deletes a list of page entries
     *
     * @param array $ids
     */
    public function delete(array $ids);

    /**
     * Finds entries by the given criteria
     * and from the query offset.
     *
     * @param integer 	$offset
     * @param integer	$limit
     * @param array 	$criteria
     *
     * @return array of EntryInterface
     */
    public function getPaginatedList($offset, $limit, $criteria = array());

    /**
     * Gets the pagination html
     *
     * @return string
     */
    public function getPaginationHtml();

}
