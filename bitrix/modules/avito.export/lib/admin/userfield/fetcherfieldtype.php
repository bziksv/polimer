<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Feed\Source;
use Bitrix\Main;
use Bitrix\Main\UI\Extension;

class FetcherFieldType extends EnumerationType
{
	use Concerns\HasLocale;

	public static function GetList($userField) : \CDBResult
	{
		$contexts = static::settingIblockContexts($userField);
		$pool = new Source\FetcherPool();
		$variants = [];
		$hasDisabled = isset($userField['SETTINGS']['DISABLED']);

		foreach ($pool->all() as $type => $source)
		{
			if ($hasDisabled && in_array($type, $userField['SETTINGS']['DISABLED'], true)) { continue; }

			$group = $source->title();
			$typeVariants = [];

			foreach ($contexts as $context)
			{
				foreach ($source->fields($context) as $field)
				{
					if (!$field->selectable())
					{
						continue;
					}

					if (
						isset($userField['SETTINGS']['TYPE'])
						&& !in_array($field->type(), $userField['SETTINGS']['TYPE'], true)
					)
					{
						continue;
					}

					$id = $type . '.' . $field->id();

					$typeVariants[$id] = [
						'ID' => $id,
						'VALUE' => $field->name(),
						'GROUP' => $group,
					];
				}
			}

			if (isset($userField['SETTINGS']['FILTERED'][$type]))
			{
				$filter = array_map(
					static function(string $field) use ($type) { return $type . '.' . $field; },
					$userField['SETTINGS']['FILTERED'][$type]
				);

				$typeVariants = array_intersect_key(
					$typeVariants,
					array_flip($filter)
				);
			}

			$variants += $typeVariants;
		}

		$result = new \CDBResult();
		$result->InitFromArray(array_values($variants));

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		return static::editHtml($userField, $htmlControl);
	}

	public static function GetAdminListViewHTMLMulty($userField, $htmlControl) : string
	{
		$value = $userField['VALUE'] ?? $htmlControl['VALUE'];

		if (
			isset($userField['SETTINGS']['IBLOCK_MULTIPLE'])
			&& $userField['SETTINGS']['IBLOCK_MULTIPLE'] === 'Y'
			&& is_array($htmlControl['VALUE'])
		)
		{
			$newValue = [];

			foreach ($value as $one)
			{
				if (empty($one)) { continue; }

				if (is_array($one))
				{
					array_push($newValue, ...$one);
				}
				else
				{
					$newValue[] = $one;
				}
			}

			$htmlControl['VALUE'] = array_unique($newValue);
		}

		return parent::GetAdminListViewHTMLMulty($userField, $htmlControl);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl) : string
	{
		$selected = isset($htmlControl['VALUE']) && is_array($htmlControl['VALUE']) ? $htmlControl['VALUE'] : [];
		$partials = [];
		$inputName = preg_replace('/\[]$/', '', $htmlControl['NAME']);
		$contexts = static::settingIblockContexts($userField);

		foreach ($contexts as $context)
		{
			$iblockId = $context->iblockId();
			$iblockField = $userField;
			$iblockField['SETTINGS']['IBLOCK_METHOD'] = 'context';
			$iblockField['SETTINGS']['IBLOCK_CONTEXT'] = $context;

			if (isset($userField['SETTINGS']['IBLOCK_MULTIPLE']) && $userField['SETTINGS']['IBLOCK_MULTIPLE'] === 'Y')
			{
				$iblockSelect = static::editHtml($iblockField, [
					'VALUE' => $selected[$iblockId] ?? null,
					'NAME' => $inputName . '[' . $iblockId . '][]',
				], [
					'multiple' => true,
				]);
			}
			else
			{
				$iblockSelect = static::editHtml($iblockField, [
					'VALUE' => $selected[$iblockId] ?? null,
					'NAME' => $inputName . '[' . $iblockId . ']',
				]);
			}

			if (count($contexts) > 1)
			{
				$template = <<<HTML
					<div style="margin-bottom: 10px;">
						<strong style="display: block; margin-bottom: 5px;">[%s] %s</strong>
						%s
					</div>
HTML;

				$partials[] = sprintf(
					$template,
					$iblockId,
					static::iblockTitle($iblockId),
					$iblockSelect
				);
			}
			else
			{
				$partials[] = $iblockSelect;
			}
		}

		return implode('', $partials);
	}

	protected static function iblockTitle(int $iblockId) : string
	{
		$default = self::getLocale('IBLOCK_TITLE', [ '#ID#' => $iblockId ]);

		if ($iblockId <= 0 || !Main\Loader::includeModule('iblock')) { return $default; }

		return \CIBlock::GetArrayByID($iblockId, 'NAME') ?: $default;
	}

	protected static function editHtml($userField, $htmlControl, array $attributes = []) : string
	{
		Extension::load('avitoexport.vendor.select2');

		$id = preg_replace('/\W+/', '_', $htmlControl['NAME']);
		$id = trim($id, '_');

		$html = static::editSelect($userField, $htmlControl, [ 'id' => $id ] + $attributes);
		$html .= sprintf('<script> $("#%s").select2({ width: 300 }); </script>', $id);

		return $html;
	}

	/**
	 * @param array $userField
	 *
	 * @return array<int, Source\Context>
	 */
	protected static function settingIblockContexts(array $userField) : array
	{
		$method = $userField['SETTINGS']['IBLOCK_METHOD'] ?? 'settings';
		$result = [];

		if ($method === 'feed')
		{
			$feedField = $userField['SETTINGS']['FEED_FIELD'] ?? null;

			Assert::notNull($feedField, '$userField[SETTINGS][FEED_FIELD]');

			$feedId = $userField['ROW'][$feedField] ?? null;

			if (empty($feedId)) { return $result; }

			$feed = Feed\Setup\RepositoryTable::getById($feedId)->fetchObject();

			if ($feed === null) { return $result; }

			foreach ($feed->getIblock() as $iblockId)
			{
				$result[] = $feed->iblockContext($iblockId);
			}
		}
		else if ($method === 'context')
		{
			$result = [
				$userField['SETTINGS']['IBLOCK_CONTEXT'],
			];
		}
		else
		{
			$iblockIds = (array)($userField['SETTINGS']['IBLOCK_ID'] ?? []);

			foreach ($iblockIds as $iblockId)
			{
				$result[] = new Source\Context($iblockId);
			}
		}

		return $result;
	}
}