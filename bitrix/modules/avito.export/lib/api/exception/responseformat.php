<?php
namespace Avito\Export\Api\Exception;

use Bitrix\Main;

class ResponseFormat extends Main\SystemException
{
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}