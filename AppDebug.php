<?php
/**
 * Отладочный модуль
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;


//use Doctrine\Bundle\MongoDBBundle\Logger\Logger;
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
     * @var \Doctrine\Bundle\MongoDBBundle\Logger\Logger
     */
    static $logger = null;

    static $request = null;

    static $statistic = [
        'mongo' => ['count' => 0, 'time' => 0, 'mem' => 0]
    ];

    /**
     * C-tor
     *
     */
    public function __construct() {
    }

    /**
     * Print data for debugging using @see print_r() function
     *
     * @param mixed  $data  printed data
     * @param string $title custom title for data
     */
    static function myPrint_r($data, $title = '') {
        echo "<br /><b>" . $title . "</b><br />\n";
        echo '<pre>';
        print_r($data);
        echo "</pre>\n";
    }

    /**
     * Вывод объекта со стеком вызова
     *
     * @param        $object
     * @param string $title
     * @param bool   $debug
     * @param int    $count
     */
    public static function _dx($object, $title = '', $debug = true, $count = 20) {
        self::_d($object, $title, $count, $debug);
        if (self::getEnv() != 'prod') {
            die(0);
        }
    }

    /**
     * Short version of myPrint_r
     *
     * @param mixed  $object
     * @param string $title
     * @param int    $lineCount
     * @param bool   $isEcho
     */
    public static function _d($object, $title = '', $lineCount = 2, $isEcho = true) {
        $s = "<br /><b>" . $title . "</b><br />\n<PRE>" . print_r($object, true) . "</PRE><BR/>";
        $message = print_r($object, true);
        $exception = new Exception("Debug exception " . $title . ': ' . $message);
        if ($lineCount) {
            $trace = $exception->getTrace();
            $count = count($trace);
            for ($i = 0; $i < $lineCount && $i < $count; $i++) {
                if (isset($trace[$i]['file']) && isset($trace[$i]['line'])) {
                    $s .= "file $i: " . $trace[$i]['file'] . "<br/>line: " . $trace[$i]['line'] . "<br/>\n";
                }
            }
        }
        if (self::getEnv() != 'prod' && $isEcho) {
            echo $s;
            self::writeLog($s, false, 'debug');
        } else {
            self::writeLog($s, false, 'debug');
            if (self::getEnv() == 'prod') {
                try {
                    self::createMailAndSend($exception, $_REQUEST);
                } catch (\Exception $e) {
                    self::createMailAndSend('error send message', $_REQUEST);
                }
            }
        }
    }

    /**
     * Вычисляет текущее окружение.
     *
     * @return string
     */
    public static function getEnv() {
        if (self::$container) {
            return self::$container->getParameter('kernel.environment');
        }
        return 'dev';
    }

    /**
     * @param Exception $exception
     */
    public static function createMailAndSend($exception) {
        if (self::$container) {
            $listener = self::$container->get('elao.error_notifier.listener');
            /** @var $listener Notifier */
            $listener->createMailAndSend($exception, self::getRequest(), self::$container);
        }
    }

    /**
     * @return null|Request
     */
    public static function getRequest() {
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
     * @param bool   $isEcho
     * @param string $name
     *
     */
    public static function writeLog($s, $isEcho = false, $name = null) {
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

    static public function getUsername() {
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

    public static function printR($value) {
        if (is_string($value) || is_numeric($value)) {
            return $value;
        }
        if (is_array($value)) {
            $r = '(';
            foreach ($value as $k => $item) {
                if (is_numeric($k)) {
                    $r .= self::printR($item) . ', ';
                }
            }
            $r .= ')';
            return str_replace(', )', ')', $r);
        }
        return print_r($value, true);
    }

    /**
     * Вычисляет путь куда писать лог
     *
     * @param $name
     *
     * @return string
     */
    public static function calcLogName($name) {
        $dir = pathinfo(pathinfo(pathinfo(__DIR__, PATHINFO_DIRNAME), PATHINFO_DIRNAME), PATHINFO_DIRNAME) .
            DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'named';
        @mkdir($dir, 0777, true);
        return $dir . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Удаляет файл с логом
     *
     * @param $name
     */
    public static function removeLog($name) {
        @unlink(self::calcLogName($name));
    }

    /**
     * @param $logger
     */
    public static function setLogger($logger) {
        self::$logger = $logger;
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container) {
        self::$container = $container;
        self::$logger = $container->get('logger');
    }

    /**
     * @param      $name
     * @param null $category
     */
    public static function startWatch($name, $category = null) {
        if (self::$container->has('debug.stopwatch')) {
            $stopwatch = self::$container->get('debug.stopwatch');
            /* @var $stopwatch \Symfony\Component\Stopwatch\Stopwatch */
            $stopwatch->start($name, $category);
        }
    }

    /**
     * @param $name
     */
    public static function stopWatch($name) {
        if (self::$container->has('debug.stopwatch')) {
            $stopwatch = self::$container->get('debug.stopwatch');
            /* @var $stopwatch \Symfony\Component\Stopwatch\Stopwatch */
            $stopwatch->stop($name);
        }
    }

    /**
     * @var \Doctrine\Bundle\MongoDBBundle\DataCollector\PrettyDataCollector
     */
    static $doctrineLogger = null;
    static $kernelDebug = null;

    public static function kernelDebug() {
        if (self::$kernelDebug === null) {
            self::$kernelDebug = self::$container->getParameterBag()->resolveValue('%kernel.debug%') ? true : false;
        }
        return self::$kernelDebug;
    }

    private static function trace($n, $skip = ['AppDebug']) {
        $r = [];
        foreach (debug_backtrace(false, $n) as $item) {
            if (empty($item['class']) || !in_array($item['class'], $skip)) {
                if (isset($item['file']) && isset($item['line'])) {
                    $r[] = $item['file'] . '(' . $item['line'] . ')';
                }
            }
        }
        return $r;
    }

    private static function traceAsString($n, $skip = []) {
        return implode("<br>\n", self::trace($n, $skip));
    }

    public static function mongoLog($log) {

//        self::$statistic['mongo']['start_mem'] = memory_get_usage();
        self::$statistic['mongo']['start_time'] = microtime(true);
        self::$statistic['mongo']['count']++;
        if (self::kernelDebug()) {
            if (self::$doctrineLogger == null) {
                $dataCollectorId = sprintf(
                    'doctrine_mongodb.odm.data_collector.%s',
                    self::$container->getParameterBag()->resolveValue('%kernel.debug%') ? 'pretty' : 'standard');
                self::$doctrineLogger = self::$container->get($dataCollectorId);
            }
            if (self::$doctrineLogger) {
                if (!empty($log['skip'])) {
                    $log['skipNum'] = $log['skip'];
                    $log['skip'] = true;
                } elseif (isset($log['skip'])) {
                    unset($log['skip']);
                }
                self::$doctrineLogger->logQuery($log);
            }
        }
    }

    public static function mongoLogEnd() {
//        self::$statistic['mongo']['mem']+= memory_get_usage() - self::$statistic['mongo']['start_mem'];
        self::$statistic['mongo']['time'] = microtime(true) - self::$statistic['mongo']['start_time'];
        unset(self::$statistic['mongo']['start_time']);
    }

    public static function addTime($name, $time, $dop = null) {
        if (isset(self::$statistic[$name])) {
            self::$statistic[$name]['count']++;
            self::$statistic[$name]['time'] += $time;
        } else {
            self::$statistic[$name]['count'] = 1;
            self::$statistic[$name]['time'] = $time;
        }
        if ($time > 5) {
            self::$statistic[$name]['trace'] = self::traceAsString(15);
        }
        if ($time > 0.1 && $dop) {
            if (isset(self::$statistic[$dop])) {
                self::$statistic[$dop]['count']++;
                self::$statistic[$dop]['time'] += $time;
            } else {
                self::$statistic[$dop]['count'] = 1;
                self::$statistic[$dop]['time'] = $time;
            }
            if ($time > 1) {
                self::$statistic[$dop]['trace'] = self::traceAsString(15);
            }
        }
    }
} 