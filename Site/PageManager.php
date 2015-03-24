<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 11.03.2015
 * Time: 14:32
 */

namespace Brother\CommonBundle\Site;

use Brother\CommonBundle\AppDebug;
use Doctrine\ORM\NoResultException;
use Sonata\PageBundle\Entity\PageManager as BasePageManager;

class PageManager extends BasePageManager
{

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $c = $criteria;
        $query = $this->getRepository()->createQueryBuilder('p');
//            ->orderBy('s.isDefault', 'DESC')
//            ->andWhere('s.enabled=true')
//            ->getQuery()->useResultCache(true, 3600)->execute();

        foreach ($c as $k => $v) {
            switch($k) {
                case 'url':
                    $query->andWhere('p.url=:url')->setParameter('url', $v);
                    unset($c[$k]);
                    break;
                case 'site':
                    $query->andWhere('p.site=:site')->setParameter('site', $v);
                    unset($c[$k]);
                    break;
                case 'routeName':
                    $query->andWhere('p.routeName=:routeName')->setParameter('routeName', $v);
                    unset($c[$k]);
                    break;
                default: AppDebug::_dx($c, $k);
            }
        }
        if ($orderBy) {
            AppDebug::_dx($orderBy);
        }

        if (count($c) || $orderBy) {
            return parent::findOneBy($criteria, $orderBy);
        } else {
            try {
                return $query->getQuery()->useResultCache(true, 300)->getSingleResult();
            } catch (NoResultException $e){
                return null;
            }
        }
    }


    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        AppDebug::_dx($criteria);
        if ($criteria == array('enabled' => 1)) {
            $repository = $this->getRepository();
            /* @var $repository \Doctrine\ORM\EntityRepository */
            return $repository->createQueryBuilder('s')
                ->orderBy('s.isDefault', 'DESC')
                ->andWhere('s.enabled=true')
                ->getQuery()->useResultCache(true, 300)->execute();
        }
        return parent::findBy($criteria, $orderBy, $limit, $offset); // TODO: Change the autogenerated stub
    }


} 