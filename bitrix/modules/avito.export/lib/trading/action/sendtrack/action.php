<?php
namespace Avito\Export\Trading\Action\SendTrack;

use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Glossary;
use Avito\Export\Trading;
use Avito\Export\Trading\Entity\Sale as TradingEntity;
use Bitrix\Main;

class Action extends Trading\Action\Reference\Action
{
	use Concerns\HasLocale;
	use Trading\Action\Reference\Concerns\HasChanges;

	public const MARK_CODE = 'AVITO_SEND_TRACK';

	/** @var Command */
	protected $command;
	/** @var TradingEntity\Order $order */
	protected $order;

	public function __construct(Trading\Setup\Model $trading, Command $command)
	{
		parent::__construct($trading, $command);
	}

	public function process() : void
	{
		try
		{
			$this->load();

            if (!$this->canSend()) { return; }

			$this->submit();
            $this->unmark();
            $this->save();
            $this->log();
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

    protected function canSend() : bool
    {
		if ($this->command->userInput()) { return true; }

		return in_array($this->order->tradingParameter('DELIVERY_TYPE'), [
	        Trading\Service\Delivery::TYPE_DBS,
			Trading\Service\Delivery::TYPE_CDBS,
        ], true);
    }

	protected function submit() : void
	{
		$client = new Api\OrderManagement\V1\Order\SetTrackingNumber\Request();
		$client->token($this->settings->commonSettings()->token());
		$client->orderId($this->command->externalId());
		$client->trackingNumber($this->command->trackingNumber());

		$client->execute();
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
			'#TRACKING_NUMBER#' => $this->command->trackingNumber(),
		]), [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->command->externalNumber(),
		]);
	}
}