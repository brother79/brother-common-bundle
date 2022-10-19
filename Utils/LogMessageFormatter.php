<?php


namespace Brother\CommonBundle\Utils;

/**
 * Типа как в mysql, форматируем
 * %имя% - переменная
 * { бла бла %имя% бла бла} Врезает весь блок если имя не задано
 * Class LogMessageFormatter
 * @package App\logs\Formatter
 */
class LogMessageFormatter {

    static private function parseBreak(string $s, int $i, string $char, int $len): int {
        $i++;
        while ($i < $len) {
            switch ($s[$i]) {
                case $char:
                    return $i + 1;
                case '{':
                    $i = self::parseBreak($s, $i, '}', $len);
                    break;
                default:
                    $i++;
                    break;
            }
        }
        return $i;
    }

    static function format($message, $params) {
        $i = 0;
        $len = strlen($message);
        $res = '';
        while ($i < $len) {
            switch ($message[$i]) {
                case '{':
                    $i0 = $i;
                    $i = self::parseBreak($message, $i, '}', $len);
                    $res .= self::format(substr($message, $i0 + 1, $i - $i0 - 2), $params);
                    break;
                case '%':
                    $i0 = $i;
                    $i = Parser::parseQuote($message, $i, '%', $len);
                    $name = substr($message, $i0 + 1, $i - $i0 - 2);
                    if (isset($params[$name])) {
                        $res .= $params[$name];
                    } else {
                        return null;
                    }
                    break;
                default:
                    $res .= $message[$i];
                    $i++;
                    break;
            }
        }
        return $res;
    }
}
