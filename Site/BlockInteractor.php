<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Site;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Sonata\BlockBundle\Model\BlockManagerInterface;
use Sonata\PageBundle\Model\BlockInteractorInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Entity\BlockInteractor as BaseBlockInteractor;

/**
 * This class interacts with blocks
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class BlockInteractor extends BaseBlockInteractor
{

    /**
     * {@inheritdoc}
     */
    public function getBlock($id)
    {
        $blocks = $this->getEntityManager()->createQueryBuilder()
            ->select('b')
            ->from($this->blockManager->getClass(), 'b')
            ->where('b.id = :id')
            ->setParameters(array(
              'id' => $id
            ))
            ->getQuery()
            ->useResultCache(true, 300)
            ->execute();

        return count($blocks) > 0 ? $blocks[0] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlocksById(PageInterface $page)
    {
        $blocks = $this->getEntityManager()
            ->createQuery(sprintf('SELECT b FROM %s b INDEX BY b.id WHERE b.page = :page ORDER BY b.position ASC', $this->blockManager->getClass()))
            ->setParameters(array(
                 'page' => $page->getId()
            ))
            ->useResultCache(true, 300)
            ->execute();

        return $blocks;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->blockManager->getClass());
    }

}
