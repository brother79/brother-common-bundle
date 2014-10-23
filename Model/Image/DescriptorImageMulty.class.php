<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 21.09.11
 * Time: 17:08
 * To change this template use File | Settings | File Templates.
 */

namespace Brother\CommonBundle\Model\Image;

class DescriptorImageMulty extends DescriptorImage
{

    /**
     * @param sfDoctrineRecord $object
     * @param bool $isNew
     * @return string
     */

    public function getWebDir($object, $isNew = false)
    {
        return $object == null ? ''
            : '/uploads/images/' . $object->getTable()->getTableName() . '/' . $this->getIdPath($object);
    }

    /**
     * get upload imageName for param
     *
     * @param \sfDoctrineRecord $object
     * @param string $name
     * @param bool $isNew
     *
     * @internal param array $options
     * @internal param string $name
     * @internal param \Class $Model $object
     *
     * @internal param array $param - image params
     * @return string
     */

    public function getFileName($object, $name = '', $isNew = false)
    {
        if ($name == '') {
            $t = glob($this->computePath($this->getWebDir($object, $name)) . DIRECTORY_SEPARATOR . '*.*');
            if ($t) {
                foreach ($t as $v) {
                    if (array_search(pathinfo($v, PATHINFO_EXTENSION), $this->exts) !== false && !preg_match('/_(\d+)x(\d+)\.(\w+)$/', $v)) {
                        return pathinfo($v, PATHINFO_FILENAME);
                    }
                }
            }
        } else {
            return svFormUtils::sluggable($name);
        }
        return '';
    }

    /**
     * @param sfDoctrineRecord $object
     * @return string
     */

    public function getDefaultWebPath($object)
    {
        return 'default/' . $object->getTable()->getTableName();
    }

    /**
     * @public
     * @param sfDoctrineRecord $object
     * @param string $name
     * @param array $options* @internal param string $name
     * @return string
     */

    public function getDefaultFileName($object, $name = '', $options = array())
    {
        return $name == '' && isset($options['name']) ? $options['name'] : $name;
    }


}
