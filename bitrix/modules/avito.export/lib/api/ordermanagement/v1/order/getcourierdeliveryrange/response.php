<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange;

use Avito\Export\Api;
use Avito\Export\Data;
use Bitrix\Main;

class Response extends Api\Response
{
	public function address() : string
	{
		return (string) $this->requireValue('result.address');
	}

    public function addressDetails() : string
    {
        return (string) $this->requireValue('result.addressDetails');
    }

    public function dateOptions() : Result\DateOptions
    {
        return $this->requireCollection('result.dateOptions', Result\DateOptions::class);
    }

    public function startDate(): Main\Type\DateTime
    {
        return Data\DateTime::cast($this->requireValue('result.startDate'));
    }

    public function endDate(): Main\Type\DateTime
    {
        return Data\DateTime::cast($this->requireValue('result.endDate'));
    }

    public function name(): string
    {
        return (string) $this->requireValue('result.name');
    }

    public function phone(): string
    {
        return (string) $this->requireValue('result.phone');
    }

    public function status(): string
    {
        return (string) $this->requireValue('status');
    }
}