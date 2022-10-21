<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 26.09.2014
 * Time: 13:36
 */

namespace Brother\CommonBundle\Model\Image;


/**
 * Class DescriptorImageNamed
 * @package Brother\CommonBundle\Model\Image
 */
class DescriptorImageNamed extends DescriptorImageBase
{

    /**
     * Вычисление дополнительного пути к файлу
     *
     * @param $name
     *
     * @return mixed
     */
    protected function getFileNameDir(string $name = ''): string
    {
        return '';
    }

    /**
     * Имя файла для загрузки
     * @abstract
     *
     * @param string $name
     *
     * @return string
     * @internal param $options
     */
    protected function getFileName(string $name = ''): string
    {
        $id = $this->getId();
        return ($name && $id) ? $id . '_' . $name : $id . $name;
    }
}