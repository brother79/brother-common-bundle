<?php
namespace Brother\CommonBundle\Route;
use Brother\CommonBundle\AppDebug;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * User: Andrey Dashkovskiy
 * Date: 06.07.13
 * Time: 21:19
 */

class AppRouteAction
{

    static $params = array();
    static $menuOptions = array();
    static $breadcrumbs = array();
    static $seo = null;
    protected static $options = array();

    /**
     * @param $container ContainerInterface
     * @return \Symfony\Cmf\Component\Routing\ChainRouter
     */
    public static function getRouter(ContainerInterface $container)
    {
        return $container->get("router");
    }

    /**
     * @param $container ContainerInterface
     * @return mixed
     */
    public static function getCurrentRouteName(ContainerInterface $container)
    {
        $request = $container->get('request');
        /* @var $request Request */
        return $request->get('_route');
    }

    public static function getParentUri(ContainerInterface $container, $routes = array())
    {
        foreach (self::getBreadcrumbsRoutes($container) as $breadcrumb) {
            /* @var $breadcrumb AppBreadcrumbsItem */
            if (isset($breadcrumb->url['sf_route'])) {
                $routeName = $breadcrumb->url['sf_route'];
                if (in_array($routeName, $routes)) {
                    $params = $breadcrumb->url;
                    unset($params['sf_route']);
                    return AppRouteAction::getRouter($container)->generate($routeName, $params);
                }
            } else {
                AppDebug::_dx($breadcrumb);
            }
        }
        $request = $container->get('request');
        /* @var $request Request */
        return $request->getRequestUri();
    }

    /**
     * @param ContainerInterface $container
     * @param \Symfony\Component\Routing\Route $route
     * @return mixed|null
     */
    public static function getRouteName(ContainerInterface $container, $route = null)
    {
        if ($route == null) {
            return self::getCurrentRouteName($container);
        }
        if (is_object($route)) {
            AppDebug::_dx(array_keys(self::getRouter($container)->getRouteCollection()->getIterator()));
            return array_search($route, sfContext::getInstance()->getRouting()->getRoutes());
        }
        return $route;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param $route
     * @return \Symfony\Component\Routing\Route
     */
    private static function getRoute(ContainerInterface $container, $route = null)
    {
        if ($route == null) {
            $route = self::getCurrentRouteName($container);
        }
        if (is_string($route)) {
            return self::getRouter($container)->getRouteCollection()->get($route);
        }
        return $route;
    }

    // region common
    /**
     * @param mixed $value
     * @param array $params
     *
     * @throws Exception
     * @internal param \sfDoctrineRecord $object
     * @return mixed
     */

    public static function translate($value, $params = array())
    {
        if (is_object($value)) {
            throw new Exception("Type of value must be string or array ");
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::translate($v, $params);
            }
            return $value;
        } else {
            $result = $value;
            $params = array_merge(self::$params, $params);
            if (is_string($result) && preg_match_all('/\%\%([^\%]+)\%\%/', $result, $m)) {
                foreach ($m[1] as $key => $value) {
                    /* @var $value string */
                    if ($value == 'prev') {
                        continue;
                    }
                    if (isset($params[$value])) {
                        $result = str_replace($m[0][$key], $params[$value], $result);
                    }
                }
            }
            return $result;
        }
    }

    // endregion common

