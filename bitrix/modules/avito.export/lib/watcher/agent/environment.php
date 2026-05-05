<?php
namespace Avito\Export\Watcher\Agent;

class Environment
{
	protected $globals = [];

	public function prepare() : void
	{
		$this->sanitizeUser();
	}

	protected function sanitizeUser() : void
	{
		if (isset($GLOBALS['USER']) && !($GLOBALS['USER'] instanceof \CUser))
		{
			$this->globals['USER'] = $GLOBALS['USER'];
			unset($GLOBALS['USER']);
		}
	}

	public function rollback() : void
	{
		$this->rollbackGlobals();
	}

	protected function rollbackGlobals() : void
	{
		foreach ($this->globals as $name => $value)
		{
			$this->globals[$name] = $value;
		}
	}
}