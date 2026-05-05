<?php /** @noinspection SpellCheckingInspection */
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Avito\Export\Assert;
use Bitrix\Iblock;

class Registry
{
	/** @var array<string, Iblock\Template\Functions\FunctionBase> */
	protected static $types = [
		'sum' => FunctionSum::class,
		'multiply' => FunctionMultiply::class,
		'if' => FunctionIf::class,
		'equals' => FunctionEquals::class,
		'mapvalues' => FunctionMapValues::class,
		'min' => FunctionMin::class,
		'max' => FunctionMax::class,
		'first' => FunctionFirst::class,
		'fileexists' => FunctionFileExists::class,
        'weekday' => FunctionWeekday::class,
		'merge' => FunctionMerge::class,
		'match' => FunctionMatch::class,
		'characteristic' => FunctionCharacteristic::class,
		'notempty' => FunctionNotEmpty::class,
		'watermark' => FunctionWatermark::class,
		'date' => FunctionDate::class,
		'range' => FunctionRange::class,
		'truncate' => FunctionTruncate::class,
		'round' => FunctionRound::class,
		'ceil' => FunctionCeil::class,
		'floor' => FunctionFloor::class,
	];

	public static function isExists(string $type) : bool
	{
		return isset(static::$types[$type]);
	}

	public static function make(string $type) : Iblock\Template\Functions\FunctionBase
	{
		$className = static::$types[$type];

		Assert::notNull($className, 'static::$types[$type]');
		Assert::isSubclassOf($className, Iblock\Template\Functions\FunctionBase::class);

		return new $className();
	}
}