<?php
namespace Avito\Export\Trading\Entity\Sale\Internals;

use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale')) { return; }

class Platform extends Sale\TradingPlatform\Platform
{
	public function installExtended(array $fields = []) : Main\ORM\Data\AddResult
	{
		$defaults = [
			'CODE' => $this->getCode(),
			'ACTIVE' => 'N',
			'SETTINGS' => '',
			'CLASS' => '\\' . static::class,
		];
		$fields = $defaults + array_intersect_key($fields, [
			'NAME' => true,
			'DESCRIPTION' => true,
		]);

		$addResult = Sale\TradingPlatformTable::add($fields);

		if ($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
			$this->isInstalled = true;

			self::$instances[$this->getCode()] = $this;
		}

		return $addResult;
	}

	public function install() : ?int
	{
		return $this->installExtended()->getId();
	}
}