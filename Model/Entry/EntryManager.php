<?php

namespace Brother\CommonBundle\Model\Entry;

use Knp\Component\Pager\PaginatorInterface;
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
     * @var PaginatorInterface|\Knp\Component\Pager\Paginator
     */
    protected $paginator = null;

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
     * Returns the fully qualified page class name
     *
     * @return string
     **/
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Persists a page entry.
     *
     * @param EntryInterface $entry
     *
     * @return boolean
     */
    public function save($entry, $andFlush = true)
    {
        $this->doSave($entry, $andFlush);

        return true;
    }

    /**
     * Performs the persistence of the page entry.
     *
     * @param EntryInterface $entry
     */
    abstract protected function doSave($entry, $andFlush = true);

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
     * Performs the removal of the entry.
     *
     * @param EntryInterface $entry
     */
    abstract protected function doRemove(EntryInterface $entry);

    /**
     * Deletes a list of page entries
     *
     * @param array $ids
     *
     * @return boolean
     */
    public function delete($ids, $andFlush = true)
    {

        $this->doDelete($ids);

        return true;
    }
	
    /**
     * Performs the removal of a list of page entries.
     *
     * @param array $ids
     */
    abstract protected function doDelete($ids);

    /**
     * Set
     *
     * @param PaginatorInterface $paginator
     **/
    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Создаёт выборку для пэйджера
     * @param $perPage
     * @param array $params
     * @throws \Exception
     * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
     */
    public function makePagination($perPage, $params=array())
    {
        if ($this->paginator instanceof \Knp\Component\Pager\Paginator) {
            return $this->makeKnpPagination($perPage, $params);
        }
        throw new \Exception('Paginator unknown');
    }

    /**
     * KNP пагинация
     * @param $perPage
     * @param array $params
     * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination|\Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function makeKnpPagination($perPage, $params=array())
    {
        $page = empty($params['page']) ? 1 : $params['page'];
        if (empty($params['target'])) {
            $target = $this->createKnpTarget();
        } else {
            $target = $params['target'];
        }
        if (isset($params['subscriber'])) {
            $this->paginator->subscribe($params['subscriber']);
        }
        $pagination = $this->paginator->paginate($target, $page, $perPage);
        /* @var $pagination \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination */
        if (isset($params['route'])) {
            $pagination->setUsedRoute($params['route']);
        }
        $customParameters = array('parameters' => array());
        if (isset($params['parameters'])) {
            $customParameters['parameters'] = $params['parameters'];
        }
        if (isset($params['ajax'])) {
            $customParameters['ajax'] = true;
        }
        $pagination->setCustomParameters($customParameters);
        /* @var $pagination \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination */
        return $pagination;
    }

    /**
     * Создаёт соллекцию или кверю для KNP пагинации
     * @return array
     */
    protected function createKnpTarget()
    {
        return array();
    }
}
