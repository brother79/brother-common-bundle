<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 12.06.14
 * Time: 16:45
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\Model\Sphinx;


use Brother\CommonBundle\AppDebug;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 0]
        ];
    }

    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof SphinxCollection) {
            $collection = clone $event->target;
            /* @var $collection \Brother\CommonBundle\Model\Sphinx\SphinxCollection */
            $collection->setLimit($event->getLimit());
            $collection->setOffset($event->getOffset());
            /* Array([filterFieldParameterName] => filterField, [filterValueParameterName] => filterValue)*/
            $event->count = $collection->getCount();
            $event->items = $collection->getItems();
            $event->stopPropagation();
        }
    }
}
