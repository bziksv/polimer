<?php

namespace Avito\Export\Admin\Component\Base;

use Avito\Export\Utils\Field;
use Bitrix\Main;

abstract class EditForm extends AbstractProvider
{
	public function modifyRequest($request, $fields) : array
	{
		$result = $request;

		foreach ($fields as $fieldName => $field)
		{
			if (!isset($request[$fieldName], $field['USER_TYPE']['BASE_TYPE'])) { continue; }

			if ($field['USER_TYPE']['BASE_TYPE'] === 'datetime')
			{
				if (trim($request[$fieldName]) !== '')
				{
					$result[$fieldName] = new Main\Type\DateTime($request[$fieldName]);
				}
				else
				{
					$result[$fieldName] = null;
				}
			}
		}

		return $result;
	}

	abstract public function getFields(array $select = [], array $item = null) : array;

	abstract public function load($primary, array $select = [], $isCopy = false) : array;

	public function extendDefaults(array $data, array $fields) : array
	{
		foreach ($fields as $name => $field)
		{
			if (empty($field['SETTINGS']['DEFAULT_VALUE'])) { continue; }

			$filled = Field::getChainValue($data, $name, Field::GLUE_BRACKET);

			if ($filled !== null) { continue; }

			Field::setChainValue($data, $name, $field['SETTINGS']['DEFAULT_VALUE'], Field::GLUE_BRACKET);
		}

		return $data;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function extend($data, array $select = []) : array
	{
		return $data;
	}

	abstract public function validate(array $data, array $fields = null) : Main\Result;

	abstract public function add(array $fields) : Main\ORM\Data\AddResult;

	abstract public function update($primary, array $fields) : Main\ORM\Data\UpdateResult;
}
