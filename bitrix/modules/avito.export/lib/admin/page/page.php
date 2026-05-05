<?php

namespace Avito\Export\Admin\Page;

use Avito\Export\Admin\Access;
use Avito\Export\Concerns;
use Avito\Export\Admin\UserField;
use Bitrix\Main;

abstract class Page
{
	use Concerns\HasLocale;
	
	protected $errors = [];
	protected $warnings = [];
	protected $messages = [];
	protected $request;

	public function __construct(Main\HttpRequest $request = null)
	{
		$this->request = $request ?? Main\Application::getInstance()->getContext()->getRequest();
	}

	public function isAjaxRequest() : bool
	{
		return $this->request->isAjaxRequest();
	}

	public function checkSession() : void
	{
		if (!check_bitrix_sessid())
		{
			throw new Main\SystemException(self::getLocale('SESSION_EXPIRED'));
		}
	}

	public function checkReadAccess() : void
	{
		if (!Access::isReadAllowed())
		{
			throw new Main\AccessDeniedException(self::getLocale('READ_ACCESS_DENIED'));
		}
	}

	public function checkSaveAccess() : void
	{
		if (!Access::isWriteAllowed())
		{
			throw new Main\AccessDeniedException(self::getLocale('SAVE_ACCESS_DENIED'));
		}
	}

	public function loadModules() : void
	{
		$modules = $this->getRequiredModules();

		foreach ($modules as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException(self::getLocale('REQUIRE_MODULE', ['#MODULE#' => $module]));
			}
		}
	}

	public function getRequiredModules() : array
	{
		return [];
	}

	public function refreshPage() : void
	{
		$url = $this->request->getRequestUri();

		LocalRedirect($url);
	}

	public function addWarning($message) : void
	{
		$this->warnings[] = $message;
	}

	public function addMessage($message) : void
	{
		$this->messages[] = $message;
	}

	public function hasWarnings() : bool
	{
		return !empty($this->warnings);
	}

	public function hasMessages() : bool
	{
		return !empty($this->messages);
	}

	public function showWarnings() : void
	{
		if (empty($this->warnings)) { return; }

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => implode('<br />', $this->warnings),
			'HTML' => true,
		]);
	}

	public function showMessages() : void
	{
		if (empty($this->messages)) { return; }

		\CAdminMessage::ShowMessage([
			'TYPE' => 'OK',
			'MESSAGE' => implode('<br />', $this->messages),
			'HTML' => true,
		]);
	}

	public function addError($message) : void
	{
		$this->errors[] = $message;
	}

	public function hasErrors() : bool
	{
		return !empty($this->errors);
	}

	public function showErrors() : void
	{
		if (empty($this->errors)) { return; }

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => implode('<br />', $this->errors),
			'HTML' => true,
		]);
	}

	protected function extendFields(array $fields, array $extensionMap = [], array $defaults = []) : array
	{
		foreach ($fields as $name => &$field)
		{
			$field = ($extensionMap[$name] ?? []) + $field + $defaults;
			$field = $this->extendField($name, $field);
		}
		unset($field);

		return $fields;
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
