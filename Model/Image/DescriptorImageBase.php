<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 26.09.2014
 * Time: 13:35
 */

namespace Brother\CommonBundle\Model\Image;

use Brother\CommonBundle\AppDebug;

abstract class DescriptorImageBase
{
    protected $exts = array('jpg', 'gif', 'png', 'bmp');

    protected static $mimeMap = array(
        'bmp' => 'image/bmp',
        'bmp2' => 'image/bmp',
        'bmp3' => 'image/bmp',
        'cur' => 'image/x-win-bitmap',
        'dcx' => 'image/dcx',
        'epdf' => 'application/pdf',
        'epi' => 'application/postscript',
        'eps' => 'application/postscript',
        'eps2' => 'application/postscript',
        'eps3' => 'application/postscript',
        'epsf' => 'application/postscript',
        'epsi' => 'application/postscript',
        'ept' => 'application/postscript',
        'ept2' => 'application/postscript',
        'ept3' => 'application/postscript',
        'fax' => 'image/g3fax',
        'fits' => 'image/x-fits',
        'g3' => 'image/g3fax',
        'gif' => 'image/gif',
        'gif87' => 'image/gif',
        'icb' => 'application/x-icb',
        'ico' => 'image/x-win-bitmap',
        'icon' => 'image/x-win-bitmap',
        'jng' => 'image/jng',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'm2v' => 'video/mpeg2',
        'miff' => 'application/x-mif',
        'mng' => 'video/mng',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'otb' => 'image/x-otb',
        'p7' => 'image/x-xv',
        'palm' => 'image/x-palm',
        'pbm' => 'image/pbm',
        'pcd' => 'image/pcd',
        'pcds' => 'image/pcd',
        'pcl' => 'application/pcl',
        'pct' => 'image/pict',
        'pcx' => 'image/x-pcx',
        'pdb' => 'application/vnd.palm',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-pgm',
        'picon' => 'image/xpm',
        'pict' => 'image/pict',
        'pjpeg' => 'image/pjpeg',
        'png' => 'image/png',
        'png24' => 'image/png',
        'png32' => 'image/png',
    );

    /**
     * @var array $_options Template options
     */
    protected $_options = array();

    /**
     * __construct
     *
     * @param array $options
     *
     * @internal param array $array
     * @return \Brother\CommonBundle\Model\Image\DescriptorImageBase
     */
    public function __construct(array $options = array())
    {
        $rootDir = __DIR__;
        for ($i = 0; $i < 7; $i++) {
            $rootDir = substr($rootDir, 0, strrpos($rootDir, DIRECTORY_SEPARATOR));
        }
        $this->_options['sf_root_dir'] = $rootDir;
        $this->_options['sf_web_dir'] = $rootDir . DIRECTORY_SEPARATOR . 'web';
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Имя каталога для загрузки вебовское
     * @abstract
     * @param bool $isNew
     * @return string
     */

    function getWebDir()
    {
        return $this->getOption('web_dir');
    }

    /**
     * Имя файла для загрузки
     * @abstract
     * @param string $name
     * @internal param $options
     * @return string
     */

    function getFileName($name = '')
    {
        $id = $this->getId();
        return ($name && $id) ? $id . '_' . $name : $id . $name;
    }

    /**
     * @abstract
     * @param $name
     * @param array $options * @internal param string $name
     * @return string
     */

    function getImageUrl($name = '', $options = array(), $default = '')
    {
        if ($name == '' && isset($options['name'])) {
            $name = $options['name'];
        }
        $result = $this->getWebDir() . '/' . $this->getFileName($name);
        $ext = $this->getImageExt($result);
        if ($ext) {
            return $result . '.' . $ext;
        } else {
            return empty($this->_options['default'][$name]) ? $default : $this->_options['default'][$name];
        }
    }

    /**
     * @return string
     */

    public function getId()
    {
        return $this->getOption('id');
    }

    /**
     *
     * Split id for path
     *
     * @param int $len
     * @return string
     */

    public function getIdPath($len = 3)
    {
        $id = $this->getId();
        $l = $id ? strlen($id) : 1;
        return implode('/', str_split(str_pad($id, $l % $len > 0 ? $l + $len - ($l % $len) : $l, '0', STR_PAD_LEFT), $len));
    }

    public function getOption($name, $default = null)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

    /**
     * @param string $filename
     * @return string
     */

    public function getImageExt($filename)
    {
        $filename = self::computePath($filename);
        foreach ((array)glob($filename . '.*') as $fn) {
            if (($result = pathinfo($fn, PATHINFO_EXTENSION)) != '') {
                return $result;
            }
        }
        return '';
    }

    /**
     * @param string $filename
     * @return string
     */

    public function computePath($filename)
    {
        if (substr($filename, 0, 1) == '/') {
            $result = $this->getOption('sf_web_dir') . $filename;
        } else {
            $result = $this->getOption('sf_web_dir') . '/images/' . $filename;
        }
        return str_replace('/', DIRECTORY_SEPARATOR, $result);
    }

    public function getMimeType($ext)
    {
        $ext = strtolower($ext);
        return isset(self::$mimeMap[$ext]) ? self::$mimeMap[$ext] : '';
    }

    public function uploadFileFromContent($name, $content, $ext)
    {
        if ($content == '') {
            return false;
        }
        $dir = $this->computePath($this->getWebDir());
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . $this->getFileName($name) . '.' . $ext, $content);
        return true;
    }
} 