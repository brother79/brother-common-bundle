<?php
/**
 * Created by PhpStorm.
 * User: Andrey Dashkovskiy
 * Date: 18.03.14
 * Time: 14:56
 */

namespace Brother\CommonBundle;

use App\Utils\Config;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Liip\ImagineBundle\Controller\ImagineController;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

//use Doctrine\ODM\MongoDB\DocumentManager;

class AppTools
{
    /**
     * @var ContainerInterface|null
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

    public static function stringToXml($feed)
    {
        $xml = @simplexml_load_string($feed, "SimpleXMLElement");
        if ($xml == null) {
            $xml = @simplexml_load_string(@iconv('utf-8', 'cp1251', $feed), "SimpleXMLElement");
        }
        if ($xml == null) {
            $xml = @simplexml_load_string(preg_replace('/[\x1C-\x1F]/', '', $feed), "SimpleXMLElement");
        }

        return $xml;
    }

    public static function normPhone($phone)
    {
        switch (strlen($phone)) {
            case 5:
                return substr($phone, 0, 1) . '-' . substr($phone, 1, 2) . '-' . substr($phone, 3, 2);
                break;
            case 6:
                return substr($phone, 0, 2) . '-' . substr($phone, 2, 2) . '-' . substr($phone, 4, 2);
                break;
            case 11:
                if (substr($phone, 0, 2) == '89') {
                    return substr($phone, 0, 1) . '-' . substr($phone, 1, 3) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7, 4);
                } else {
                    return $phone;
                }
                break;
            default:
                return $phone;
                break;
        }
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
     * Return Header
     *
     * @param String $url
     * @param String $metod - get or post
     *
     * @return String
     */

    static function readHeader($url, $metod = 'get')
    {
        return self::readUrlCommon($url, $metod, [CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1]);

//		return  self::readUrlCommon($url, $metod, array(CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1,
//			CURLOPT_SSL_VERIFYPEER => FALSE, CURLOPT_AUTOREFERER => TRUE, CURLOPT_FAILONERROR => 1,
//			CURLOPT_BINARYTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_MAXREDIRS => 4, CURLOPT_FILETIME => TRUE,
//			CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 20,
//			CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506)",
//			CURLOPT_FRESH_CONNECT => TRUE));
    }

    /**
     * Read url
     *
     * @param string $url - url
     * @param string $metod - metod post or get
     * @param array $options - options for curl
     *
     * @return string
     */
    static protected function readUrlCommon(string $url, string $metod = 'get', array $options = [], array $params = [])
    {
//        AppDebug::_dx($options);
        $t = explode('?', $url);
        if (is_array($params)) {
            $params = http_build_query($params, '=', '&');
        }
        if ($metod == 'post') {
            $params = (isset($t[1])) ? ($params ? $params . '&' : '') . $t[1] : $params;
        }
        $ch = curl_init();
        $str = [
            "Accept-Language: ru, en-us,en;q=0.5",
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Keep-Alive: 300",
            "Connection: keep-alive"
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $str);

        curl_setopt($ch, CURLOPT_URL, $url);

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
//        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        AppDebug::_dx([$url, $httpCode, $res, $options]);
        curl_close($ch);

        return $res;
    }

    /**
     * Enter description here...
     *
     * @param string $url
     * @param string $metod
     *
     * @return string
     */

    static function readUrlAndHeader(string $url, string $metod = 'get')
    {
        return self::readUrlCommon($url, $metod, [CURLOPT_HEADER => 1, CURLOPT_NOBODY => 0]);
    }

    /**
     * Convert Header to Array
     *
     * @param String $header
     *
     * @return array
     */

