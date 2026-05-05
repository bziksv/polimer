<?php
namespace Avito\Export\Api\OrderManagement\Model;

use Bitrix\Main;
use Avito\Export\Api;

class Order extends Api\Response
{
	public function id() : int
	{
		return (int)$this->requireValue('id');
	}

	public function marketplaceId() : ?string
	{
		return $this->getValue('marketplaceId');
	}

	public function number() : string
	{
		return $this->marketplaceId() ?? (string)$this->id();
	}

	public function status() : string
	{
		return (string)$this->requireValue('status');
	}

	public function returnPolicy() : ?Order\ReturnPolicy
	{
		return $this->getModel('returnPolicy', Order\ReturnPolicy::class);
	}

	public function createdAt() : Main\Type\DateTime
	{
		return new Main\Type\DateTime($this->requireValue('createdAt'), \DateTimeInterface::ATOM);
	}

	public function updatedAt() : Main\Type\DateTime
	{
		return new Main\Type\DateTime($this->requireValue('updatedAt'), \DateTimeInterface::ATOM);
	}

	public function delivery() : Order\Delivery
	{
		return $this->requireModel('delivery', Order\Delivery::class);
	}

	public function items() : Order\Items
	{
		return $this->requireCollection('items', Order\Items::class);
	}

	public function prices() : Order\Prices
	{
		return $this->requireModel('prices', Order\Prices::class);
	}

	public function schedules() : Order\Schedules
	{
		return $this->requireModel('schedules', Order\Schedules::class);
	}

	public function availableActions() : ?Order\AvailableActions
	{
		return $this->getCollection('availableActions', Order\AvailableActions::class);
	}
}