<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MainBundle\Block;

use Brother\CommonBundle\AppDebug;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\FormatterBundle\Block\FormatterBlockService as BaseFormatterBlockService;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class FormatterBlockService extends BaseFormatterBlockService
{
    protected function getCkEditorToolbarIcons()
    {
        return array(array(
            'Bold', 'Italic', 'RemoveFormat', 'Subscript', 'Superscript',
            '-', 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord',
            '-', 'Undo', 'Redo',
            '-', 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent',
            '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock',
            '-', 'Image', 'Link', 'Unlink', 'Table', 'HorizontalRule'),
            array('Maximize', 'Source', 'Scayt', 'Format'),
        );
    }

    protected function getSonataFormatterTypeOptions()
    {
        return function (FormBuilderInterface $formBuilder) {
            return array(
                'event_dispatcher' => $formBuilder->getEventDispatcher(),
                'format_field' => array('format', '[format]'),
                'source_field' => array('rawContent', '[rawContent]'),
                'target_field' => '[content]',
                'ckeditor_toolbar_icons' => $this->getCkEditorToolbarIcons()
            );
        };
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', 'sonata_type_immutable_array', array(
            'keys' => array(
                array('content', 'sonata_formatter_type', $this->getSonataFormatterTypeOptions()),
            )
        ));
    }

}
