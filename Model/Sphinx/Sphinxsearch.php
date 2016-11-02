<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 06.01.2016
 * Time: 18:27
 */

namespace Brother\CommonBundle\Model\Sphinx;


use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Logger\SphinxLogger;
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

    /**
     * @var SphinxLogger
     */
    protected $logger;

    /**
     */
    function __construct() {
        $this->sphinx = AppRouteAction::$container->get('iakumai.sphinxsearch.search');
        $this->logger = AppRouteAction::$container->get('brother_common.sphinx_logger');

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

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    function __call($name, $arguments) {
        if ($name != 'ResetFilters' && $name != 'setFilter' && $name != 'search' && $name != 'SetLimits'
            && $name != 'SetSortMode' && $name != 'setFilterBetweenDates' && $name != 'SetMatchMode'
            && $name != 'escapeString' && $name != 'setFilterRange'
        ) {
            AppDebug::_dx($arguments, $name);
        }
        $time = microtime(true);
        $result = call_user_func_array(array($this->sphinx, $name), $arguments);
        $td = round((microtime(true) - $time) * 1000, 3);
        $this->logger->logCommand(json_encode($arguments), $td, $name, $td > 200);
        AppDebug::addTime($td > 1000 ? 'sphinx ' . json_encode($arguments) : 'sphinx', microtime(true) - $time);
        return $result;
    }


} 