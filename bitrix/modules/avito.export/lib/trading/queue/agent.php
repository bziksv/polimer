<?php
namespace Avito\Export\Trading\Queue;

use Avito\Export\Agent as AgentReference;

class Agent extends AgentReference\Base
{
	public static function repeat() : bool
	{
		global $pPERIOD;

		$repeater = new Repeater();
		$repeater->processQueue();

		$nearestInterval = $repeater->nearestInterval();
		$result = false;

		if ($nearestInterval !== null)
		{
			$result = true;
			$pPERIOD = $nearestInterval;
		}

		return $result;
	}
}