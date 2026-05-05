<?php
namespace Avito\Export\Trading\Action\SendStatus;

use Avito\Export\Assert;
use Avito\Export\Api;
use Avito\Export\Concerns;
use Avito\Export\Glossary;
use Avito\Export\Trading;
use Bitrix\Main;

class Action extends Trading\Action\Reference\Action
	implements Trading\Action\Reference\ActionMovable
{
	use Concerns\HasLocale;
	use Trading\Action\Reference\Concerns\HasChanges;

	public const MARK_CODE = 'AVITO_SEND_STATUS';

	/** @var Command */
	protected $command;
	protected $state;
	/** @var Trading\Entity\Sale\Order */
	protected $order;
	protected $transition;
	protected $needSync = false;

	public function __construct(Trading\Setup\Model $trading, Command $command)
	{
		parent::__construct($trading, $command);

		$this->state = Trading\State\Repository::forOrder($command->externalId());
	}

	public function needSync() : bool
	{
		return $this->needSync;
	}

	public function process() : void
	{
		try
		{
			if (!$this->mapTransition() || $this->alreadyReached()) { return; }

			$this->load();

			if (!$this->needSend())
			{
				$this->unmark();
				$this->save();
				return;
			}

			if (!$this->canSend()) { return; }

			$this->submit();
			$this->unmark();
			$this->save();
			$this->log();

			$this->needSync = true;
		}
		catch (Main\SystemException $exception)
		{
			if ($this->order === null) { throw $exception; }

			$this->mark($exception->getMessage());
			$this->save();

			throw $exception;
		}
	}

	protected function load() : void
	{
		$this->order = $this->environment->orderRegistry()->load($this->command->orderId());
	}

	protected function mapTransition() : bool
	{
		if ($this->command->transition() !== null)
		{
			$this->transition = $this->command->transition();
		}
		else
		{
			$this->transition = $this->settings->statusOut($this->command->status());
		}

		return $this->transition !== null;
	}

	protected function alreadyReached() : bool
	{
		if ($this->command->userInput()) { return false; }

		$stored = $this->state->get('STATUS');

		if ($stored === null) { return false; }

		if ($this->transition === Trading\Service\Status::TRANSITION_CONFIRM)
		{
			$deliveryQueue = $this->service->status()->deliveryQueue();
			$storedIndex = array_search($stored, $deliveryQueue, true);
			$confirmationIndex = array_search(Trading\Service\Status::STATUS_ON_CONFIRMATION, $deliveryQueue, true);

			$result = ($storedIndex !== false && $storedIndex > $confirmationIndex);
		}
		else if ($this->transition === Trading\Service\Status::TRANSITION_PERFORM)
		{
			$deliveryQueue = $this->service->status()->deliveryQueue();
			$storedIndex = array_search($stored, $deliveryQueue, true);
			$transitIndex = array_search(Trading\Service\Status::STATUS_IN_TRANSIT, $deliveryQueue, true);

			$result = ($storedIndex !== false && $storedIndex >= $transitIndex);
		}
		else if ($this->transition === Trading\Service\Status::TRANSITION_RECEIVE)
		{
			$result = (
				$stored === Trading\Service\Status::STATUS_DELIVERED
				|| $stored === Trading\Service\Status::STATUS_CLOSED
			);
		}
		else if ($this->transition === Trading\Service\Status::TRANSITION_REJECT)
		{
			$result = (
				$stored === Trading\Service\Status::STATUS_CANCELED
				|| $stored === Trading\Service\Status::STATUS_DELIVERED // allow to delete order
				|| $stored === Trading\Service\Status::STATUS_CLOSED // allow to delete order
			);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function needSend() : bool
	{
		if ($this->command->userInput()) { return true; }

		if (
			$this->transition === Trading\Service\Status::TRANSITION_CONFIRM
			&& $this->order->tradingParameter('DELIVERY_TYPE') === Trading\Service\Delivery::TYPE_COURIER
			&& $this->state->get('COURIER_DELIVERY_RANGE') !== null
		)
		{
			return false;
		}

		return true;
	}

	protected function canSend() : bool
	{
		if ($this->command->userInput()) { return true; }

        if (
	        $this->transition === Trading\Service\Status::TRANSITION_CONFIRM
			&& $this->order->tradingParameter('DELIVERY_TYPE') === Trading\Service\Delivery::TYPE_COURIER
        )
        {
            throw new Main\SystemException(self::getLocale('NEED_COURIER_DELIVERY_RANGE'));
        }

		if (in_array($this->transition, [
			Trading\Service\Status::TRANSITION_PERFORM,
			Trading\Service\Status::TRANSITION_RECEIVE,
		], true))
		{
			return ($this->order->tradingParameter('DELIVERY_TYPE') === Trading\Service\Delivery::TYPE_RDBS);
		}

		return true;
	}

	protected function submit() : void
	{
		$client = new Api\OrderManagement\V1\Order\ApplyTransition\Request();
		$client->token($this->settings->commonSettings()->token());
		$client->orderId($this->command->externalId());
		$client->transition($this->transition);

		$this->validateResponse($client->execute());
	}

	protected function validateResponse(Api\OrderManagement\V1\Order\ApplyTransition\Response $response) : void
	{
		if (!$response->success())
		{
			throw new Main\SystemException(self::getLocale('FAIL_MESSAGE'));
		}
	}

	protected function unmark() : void
	{
		$unmarkResult = $this->order->unmark(static::MARK_CODE);

		$this->testChanged($unmarkResult);
	}

	protected function mark(string $reason) : void
	{
		if ($this->command->userInput() || !$this->command->alreadySaved()) { return; }

		$markResult = $this->order->mark($reason, static::MARK_CODE);

		$this->testChanged($markResult);
	}

	protected function save() : void
	{
		if (!$this->needSave) { return; }

		$saveResult = $this->order->save();

		Assert::result($saveResult);
	}

	protected function log() : void
	{
		$message = $this->service->status()->transitionTitle($this->transition, 'ACTION');

		$this->logger->info($message, [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->command->externalNumber(),
		]);
	}
}