    static function normHeader(string $header)
    {
        $header = explode("\n", $header);
        $result = [];
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
     *
     * @return mixed
     */

    static function findPr1(string $url)
    {
        $s = self::readUrl("http://gogolev.net/tools/webmaster/enter.php?q=" . $url);
        if (preg_match('/ pr: (\d+)/', $s, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Return content from Url
     *
     * @param String $url
     * @param String $metod - get or post
     * @param array $options
     * @param array $params
     *
     * @return String
     */

    static function readUrl(string $url, string $metod = 'get', array $options = [], array $params = [])
    {
        $options[CURLOPT_HEADER] = 0;
        $options[CURLOPT_NOBODY] = 0;
        return self::readUrlCommon($url, $metod, $options, $params);
    }

    /**
     * Find pagerank from http://4seo.biz/tools/29
     *
     * @param string $url
     *
     * @return mixed
     */

    static function findPr2(string $url)
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

    static function serialize(array $array)
    {
        $r = [];
        foreach ($array as $key => $value) {
            $r[] = $key . '=' . $value;
        }
        return implode('&', $r);
    }

    /**
     * Convert to Time stamp
     *
     * @param UTCDateTime|DateTime $value
     * @param bool $isDate
     *
     * @return integer
     */

    static function getTimeStamp($value, $isDate = false)
    {
        if (is_object($value)) {
            switch (get_class($value)) {
                case 'MongoDB\BSON\UTCDateTime':
                    return self::getTimeStamp($value->toDateTime());
                case 'DateTime':
                    return $value->getTimestamp();
                default:
                    AppDebug::_dx($value);
            }
        }
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
        $t = [];
        for ($i = 0; $i < 3; $i++) {
            $tt = $time % 60;
            array_unshift($t, $tt < 10 ? '0' . $tt : $tt);
            $time = ($time - $tt) / 60;
        }
        return implode(':', $t);
    }

    /**
     * Convert xml to array
     *
     * @param string $xmlStr
     *
     * @return array
     */

    static function xmlToArray(string $xmlStr)
    {
        $xmlStr = preg_replace('/\<\!\[CDATA\[(.*?)\]\]\>/', '$1', $xmlStr);

        $xmlObj = simplexml_load_string($xmlStr);
        $arrXml = self::objectsIntoArray($xmlObj);
        return $arrXml;
    }

    /**
     * For XmlToArray
     *
     * @param mixed $arrObjData
     * @param array $arrSkipIndices
     *
     * @return array
     */

    static function objectsIntoArray($arrObjData, $arrSkipIndices = [])
    {
        $arrData = [];
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

    public static function readUrl2($url)
    {
        $ch = curl_init();

        $options = [
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
        ];
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

    /**
     * @param $date DateTime
     *
     * @return string
     */
    public static function getMonthStr(DateTime $date)
    {
        // $short = ['01' => 'янв', '02' => 'фев', '03' => 'мар', '04' => 'апр.', '05' => 'май', '06' => 'июн', '07' => 'июл', '08' => 'авг', '09' => 'сен', '10' => 'окт', '11' => 'ноя', '12' => 'дек'];
        $long = ['01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля', '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа', '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'];
        return $long[$date->format('m')];
    }

    /**
     * @param $form AbstractType
     *
     * @return array
     */
    public static function getFormErrors($form)
    {
        $errors = [];
        foreach ($form as $name => $field) {
            /* @var $field Form */
            foreach ($field->getErrors(true) as $error) {
                /* @var $error FormError */
                $errors[$form->getName() . '_' . $name][] = $error->getMessage();
            }
        }
        foreach ($form->getErrors(false) as $error) {
            /* @var $error FormError */
            $errors[$form->getName()][] = $error->getMessage();
        }
        return $errors;
    }

    public static function thumbnail($webPath, $filter)
    {
        $imageManager = self::$container->get('liip_imagine.controller');
        /* @var $imageManager ImagineController */
        $a = $imageManager->filterAction(self::getRequest(), $webPath, $filter);
        /* @var $a RedirectResponse */
        return $a->getTargetUrl();

    }

    private static function getRequest()
    {
        return self::$container->get('request');
    }

    /**
     * @return Session
     */
    public static function getSession()
    {
        if (self::$container) {
            return self::$container->get('session');
        } else {
            return null;
        }
    }

    static function mb_transliterate(string $string)
    {
        $table = [
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
        ];

        $output = str_replace(
            array_keys($table),
            array_values($table), $string
        );

        // таеже те символы что неизвестны
        $output = preg_replace('/[^-a-z0-9._\[\]\'"]/i', ' ', $output);
        $output = preg_replace('/ +/', '-', $output);

        return $output;
    }

    static function transliterate(string $string)
    {
        $cyr = [
            "Щ", "Ш", "Ч", "Ц", "Ю", "Я", "Ж", "А", "Б", "В",
            "Г", "Д", "Е", "Ё", "З", "И", "Й", "К", "Л", "М", "Н",
            "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ь", "Ы", "Ъ",
            "Э", "Є", "Ї", "І",
            "щ", "ш", "ч", "ц", "ю", "я", "ж", "а", "б", "в",
            "г", "д", "е", "ё", "з", "и", "й", "к", "л", "м", "н",
            "о", "п", "р", "с", "т", "у", "ф", "х", "ь", "ы", "ъ",
            "э", "є", "ї", "і"
        ];
        $lat = [
            "Shch", "Sh", "Ch", "C", "Yu", "Ya", "J", "A", "B", "V",
            "G", "D", "e", "e", "Z", "I", "y", "K", "L", "M", "N",
            "O", "P", "R", "S", "T", "U", "F", "H", "",
            "Y", "", "E", "E", "Yi", "I",
            "shch", "sh", "ch", "c", "Yu", "Ya", "j", "a", "b", "v",
            "g", "d", "e", "e", "z", "i", "y", "k", "l", "m", "n",
            "o", "p", "r", "s", "t", "u", "f", "h",
            "", "y", "", "e", "e", "yi", "i"
        ];
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
            [
                'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', '{', '}',
                'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', ':', '"',
                'Z', 'X', 'C', 'V', 'B', 'N', 'M', '<', '>', '?',

                'q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']',
                'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', '\'',
                'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '/'],
            [
                'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ',
                'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д', 'Ж', 'Э',
                'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю', ',',
                'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ',
                'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э',
                'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', '.'],
            $value);
    }

    /**
     * Callback for generation sluggable value
     *
     * @param string $value
     * @param null $object $object
     *
     * @return string
     */

    static function sluggable($value, $object = null)
    {
        return preg_replace(array('/[^a-zA-Z\d]+/', '/^_|_$/'), ['_', ''], strtolower(self::translit($value)));
    }

    /**
     * Translit from Rus utf-8
     *
     * @param string $value
     *
     * @return string
     */

    public static function translit($value)
    {
        return str_replace(
            ['№',
                'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь', 'Ы', 'Ъ', 'Э', 'Ю', 'Я',
                'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь', 'ы', 'ъ', 'э', 'ю', 'я'],
            ['#',
                'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sh', '', 'I', '', 'E', 'Yu', 'Ya',
                'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sh', '', 'i', '', 'e', 'yu', 'ya'],
            $value);
    }

    /**
     * @param $v DateTime|String
     *
     * @return UTCDateTime
     */
    public static function getMongoDate($v)
    {
        if ($v === null) {
            return null;
        }
        if (is_numeric($v)) {
            return new UTCDateTime($v * 1000);
        }
        if (is_string($v)) {
            return new UTCDateTime(strtotime($v) * 1000);
        }
        if (is_object($v)) {
            switch (get_class($v)) {
                case 'DateTime':
                    return new UTCDateTime($v->getTimeStamp() * 1000);
                case 'MongoDB\BSON\UTCDateTime':
                    return $v;
                default:
                    AppDebug::_dx($v);
                    return $v;
            }
        }
        AppDebug::_dx($v);
        return null;
    }

    /**
     * @param $v
     *
     * @return DateTime
     * @throws Exception
     */
    public static function getDateTime($v): ?DateTime
    {
        if ($v === null) {
            return $v;
        }
        if (is_object($v)) {
            switch (get_class($v)) {
                case DateTime::class:
                    return $v;
                default:
                    AppDebug::_dx(get_class($v));
                    break;
            }
        }
        if (is_array($v)) {
            if (isset($v['date'])) {
                $r = new DateTime($v['date']);
                if (isset($v['timezone']) && $v['timezone'] != $r->getTimezone()->getName()) {
                    $r->setTimezone(new DateTimeZone($v['timezone']));
                }
                return $r;
            }
        }
        AppDebug::_dx($v);
        return null;
    }

    public static function fixUrl(string $url, ?string $baseUrl = null)
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
            $r = $baseUrl ? parse_url($baseUrl) : [];
            $scheme = isset($r['scheme']) ? $r['scheme'] : 'http';
            $url = $scheme . '://' . $url;
        } elseif (preg_match('/^\/\//', $url)) { //www.domain.com/url
            $r = $baseUrl ? parse_url($baseUrl) : [];
            $scheme = isset($r['scheme']) ? $r['scheme'] : 'http';
            $url = $scheme . ':' . $url;
        } elseif (preg_match('/^\//', $url)) { // /url
            $r = $baseUrl ? parse_url($baseUrl) : ['scheme' => 'http'];
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

    public static function getWebDir(): string
    {
        return self::getRootDir() . '/public';
    }

    public static function getRootDir(): string
    {
        return self::$container->get('kernel')->getProjectDir() . '/..';
    }

    /**
     * @param string $name
     * @param null $default
     *
     * @return null|string
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public static function getSetting(string $name, $default = null)
    {
        /** @var Config $c */
        $c = self::$container->get('brother_config');
        /* @ var $c \Brother\ConfigBundle\Util\Config */
        $r = $c->get($name, false);
        if ($r == null) {
            $c->set($name, $default, false);
            return $default;
        }
        return $r;
    }

    public static function mbUcFirst(string $tag)
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
                }
                return $data;
            }
        } catch (Exception $e) {
            return $data;
        }
        return $data;
    }

