<?php
/**
 * Created by PhpStorm.
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;


use AppCache;
use AppKernel;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use MongoDate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;

class AppTools
{
    /**
     * @var ContainerInterface
     */
    static $container = null;

    public static function setContainer($container)
    {
        self::$container = $container;
    }

    public static function shtirlic($s)
    {
        $r = array(
            $s,
            iconv('cp1251', 'utf-8', $s),
            iconv('cp1252', 'utf-8', $s),
            iconv('cp1253', 'utf-8', $s),
            iconv('cp1254', 'utf-8', $s),
            iconv('cp1255', 'utf-8', $s),
            iconv('cp1251', 'utf-8', iconv('cp1252', 'cp1251', $s)),
            iconv('cp1251', 'utf-8', iconv('cp1251', 'cp1252', $s)),
            iconv('cp1251', 'cp1252', $s)
        );
        AppDebug::_dx($r, 'тест');
    }

    /**
     * @return null|DocumentManager
     */
    private static function getDocumentManager()
    {
        if (self::$container) {
            return self::$container->get('doctrine_mongodb')->getManager();
        }
        return null;
    }

    /**
     * @return null|EntityManager
     */
    public static function getEntityManager()
    {
        if (self::$container) {
            return self::$container->get('doctrine.orm.entity_manager');
        }
        return null;
    }


    /**
     * Function for redirect.
     *
     * Function redirect browser to another URL and exit current flow.
     *
     * @param string $url redirect to this URL.
     */
    public static function redirect($url)
    {
        header("Location: $url");

        exit();
    }

    /**
     * GetIp
     *
     * @return string
     */

    static public function getIp()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * Read url
     *
     * @param string $url - url
     * @param string $metod - metod post or get
     * @param array $options - options for curl
     * @return string
     */
    static protected function readUrlCommon($url, $metod = 'get', $options, $params = array())
    {
//        svDebug::_dx($options);
        $t = explode('?', $url);
        if (is_array($params)) {
            $params = http_build_query($params, '=', '&');
        }
        if ($metod == 'post') {
            $params = (isset($t[1])) ? ($params ? $params . '&' : '') . $t[1] : $params;
        }
        $ch = curl_init();
        $str = array(
            "Accept-Language: ru, en-us,en;q=0.5",
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Keep-Alive: 300",
            "Connection: keep-alive"
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $str);

        curl_setopt($ch, CURLOPT_URL, $url);

//        AppDebug::_dx($params);
        if ($metod == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (isset($options[CURLOPT_POSTFIELDS])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $options[CURLOPT_POSTFIELDS]);
                unset($options[CURLOPT_POSTFIELDS]);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
            if ($params) {
                $url .= '?' . $params;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!isset($options[CURLOPT_TIMEOUT])) {
            $options[CURLOPT_TIMEOUT] = 90;
        }

        foreach ($options as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $res = curl_exec($ch);
        //$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $res;
    }

    /**
     * Return Header
     *
     * @param String $url
     * @param String $metod - get or post
     * @return String
     */

    static function readHeader($url, $metod = 'get')
    {
        return self::readUrlCommon($url, $metod, array(CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1));

//		return  self::readUrlCommon($url, $metod, array(CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1,
//			CURLOPT_SSL_VERIFYPEER => FALSE, CURLOPT_AUTOREFERER => TRUE, CURLOPT_FAILONERROR => 1,
//			CURLOPT_BINARYTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_MAXREDIRS => 4, CURLOPT_FILETIME => TRUE,
//			CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 20,
//			CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506)",
//			CURLOPT_FRESH_CONNECT => TRUE));
    }

    /**
     * Return content from Url
     *
     * @param String $url
     * @param String $metod - get or post
     * @param array $options
     * @return String
     */

    static function readUrl($url, $metod = 'get', $options = array(), $params = array())
    {
        $options[CURLOPT_HEADER] = 0;
        $options[CURLOPT_NOBODY] = 0;
        return self::readUrlCommon($url, $metod, $options, $params);
    }

    /**
     * Read url fast
     *
     * @param string $url
     * @param string $metod
     * @param array $params
     * @return string
     */

    static function readUrlFast($url, $method = 'get', $options = array(), $params = array())
    {
        $userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $o = array(CURLOPT_HEADER => 0, CURLOPT_NOBODY => 0,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        );
        foreach ($options as $k => $v) {
            $o[$k] = $v;
        }
        return self::readUrlCommon($url, $method, $o, $params);
    }


    /**
     * Enter description here...
     *
     * @param string $url
     * @param string $metod
     * @return string
     */

    static function readUrlAndHeader($url, $metod = 'get')
    {
        return self::readUrlCommon($url, $metod, array(CURLOPT_HEADER => 1, CURLOPT_NOBODY => 0));
    }

    /**
     * Convert Header to Array
     *
     * @param String $header
     * @return Array
     */

    static function normHeader($header)
    {
        $header = explode("\n", $header);
        $result = array();
        foreach ($header as $value) {
            $t = array_map('trim', explode(':', $value));
            $result[$t[0]] = $t[1];
        }
        return $result;
    }

    /**
     * Find pagerank from http://gogolev.net/tools/webmaster/enter.php?q=
     *
     * @param string $url
     * @return mixed
     */

    static function findPr1($url)
    {
        $s = self::readUrl("http://gogolev.net/tools/webmaster/enter.php?q=" . $url);
        if (preg_match('/ pr: (\d+)/', $s, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Find pagerank from http://4seo.biz/tools/29
     *
     * @param string $url
     * @return mixed
     */

    static function findPr2($url)
    {
        //http://www.pageranktool.net/google_pr.php?url=<page>&query=Query
        //http://4seo.biz/tools/29/

        if (strpos($url, 'http://') === false) {
            $url = 'http://' . $url;
        }

        $s = self::readUrl("http://4seo.biz/tools/29/?url=" . $url, 'post');
        $s = preg_replace('/<img[^>]*>/', '', $s);
        $s = preg_replace('/\s*<a[^>]*>\s*(.*?)\s*<\/a>/', '$1', $s);

        if (preg_match_all('/<td>(.*?)<\/td>/', $s, $matches)) {
            if (isset($matches[1][1])) {
                return $matches[1][1];
            }
        }
        return '';
    }

    static function serialize($array)
    {
        $r = array();
        foreach ($array as $key => $value) {
            $r[] = $key . '=' . $value;
        }
        return implode('&', $r);
    }

    /**
     * Convert to Time stamp
     *
     * @param mixed $value
     * @param bool $isDate
     * @return integer
     */

    static function getTimeStamp($value, $isDate = false)
    {
//        if (is_object($value) && get_class($value) == 'sfOutputEscaperArrayDecorator') {
//            $value = sfOutputEscaperArrayDecorator::unescape($value);
//        }
        if (is_array($value)) {
            $result = mktime(
                isset($value['hour']) ? $value['hour'] : 0,
                isset($value['min']) ? $value['min'] : 0,
                isset($value['sek']) ? $value['sek'] : 0,
                $value['month'],
                $value['day'],
                $value['year']
            );
        } elseif (is_string($value)) {
            $result = strtotime($value);
        } else {
            $result = $value;
        }

        return $isDate ? $result - ($result + 25200) % 86400 : $result;
    }

    static function getDate($time)
    {
        return date("Y-m-d H:i:s", $time);
    }

    static function getTime($time, $null = false)
    {
        if ($time == 0) {
            return $null === false ? '00:00:00' : $null;
        }
        $t = array();
        for ($i = 0; $i < 3; $i++) {
            $tt = $time % 60;
            array_unshift($t, $tt < 10 ? '0' . $tt : $tt);
            $time = ($time - $tt) / 60;
        }
        return implode(':', $t);
    }

    /**
     * For XmlToArray
     *
     * @param mixed $arrObjData
     * @param array $arrSkipIndices
     * @return array
     */

    static function objectsIntoArray($arrObjData, $arrSkipIndices = array())
    {
        $arrData = array();
        // if input is object, convert into array
        if (is_object($arrObjData)) {
            $arrObjData = get_object_vars($arrObjData);
        }
        if (is_array($arrObjData)) {
            foreach ($arrObjData as $index => $value) {
                if (is_object($value) || is_array($value)) {
                    $value = self::objectsIntoArray($value, $arrSkipIndices); // recursive call
                }
                if (in_array($index, $arrSkipIndices)) {
                    continue;
                }
                $arrData[$index] = $value;
            }
        }
        return $arrData;
    }

    /**
     * Convert xml to array
     *
     * @param string $xmlStr
     * @return array
     */

    static function xmlToArray($xmlStr)
    {
        $xmlStr = preg_replace('/\<\!\[CDATA\[(.*?)\]\]\>/', '$1', $xmlStr);

        $xmlObj = simplexml_load_string($xmlStr);
        $arrXml = self::objectsIntoArray($xmlObj);
        return $arrXml;
    }

    public static function readUrl2($url)
    {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            echo "Return code is {$httpCode} \n"
                . curl_error($ch);
        } else {
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }

        return $response;
    }

    public static function readUrlHttps($url)
    {
        return self::readUrl($url, 'get', array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6")
        );
    }

    /**
     * @param $date \DateTime
     */
    public static function getMonthStr($date)
    {
        // $short = array('01' => 'янв', '02' => 'фев', '03' => 'мар', '04' => 'апр.', '05' => 'май', '06' => 'июн', '07' => 'июл', '08' => 'авг', '09' => 'сен', '10' => 'окт', '11' => 'ноя', '12' => 'дек');
        $long = array('01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля', '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа', '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря');
        return $long[$date->format('m')];
    }

    /**
     * @param $form AbstractType
     * @return array
     */
    public static function getFormErrors($form)
    {
        $errors = array();
        foreach ($form as $name => $field) {
            /* @var $field \Symfony\Component\Form\Form */
            foreach ($field->getErrors(true) as $error) {
                /* @var $error \Symfony\Component\Form\FormError */
                $errors[$form->getName() . '_' . $name][] = $error->getMessage();
            }
        }
        foreach ($form->getErrors(false) as $error) {
            /* @var $error \Symfony\Component\Form\FormError */
            $errors[$form->getName()][] = $error->getMessage();
        }
        return $errors;
    }

    public static function thumbnail($webPath, $filter)
    {
        $imageManager = self::$container->get('liip_imagine.controller');
        /* @var $imageManager \Liip\ImagineBundle\Controller\ImagineController */
        $a = $imageManager->filterAction(self::getRequest(), $webPath, $filter);
        /* @var $a \Symfony\Component\HttpFoundation\RedirectResponse */
        return $a->getTargetUrl();

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public static function getSession()
    {
        if (self::$container) {
            return self::$container->get('session');
        } else {
            return null;
        }
    }

    /**
     * Translit from Rus utf-8
     *
     * @param string $value
     * @return string
     */

    public static function translit($value)
    {
        return str_replace(
            array('№',
                'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь', 'Ы', 'Ъ', 'Э', 'Ю', 'Я',
                'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь', 'ы', 'ъ', 'э', 'ю', 'я'),
            array('#',
                'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sh', '', 'I', '', 'E', 'Yu', 'Ya',
                'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sh', '', 'i', '', 'e', 'yu', 'ya'),
            $value);
    }

    static function mb_transliterate($string)
    {
        $table = array(
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
            'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH',
            'Ш' => 'SH', 'Щ' => 'SCH', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA',

            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        );

        $output = str_replace(
            array_keys($table),
            array_values($table), $string
        );

        // таеже те символы что неизвестны
        $output = preg_replace('/[^-a-z0-9._\[\]\'"]/i', ' ', $output);
        $output = preg_replace('/ +/', '-', $output);

        return $output;
    }

    static function transliterate($string)
    {
        $cyr = array(
            "Щ", "Ш", "Ч", "Ц", "Ю", "Я", "Ж", "А", "Б", "В",
            "Г", "Д", "Е", "Ё", "З", "И", "Й", "К", "Л", "М", "Н",
            "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ь", "Ы", "Ъ",
            "Э", "Є", "Ї", "І",
            "щ", "ш", "ч", "ц", "ю", "я", "ж", "а", "б", "в",
            "г", "д", "е", "ё", "з", "и", "й", "к", "л", "м", "н",
            "о", "п", "р", "с", "т", "у", "ф", "х", "ь", "ы", "ъ",
            "э", "є", "ї", "і"
        );
        $lat = array(
            "Shch", "Sh", "Ch", "C", "Yu", "Ya", "J", "A", "B", "V",
            "G", "D", "e", "e", "Z", "I", "y", "K", "L", "M", "N",
            "O", "P", "R", "S", "T", "U", "F", "H", "",
            "Y", "", "E", "E", "Yi", "I",
            "shch", "sh", "ch", "c", "Yu", "Ya", "j", "a", "b", "v",
            "g", "d", "e", "e", "z", "i", "y", "k", "l", "m", "n",
            "o", "p", "r", "s", "t", "u", "f", "h",
            "", "y", "", "e", "e", "yi", "i"
        );
        for ($i = 0; $i < count($cyr); $i++) {
            $c_cyr = $cyr[$i];
            $c_lat = $lat[$i];
            $string = str_replace($c_cyr, $c_lat, $string);
        }
        $string =
            preg_replace(
                "/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]e/",
                "\${1}e", $string);
        $string =
            preg_replace(
                "/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]/",
                "\${1}'", $string);
        $string = preg_replace("/([eyuioaEYUIOA]+)[Kk]h/", "\${1}h", $string);
        $string = preg_replace("/^kh/", "h", $string);
        $string = preg_replace("/^Kh/", "H", $string);
        return $string;
    }


    static function translitKeyb($value)
    {
        return str_replace(
            array(
                'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', '{', '}',
                'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', ':', '"',
                'Z', 'X', 'C', 'V', 'B', 'N', 'M', '<', '>', '?',

                'q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']',
                'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', '\'',
                'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '/'),
            array(
                'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ',
                'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д', 'Ж', 'Э',
                'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю', ',',
                'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ',
                'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э',
                'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', '.'),
            $value);
    }

    /**
     * Callback for generation sluggable value
     *
     * @param string $value
     * @param object $object
     * @return string
     */

    static function sluggable($value, $object = null)
    {
        return preg_replace(array('/[^a-zA-Z\d]+/', '/^_|_$/'), array('_', ''), strtolower(self::translit($value)));
    }

    /**
     * @param $v DateTime|String
     * @return MongoDate
     */
    public static function getMongoDate($v)
    {
        if (is_numeric($v)) {
            return new MongoDate($v);
        }
        if (is_string($v)) {
            return new MongoDate(strtotime($v));
        }
        if (is_object($v)) {
            if (get_class($v) == 'DateTime') {
                return new MongoDate($v->getTimeStamp());
            } else {
                return $v;
            }
        }
        return null;
    }

    public static function fixUrl($url, $baseUrl = null)
    {
        if (preg_match('/^\w+\.\w+$/', $url)) {
            $url = 'http://' . $url;
        }
        if (preg_match('/data:[^;]+;base64/', $url)) {
            return $url;
        }

        $url = preg_replace('/^[^\/]+\/\/[^\/]\/\//', '//', $url);
        $url = str_replace('\\"', '', $url);
        if (preg_match('/^[\w-]+\./', $url)) { //  www.domain.com/url
            $r = $baseUrl ? parse_url($baseUrl) : array();
            $scheme = isset($r['scheme']) ? $r['scheme'] : 'http';
            $url = $scheme . '://' . $url;
        } elseif (preg_match('/^\/\//', $url)) { //www.domain.com/url
            $r = $baseUrl ? parse_url($baseUrl) : array();
            $scheme = isset($r['scheme']) ? $r['scheme'] : 'http';
            $url = $scheme . ':' . $url;
        } elseif (preg_match('/^\//', $url)) { // /url
            $r = $baseUrl ? parse_url($baseUrl) : array('scheme' => 'http');
            if (!empty($r['host'])) {
                $url = (empty($r['scheme']) ? 'http' : $r['scheme']) . '://' . $r['host'] . $url;
            }
        }
        $urlRel = preg_replace('/https?:\/\/[^\/]+/', '', $url);
        $path = str_replace('/', DIRECTORY_SEPARATOR, self::getWebDir() . $urlRel);
        if (file_exists($path) && $urlRel) {
            return $urlRel;
        }
        return $url;

    }

    /**
     * @param $name
     * @param $default
     * @return null|string
     */
    public static function getSetting($name, $default = null)
    {
        $c = self::$container->get('brother_config');
        /* @var $c \Brother\ConfigBundle\Util\Config */
        $r = $c->get($name);
        if ($r == null) {
            $c->set($name, $default);
            return $default;
        }
        return $r;
    }

    public static function mbUcFirst($tag)
    {
        return mb_strtoupper(mb_substr($tag, 0, 1, 'utf-8'), 'utf-8') . mb_substr($tag, 1, 200, 'utf-8');
    }

    public static function updateVideoData($data, $key)
    {
        try {
            if (strpos($data['frame'], '.youtube.') !== false) {
                $url = "https://www.googleapis.com/youtube/v3/videos?id=" . $data['id'] . '&key=' . $key . "&fields=items(id,snippet(channelId,title,description,categoryId,thumbnails),statistics)&part=snippet,statistics";
                $videoData = json_decode(self::readUrlHttps($url), true);
                if (isset($videoData['items'][0]['snippet'])) {
                    $videoData = $videoData['items'][0]['snippet'];
                    if (!empty($videoData['channelId'])) {
                        $data['channelId'] = $videoData['channelId'];
                    }
                    if (!empty($videoData['title'])) {
                        $data['title'] = $videoData['title'];
                    }
                    if (!empty($videoData['description'])) {
                        $data['content'] = $videoData['description'];
                    }
                    if (!empty($videoData['thumbnails'])) {
                        $w = 0;
                        foreach ($videoData['thumbnails'] as $thumb) {
                            if ($thumb['width'] > $w) {
                                $w = $thumb['width'];
                                $data['image'] = $thumb['url'];
                            }
                        }
                    }
                } elseif (!isset($videoData['items'])) {
//                    AppDebug::_dx($videoData, $url);
                }
                return $data;
            }
        } catch (\Exception $e) {
            return $data;
        }
    }

    public static function getVideoData($url)
    {
        $url = trim($url, '\'" \n\r\t"');
        if ($url == 'http://www.youtube.com') {
            return null;
        }
        if (preg_match('/youtube\.com(?:\/|%2F)watch(?:\/|%2F)?(?:\?|%3F)v(?:=|%3D)([\w-]+)/', $url, $m)) {
            return array('id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>');
        }
        if (preg_match('/(?:[\&;]amp;v=|\&v=|youtube\.com\/embed\/|%2Fembed%2|\dv%3D|\/v\/|\?v=)([\w-]+)/', $url, $m)) {
            return array('id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>');
        }
        if (preg_match('/youtube\.com.*(?:v%3D|v%253D)([\w-]+)/', $url, $m)) {
            return array('id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>');
        }
        if (preg_match('/\/\/youtu.be\/([\w-]+)/', $url, $m)) {
            return array('id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>');
        }
        if (strpos($url, 'smartknowledgeu')) {
            return null;
        }
        if (preg_match('/\Wyoutube\.[cr]/', $url)) {
            $r = parse_url($url);
            if (isset($r['path']) && preg_match('/\/channel\/[\w-]+/', $r['path']) ||
                isset($r['path']) && preg_match('/\/videos/', $r['path']) ||
                strpos($url, '/user/') ||
                strpos($url, '/profile_redirector/') ||
                strpos($url, 'youtube.html') ||
                strpos($url, 'view_play_list') ||
                strpos($url, 'HouseConference') ||
                strpos($url, 'playlist') ||
                strpos($url, 'search_query')
            ) {
                return null;
            }
            if (empty($r['query'])) {
                return null;
            }
            if (strpos($url, '?user=') != false ||
                strpos($url, '/categories_portal') != false ||
                strpos($url, '/redirect?q') != false ||
                $url == 'http://www.youtube.com/watch?v=' ||
                strpos($url, '/subscription_center?&amp;add_user=') !== false ||
                strpos($url, 'http://www.youtube.com/comment?lc=') !== false
            ) {
                return null;
            }
//            AppDebug::_dx($r, $url);
            if (isset($r['query'])) {
                $params = array();
                parse_str($r['query'], $params);
                if (isset($params['v'])) {
                    return array('id' => $params['v'], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $params['v'] . '" frameborder="0" allowfullscreen></iframe>');
                }
                if (isset($params['amp;v'])) {
                    return array('id' => $params['amp;v'], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $params['amp;v'] . '" frameborder="0" allowfullscreen></iframe>');
                }

                if (isset($params['search_query'])) {
                    return null;
                }
                if (isset($params['u'])) {
                    return self::getVideoData($params['u']);
                }
                if (isset($params['amp;goto'])) {
                    return self::getVideoData($params['amp;goto']);
                }
                if (isset($params['amp;link'])) {
                    return self::getVideoData($params['amp;link']);
                }
//                if (strpos($url, '/categories_portal') == false) {
//                    AppDebug::_dx($params, $url);
//                }
            }
            if (strpos($url, '?user=') == false) {
//                AppDebug::_dx($r, $url);
            }
        }
        return null;
    }

    public static function getVideosFromChannel($channelId, $key)
    {
        $url = 'https://www.googleapis.com/youtube/v3/search?key=' . $key . '&channelId=' . $channelId . '&part=snippet,id&order=date&maxResults=20';
        AppDebug::_dx(self::readUrlHttps($url));

    }

    public static function arrayDiffAssoc($r2, $r1)
    {
        foreach ($r1 as $k => $v) {
            if ($r2[$k] == $v) {
                unset($r2[$k]);
            }
        }
        return $r2;
    }

    private static function getRequest()
    {
        return self::$container->get('request');
    }

    public static function getRootDir()
    {
        return self::$container->get('kernel')->getRootDir() . '/..';
    }

    public static function getWebDir()
    {
        return self::getRootDir() . '/web';
    }

    public static function isRss($feed)
    {
        $feed = trim($feed, " \n\r\d");
        if (strpos($feed, 'Ошибка 404') ||
            preg_match('/^<(\!doctype|html|body|head|h1)/i', $feed) ||
            !$feed
        ) {
            return false;
        }
        if (strpos($feed, '<rss') !== false || strpos($feed, '<?xml') !== false) {
            return true;
        }
        return true;
    }

    public static function readRssContent($url)
    {
        $feed = AppTools::readUrl($url, 'get', array(
            CURLOPT_URL => $url
        , CURLOPT_HEADER => 0
        , CURLOPT_RETURNTRANSFER => 1
        , CURLOPT_ENCODING => 'gzip'
        ));
        if ($feed && self::isRss($feed)) {
            return $feed;
        }

        $headers = AppTools::readUrlFast($url, 'get', array(CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1));
        if (preg_match_all('/Location: (.*)/', $headers, $m)) {
            $url = array_pop($m[1]);
        }
        $feed = AppTools::readUrlFast($url);
        if (!$feed || !self::isRss($feed)) {
            $feed = AppTools::readUrl($url);
        }
        if (!$feed || !self::isRss($feed)) {
            try {
                $feed = @file_get_contents($url);
            } catch (\Exception $e) {
                $feed = null;
            }
        }
        return $feed;
    }
}