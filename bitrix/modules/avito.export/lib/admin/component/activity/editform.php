<?php

namespace Avito\Export\Admin\Component\Activity;

use Avito\Export;
use Avito\Export\Concerns;
use Bitrix\Main;
use Avito\Export\Api;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity\Reference as TradingReference;

class EditForm extends Export\Admin\Component\Plain\EditForm
{
	use Concerns\HasOnce;

	protected $saleOrder;
	/** @var Api\OrderManagement\Model\Order */
	protected $externalOrder;

	public function prepareComponentParams($params)
	{
		return $params;
	}

	public function load($primary, array $select = [], $isCopy = false) : array
	{
		$this->saleOrder = $this->loadSaleOrder($primary);
		$this->externalOrder = $this->loadExternalOrder();

		return $this->activity()->values($this->saleOrder, $this->externalOrder);
	}

	protected function loadSaleOrder($primary) : Trading\Entity\Sale\Order
	{
		$order = $this->setup()->getEnvironment()->orderRegistry()->load((int)$primary);

		Export\Assert::notNull($order, 'saleOrder');

		return $order;
	}

	protected function loadExternalOrder() : Api\OrderManagement\Model\Order
	{
		return Api\OrderManagement\V1\Orders\Facade::cachedById($this->setup(), $this->externalId());
	}

	public function add(array $fields) : Main\ORM\Data\AddResult
	{
		throw new Main\NotSupportedException();
	}

	public function update($primary, array $fields) : Main\ORM\Data\UpdateResult
	{
		$result = new Main\ORM\Data\UpdateResult();

		try
		{
			Export\Assert::notNull($this->saleOrder, 'saleOrder');
			Export\Assert::notNull($this->externalOrder, 'externalOrder');

			$activity = $this->activity();
			$payload = $activity->payload($fields);
			$payload += [
				'orderId' => $primary,
				'externalId' => $this->externalId(),
				'externalNumber' => $this->externalNumber(),
				'userInput' => true,
			];

			$procedure = new Trading\Action\Procedure(
				$this->setup(),
				$activity->path(),
				$payload
			);

			$procedure->run();

			if ($procedure->needSync())
			{
				Trading\Action\Facade::syncOrder($this->setup(), $primary);
			}
		}
		catch (\Throwable $exception)
		{
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	protected function getAllFields() : array
	{
		return $this->once('getAllFields', function() {
			Export\Assert::notNull($this->saleOrder, 'saleOrder');
			Export\Assert::notNull($this->externalOrder, 'externalOrder');

			$fields = $this->activity()->fields($this->saleOrder, $this->externalOrder);
			$fields = $this->extendFields($fields);

			return $fields;
		});
	}

	protected function activity() : TradingReference\FormActivity
	{
		return $this->once('activity', function() {
			$activity = $this->getComponentParam('TRADING_ACTIVITY');

			Export\Assert::typeOf($activity, TradingReference\FormActivity::class, 'TRADING_ACTIVITY');

			return $activity;
		});
	}

	protected function setup() : Trading\Setup\Model
	{
		$setup = $this->getComponentParam('TRADING_SETUP');

		Export\Assert::typeOf($setup, Trading\Setup\Model::class, 'TRADING_SETUP');

		return $setup;
	}

	protected function externalId() : int
	{
		$externalId = $this->getComponentParam('EXTERNAL_ID');

		Export\Assert::isNumber($externalId, 'EXTERNAL_ID');

		return (int)$externalId;
	}

	protected function externalNumber() : string
	{
		$externalNumber = $this->getComponentParam('EXTERNAL_NUMBER');

		Export\Assert::isString($externalNumber, 'EXTERNAL_NUMBER');

		return $externalNumber;
	}
}
