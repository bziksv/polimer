<?php
namespace Avito\Export\Utils;

use Bitrix\Main;

class Agent
{
	public static function cronConfigured() : bool
	{
		return (
			Main\Config\Option::get('main', 'agents_use_crontab', 'N') === 'Y' // agents use crontab
			|| Main\Config\Option::get('main', 'check_agents', 'Y') !== 'Y' // auto call agents disabled
			|| (defined('BX_CRONTAB_SUPPORT') && BX_CRONTAB_SUPPORT === true)
		);
	}

	/** @noinspection PhpUndefinedConstantInspection */
	public static function nowCli() : bool
	{
		if (defined('BX_CRONTAB') && BX_CRONTAB === true)
		{
			$result = true;
		}
		else if (defined('CHK_EVENT') && CHK_EVENT === true)
		{
			$result = true;
		}
		else
		{
			$result = (mb_strrpos(PHP_SAPI, 'cli') === 0);
		}

		return $result;
	}

	public static function nextExec(int $interval, int $hour, int $minutes) : string
	{
		$dateTime = new Main\Type\DateTime();
		$dateTime->add(sprintf('PT%sS', $interval));
		$dateTime->setTime($hour, $minutes);

		if ($dateTime->getTimestamp() <= time())
		{
			$dateTime->add('P1D');
		}

		return ConvertTimeStamp($dateTime->getTimestamp(), 'FULL');
	}
}