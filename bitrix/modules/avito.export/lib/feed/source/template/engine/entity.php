<?php

namespace Avito\Export\Feed\Source\Template\Engine;

use Bitrix\Iblock\Template;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class Entity extends Template\Entity\Base
{
	private $elementValues;

	public function __construct(array $elementValues)
	{
		parent::__construct(0);
		$this->elementValues = $elementValues;
	}

	public function getFieldValue(string $sourceName, string $fieldName)
	{
		return $this->elementValues[$sourceName][$fieldName] ?? null;
	}
}