<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result;

use Avito\Export\Api;

/**
 * @property DateOption[] $collection
 */
class DateOptions extends Api\ResponseCollection
{
    protected function itemClass() : string
    {
        return DateOption::class;
    }
}