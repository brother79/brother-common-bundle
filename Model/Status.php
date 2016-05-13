<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 13.05.2016
 * Time: 11:19
 */

namespace Brother\CommonBundle\Model;


class Status {
    const STATUS_VALID = 0;       // Валидный
    const STATUS_INVALID = 1;     // Инвалидный или удалённый, который не показывать
    const STATUS_MODERATE = 2;    // Требуется модерация
    const STATUS_FREEZE = 3;      // Заморожет
    const STATUS_RESOLVED = 4;    // Решённый
    const STATUS_IN_PROGRESS = 5; // В работе
}