<?php

namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result\TimeIntervals;

use Avito\Export\Api;
use Avito\Export\Data;
use Bitrix\Main;

class TimeInterval extends Api\Response
{
    public function startDate(): Main\Type\DateTime
    {
        return Data\DateTime::cast($this->requireValue('startDate'));
    }

    public function endDate(): Main\Type\DateTime
    {
        return Data\DateTime::cast($this->requireValue('endDate'));
    }

    public function title() : string
    {
        return (string) $this->requireValue('title');
    }

    public function type() : string
    {
        return (string) $this->requireValue('type');
    }
}