<?php

namespace Brother\CommonBundle\Route;

use Brother\CommonBundle\AppDebug;
use Brother\CMSBundle\Model\BasePage;
use Brother\CommonBundle\AppTools;
use Brother\CommonBundle\Cache\BrotherCacheProvider;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Exception;
use Sonata\PageBundle\Page\PageServiceManagerInterface;
use Sonata\UserBundle\Model\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * User: Andrey Dashkovskiy
 * Date: 06.07.13
 * Time: 21:19
 */
class AppRouteAction {
    /**
     * @var ContainerInterface
     */
    static $container = null;

    static $params = [];
    static $menuOptions = [];
    static $breadcrumbs = [];
    static $seo = null;
    protected static $options = [];

    public static function getParentRoute(ContainerInterface $container, $routes = []) {
        foreach (self::getBreadcrumbsRoutes($container) as $breadcrumb) {
            /* @var $breadcrumb AppBreadcrumbsItem */
            if (isset($breadcrumb->url['sf_route'])) {
                $routeName = $breadcrumb->url['sf_route'];
                if (in_array($routeName, $routes)) {
                    $params = $breadcrumb->url;
                    unset($params['sf_route']);
                    return $routeName;
                }
            } else {
                AppDebug::_dx($breadcrumb);
            }
        }
        return self::getCurrentRouteName($container);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     * @throws Exception
     */
    public static function getBreadcrumbsRoutes(ContainerInterface $container) {
        $name = self::getRouteName($container);
        if ('homepage' == $name) {
            return [];
        }
        $vv = self::getRouteOption($container, $name, 'breadcrumbs', []);
        array_unshift($vv, 'homepage');
        $result = [];
        $vv = array_merge($vv, self::$breadcrumbs);
        foreach ($vv as $k => $v) {
            $b = new AppBreadcrumbsItem();
            if (is_array($v)) {
                if (!isset($v['sf_route'])) {
                    $v['sf_route'] = $k;
                }
            } else {
                $v = ['sf_route' => $v];
            }
            $b->name = $k;
            $b->routeName = $v['sf_route'];
            if (isset($v['params'])) {
                $params = $v['params'];
                unset($v['params']);
            } else {
                $params = [];
            }
            $b->setParams($params);
            $b->url = self::translate($v, $params);
            $b->title = self::translate(self::getRouteOption($container, $b->routeName, 'title', ''), $params);
            $result[] = $b;
        }
        return $result;
    }

    /**
     * @param ContainerInterface $container
     * @param Route              $route
     *
     * @return mixed|null
     */
    public static function getRouteName(ContainerInterface $container, $route = null) {
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
     * @param $container ContainerInterface
     *
     * @return mixed
     */
    public static function getCurrentRouteName(ContainerInterface $container) {
        $request = $container->get('request');
        /* @var $request Request */
        return $request->get('_route');
    }

    /**
     * @param $container ContainerInterface
     *
     * @return \Symfony\Cmf\Component\Routing\ChainRouter
     */
    public static function getRouter(ContainerInterface $container) {
        return $container->get("router");
    }

    /**
     * @param ContainerInterface                                        $container
     * @param                                                           $route
     *
     * @return Route
     */
    private static function getRoute(ContainerInterface $container, $route = null) {
        if ($route == null) {
            $route = self::getCurrentRouteName($container);
        }
        if (is_string($route)) {
            return self::getRouter($container)->getRouteCollection()->get($route);
        }
        return $route;
    }

    /**
     * @param ContainerInterface $container
     * @param                    $routeName
     * @param                    $optionName
     * @param null               $default
     *
     * @return false|mixed|null
     */
    private static function getRouteOption(ContainerInterface $container, $routeName, $optionName, $default = null) {
        /** @var BrotherCacheProvider $cacheManager */
        $cacheManager = AppRouteAction::getContainer('brother_cache');
        $key = 'route_option:' . $routeName . ':' . $optionName;
        $value = $cacheManager->fetch($key);
        if ($value === false) {
            $value = self::getOption($container, $routeName, $optionName, $default);
            $cacheManager->save($key, $value, 86400);
        }
        return $value;
    }

    /**
     * @param ContainerInterface $container
     * @param Route              $route
     * @param string             $name
     * @param null               $default
     *
     * @return null
     */
    private static function getOption(ContainerInterface $container, $route, $name, $default = null) {
        $r = self::getRoute($container, $route);
        $options = $r ? $r->getOptions() : [];
        $options = isset($options['action']) ? $options['action'] : [];
        if ($route == null) {
            $options = array_merge($options, self::$options);
        }
        return isset($options[$name]) ? $options[$name] : $default;
    }

    // region common

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     * @throws Exception
     */

    public static function translate($value, $params = []) {
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
            if (is_string($result) && preg_match_all('/(?:\{\{|\%\%)([^\}]+)(?:\}\}|\%\%)/', $result, $m)) {

                foreach ($m[1] as $key => $value) {
                    /* @var $value string */
                    if ($value == 'prev') {
                        continue;
                    }
                    if (isset($params[$value]) && !is_array($params[$value])) {
                        $result = str_replace($m[0][$key], $params[$value], $result);
                    } else {
                        $result = str_replace($m[0][$key], '', $result);
                    }
                }
            }
            return $result;
        }
    }

    // endregion common

    // region getters

    /**
     * @param ContainerInterface $container
     * @param array              $routes
     *
     * @return string
     * @throws Exception
     */
    public static function getParentUri(ContainerInterface $container, $routes = []) {
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
     *
     * @return bool
     */
//    public static function getSeo(ContainerInterface $container)
//    {
//        if (self::$seo === null) {
//            AppDebug::_dx(1);
//            self::$seo = array_merge(svActionsList::getInstance()->getSeo(), self::getOption($container, null, 'seo', []));
//            if (isset(self::$seo['keywords']) && !is_array(self::$seo['keywords'])) {
//                self::$seo['keywords'] = array_map('trim', explode(',', self::$seo['keywords']));
//            }
//        }
//        return self::$seo;
//    }

    /**
     * @param ContainerInterface $container
     * @param null               $route
     *
     * @return mixed
     * @throws Exception
     */
    public static function getTitle(ContainerInterface $container, $route = null) {
        return self::translate(self::getRouteOption($container, $route, 'title'));
    }

    /**
     * @param null $route
     *
     * @return mixed
     * @throws Exception
     */
    public static function getTitleExt($route = null) {
//        AppDebug::_dx(self::$params);
        return self::translate(self::getRouteOption(self::$container, $route, 'title_ext'));
    }

    /**
     * @param ContainerInterface $container
     * @param Route              $route
     *
     * @return null
     */
    public static function getMenu(ContainerInterface $container, Route $route) {
        return self::getRouteOption($container, $route, 'menu');
    }

    /**
     * Расчитывает слассы для менюшки
     *
     * @param $page BasePage
     *
     * @return string
     */
    public static function getMenuClass($page) {
        $class = [];
        if ($page->hasChildren()) {
            $class[] = 'has_children';
        }
        $manager = AppRouteAction::getCmsManager();
        $curPage = $manager->getCurrentPage();
        if (self::isPage($page, $curPage) || self::isParent($page, $curPage)) {
            $class[] = 'active';
        }

        return implode(' ', $class);
    }

    /**
     * @return \Sonata\PageBundle\CmsManager\CmsSnapshotManager
     */
    public static function getCmsManager() {
        return self::getCmsManagerSelector()->retrieve();
    }

    private static function getCmsManagerSelector() {
        return self::$container->get('sonata.page.cms_manager_selector');

    }

    // endregion getters

    /**
     * @param $page    BasePage
     * @param $curPage BasePage
     *
     * @return bool
     */
    private static function isPage($page, $curPage) {
        return $page->getUrl() == $curPage->getUrl();
    }

//    public static function addSeoTitle(ContainerInterface $container, $value)
//    {
//        $seo = self::getSeo($container);
//        self::$seo['title'] = (isset($seo['title']) ? $seo['title'] : '') . $value;
//    }
//
//    public static function setSeoTitle(ContainerInterface $container, $value)
//    {
//        self::getSeo($container);
//        self::$seo['title'] = $value;
//    }

//    public static function setSeoDescription(ContainerInterface $container, $value)
//    {
//        self::getSeo($container);
//        self::$seo['description'] = $value;
//    }

//    public static function addSeoDescription(ContainerInterface $container, $value)
//    {
//        $seo = self::getSeo($container);
//        self::$seo['description'] = (isset($seo['description']) ? $seo['description'] : '') . $value;
//    }


//    public static function addSeoKeywords(ContainerInterface $container, $value)
//    {
//        $seo = self::getSeo($container);
//        $old = isset($seo['keywords']) ? $seo['keywords'] : [];
//        if (!is_array($old)) {
//            $old = explode(',', $old);
//        }
//        if (!is_array($value)) {
//            $value = explode(',', $value);
//        }
//        self::$seo['keywords'] = array_merge($value, $old);
//    }

    /**
     * @param $parent  BasePage
     * @param $curPage BasePage
     *
     * @return bool
     */
    private static function isParent($parent, $curPage) {
        if (strpos($curPage->getUrl(), $parent->getUrl()) === 0) {
            return true;
        }
        foreach ($curPage->getParents() as $page) {
            if (self::isPage($page, $parent)) {
                return true;
            }
        }
        return false;
    }

    public static function addParams($params) {
        if ($params) {
            self::$params = array_merge(self::$params, $params);
        }
    }

    /*    public static function hasList($key)
        {
            $options = self::getMenuOptions($key);
            return $options && count($options);
        }
    */

    public static function addBreadcrumb($route, $value = [], $params = []) {
        $value['sf_route'] = $route;
        if (count($params) > 0) {
            $value['params'] = $params;
        }
        self::$breadcrumbs[] = $value;
    }

    public static function setTitle($title) {
        self::setOption('title', $title);
    }

    private static function setOption($name, $value) {
        self::$options[$name] = $value;
    }

    public static function updateSeo() {
        if (self::$container) {
            $page = self::getCmsManager()->getCurrentPage();
            /* @var $page \Sonata\PageBundle\Model\SnapshotPageProxy|BasePage */

            $seoPage = self::getSeoPage();
            if ($page && $page->getTitle()) {
                $title = self::translate($page->getTitle() ?: $page->getName());
                $seoPage->setTitle($title);
                $seoPage->addMeta('property', 'og:title', $title);
            }

            if ($page && $page->getMetaDescription()) {
                $seoPage->addMeta('name', 'description', self::translate($page->getMetaDescription()));
            }

//            AppDebug::_dx(array(self::translate($page->getMetaDescription()), $page->getMetaDescription(), self::$params), 'debug seo description1: ');

            if ($page && $page->getMetaKeyword()) {
                $seoPage->addMeta('name', 'keywords', self::translate($page->getMetaKeyword()));
            }

            if (!empty(self::$params['image_url'])) {
                $seoPage->addMeta('property', 'og:image', self::translate(self::$params['image_url']));
            }

            if ($page && $page->getSite()->getTitle()) {
                $seoPage->addMeta('property', 'og:site_name', self::translate($page->getSite()->getTitle()));
            }

        }
    }

    /**
     * @return \Sonata\SeoBundle\Seo\SeoPage
     */
    protected static function getSeoPage() {
        return self::$container->get('sonata.seo.page.default');
    }

    /**
     * @param string|null $name
     *
     * @return object|ContainerInterface|null
     */
    public static function getContainer(?string $name = null) {
        if ($name) {
            return self::$container ? self::$container->get($name) : null;
        }
        return self::$container;
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer($container) {
        if ($container) {
            self::$container = $container;
            if (!AppDebug::$container) {
                AppDebug::setContainer($container);
            }
            if (!AppTools::$container) {
                AppTools::setContainer($container);
            }
        } else {
            AppDebug::_dx(2);
        }
    }

    public static function getUserId() {
        $user = self::getUser();
        return $user ? $user->getId() : null;
    }

    /**
     * @return User
     */
    public static function getUser() {
//        AppDebug::_dx([self::$container == null, AppRouteAction::getContainer() == null, AppTools::getContainer() == null]);
        if (!self::$container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = self::$container->get('security.token_storage')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

    /**
     * @return null|EntityManager
     */
    public static function getEntityManager() {
        if (self::$container) {
            return self::$container->get('doctrine.orm.entity_manager');
        }
        return null;
    }

    public static function timeLineStart($name) {
        if ($timeLine = self::getTimeLine()) {
            $timeLine->start($name, 'user');
        }
    }

    public static function getTimeLine() {
        try {
            if (self::$container->has('debug.stopwatch')) {
                return self::$container->get('debug.stopwatch');
                /* @var $stopwatch \Symfony\Component\Stopwatch\Stopwatch */
            }
        } catch (ServiceNotFoundException $e) {
            return null;

        }
    }

    public static function timeLineStop($name) {
        if ($timeLine = self::getTimeLine()) {
            $timeLine->stop($name, 'user');
        }
    }

    public static function clear() {
        self::$container = null;
        AppDebug::$container = null;
        AppTools::$container = null;
    }

    /**
     * @return DocumentManager
     */
    public static function getDocumentManager() {
        return self::getContainer('doctrine_mongodb')->getManager();
    }

    /**
     * @return PageServiceManagerInterface
     */
    protected function getPageServiceManager() {
        return self::$container->get('sonata.page.page_service_manager');
    }


}
