<?php

namespace Avito\Export\Admin\Component\Data;

use Avito\Export;
use Bitrix\Main;

class EditForm extends Export\Admin\Component\Base\EditForm
{
	public function prepareComponentParams($params)
	{
		$params['DATA_CLASS_NAME'] = trim($params['DATA_CLASS_NAME']);

		return $params;
	}

	public function getRequiredParams():array
	{
		return [
			'DATA_CLASS_NAME',
		];
	}

	public function getFields(array $select = [], $item = null) : array
	{
		$dataClass = $this->getDataClass();
		$fields = $this->loadTableFields($dataClass);

		return $this->filterSelectFields($fields, $select);
	}

	protected function filterSelectFields(array $fields, array $select) : array
	{
		if (empty($select)) { return $fields; }

		$result = [];

		foreach ($select as $name)
		{
			if (!isset($fields[$name])) { continue; }

			$result[$name] = $fields[$name];
		}

		return $result;
	}

	protected function applyFieldsDefaults(array $fields, array $defaults) : array
	{
		foreach ($fields as $name => &$field)
		{
			if (!isset($defaults[$name]) || $field['EDIT_IN_LIST'] === 'N') { continue; }

			if (!isset($field['SETTINGS']))
			{
				$field['SETTINGS'] = [];
			}

			$field['SETTINGS']['DEFAULT_VALUE'] = $defaults[$name];
		}
		unset($field);

		return $fields;
	}

	/**
	 * @return Export\DB\Table
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function getDataClass() : string
	{
		return $this->getComponentParam('DATA_CLASS_NAME');
	}

	/**
	 * @param class-string<Export\DB\Table> $dataClass
	 *
	 * @return array
	 */
	protected function loadTableFields(string $dataClass) : array
	{
		return $dataClass::getMapDescription();
	}

	public function load($primary, array $select = [], $isCopy = false) : array
	{
		$dataClass = $this->getDataClass();
		$query = $dataClass::getByPrimary($primary);

		if ($result = $query->fetch())
		{
			if ($isCopy)
			{
				unset($result['ID']);
			}
		}
		else
		{
			throw new Main\SystemException($this->getComponentLang('ITEM_NOT_FOUND'));
		}

		return $result;
	}

	public function validate($data, array $fields = null) : Main\Result
	{
		$primary = $data['PRIMARY'] ?? null;
		$sanitizedData = array_diff_key($data, ['PRIMARY' => true]);
		$dataClass = $this->getDataClass();
		$validateResult = new Main\ORM\Data\Result();
		$dataClass::checkFields($validateResult, $primary, $sanitizedData);

		if ($fields === null)
		{
			$result = $validateResult;
		}
		else
		{
			$result = new Main\ORM\Data\Result();
			$fieldsMap = [];

			foreach ($fields as $field)
			{
				$fieldsMap[$field['FIELD_NAME']] = true;
			}

			foreach ($validateResult->getErrors() as $error)
			{
				$entityField = $error->getField();
				$fieldName = $entityField->getName();

				if (isset($fieldsMap[$fieldName]))
				{
					$result->addError($error);
				}
			}
		}

		return $result;
	}

	public function add($fields) : Main\ORM\Data\AddResult
	{
		/** @var Main\ORM\Objectify\EntityObject $model */
		$dataClass = $this->getDataClass();
		$model = $dataClass::createObject();

		$this->passModelValues($model, $fields);
		$this->beforeAdd($model);

		$addResult = $model->save();

		if (!$addResult->isSuccess()) { return $addResult; }

		$this->afterAdd($model);

		return $addResult;
	}

	protected function beforeAdd(Main\ORM\Objectify\EntityObject $model) : void
	{
		// nothing
	}

	protected function afterAdd(Main\ORM\Objectify\EntityObject $model) : void
	{
		// nothing
	}

	public function update($primary, $fields) : Main\ORM\Data\UpdateResult
	{
		/** @var Main\ORM\Objectify\EntityObject $model */
		$dataClass = $this->getDataClass();
		$model = $dataClass::wakeUpObject($primary);

		$this->passModelValues($model, $fields);
		$this->beforeUpdate($model);

		$updateResult = $model->save();

		if (!$updateResult->isSuccess()) { return $updateResult; }

		$this->afterUpdate($model);

		return $updateResult;
	}

	protected function beforeUpdate(Main\ORM\Objectify\EntityObject $model) : void
	{
		// nothing
	}

	protected function afterUpdate(Main\ORM\Objectify\EntityObject $model) : void
	{
		// nothing
	}

	protected function passModelValues(Main\ORM\Objectify\EntityObject $model, array $values) : void
	{
		foreach ($values as $name => $value)
		{
			if (isset($model->primary[$name])) { continue; }

			$model->set($name, $value);
		}
	}
}
