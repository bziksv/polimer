<?php
namespace Avito\Export\Api\Exception;

use Bitrix\Main;
use Avito\Export\Concerns;

class ParseError extends Main\SystemException
{
	use Concerns\HasLocale;

	public function __construct(\Exception $previous)
	{
		$message = self::getLocale('MESSAGE', [
			'#MESSAGE#' => $previous->getMessage(),
		]);

		parent::__construct($message, 0, '', 0, $previous);
	}
}