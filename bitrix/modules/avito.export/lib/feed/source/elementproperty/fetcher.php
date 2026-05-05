<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Feed\Source\Context;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\HighloadBlock;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherInvertible
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected $highloadCache = [];
	protected $specialTypes = [
		'F' => true,
		'E' => true,
		'directory' => true,
		'ElementXmlID' => true,
	];

	public function listener() : Source\Listener
	{
		return new Listener();
	}
	
	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function modules() : array
	{
		return [ 'iblock' ];
	}

	public function fields(Source\Context $context) : array
	{
		$iblockId = $this->contextIblockId($context);

		if ($iblockId === null) { return []; }

		return $this->once('fields', function() use ($iblockId) {
			return $this->propertyFields($iblockId);
		});
	}

	protected function propertyFields(int $iblockId, array $propertyIds = null) : array
	{
		$result = [];
		$fieldFactory = new Source\Field\Factory();
		$propertyFactory = new FieldFactory();
		$filter = [
			'=IBLOCK_ID' => $iblockId,
			'=ACTIVE' => 'Y',
		];

		if ($propertyIds !== null)
		{
			if (empty($propertyIds)) { return []; }

			$filter['=ID'] = $propertyIds;
		}

		$query = Iblock\PropertyTable::getList([
			'filter' => $filter,
			'select' => [
				'ID',
				'CODE',
				'NAME',
				'PROPERTY_TYPE',
				'LINK_IBLOCK_ID',
				'USER_TYPE',
				'USER_TYPE_SETTINGS_LIST',
			],
		]);

		while ($property = $query->fetch())
		{
			$userType = (string)$property['USER_TYPE'] !== '' ? \CIBlockProperty::GetUserType($property['USER_TYPE']) : null;
			$commonField = [
				'ID' => $property['ID'],
				'CODE' => $property['CODE'],
				'NAME' => sprintf('[%s] %s', $property['ID'], $property['NAME']),
			];

			if (isset($userType['avitoExportFeedFields']))
			{
				$embeddedFields = call_user_func($userType['avitoExportFeedFields'], $property);

				if (!is_array($embeddedFields)) { continue; }

				foreach ($embeddedFields as $embeddedField)
				{
					$field = $embeddedField + $commonField;
					$field['ID'] = $commonField['ID'] . '.' . $embeddedField['ID'];
					$field['NAME'] = $commonField['NAME'] . sprintf(' (%s)', $embeddedField['TITLE']);
					$field['FILTERABLE'] = false;

					$result[] = $fieldFactory->make($field);
				}
			}
			else
			{
				$commonField += $property;
				$commonField['FILTERABLE'] = 'PROPERTY_' . $property['ID'];

				$result[] = $propertyFactory->make($commonField);
			}
		}

		return $result;
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		return [
			'ELEMENT' => $this->makeFilter($conditions, $context->iblockId()),
		];
	}

	protected function makeFilter(array $conditions, int $iblockId) : array
	{
		$usedFields = array_column($conditions, 'FIELD');
		$usedFields = array_map('intval', $usedFields);
		$fields = $this->propertyFields($iblockId, $usedFields);

		return Source\Routine\QueryFilter::make($conditions, $fields);
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$result = [];
		$catalog = Source\Routine\Values::catalogElements($elements, $parents);
		$propertyValues = $this->propertyValues(
			$context->iblockId(),
			array_values(array_column($catalog, 'ID', 'ID')),
			$select
		);

		foreach ($catalog as $elementId => $element)
		{
			if (!isset($propertyValues[$element['ID']])) { continue; }

			$result[$elementId] = $propertyValues[$element['ID']];
		}

		return $result;
	}

	protected function propertyValues(int $iblockId, array $elementIds, array $select) : array
	{
		$embeddedMap = $this->propertiesEmbeddedMap($select);
		$plainSelect = array_diff($select, array_keys($embeddedMap));
		$propertyIds = array_unique(array_merge(
			$plainSelect,
			array_column($embeddedMap, 0)
		));

		$propertyValues = $this->queryProperties($iblockId, $elementIds, $propertyIds);
		$properties = $this->extractProperties($propertyValues);
		[$rawSelect, $specialSelect] = $this->splitSpecialSelect($plainSelect, $properties);

		$result = [];
		$result = $this->extendRawValues($result, $propertyValues, $rawSelect);
		$result = $this->extendSpecialValues($result, $propertyValues, $specialSelect);
		$result = $this->extendEmbeddedValues($result, $propertyValues, $embeddedMap);

		return $result;
	}

	protected function propertyType($property)
	{
		$result = $property['PROPERTY_TYPE'];

		if (isset($this->specialTypes[$property['USER_TYPE']]))
		{
			$result = $property['USER_TYPE'];
		}

		return $result;
	}

	protected function propertiesEmbeddedMap($select) : array
	{
		$result = [];

		foreach ($select as $name)
		{
			$dotPosition = mb_strpos($name, '.');

			if ($dotPosition === false) { continue; }

			$result[$name] = [
				mb_substr($name, 0, $dotPosition),
				mb_substr($name, $dotPosition + 1),
			];
		}

		return $result;
	}

	protected function queryProperties(int $iblockId, array $elementIds, array $propertyIds): array
	{
		if (empty($propertyIds) || empty($elementIds)) { return []; }

		$result = array_fill_keys($elementIds, []);

		\CIBlockElement::GetPropertyValuesArray(
			$result,
			$iblockId,
			[ 'ID' => $elementIds ],
			[ 'ID' => $propertyIds ],
			[ 'USE_PROPERTY_ID' => 'Y' ]
		);

		return $result;
	}

	protected function extractProperties(array $propertyValues) : array
	{
		$result = [];

		foreach ($propertyValues as $elementValues)
		{
			if (empty($elementValues)) { continue; }

			$result = $elementValues;
			break;
		}

		return $result;
	}

	protected function splitSpecialSelect(array $select, array $properties) : array
	{
		$raw = [];
		$special = [];

		foreach ($select as $propertyId)
		{
			if (!isset($properties[$propertyId])) { continue; }

			$property = $properties[$propertyId];
			$type = $this->propertyType($property);

			if (isset($this->specialTypes[$type]))
			{
				$special[] = $propertyId;
			}
			else
			{
				$raw[] = $propertyId;
			}
		}

		return [$raw, $special];
	}

	protected function extendRawValues(array $result, array $propertyValues, array $select) : array
	{
		if (empty($select)) { return $result; }

		foreach ($propertyValues as $elementId => $elementValues)
		{
			if (!isset($result[$elementId])) { $result[$elementId] = []; }

			foreach ($select as $propertyId)
			{
				if (!isset($elementValues[$propertyId]['VALUE'])) { continue; }

				$result[$elementId][$propertyId] = $this->displayValue($elementValues[$propertyId]);
			}
		}

		return $result;
	}

	protected function displayValue(array $property)
	{
		$userType = $property['USER_TYPE'] ?? null;

        if ($userType === 'Money')
        {
            $price = $property['VALUE'] ?? null;

            if (!is_string($price)) { return null; }

            [$price, $currency] = explode('|', $price, 2);

			if (!is_numeric($price)) { return null; }

            if (!Main\Loader::includeModule('currency'))
            {
                return (float) $price;
            }

            $targetCurrency = CurrencyManager::getBaseCurrency();
            $result = \CCurrencyRates::ConvertCurrency($price, $currency, $targetCurrency);
        }
		else if ($userType === 'HTML')
		{
			$display = [];
			$raw = $property['~VALUE'] ?? $property['VALUE'];
			$isMultiple = !isset($raw['TEXT'], $raw['TYPE']);

			if (!$isMultiple) { $raw = [ $raw ]; }
			if (!is_array($raw)) { return null; }

			foreach ($raw as $one)
			{
				if (!isset($one['TEXT'], $one['TYPE'])) { continue; }

				if (mb_strtolower($one['TYPE']) === 'html')
				{
					$display[] = $one['TEXT'];
				}
				else
				{
					$text = trim($one['TEXT']);
					$text = str_replace(
						['<', '>', "\r\n", "\n"],
						['&lt;', '&gt;', "\n", "<br />\n"],
						$text
					);

					$display[] = $text;
				}
			}

			$result = $isMultiple ? $display : reset($display);
		}
		else
		{
			$result = $property['~VALUE'] ?? $property['VALUE'];
		}

		return $result;
	}

	protected function extendSpecialValues(array $result, array $propertyValues, array $select): array
	{
		if (empty($select)) { return $result; }

		$valuesMap = $this->mapPropertiesValues($propertyValues, $select);
		$properties = $this->extractProperties($propertyValues);

		foreach ($valuesMap as $propertyId => $elementMap)
		{
			if (!isset($properties[$propertyId]) || empty($elementMap)) { continue; }

			$property = $properties[$propertyId];
			$type = $this->propertyType($property);
			$values = array_keys($elementMap);
			$specialValues = [];

			if ($type === 'E')
			{
				$specialValues = $this->specialElementValues($values, 'ID');
			}
			else if ($type === 'ElementXmlID')
			{
				$specialValues = $this->specialElementValues($values, 'XML_ID');
			}
			else if ($type === 'directory')
			{
				$specialValues = $this->specialHighloadValues($property, $values);
			}
			else if ($type === 'F')
			{
				$specialValues = $this->specialFileValues($values);
			}

			foreach ($specialValues as $value => $display)
			{
				if (!isset($elementMap[$value])) { continue; }

				foreach ($elementMap[$value] as $elementId)
				{
					if (!isset($result[$elementId])) { $result[$elementId] = []; }

					if (isset($result[$elementId][$propertyId]))
					{
						if (!is_array($result[$elementId][$propertyId]))
						{
							$result[$elementId][$propertyId] = (array)$result[$elementId][$propertyId];
						}

						$result[$elementId][$propertyId][] = $display;
					}
					else
					{
						$result[$elementId][$propertyId] = $display;
					}
				}
			}
		}
		unset($specialValues);

		return $result;
	}

	protected function mapPropertiesValues(array $propertyValues, array $select) : array
	{
		$result = [];
		$selectMap = array_flip($select);

		foreach ($propertyValues as $elementId => $elementValues)
		{
			foreach ($elementValues as $property)
			{
				$propertyId = (int)$property['ID'];
				$propertyValue = $property['VALUE'];

				if (!isset($selectMap[$propertyId]) || empty($propertyValue)) { continue; }

				if (!is_array($propertyValue)) { $propertyValue = [ $propertyValue ]; }

				foreach ($propertyValue as $value)
				{
					if (!is_scalar($value)) { continue; }

					$value = trim($value);

					if ($value === '') { continue; }

					if (!isset($result[$propertyId]))
					{
						$result[$propertyId] = [];
					}

					if (!isset($result[$propertyId][$value]))
					{
						$result[$propertyId][$value] = [];
					}

					$result[$propertyId][$value][] = $elementId;
				}
			}
		}

		return $result;
	}

	protected function specialElementValues(array $values, string $field) : array
	{
		if (empty($values)) { return []; }

		$result = [];

		$query = Iblock\ElementTable::getList([
			'filter' => [ '=' . $field => $values ],
			'select' => [ $field, 'NAME' ],
		]);

		while ($element = $query->Fetch())
		{
			if (empty($element[$field])) { continue; }

			$sign = $element[$field];

			if (isset($result[$sign])) { continue; } // xml id conflict

			$result[$sign] = $element['NAME'];
		}

		return $result;
	}

	protected function specialHighloadValues(array $property, array $values) : array
	{
		if (empty($values)) { return []; }

		$result = [];

		try
		{
			$highloadEntity = $this->highloadEntity($property);

			if (
				$highloadEntity === null
				|| !$highloadEntity->hasField('UF_XML_ID')
				|| !$highloadEntity->hasField('UF_NAME')
			)
			{
				return [];
			}

			$highloadDataClass = $highloadEntity->getDataClass();

			$query = $highloadDataClass::getList([
				'filter' => [ '=UF_XML_ID' => $values ],
				'select' => [ 'UF_XML_ID', 'UF_NAME' ],
			]);

			while ($row = $query->fetch())
			{
				$result[$row['UF_XML_ID']] = $row['UF_NAME'];
			}
		}
		catch (Main\DB\SqlException $exception)
		{
			// nothing
		}

		return $result;
	}

	protected function highloadEntity($property) : ?Main\ORM\Entity
	{
		$tableName = !empty($property['USER_TYPE_SETTINGS']['TABLE_NAME'])
			? trim($property['USER_TYPE_SETTINGS']['TABLE_NAME'])
			: '';

		if ($tableName === '') { return null; }

		$result = null;

		if (isset($this->highloadCache[$tableName]) || array_key_exists($tableName, $this->highloadCache))
		{
			$result = $this->highloadCache[$tableName];
		}
		else if (Main\Loader::includeModule('highloadblock'))
		{
			$query = HighloadBlock\HighloadBlockTable::getList([
				'filter' => ['=TABLE_NAME' => $tableName],
			]);

			if ($highload = $query->fetch())
			{
				$result = HighloadBlock\HighloadBlockTable::compileEntity($highload);
			}

			$this->highloadCache[$tableName] = $result;
		}

		return $result;
	}

	protected function specialFileValues(array $values) : array
	{
		if (empty($values)) { return []; }

		$result = array_fill_keys($values, null);

		$query = \CFile::GetList([], ['@ID' => $values]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = \CFile::GetFileSRC($row);
		}

		return $result;
	}

	protected function extendEmbeddedValues(array $result, array $propertyValues, array $embeddedMap) : array
	{
		if (empty($embeddedMap)) { return $result; }

		foreach ($propertyValues as $elementId => $elementValues)
		{
			foreach ($embeddedMap as $embeddedSign => [$propertyId, $embeddedField])
			{
				if (!isset($elementValues[$propertyId])) { continue; }

				$propertyValue = $elementValues[$propertyId];

				if (isset($propertyValue[$embeddedField]))
				{
					$result[$elementId][$embeddedSign] = $propertyValue[$embeddedField];
					continue;
				}

				if (empty($propertyValue['USER_TYPE'])) { continue; }

				$userType = \CIBlockProperty::GetUserType($propertyValue['USER_TYPE']);

				if (!isset($userType['avitoExportFeedValue'])) { continue; }

				$result[$elementId][$embeddedSign] = call_user_func(
					$userType['avitoExportFeedValue'],
					$propertyValue,
					[
						'VALUE' => $propertyValue['VALUE'],
						'DESCRIPTION' => $propertyValue['DESCRIPTION'],
					],
					$embeddedField
				);
			}
		}

		return $result;
	}

	protected function contextIblockId(Context $context) : ?int
	{
		return $context->iblockId();
	}

	public function elements(array $values, string $field, Context $context) : array
	{
		$iblockId = $this->contextIblockId($context);
		$propertyId = (int)$field;

		if ($iblockId === null || $propertyId <= 0 || empty($values)) { return []; }

		$result = [];
		$propertyType = $this->fetchPropertyType($propertyId);
		$filterKey = '=PROPERTY_' . $propertyId;

		if ($propertyType === Iblock\PropertyTable::TYPE_LIST) { $filterKey .= '_VALUE'; }

		$query = \CIBlockElement::GetList(
			[],
			[
				'IBLOCK_ID' => $iblockId,
				$filterKey => $values
			],
			false,
			false,
			[
				'IBLOCK_ID',
				'ID',
				'PROPERTY_' . $propertyId,
			]
		);

		while ($row = $query->fetch())
		{
			$fieldValue = (string)($row[$field] ?? $row['PROPERTY_' . $field . '_VALUE'] ?? '');

			if ($fieldValue === '') { continue; }

			$result[$fieldValue] = (int)$row['ID'];
		}

		return $result;
	}

	protected function fetchPropertyType(int $propertyId) : ?string
	{
		return $this->once('fetchPropertyType-' . $propertyId, function() use ($propertyId) {
			$result = null;

			$iterator = Iblock\PropertyTable::getList([
				'filter' => [ '=ID' => $propertyId ],
				'select' => [ 'PROPERTY_TYPE' ],
			]);

			if ($property = $iterator->fetch())
			{
				$result = $property['PROPERTY_TYPE'];
			}

			return $result;
		});
	}
}