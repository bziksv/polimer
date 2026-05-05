<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result\TimeIntervals;

use Avito\Export\Api;

/**
 * @property TimeInterval[] $collection
 */
class TimeIntervals extends Api\ResponseCollection
{
    protected function itemClass() : string
    {
        return TimeInterval::class;
    }
}