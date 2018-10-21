<?php
namespace Brother\CommonBundle\DataCollector;

use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Logger\SphinxLogger;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector as BaseDoctrineDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SphinxDataCollector
 */
class DoctrineDataCollector extends BaseDoctrineDataCollector{



    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null) {
        parent::collect($request, $response, $exception);
//        AppDebug::_d(array_keys($this->data));
//        $this->data = [
//            'commands' => null !== $this->logger ? $this->logger->getCommands() : [],
//        ];
    }


}