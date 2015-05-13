<?php
namespace Brother\CommonBundle\Command;

use Brother\CommonBundle\AppDebug;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixSonataHierarchyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('brother-common:fix-sonata-hierarchy')
            ->setDescription('Иерархия страниц сонаты')
	;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $t = time();
        $output->writeln(__CLASS__ . ' Start');
        $em = $this->getContainer()->get('doctrine')->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $pageRepository = $em->getRepository('AppBundle:Page');
        $pages = $pageRepository->findAll();
        foreach ($pages as $page) {
            /* @var $page \AppBundle\Entity\Page */
            foreach ($pages as $p) {
                /* @var $p \AppBundle\Entity\Page */
                $parent = null;
                $pUrl = preg_replace('|\{[^\}]+\}|', '', $p->getUrl());
                $pUrl = str_replace('//', '/', $pUrl);
                /* @var $parent \AppBundle\Entity\Page */
                if ($page->getId() != $p->getId() &&
                    $page->getSite()->getId() == $p->getSite()->getId() &&
                    $p->getUrl() != '/' &&
                    ($p->getParent() == null || $p->getParent()->getId() != $page->getId()) &&
                    strpos($page->getUrl(), $p->getUrl()) !== false ||
                    strpos($page->getUrl(), $pUrl) !== false

                ) {
                    if ($parent == null || strlen($parent->getUrl() < strlen($p->getUrl()))) {
                        $parent = $p;
                    }
                }
                if ($parent && $parent->getUrl() != '/' && $parent->getParent()->getId() != $page->getId()) {
                    $output->writeln($parent->getUrl() . '(' . $parent->getName() . ') -> ' . $page->getUrl() . '(' . $page->getName() . ')');
                    $page->setParent($parent);
                    $em->persist($page);
                }
            }
        }
        $em->flush();
        $output->writeln(__CLASS__ . ' End');
    }
}