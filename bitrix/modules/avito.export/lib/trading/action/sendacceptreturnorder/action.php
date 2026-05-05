<?php
namespace Avito\Export\Trading\Action\SendAcceptReturnOrder;

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

	public const MARK_CODE = 'AVITO_SEND_ACCEPT_RETURN_ORDER';

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
		$client = new Api\OrderManagement\V1\Order\AcceptReturnOrder\Request();
		$client->token($this->settings->commonSettings()->token());
		$client->orderId($this->command->externalId());
		$client->recipient($this->command->recipientName(), $this->command->recipientPhone());
		$client->terminalNumber($this->command->terminalNumber());

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

	protected function memoize() : void
	{
		$values = [
			'recipientPhone' => $this->command->recipientPhone(),
			'recipientName' => $this->command->recipientName(),
			'terminalNumber' => $this->command->terminalNumber(),
		];

		$this->state->set('ACCEPT_RETURN_ORDER', $values);
		\CUserOptions::SetOption('AVITO_EXPORT', 'ACCEPT_RETURN_ORDER', $values);
	}

	protected function log() : void
	{
		$this->logger->info(self::getLocale('LOG_SUCCESS', [
			'#TERMINAL_NUMBER#' => $this->command->terminalNumber(),
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