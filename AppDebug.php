<?php
/**
 * Created by PhpStorm.
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;


use Elao\ErrorNotifierBundle\Listener\Notifier;
use Exception;

class AppDebug {
    static public $log = array();

    static $username = false;

    /**
     * C-tor
     *
     */
    public function __construct()
    {
    }

    /**
     * Enter description here...
     *
     * @return string
     */

    static public function getUsername()
    {
        if (self::$username === false) {
            try {
                self::$username = (string)sfContext::getInstance()->getUser()->getCurrentUser();
            } catch (sfException $e) {
                self::$username = '';
            }
        }
        return self::$username;
    }

    static public function getLog($name = 'batch')
    {
        if (empty(self::$log[$name])) {
            self::$log[$name] = new sfFileLogger(new sfEventDispatcher(), array('file' => sfConfig::get('sf_log_dir') . DIRECTORY_SEPARATOR . $name . '.log'));
        }
        return self::$log[$name];
    }

    /**
     * Print data for debugging using @see print_r() function
     *
     * @param mixed $data printed data
     * @param string $title custom title for data
     */
    static function myPrint_r($data, $title = '')
    {
        echo "<br /><b>" . $title . "</b><br />\n";
        echo '<pre>';
        print_r($data);
        echo "</pre>\n";
    }

    /**
     * Short version of myPrint_r
     *
     * @param Object $object
     * @param string $title
     * @param int $lineCount
     * @param bool $isEcho
     */
    public static function _d($object, $title = '', $lineCount = 1, $isEcho=true)
    {
        $s = "<br /><b>" . $title . "</b><br />\n<PRE>" . print_r($object, true) . "</PRE><BR/>";
        $exception = new Exception("Debug exception " . $title . ' ' . print_r($object, true));
        if ($lineCount) {
            ini_set('memory_limit', '4048M');
            $trace = $exception->getTrace();
//            $trace = debug_backtrace(false, $lineCount);
            $count = count($trace);
            for ($i = 0; $i < $lineCount && $i < $count; $i++) {
                if (isset($trace[$i]['file']) && isset($trace[$i]['line'])) {
                    $s .= "file $i: " . $trace[$i]['file'] . "<br/>line: " . $trace[$i]['line'] . "<br/>\n";
                }
            }
        }
        if (defined('DEV') && $isEcho) {
            echo $s;
        } else {
            self::createMailAndSend($exception, $_REQUEST);
            self::writeLog($s, false, 'debug');
        }
    }

    public static function _dx($object, $title = '', $debug = true, $count = 15)
    {
        self::_d($object, $title, $count);
        if (defined('DEV')) {
            die(0);
        }
    }

    /**
     * WriteLog on screen and log file
     *
     * @param string $s
     * @param bool $isEcho
     * @param string $name
     *
     */
    public static function writeLog($s, $isEcho = true, $name = 'batch')
    {
        $name .= date('_Y_m_d');
        if (is_array($s) || is_object($s)) {
            $s = print_r($s, true);
        }
        $s .= "; user: " . self::getUsername() . "; mem: " . memory_get_usage();
        if ($isEcho) {
            echo strftime('%Y-%m-%d %H:%M:%S') . ": " . $s . "<br/>\n";
        }
        if ($log = self::getLog($name)) {
            /* @var $log sfFileLogger */
            $log->log($s, sfFileLogger::INFO);
        }
    }

    public static function  logRun()
    {
        $t = explode('.', microtime(true));
        $f = sfConfig::get('sf_cache_dir') . '/1/' . substr($t[0], 0, 6);
        @mkdir($f, 0777, true);
        file_put_contents($f . '/' . substr($t[0], 6) . '_' . $t[1] . '.txt', $_SERVER['QUERY_STRING']);

    }

    public static function createMailAndSend()
    {
        # todo
    }

} 