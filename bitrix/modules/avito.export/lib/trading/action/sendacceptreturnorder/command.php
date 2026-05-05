<?php
namespace Avito\Export\Trading\Action\SendAcceptReturnOrder;

use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $externalId;
	protected $externalNumber;
	protected $recipientName;
	protected $recipientPhone;
	protected $terminalNumber;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(int $orderId, string $externalId, string $externalNumber, string $recipientName, string $recipientPhone, string $terminalNumber, bool $userInput = false, bool $alreadySaved = true)
	{
		$this->orderId = $orderId;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
		$this->recipientName = $recipientName;
		$this->recipientPhone = $recipientPhone;
		$this->terminalNumber = $terminalNumber;
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

	public function recipientName() : string
	{
		return $this->recipientName;
	}
	public function recipientPhone() : string
	{
		return $this->recipientPhone;
	}

	public function terminalNumber() : string
	{
		return $this->terminalNumber;
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