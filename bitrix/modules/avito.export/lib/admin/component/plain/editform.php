<?php

namespace Avito\Export\Admin\Component\Plain;

use Bitrix\Main;
use Avito\Export;
use Avito\Export\Admin\UserField;

abstract class EditForm extends Export\Admin\Component\Base\EditForm
{
	use Export\Concerns\HasLocale;

	public function prepareComponentParams($params)
	{
		$params['FIELDS'] = $this->extendFields($params['FIELDS']);

		return $params;
	}

	protected function extendFields(array $fields) : array
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$field += [
				'TAB' => 'COMMON',
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => 'Y',
				'EDIT_FORM_LABEL' => $field['NAME'],
				'LIST_COLUMN_LABEL' => $field['NAME'],
				'FIELD_NAME' => $name,
				'SETTINGS' => [],
			];

			if (!isset($field['USER_TYPE']) && isset($field['TYPE']))
			{
				$field['USER_TYPE'] = UserField\Registry::description($field['TYPE']);
			}

			$result[$name] = $field;
		}

		return $result;
	}

	public function validate(array $data, array $fields = null) : Main\Result
	{
		$result = new Main\Result();

		foreach ((array)$fields as $fieldName => $userField)
		{
			if (!empty($userField['DEPEND_HIDDEN'])) { continue; }
			if (!empty($userField['HIDDEN']) && $userField['HIDDEN'] !== 'N') { continue; }

			$input = Export\Utils\Field::getChainValue($data, $fieldName, Export\Utils\Field::GLUE_BRACKET);

			if ($userField['MULTIPLE'] === 'Y')
			{
				$values = is_array($input) ? $input : [];
			}
			else
			{
				$values = !Export\Utils\Value::isEmpty($input) ? [ $input ] : [];
			}

			if (!empty($values))
			{
				foreach ($values as $value)
				{
					$checkResult = $this->checkUserFieldValue($userField, $value);

					if (!$checkResult->isSuccess())
					{
						$result->addErrors($checkResult->getErrors());
					}
				}
			}
			else if ($userField['MANDATORY'] === 'Y')
			{
				if (isset($userField['DEPRECATED']) && $userField['DEPRECATED'] === 'Y') { continue; }

				$result->addError(new Main\Error(self::getLocale('VALIDATE_REQUIRED', [
					'#FIELD_NAME#' => $userField['EDIT_FORM_LABEL'] ?: $fieldName
				])));
			}
		}

		return $result;
	}

	protected function checkUserFieldValue(array $userField, $value) : Main\Result
	{
		if (empty($userField['USER_TYPE']['CLASS_NAME']) || !is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'CheckFields'])) { return new Main\Result(); }

		$result = new Main\Result();
		$userErrors = call_user_func(
			[$userField['USER_TYPE']['CLASS_NAME'], 'CheckFields'],
			$userField,
			$value
		);

		if (!empty($userErrors) && is_array($userErrors))
		{
			foreach ($userErrors as $userError)
			{
				$result->addError(new Main\Error($userError['text']));
			}
		}

		return $result;
	}

	public function getFields(array $select = [], array $item = null) : array
	{
		$result = $this->getAllFields();
		$result = $this->applyFieldsSelect($result, $select);

		return $result;
	}

	protected function getAllFields() : array
	{
		return (array)$this->getComponentParam('FIELDS');
	}

	protected function applyFieldsSelect(array $fields, array $select) : array
	{
		if (empty($select)) { return $fields; }

		$selectMap = array_flip($select);

		return array_intersect_key($fields, $selectMap);
	}
}
