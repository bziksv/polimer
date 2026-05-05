<?php

namespace Avito\Export\Watcher\Exception;

use Bitrix\Main;

class LockFailed extends Main\SystemException
{
	public function __construct()
	{
		parent::__construct('lock failed');
	}
}
