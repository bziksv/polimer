<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class Condition implements Listing
{
	protected $parameters;

	use Concerns\HasLocale;

	public function __construct(array $parameters = [])
	{
		if (!empty($parameters['ADDITIONAL_VALUES']))
		{
			$this->parameters['ADDITIONAL_VALUES'] = $parameters['ADDITIONAL_VALUES'];
		}
	}

	public function values() : array
	{
		$values = [
			self::getLocale('NEW'),
			self::getLocale('USED'),
		];

		if ($this->parameters['ADDITIONAL_VALUES'])
		{
			$values = array_merge($values, $this->parameters['ADDITIONAL_VALUES']);
		}

		return $values;
	}

	public function value(string $key) : ?string
	{
		$value = self::getLocale($key);

		if ($value === $key) { return null; }

		return $value;
	}
}