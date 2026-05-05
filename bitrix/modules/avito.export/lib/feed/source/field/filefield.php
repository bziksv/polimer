<?php
namespace Avito\Export\Feed\Source\Field;

use Avito\Export\Concerns;

class FileField extends EnumField
{
	use Concerns\HasLocale;

	public const VARIANT_EMPTY = 'empty';
	public const VARIANT_FILLED = 'filled';

	protected function defaults() : array
	{
		return [
			'TYPE' => 'F',
		];
	}

	public function conditions() : array
	{
		return [
			Condition::EQUAL,
		];
	}

	public function variants() : array
	{
		return [
			[
				'ID' => static::VARIANT_FILLED,
				'VALUE' => self::getLocale('FILLED'),
			],
			[
				'ID' => static::VARIANT_EMPTY,
				'VALUE' => self::getLocale('EMPTY'),
			],
		];
	}

	public function filter(string $compare, $value) : array
	{
		if ($value === static::VARIANT_EMPTY)
		{
			return [
				$this->filterName() => false,
			];
		}

		if ($value === static::VARIANT_FILLED)
		{
			return [
				'!' . $this->filterName() => false,
			];
		}

		return [];
	}
}