    /**
     * @param $url
     *
     * @return String
     */
    public static function readUrlHttps($url)
    {
        return self::readUrl($url, 'get', array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6")
        );
    }

    /**
     * @param $url
     *
     * @return array|null
     */
    public static function getVideoData($url)
    {
        if (preg_match('/vestifinance\.ru\/videos\/(\d+)/', $url, $m)) {
            return ['provider' => 'vestifinance', 'id' => $m[1], 'frame' => '<iframe width="640" height="512" src="http://www.vestifinance.ru/v/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>'];
        }
        $url = trim($url, '\'" \n\r\t"');
        if ($url == 'http://www.youtube.com') {
            return null;
        }
        if (preg_match('/youtube\.com(?:\/|%2F)watch(?:\/|%2F)?(?:\?|%3F)v(?:=|%3D)([\w-]+)/', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>'];
        }
        if (preg_match('/(?:[\&;]amp;v=|\&v=|youtube\.com\/embed\/|%2Fembed%2|\dv%3D|\/v\/|\?v=)([\w-]+)/', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>'];
        }
        if (preg_match('/youtube\.com.*(?:v%3D|v%253D)([\w-]+)/', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>'];
        }
        if (preg_match('/\/\/youtu.be\/([\w-]+)/', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $m[1] . '" frameborder="0" allowfullscreen></iframe>'];
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
            if (isset($r['query'])) {
                $params = [];
                parse_str($r['query'], $params);
                if (isset($params['v'])) {
                    return ['provider' => 'youtube', 'id' => $params['v'], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $params['v'] . '" frameborder="0" allowfullscreen></iframe>'];
                }
                if (isset($params['amp;v'])) {
                    return ['provider' => 'youtube', 'id' => $params['amp;v'], 'frame' => '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $params['amp;v'] . '" frameborder="0" allowfullscreen></iframe>'];
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
            }
            if (strpos($url, '?user=') == false) {
            }
        }
        return null;
    }

    /**
     * @param $channelId
     * @param $key
     */
    public static function getVideosFromChannel($channelId, $key)
    {
        $url = 'https://www.googleapis.com/youtube/v3/search?key=' . $key . '&channelId=' . $channelId . '&part=snippet,id&order=date&maxResults=20';
        AppDebug::_dx(self::readUrlHttps($url));

    }

    /**
     * @param $r2
     * @param $r1
     *
     * @return mixed
     */
    public static function arrayDiffAssoc($r2, $r1)
    {
        foreach ($r1 as $k => $v) {
            if ($r2[$k] == $v) {
                unset($r2[$k]);
            }
        }
        return $r2;
    }

    /**
     * @param $url
     *
     * @return bool|null|string
     */
    public static function readRssContent($url)
    {
        $feed = AppTools::readUrl($url, 'get', [
            CURLOPT_URL => $url
            , CURLOPT_HEADER => 0
            , CURLOPT_FOLLOWLOCATION => 1
            , CURLOPT_RETURNTRANSFER => 1
            , CURLOPT_ENCODING => 'gzip'
        ]);
        if ($feed && self::isRss($feed)) {
            return $feed;
        }

        if (strpos($url, 'http://www.') !== false && !self::isRss($feed)) {
            $feed = AppTools::readUrl(str_replace('http://www.', 'http://', $url));
            if ($feed && self::isRss($feed)) {
                return $feed;
            }
        }


        $headers = AppTools::readUrlFast($url, 'get', [CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1]);
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
            } catch (Exception $e) {
                $feed = null;
            }
        }

        return $feed;
    }

    /**
     * @param $feed
     *
     * @return bool
     */
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

    /**
     * Read url fast
     *
     * @param string $url
     * @param string $method
     * @param array $options
     * @param array $params
     *
     * @return string
     */

    static function readUrlFast($url, $method = 'get', $options = [], $params = [])
    {
        $userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $o = [
            CURLOPT_HEADER => 0, CURLOPT_NOBODY => 0,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ];
        foreach ($options as $k => $v) {
            $o[$k] = $v;
        }
        return self::readUrlCommon($url, $method, $o, $params);
    }

    /**
     * @param $url
     * @param $param
     * @param $value
     *
     * @return string
     */
    static function normUrl($url, $param, $value)
    {
        if ($url == '') {
            return $url;
        }
        switch ($param) {
            case PHP_URL_SCHEME:
                if (parse_url($url, PHP_URL_SCHEME) == '') {
                    $url = $value . '://' . $url;
                }
                break;
        }
        return $url;
    }

    /**
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        return self::$container;
    }

    /**
     * @param $html
     *
     * @return string
     */
    public static function detectEncoding($html)
    {
        if ($t = @iconv('utf-8', 'utf-8', $html)) {
            return $html;
        }
        if ($t = @iconv('utf8', 'utf8', $html)) {
            return $html;
        }
        if ($t = @iconv('cp1251', 'cp1251', $html)) {
            return iconv('cp1251', 'utf-8', $html);
        }
        return $html;
    }

    /**
     * @param $dateTime
     *
     * @return DateTime
     * @throws Exception
     */
    public static function toDateTime($dateTime): DateTime
    {
        if (is_object($dateTime)) {
            switch (get_class($dateTime)) {
                case 'DateTime':
                    return $dateTime;
                default:
                    AppDebug::_dx([get_class($dateTime), $dateTime]);
            }
        }
        if (is_string($dateTime)) {
            return new DateTime($dateTime);
        }
        AppDebug::_dx($dateTime);
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
     * @param $s
     *
     * @return bool
     */
    static function isUtf8($s)
    {
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $c = ord($s[$i]);
            if ($i + 3 < $len && ($c & 248) === 240 && (ord($s[$i + 1]) & 192) === 128 && (ord($s[$i + 2]) & 192) === 128 && (ord($s[$i + 3]) & 192) === 128) {
                $i += 3;
            } else if ($i + 2 < $len && ($c & 240) === 224 && (ord($s[$i + 1]) & 192) === 128 && (ord($s[$i + 2]) & 192) === 128) {
                $i += 2;
            } else if ($i + 1 < $len && ($c & 224) === 192 && (ord($s[$i + 1]) & 192) === 128) {
                $i += 1;
            } else if (($c & 128) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $s
     *
     * @return bool|string
     */
    static function fixUtf8($s)
    {
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $c = ord($s[$i]);
            if ($i + 3 < $len && ($c & 248) === 240 && (ord($s[$i + 1]) & 192) === 128 && (ord($s[$i + 2]) & 192) === 128 && (ord($s[$i + 3]) & 192) === 128) {
                $i += 3;
            } else if ($i + 2 < $len && ($c & 240) === 224 && (ord($s[$i + 1]) & 192) === 128 && (ord($s[$i + 2]) & 192) === 128) {
                $i += 2;
            } else if ($i + 1 < $len && ($c & 224) === 192 && (ord($s[$i + 1]) & 192) === 128) {
                $i += 1;
            } else if (($c & 128) !== 0) {
                return substr($s, 0, $i);
            }
        }
        return $s;
    }

    /**
     * @param      $s
     * @param null $toRus
     *
     * @return bool|mixed
     */
    static function strRotateKeyboard($s, $toRus = null)
    {
        if ($toRus === null) {
            $s1 = preg_replace('/[^a-z]/i u', '', $s);
            $s2 = preg_replace('/[^а-я]/i u', '', $s);
            if (strlen($s1) > 0 xor strlen($s2) > 0) {
                return self::strRotateKeyboard($s, strlen($s1) > 0);
            }
            return false;
        }
        $chars = [
            true => [
                'q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', '\'', '\\', 'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '/',
                'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', '{', '}', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', ':', '"', '|', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', '<', '>', '?'
            ],
            false => [
                'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', 'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э', '\\', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', '.',
                'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ', 'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д', 'Ж', 'Э', '/', 'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю', ','
            ]
        ];
        $s = str_replace($chars[$toRus], $chars[!$toRus], $s);
        return $s;
    }

    /**
     * Сравнивает 2 массива рекурсивно
     *
     * @param array $a1 Массив для сравнения
     * @param array $a2 Массив для сравнени
     * @param array $ignoreKey Игнорируемые индексы
     * @param bool $debug Для отладки вывод различающихся элементов
     *
     * @return bool
     */
    public static function arrayCmp($a1, $a2, array $ignoreKey = [], bool $debug = false)
    {
        if (!is_array($a1) || !is_array($a2)) {
            if ($debug) {
                AppDebug::_dx([$a1, $a2]);
            }
            return false;
        }
        foreach ($a1 as $k => $v) {
            if ($v) {
                if (!empty($a2[$k])) {
                    if (is_array($v)) {
                        if (!self::arrayCmp($v, $a2[$k], $ignoreKey)) {
                            return false;
                        }
                    } elseif (is_object($v)) {
                        if (json_encode($v) != json_encode($a2[$k])) {
                            if ($debug) {
                                AppDebug::_dx([$v, $a2[$k]]);
                            }
                            return false;
                        }
                    } else {
                        if ($v != $a2[$k] && array_search($k, $ignoreKey) === false) {
                            if ($debug) {
                                AppDebug::_dx([$v, $a2[$k]]);
                            }
                            return false;
                        }
                    }
                } else {
                    if ($debug) {
                        AppDebug::_dx([$k, $v, $a1, $a2]);
                    }
                    return false;
                }
            }
        }
        foreach ($a2 as $k => $v) {
            if ($v && empty($a1[$k])) {
                if ($debug) {
                    AppDebug::_dx([$k, $v, $a1, $a2]);
                }
                return false;
            }
        }
        return true;
    }

    /**
     * @param $memory
     *
     * @return string
     */
    public static function formatMemory($memory)
    {
        if ($memory < 1024) {
            return $memory . 'b';
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 2) . 'Kb';
        }

        return round($memory / 1048576, 2) . 'Mb';
    }

}