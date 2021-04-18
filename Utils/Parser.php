<?php


namespace Brother\CommonBundle\Utils;

/**
 * Вынес парсинг станлдартных конструкций, чтобы не плодить одинаковые сетоды
 *
 * Class slParser
 * @package App\utils
 */
class Parser {

    /**
     * Парсинг кавычек. Реагирует на эканирование через \
     *
     * @param string   $s
     * @param int      $i
     * @param string   $char
     * @param int|null $len
     *
     * @return int
     */
    public static function parseQuote(string $s, int $i, string $char, int $len): int {
        $i++;
        while ($i < $len) {
            switch ($s[$i]) {
                case '\\':
                    $i += 2;
                    break;
                case $char:
                    return $i + 1;
                default:
                    $i++;
            }
        }
        return $i;
    }

    /**
     * @param string $s
     * @param int    $i
     * @param int    $len
     *
     * @return bool
     */
    public static function isCommentJs(string $s, int $i, int $len): bool {
        $i++;
        return $i < $len && ($s[$i] == '*' || $s[$i] == '/');
    }

    /**
     * Парсим коммантарий в js
     *
     * @param string $s
     * @param int    $i
     * @param int    $len
     *
     * @return int
     */
    public static function parseCommentJs(string $s, int $i, int $len): int {
        $i++;
        switch ($s[$i]) {
            case '/':
                $i++;
                while ($i < $len && $s[$i] !== "\n" && $s[$i] !== "\r") {
                    $i++;
                }
                $i++;
                break;
            case '*':
                $i++;
                while ($i + 1 < $len && ($s[$i] !== "*" || $s[$i + 1] != '/')) {
                    $i++;
                }
                $i += 2;
                break;
        }
        return $i;
    }

    /**
     * Парсим комментарий твига
     *
     * @param string $s
     * @param int    $i
     * @param int    $len
     *
     * @return int
     */
    public static function parseCommentTwig(string $s, int $i, int $len): int {
        $i += 2;
        while ($i + 1 < $len && ($s[$i] !== "#" || $s[$i + 1] != '}')) {
            $i++;
        }
        $i += 2;
        return $i;
    }

    /**
     * Парсинг {{}}
     *
     * @param string $s
     * @param int    $i
     * @param int    $len
     *
     * @return int
     */
    public static function parseTwigContent(string $s, int $i, int $len): int {
        $i += 2;
        while ($i + 1 < $len && ($s[$i] !== "}" || $s[$i + 1] != '}')) {
            $i++;
        }
        $i += 2;
        return $i;
    }

    /**
     * Парсинг {%%}
     *
     * @param string $s
     * @param int    $i
     *
     * @param int    $len
     *
     * @return int
     */
    public static function parseTwigBlock(string $s, int $i, int $len): int {
        $i += 2;
        while ($i + 1 < $len && ($s[$i] !== "%" || $s[$i + 1] != '}')) {
            $i++;
        }
        $i += 2;
        return $i;
    }
}
