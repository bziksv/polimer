<?php
namespace Avito\Export\Feed\Source\Element;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Context;
use Avito\Export\Data;
use Bitrix\Iblock;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherInvertible
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

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

	public function order() : int
	{
		return 400;
	}

	public function fields(Source\Context $context) : array
	{
		return $this->once('fields', function() use ($context) {
			return $this->elementFields($context->iblockId());
		});
	}

	protected function elementFields(int $iblockId) : array
	{
		return [
			new Source\Field\NumberField([
				'ID' => 'ID',
				'NAME' => 'ID',
			]),
			new Source\Field\StringField([
				'ID' => 'XML_ID',
				'NAME' => 'XML_ID',
			]),
			new Source\Field\StringField([
				'ID' => 'NAME',
				'NAME' => self::getLocale('FIELD_NAME'),
			]),
			new SectionField([
				'ID' => 'SECTION_ID',
				'IBLOCK_ID' => $iblockId,
				'NAME' => self::getLocale('FIELD_IBLOCK_SECTION_ID'),
				'SELECTABLE' => false,
			]),
            new SectionAloneField([
                'ID' => 'SECTION_ALONE',
                'IBLOCK_ID' => $iblockId,
                'NAME' => self::getLocale('FIELD_IBLOCK_SECTION_ALONE'),
                'SELECTABLE' => false,
                'FILTERABLE' => 'SECTION_ID',
            ]),
			new Source\Field\DateField([
				'ID' => 'DATE_CREATE',
				'NAME' => self::getLocale('FIELD_DATE_CREATE'),
			]),
			new Source\Field\DateField([
				'ID' => 'TIMESTAMP_X',
				'NAME' => self::getLocale('FIELD_TIMESTAMP_X'),
			]),
			new Source\Field\DateField([
				'ID' => 'ACTIVE_FROM',
				'NAME' => self::getLocale('FIELD_ACTIVE_FROM'),
			]),
			new Source\Field\DateField([
				'ID' => 'ACTIVE_TO',
				'NAME' => self::getLocale('FIELD_ACTIVE_TO'),
			]),
			new Source\Field\StringField([
				'ID' => 'PREVIEW_TEXT',
				'NAME' => self::getLocale('FIELD_PREVIEW_TEXT'),
			]),
			new Source\Field\FileField([
				'ID' => 'PREVIEW_PICTURE',
				'NAME' => self::getLocale('FIELD_PREVIEW_PICTURE'),
			]),
			new Source\Field\StringField([
				'ID' => 'DETAIL_TEXT',
				'NAME' => self::getLocale('FIELD_DETAIL_TEXT'),
			]),
			new Source\Field\FileField([
				'ID' => 'DETAIL_PICTURE',
				'NAME' => self::getLocale('FIELD_DETAIL_PICTURE'),
			]),
			new Source\Field\StringField([
				'ID' => 'DETAIL_PAGE_URL',
				'NAME' => self::getLocale('FIELD_DETAIL_PAGE_URL'),
				'FILTERABLE' => false,
			]),
		];
	}

	public function select(array $fields) : array
	{
		return [
			'ELEMENT' => $fields,
		];
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		return [
			'ELEMENT' => Source\Routine\QueryFilter::make($conditions, $this->fields($context)),
		];
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$catalog = Source\Routine\Values::catalogElements($elements, $parents);

		return $this->mergeValues(
			$this->commonValues($catalog, array_diff($select, [
				'IBLOCK_SECTION_ID',
			])),
			$this->iblockSectionIdValues($catalog, $select, $context)
		);
	}

	protected function commonValues(array $elements, array $select) : array
	{
		$parentCache = [];
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$isParent = ($elementId !== (int)$element['ID']);

			if ($isParent && isset($parentCache[$element['ID']]))
			{
				$result[$elementId] = $parentCache[$element['ID']];
				continue;
			}

			$result[$elementId] = $this->fieldValues($element, $select);

			if ($isParent)
			{
				$parentCache[$element['ID']] = $result[$elementId];
			}
		}

		return $result;
	}

	protected function fieldValues(array $element, array $select) : array
	{
		$result = [];

		foreach ($select as $name)
		{
			if (!isset($element[$name])) { continue; }

			if ($name === 'PREVIEW_PICTURE' || $name === 'DETAIL_PICTURE')
			{
				$fileId = (int)$element[$name];

				if ($fileId <= 0) { continue; }

				$result[$name] = \CFile::GetPath($fileId);
			}
			else
			{
				$result[$name] = $element[$name];
			}
		}

		return $result;
	}

	protected function iblockSectionIdValues(array $elements, array $select, Source\Context $context) : array
	{
		if (!in_array('IBLOCK_SECTION_ID', $select, true)) { return []; }

		$primarySections = array_column($elements, 'IBLOCK_SECTION_ID', 'ID');
		$primarySections = Data\Iblock\PrimarySection::forElements($primarySections, $context->iblockId());
		$result = [];

		foreach ($elements as $targetId => $element)
		{
			$elementId = $element['ID'];

			if (empty($primarySections[$elementId])) { continue; }

			$result[$targetId]['IBLOCK_SECTION_ID'] = $primarySections[$elementId];
		}

		return $result;
	}

	protected function mergeValues(array ...$partials) : array
	{
		$partials = array_reverse($partials);
		$result = array_shift($partials);

		foreach ($partials as $partial)
		{
			foreach ($partial as $elementId => $values)
			{
				if (!isset($result[$elementId]))
				{
					$result[$elementId] = $values;
				}
				else
				{
					$result[$elementId] += $values;
				}
			}
		}

		return $result;
	}

	public function elements(array $values, string $field, Context $context) : array
	{
		$result = [];
		$leftValues = array_flip($values);

		foreach ($this->elementsSearchIblockIds($context) as $iblockId)
		{
			if (empty($leftValues)) { break; }

			$query = Iblock\ElementTable::getList([
				'filter' => [
					'=IBLOCK_ID' => $iblockId,
					'=' . $field => array_keys($leftValues),
				],
				'select' => array_unique([ 'ID', $field ]),
			]);

			while ($row = $query->fetch())
			{
				$fieldValue = (string)($row[$field] ?? '');

				if (!isset($leftValues[$fieldValue])) { continue; }

				$result[$fieldValue] = (int)$row['ID'];
			}

			$leftValues = array_diff_key($leftValues, $result);
		}

		return $result;
	}

	protected function elementsSearchIblockIds(Context $context) : array
	{
		return [
			$context->iblockId(),
		];
	}
}