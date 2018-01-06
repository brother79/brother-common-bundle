<?php
namespace Brother\CommonBundle\DataCollector;

use Brother\CommonBundle\Logger\SphinxLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * SphinxDataCollector
 */
class SphinxDataCollector extends DataCollector{
    /**
     * @var SphinxLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param SphinxLogger $logger
     */
    public function __construct(SphinxLogger $logger=null) {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null) {
        $this->data = [
            'commands' => null !== $this->logger ? $this->logger->getCommands() : [],
        ];
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array
     */
    public function getCommands() {
        return $this->data['commands'];
    }

    /**
     * Returns the number of collected commands.
     *
     * @return integer
     */
    public function getCommandCount() {
        return count($this->data['commands']);
    }

    /**
     * Returns the number of failed commands.
     *
     * @return integer
     */
    public function getErroredCommandsCount() {
        return count(array_filter($this->data['commands'], function ($command) {
            return $command['error'] !== false;
        }));
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     *
     * @return float
     */
    public function getTime() {
        $time = 0;
        foreach ($this->data['commands'] as $command) {
            $time += $command['executionMS'];
    }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'sphinx';
    }
}