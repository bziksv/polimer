<?php
namespace Avito\Export\Feed\Source\Section;

use Avito\Export\Concerns;
use Avito\Export\Config;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Context;
use Avito\Export\Feed\Source\Data;
use Avito\Export\Utils;

class Fetcher extends Source\FetcherSkeleton
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected $typeMap = [
		'string' => 'S',
		'integer' => 'N',
		'double' => 'N',
		'enumeration' => 'L',
		'hlblock' => 'L',
		'url' => 'S',
		'file' => 'F',
		'iblock_element' => 'E',
		'iblock_section' => 'G',
	];
	protected $sectionSelectMap = [
		'SECTION_ID' => 'ID',
		'DEPTH_LEVEL' => true,
		'LEFT_MARGIN' => true,
		'RIGHT_MARGIN' => true,
	];

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
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
		return $this->once('fields', function() use ($context) {
			return array_merge(
				$this->commonFields(),
				$this->userFields($context)
			);
		});
	}

	protected function commonFields() : array
	{
		return [
			new Source\Field\NumberField([
				'ID' => 'ID',
				'NAME' => 'ID',
				'FILTERABLE' => false,
			]),
			new Source\Field\StringField([
				'ID' => 'XML_ID',
				'NAME' => 'XML_ID',
				'FILTERABLE' => false,
			]),
			new Source\Field\StringField([
				'ID' => 'NAME',
				'NAME' => self::getLocale('FIELD_NAME'),
				'FILTERABLE' => false,
			]),
			new Source\Field\StringField([
				'ID' => 'DESCRIPTION',
				'NAME' => self::getLocale('FIELD_DESCRIPTION'),
				'FILTERABLE' => false,
			]),
			new Source\Field\FileField([
				'ID' => 'PICTURE',
				'NAME' => self::getLocale('FIELD_PICTURE'),
				'FILTERABLE' => false,
			]),
			new Source\Field\FileField([
				'ID' => 'DETAIL_PICTURE',
				'NAME' => self::getLocale('FIELD_DETAIL_PICTURE'),
				'FILTERABLE' => false,
			]),
		];
	}

	protected function userFields(Source\Context $context) : array
	{
		$result = [];
		$factory = new Source\Field\Factory();
		$userFields = $this->iblockUserFields($context->iblockId());

		foreach ($userFields as $userField)
		{
			$userClassName = $userField['USER_TYPE']['CLASS_NAME'] ?? null;
			$commonField = [
				'ID' => $userField['FIELD_NAME'],
				'NAME' => sprintf('[%s] %s', $userField['FIELD_NAME'], $userField['EDIT_FORM_LABEL']),
				'TYPE' => $this->typeMap[$userField['USER_TYPE_ID']] ?? 'S',
				'FILTERABLE' => false,
			];

			if ($userClassName !== null && class_exists($userClassName) && method_exists($userClassName, 'avitoExportFeedFields'))
			{
				$embeddedFields = $userClassName::avitoExportFeedFields($userField);

				if (!is_array($embeddedFields)) { continue; }

				foreach ($embeddedFields as $embeddedField)
				{
					$field = $embeddedField + $commonField;
					$field['ID'] = $commonField['ID'] . '.' . $embeddedField['ID'];
					$field['NAME'] = $commonField['NAME'] . sprintf(' (%s)', $embeddedField['TITLE']);

					$result[] = $factory->make($field);
				}
			}
			else
			{
				$result[] = $factory->make($commonField);
			}
		}

		return $result;
	}

	public function extend(array $fields, Data\SourceSelect $sources, Context $context) : void
	{
		$sources->add(Source\Registry::IBLOCK_FIELD, 'IBLOCK_SECTION_ID');
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$catalog = Source\Routine\Values::catalogElements($elements, $parents);
		$primarySections = $this->primarySections($siblings);
		$linkedSections = $this->linkedSections(array_values(array_column($catalog, 'ID', 'ID')));
		$sectionIds = $this->usedSections($primarySections, $linkedSections);
		$sectionValues = $this->sectionValues($context->iblockId(), $sectionIds, $select);
		$result = [];

		foreach ($catalog as $targetId => $element)
		{
			$result[$targetId] = $this->mergeValues(
				$sectionValues,
				(int)($primarySections[$targetId] ?? 0),
				$linkedSections[$element['ID']] ?? []
			);
		}

		return $result;
	}

	protected function linkedSections(array $elementIds) : array
	{
		if (empty($elementIds)) { return []; }

		$result = [];
		$query = \CIBlockElement::GetElementGroups($elementIds, true, [
			'IBLOCK_ELEMENT_ID',
			'ID',
		]);

		while ($group = $query->Fetch())
		{
			$elementId = $group['IBLOCK_ELEMENT_ID'];

			if (!isset($result[$elementId])) { $result[$elementId] = []; }

			$result[$elementId][] = $group['ID'];
		}

		return $result;
	}

	protected function usedSections(array $primarySections, array $linkedSections) : array
	{
		$map = array_flip($primarySections);

		foreach ($linkedSections as $linked)
		{
			$map += array_flip($linked);
		}

		return array_keys($map);
	}

	protected function mergeValues(array $sectionValues, int $primaryId, array $linkedIds) : array
	{
		$filter = static function($value) { return is_scalar($value) ? (string)$value !== '' : !empty($value); };
		$result = array_filter($sectionValues[$primaryId] ?? [], $filter);

		foreach ($linkedIds as $linkedId)
		{
			if (!isset($sectionValues[$linkedId])) { continue; }

			$result += array_filter($sectionValues[$linkedId], $filter);
		}

		return $result;
	}

	protected function sectionValues(int $iblockId, array $sectionIds, array $select) : array
	{
		[$embeddedMap, $sectionSelectMap] = $this->propertiesEmbeddedMap($select);
		$plainSelect = array_diff($select, array_keys($embeddedMap), array_keys($sectionSelectMap));
		$plainSelect = array_unique(array_merge($plainSelect, array_column($embeddedMap, 0), array_column($sectionSelectMap, 0)));

		$sectionValues = $this->querySectionValues($iblockId, $sectionIds, $plainSelect, $this->compileSectionSelect($sectionSelectMap));
		$sectionValues = $this->extendEmbeddedValues($sectionValues, $iblockId, $embeddedMap);

		return $sectionValues;
	}

	protected function propertiesEmbeddedMap(array $select) : array
	{
		$userFieldSelect = [];
		$sectionSelect = [];

		foreach ($select as $name)
		{
			$dotPosition = mb_strpos($name, '.');

			if ($dotPosition === false) { continue; }

			$fieldName = mb_substr($name, 0, $dotPosition);
			$subName = mb_substr($name, $dotPosition + 1);

			if (isset($this->sectionSelectMap[$subName]))
			{
				$sectionSelect[$name] = [ $fieldName, $subName ];
			}
			else
			{
				$userFieldSelect[$name] = [ $fieldName, $subName ];
			}
		}

		return [$userFieldSelect, $sectionSelect];
	}

	protected function compileSectionSelect(array $sectionSelectMap) : array
	{
		$result = [];

		foreach ($sectionSelectMap as [$fieldName, $subName])
		{
			if (!isset($result[$fieldName])) { $result[$fieldName] = []; }

			$result[$fieldName][] = $subName;
		}

		return $result;
	}

	public function querySectionValues(int $iblockId, array $sectionIds, array $select, array $sectionSelect = []) : array
	{
		if (empty($select) || empty($sectionIds)) { return []; }

		$select = array_unique($select);
		$result = array_fill_keys($sectionIds, []);
		$nextIds = $sectionIds;
		$fetched = [];

		while (!empty($nextIds))
		{
			$nextTargetMap = [];

			$query = \CIBlockSection::GetList(
				[],
				[ 'IBLOCK_ID' => $iblockId, 'ID' => $nextIds ],
				false,
				array_merge(
					[ 'IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID' ],
					$select,
					$this->fieldsFromSectionSelect($sectionSelect)
				)
			);

			while ($row = $query->Fetch())
			{
				$sectionId = (int)$row['ID'];
				$fetched[$sectionId] = $row;
				$targetIds = $targetMap[$sectionId] ?? [];

				if (isset($result[$sectionId]))
				{
					$targetIds[] = $sectionId;
				}

				foreach ($targetIds as $targetId)
				{
					$sourceId = $sectionId;

					while ($sourceId > 0 && isset($fetched[$sourceId]))
					{
						$sourceRow = $fetched[$sourceId];
						$parentId = (int)$sourceRow['IBLOCK_SECTION_ID'];
						$isAllFilled = true;

						foreach ($select as $name)
						{
							if (isset($result[$targetId][$name])) { continue; }

							$value = $sourceRow[$name] ?? null;

							if (Source\Routine\Values::isEmpty($value))
							{
								$isAllFilled = false;
								continue;
							}

							$result[$targetId][$name] = $this->fieldDisplayValue($name, $value, $iblockId);

							if (isset($sectionSelect[$name]))
							{
								$result[$targetId] += $this->fieldSectionValues($sourceRow, $name, $sectionSelect[$name]);
							}
						}

						if ($parentId > 0 && !$isAllFilled && !isset($fetched[$parentId]))
						{
							if (!isset($nextTargetMap[$parentId]))
							{
								$nextTargetMap[$parentId] = [];
							}

							$nextTargetMap[$parentId][] = $targetId;
							break;
						}

						$sourceId = $parentId;
					}
				}
			}

			$targetMap = $nextTargetMap;
			$nextIds = array_keys($targetMap);
		}

		return $result;
	}

	protected function fieldsFromSectionSelect(array $sectionSelect) : array
	{
		$fieldsMap = [];

		foreach ($sectionSelect as $select)
		{
			foreach ($select as $name)
			{
				if (!isset($this->sectionSelectMap[$name])) { continue; }

				$field = $this->sectionSelectMap[$name] === true ? $name : $this->sectionSelectMap[$name];
				$fieldsMap[$field] = true;
			}
		}

		return array_keys($fieldsMap);
	}

	protected function extendEmbeddedValues(array $sectionValues, int $iblockId, array $embeddedMap) : array
	{
		$userFields = $this->iblockUserFields($iblockId);

		foreach ($sectionValues as &$values)
		{
			foreach ($embeddedMap as $embeddedSign => [$propertyCode, $embeddedField])
			{
				if (!isset($userFields[$propertyCode])) { continue; }

				$userField = $userFields[$propertyCode];
				$userClassName = $userField['USER_TYPE']['CLASS_NAME'] ?? null;

				if (
					$userClassName === null
					|| !class_exists($userClassName)
					|| !method_exists($userClassName, 'avitoExportFeedValue')
				)
				{
					continue;
				}

				$values[$embeddedSign] = $userClassName::avitoExportFeedValue(
					$userField,
					[ 'VALUE' => $values[$propertyCode] ],
					$embeddedField
				);
			}
		}
		unset($values);

		return $sectionValues;
	}

	protected function primarySections(array $siblings) : array
	{
		$elementValues = Utils\ArrayHelper::column($siblings, Source\Registry::IBLOCK_FIELD);

		return Utils\ArrayHelper::column($elementValues, 'IBLOCK_SECTION_ID');
	}

	protected function fieldDisplayValue(string $name, $value, int $iblockId)
	{
		if (empty($value)) { return $value; }

		if ($name === 'PICTURE' || $name === 'DETAIL_PICTURE')
		{
			return \CFile::GetPath($value);
		}

		if (mb_strpos($name, 'UF_') === 0)
		{
			$userField = $this->iblockUserFields($iblockId)[$name] ?? null;

			if ($userField === null) { return $value; }

            if (
                $userField['USER_TYPE_ID'] === 'enumeration' &&
                Config::getOption('process_uf_list_fields', 'Y') === 'Y'
            )
            {
                $query = \CUserFieldEnum::GetList(
                    [],
                    [
                        'ID' => $value,
                        'USER_FIELD_ID' => $userField['ID'],
                    ]
                );

                if ($userField['MULTIPLE'] === 'Y')
                {
                    $result = [];

                    while ($row = $query->Fetch())
                    {
                        $result[] = $row['VALUE'];
                    }
                }
                else
                {
                    $row = $query->Fetch();

                    $result = $row['VALUE'] ?? null;
                }

                return $result;
            }

			if ($userField['USER_TYPE_ID'] === 'file')
			{
				if ($userField['MULTIPLE'] === 'Y')
				{
					$query = \CFile::GetList([], ['@ID' => $value]);

					$result = [];
					while ($row = $query->Fetch())
					{
						$result[] = \CFile::GetFileSRC($row);
					}
				}
				else
				{
					$result = \CFile::GetPath($value);
				}

				return $result;
			}
		}

		return $value;
	}

	protected function fieldSectionValues(array $section, string $fieldName, array $select) : array
	{
		$result = [];

		foreach ($select as $name)
		{
			$key = $name === 'SECTION_ID' ? 'ID' : $name;

			$result[$fieldName . '.' . $name] = $section[$key] ?? null;
		}

		return $result;
	}

	protected function iblockUserFields(int $iblockId) : array
	{
		return $this->once('userFields', function ($iblockId) {
			global $USER_FIELD_MANAGER;
			return $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iblockId . '_SECTION', 0, LANGUAGE_ID);
		}, $iblockId);
	}
}