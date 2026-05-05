<?php

namespace Avito\Export\Feed\Exception;

use Bitrix\Main;
use Avito\Export\Feed\Engine\Steps;

class TimeExpired extends Main\SystemException
{
	protected $step;
	protected $offset;

	public function __construct(Steps\Step $step, string $offset = null)
	{
		parent::__construct('time is expired');

		$this->step = $step;
		$this->offset = $offset;
	}

	public function getStep() : Steps\Step
	{
		return $this->step;
	}

	public function getOffset() : ?string
	{
		return $this->offset;
	}
}
