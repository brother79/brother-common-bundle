<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 10.01.2015
 * Time: 15:48
 */

namespace Brother\CommonBundle\Model;



use Sonata\PageBundle\Request\RequestFactory;
use Symfony\Component\BrowserKit\Request as DomRequest;

class SonataTestClient extends \Symfony\Bundle\FrameworkBundle\Client {
    protected function filterRequest(DomRequest $request)
    {
        $httpRequest = RequestFactory::create(
            'host_with_path', $request->getUri(),
            $request->getMethod(), $request->getParameters(),
            $request->getCookies(),
            $request->getFiles(), $request->getServer(), $request->getContent());
        foreach ($this->filterFiles($httpRequest->files->all()) as $key => $value) {
            $httpRequest->files->set($key, $value);
        }
        return $httpRequest;
    }

} 