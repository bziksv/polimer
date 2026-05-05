<?php

namespace Avito\Export\Admin\Property;

use Avito\Export\Admin\View;
use Avito\Export\Dictionary\Listing\Listing;
use Avito\Export\Dictionary\Listing\ListingWithDisplay;
use Bitrix\Main;

abstract class ListingSkeleton
{
	public static function GetUserTypeDescription() : array
	{
		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => static::USER_TYPE,
			'DESCRIPTION' => static::getMessage('DESCRIPTION'),
			'GetPropertyFieldHtml' => [static::class, 'GetPropertyFieldHtml'],
		];
	}

	public static function GetPropertyFieldHtml($property, $value, $controlName) : string
	{
		$listing = static::getListing();
		$options = static::makeOptions($listing);

		return View\Select::edit($options, $value['VALUE'], [
			'name' => $controlName['VALUE'],
			'style' => 'max-width: 500px',
		], [
			'ALLOW_NO_VALUE' => ($property['IS_REQUIRED'] !== 'Y'),
		]);
	}

	protected static function getListing() : Listing
	{
		throw new Main\NotImplementedException();
	}

	protected static function makeOptions(Listing $listing) : array
	{
		if ($listing instanceof ListingWithDisplay)
		{
			$result = array_map(static function(string $code) use ($listing) {
				return [
					'ID' => $code,
					'VALUE' => $listing->display($code),
				];
			}, $listing->values());
		}
		else
		{
			$result = $listing->values();
		}

		return $result;
	}

	protected static function getMessage(string $code) : string
	{
		throw new Main\NotImplementedException();
	}
}