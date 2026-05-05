<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Bitrix\Main;
use Avito\Export\Api;
use Avito\Export\Data;

class Schedules extends Api\Response
{
	public function meaningfulValues() : array
	{
		$result = [
			'CONFIRM_TILL' => $this->confirmTill(),
			'SHIP_TILL' => $this->shipTill(),
			'DELIVERY_DATE_MIN' => $this->deliveryDateMin(),
			'DELIVERY_DATE_MAX' => $this->deliveryDateMax(),
			'SET_TERMS_TILL' => $this->setTermsTill(),
			'SET_TRACKING_NUMBER_TILL' => $this->setTrackingNumberTill()
		];

		if ($result['DELIVERY_DATE_MIN'] === null && $result['DELIVERY_DATE_MAX'] === null)
		{
			$result['DELIVERY_DATE_MIN'] = $this->deliveryDate();
			$result['DELIVERY_DATE_MAX'] = $this->deliveryDate();
		}

		return $result;
	}

	public function confirmTill() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('confirmTill'));
	}

	public function shipTill() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('shipTill'));
	}

	public function deliveryDateMin() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('deliveryDateMin'));
	}

	public function deliveryDateMax() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('deliveryDateMax'));
	}

	public function deliveryDate() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('deliveryDate'));
	}

	public function setTermsTill() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('setTermsTill'));
	}

	public function setTrackingNumberTill() : ?Main\Type\DateTime
	{
		return Data\DateTime::cast($this->getValue('setTrackingNumberTill'));
	}
}