<?php
namespace Avito\Export\Trading\Action\SendMarking;

use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $codes;
	protected $externalId;
	protected $externalNumber;
	protected $itemsMapped;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(int $orderId, string $externalId, string $externalNumber, array $codes, bool $itemsMapped = false, bool $userInput = false, bool $alreadySaved = true)
	{
		$this->orderId = $orderId;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
		$this->codes = $codes;
		$this->itemsMapped = $itemsMapped;
		$this->userInput = $userInput;
		$this->alreadySaved = $alreadySaved;
	}

	public function orderId() : int
	{
		return $this->orderId;
	}

	public function codes() : array
	{
		return $this->codes;
	}

	public function externalId() : string
	{
		return $this->externalId;
	}

	public function externalNumber() : string
	{
		return $this->externalNumber;
	}

	public function itemsMapped() : bool
	{
		return $this->itemsMapped;
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