<?php
namespace Avito\Export\Exchange\Setup;

use Avito\Export\Concerns;
use Bitrix\Main;

abstract class SettingsSkeleton implements SettingsInterface
{
	use Concerns\HasLocale;

	protected $values;

	public function __construct(array $values = [])
	{
		$this->values = $values;
	}

	protected function requireValue(string $name)
	{
		$value = $this->value($name);

		if ($value === null || $value === '')
		{
			throw new Main\SystemException(self::getLocale('REQUIRE_VALUE', [
				'#NAME#' => self::getLocale($name),
			]));
		}

		return $value;
	}

	public function value(string $name)
	{
		return $this->values[$name] ?? null;
	}
}