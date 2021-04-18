<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Logger;

use Psr\Log\LoggerInterface;

/**
 * SphinxLogger
 */
class SphinxLogger
{
    protected $logger;
    protected $nbCommands = 0;
    protected $commands = [];
    protected $start;

    /**
     * Constructor.
     *
     * @param null $logger A LoggerInterface instance
     */
    public function __construct($logger = null)
    {
        if (!$logger instanceof LoggerInterface && null !== $logger) {
            throw new \InvalidArgumentException(sprintf('SphinxLogger needs either the HttpKernel LoggerInterface or PSR-3 LoggerInterface, "%s" was injected instead.', is_object($logger) ? get_class($logger) : gettype($logger)));
        }

        $this->logger = $logger;
    }

    /**
     * Logs a command
     *
     * @param string      $command    Sphinx command
     * @param float       $duration   Duration in milliseconds
     * @param string      $connection Connection alias
     * @param bool|string $error      Error message or false if command was successful
     */
    public function logCommand($command, $duration, $connection, $error = false)
    {
        ++$this->nbCommands;

        if (null !== $this->logger) {
            $this->commands[] = ['cmd' => $command, 'executionMS' => $duration, 'conn' => $connection, 'error' => $error];
            if ($error) {
                $message = 'Command "' . $command . '" failed (' . $error . ')';

                if ($this->logger instanceof LoggerInterface) {
                    // Symfony 2.2+
                    $this->logger->error($message);
                } else {
                    // Symfony 2.1
                    $this->logger->err($message);
                }
            } else {
                $this->logger->info('Executing command "' . $command . '"');
            }
        }
    }

    /**
     * Returns the number of logged commands.
     *
     * @return integer
     */
    public function getNbCommands()
    {
        return $this->nbCommands;
    }

    /**
     * Returns an array of the logged commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
