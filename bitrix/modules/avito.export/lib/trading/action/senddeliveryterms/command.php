<?php
namespace Avito\Export\Trading\Action\SendDeliveryTerms;

use Bitrix\Main;
use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $externalId;
	protected $externalNumber;
	protected $deliveryCostRub;
	protected $deliveryDate;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(int $orderId, string $externalId, string $externalNumber, float $deliveryCostRub, Main\Type\Date $deliveryDate = null, bool $userInput = false, bool $alreadySaved = true)
	{
		$this->orderId = $orderId;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
		$this->deliveryCostRub = $deliveryCostRub;
		$this->deliveryDate = $deliveryDate;
		$this->userInput = $userInput;
		$this->alreadySaved = $alreadySaved;
	}

	public function orderId() : int
	{
		return $this->orderId;
	}

	public function externalId() : string
	{
		return $this->externalId;
	}

	public function externalNumber() : string
	{
		return $this->externalNumber;
	}

	public function deliveryCostRub() : float
	{
		return $this->deliveryCostRub;
	}

	public function deliveryDate() : ?Main\Type\Date
	{
		return $this->deliveryDate;
	}

	public function userInput() : bool
	{
		return $this->userInput;
	}

	public function alreadySaved() : bool
	{
		return $this->alreadySaved;
	}
}