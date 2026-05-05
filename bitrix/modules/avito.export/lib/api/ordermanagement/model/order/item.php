<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

class Item extends Api\Response
{
	public function avitoId() : string
	{
		return (string)$this->requireValue('avitoId');
	}

	public function chatId() : ?string
	{
		return $this->getValue('chatId');
	}

	public function id() : ?string
	{
		$value = $this->getValue('id');

		return $value !== null ? (string)$this->getValue('id') : null;
	}

	public function title() : string
	{
		return (string)$this->requireValue('title');
	}

	public function count() : float
	{
		return (float)$this->requireValue('count');
	}

	public function prices() : Item\Prices
	{
		return $this->requireModel('prices', Item\Prices::class);
	}

	public function discounts() : ?Item\Discounts
	{
		return $this->getCollection('discounts', Item\Discounts::class);
	}
}