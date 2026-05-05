<?php
namespace Avito\Export\Trading\Action\OrderStatus;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Data;
use Avito\Export\Glossary;
use Avito\Export\Trading;
use Avito\Export\Trading\Action\Reference as TradingReference;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class Action extends TradingReference\Action
{
	use Concerns\HasLocale;
	use TradingReference\Concerns\HasChanges;

	public const MARK_CODE = 'AVITO_ORDER_STATUS';

	/** @var Command $command */
	protected $command;
	/** @var Trading\Entity\Sale\Order $order */
	protected $order;
	protected $orderId;
	protected $state;
	protected $storedStatus;

	public function __construct(Trading\Setup\Model $trading, Command $command)
	{
		parent::__construct($trading, $command);

		$this->state = Trading\State\Repository::forOrder($command->order()->id());
	}

	public function process() : void
	{
		try
		{
			if (!$this->find() || !$this->updated()) { return; }

			$this->load();
			$this->holdStatus();
			$this->fillPaid();
			$this->fillStatus();
			$this->fillTrackingNumber();
			$this->fillBuyerProperties();
			$this->fillScheduleProperties();
			$this->fillDeliveryDispatchNumber();
			$this->fillContact();
			$this->unmark();
			$this->save();
			$this->commit();
		}
		catch (Main\SystemException $exception)
		{
			if ($this->order === null) { throw $exception; }

			$this->mark($exception->getMessage());
			$this->save();

			throw $exception;
		}
	}

	protected function find() : bool
	{
		$this->orderId = $this->environment->orderRegistry()->search($this->command->order()->id());

		return $this->orderId !== null;
	}

	protected function updated() : bool
	{
		$updated = $this->command->order()->updatedAt();
		$stored = Data\DateTime::cast($this->state->get('UPDATED_AT'));

		if ($stored !== null && Data\DateTime::compare($stored, $updated) !== -1) { return false; }

		$this->state->set('UPDATED_AT', Data\DateTime::stringify($updated));

		return true;
	}

	protected function load() : void
	{
		$this->order = $this->environment->orderRegistry()->load($this->orderId);

		Assert::notNull($this->order, 'order');
	}

	protected function holdStatus() : void
	{
		$status = $this->command->order()->status();
		$stored = $this->state->get('STATUS');

		if ($stored !== $status)
		{
			$this->state->set('STATUS', $status); // commit status for send/status ignore
		}

		$this->storedStatus = $stored;
	}

	protected function fillPaid() : void
	{
		if (!$this->isPaid()) { return; }
		if (!$this->state->changed('PAID', 'Y')) { return; }

		$this->applyStatus(Trading\Service\Status::VIRTUAL_PAID);
		$this->state->set('PAID', 'Y');
	}

	protected function isPaid() : bool
	{
		$status = $this->command->order()->status();

		if ($status === Trading\Service\Status::STATUS_ON_CONFIRMATION)
		{
			$actions = $this->command->order()->availableActions();

			$result = ($actions !== null && $actions->has('confirm'));
		}
		else
		{
			$deliveryQueue = $this->service->status()->deliveryQueue();
			$confirmationIndex = array_search(Trading\Service\Status::STATUS_ON_CONFIRMATION, $deliveryQueue, true);
			$statusIndex = array_search($status, $deliveryQueue, true);

			$result = ($statusIndex !== false && $statusIndex > $confirmationIndex);
		}

		return $result;
	}

	protected function fillStatus() : void
	{
		$status = $this->command->order()->status();
		$stored = $this->storedStatus;
		$queue = $this->service->status()->statusQueue($status, $stored);

		foreach ($queue as $queueStatus)
		{
			if ($this->needCommitStatus($queueStatus)) { continue; } // wait closed

			if ($queueStatus === Trading\Service\Status::STATUS_CLOSED && $stored !== null && $this->needCommitStatus($stored))
			{
				$queueStatus = $stored;
			}

			$this->applyStatus($queueStatus);
		}

		if ($stored !== $status)
		{
			$this->logger->info(self::getLocale('LOG_STATUS', [
				'#STATUS#' => $status,
			]), [
				'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
				'ENTITY_ID' => $this->command->order()->number(),
			]);
		}
	}

	protected function needCommitStatus(string $status) : bool
	{
		$deliveryQueue = $this->service->status()->deliveryQueue();
		$returnQueue = $this->service->status()->returnQueue();

		return (
			$status === end($deliveryQueue)
			|| $status === end($returnQueue)
		);
	}

	protected function applyStatus(string $externalStatus) : void
	{
        foreach ($this->settings->statusIn($externalStatus) as $status)
        {
            $setResult = $this->order->fillStatus($status);

            Assert::result($setResult);

            $this->testChanged($setResult);
        }
	}

	protected function fillTrackingNumber() : void
	{
		$delivery = $this->command->order()->delivery();

		if ($delivery->serviceType() !== Trading\Service\Delivery::TYPE_PVZ) { return; }

		$trackingNumber = $delivery->trackingNumber();

		if ($trackingNumber === null || !$this->state->changed('TRACKING_NUMBER', $trackingNumber)) { return; }

		$setResult = $this->order->fillTrackingNumber($trackingNumber);

		Assert::result($setResult);

		$this->state->set('TRACKING_NUMBER', $trackingNumber);
		$this->testChanged($setResult);

		$this->logger->info(self::getLocale('LOG_TRACKING_NUMBER', [
			'#TRACK#' => $trackingNumber,
		]), [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->command->order()->number(),
		]);
	}

	protected function fillBuyerProperties() : void
	{
		$buyerInfo = $this->command->order()->delivery()->buyerInfo();

		if ($buyerInfo === null) { return; }

		$values = [
			'DELIVERY_NAME' => $buyerInfo->fullName(),
			'DELIVERY_PHONE' => $buyerInfo->phoneNumber(),
		];

		if (!$this->state->hashChanged('BUYER', $values)) { return; }

		$setResult = $this->order->fillProperties($values, $this->settings);
		$this->state->setHash('BUYER', $values);

		$this->testChanged($setResult);
	}

	protected function fillContact() : void
	{
		if (
			!($this->order instanceof Trading\Entity\SaleCrm\Order)
			|| !($this->environment instanceof Trading\Entity\SaleCrm\Container)
			|| $this->command->order()->delivery()->buyerInfo() === null
		)
		{
			return;
		}

		$stage = new Stage\FillContact(
			$this->command->order(),
			$this->order,
			$this->state,
			$this->environment,
			$this->settings
		);

		if ($stage->need())
		{
			$stage->execute();
		}
	}

	protected function fillScheduleProperties() : void
	{
		$values = $this->command->order()->schedules()->meaningfulValues();
		$values = ArrayHelper::prefixKeys($values, 'SCHEDULE_');

		if (!$this->state->hashChanged('SCHEDULE', $values)) { return; }

		$setResult = $this->order->fillProperties($values, $this->settings);
		$this->state->setHash('SCHEDULE', $values);

		$this->testChanged($setResult);
	}

	protected function fillDeliveryDispatchNumber() : void
	{
		$delivery = $this->command->order()->delivery();
		$value = $delivery->dispatchNumber();

		if ($value === null || $delivery->serviceType() !== Trading\Service\Delivery::TYPE_PVZ) { return; }

		if (!$this->state->changed('DELIVERY_DISPATCH_NUMBER', $value)) { return; }

		$setResult = $this->order->fillProperties([
			'DELIVERY_DISPATCH_NUMBER' => $value
		], $this->settings);

		if ($setResult->isSuccess())
		{
			$this->state->set('DELIVERY_DISPATCH_NUMBER', $value);
			$this->testChanged($setResult);
		}
	}

	protected function unmark() : void
	{
		$unmarkResult = $this->order->unmark(static::MARK_CODE);

		$this->testChanged($unmarkResult);
	}

	protected function mark(string $reason) : void
	{
		$markResult = $this->order->mark($reason, static::MARK_CODE);

		$this->testChanged($markResult);
	}

	protected function save() : void
	{
		if (!$this->needSave) { return; }

		$saveResult = $this->order->save();

		Assert::result($saveResult);
	}

	protected function commit() : void
	{
		$this->state->save();
	}
}