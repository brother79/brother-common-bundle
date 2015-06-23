<?php

namespace Brother\CommonBundle\Model\Entry;

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
    public function findBy(array $criteria)
    {
        return $this->repository->findBy($criteria);
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
}
