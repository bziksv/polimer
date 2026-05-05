<?php
namespace Avito\Export\Trading\Action\SendSetCourierDeliveryRange;

use Avito\Export\Trading\Action\Reference as TradingReference;
use Bitrix\Main\Type;

class Command implements TradingReference\Command
{
	protected $orderId;
	protected $externalId;
	protected $externalNumber;
    protected $address;
    protected $addressDetails;
    protected $startDate;
    protected $endDate;
    protected $intervalType;
    protected $senderName;
    protected $phone;
	protected $userInput;
	protected $alreadySaved;

	public function __construct(
        int $orderId,
        string $externalId,
		string $externalNumber,
		string $address,
        string $addressDetails,
        Type\DateTime $startDate,
        Type\DateTime $endDate,
        string $intervalType,
        string $senderName,
        string $phone,
        bool $userInput = false,
        bool $alreadySaved = true
    )
	{
		$this->orderId = $orderId;
		$this->externalId = $externalId;
		$this->externalNumber = $externalNumber;
        $this->address = $address;
        $this->addressDetails = $addressDetails;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->intervalType = $intervalType;
        $this->senderName = $senderName;
        $this->phone = $phone;
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

    public function address() : string
    {
        return $this->address;
    }

    public function addressDetails() : string
    {
        return $this->addressDetails;
    }

    public function startDate(): Type\DateTime
    {
        return $this->startDate;
    }

    public function endDate(): Type\DateTime
    {
        return $this->endDate;
    }

    public function intervalType(): string
    {
        return $this->intervalType;
    }

    public function senderName() : string
    {
        return $this->senderName;
    }

    public function phone() : string
    {
        return $this->phone;
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