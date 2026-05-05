<?php
namespace Avito\Export\Watcher\Setup;

use Avito\Export\Watcher;
use Bitrix\Main;

/**
 * @method string watcherType()
 */
trait HasModelChanges
{
	public function handleChanges(bool $direction): void
	{
		$watcher = new Watcher\Watcher($this->watcherType(), $this->getId());

		if ($direction)
		{
			$this->bindChanges($watcher);

			$watcher->flush();
		}
		else
		{
			$watcher->flush();

			Watcher\Agent\Routine::removeState($this->watcherType(), $this->getId(), 'change');
			Watcher\Engine\Changes::releaseAll($this->watcherType(), $this->getId());
		}
	}

	protected function bindChanges(Watcher\Watcher $watcher) : void
	{
		throw new Main\NotImplementedException();
	}
}