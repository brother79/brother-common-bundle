<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 03.07.2015
 * Time: 10:36
 */

namespace Brother\CommonBundle\Site;

use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Route\AppRouteAction;
use Sonata\PageBundle\Entity\BasePage as SonataBasePage;

abstract class BasePage extends SonataBasePage {

    /**
     *
     */
    public function getMenuClass()
    {
        return AppRouteAction::getMenuClass($this);
    }

    public function getMenuTitle()
    {
        AppDebug::_dx($this);
        return $this->title? $this->title : $this->slug;
    }

    public function hasChildren()
    {
        return count($this->children);
    }
} 