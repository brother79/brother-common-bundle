<?php
/**
 * Created by PhpStorm.
 * User: Andrey
 * Date: 30.05.2018
 * Time: 18:29
 */

namespace Brother\CommonBundle\Command;

use Sonata\NotificationBundle\Backend\BackendInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;


class BaseCommand extends Command
{

    protected static $defaultName = 'base';

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }

            $this->container = $application->getKernel()->getContainer();
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $mode
     *
     * @return BackendInterface
     */
    protected function getNotificationBackend($mode)
    {
        if ($mode == 'async') {
            return $this->getContainer()->get('sonata.notification.backend');
        }

        return $this->getContainer()->get('sonata.notification.backend.runtime');
    }

}