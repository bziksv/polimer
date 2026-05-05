<?php
namespace Avito\Export\Trading\Action\SendSetCourierDeliveryRange;

use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Data;
use Avito\Export\Glossary;
use Avito\Export\Trading;
use Avito\Export\Trading\Entity\Sale as TradingEntity;
use Bitrix\Main;

class Action extends Trading\Action\Reference\Action
{
	use Concerns\HasLocale;
	use Trading\Action\Reference\Concerns\HasChanges;

	public const MARK_CODE = 'AVITO_SEND_COURIER_DELIVERY_RANGE';

	/** @var Command */
	protected $command;
	/** @var TradingEntity\Order $order */
	protected $order;
	protected $state;

	public function __construct(Trading\Setup\Model $trading, Command $command)
	{
		parent::__construct($trading, $command);

		$this->state = Trading\State\Repository::forOrder($command->externalId());
	}

	public function process() : void
	{
		try
		{
			$this->load();
			$this->submit();
            $this->unmark();
            $this->save();
            $this->memoize();
            $this->log();
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

	protected function load() : void
	{
		$this->order = $this->environment->orderRegistry()->load($this->command->orderId());
	}

	protected function submit() : void
	{
		$client = new Api\OrderManagement\V1\Order\SetCourierDeliveryRange\Request();
		$client->token($this->settings->commonSettings()->token());
		$client->orderId($this->command->externalId());

        $client->address($this->command->address());
        $client->addressDetails($this->command->addressDetails());
        $client->startDate($this->command->startDate());
        $client->endDate($this->command->endDate());
        $client->intervalType($this->command->intervalType());
        $client->name($this->command->senderName());
        $client->phone($this->command->phone());

		$client->execute();
	}

    protected function memoize() : void
    {
        $values = [
            'address' => $this->command->address(),
            'addressDetails' => $this->command->addressDetails(),
            'senderName' => $this->command->senderName(),
            'phone' => $this->command->phone(),
        ];

	    $this->state->set('COURIER_DELIVERY_RANGE', $values);
        \CUserOptions::SetOption('AVITO_EXPORT', 'COURIER_DELIVERY_RANGE', $values);
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
		$this->logger->info(self::getLocale('LOG_SUCCESS', [
			'#ADDRESS#' => $this->command->address(),
            '#PERIOD#' => Data\DateTimePeriod::format($this->command->startDate(), $this->command->endDate()),
		]), [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->command->externalNumber(),
		]);
	}

	protected function commit() : void
	{
		$this->state->save();
	}
}