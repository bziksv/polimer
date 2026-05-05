<?php
namespace Avito\Export\Feed\Source\ElementSeo;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Feed;
use Bitrix\Iblock;

class Fetcher extends Source\FetcherSkeleton
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

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
		return $this->once('fields', function() {
			return [
				new Source\Field\StringField([
					'ID' => 'ELEMENT_PAGE_TITLE',
					'NAME' => self::getLocale('FIELD_ELEMENT_PAGE_TITLE'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => 'ELEMENT_META_TITLE',
					'NAME' => self::getLocale('FIELD_ELEMENT_META_TITLE'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => 'ELEMENT_META_DESCRIPTION',
					'NAME' => self::getLocale('FIELD_ELEMENT_META_DESCRIPTION'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => 'SECTION_PAGE_TITLE',
					'NAME' => self::getLocale('FIELD_SECTION_PAGE_TITLE'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => 'SECTION_META_TITLE',
					'NAME' => self::getLocale('FIELD_SECTION_META_TITLE'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => 'SECTION_META_DESCRIPTION',
					'NAME' => self::getLocale('FIELD_SECTION_META_DESCRIPTION'),
					'FILTERABLE' => false,
				]),
			];
		});
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$parentCache = [];
		$result = [];

		foreach (Source\Routine\Values::catalogElements($elements, $parents) as $elementId => $element)
		{
			$isParent = ($elementId !== (int)$element['ID']);

			if ($isParent && isset($parentCache[$element['ID']]))
			{
				$result[$elementId] = $parentCache[$element['ID']];
				continue;
			}

			$result[$elementId] = $this->getSeoValues($element, $select);

			if ($isParent)
			{
				$parentCache[$element['ID']] = $result[$elementId];
			}
		}

		return $result;
	}

	protected function getSeoValues(array $element, array $select) : array
	{
		$provider = new Iblock\InheritedProperty\ElementValues($element['IBLOCK_ID'], $element['ID']);
		$values = $provider->queryValues();
		$result = [];

		foreach ($values as $code => $row)
		{
			if (!in_array($code, $select, true)) { continue; }

			$result[$code] = $row['VALUE'];
		}

		return $result;
	}
}