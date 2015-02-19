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

class AppDebug {

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
     * @param mixed $object
     * @param string $title
     * @param int $lineCount
     * @param bool $isEcho
     */
    public static function _d($object, $title = '', $lineCount = 2, $isEcho=true)
    {
        $s = "<br /><b>" . $title . "</b><br />\n<PRE>" . print_r($object, true) . "</PRE><BR/>";
        $message = print_r($object, true);
//        $message = str_replace("\n", "<br/>\n", $message);
        $exception = new Exception("Debug exception " . $title . $message);
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
            self::createMailAndSend($exception, $_REQUEST);
            self::writeLog($s, false, 'debug');
        }
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
     * WriteLog on screen and log file
     *
     * @param string $s
     * @param bool $isEcho
     * @param string $name
     *
     */
    public static function writeLog($s, $isEcho = true, $name = 'batch')
    {
//        $name .= date('_Y_m_d');
        if (is_array($s) || is_object($s)) {
            $s = print_r($s, true);
        }
        $s .= "; user: " . self::getUsername() . "; mem: " . memory_get_usage();
        if ($isEcho) {
            echo strftime('%Y-%m-%d %H:%M:%S') . ": " . $s . "<br/>\n";
        }
        if ($log = self::$logger) {
            $log->info($s);
        }
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
     * @param $logger
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
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
     * @param ContainerInterface $container
     */
    public static function setContainer($container)
    {
        self::$container = $container;
        self::$logger = $container->get('logger');
    }

    public static function getEnv()
    {
        if (self::$container) {
            return self::$container->getParameter('kernel.environment');
        }
        return 'dev';
    }

} 