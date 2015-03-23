<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 11.03.2015
 * Time: 13:40
 */

namespace Brother\CommonBundle\Site;

use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\AppTools;
use Sonata\PageBundle\Site\HostSiteSelector as BaseHostSiteSelector;
use Symfony\Component\HttpFoundation\Request;

class HostSiteSelector extends BaseHostSiteSelector  {

    protected function getSites(Request $request)
    {
        return $this->siteManager->findBy(array('enabled' => true), array('isDefault' => 'DESC'));
    }


    public function retrieve()
    {
        if ($this->site == null) {
            $sites = $this->getSites(AppDebug::getRequest());
            $this->site = reset($sites);
        }
        return parent::retrieve();
    }

} 