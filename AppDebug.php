<?php
/**
 * Created by PhpStorm.
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;


use Doctrine\Bundle\MongoDBBundle\Logger\Logger;
use Elao\ErrorNotifierBundle\Listener\Notifier;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AppDebug
{

    static public $log = array();

    static $username = false;

    /**
     * @var ContainerInterface
     */
    static $container = null;

    /**
     * @var Logger
     */
    static $logger = null;

    static $request = null;

    /**
     * C-tor
     *
     */
    public function __construct()
    {
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
     * @param $object
     * @param string $title
     * @param bool $debug
     * @param int $count
     */
    public static function _dx($object, $title = '', $debug = true, $count = 15)
    {
        self::_d($object, $title, $count, $debug);
        if (self::getEnv() != 'prod') {
            die(0);
        }
    }

    /**
     * Short version of myPrint_r
     *
     * @param mixed $object
     * @param string $title
     * @param int $lineCount
     * @param bool $isEcho
     */
    public static function _d($object, $title = '', $lineCount = 2, $isEcho = true)
    {
        $s = "<br /><b>" . $title . "</b><br />\n<PRE>" . print_r($object, true) . "</PRE><BR/>";
        $message = print_r($object, true);
//        $message = str_replace("\n", "<br/>\n", $message);
        $exception = new Exception("Debug exception " . $title . ': ' . $message);
        if ($lineCount) {
            $trace = $exception->getTrace();
//            $trace = debug_backtrace(false, $lineCount);
            $count = count($trace);
            for ($i = 0; $i < $lineCount && $i < $count; $i++) {
                if (isset($trace[$i]['file']) && isset($trace[$i]['line'])) {
                    $s .= "file $i: " . $trace[$i]['file'] . "<br/>line: " . $trace[$i]['line'] . "<br/>\n";
                }
            }
        }
        if (self::getEnv() != 'prod' && $isEcho) {
            echo $s;
        } else {
            if (self::getEnv() == 'prod') {
                self::createMailAndSend($exception, $_REQUEST);
            }
            self::writeLog($s, false, 'debug');
        }
    }

    public static function getEnv()
    {
        if (self::$container) {
            return self::$container->getParameter('kernel.environment');
        }
        return 'dev';
    }

    /**
     * @param $exception
     */
    public static function createMailAndSend($exception)
    {
        if (self::$container) {
            $listener = self::$container->get('elao.error_notifier.listener');
            /** @var $listener Notifier */
            $listener->createMailAndSend($exception, self::getRequest(), self::$container);
        }
    }

    /**
     * @return null|Request
     */
    public static function getRequest()
    {
        if (self::$request == null) {
            self::$request = Request::createFromGlobals();
            self::$request->setSession(self::$container->get('session'));
        }
        return self::$request;
    }

    /**
     * WriteLog on screen and log file
     *
     * @param string $s
     * @param bool $isEcho
     * @param string $name
     *
     */
    public static function writeLog($s, $isEcho = true, $name = null)
    {
        if (is_array($s) || is_object($s)) {
            $s = print_r($s, true);
        }
        $s .= "; user: " . self::getUsername() . "; mem: " . memory_get_usage();
        if ($isEcho) {
            echo strftime('%Y-%m-%d %H:%M:%S') . ": " . $s . "<br/>\n";
        }
        if ($name == null) {
            if ($log = self::$logger) {
                $log->info($s);
            }
        } else {
            $dir = pathinfo(pathinfo(pathinfo(pathinfo(pathinfo(__DIR__, PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME) .
            DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'named';
            @mkdir($dir, 0777, true);
            file_put_contents(self::calcLogName($name),
                strftime('%Y-%m-%d %H:%M:%S') . ": " . $s . "<br/>\n",
                FILE_APPEND);
        }
    }

    /**
     * Enter description here...
     *
     * @return string
     */

    static public function getUsername()
    {
        if (self::$container == null || !self::$container->has('security.context')) {
            return null;
        }

        if (null === $token = self::$container->get('security.context')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    public static function calcLogName($name)
    {
        $dir = pathinfo(pathinfo(pathinfo(pathinfo(pathinfo(__DIR__, PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME) .
            DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'named';
        @mkdir($dir, 0777, true);
        return $dir . DIRECTORY_SEPARATOR . $name;
    }

    public static function removeLog($name)
    {
        @unlink(self::calcLogName($name));
    }

    /**
     * @param $logger
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer($container)
    {
        self::$container = $container;
        self::$logger = $container->get('logger');
    }

} 