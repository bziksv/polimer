<?php
namespace Avito\Export\Trading\Action\SendStatus;

use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $status;
	protected $externalId;
	protected $externalNumber;
	protected $transition;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(int $orderId, string $externalId, string $externalNumber, string $status = null, string $transition = null, bool $userInput = false, bool $alreadySaved = true)
	{
		$this->orderId = $orderId;
		$this->status = $status;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
		$this->transition = $transition;
		$this->userInput = $userInput;
		$this->alreadySaved = $alreadySaved;
	}

	public function orderId() : int
	{
		return $this->orderId;
	}

	public function status() : ?string
	{
		return $this->status;
	}

	public function externalId() : string
	{
		return $this->externalId;
	}

	public function externalNumber() : string
	{
		return $this->externalNumber;
	}

	public function transition() : ?string
	{
		return $this->transition;
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