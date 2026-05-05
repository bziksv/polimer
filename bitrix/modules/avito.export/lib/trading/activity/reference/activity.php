<?php
namespace Avito\Export\Trading\Activity\Reference;

use Avito\Export\Api;
use Avito\Export\Trading;

abstract class Activity
{
	protected $name;
	protected $service;
	protected $environment;
    protected $exchangeId;

	public function __construct(string $name, Trading\Service\Container $service, Trading\Entity\Sale\Container $environment, int $exchangeId)
	{
		$this->name = $name;
		$this->service = $service;
		$this->environment = $environment;
        $this->exchangeId = $exchangeId;
	}

	abstract public function title(Api\OrderManagement\Model\Order $order) : string;

	public function name() : string
	{
		return $this->name;
	}

	public function order() : int
	{
		return 500;
	}

	public function note(Api\OrderManagement\Model\Order $order) : ?string
	{
		return null;
	}

	public function uiOptions() : ?array
	{
		return [];
	}
}