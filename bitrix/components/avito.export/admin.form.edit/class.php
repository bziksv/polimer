<?php

namespace Avito\Export\Components;

use Avito\Export\Admin\Component\Base\EditForm;
use Avito\Export\Utils;
use Bitrix\Main;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

class AdminFormEdit extends \CBitrixComponent
{
	protected static $langPrefix = 'AVITO_EXPORT_FORM_EDIT_';
	/** @var EditForm */
	protected $provider;

	public function onPrepareComponentParams($params)
	{
		$params['FORM_ID'] = trim($params['FORM_ID']);
		$params['TITLE'] = trim($params['TITLE']);
		$params['TITLE_ADD'] = trim($params['TITLE_ADD']);
		$params['BTN_SAVE'] = trim($params['BTN_SAVE']);
		$params['BTN_APPLY'] = trim($params['BTN_APPLY']);
		$params['LIST_URL'] = trim($params['LIST_URL']);
		$params['SAVE_URL'] = trim($params['SAVE_URL']);
		$params['CONTEXT_MENU'] = (array)$params['CONTEXT_MENU'];
		$params['TABS'] = (array)$params['TABS'];
		$params['FORM_BEHAVIOR'] = ($params['FORM_BEHAVIOR'] === 'steps' ? 'steps' : 'tabs');
		$params['COPY'] = (bool)$params['COPY'];
		$params['ALLOW_SAVE'] = isset($params['ALLOW_SAVE']) ? (bool)$params['ALLOW_SAVE'] : true;
		$params['SAVE_PARTIALLY'] = isset($params['SAVE_PARTIALLY']) ? (bool)$params['SAVE_PARTIALLY'] : false;

		if (empty($params['TABS']))
		{
			$params['TABS'] = [
				['name' => $this->getLangMessage('DEFAULT_TAB_NAME')],
			];
		}

		$params['PROVIDER_TYPE'] = trim($params['PROVIDER_TYPE']);

		$provider = $this->getProvider($params['PROVIDER_TYPE']);

		$params = $provider->prepareComponentParams($params);

		return $params;
	}

	public function getLangMessage($code, $replaces = null)
	{
		return Main\Localization\Loc::getMessage(static::$langPrefix . $code, $replaces) ?: $code;
	}

	public function getProvider($providerType = null)
	{
		if ($this->provider === null)
		{
			if (!Main\Loader::includeModule('avito.export'))
			{
				throw new Main\SystemException($this->getLangMessage('REQUIRE_SELF_MODULE'));
			}

			if (!isset($providerType))
			{
				$providerType = $this->arParams['PROVIDER_TYPE'];
			}

			if (!class_exists($providerType)
				|| !is_subclass_of($providerType, EditForm::class))
			{
				throw new Main\SystemException($this->getLangMessage('INVALID_PROVIDER'));
			}

			$this->provider = new $providerType($this);
		}

		return $this->provider;
	}

