<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Avito\Export\Assert;
use Avito\Export\Feed\Source;
use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Concerns;
use Avito\Export\Admin\Property\CategoryField;
use Avito\Export\Admin\Property\Utils\SectionField;
use Avito\Export\Data;

class Section implements Behavior
{
	use Concerns\HasLocale;

	/** @var ?int */
	protected $contextSection;

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function variants(array $property) : array
	{
		global $USER_FIELD_MANAGER;

		$iblockId = $this->propertyIblockId($property);

		if ($iblockId <= 0 || !Main\Loader::includeModule('iblock')) { return []; }

		$typeId = sprintf('IBLOCK_%s_SECTION', $iblockId);
		$result = [];

		foreach ($USER_FIELD_MANAGER->GetUserFields($typeId, 0, LANGUAGE_ID) as $field)
		{
			if ($field['USER_TYPE_ID'] !== CategoryField::USER_TYPE_ID) { continue; }

			$result[] = [
				'ID' => $field['FIELD_NAME'],
				'NAME' => $field['EDIT_FORM_LABEL'],
			];
		}

		return $result;
	}

	public function options(array $property, string $field) : array
	{
		return [
			'primaryName' => 'IBLOCK_ELEMENT_SECTION_ID',
			'selectName' => 'IBLOCK_SECTION',
			'iblockId' => $property['IBLOCK_ID'],
			'property' => $field,
		];
	}

	public function value(array $form) : string
	{
		Assert::notNull($form['property'], 'property');

		$property = (string)$form['property'];
		$result = null;

		foreach ($this->formSections($form) as $iblockId => $sectionIds)
		{
			foreach ($sectionIds as $sectionId)
			{
				$value = SectionField::chainValue($property, $iblockId, $sectionId);

				if (is_array($value))
				{
					$value = reset($value);
				}

				if (is_scalar($value) && (string)$value !== '')
				{
					$result = (string)$value;
					break;
				}
			}

			if ($result !== null) { break; }
		}

		if ($result === null)
		{
			throw new Main\ArgumentException(self::getLocale('SECTION_CATEGORY_REQUIRED'));
		}

		return $result;
	}

	protected function formSections(array $form) : array
	{
		Assert::notNull($form['iblockId'], 'iblockId');

		$iblockId = (int)$form['iblockId'];
		$sections = (array)($form['iblockSection'] ?? []);
		$primarySection = (int)($form['iblockSectionId'] ?? 0);

		Main\Type\Collection::normalizeArrayValuesByInt($sections, false);

		if ($primarySection <= 0 && !empty($sections))
		{
			$primarySection = (int)min($sections);
			$primarySection = Data\Iblock\PrimarySection::forLinkedSections($sections, $primarySection, $iblockId);
		}

		if ($primarySection > 0)
		{
			$sections = array_unique(array_merge([ $primarySection ], $sections));
		}

		if (empty($sections))
		{
			throw new Main\ArgumentException(self::getLocale('FORM_SECTION_REQUIRED'));
		}

		return [ $iblockId => $sections ];
	}

	protected function propertyIblockId(array $property) : int
	{
		return (int)($property['IBLOCK_ID'] ?? 0);
	}

	public function elementValuesWithSection(string $propertyId, array $elementIds) : array
	{
		if (empty($elementIds) || !Main\Loader::includeModule('iblock')) { return []; }

		$elements = $this->findElements($elementIds);
		$result = [];

		$sourceValues = Source\Routine\QueryFacade::fetch(
			$this->elementsIblockId($elements),
			array_column($elements, 'ID'),
			Source\Routine\QueryFacade::sourceSelect([
				Source\Registry::SECTION_PROPERTY => [
					$propertyId,
					$propertyId . '.SECTION_ID',
					$propertyId . '.LEFT_MARGIN',
					$propertyId . '.RIGHT_MARGIN',
				],
			])
		);

		foreach ($sourceValues as $elementId => $elementValues)
		{
			if (empty($elementValues[Source\Registry::SECTION_PROPERTY][$propertyId])) { continue; }

			$result[$elementId] = [
				'VALUE' => $elementValues[Source\Registry::SECTION_PROPERTY][$propertyId],
				'SECTION_ID' => $elementValues[Source\Registry::SECTION_PROPERTY][$propertyId . '.SECTION_ID'],
				'LEFT_MARGIN' => $elementValues[Source\Registry::SECTION_PROPERTY][$propertyId . '.LEFT_MARGIN'],
				'RIGHT_MARGIN' => $elementValues[Source\Registry::SECTION_PROPERTY][$propertyId . '.RIGHT_MARGIN'],
			];
		}

		return $result;
	}

	public function elementValues(string $propertyId, array $elementIds) : array
	{
		if (empty($elementIds) || !Main\Loader::includeModule('iblock')) { return []; }

		$elements = $this->findElements($elementIds);
		$result = [];

		$sourceValues = Source\Routine\QueryFacade::fetch(
			$this->elementsIblockId($elements),
			array_column($elements, 'ID'),
			Source\Routine\QueryFacade::sourceSelect([
				Source\Registry::SECTION_PROPERTY => [ $propertyId ],
			])
		);

		foreach ($sourceValues as $elementId => $elementValues)
		{
			if (empty($elementValues[Source\Registry::SECTION_PROPERTY][$propertyId])) { continue; }

			$result[$elementId] = $elementValues[Source\Registry::SECTION_PROPERTY][$propertyId];
		}

		return $result;
	}

	protected function findElements(array $elementIds) : array
	{
		if (empty($elementIds)) { return []; }

		$query = Iblock\ElementTable::getList([
			'filter' => ['ID' => $elementIds],
			'select' => ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID']
		]);

		return $query->fetchAll();
	}

	protected function elementsIblockId(array $elements) : int
	{
		$element = reset($elements);
		$iblockId = $element['IBLOCK_ID'] ?? null;

		Assert::notNull($iblockId, '$iblockId');

		return (int)$iblockId;
	}

	protected function primarySections(array $elements) : array
	{
		$result = array_column($elements, 'IBLOCK_SECTION_ID', 'ID');
		$iblockId = $this->elementsIblockId($elements);

		return Data\Iblock\PrimarySection::forElements($result, $iblockId);
	}

	public function contextSection(int $sectionId) : void
	{
		$this->contextSection = $sectionId;
	}

	public function saveValues(string $propertyId, array $elementIds, string $value) : void
	{
		if (!Main\Loader::includeModule('iblock')) { return; }

		foreach ($this->sectionsForSave($elementIds) as $sectionId)
		{
			$updateProvider = new \CIBlockSection();
			$updated = $updateProvider->Update($sectionId, [ $propertyId => $value ], false, false);

			if ($updated === false)
			{
				throw new Main\SystemException(self::getLocale('SAVE_VALUE_FAILED', [
					'#MESSAGE#' => $updateProvider->LAST_ERROR,
				]));
			}
		}
	}

	protected function sectionsForSave(array $elementIds) : array
	{
		if ($this->contextSection > 0) { return [ $this->contextSection ]; }

		$elements = $this->findElements($elementIds);

		if (empty($elements)) { return []; }

		return array_unique($this->primarySections($elements));
	}
}