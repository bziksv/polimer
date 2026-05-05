<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\SetCourierDeliveryRange;

use Avito\Export\Api;

class Response extends Api\Response
{
    public function success() : bool
    {
        return (bool)$this->getValue('success');
    }
}