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

use Monolog\Processor\ProcessorInterface;

/**
 * Внедряет имя домена в екстра данные
 */
class DomainProcessor implements ProcessorInterface {


    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array {
        $record['extra']['domain'] = AppDebug::getHttpHost();
        return $record;
    }
}
