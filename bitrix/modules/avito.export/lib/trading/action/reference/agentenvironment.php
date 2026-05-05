<?php
namespace Avito\Export\Trading\Action\Reference;

class AgentEnvironment
{
	protected $globalUser;

	public function wake() : void
	{
		$this->wakeUser();
	}

	public function restore() : void
	{
		$this->restoreUser();
	}

	protected function wakeUser() : void
	{
		$globalUser = $GLOBALS['USER'] ?? null;

		if ($globalUser instanceof \CUser)
		{
			$this->globalUser = null;
			return;
		}

		$this->globalUser = $globalUser;
		$GLOBALS['USER'] = new Internals\DummyUser();
	}

	protected function restoreUser() : void
	{
		if ($this->globalUser === null) { return; }

		$GLOBALS['USER'] = $this->globalUser;
	}
}