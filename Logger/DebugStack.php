<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brother\CommonBundle\Logger;

use Brother\CommonBundle\AppDebug;


/**
 * SphinxLogger
 */
class DebugStack extends \Doctrine\DBAL\Logging\DebugStack {

    public function stopQuery() {
        parent::stopQuery();
        if ($this->enabled) {
            $this->queries[$this->currentQuery]['trace'] = AppDebug::traceAsString(30);
        }
    }


}
