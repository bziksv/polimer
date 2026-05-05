<?php
namespace Avito\Export\Feed\Source\GroupProperty;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Utils;
use Bitrix\Iblock;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherCloneable, Source\FetcherDelayed
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected const PROPERTY_OPEN_TEMPLATE = '<ul>';
	protected const PROPERTY_ITEM_TEMPLATE = '<li>#NAME#: #VALUE#</li>';
	protected const PROPERTY_CLOSE_TEMPLATE = '</ul>';

	protected $propertyMap = [];
	protected $propertyData = [];
	protected $featurePool;

	public function __construct()
	{
		$this->featurePool = new Feature\Pool();
	}

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
	}

	public function modules() : array
	{
		return [ 'iblock' ];
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function order() : int
	{
		return 900;
	}

	public function fields(Source\Context $context) : array
	{
		return $this->once('fields', function() {
			$result = [];

			/** @var Feature\Feature $feature */
			foreach ($this->featurePool->getIterator() as $feature)
			{
				$result[] = new Source\Field\StringField([
					'ID' => $feature->id(),
					'NAME' => $feature->title(),
					'FILTERABLE' => false,
				]);
			}

			return $result;
		});
	}

	public function extend(array $fields, Source\Data\SourceSelect $sources, Source\Context $context) : void
	{
		$used = [];

		/** @var Feature\Feature $feature */
		foreach ($this->featurePool->getIterator() as $feature)
		{
			if (!in_array($feature->id(), $fields, true)) { continue; }

			$propertyMap = $feature->properties($context);

			foreach ($propertyMap as $sourceType => $properties)
			{
				foreach ($properties as $propertyId)
				{
					$sources->add($sourceType, $propertyId);
					$used[$propertyId] = true;
				}
			}

			$this->propertyMap[$feature->id()] = $propertyMap;
		}

		$this->propertyData = $this->propertyData(array_keys($used));
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$result[$elementId] = [];

			foreach ($select as $code)
			{
				if (!isset($this->propertyMap[$code])) { continue; }

				$partials = [];

				foreach ($this->propertyMap[$code] as $type => $propertyIds)
				{
					foreach ($propertyIds as $propertyId)
					{
						$value = $siblings[$elementId][$type][$propertyId] ?? null;

						if (Utils\Value::isEmpty($value)) { continue; }

						$partials[] = str_replace(
							[ '#NAME#', '#VALUE#' ],
							[
								$this->propertyData[$propertyId]['NAME'],
								(is_array($value) ? implode(', ', array_unique($value)) : $value),
							],
							static::PROPERTY_ITEM_TEMPLATE
						);
					}
				}

				if (empty($partials)) { continue; }

				$result[$elementId][$code] =
					static::PROPERTY_OPEN_TEMPLATE
					. implode(' ', $partials)
					. static::PROPERTY_CLOSE_TEMPLATE;
			}
		}

		return $result;
	}

	protected function propertyData(array $propertyIds) : array
	{
		if (empty($propertyIds)) { return []; }

		$propertyData = [];

		$iterator = Iblock\PropertyTable::getList([
			'select' => [ 'ID', 'NAME' ],
			'filter' => [ '=ID' => $propertyIds ],
		]);

		while ($property = $iterator->fetch())
		{
			$propertyData[$property['ID']] = [
				'ID' => $property['ID'],
				'NAME' => strip_tags($property['NAME'])
			];
		}

		return $propertyData;
	}
}