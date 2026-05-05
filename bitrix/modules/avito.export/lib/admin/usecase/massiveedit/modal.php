<?php
namespace Avito\Export\Admin\UseCase\MassiveEdit;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Admin;
use Avito\Export\Admin\Property\FormCategory;
use Avito\Export\Concerns;
use Avito\Export\Assert;
use Avito\Export\Utils\Word;
use Avito\Export\Config;
use Avito\Export\Structure\CategoryLevelFacade;
use Avito\Export\Utils\ArrayHelper;

class Modal extends Admin\Page\Page
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected const NO_SECTION = 'NO_SECTION';
	protected const NO_CATEGORY = 'NO_CATEGORY';
	protected const SHOW_DETAILS_LIMIT = 1000;
	protected const CHUNK_SIZE = 1000;

	public function hasRequest() : bool
	{
		return $this->request->getPost('massiveEditAction') !== null;
	}

	public function processRequest() : void
	{
		$action = $this->request->getPost('massiveEditAction');

		if ($action === 'save')
		{
			$this->save();
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown %s action', $action));
		}
	}

	/** @noinspection JSUnresolvedReference */
	public function save() : void
	{
		global $APPLICATION;

		$values = $this->request->getPost('VALUES');
		$propertyId = $this->request->getPost('property');

		Assert::notNull($propertyId, '$_POST[property]');
		Assert::isArray($values, '$_POST[VALUES]');

		$characteristicProperty = $this->characteristicProperty($propertyId);
		$characteristicField = $this->characteristicField($characteristicProperty['IBLOCK_ID']);

		$categoryProperties = $this->categoryProperties($characteristicProperty['IBLOCK_ID']);
		$sectionIds = array_diff(array_column($values, 'SECTION'), [ 0 ]);
		$sectionUsage = ArrayHelper::countColumn($sectionIds);
		$sectionRows = $this->sectionFields($sectionIds);

		foreach ($values as $incoming)
		{
			$elementIds = explode(',', $incoming['ELEMENTS']);
			$sectionId = (int)($incoming['SECTION'] ?? 0);
			$sectionRow = $sectionRows[$sectionId] ?? null;

			$this->checkElementsWriteAccess($characteristicProperty['IBLOCK_ID'], $elementIds);

			$characteristics = $this->normalizeIncomingCharacteristic($characteristicProperty, $incoming['CHARACTERISTICS'] ?? []);
			[$categoryValues, $categoryFilled] = $this->categoryValues($categoryProperties, $elementIds);
			$incomingGroups = $this->groupIncomingBySection($sectionRow, $categoryProperties, $categoryFilled, $elementIds);
			$sectionHaveFewGroups = (isset($incomingGroups[0]) || ($sectionUsage[$sectionId] ?? 0) > count($incomingGroups));

			foreach ($incomingGroups as $groupSectionId => $groupElementIds)
			{
				// category

				if ($incoming['CATEGORY_ORIGIN'] !== $incoming['CATEGORY'])
				{
					$this->saveCategory($groupElementIds, $groupSectionId, $categoryProperties, $categoryValues, $categoryFilled, $incoming['CATEGORY_ORIGIN'], $incoming['CATEGORY']);
				}

				// characteristic

				$this->saveCharacteristic($groupElementIds, $groupSectionId, $characteristicProperty, $characteristicField, $characteristics, $sectionHaveFewGroups);
			}
		}

		$APPLICATION->RestartBuffer();
		echo '<script> top.BX.onCustomEvent("avitoExportMassiveEditDone"); </script>';
		die();
	}

	protected function groupIncomingBySection(?array $sectionRow, array $categoryProperties, array $categoryFilled, array $elementIds) : array
	{
		$sectionCategoryProperties = ArrayHelper::column(array_filter($categoryProperties, static function(array $property) {
			return $property[0] === FormCategory\Registry::SECTION;
		}), 1);

		if ($sectionRow === null || empty($sectionCategoryProperties)) { return [ 0 => $elementIds ]; }

		$result = [];

		foreach ($elementIds as $elementId)
		{
			$hasValue = false;
			$elementContextSection = null;

			foreach ($categoryFilled as $propertyIndex => $values)
			{
				if (!isset($values[$elementId])) { continue; }

				$hasValue = true;

				if (!isset($sectionCategoryProperties[$propertyIndex]))
				{
					$elementContextSection = 0;
					break;
				}

				$value = $values[$elementId];

				if (!isset($value['LEFT_MARGIN'])) { continue; } // not section

				if ($value['LEFT_MARGIN'] <= $sectionRow['LEFT_MARGIN'] && $value['RIGHT_MARGIN'] >= $sectionRow['RIGHT_MARGIN']) // filled for self or parents
				{
					$elementContextSection = $sectionRow['ID'];
					break;
				}

				if ($value['LEFT_MARGIN'] > $sectionRow['LEFT_MARGIN'] && $value['RIGHT_MARGIN'] < $sectionRow['RIGHT_MARGIN'])
				{
					$elementContextSection = $value['SECTION_ID'];
					break;
				}
			}

			if ($elementContextSection === null)
			{
				$elementContextSection = $hasValue ? 0 : $sectionRow['ID'];
			}

			if (!isset($result[$elementContextSection]))
			{
				$result[$elementContextSection] = [];
			}

			$result[$elementContextSection][] = $elementId;
		}

		return $result;
	}

	protected function saveCategory(array $elementIds, int $sectionId, array $categoryProperties, array $categoryValues, array $categoryFilled, ?string $originCategory, ?string $newCategory) : void
	{
		$leftElements = array_flip($elementIds);

		foreach ($categoryProperties as $propertyIndex => [$propertyType, $propertyId])
		{
			$propertyFilled = $categoryFilled[$propertyIndex] ?? [];
			$propertyValues = array_intersect_key($categoryValues, $propertyFilled);
			$propertyElements = array_intersect($propertyValues, [ (string)$originCategory ]);

			if (empty($propertyElements)) { continue; }

			$adapter = $this->categoryAdapter($propertyType);

			if (
				($adapter instanceof FormCategory\Section && $sectionId === 0)
				|| $adapter instanceof FormCategory\ProductElement
			)
			{
				[$firstType, $propertyId] = reset($categoryProperties);
				$adapter = $this->categoryAdapter($firstType);
			}

			if ($adapter instanceof FormCategory\Section)
			{
				$adapter->contextSection($sectionId);
			}

			$adapter->saveValues($propertyId, array_keys($propertyElements), $newCategory);

			$leftElements = array_diff_key($leftElements, $propertyElements);
		}

		if (empty($leftElements)) { return; }

		[$lastType, $lastId] = end($categoryProperties);
		$lastAdapter = $this->categoryAdapter($lastType);

		if (empty($categoryValues) && $sectionId > 0 && $lastAdapter instanceof FormCategory\Section)
		{
			$lastAdapter->contextSection($sectionId);
			$lastAdapter->saveValues($lastId, array_keys($leftElements), $newCategory);
		}
		else
		{
			[$firstType, $firstId] = reset($categoryProperties);
			$firstAdapter = $this->categoryAdapter($firstType);

			$firstAdapter->saveValues($firstId, array_keys($leftElements), $newCategory);
		}
	}

	protected function saveCharacteristic(array $elementIds, int $sectionId, array $property, ?array $field, array $values, bool $resetSection = false) : void
	{
		$propertyStored = $this->characteristicPropertyValues($property, $elementIds);

		if ($field !== null && $sectionId > 0)
		{
			$fewFieldStored = $this->characteristicFieldValues($field, [ $sectionId ]);
			$fieldStored = $fewFieldStored[$sectionId] ?? [];

			if ($resetSection)
			{
				$sectionCharacteristic = [];
			}
			else
			{
				[$sectionCharacteristic, $values] = $this->splitCharacteristicValues(
					$values,
					array_keys($fieldStored),
					$this->characteristicsUsedKeys($elementIds, $propertyStored)
				);
			}

			/** @noinspection TypeUnsafeComparisonInspection */
			if ($fieldStored != $sectionCharacteristic)
			{
				$updateProvider = new \CIBlockSection();
				$updateProvider->Update($sectionId, [ $field['FIELD_NAME'] => $sectionCharacteristic ], false, false);
			}
		}

		$saveValues = $this->prepareCharacteristicValuesSave($elementIds, $propertyStored, $values, $property);

		foreach ($saveValues as $elementId => $value)
		{
			\CIBlockElement::SetPropertyValuesEx($elementId, $property['IBLOCK_ID'], [ $property['ID'] => $value ]);
		}
	}

	protected function prepareCharacteristicValuesSave(array $elementIds, array $savedValues, array $incomingValues, array $property) : array
	{
		$commonValues = $this->characteristicsCommonValues($elementIds, $savedValues);
		$result = [];

		foreach ($elementIds as $elementId)
		{
			$storedValues = $savedValues[$elementId] ?? [];
			$newValues = $incomingValues + array_diff_key($storedValues, $commonValues);

			/** @noinspection TypeUnsafeComparisonInspection */
			if ($newValues == $storedValues) { continue; }

			if ($property['MULTIPLE'] === 'Y')
			{
				$storageValue = $this->rebuildCharacteristicValuesToMultiple($newValues);
				if (empty($storageValue))
				{
					$storageValue = false;
				}
			}
			else
			{
				$storageValue = [ 'VALUE' => $newValues ];
			}

			$result[$elementId] = $storageValue;
		}

		return $result;
	}

	protected function normalizeIncomingCharacteristic(array $property, $values) : array
	{
		if (!is_array($values)) { return []; }
		if ($property['MULTIPLE'] !== 'Y') { return $values; }

		$result = [];

		foreach ($values as $item)
		{
			$result[$item['DESCRIPTION']] = $item['VALUE'];
		}

		return $result;
	}

	public function getRequiredModules() : array
	{
		return [
			'iblock',
		];
	}

	public function show() : void
	{
		global $APPLICATION;

		$propertyId = $this->request->get('property');
		$elementIds = $this->request->get('selected');
		$iblockId = $this->request->get('iblockId');

		Assert::notNull($propertyId, '$_POST[property]');
		Assert::isArray($elementIds, '$_POST[selected]');
		Assert::notNull($elementIds, '$_POST[iblockId]');

		$this->checkIblockReadAccess($iblockId);

		$selectedElements = $this->selectedElements($elementIds, $iblockId);
		$isElementsLimited = false;

		if (count($selectedElements) > self::elementsLimit())
		{
			$selectedElements = array_slice($selectedElements, 0, self::elementsLimit(), true);
			$isElementsLimited = true;
		}

		$elementIds = array_keys($selectedElements);

		$characteristicProperty = $this->characteristicProperty($propertyId);
		$characteristicValues = $this->characteristicPropertyValues($characteristicProperty, $elementIds);
		$parentCharacteristicValues = $this->parentCharacteristicValues($characteristicProperty, $elementIds);

		$categoryProperties = $this->categoryProperties($iblockId);
		[$categoryValues] = $this->categoryValues($categoryProperties, $elementIds);
		[$firstCategoryPropertyType, $firstCategoryPropertyId] = reset($categoryProperties);
		$firstCategoryProperty = $this->categoryProperty($iblockId, $firstCategoryPropertyType, $firstCategoryPropertyId);
		$sectionCategoryProperties = ArrayHelper::column(array_filter($categoryProperties, static function(array $property) {
			return $property[0] === FormCategory\Registry::SECTION;
		}), 1);

		if (!empty($sectionCategoryProperties))
		{
			$sectionGroups = $this->groupBySection($selectedElements);
			$sectionIds = array_diff(array_keys($sectionGroups), [ self::NO_SECTION ]);
			$sectionsRows = $this->sectionFields($sectionIds);
		}
		else
		{
			$sectionGroups = [ self::NO_SECTION => $elementIds ];
			$sectionsRows = [];
		}

		$elementNames = $this->elementNames($elementIds);

		?>
		<form action="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam()) ?>" method="post" name="form_avito_massive_edit" id="form_avito_massive_edit">
			<input type="hidden" name="massiveEditAction" value="save" />
			<input type="hidden" name="property" value="<?= $propertyId ?>" />
			<?php
			if ($isElementsLimited)
			{
				echo BeginNote('style="margin-top: -10px;"');
				echo self::getLocale('ELEMENTS_LIMITED', ['#COUNT#' => self::elementsLimit()]);
				echo EndNote();
			}

			$index = 0;

			foreach ($sectionGroups as $sectionId => $sectionGroup)
			{
				if (count($sectionGroups) > 1)
				{
					if ($sectionId === self::NO_SECTION)
					{
						echo sprintf(
							'<span class="bx-avito-massive-edit-panel-heading">%s <small>%s</small></span>',
							self::getLocale('NO_SECTION'),
							self::getLocale('NO_SECTION_HELP')
						);
					}
					else
					{
						echo sprintf(
							'<span class="bx-avito-massive-edit-panel-heading">%s <small>%s</small></span>',
							$sectionsRows[$sectionId]['NAME'] ?? self::getLocale('UNKNOWN_SECTION'),
							self::getLocale('SECTION_TITLE_HELP')
						);
					}
				}

				foreach ($this->groupByCategory($sectionGroup, $categoryValues) as $category => $groupIds)
				{
					if ($category === self::NO_CATEGORY) { $category = null; }

					$groupCharacteristics = $this->characteristicsCommonValues($groupIds, $characteristicValues);
					$groupParentCharacteristics = $this->characteristicsCommonValues($groupIds, $parentCharacteristicValues);
					$groupCount = count($groupIds);

					if (!empty($sectionCategoryProperties))
					{
						$groupCharacteristics = $groupParentCharacteristics + $groupCharacteristics;
						$groupParentCharacteristics = [];
					}

					?>
					<div class="bx-avito-massive-edit-panel">
						<?php
						echo sprintf(
							'<span class="bx-avito-massive-edit-panel__title">%s</span>',
							$category ?? self::getLocale('NO_CATEGORY')
						);
						?>
						<input type="hidden" name="VALUES[<?= $index ?>][SECTION]" value="<?= $sectionId !== self::NO_SECTION ? $sectionId : 0 ?>" />
						<input type="hidden" name="VALUES[<?= $index ?>][ELEMENTS]" value="<?= implode(',', $groupIds) ?>" />
						<input type="hidden" name="VALUES[<?= $index ?>][CATEGORY_ORIGIN]" value="<?= htmlspecialcharsbx($category) ?>">
						<div class="bx-avito-massive-edit-field">
							<?php
							if (
								$firstCategoryPropertyType === FormCategory\Registry::ELEMENT
								|| $firstCategoryPropertyType === FormCategory\Registry::PRODUCT_ELEMENT
							)
							{
								echo Admin\Property\CategoryProperty::getPropertyFieldHtml(
									$firstCategoryProperty,
									[ 'VALUE' => $category ],
									[ 'VALUE' => "VALUES[$index][CATEGORY]" ]
								);
							}
							else
							{
								echo Admin\Property\CategoryField::getEditFormHTML(
									$firstCategoryProperty,
									[ 'NAME' => "VALUES[$index][CATEGORY]", 'VALUE' => $category ]
								);
							}
							?>
						</div>
						<div class="bx-avito-massive-edit-field">
							<span class="bx-avito-massive-edit-field__label">
								<?= $characteristicProperty['NAME'] ?>
							</span>
							<?php
							if ($characteristicProperty['MULTIPLE'] === 'Y')
							{
								echo Admin\Property\CharacteristicProperty::getPropertyFieldHtmlMulty(
									$characteristicProperty,
									$this->rebuildCharacteristicValuesToMultiple($groupCharacteristics, 'old'),
									[
										'VALUE' => "VALUES[$index][CHARACTERISTICS]",
										'PARENT_VALUE' => $groupParentCharacteristics,
										'CATEGORY_PROPERTIES' => [
											[ FormCategory\Registry::MASSIVE_EDIT, $index ],
										],
									]
								);
							}
							else
							{
								echo Admin\Property\CharacteristicProperty::getPropertyFieldHtml(
									$characteristicProperty,
									[ 'VALUE' => $groupCharacteristics ],
									[
										'VALUE' => "VALUES[$index][CHARACTERISTICS]",
										'PARENT_VALUE' => $groupParentCharacteristics,
										'CATEGORY_PROPERTIES' => [
											[ FormCategory\Registry::MASSIVE_EDIT, $index ],
										],
									]
								);
							}
							?>
						</div>
						<details>
							<summary><?= Word::declension($groupCount, [
								self::getLocale('FOR_ELEMENTS_1', [ '#COUNT#' => $groupCount ]),
								self::getLocale('FOR_ELEMENTS_2', [ '#COUNT#' => $groupCount ]),
								self::getLocale('FOR_ELEMENTS_5', [ '#COUNT#' => $groupCount ]),
							]) ?></summary>
							<ul>
								<?php
								$elements = array_slice($groupIds, 0, self::SHOW_DETAILS_LIMIT);
								foreach ($elements as $elementId)
								{
									echo sprintf('<li>[%s] %s</li>', $elementId, $elementNames[$elementId]);
								}
								if ($groupCount > self::SHOW_DETAILS_LIMIT)
								{
									echo '<li>...</li>';
								}
								?>
							</ul>
						</details>
					</div>
					<?php

					$index++;
				}
			}
			?>
		</form>
		<?php
	}

	protected function characteristicProperty(int $propertyId) : array
	{
		$queryProperty = Iblock\PropertyTable::getList([
			'filter' => [ '=ID' => $propertyId ],
			'limit' => 1,
		]);
		$characteristicProperty = $queryProperty->fetch();

		if ($characteristicProperty === false)
		{
			throw new Main\ObjectNotFoundException('characteristic property not found');
		}

		if ($characteristicProperty['USER_TYPE'] !== Admin\Property\CharacteristicProperty::USER_TYPE)
		{
			throw new Main\ArgumentException('characteristic property not valid user type');
		}

		if ($characteristicProperty['MULTIPLE'] === 'Y' && $characteristicProperty['WITH_DESCRIPTION'] !== 'Y')
		{
			throw new Main\SystemException(sprintf('%s: %s',
				$characteristicProperty['NAME'],
				self::getLocale('CHARACTERISTICS_PROPERTY_NEED_DESCRIPTION')
			));
		}

		$characteristicProperty['USER_TYPE_SETTINGS'] = $characteristicProperty['USER_TYPE_SETTINGS_LIST'];

		return $characteristicProperty;
	}

	protected function categoryAdapter(string $type) : FormCategory\Behavior
	{
		return $this->once('categoryAdapter', function(string $type) {
			return FormCategory\Registry::make($type);
		}, $type);
	}

	protected function characteristicField(int $iblockId) : ?array
	{
		$fields = Admin\Property\ValueInherit\Characteristic::sectionFields($iblockId);

		return reset($fields) ?: null;
	}

	protected function categoryProperties(int $iblockId) : array
	{
		$result = Admin\Property\ValueInherit\Category::properties($iblockId);

		if (empty($result))
		{
			throw new Main\SystemException(self::getLocale('CATEGORY_PROPERTY_MISSING'));
		}

		return $result;
	}

	protected function categoryValues(array $categoryProperties, array $elementIds) : array
	{
		$leftElements = array_flip($elementIds);
		$values = [];
		$filled = [];

		foreach ($categoryProperties as $index => [$categoryType, $categoryPropertyId])
		{
			if (empty($leftElements)) { break; }

			$formCategory = $this->categoryAdapter($categoryType);

			foreach (array_chunk($leftElements, static::CHUNK_SIZE, true) as $leftChunk)
			{
				if ($formCategory instanceof FormCategory\Section)
				{
					$stored = $formCategory->elementValuesWithSection($categoryPropertyId, array_keys($leftChunk));
					$stored = array_filter($stored, static function(array $value) { return !empty($value['VALUE']); });

					$values += ArrayHelper::column($stored, 'VALUE');
				}
				else
				{
					$stored = $formCategory->elementValues($categoryPropertyId, array_keys($leftChunk));
					$stored = array_filter($stored);

					$values += $stored;
				}

				$filled[$index] = $stored;
			}

			$leftElements = array_diff_key($leftElements, $values);
		}

		return [$values, $filled];
	}

	protected function categoryProperty(int $iblockId, string $propertyType, string $propertyId) : array
	{
		if (
			$propertyType === FormCategory\Registry::ELEMENT
			|| $propertyType === FormCategory\Registry::PRODUCT_ELEMENT
		)
		{
			$result = $this->elementCategoryProperty($propertyId);
		}
		else if ($propertyType === FormCategory\Registry::SECTION)
		{
			$result = $this->sectionCategoryField($iblockId, $propertyId);
		}
		else if ($propertyType === FormCategory\Registry::PRODUCT_SECTION)
		{
			if (!Main\Loader::includeModule('catalog'))
			{
				throw new Main\SystemException('cant load catalog module');
			}

			$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

			if (empty($catalog['PRODUCT_IBLOCK_ID']))
			{
				throw new Main\SystemException('product iblock not linked for offers');
			}

			$result = $this->sectionCategoryField((int)$catalog['PRODUCT_IBLOCK_ID'], $propertyId);
		}
		else
		{
			throw new Main\ArgumentException(self::getLocale('CATEGORY_PROPERTY_WRONG_TYPE'));
		}

		return $result;
	}

	protected function elementCategoryProperty(int $categoryPropertyId) : array
	{
		$queryProperty = Iblock\PropertyTable::getList([
			'filter' => [ '=ID' => $categoryPropertyId ],
			'limit' => 1,
		]);

		$categoryProperty = $queryProperty->fetch();

		if ($categoryProperty === false)
		{
			throw new Main\ObjectNotFoundException(self::getLocale('CATEGORY_PROPERTY_NOT_FOUND', ['#ID#' => $categoryPropertyId]));
		}

		if ($categoryProperty['USER_TYPE'] !== Admin\Property\CategoryProperty::USER_TYPE)
		{
			throw new Main\ArgumentException(self::getLocale('CATEGORY_PROPERTY_WRONG_TYPE'));
		}

		$categoryProperty['USER_TYPE_SETTINGS'] = $categoryProperty['USER_TYPE_SETTINGS_LIST'];

		return $categoryProperty;
	}

	protected function sectionCategoryField(int $iblockId, string $fieldCode) : array
	{
		global $USER_FIELD_MANAGER;

		$entityId = sprintf('IBLOCK_%d_SECTION', $iblockId);
		$result = null;

		foreach ($USER_FIELD_MANAGER->GetUserFields($entityId, 0, LANGUAGE_ID) as $field)
		{
			if ($field['FIELD_NAME'] === $fieldCode)
			{
				$result = $field;
				break;
			}
		}

		if ($result === null)
		{
			throw new Main\ObjectNotFoundException(self::getLocale('CATEGORY_PROPERTY_NOT_FOUND', ['#ID#' => $fieldCode]));
		}

		if ($result['USER_TYPE_ID'] !== Admin\Property\CategoryField::USER_TYPE_ID)
		{
			throw new Main\ObjectNotFoundException(self::getLocale('CATEGORY_PROPERTY_WRONG_TYPE'));
		}

		return $result;
	}

	protected function selectedElements(array $elementIds, int $iblockId) : array
	{
		$result = [];

		foreach ($elementIds as $id)
		{
			if (is_numeric($id))
			{
				$elementId = (int)$id;

				$result[$elementId] = self::NO_SECTION;
			}
			else if (mb_strpos($id, 'E') === 0)
			{
				$elementId = (int)mb_substr($id, 1);

				$result[$elementId] = self::NO_SECTION;
			}
			else if (mb_strpos($id, 'S') === 0)
			{
				$sectionId = (int)mb_substr($id, 1);

				$result += array_fill_keys(
					$this->sectionElements($sectionId, $iblockId),
					$sectionId
				);
			}
		}

		if (empty($result))
		{
			throw new Main\ArgumentException(self::getLocale('ELEMENTS_NOT_FOUND'));
		}

		return $result;
	}

	protected function sectionElements(int $sectionId, int $iblockId) : array
	{
		if ($sectionId <= 0) { return []; }

		$result = [];

		$queryElements = \CIBlockElement::GetList(
			[],
			[
				'IBLOCK_ID' => $iblockId,
				'SECTION_ID' => $sectionId,
				'INCLUDE_SUBSECTIONS' => 'Y'
			],
			false,
			[ 'nTopCount' => self::elementsLimit() + 1 ],
			[ 'ID' ]
		);

		while ($row = $queryElements->Fetch())
		{
			$result[] = (int)$row['ID'];
		}

		return $result;
	}

	protected function characteristicPropertyValues(array $property, array $elementIds) : array
	{
		$result = [];
		$isMultiple = ($property['MULTIPLE'] === 'Y');

		foreach (array_chunk($elementIds, static::CHUNK_SIZE) as $chunkIds)
		{
			$queryValue = \CIBlockElement::GetPropertyValues(
				$property['IBLOCK_ID'],
				[ '=ID' => $chunkIds ],
				$isMultiple,
				[ 'ID' => $property['ID'] ]
			);

			while ($row = $queryValue->Fetch())
			{
				if (empty($row[$property['ID']])) { continue; }

				if ($isMultiple)
				{
					if (empty($row['DESCRIPTION'][$property['ID']])) { continue; }

					$propertyValue = array_map(static function ($value) use ($property) {
						return Admin\Property\CharacteristicProperty::convertFromDB($property, [ 'VALUE' => $value ])['VALUE'];
					}, (array)$row[$property['ID']]);

					$result[$row['IBLOCK_ELEMENT_ID']] = array_combine(
						(array)$row['DESCRIPTION'][$property['ID']],
						$propertyValue
					);
				}
				else
				{
					$propertyValue = Admin\Property\CharacteristicProperty::convertFromDB($property, [ 'VALUE' => $row[$property['ID']] ]);

					$result[$row['IBLOCK_ELEMENT_ID']] = $propertyValue['VALUE'];
				}
			}
		}

		return $result;
	}

	protected function characteristicFieldValues(array $field, array $sectionIds) : array
	{
		$result = [];
		$fieldName = $field['FIELD_NAME'];

		foreach (array_chunk($sectionIds, static::CHUNK_SIZE) as $chunkIds)
		{
			$queryValue = \CIBlockSection::GetList(
				[],
				[ 'IBLOCK_ID' => $field['IBLOCK_ID'], 'ID' => $chunkIds ],
				false,
				[ 'ID', $fieldName ]
			);

			while ($row = $queryValue->Fetch())
			{
				if (empty($row[$fieldName])) { continue; }

				$result[$row['ID']] = Admin\Property\CharacteristicField::parseFieldValue($row[$fieldName]);
			}
		}

		return $result;
	}

	protected function splitCharacteristicValues(array $characteristic, array $sectionKeys, array $elementKeys) : array
	{
		$sectionKeys = array_flip($sectionKeys);
		$elementKeys = array_flip($elementKeys);
		$categoryKeys = array_diff_key(array_flip(CategoryLevelFacade::tags()), $elementKeys);

		$sectionValues = array_intersect_key($characteristic, $sectionKeys) + array_intersect_key($characteristic, $categoryKeys);
		$elementValues = array_intersect_key($characteristic, $elementKeys) + array_diff_key($characteristic, $sectionValues);

		return [ $sectionValues, $elementValues ];
	}

	protected function parentCharacteristicValues(array $property, array $elementIds) : array
	{
		$result = [];
		foreach (array_chunk($elementIds, static::CHUNK_SIZE) as $chunkIds)
		{
			$result += Admin\Property\ValueInherit\Characteristic::parentValues($chunkIds, $property['IBLOCK_ID']);
		}
		return $result;
	}

	protected function groupBySection(array $elementToSection) : array
	{
		$result = [];

		foreach ($elementToSection as $elementId => $sectionId)
		{
			$result[$sectionId][] = $elementId;
		}

		return $result;
	}

	protected function groupByCategory(array $elementIds, array $categoryValues) : array
	{
		$result = [];

		foreach ($elementIds as $elementId)
		{
			$category = $categoryValues[$elementId] ?? self::NO_CATEGORY;

			if (!isset($result[$category]))
			{
				$result[$category] = [];
			}

			$result[$category][] = $elementId;
		}

		return $result;
	}

	protected function characteristicsCommonValues(array $elementIds, array $characteristicValues) : array
	{
		$characteristicValues = array_intersect_key($characteristicValues, array_flip($elementIds));

		if (count($characteristicValues) < count($elementIds)) { return []; }
		if (count($characteristicValues) === 1) { return reset($characteristicValues); }

		$commonValues = array_intersect_assoc(...$characteristicValues);

		foreach ($commonValues as $tag => $value)
		{
			if (!is_array($value)) { continue; }

			$tagValues = array_column($characteristicValues, $tag);

			$equals = array_reduce($tagValues, function ($carry, $item) use ($value) {
				return $carry && $item == $value;
			}, true);

			if (!$equals)
			{
				unset($commonValues[$tag]);
			}
		}

		return $commonValues;
	}

	protected function characteristicsUsedKeys(array $elementIds, array $characteristicValues) : array
	{
		$characteristicValues = array_intersect_key($characteristicValues, array_flip($elementIds));

		return array_keys(array_reduce($characteristicValues, static function(array $carry, array $one) {
			return $carry + $one;
		}, []));
	}

	protected function sectionFields(array $sectionIds) : array
	{
		if (empty($sectionIds)) { return []; }

		$query = Iblock\SectionTable::getList([
			'filter' => [ '=ID' => $sectionIds ],
			'select' => [ 'ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN' ],
		]);

		return ArrayHelper::columnToKey($query->fetchAll(), 'ID');
	}

	protected function elementNames(array $elementIds) : array
	{
		if (empty($elementIds)) { return []; }

		$result = [];

		foreach (array_chunk($elementIds, static::CHUNK_SIZE) as $chunkIds)
		{
			$elements = Iblock\ElementTable::getList([
				'filter' => [ 'ID' => $chunkIds ],
				'select' => [ 'ID', 'NAME' ]
			])->fetchAll();

			$result += array_column($elements, 'NAME', 'ID');
		}

		return $result;
	}

	protected function rebuildCharacteristicValuesToMultiple(array $values, string $keyPrefix = 'n') : array
	{
		$result = [];
		$index = 1;

		foreach ($values as $key => $value)
		{
			$result["$keyPrefix$index"] = [
				'VALUE' => $value,
				'DESCRIPTION' => $key
			];
			$index++;
		}

		return $result;
	}

	protected static function elementsLimit() : int
	{
		return (int)Config::getOption('massive_edit_elements_limit', 20000);
	}

	public function checkIblockReadAccess($iblockId) : void
	{
		if (!\CIBlockRights::UserHasRightTo($iblockId, $iblockId, "iblock_admin_display"))
		{
			throw new Main\AccessDeniedException(self::getLocale('READ_ACCESS_DENIED'));
		}
	}

	protected function checkElementsWriteAccess(int $iblockId, array $elementIds) : void
	{
		if (\CIBlock::GetArrayByID($iblockId, 'RIGHTS_MODE') === 'E')
		{
			foreach ($elementIds as $elementId)
			{
				if (!\CIBlockElementRights::UserHasRightTo($iblockId, $elementId, "element_edit"))
				{
					throw new Main\AccessDeniedException(self::getLocale('WRITE_ACCESS_DENIED'));
				}
			}
		}
		else if (\CIBlock::GetPermission($iblockId) < 'W')
		{
			throw new Main\AccessDeniedException(self::getLocale('WRITE_ACCESS_DENIED'));
		}
	}
}