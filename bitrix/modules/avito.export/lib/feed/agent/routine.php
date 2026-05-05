<?php

namespace Avito\Export\Feed\Agent;

use Avito\Export\DB;

class Routine
{
	public static function removeState(int $feedId, string $method) : void
	{
		$batch = new DB\Facade\BatchDelete(StateTable::class);

		$batch->run([
			'filter' => [
				'=FEED_ID' => $feedId,
				'=METHOD' => $method,
			],
		]);

	}
}
