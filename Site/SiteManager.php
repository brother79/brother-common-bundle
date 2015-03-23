<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 11.03.2015
 * Time: 14:32
 */

namespace Brother\CommonBundle\Site;

use Brother\CommonBundle\AppDebug;
use Sonata\PageBundle\Entity\SiteManager as BaseSiteManager;

class SiteManager extends BaseSiteManager {

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if ($criteria == array('enabled' => 1)) {
            $repository = $this->getRepository();
            /* @var $repository \Doctrine\ORM\EntityRepository */

            return $repository->createQueryBuilder('s')
                ->orderBy('s.isDefault', 'DESC')
                ->andWhere('s.enabled=true')
                ->getQuery()->useResultCache(true, 3600)->execute();
        }
        return parent::findBy($criteria, $orderBy, $limit, $offset); // TODO: Change the autogenerated stub
    }


} 