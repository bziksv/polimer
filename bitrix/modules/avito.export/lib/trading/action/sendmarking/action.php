<?php
namespace Avito\Export\Trading\Action\SendMarking;

use Avito\Export\Assert;
use Avito\Export\Api;
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

	public const MARK_CODE = 'AVITO_SEND_MARKING';

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

	protected function submit() : void
	{
		$client = new Api\OrderManagement\V1\Markings\Request();
		$client->token($this->settings->commonSettings()->token());
		$client->markings($this->prepareCodes($this->command->codes()));

		$this->validateResponse($client->execute());
	}

	protected function prepareCodes(array $codes) : array
	{
		$result = [];

		foreach ($codes as $basketCode => $itemCodes)
		{
			$itemId = $this->command->itemsMapped()
				? $basketCode
				: $this->order->itemExternalId($basketCode);

			Assert::notNull($itemId, 'itemId');

			$result[] = [
				'itemId' => (string)$itemId,
				'markings' => array_map(static function(string $itemCode) {
					return Data\MarkingCode::sanitize($itemCode);
				}, $itemCodes),
				'orderId' => $this->command->externalId(),
			];
		}

		return $result;
	}

	protected function validateResponse(Api\OrderManagement\V1\Markings\Response $response) : void
	{
		$errors = [];

		/** @var Api\OrderManagement\Model\Marking\Result $result */
		foreach ($response->results() as $result)
		{
			if ($result->success()) { continue; }

			$errors[] = $result->error();
		}

		if (empty($errors)) { return; }

		throw new Main\SystemException(implode(' / ', $errors));
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
		$codesQuantity = 0;

		foreach ($this->command->codes() as $codes)
		{
			$codesQuantity += count($codes);
		}

		$this->logger->info(self::getLocale('LOG_SUCCESS', [
			'#QUANTITY#' => $codesQuantity,
		]), [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->command->externalNumber(),
		]);
	}
}