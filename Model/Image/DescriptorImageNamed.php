<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 26.09.2014
 * Time: 13:36
 */

namespace Brother\CommonBundle\Model\Image;


use Brother\CommonBundle\AppDebug;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class DescriptorImageNamed
 * @package Brother\CommonBundle\Model\Image
 */
class DescriptorImageNamed extends DescriptorImageBase
{

    /**
     * Вычисление дополнительного пути к файлу
     * @param $name
     * @return mixed
     */
    protected function getFileNameDir($name = '')
    {
        return '';
    }

    /**
     * Имя файла для загрузки
     * @abstract
     * @param string $name
     * @internal param $options
     * @return string
     */

    protected function getFileName($name = '')
    {
        $id = $this->getId();
        return ($name && $id) ? $id . '_' . $name : $id . $name;
    }
}