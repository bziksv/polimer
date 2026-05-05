<?php
namespace Avito\Export\Admin\Page;

use Bitrix\Main;
use Avito\Export\Chat;
use Avito\Export\Concerns;
use Avito\Export\Exchange;
use Avito\Export\Assert;
use Avito\Export\Glossary;

class ChatCallback extends Page
{
	use Concerns\HasOnce;

	public function handle() : void
	{
		$logger = null;

		try
		{
			$exchange = $this->exchange();
			$logger = $exchange->getChat()->makeLogger();

			if (!$exchange->getUseChat())
			{
				throw new Main\SystemException(self::getLocale('USE_CHAT_DISABLED'));
			}

			$payload = $this->payload();

			$this->save($exchange, $payload);
			$this->installViewer();
			$this->installActualizer($exchange);
			$this->checkDevelopment($exchange);
		}
		catch (\Throwable $exception)
		{
			if ($logger === null) { throw $exception; }

			$logger->error($exception, [
				'ENTITY_TYPE' => Glossary::ENTITY_MESSAGE,
			]);
		}
	}

	protected function exchange() : Exchange\Setup\Model
	{
		$setupId = $this->request->get('setupId');

		Assert::notNull($setupId, 'request[setupId]');

		return Exchange\Setup\Model::getById($setupId);
	}

	protected function payload() : array
	{
		$requestBody = Main\HttpRequest::getInput();

		if (!is_string($requestBody) || $requestBody === '')
		{
			throw new Main\SystemException(self::getLocale('CANT_GET_REQUEST_BODY'));
		}

		$requestData = Main\Web\Json::decode($requestBody);
		$payload = $requestData['payload']['value'] ?? null;

		Assert::notNull($payload, 'request[payload][value]');

		return $payload;
	}

	protected function save(Exchange\Setup\Model $exchange, array $payload) : void
	{
		$primary = [
			'EXTERNAL_ID' => $payload['id'],
		];
		$fields = [
			'SETUP_ID' => $exchange->getId(),
			'CHAT_ID' => $payload['chat_id'],
			'AUTHOR_ID' => $payload['author_id'],
			'CHAT_TYPE' => $payload['chat_type'],
			'CONTENT' => $payload['content'],
			'CREATED' => new Main\Type\DateTime(),
			'ITEM_ID' => (int)$payload['item_id'],
			'TYPE' => $payload['type'],
			'USER_ID' => $payload['user_id'],
		];

		$queryExists = Chat\Unread\MessageTable::getList([
			'filter' => [ '=EXTERNAL_ID' => $primary['EXTERNAL_ID'] ],
			'limit' => 1,
		]);

		if ($queryExists->fetch())
		{
			$saveResult = Chat\Unread\MessageTable::update($primary, $fields);
		}
		else
		{
			$saveResult = Chat\Unread\MessageTable::add($primary + $fields);
		}

		Assert::result($saveResult);
	}

	protected function installViewer() : void
	{
		Chat\Informer\Viewer::install();
	}

	protected function installActualizer(Exchange\Setup\Model $exchange) : void
	{
		Chat\Informer\Actualizer::install($exchange->getId());
	}

	protected function checkDevelopment(Exchange\Setup\Model $exchange) : void
	{
		Chat\Setup\DevelopmentBlocker::check($exchange->getId());
	}
}