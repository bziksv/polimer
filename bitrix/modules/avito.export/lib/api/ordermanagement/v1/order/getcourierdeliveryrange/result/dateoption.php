<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result;

use Avito\Export\Api;
use Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result\TimeIntervals\TimeIntervals;
use Avito\Export\Data;
use Bitrix\Main;

class DateOption extends Api\Response
{
    public function date(): Main\Type\DateTime
    {
        return Data\DateTime::cast($this->requireValue('date'));
    }

    public function timeIntervals(): TimeIntervals
    {
        return $this->requireCollection('timeIntervals', TimeIntervals::class);
    }
}