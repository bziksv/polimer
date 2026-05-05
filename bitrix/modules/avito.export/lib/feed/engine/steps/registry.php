<?php
/** @noinspection PhpDeprecationInspection */
namespace Avito\Export\Feed\Engine\Steps;

use Bitrix\Main;
use Avito\Export\Feed;

/** @deprecated */
class Registry
{
	public const ROOT = 'root';
	public const OFFER = 'offer';

	/** @return string[] */
	public static function types(): array
	{
		return [
			static::ROOT,
			static::OFFER,
		];
	}

	public static function make(string $name, Feed\Engine\Controller $controller): Step
	{
		if ($name === static::ROOT)
		{
			$result = new Root($controller);
		}
		else if ($name === static::OFFER)
		{
			$result = new Offer($controller);
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown %s step', $name));
		}

		return $result;
	}
}