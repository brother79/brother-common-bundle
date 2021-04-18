<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Logger\Processor;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Внедряет имя файла и строку вызвавшего метода, сокращёный трейс типа
 */
class LineFileProcessor implements ProcessorInterface {

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        $prev = null;
        foreach ($trace as $item) {
            $skip = false;
            if (
                'log' === $item['function'] || '__invoke' == $item['function'] ||
                strpos($item['function'], 'writeLog') === 0 ||
                LineFileProcessor::class === $item['class'] || // Пропускаем себя
//                HandlerWrapperBubble::class === $item['class'] || // пропускаем обрабтчик логов
                AbstractProcessingHandler::class === $item['class'] ||
                Logger::class === $item['class']
            ) {
                $skip = true;
            }
            if ($skip) {
                $prev = $item;
            } else {
                $record['extra']['line'] = $prev['file'] . '(' . $prev['line'] . ') ' . $item['class'] . $item['type'] . $item['function'];
                break;
            }
        }
        return $record;
    }
}
