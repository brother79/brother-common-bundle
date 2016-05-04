<?php

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
     * @param array $orderBy
     *
     * @return EntryInterface
     */
    public function findOneBy(array $criteria, array $orderBy = NULL);

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
     * @param bool           $andFlush
     *
     * @return
     */
    public function save($entry, $andFlush = true);

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
    public function delete($ids, $andFlush = true);


}
