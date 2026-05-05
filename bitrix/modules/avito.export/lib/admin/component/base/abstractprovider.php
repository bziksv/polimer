<?php

namespace Avito\Export\Admin\Component\Base;

use Bitrix\Main;
use Avito\Export\Admin\UserField;

abstract class AbstractProvider
{
	/** @var \Avito\Export\Components\AdminFormEdit */
	protected $component;

	public function __construct(\CBitrixComponent $component)
	{
		$this->component = $component;
	}

	public function prepareComponentParams($params)
	{
		return $params;
	}

	/** @return string[] */
	public function getRequiredParams() : array
	{
		return [];
	}

	/** @return string[] */
	public function getRequiredModules() : array
	{
		return [];
	}

	public function getComponentResult($key)
	{
		return $this->component->arResult[$key] ?? null;
	}

	public function getComponentParam($key)
	{
		return $this->component->arParams[$key] ?? null;
	}

	public function setComponentParam($key, $value):void
	{
		$this->component->arParams[$key] = $value;
	}

	public function getComponentLang($key, $replaces = null) : string
	{
		return $this->component->getLangMessage($key, $replaces);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function processAjaxAction($action, $data) : array
	{
		throw new Main\SystemException('ACTION_NOT_FOUND');
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function processPostAction($action, $data) : void
	{
		throw new Main\SystemException('ACTION_NOT_FOUND');
	}

	protected function extendField(string $name, array $field) : array
	{
		$field += [
			'MULTIPLE' => 'N',
			'EDIT_IN_LIST' => 'Y',
			'EDIT_FORM_LABEL' => $field['NAME'],
			'LIST_COLUMN_LABEL' => $field['NAME'],
			'FIELD_NAME' => $name,
			'SETTINGS' => [],
		];

		if (isset($field['TYPE']))
		{
			$field['USER_TYPE'] = UserField\Registry::description($field['TYPE']);
		}

		return $field;
	}
}
