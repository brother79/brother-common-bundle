<?php

namespace Brother\CommonBundle;

use Brother\CommonBundle\Error\ErrorLogger;
use Monolog\ErrorHandler;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BrotherCommonBundle extends Bundle
{
    public function boot()
    {
        parent::boot();
//        $logger = $this->container->get('logger');
//        \Monolog\ErrorHandler::register($logger);
    }

}
