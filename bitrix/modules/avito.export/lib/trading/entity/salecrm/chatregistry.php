<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Avito\Export\Concerns;
use Bitrix\Main;
use Bitrix\Im;
use Bitrix\ImOpenLines;
use Bitrix\ImConnector;

class ChatRegistry
{
	use Concerns\HasOnce;

	public const CONNECTOR_ID = 'avito';

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function supports() : bool
	{
		if (!Main\Loader::includeModule('imconnector')) { return false; }

		$connectors = ImConnector\Connector::getListConnectorMenu(true);

		return isset($connectors[self::CONNECTOR_ID]);
	}

	public function configured(int $serviceId) : bool
	{
		$lineIds = $this->lines($serviceId);

		return !empty($lineIds);
	}

	public function createConfig() : ?int
	{
		if (!Main\Loader::includeModule('imopenlines')) { return false; }

		$userPermissions = ImOpenlines\Security\Permissions::createWithCurrentUser();

		if (!$userPermissions->canPerform(
			ImOpenlines\Security\Permissions::ENTITY_LINES,
			ImOpenlines\Security\Permissions::ACTION_MODIFY
		))
		{
			return null;
		}

		if(!ImOpenLines\Config::canActivateLine()) { return null; }

		$configManager = new ImOpenLines\Config();

		return $configManager->create();
	}

	public function search(int $serviceId, string $chatId) : ?Chat
	{
		$lineIds = $this->lines($serviceId);

		foreach ($lineIds as $lineId)
		{
			if ($chat = $this->searchChat($lineId, $chatId))
			{
				return new Chat($this->environment, $chat);
			}
		}

		return null;
	}

	public function wakeup(ImOpenLines\Chat $chat) : Chat
	{
		return new Chat($this->environment, $chat);
	}

	protected function searchChat(int $lineId, string $chatId) : ?ImOpenLines\Chat
	{
		if (!Main\Loader::includeModule('im') || !Main\Loader::includeModule('imopenlines')) { return null; }

		$userCode = implode('|', [ self::CONNECTOR_ID, $lineId, $chatId, '%' ]);

		$row = Im\Model\ChatTable::getRow([
			'filter' => [
				'=ENTITY_TYPE' => ImOpenLines\Chat::CHAT_TYPE_OPERATOR,
				'%=ENTITY_ID' => $userCode,
			],
			'select' => ['ID'],
		]);

		if ($row === null) { return null; }

		return new ImOpenLines\Chat($row['ID']);
	}

	protected function lines(int $serviceId) : array
	{
		return $this->once('lines', function(int $serviceId) {
			if (!Main\Loader::includeModule('imconnector')) { return []; }

			$result = [];

			$query = ImConnector\Model\StatusConnectorsTable::getList([
				'filter' => [
					'=CONNECTOR' => self::CONNECTOR_ID,
					'=ACTIVE' => true,
				],
				'select' => [ 'LINE' ],
			]);

			while ($status = $query->fetch())
			{
				$infoConnector = ImConnector\Connector::infoConnectorsLine($status['LINE']);
				$connectorService = $infoConnector[self::CONNECTOR_ID]['RESULT']['id'] ?? null;

				if ((string)$connectorService === (string)$serviceId)
				{
					$result[] = (int)$status['LINE'];
				}
			}

			return $result;
		}, $serviceId);
	}
}