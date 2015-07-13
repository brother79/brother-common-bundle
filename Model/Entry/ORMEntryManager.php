<?php

namespace Brother\CommonBundle\Model\Entry;

use Brother\CommonBundle\Event\EntryEvent;
use Brother\CommonBundle\Event\Events;
use Brother\CommonBundle\Pager\PagerInterface;
use Brother\GuestbookBundle\Event\EntryDeleteEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default ORM EntryManager.
 */
class ORMEntryManager extends EntryManager
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var \Brother\CommonBundle\Pager\DefaultORM
     */
    protected $pager = null;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     */
    public function __construct(EventDispatcherInterface $dispatcher, EntityManager $em, $class)
    {
        parent::__construct($dispatcher, $em->getClassMetadata($class)->name);

        $this->em = $em;
        $this->repository = $em->getRepository($class);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, array $orderBy=null, $limit = null, $offset = null)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function isNew(EntryInterface $entry)
    {
        return !$this->em->getUnitOfWork()->isInIdentityMap($entry);
    }

    public function findByNames($names)
    {
        return $this->findBy(array('name' => $names));
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave(EntryInterface $entry)
    {
        $this->em->persist($entry);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function doRemove(EntryInterface $entry)
    {
        $this->em->remove($entry);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($ids)
    {
        $this->em->createQueryBuilder()
            ->delete($this->getClass(), 'c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
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

        $event = new EntryEvent($entry);
        $this->dispatcher->dispatch(Events::ENTRY_CREATE, $event);

        return $entry;
    }

    /**
     * Set pager
     *
     * @param PagerInterface $pager
     **/
    public function setPager(PagerInterface $pager)
    {
        $this->pager = $pager;
    }

    /**
     * Get the pagination html
     *
     * @return string
     */
    public function getPaginationHtml()
    {
        $html = '';
        if(null !== $this->pager)
        {
            $html = $this->pager->getHtml();
        }

        return $html;
    }

    /**
     * Returns the fully qualified quest class name
     *
     * @return string
     **/
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Deletes a list of quest entries
     *
     * @param array $ids
     *
     * @return boolean
     */
    public function delete(array $ids)
    {
        $event = new EntryDeleteEvent($ids);
        $this->dispatcher->dispatch(Events::ENTRY_PRE_DELETE, $event);

        if ($event->isPropagationStopped()) {
            return false;
        }

        $this->doDelete($ids);

        $this->dispatcher->dispatch(Events::ENTRY_POST_DELETE, $event);

        return true;
    }

    /**
     * Finds entries by the given criteria
     * and from the query offset.
     *
     * @param integer $offset
     * @param integer $limit
     * @param array $criteria
     *
     * @return array of EntryInterface
     */
    public function getPaginatedList($offset, $limit, $criteria = array())
    {
        $queryBuilder = $this->repository->createQueryBuilder('c');

        if (null === $this->pager) {
            return $queryBuilder->getQuery()->getResult();
        }

        return $this->pager->getList($queryBuilder, $offset, $limit);    }
}
