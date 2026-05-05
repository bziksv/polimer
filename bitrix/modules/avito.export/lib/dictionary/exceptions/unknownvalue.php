<?php
namespace Avito\Export\Dictionary\Exceptions;

use Bitrix\Main;
use Avito\Export\Concerns;

class UnknownValue extends Main\SystemException
{
	use Concerns\HasLocale;

	protected $attributeName;
	protected $attributeValue;

	public function __construct(string $attributeName, $attributeValue)
	{
		parent::__construct(self::getLocale('MESSAGE',[
			'#NAME#' => $attributeName,
			'#VALUE#' => $attributeValue
		]));

		$this->attributeName = $attributeName;
		$this->attributeValue = (string)$attributeValue;
	}

	public function attributeName() : string
	{
		return $this->attributeName;
	}

	public function attributeValue() : string
	{
		return $this->attributeValue;
	}
}