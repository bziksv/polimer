<?php

namespace Avito\Export\Watcher\Exception;

use Bitrix\Main;
use Avito\Export\Watcher\Engine;

class TimeExpired extends Main\SystemException
{
	protected $step;
	protected $offset;

	public function __construct(Engine\Step $step, string $offset = null)
	{
		parent::__construct('time is expired');

		$this->step = $step;
		$this->offset = $offset;
	}

	public function getStep() : Engine\Step
	{
		return $this->step;
	}

	public function getOffset() : ?string
	{
		return $this->offset;
	}
}
