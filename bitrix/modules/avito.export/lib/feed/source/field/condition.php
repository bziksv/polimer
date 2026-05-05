<?php

namespace Avito\Export\Feed\Source\Field;

use Avito\Export\Concerns;
use Avito\Export\Utils;
use Bitrix\Main;

class Condition
{
	use Concerns\HasLocale;

	public const EQUAL = 'equal';
	public const NOT_EQUAL = 'notEqual';
	public const MORE_THEN = 'moreThen';
	public const LESS_THEN = 'lessThen';
	public const MORE_OR_EQUAL = 'moreOrEqual';
	public const LESS_OR_EQUAL = 'lessOrEqual';
	public const AT_LIST = 'atList';
	public const NOT_AT_LIST = 'notAtList';
	public const HAS_SUBSTRING = 'hasSubstring';
	public const HAS_NOT_SUBSTRING = 'hasNotSubstring';

	public static function isMultiple(string $condition) : bool
	{
		return in_array($condition, static::multipleCondition(), true);
	}

	protected static function multipleCondition() : array
	{
		return [
			self::AT_LIST,
			self::NOT_AT_LIST,
		];
	}
	
	public static function all() : array
	{
		return [
			self::AT_LIST => [
				'QUERY' => '=',
				'MULTIPLE' => true
			],
			self::NOT_AT_LIST => [
				'QUERY' => '!=',
				'MULTIPLE' => true
			],
			self::EQUAL => [
				'QUERY' => '=',
				'MULTIPLE' => false
			],
			self::NOT_EQUAL => [
				'QUERY' => '!',
				'MULTIPLE' => false
			],
			self::MORE_THEN => [
				'QUERY' => '>',
				'MULTIPLE' => false
			],
			self::MORE_OR_EQUAL => [
				'QUERY' => '>=',
				'MULTIPLE' => false
			],
			self::LESS_THEN => [
				'QUERY' => '<',
				'MULTIPLE' => false
			],
			self::LESS_OR_EQUAL => [
				'QUERY' => '<=',
				'MULTIPLE' => false
			],
			self::HAS_SUBSTRING => [
				'QUERY' => '%',
				'MULTIPLE' => false
			],
			self::HAS_NOT_SUBSTRING => [
				'QUERY' => '!%',
				'MULTIPLE' => false
			],
		];
	}

	public static function some(string $compare) : array
	{
		$all = static::all();

		if (!isset($all[$compare]))
		{
			throw new Main\SystemException(sprintf('unknown %s compare', $compare));
		}

		return $all[$compare];
	}

	public static function title(string $compare) : string
	{
		$key = Utils\Name::screamingSnakeCase($compare);

		return self::getLocale($key);
	}
}