    // region getters
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Symfony\Component\Routing\Route $route
     * @param string $name
     * @param null $default
     * @return null
     */
    private static function getOption(ContainerInterface $container, $route, $name, $default = null)
    {
        $r = self::getRoute($container, $route);
        $options = $r ? $r->getOptions() : array();
        $options = isset($options['action']) ? $options['action'] : array();
        if ($route == null) {
            $options = array_merge($options, self::$options);
        }
        return isset($options[$name]) ? $options[$name] : $default;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return bool
     */
    public static function getSeo(ContainerInterface $container)
    {
        if (self::$seo === null) {
            self::$seo = array_merge(svActionsList::getInstance()->getSeo(), self::getOption($container, null, 'seo', array()));
            if (isset(self::$seo['keywords']) && !is_array(self::$seo['keywords'])) {
                self::$seo['keywords'] = array_map('trim', explode(',', self::$seo['keywords']));
            }
        }
        return self::$seo;
    }

    /**
     * @param ContainerInterface $container
     * @param \Symfony\Component\Routing\Route $route
     * @return mixed
     */
    public static function getTitle(ContainerInterface $container, Route $route = null)
    {
        return self::translate(self::getOption($container, $route, 'title'));
    }

    /**
     * @param ContainerInterface $container
     * @param null $route
     * @return mixed
     */
    public static function getTitleExt(ContainerInterface $container, $route = null)
    {
        return self::translate(self::getOption($container, $route, 'title_ext'));
    }

    /**
     * @param ContainerInterface $container
     * @param \Symfony\Component\Routing\Route $route
     * @return null
     */
    public static function getMenu(ContainerInterface $container, Route $route)
    {
        return self::getOption($container, $route, 'menu');
    }

    /**
     * @param ContainerInterface $container
     * @param \Symfony\Component\Routing\Route $route
     * @param string $name
     * @param string $nameSpace
     * @return string
     */
    public static function getClass(ContainerInterface $container, Route $route, $name = '', $nameSpace = '')
    {
        $select = sfContext::getInstance()->getRouting()->getCurrentRouteName();
        if ($route == null) {
            $route = self::getRoute($container, $name);
        }
        if ($name == null) {
            $name = self::getRouteName($container, $route);
        }
        $class = self::getOption($container, $route, 'class');
        if ($select == $name) {
            $class .= ' select';
        }
        if ($nameSpace) {
            $menuOptions = self::getMenuOptions($nameSpace);
            $item = reset($menuOptions);
            if ($item['sf_route'] == $name) {
                $class .= ' first';
            }
            $item = end($menuOptions);
            if ($item['sf_route'] == $name) {
                $class .= ' last';
            }
        }
        return trim($class);
    }

    /**
     * @param $menuName
     * @param $menu
     * @param $routeName
     * @param $route
     * @return array|null
     */
    private static function getMenuItemOptions($menuName, $menu, $routeName, $route)
    {
        $params = array();
        $route_params = array('sf_route' => $routeName);
        $order = '';
        $name = '';
        if (is_string($menu)) {
            $name = $menu;
        } else if (is_array($menu)) {
            if (isset($menu[$menuName])) {
                $name = $menuName;
                if (is_array($menu[$menuName])) {
                    if (isset($menu[$menuName]['params'])) {
                        $route_params = array_merge($route_params, $menu[$menuName]['params']);
                    }
                    $params = $menu[$menuName];
                } else {
                    $order = $menu[$name];
                }
            }
        }
        if ($name == $menuName) {
            return array_merge(
                array('order' => $order, 'sf_route' => $routeName, 'route_params' => $route_params, 'route' => $route),
                $params
            );
        }
        return null;
    }

    public static function getMenuOptions($menu)
    {
        if (!isset(self::$menuOptions[$menu])) {
            $r = array();
            foreach (sfContext::getInstance()->getRouting()->getRoutes() as $name => $route) {
                /* @var $route \Symfony\Component\Routing\Route */
                if ($menuOptions = self::getMenuItemOptions($menu, self::getMenu($route), $name, $route)) {
                    $r[$name] = $menuOptions;
                }
            }
            uasort($r, function ($ma, $mb) {
                return $ma['order'] == $mb['order'] ? 0 : ($ma['order'] > $mb['order'] ? 1 : -1);
            });
            self::$menuOptions[$menu] = $r;
        }
        return self::$menuOptions[$menu];
    }

    public static function addParams($params)
    {
        if ($params) {
            self::$params = array_merge(self::$params, $params);
        }
    }

    /**
     * @param ContainerInterface $container
     * @return array
     */
    public static function getBreadcrumbsRoutes(ContainerInterface $container)
    {
        $name = self::getRouteName($container);
        if ('homepage' == $name) {
            return array();
        }
        $route = self::getRoute($container, $name);
        $vv = self::getOption($container, $route, 'breadcrumbs', array());
        array_unshift($vv, 'homepage');
        $result = array();
        $vv = array_merge($vv, self::$breadcrumbs);
        foreach ($vv as $k => $v) {
            $b = new AppBreadcrumbsItem();
            if (is_array($v)) {
                if (!isset($v['sf_route'])) {
                    $v['sf_route'] = $k;
                }
            } else {
                $v = array('sf_route' => $v);
            }
            $b->name = $k;
            $b->route = self::getRoute($container, $v['sf_route']);
            if (isset($v['params'])) {
                $params = $v['params'];
                unset($v['params']);
            } else {
                $params = array();
            }
            $b->setParams($params);
            $b->url = self::translate($v, $params);
            $b->title = self::translate(self::getOption($container, $b->route, 'title'), $params);
            $result[] = $b;
        }
        return $result;
    }

    // endregion getters

    public static function include_bottom(ContainerInterface $container, $route = null)
    {
        foreach (self::getOption($container, $route, 'bottom_components', array()) as $value) {
            $t = explode('/', $value);
            include_component($t[0], $t[1]);
        }
    }

    public static function getInformersList(ContainerInterface $container, $route, $value = false)
    {
        if ($value) {
            $informers = self::getOption($container, $route, 'informers', array());
            $result = isset($informers[$value]) ? $informers[$value] : svActionsList::getInstance()->getDefaultInformers($value);
            return is_array($result) ? $result : array_map('trim', explode(',', $result));
        } else {
            return array_merge(
                self::getInformersList($route, 'left'),
                self::getInformersList($route, 'right'),
                self::getInformersList($route, 'bottom')
            );
        }

    }

    public static function addBreadcrumb($route, $value = array(), $params = array())
    {
        $value['sf_route'] = $route;
        if (count($params) > 0) {
            $value['params'] = $params;
        }
        self::$breadcrumbs[] = $value;
    }

    public static function addSeoTitle(ContainerInterface $container, $value)
    {
        $seo = self::getSeo($container);
        self::$seo['title'] = (isset($seo['title']) ? $seo['title'] : '') . $value;
    }

    public static function setSeoTitle(ContainerInterface $container, $value)
    {
        self::getSeo($container);
        self::$seo['title'] = $value;
    }

    public static function setSeoDescription(ContainerInterface $container, $value)
    {
        self::getSeo($container);
        self::$seo['description'] = $value;
    }

    public static function addSeoDescription(ContainerInterface $container, $value)
    {
        $seo = self::getSeo($container);
        self::$seo['description'] = (isset($seo['description']) ? $seo['description'] : '') . $value;
    }


    public static function addSeoKeywords(ContainerInterface $container, $value)
    {
        $seo = self::getSeo($container);
        $old = isset($seo['keywords']) ? $seo['keywords'] : array();
        if (!is_array($old)) {
            $old = explode(',', $old);
        }
        if (!is_array($value)) {
            $value = explode(',', $value);
        }
        self::$seo['keywords'] = array_merge($value, $old);
    }

    public static function setTitle($title)
    {
        self::setOption('title', $title);
    }

    private static function setOption($name, $value)
    {
        self::$options[$name] = $value;
    }

    public static function hasList($key)
    {
        $options = self::getMenuOptions($key);
        return $options && count($options);
    }


}
