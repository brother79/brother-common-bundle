<?php

namespace Brother\CommonBundle\Model\Entry;

use Brother\CommonBundle\Event\EntryDeleteEvent;
use Brother\CommonBundle\Event\EntryEvent;
use Brother\CommonBundle\Event\Events;
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
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
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
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
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
     * Returns the fully qualified quest class name
     *
     * @return string
     **/
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get the pagination html
     *
     * @return string
     */
    public function getPaginationHtml()
    {
        $html = '';
        if (null !== $this->paginator) {
            $html = $this->paginator->getHtml();
        }

        return $html;
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
     * @return array|\Doctrine\ORM\Query
     */
    protected function createKnpTarget()
    {
        return $this->getRepository()->createQueryBuilder('t')->getQuery();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }


}