	public function executeComponent()
	{
		$this->initResult();

		if (!$this->checkParams() || !$this->loadModules())
		{
			$this->showErrors();
			return;
		}

		$templatePage = '';
		$isStepsBehavior = ($this->arParams['FORM_BEHAVIOR'] === 'steps');

		try
		{
			if ($this->hasCancelRequest())
			{
				$this->redirectCancel();
			}

            $this->restoreDelayedMessage();
			$this->loadItem();
			$this->buildContextMenu();
			$this->buildTabs();
			$this->buildButtons();

			$requestStep = $this->getRequestStep();
			$hasRequest = $this->hasRequest();
			$hasSaveRequest = $this->hasSaveRequest();
			$isFoundRequestStep = false;
			$isFirstTab = true;

			if (!empty($this->arParams['PRELOAD']))
			{
				$preloadFields = $this->loadFields($this->arParams['PRELOAD']);

				if ($hasRequest)
				{
					$this->fillRequest($preloadFields);
				}
				else if ($this->getPrimary(true) === null)
				{
					$this->extendDefaults($preloadFields);
				}
			}

			foreach ($this->arResult['TABS'] as &$tab)
			{
				$tabFields = !empty($tab['SELECT']) || $isFirstTab ? $this->loadFields($tab['SELECT']) : [];
				$stepValidateResult = true;

				$this->registerTabFields($tab, $tabFields);

				if ($hasRequest)
				{
					$this->fillRequest($tabFields);
					$this->resolveDependency($tabFields);

					if (
						$isStepsBehavior
						&& (
							$hasSaveRequest // validate all on save
							|| (!$isFoundRequestStep && $requestStep !== $tab['STEP']) // validate previous steps on move
						)
					)
					{
						$stepValidateResult = $this->validateRequest($tabFields);
					}
				}
				else
				{
					if ($this->getPrimary(true) === null)
					{
						$this->extendDefaults($tabFields);
					}

					$this->resolveDependency($tabFields);
				}

				$this->registerFields($tabFields);

				if ($isStepsBehavior && !$isFoundRequestStep)
				{
					$this->arResult['STEP'] = $tab['STEP'];
					$this->arResult['STEP_FINAL'] = $tab['FINAL'];

					if (!$stepValidateResult || $requestStep === $tab['STEP'])
					{
						$isFoundRequestStep = true;
					}
				}

				$isFirstTab = false;
			}
			unset($tab);

			if (!$isStepsBehavior && $hasSaveRequest)
			{
				$this->validateRequest();
			}

			if ($this->hasAjaxAction())
			{
				$this->processAjaxAction();
			}
			elseif ($this->hasPostAction())
			{
				if (!check_bitrix_sessid())
				{
					$this->addError($this->getLangMessage('EXPIRE_SESSION'));
				}

				if (!$this->hasErrors())
				{
					$this->processPostAction();
				}

				if (!$this->hasErrors())
				{
					$this->afterSave($this->getPostAction());
				}
			}
			elseif ($hasSaveRequest)
			{
				$savePrimary = null;

				if (!$this->arParams['ALLOW_SAVE'])
				{
					$this->addError($this->getLangMessage('SAVE_DISALLOW'));
				}
				elseif (!check_bitrix_sessid())
				{
					$this->addError($this->getLangMessage('EXPIRE_SESSION'));
				}
				elseif (!$this->hasErrors())
				{
					$savePrimary = $this->saveFull();
				}
				elseif ($this->arParams['SAVE_PARTIALLY'])
				{
					$savePrimary = $this->savePartially();
				}

				if ($savePrimary !== null && !$this->hasErrors())
				{
					$this->afterSave('save', $savePrimary);
				}
			}

			$this->extendItem();
		}
		catch (Main\SystemException $exception)
		{
			$templatePage = 'exception';
			$this->addError($exception->getMessage());
		}

		$this->setTitle();

		$this->includeComponentTemplate($templatePage);
	}

	protected function initResult()
	{
		$this->arResult['STEP'] = null;
		$this->arResult['STEP_FINAL'] = false;
		$this->arResult['FIELDS'] = [];
		$this->arResult['ITEM'] = [];
		$this->arResult['ITEM_ORIGINAL'] = [];
		$this->arResult['ERRORS'] = [];
		$this->arResult['FIELD_ERRORS'] = [];
		$this->arResult['TABS'] = [];
		$this->arResult['BUTTONS'] = [];
		$this->arResult['HAS_REQUEST'] = false;
	}

	protected function checkParams()
	{
		$result = true;
		$requiredParams = $this->getRequiredParams();

		foreach ($requiredParams as $paramKey)
		{
			if (empty($this->arParams[$paramKey]))
			{
				$result = false;

				$this->addError($this->getLangMessage('PARAM_REQUIRE', [
					'#PARAM#' => $paramKey,
				]));
			}
		}

		return $result;
	}

	protected function getRequiredParams()
	{
		$provider = $this->getProvider();
		$result = ['FORM_ID'] + $provider->getRequiredParams();

		return $result;
	}

	protected function addError($message)
	{
		$this->arResult['ERRORS'][] = $message;
	}

    public function delayMessage(string $message) : void
    {
        $_SESSION[$this->arParams['FORM_ID'] . '_MESSAGE'] = $message;
    }

    protected function restoreDelayedMessage() : void
    {
        $key = $this->arParams['FORM_ID'] . '_MESSAGE';

        if (empty($_SESSION[$key])) { return; }

        $this->arParams['~MESSAGE'] = [
            'TYPE' => 'OK',
            'MESSAGE' => $_SESSION[$key],
        ];
        unset($_SESSION[$key]);
    }

	protected function loadModules()
	{
		$result = true;
		$modules = $this->getRequiredModules();

		foreach ($modules as $module)
		{
			if (!$this->loadModule($module))
			{
				$result = false;
			}
		}

		return $result;
	}

