<?php
/**
 * Отладочный модуль
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;


//use Doctrine\Bundle\MongoDBBundle\Logger\Logger;
use Brother\ErrorNotifierBundle\Listener\Notifier;
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
        $ss = gettype($object);
        $ss = print_r(dump($object), true);
        $s = "<br /><b>" . $title . "</b><br />\n<PRE>" . $ss . "</PRE><BR/>";
        $message = print_r($object, true);

//        $message = gettype($object);

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
            $listener = self::$container->get('brother.error_notifier.listener');
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
                DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'named';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
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
            DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'named';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir . DIRECTORY_SEPARATOR . preg_replace('/\W/', '_', $name);
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
        if ($container->has('logger')) {
            self::$logger = $container->get('logger');
        }
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

    public static function trace($n, $skip = ['AppDebug'], $trace = null) {
        $r = [];
        $trace = $trace ?: debug_backtrace(false, $n);
        foreach ($trace as $item) {
            if (empty($item['class']) || !in_array($item['class'], $skip)) {
                if (isset($item['file']) && isset($item['line'])) {
                    if (strpos($item['file'], 'AppDebug') === false) {
                        $f = '';
                        if (isset($item['class'])) {
                            $f .= $item['class'];
                        }
                        if (isset($item['type'])) {
                            $f .= $item['type'];
                        }
                        if (isset($item['function'])) {
                            $f .= $item['function'] . '(';
                        }
                        if (isset($item['args'])) {
                            $args = [];
                            foreach ($item['args'] as $arg) {
                                if (is_numeric($arg)) {
                                    $args[] = $arg;
                                } elseif (is_string($arg) || is_numeric($arg)) {
                                    $args[] = "'" . mb_substr($arg, 0, 20, 'utf-8') . "'";
                                } elseif (is_object($arg)) {
                                    if ($arg instanceof \Model) {
                                        $args[] = get_class($arg) . '(' . mb_substr(print_r($arg->getProperties(), true), 0, 50, 'utf-8') . ')';
                                    } else {
                                        $args[] = get_class($arg);
                                    }
                                } elseif (is_array($arg)) {
                                    $args[] = 'array(...)';
//                                $args[] = '[' . mb_substr(print_r(array_slice($arg, 0, 4), true), 0, 50, 'utf-8') . ']';
                                } else {
                                    $args[] = mb_substr(print_r($arg, true), 0, 50, 'utf-8');
                                }
                            }
                            $f .= implode(', ', $args);
                        }
                        if (isset($item['function'])) {
                            $f .= ')';
                        }
                        $r[] = $f;

                        $s = $item['file'] . '(' . $item['line'] . ')';
                        foreach ($skip as $item1) {
                            if (strpos($s, $item1) !== false) {
                                $s = null;
                                break;
                            }
                        }
                        if ($s) {
                            $r[] = $s;
                        }
                    }
                }
            }
        }
        return $r;
    }

    public static function traceAsString($n, $skip = [], $trace = null) {
        return implode("<br>\n", self::trace($n, $skip, $trace));
    }

    /**
     * @param       $n
     * @param array $skip
     *
     * @param null  $trace
     *
     * @param null  $sourceLines
     *
     * @return string
     */
    public static function traceAsStringWithCode($n, $skip = [], $trace = null, $sourceLines = null) {
        $r = [];
        static $files = [];
        $trace = self::trace($n, $skip, $trace);
        foreach ($trace as $k => $item) {
            $r[] = '<b>' . $item . '</b>';
            if (preg_match('/^(.*)\((\d+)\)$/', $item, $m)) {
                if ($k < 20) {
                    if (file_exists($m[1])) {
                        if (empty($files[$m[1]])) {
                            $files[$m[1]] = file($m[1]);
                        }
                        $r[] = '<pre>';
                        $f = $files[$m[1]];
                        if (preg_match('/\/\* .*\.html\.twig \*\//', $f[2])) {
                            $r[] = rtrim(htmlspecialchars($f[2]));
                            $line = 0;
                            for ($t = 0; $t < $m[2]; $t++) {
                                if ($t < count($f) && preg_match('/\s+\/\/ line (\d+)/', $f[$t], $m2)) {
                                    $line = $f[$t];
                                }
                            }
                            $r[] = $line;
                        }
                        if ($sourceLines) {
                            $start = $m[2] - 1 - (int)($sourceLines / 2);
                            $end = $start + $sourceLines - 1;
                        } else {
                            $start = $m[2] - 4;
                            $end = $m[2] + 2;
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            if (isset($f[$i])) {
                                if ($i + 1 == $m[2]) {
                                    $r[] = '<b>[' . ($i + 1) . ']' . rtrim(htmlspecialchars($f[$i])) . '</b>';
                                } else {
                                    $r[] = '[' . ($i + 1) . ']' . rtrim(htmlspecialchars($f[$i]));
                                }
                            }
                            $r[] = '</pre>';

                        }
//                    } else {
//                        print_r($m);
//                        die();
                    }
                }
            }
        }
        return implode("<br>\n", $r);
    }

    /**
     * @param $log
     */
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

    /**
     *
     */
    public static function mongoLogEnd() {
//        self::$statistic['mongo']['mem']+= memory_get_usage() - self::$statistic['mongo']['start_mem'];
        self::$statistic['mongo']['time'] = microtime(true) - self::$statistic['mongo']['start_time'];
        unset(self::$statistic['mongo']['start_time']);
    }

    /**
     * @param      $name
     * @param      $time
     * @param null $dop
     */
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

    /**
     * @param     $v
     * @param int $level
     *
     * @return array|string
     */
    public static function print_r_safe($v, $level = 10) {
        if ($level == 0) {
            return '...';
        }
        if (is_object($v)) {
            $type = get_class($v);
            return ['type' => $type, 'attributes' => self::print_r_safe(get_object_vars($v), $level)];
        }
        if (is_array($v)) {
            $r = [];
            foreach ($v as $k => $item) {
                $r[$k] = self::print_r_safe($item, $level - 1);
            }
            return $r;
        }
    }


}