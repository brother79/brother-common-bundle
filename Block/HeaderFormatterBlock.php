<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 18.05.2015
 * Time: 13:40
 */

namespace MainBundle\Block;


use Brother\CommonBundle\AppDebug;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class HeaderFormatterBlock extends FormatterBlockService
{

    public function getName()
    {
        return 'Header Rich Text Area';
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', 'sonata_type_immutable_array', array(
            'keys' => array(
                array('title', 'text', array('required' => false)),
                array('content', 'sonata_formatter_type', $this->getSonataFormatterTypeOptions()),
            )
        ));
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse('MainBundle:Block:block_formatter.html.twig', array(
            'block'     => $blockContext->getBlock(),
            'settings'  => $blockContext->getSettings()
        ), $response);

    }

    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'title'      => '<b>Введите заголовок блока</b>',
            'format'     => 'richhtml',
            'rawContent' => '<b>Insert your custom content here</b>',
            'content'    => '<b>Insert your custom content here</b>',
            'template'   => 'MainBundle:Block:block_formatter.html.twig'
        ));
    }
} 