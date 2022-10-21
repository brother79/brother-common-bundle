<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Андрей
 * Date: 21.09.11
 * Time: 15:04
 * To change this template use File | Settings | File Templates.
 */

/**
 * Class DescriptorImageSingle
 */
class DescriptorImageSingle extends DescriptorImage
{

	/**
	 * @param sfDoctrineRecord $object
	 * @param bool $isNew
	 * @return string
	 */
	public function getWebDir($object, $isNew = false)
	{
		return $object == null ? '' : '/uploads/images/' . Doctrine_Core::getTable(get_class($object))->tableName;
	}

	/**
	 * get upload imageName for param
	 *
	 * @param $object
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
		$id = $this->getId($object);
		return $id == '' ? '0' : $id . ($name ? '_' . $name : '');
	}

	/**
	 * @param sfDoctrineRecord $object
	 * @return string
	 */
	public function getDefaultWebPath($object)
	{
		return 'default';
	}

	/**
	 * @public
	 * @param sfDoctrineRecord $object
	 * @param string $name
     * @param array $options* @internal param string $name
	 * @return string
	 */
	public function getDefaultFileName($object, $name='', $options = [])
	{
		return Doctrine_Core::getTable(get_class($object))->tableName;
	}

}
