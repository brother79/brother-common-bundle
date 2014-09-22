<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 12.06.14
 * Time: 16:45
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\MongoDB;


use Brother\CommonBundle\AppDebug;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{

    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof MongoCollection) {
            $collection = clone $event->target;
            /* @var $collection \Brother\CommonBundle\MongoDB\MongoCollection */

            $collection->setLimit($event->getLimit());
            $collection->setOffset($event->getOffset());
            /* Array([filterFieldParameterName] => filterField, [filterValueParameterName] => filterValue)*/
            if (!empty($_GET[$event->options['sortFieldParameterName']])) {
                $direction = isset($_GET[$event->options['sortDirectionParameterName']]) ? $_GET[$event->options['sortDirectionParameterName']] : 'asc';
                $collection->setSort(array($_GET[$event->options['sortFieldParameterName']] => ($direction == 'desc' || $direction == -1 ? -1 : 1)));
            }
            $event->count = $collection->getCount();
            $event->items = $collection->getItems();
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}