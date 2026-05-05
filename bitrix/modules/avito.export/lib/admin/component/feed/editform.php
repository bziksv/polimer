<?php

namespace Avito\Export\Admin\Component\Feed;

use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Watcher;
use Bitrix\Main;

class EditForm extends Admin\Component\Data\EditForm
{
	use Concerns\HasLocale;

	public function validate($data, array $fields = null) : Main\Result
	{
		$result = parent::validate($data, $fields);

		if (!$result->isSuccess()) { return $result; }

		$this->validateIblock($result, $data, $fields);
		$this->validateTags($result, $data, $fields);

		return $result;
	}

	protected function validateIblock(Main\Result $result, $data, array $fields = null) : void
	{
		if (!isset($fields['IBLOCK'])) { return; }

		$iblockIds = (array)($data['IBLOCK'] ?? []);

		Main\Type\Collection::normalizeArrayValuesByInt($iblockIds);

		if (empty($iblockIds))
		{
			$result->addError(new Main\Error(self::getLocale('IBLOCK_REQUIRED')));
		}
	}

	protected function validateTags(Main\Result $result, $data, array $fields = null) : void
	{
		$iblockIds = (array)($data['IBLOCK'] ?? []);

		if (!isset($fields['TAGS']) || empty($iblockIds)) { return; }

		$format = new Feed\Tag\Format();

		foreach ($iblockIds as $iblockId)
		{
			$tagsIblock = $data['TAGS'][$iblockId];
			$dataFields = array_column($tagsIblock, 'VALUE', 'CODE');
			$relatedChecked = [];

			foreach ($format->tags() as $tag)
			{
				$required = $tag->required();
				$tagCode = $tag->name();
				$tagValue = trim($dataFields[$tagCode] ?? '');

				if ($tagValue !== '') { continue; }

				if (is_array($required))
				{
					if (isset($relatedChecked[$tagCode])) { continue; }

					$relatedValues = array_intersect_key($dataFields, array_flip($required));
					$relatedFilled = array_filter($relatedValues, static function($value) { return trim($value) !== ''; });

					if (count($relatedFilled) < count($required))
					{
						$relatedChecked += array_flip($required);

						$message = self::getLocale('TAG_REQUIRED_RELATED', [
							'#CODE#' => $tagCode,
							'#RELATED#' => implode(', ', $required),
						]);
						$result->addError(new Main\Error($message));
					}
				}
				else if ($required === true)
				{
					$message = self::getLocale('TAG_REQUIRED', ['#CODE#' => $tagCode]);
					$result->addError(new Main\Error($message));
				}
			}
		}
	}

	public function getFields(array $select = [], $item = null) : array
	{
		$result = parent::getFields($select, $item);
		$result = $this->applyFieldsDefaults($result, $this->getDefaults());

		return $result;
	}

	protected function getDefaults() : array
	{
		return [
			'NAME' => self::getLocale('DEFAULT_NAME'),
			'HTTPS' => '1',
			'AUTO_UPDATE' => true,
			'REFRESH_PERIOD' => Watcher\Setup\RefreshFacade::PERIOD_SIX_HOURS,
		];
	}

	public function load($primary, array $select = [], $isCopy = false) : array
	{
		$result = parent::load($primary, $select, $isCopy);

		if ($isCopy)
		{
			if (isset($result['NAME']))
			{
				$result['NAME'] = $this->nameCopyMarker($result['NAME']);
			}

			if (isset($result['FILE_NAME']))
			{
				$result['FILE_NAME'] = $this->generateFileName();
			}
		}

		return $result;
	}

	protected function nameCopyMarker($name) : string
	{
		$marker = self::getLocale('COPY_MARKER');

		if (mb_strpos($name, $marker) === false)
		{
			$name .= ' ' . $marker;
		}

		return $name;
	}

	public function add($fields) : Main\ORM\Data\AddResult
	{
		$fields = $this->sanitizeFields($fields);

		return parent::add($fields);
	}

	public function update($primary, $fields) : Main\ORM\Data\UpdateResult
	{
		$fields = $this->sanitizeFields($fields);

		return parent::update($primary, $fields);
	}

	protected function beforeAdd(Main\ORM\Objectify\EntityObject $model) : void
	{
		/** @var Feed\Setup\Model $model */
		$model->setTimestampX(new Main\Type\DateTime());
	}

	protected function beforeUpdate(Main\ORM\Objectify\EntityObject $model) : void
	{
		/** @var Feed\Setup\Model $model */
		$model->setTimestampX(new Main\Type\DateTime());
	}

	public function extend($data, array $select = []) : array
	{
		$result = $data;

		if (!isset($result['FILE_NAME']) || trim($result['FILE_NAME']) === '')
		{
			$result['FILE_NAME'] = $this->generateFileName();
		}

		return $result;
	}

	private function generateFileName() : string
	{
		return 'avito_' . Main\Security\Random::getString(5, true) . '.xml';
	}

	protected function sanitizeFields(array $fields) : array
	{
		if (isset($fields['TAGS']) && is_array($fields['TAGS']))
		{
			foreach ($fields['TAGS'] as $iblockId => $tags)
			{
				$fields['TAGS'][$iblockId] = $this->sanitizeTags($tags);
			}
		}

		if (isset($fields['FILTER']) && is_array($fields['FILTER']))
		{
			foreach ($fields['FILTER'] as $iblockId => $filter)
			{
				$fields['FILTER'][$iblockId] = $this->sanitizeFilter($filter);
			}
		}

		return $fields;
	}

	protected function sanitizeTags($tags) : array
	{
		if (!is_array($tags)) { return []; }

		$result = [];

		foreach ($tags as $tag)
		{
			if (trim($tag['VALUE'] ?? '') === '') { continue; }
			if (array_key_exists('TAG', $tag) && trim($tag['TAG']) === '') { continue; }

			$result[] = $tag;
		}

		return $result;
	}

	protected function sanitizeFilter($filters) : array
	{
		if (!is_array($filters)) { return []; }

		$result = [];

		foreach ($filters as $filter)
		{
			if (!is_array($filter)) { continue; }

			$resultFilter = [];

			foreach ($filter as $item)
			{
				if (!isset($item['VALUE'])) { continue; }
				if (is_string($item['VALUE']) && trim($item['VALUE']) === '') { continue; }

				$resultFilter[] = $item;
			}

			if (empty($resultFilter)) { continue; }

			$result[] = $resultFilter;
		}

		return $result;
	}
}
