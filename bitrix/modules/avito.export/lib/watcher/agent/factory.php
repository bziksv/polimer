<?php
namespace Avito\Export\Watcher\Agent;

use Avito\Export\Feed;
use Avito\Export\Push;
use Avito\Export\Glossary;
use Bitrix\Main;

class Factory
{
	public static function makeProcessor(string $method, string $setupType, int $setupId) : Processor
	{
		if ($setupType === Glossary::SERVICE_FEED)
		{
			$result = new Feed\Agent\FeedProcessor($method, $setupId);
		}
		else if ($setupType === Glossary::SERVICE_PUSH)
		{
			$result = new Push\Agent\PushProcessor($method, $setupId);
		}
		else
		{
			throw new Main\ArgumentException($setupType);
		}

		return $result;
	}

	/**
	 * @param string $setupType
	 * @param int $setupId
	 *
	 * @return Feed\Setup\Model|Push\Setup\Model
	 */
	public static function loadSetup(string $setupType, int $setupId)
	{
		if ($setupType === Glossary::SERVICE_FEED)
		{
			$result = Feed\Setup\Model::getById($setupId);
		}
		else if ($setupType === Glossary::SERVICE_PUSH)
		{
			$result = Push\Setup\Model::getById($setupId);
		}
		else
		{
			throw new Main\ArgumentException($setupType);
		}

		return $result;
	}
}