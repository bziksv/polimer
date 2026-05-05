<?php
namespace Avito\Export\Dictionary\Exceptions;

use Bitrix\Main;
use Avito\Export\Concerns;

class AttributeRequired extends Main\SystemException
{
	use Concerns\HasLocale;

	protected $attributeName;

	public function __construct(string $attributeName)
	{
		parent::__construct(self::getLocale('MESSAGE',[
			'#NAME#' => $attributeName
		]));

		$this->attributeName = $attributeName;
	}

	public function attributeName() : string
	{
		return $this->attributeName;
	}
}