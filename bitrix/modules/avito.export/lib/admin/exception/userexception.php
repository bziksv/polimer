<?php
namespace Avito\Export\Admin\Exception;

use Bitrix\Main\SystemException;

class UserException extends SystemException
{
	private $details;

	public function __construct(string $message = "", string $details = null)
	{
		parent::__construct($message);
		$this->details = $details;
	}

	public function getDetails() : ?string
	{
		return $this->details;
	}
}