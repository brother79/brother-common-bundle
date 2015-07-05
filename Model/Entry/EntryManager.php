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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for the page manager.
 */
abstract class EntryManager implements EntryManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface 	$dispatcher
     * @param string                                              			$class
     * @param boolean                                              			$autoPublish
     */
    public function __construct(EventDispatcherInterface $dispatcher, $class)
    {
        $this->dispatcher = $dispatcher;
        $this->class = $class;
    }

    /**
     * Returns the fully qualified page class name
     *
     * @return string
     **/
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Finds a page entry by given id
     *
     * @param  string $id
	 *
     * @return EntryInterface
     */
    public function findOneById($id)
    {
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * Creates an empty Entry instance
     *
     * @param integer $id
	 *
     * @return EntryInterface
     */
    public function createEntry($id = null)
    {
        $class = $this->getClass();
        $entry = new $class;
        /* @var $entry EntryInterface */
        if (null !== $id) {
            $entry->setId($id);
        }

        return $entry;
    }

    /**
     * Persists a page entry.
     *
     * @param EntryInterface $entry
     *
     * @return boolean
     */
    public function save(EntryInterface $entry)
    {
        $this->doSave($entry);

        return true;
    }

    /**
     * Removes a page entry.
     *
     * @param EntryInterface $entry
     *
     * @return boolean
     */
    public function remove(EntryInterface $entry)
    {
        $this->doRemove($entry);

        return true;
    }

    /**
     * Deletes a list of page entries
     *
     * @param array $ids
     *
     * @return boolean
     */
    public function delete(array $ids)
    {

        $this->doDelete($ids);

        return true;
    }

    /**
     * Performs the persistence of the page entry.
     *
     * @param EntryInterface $entry
     */
    abstract protected function doSave(EntryInterface $entry);

    /**
     * Performs the removal of the entry.
     *
     * @param EntryInterface $entry
     */
    abstract protected function doRemove(EntryInterface $entry);
	
    /**
     * Performs the removal of a list of page entries.
     *
     * @param array $ids
     */
    abstract protected function doDelete($ids);



}
