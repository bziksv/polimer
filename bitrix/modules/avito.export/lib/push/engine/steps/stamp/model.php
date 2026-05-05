<?php
namespace Avito\Export\Push\Engine\Steps\Stamp;

use Avito\Export\Config;

class Model extends EO_Repository
{
	public static function repeatLimit() : int
	{
		return (int)Config::getOption('push_repeat_limit', 5);
	}

	public function increaseRepeat() : void
	{
		$count = $this->getRepeat() + 1;

		$this->setRepeat($count);

		if ($count > static::repeatLimit())
		{
			$this->setStatus(RepositoryTable::STATUS_FAILED);
		}
	}
}