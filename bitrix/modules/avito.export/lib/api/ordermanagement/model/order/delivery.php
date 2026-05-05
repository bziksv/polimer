<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

class Delivery extends Api\Response
{
	public function serviceName() : string
	{
		return (string)$this->requireValue('serviceName');
	}

	public function serviceType() : string
	{
		return (string)$this->requireValue('serviceType');
	}

	public function trackingNumber() : ?string
	{
		return $this->getValue('trackingNumber');
	}

	public function dispatchNumber() : ?string
	{
		return $this->getValue('dispatchNumber');
	}

	public function buyerInfo() : ?Delivery\BuyerInfo
	{
		return $this->getModel('buyerInfo', Delivery\BuyerInfo::class);
	}

	public function courierInfo() : ?Delivery\CourierInfo
	{
		return $this->getModel('courierInfo', Delivery\CourierInfo::class);
	}

	public function terminalInfo() : ?Delivery\TerminalInfo
	{
		return $this->getModel('terminalInfo', Delivery\TerminalInfo::class);
	}
}