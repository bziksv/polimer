<?php

namespace Bitrix\Location\Entity\Source;

use Bitrix\Location\Entity\Source;
use Bitrix\Location\Exception\RuntimeException;
use Bitrix\Location\Source\Osm\OsmSource;

/**
 * Class Factory
 * @package Bitrix\Location\Entity\Source
 * @internal
 */
final class Factory
{
	public const GOOGLE_SOURCE_CODE = 'GOOGLE';
	public const OSM_SOURCE_CODE = 'OSM';

	private const GOOGLE_SOURCE_CLASS = 'Bitrix\\Location\\Source\\Google\\GoogleSource';

	/**
	 * @param string $code
	 * @return Source
	 */
	public static function makeSource(string $code): Source
	{
		$class = null;

		switch ($code)
		{
			case static::GOOGLE_SOURCE_CODE:
				$class = class_exists(static::GOOGLE_SOURCE_CLASS)
					? static::GOOGLE_SOURCE_CLASS
					: OsmSource::class;
				break;
			case static::OSM_SOURCE_CODE:
				$class = OsmSource::class;
				break;
		}

		if (is_null($class))
		{
			throw new RuntimeException(sprintf('Unexpected source code - %s', $code));
		}

		/** @var Source $source */
		return (new $class())->setCode($code);
	}
}
