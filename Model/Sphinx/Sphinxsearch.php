<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 06.01.2016
 * Time: 18:27
 */

namespace Brother\CommonBundle\Model\Sphinx;


use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Route\AppRouteAction;

class Sphinxsearch {

    /**
     * @var Sphinxsearch
     */
    static $instance = null;

    /**
     * @var \IAkumaI\SphinxsearchBundle\Search\Sphinxsearch $sphinx
     */
    protected $sphinx;

    static $statistic = array();

    /**
     */
    function __construct() {
        $this->sphinx = AppRouteAction::$container->get('iakumai.sphinxsearch.search');

    }

    /**
     * @return Sphinxsearch
     */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Sphinxsearch();
        }
        return self::$instance;
    }

    function __call($name, $arguments) {
        if ($name != 'ResetFilters' && $name != 'setFilter' && $name != 'search' && $name != 'SetLimits'
            && $name != 'SetSortMode' && $name != 'setFilterBetweenDates' && $name != 'SetMatchMode'
            && $name != 'escapeString' && $name != 'setFilterRange'
        ) {
            AppDebug::_dx($arguments, $name);
        }
        $time = microtime(true);
        $result = call_user_func_array(array($this->sphinx, $name), $arguments);
        $td = round((microtime(true) - $time)*1000, 3);
        self::$statistic[$name][] = $td;
        self::$statistic[$name]['time'] = empty(self::$statistic[$name]['time']) ? $td : self::$statistic[$name]['time'] + $td;
        self::$statistic[$name]['count'] = empty(self::$statistic[$name]['count']) ? 1 : self::$statistic[$name]['count'] + 1;
        return $result;
    }


} 