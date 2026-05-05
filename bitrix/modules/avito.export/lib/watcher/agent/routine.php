<?php

namespace Avito\Export\Watcher\Agent;

use Avito\Export\DB;

class Routine
{
	public static function removeState(string $setupType, int $setupId, string $method) : void
	{
		$batch = new DB\Facade\BatchDelete(StateTable::class);

		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => $setupType,
				'=SETUP_ID' => $setupId,
				'=METHOD' => $method,
			],
		]);
	}
}
