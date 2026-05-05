<?php
namespace Avito\Export\Watcher\Agent;

use Avito\Export\Agent;
use Avito\Export\Watcher;
use Bitrix\Main;

class Refresh extends Agent\Base
{
	public static function start(string $setupType, int $setupId) : void
	{
		static::normalizeRefreshStartPeriod($setupType, $setupId);
		static::register([
			'method' => 'process',
			'arguments' => [ $setupType, $setupId ],
			'interval' => 5,
		]);
	}

	protected static function normalizeRefreshStartPeriod(string $setupType, int $setupId) : void
	{
		global $pPERIOD;

		try
		{
			$feed = Factory::loadSetup($setupType, $setupId);

			if ($feed->hasFullRefresh() && $feed->hasRefreshTime())
			{
				$now = new Main\Type\DateTime();
				$nextExec = $feed->getRefreshNextExec();

				$pPERIOD = $nextExec->getTimestamp() - $now->getTimestamp();
			}
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			// nothing
		}
	}

	public static function process(string $setupType, int $setupId) : bool
	{
		$processor = Watcher\Agent\Factory::makeProcessor('refresh', $setupType, $setupId);

		return $processor->run(Watcher\Engine\Controller::ACTION_REFRESH);
	}
}
