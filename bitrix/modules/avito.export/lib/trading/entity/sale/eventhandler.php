<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Push;
use Avito\Export\Event;
use Bitrix\Main;

abstract class EventHandler extends Event\Base
{
	public function install() : void
	{
		foreach ($this->handlers() as $handler)
		{
			static::register($handler);
		}
	}

	public function uninstall() : void
	{
		foreach ($this->handlers() as $handler)
		{
			static::unregister($handler);
		}
	}

	public function handlers() : array
	{
		throw new Main\NotImplementedException();
	}
}