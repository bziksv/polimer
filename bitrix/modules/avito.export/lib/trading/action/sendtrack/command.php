<?php
namespace Avito\Export\Trading\Action\SendTrack;

use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $trackingNumber;
	protected $externalId;
	protected $externalNumber;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(int $orderId, string $trackingNumber, string $externalId, string $externalNumber, bool $userInput = false, bool $alreadySaved = true)
	{
		$this->orderId = $orderId;
		$this->trackingNumber = $trackingNumber;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
		$this->userInput = $userInput;
		$this->alreadySaved = $alreadySaved;
	}

	public function orderId() : int
	{
		return $this->orderId;
	}

	public function trackingNumber() : string
	{
		return $this->trackingNumber;
	}

	public function externalId() : string
	{
		return $this->externalId;
	}

	public function externalNumber() : string
	{
		return $this->externalNumber;
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