	protected function getRequiredModules()
	{
		$provider = $this->getProvider();

		return $provider->getRequiredModules();
	}

	protected function loadModule($module)
	{
		$result = true;

		if (!Main\Loader::includeModule($module))
		{
			$result = false;

			$this->addError($this->getLangMessage('MODULE_REQUIRE', [
				'#MODULE#' => $module,
			]));
		}

		return $result;
	}

	public function showErrors() : void
	{
		$replaces = [
			'&nbsp;' => ' ',
			'&laquo;' => '"',
			'&raquo;' => '"',
		];

		$message = implode('<br />', $this->arResult['ERRORS']);
		$message = str_replace(
			array_keys($replaces),
			array_values($replaces),
			$message
		);

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => $message,
			'HTML' => true,
		]);
	}

	protected function hasCancelRequest()
	{
		return ($this->request->getPost('cancel') !== null);
	}

	protected function redirectCancel()
	{
		LocalRedirect($this->arParams['LIST_URL']);
	}

	protected function loadItem()
	{
		$primary = $this->getPrimary(true);

		if ($primary !== null)
		{
			$provider = $this->getProvider();
			$fieldsSelect = $this->getFieldsSelect();

			$this->arResult['ITEM'] = $provider->load($primary, $fieldsSelect, $this->arParams['COPY']);
			$this->arResult['ITEM_ORIGINAL'] = $this->arResult['ITEM'];
		}
	}

	protected function getPrimary($useOrigin = false)
	{
		$result = null;

		if (!empty($this->arParams['PRIMARY'])
			&& (!$this->arParams['COPY'] || $useOrigin))
		{
			$result = $this->arParams['PRIMARY'];
		}

		return $result;
	}

	protected function getFieldsSelect()
	{
		$result = [];

		foreach ($this->arParams['TABS'] as $tab)
		{
			if (!empty($tab['fields']))
			{
				foreach ($tab['fields'] as $field)
				{
					$result[] = $field;
				}
			}
		}

		return $result;
	}

	protected function buildContextMenu()
	{
		$this->arResult['CONTEXT_MENU'] = $this->arParams['CONTEXT_MENU']; // simple copy, need for future modifications
	}

	protected function buildTabs()
	{
		$paramTabs = $this->arParams['TABS'];
		$countTabs = count($paramTabs);
		$hasFinalTab = false;
		$tabIndex = 0;
		$result = [];

		foreach ($paramTabs as $paramTab)
		{
			$isFinalTab = (!empty($paramTab['final']) || (!$hasFinalTab && $tabIndex === $countTabs - 1));

			if ($isFinalTab)
			{
				$hasFinalTab = true;
			}

			$result[] = [
				'STEP' => $tabIndex,
				'FINAL' => $isFinalTab,
				'DIV' => 'tab' . $tabIndex,
				'TAB' => $paramTab['name'],
				'TITLE' => $paramTab['name'],
				'LAYOUT' => $paramTab['layout'] ?: 'default',
				'SELECT' => $paramTab['fields'] ?: [],
				'FIELDS' => [],
				'HIDDEN' => [],
				'DATA' => isset($paramTab['data']) ? (array)$paramTab['data'] : [],
			];

			$tabIndex++;
		}

		$this->arResult['TABS'] = $result;
	}

	protected function buildButtons()
	{
		if (!empty($this->arParams['BUTTONS']))
		{
			$this->arResult['BUTTONS'] = (array)$this->arParams['BUTTONS'];
		}
		elseif ($this->arParams['FORM_BEHAVIOR'] === 'steps')
		{
			$this->arResult['BUTTONS'] = [
				['BEHAVIOR' => 'previous'],
				['BEHAVIOR' => 'next'],
			];
		}
		else
		{
			$this->arResult['BUTTONS'] = [
				['BEHAVIOR' => 'save'],
				['BEHAVIOR' => 'apply'],
			];
		}
	}

	protected function getRequestStep()
	{
		$stepCount = \count($this->arResult['TABS']);
		$stepIndex = (int)$this->request->getPost('STEP');

		// step action

		$stepAction = $this->request->getPost('stepAction');

		switch (true)
		{
			case ($stepAction === 'previous'):
				$stepIndex -= 1;
				break;

			case ($stepAction === 'next'):
				$stepIndex += 1;
				break;

			case (is_numeric($stepAction)):
				$stepIndex = (int)$stepAction;
				break;
		}

		// normalize index

		if ($stepIndex <= 0)
		{
			$stepIndex = 0;
		}
		elseif ($stepIndex >= $stepCount)
		{
			$stepIndex = $stepCount - 1;
		}

		return $stepIndex;
	}

	protected function hasRequest()
	{
		$result = (
			$this->hasStepRequest()
			|| $this->hasSaveRequest()
			|| $this->hasReloadRequest()
			|| $this->hasPostAction()
			|| $this->hasAjaxAction()
		);

		$this->arResult['HAS_REQUEST'] = $result;

		return $result;
	}

	protected function hasStepRequest() : bool
	{
		return ($this->request->getPost('stepAction') !== null);
	}

	protected function hasSaveRequest() : bool
	{
		return ($this->request->getPost('apply') !== null || $this->request->getPost('save') !== null);
	}

	protected function hasReloadRequest() : bool
	{
		return ($this->request->getPost('reload') !== null);
	}

	protected function hasPostAction() : bool
	{
		return ($this->getPostAction() !== null);
	}

	protected function getPostAction()
	{
		return $this->request->get('postAction');
	}

	protected function hasAjaxAction() : bool
	{
		return ($this->getAjaxAction() !== null);
	}

	protected function getAjaxAction()
	{
		return $this->request->getPost('ajaxAction');
	}

	protected function loadFields($select)
	{
		$provider = $this->getProvider();

		return $provider->getFields((array)$select, $this->arResult['ITEM']);
	}

	protected function registerTabFields(&$tab, $fields)
	{
		foreach ($fields as $fieldKey => $field)
		{
			if (!empty($field['HIDDEN']) && $field['HIDDEN'] !== 'N')
			{
				$tab['HIDDEN'][] = $fieldKey;
			}
			else
			{
				$tab['FIELDS'][] = $fieldKey;
			}
		}
	}

	protected function fillRequest($fields)
	{
		$provider = $this->getProvider();

		foreach ($fields as $field)
		{
			if ($field['USER_TYPE']['BASE_TYPE'] === 'file')
			{
				$this->getFileByRequestKey($_POST, $_FILES, $field['FIELD_NAME'], $this->arResult['ITEM']);
			}
			else
			{
				$this->getValueByRequestKey($_POST, $field['FIELD_NAME'], $this->arResult['ITEM']);
			}
		}

		$this->arResult['ITEM'] = $provider->modifyRequest($this->arResult['ITEM'], $fields);
	}

	protected function getFileByRequestKey($post, $files, $key, &$result)
	{
		$keyChain = $this->splitFieldNameToChain($key);

		if (count($keyChain) > 1)
		{
			throw new Main\NotImplementedException();
		}

		$requestKey = reset($keyChain);
		$deleteRequestKey = $requestKey . '_del';
		$oldIdRequestKey = $requestKey . '_old_id';

		$request = isset($files[$requestKey]) && is_array($files[$requestKey]) ? $files[$requestKey] : [];

		if (isset($post[$deleteRequestKey]))
		{
			$request['del'] = ($post[$deleteRequestKey] === 'Y');
		}

		if (isset($post[$oldIdRequestKey]))
		{
			$request['old_id'] = (int)$post[$oldIdRequestKey];
		}

		$result[$requestKey] = $request;
	}

	protected function splitFieldNameToChain($key)
	{
		$keyOffset = 0;
		$keyLength = mb_strlen($key);
		$keyChain = [];

		do
		{
			$keyPart = null;

			if ($keyOffset === 0)
			{
				$arrayEnd = mb_strpos($key, '[');

				if ($arrayEnd === false)
				{
					$keyPart = $key;
					$keyOffset = $keyLength;
				}
				else
				{
					$keyPart = mb_substr($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 1;
				}
			}
			else
			{
				$arrayEnd = mb_strpos($key, ']', $keyOffset);

				if ($arrayEnd === false)
				{
					$keyPart = mb_substr($key, $keyOffset);
					$keyOffset = $keyLength;
				}
				else
				{
					$keyPart = mb_substr($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 2;
				}
			}

			if ((string)$keyPart !== '')
			{
				$keyChain[] = $keyPart;
			}
			else
			{
				break;
			}
		} while ($keyOffset < $keyLength);

		return $keyChain;
	}

	protected function getValueByRequestKey($values, $key, &$result)
	{
		$keyChain = $this->splitFieldNameToChain($key);

		if (!empty($keyChain))
		{
			$valuesLevel = $values;
			$resultLevel = &$result;
			$keyChainLength = count($keyChain);

			for ($i = 0; $i < $keyChainLength; $i++)
			{
				$key = $keyChain[$i];
				$isLastKey = ($i === $keyChainLength - 1);

				if ($isLastKey)
				{
					$resultLevel[$key] = isset($valuesLevel[$key]) ? $valuesLevel[$key] : null;
				}
				else
				{
					if (!isset($resultLevel[$key]))
					{
						$resultLevel[$key] = [];
					}

					$resultLevel = &$resultLevel[$key];
					$valuesLevel = isset($valuesLevel[$key]) ? $valuesLevel[$key] : null;
				}
			}
		}
	}

	protected function extendDefaults(array $fields) : void
	{
		$this->arResult['ITEM'] = $this->provider->extendDefaults($this->arResult['ITEM'], $fields);
	}

	protected function resolveDependency(&$fields) : void
	{
		foreach ($this->getDependencyStatuses($fields) as $fieldName => $status)
		{
			if (!isset($fields[$fieldName])) { continue; }

			$fields[$fieldName]['DEPEND_HIDDEN'] = $status;
		}
	}

	protected function getDependencyStatuses($fields) : array
	{
		$result = [];

		foreach ($fields as $fieldName => $field)
		{
			if (!isset($field['DEPEND'])) { continue; }

			$result[$fieldName] = !Utils\DependField::checkDependencyField($field['DEPEND'], $this->arResult['ITEM']);
		}

		return $result;
	}

	protected function validateRequest($fields = null)
	{
		if ($fields === null)
		{
			$fields = $this->arResult['FIELDS'];
		}

		$data = $this->arResult['ITEM'];
		$data['PRIMARY'] = $this->getPrimary();

		$provider = $this->getProvider();
		$validationResult = $provider->validate($data, $fields);
		$result = false;

		if ($validationResult->isSuccess())
		{
			$result = true;
		}
		else
		{
			$errors = $validationResult->getErrors();

			if (!empty($errors))
			{
				foreach ($errors as $error)
				{
					$errorCustomData = method_exists($error, 'getCustomData') ? $error->getCustomData() : null;

					if (isset($errorCustomData['FIELD']))
					{
						$this->addFieldError($errorCustomData['FIELD'], $error->getMessage());
					}
					else
					{
						$this->addError($error->getMessage());
					}
				}
			}
			else
			{
				$this->addError($this->getLangMessage('VALIDATE_ERROR_UNDEFINED'));
			}
		}

		return $result;
	}

	protected function addFieldError($fieldName, $message)
	{
		$this->arResult['FIELD_ERRORS'][$fieldName] = true;

		$this->addError($message);
	}

	protected function registerFields($fields)
	{
		$this->arResult['FIELDS'] += $fields;
	}

	protected function processAjaxAction()
	{
		$ajaxAction = $this->getAjaxAction();
		$provider = $this->getProvider();

		try
		{
			$data = $this->arResult['ITEM'];
			$data['PRIMARY'] = $this->getPrimary();

			$response = $provider->processAjaxAction($ajaxAction, $data);
		}
		catch (Main\SystemException $exception)
		{
			$response = [
				'status' => 'error',
				'message' => $exception->getMessage(),
			];
		}

		/** @var Main\Application $application */
		$application = Main\Application::getInstance();
		$response = new Main\Engine\Response\Json($response);
		$response = Main\Application::getInstance()->getContext()->getResponse()->copyHeadersTo($response);

		$application->end(0, $response);
	}

	public function hasErrors()
	{
		return !empty($this->arResult['ERRORS']);
	}

	protected function processPostAction()
	{
		$postAction = $this->getPostAction();
		$provider = $this->getProvider();

		try
		{
			$data = $this->arResult['ITEM'];
			$data['PRIMARY'] = $this->getPrimary();

			$provider->processPostAction($postAction, $data);
		}
		catch (Main\SystemException $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

	protected function afterSave($action, $primary = null) : void
	{
		if ($this->isAjaxForm())
		{
			$this->arResult['PRIMARY'] = $primary ?? $this->getPrimary();
			$this->arResult['ACTION'] = $action;
			$this->arResult['SUCCESS'] = true;
		}
		else
		{
			$this->redirectAfterSave($primary);
		}
	}

	protected function isAjaxForm() : bool
	{
		return $this->request->getPost('ajaxForm') === 'Y';
	}

	protected function redirectAfterSave($primary = null) : void
	{
		global $APPLICATION;

		$redirectUrl = '';
		$parameters = [];

		if ($primary !== null)
		{
			$parameters['id'] = $primary;
		}

		if ($this->arParams['FORM_BEHAVIOR'] !== 'steps')
		{
			$activeTabRequestKey = $this->arParams['FORM_ID'] . '_active_tab';
			$activeTab = $this->request->getPost($activeTabRequestKey);

			$parameters[$activeTabRequestKey] = $activeTab;
		}

		if ($this->request->getPost('save'))
		{
			$redirectUrl = (string)($this->arParams['SAVE_URL'] ?: $this->arParams['LIST_URL']);
		}

		if ($redirectUrl !== '')
		{
			$leftParameters = [];

			foreach ($parameters as $name => $value)
			{
				$searchHolder = '#' . mb_strtoupper($name) . '#';
				$searchPosition = mb_strpos($redirectUrl, $searchHolder);

				if ($searchPosition !== false)
				{
					$redirectUrl = str_replace($searchHolder, $value, $redirectUrl);
				}
				else
				{
					$leftParameters[$name] = $value;
				}
			}

			if (!empty($leftParameters))
			{
				$redirectUrl .= (mb_strpos($redirectUrl, '?') === false ? '?' : '&')
					. http_build_query($leftParameters);
			}
		}
		else
		{
			$redirectUrl = $APPLICATION->GetCurPageParam(http_build_query($parameters), array_merge(array_keys($parameters), [ 'postAction', 'sessid' ]));
		}

		LocalRedirect($redirectUrl);
	}

	protected function saveFull()
	{
		$fields = $this->arResult['ITEM'];

		return $this->save($fields);
	}

	protected function save($fields)
	{
		$provider = $this->getProvider();
		$primary = $this->getPrimary();
		$result = null;

		if ($primary !== null)
		{
			$saveResult = $provider->update($primary, $fields);
		}
		else
		{
			$saveResult = $provider->add($fields);

			if ($saveResult->isSuccess())
			{
				$primary = $saveResult->getId();
			}
		}

		if ($saveResult->isSuccess())
		{
			$result = $primary;
		}
		else
		{
			$errors = $saveResult->getErrors();

			if (!empty($errors))
			{
				foreach ($errors as $error)
				{
					$this->addError($error->getMessage());
				}
			}
			else
			{
				$this->addError($this->getLangMessage('SAVE_ERROR_UNDEFINED'));
			}
		}

		return $result;
	}

	protected function savePartially()
	{
		$fields = $this->arResult['ITEM'];
		$fieldsOriginal = $this->arResult['ITEM_ORIGINAL'];

		foreach ($this->getFieldsWithError() as $fieldName)
		{
			if (!array_key_exists($fieldName, $fields))
			{
				// nothing
			}
			elseif (array_key_exists($fieldName, $fieldsOriginal))
			{
				$fields[$fieldName] = $fieldsOriginal[$fieldName];
			}
			else
			{
				unset($fields[$fieldName]);
			}
		}

		if (!empty($fields))
		{
			$result = $this->save($fields);
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public function getFieldsWithError()
	{
		return array_keys($this->arResult['FIELD_ERRORS']);
	}

	protected function extendItem()
	{
		$provider = $this->getProvider();
		$isStepsBehavior = ($this->arParams['FORM_BEHAVIOR'] === 'steps');
		$selectFields = [];

		foreach ($this->arResult['TABS'] as $tab)
		{
			if (!$isStepsBehavior)
			{
				array_splice($selectFields, -1, 0, $tab['FIELDS']);
			}
			elseif ($tab['STEP'] === $this->arResult['STEP'])
			{
				$selectFields = $tab['FIELDS'];
			}
		}

		$this->arResult['ITEM'] = $provider->extend($this->arResult['ITEM'], $selectFields);
	}

	protected function setTitle()
	{
		global $APPLICATION;

		$title = $this->arParams['TITLE'];
		$primary = $this->getPrimary();

		if ($primary === null && $this->arParams['TITLE_ADD'] !== '')
		{
			$title = $this->arParams['TITLE_ADD'];
		}

		if ($title !== '')
		{
			$APPLICATION->SetTitle($title);
		}
	}

	public function getField($fieldKey)
	{
		$result = null;

		if (isset($this->arResult['FIELDS'][$fieldKey]))
		{
			$result = $this->arResult['FIELDS'][$fieldKey];
		}

		return $result;
	}

	public function getFieldTitle($field)
	{
		return $this->getFirstNotEmpty($field, ['EDIT_FORM_LABEL', 'LIST_COLUMN_LABEL', 'LIST_FILTER_LABEL']);
	}

	protected function getFirstNotEmpty($data, $keys)
	{
		$result = null;

		foreach ($keys as $key)
		{
			if (!empty($data[$key]))
			{
				$result = $data[$key];
				break;
			}
		}

		return $result;
	}

	public function getOriginalValue($field)
	{
		$keyChain = $this->splitFieldNameToChain($field['FIELD_NAME']);

		return $this->getValueByChain($this->arResult['ITEM_ORIGINAL'], $keyChain);
	}

	protected function getValueByChain($item, $keyChain)
	{
		$itemLevel = $item;
		$keyChainLength = count($keyChain);
		$result = null;

		foreach ($keyChain as $i => $iValue)
		{
			$key = $iValue;
			$isLastKey = ($i === $keyChainLength - 1);

			if ($isLastKey)
			{
				$result = isset($itemLevel[$key]) ? $itemLevel[$key] : null;
			}
			else
			{
				$itemLevel = isset($itemLevel[$key]) ? $itemLevel[$key] : null;
			}
		}

		return $result;
	}

	public function getFieldHtml($field, $value = null, $isExtended = false)
	{
		global $USER_FIELD_MANAGER;

		$result = null;

		if (empty($field['HIDDEN']) || $field['HIDDEN'] === 'N')
		{
			$field['ENTITY_VALUE_ID'] = $this->getPrimary();
			$field['VALUE'] = $value ?? $this->getFieldValue($field);
			$field['VALUE'] = $this->normalizeFieldValue($field, $field['VALUE']);
			$field['ROW'] = $this->arResult['ITEM'];

			$html = $USER_FIELD_MANAGER->GetEditFormHTML(false, null, $field);

			$parsedHtml = static::parseEditHtml($html);

			$result = $isExtended ? $parsedHtml : $parsedHtml['CONTROL'];
		}

		return $result;
	}

	public static function parseEditHtml($html):array
	{
		$result = [
			'ROW_CLASS' => '',
			'VALIGN' => '',
			'CONTROL' => $html,
		];

		if (preg_match('/^<tr(.*?)>(?:<td(.*?)>.*?<\/td>)?<td.*?>(.*)<\/td><\/tr>$/s', $html, $match))
		{
			$rowAttributes = trim($match[1]);
			$rowClassName = '';
			$titleAttributes = trim($match[2]);
			$titleVerticalAlign = null;

			if (preg_match('/class="(.*?)"/', $rowAttributes, $rowMatches))
			{
				$rowClassName = $rowMatches[1];
			}

			if (preg_match('/valign="(.*?)"/', $titleAttributes, $titleMatches))
			{
				$titleVerticalAlign = $titleMatches[1];
			}
			elseif (mb_strpos($titleAttributes, 'adm-detail-valign-top') !== false)
			{
				$titleVerticalAlign = 'top';
			}

			$result['ROW_CLASS'] = $rowClassName;
			$result['VALIGN'] = $titleVerticalAlign;
			$result['CONTROL'] = $match[3];
		}

		return $result;
	}

	public function getFieldValue($field)
	{
		// try fetch from item

		$keyChain = $this->splitFieldNameToChain($field['FIELD_NAME']);
		$result = $this->getValueByChain($this->arResult['ITEM'], $keyChain);

		// may be defined value

		if ($result !== null)
		{
			// nothing
		}
		elseif (isset($field['VALUE']))
		{
			$result = $field['VALUE'];
		}
		elseif (isset($field['SETTINGS']['DEFAULT_VALUE']))
		{
			$result = $field['SETTINGS']['DEFAULT_VALUE'];
		}

		return $result;
	}

	public function normalizeFieldValue($field, $value)
	{
		if ($field['MULTIPLE'] !== 'N' && is_scalar($value) && (string)$value !== '')
		{
			$result = [$value];
		}
		else
		{
			$result = $value;
		}

		return $result;
	}
}
