<?php

namespace Avito\Export\Admin\Page;

use Avito\Export\Concerns;
use Bitrix\Main;

abstract class Grid extends Page
{
	use Concerns\HasLocale;

	protected $viewAdapter;
	protected $fields;
	protected $filterValues = [];
	protected $buttons = [];

	public function __construct(Main\HttpRequest $request = null)
	{
		parent::__construct($request);
		$this->viewAdapter = Grid\Factory::makeAdapter($this->getGridId());
	}

	abstract public function getGridId() : string;

	public function hasAjaxRequest():bool
	{
		$requestMode = $this->request->get('mode');

		return (
			$this->isExportMode()
			|| ($this->request->isAjaxRequest() && $requestMode !== null)
		);
	}

	public function hasRequestAction():bool
	{
		return $this->getRequestAction() !== null;
	}

	public function getRequestAction()
	{
		$action = $_REQUEST['action_button'] ?? null;

		if ($action === '' && isset($_REQUEST['action']))
		{
			$action = $_REQUEST['action'];
		}

		if (empty($action) && $this->request->get('action') !== null)
		{
			$action = $this->request->get('action');
		}

		return $action;
	}

	public function processAction():void
	{
		$viewList = $this->viewAdapter->listing();
		$action = $this->getRequestAction();

		try
		{
			$actionData = [
				'ID' => $viewList->GroupAction(),
				'IS_ALL' => false,
			];

			if (isset($_REQUEST['action_target']) && $_REQUEST['action_target'] === 'selected')
			{
				$filter = $this->initFilter();

				$actionData['IS_ALL'] = true;
				$actionData['FILTER'] = $filter['filter'] ?? null;
			}

			if (empty($actionData['ID']) && $this->request->get('ID') !== null)
			{
				$actionData['ID'] = $this->request->get('ID');
			}

			$this->handleAction($action, $actionData);
		}
		catch (\Throwable $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

	protected function initFilter() : array
	{
		return [
			'filter' => $this->viewAdapter->initFilter($this->getFields()),
		];
	}

	protected function sanitizeSessionQuery(array $queryParams): array
	{
		$sessionKey = $this->getGridId() . '_QUERY_SIGN';
		$stored = $_SESSION[$sessionKey] ?? null;
		$calculated = md5(serialize(array_intersect_key($queryParams, [
			'filter' => true,
			'limit' => true,
		])));

		if ($stored !== null && $stored !== $calculated)
		{
			$queryParams['offset'] = 0;
		}

		$_SESSION[$sessionKey] = $calculated;

		return $queryParams;
	}

	public function getFields() : array
	{
		if ($this->fields === null)
		{
			$this->fields = $this->loadFields();
		}

		return $this->fields;
	}

	/** @return array */
	abstract protected function loadFields():array;

	protected function handleAction($action, $data):void
	{
		$message = self::getLocale('ACTION_NOT_FOUND');

		throw new Main\NotImplementedException($message);
	}

	public function show():void
	{
		$this->loadAssets();

		$this->buildHeaders();
		$this->buildFilter();

		$queryParams = [];
		$queryParams += $this->initFilter();
		$queryParams += $this->initSelect();
		$queryParams += $this->initPager();
		$queryParams += $this->initSort();

		$queryParams = $this->sanitizeSessionQuery($queryParams);

		if ($this->isExportMode())
		{
			unset($queryParams['limit'], $queryParams['offset']);
			$items = $this->loadItems($queryParams);
			$totalCount = count($items);
		}
		else
		{
			$items = $this->loadItems($queryParams);
			$totalCount = $this->loadTotalCount($queryParams);

			if (!isset($queryParams['limit']) || $queryParams['limit'] <= 0)
			{
				$queryParams['limit'] = $totalCount;
			}

			if (empty($items)
				&& $totalCount > 0
				&& isset($queryParams['offset'], $queryParams['limit'])
				&& $queryParams['offset'] > 0
				&& $queryParams['limit'] > 0)
			{
				$queryParams['offset'] = $this->roundPagerOffset($queryParams['limit'], $totalCount);

				$items = $this->loadItems($queryParams);
			}
		}

		$this->buildContextMenu();
		$this->buildRows($items);
		$this->buildNavString($queryParams, $totalCount);
		$this->buildGroupActions();

		$this->showProlog();
		$this->checkListMode();
		$this->showFilter();
		$this->showList();
	}

	protected function loadAssets():void
	{
		// nothing
	}

	protected function buildHeaders():void
	{
		$fields = $this->getFields();
		$view = $this->viewAdapter->listing();
		$headers = [];

		foreach ($fields as $code => $field)
		{
			if (isset($field['SELECTABLE']) && $field['SELECTABLE'] === false) { continue; }

			$headers[$code] = [
				'id' => $code,
				'content' => $field['LIST_COLUMN_LABEL'],
				'sort' => !isset($field['SORTABLE']) || $field['SORTABLE'] ? $code : null,
				'first_order' => 'desc',
				'default' => !empty($field['DEFAULT']),
			];
		}

		$view->AddHeaders($headers);
	}

	protected function buildFilter() : void
	{
		$fields = $this->getFields();
		$view = $this->viewAdapter->listing();
		$filters = [];

		foreach ($fields as $code => $field)
		{
			if (!isset($field['FILTERABLE']) || $field['FILTERABLE'] === false) { continue; }

			$filterId = $this->viewAdapter->filterFieldId($code);
			$baseType = $field['USER_TYPE']['BASE_TYPE'] ?? 'string';

			if ($baseType === 'datetime')
			{
				$filters[] = $filterId . '_from';
				$filters[] = $filterId . '_to';
			}
			else
			{
				$filters[] = $filterId;
			}
		}

		$view->InitFilter($filters);
	}

	protected function initSelect():array
	{
		$view = $this->viewAdapter->listing();

		return [
			'select' => $view->GetVisibleHeaderColumns(),
		];
	}

	protected function initPager():array
	{
		/** @var class-string<\CAdminResult> $className */
		$result = [];
		$gridId = $this->getGridId();
		$className = $this->viewAdapter->resultClass();
		$navSize = $className::GetNavSize($gridId);
		$navParams = \CDBResult::GetNavParams($navSize);

		if (!$navParams['SHOW_ALL'])
		{
			$page = (int)$navParams['PAGEN'];
			$pageSize = (int)$navParams['SIZEN'];

			$result['limit'] = $pageSize;
			$result['offset'] = $pageSize * ($page - 1);
		}

		return $result;
	}

	protected function initSort():array
	{
		$viewSort = $this->viewAdapter->sorting();

		if (empty($GLOBALS[$viewSort->by_name])) { return [ 'order' => $this->defaultSort() ]; }

		$sortField = strtoupper($GLOBALS[$viewSort->by_name]);
		$fields = $this->getFields();

		if (!isset($fields[$sortField])) { return []; }

		$field = $fields[$sortField];

		if (isset($field['SORTABLE']) && !$field['SORTABLE']) { return []; }

		$sortOrder = (
			isset($GLOBALS[$viewSort->ord_name]) && strtoupper($GLOBALS[$viewSort->ord_name]) === 'DESC'
				? 'DESC'
				: 'ASC'
		);

		return [
			'order' => [
				$sortField => $sortOrder,
			],
		];
	}

	protected function defaultSort() : array
	{
		return [];
	}

	/**
	 * @param array $queryParameters
	 *
	 * @return array
	 */
	abstract protected function loadItems(array $queryParameters = []):array;

	/**
	 * @param array $queryParameters
	 *
	 * @return int
	 */
	abstract protected function loadTotalCount(array $queryParameters = []):int;

	protected function roundPagerOffset($pageSize, $totalCount)
	{
		return floor($totalCount / $pageSize) * $pageSize;
	}

	protected function buildContextMenu():void
	{
		$menu = $this->getContextMenu();
		$useExcel = $this->useExcelExport();
		$useViewSettings = $this->useViewSettings();

		if (!empty($menu) || $useExcel || $useViewSettings)
		{
			$view = $this->viewAdapter->listing();
			$view->AddAdminContextMenu($menu, $useExcel, $useViewSettings);
		}
	}

	protected function getContextMenu():array
	{
		return [];
	}

	protected function useExcelExport():bool
	{
		return false;
	}

	protected function useViewSettings():bool
	{
		return true;
	}

	protected function isExportMode() : bool
	{
		return $this->useExcelExport() && $this->request->get('mode') === 'excel';
	}

	protected function buildRows($items):void
	{
		foreach ($items as $item)
		{
			$this->buildRow($item);
		}
	}

	protected function buildRow($item)
	{
		$view = $this->viewAdapter->listing();
		$headers = $view->GetVisibleHeaderColumns();
		$fields = $this->getFields();

		$link = $item['EDIT_URL'] ?? null;
		$actions = $this->buildRowActions($item);
		$viewRow = $view->AddRow($item['ID'], [], $link, true);

		foreach ($headers as $code)
		{
			$field = null;
			$fieldValue = $item[$code] ?? null;
			$displayValue = null;

			if (isset($fields[$code]))
			{
				$field = $fields[$code];
				$displayValue = $this->getDisplayValue($field, $fieldValue, $item);
			}

			$viewRow->AddViewField($code, $displayValue);
		}

		if ($actions !== null)
		{
			$viewRow->AddActions($actions);
		}
		else
		{
			$viewRow->bReadOnly = true;
		}

		return $viewRow;
	}

	protected function getActionsBuild($item): array
	{
		return [];
	}

	protected function buildRowActions($item):?array
	{
		global $APPLICATION;

		$result = null;

		$actions = $this->getActionsBuild($item);

		if (!empty($actions))
		{
			$result = [];
			$replacesFrom = [];
			$replacesTo = [];

			foreach ($item as $key => $value)
			{
				if (is_scalar($value))
				{
					$replacesFrom[] = '#' . $key . '#';
					$replacesTo[] = $value;
				}
			}

			foreach ($actions as $type => $action)
			{
				if ($type === 'DELETE' || isset($action['ACTION']))
				{
					$actionMethod = $action['ACTION'] ?? 'delete';
					$primaryFields = $action['PRIMARY'] ?? [ 'ID' ];
					$queryParams = [
						'sessid' => bitrix_sessid(),
						'action_button' => $actionMethod,
						'ID' => implode(':', array_intersect_key($item, array_flip($primaryFields))),
					];

					if ($this->viewAdapter->listing() instanceof \CAdminUiList)
					{
						$queryParams['action'] = $actionMethod;
						unset($queryParams['action_button']);

						$actionMethod = sprintf(
							'BX.Main.gridManager.getById("%s").instance.reloadTable("POST", %s)',
							$this->getGridId(),
							Main\Web\Json::encode($queryParams)
						);
					}
					else
					{
						$url = $APPLICATION->GetCurPageParam($queryParams, array_keys($queryParams));

						$actionMethod = $this->getGridId() . '.GetAdminList("' . \CUtil::addslashes($url) . '");';
					}
				}
				else if (isset($action['ONCLICK']))
				{
					$actionMethod = $action['ONCLICK'];
				}
				else
				{
					$actionUrl = str_replace($replacesFrom, $replacesTo, $action['URL']);

					if (strpos($actionUrl, 'lang=') === false)
					{
						$actionUrl .= (strpos($actionUrl, '?') === false ? '?' : '&') . 'lang=' . LANGUAGE_ID;
					}

					if (isset($action['MODAL']) && $action['MODAL'] === 'Y')
					{
						$actionMethod = ''; // todo
					}
					elseif (isset($action['WINDOW']) && $action['WINDOW'] === 'Y')
					{
						$actionMethod = 'jsUtils.OpenWindow("' . \CUtil::AddSlashes($actionUrl) . '", 1250, 800);';
					}
					else
					{
						$actionMethod = "BX.adminPanel.Redirect([], '" . \CUtil::AddSlashes($actionUrl) . "', event);";
					}
				}

				if (!empty($action['CONFIRM']))
				{
					$confirmMessage = !empty($action['CONFIRM_MESSAGE']) ? $action['CONFIRM_MESSAGE'] : self::getLocale('ROW_ACTION_CONFIRM');
					$actionMethod = 'if (confirm("' . \CUtil::AddSlashes($confirmMessage) . '")) ' . $actionMethod;
				}

				$result[] = [
					'ACTION' => $actionMethod,
					'ICON' => $action['ICON'] ?? null,
					'DEFAULT' => $action['DEFAULT'] ?? null,
					'TEXT' => $action['TEXT'],
					'TYPE' => $type,
				];
			}
		}

		return $result;
	}

	protected function getDisplayValue($field, $value, $row) : ?string
	{
		global $USER_FIELD_MANAGER;

		$field['ENTITY_VALUE_ID'] = $row['ID'] ?? null;
		$field['ROW'] = $row;
		[$field, $value] = $this->sanitizeFieldValue($field, $value);

		return $USER_FIELD_MANAGER->getListView($field, $value);
	}

	protected function sanitizeFieldValue(array $field, $value) : array
	{
		$isMultiple = ($field['MULTIPLE'] !== 'N');

		if ($isMultiple)
		{
			$valueIterable = is_array($value) ? $value : [ $value ];
			$valuePlaceholder = [];
			$isComplex = false;

			foreach ($valueIterable as $key => $one)
			{
				if (is_array($one))
				{
					$isComplex = true;
					$valuePlaceholder[$key] = 'PLACEHOLDER';
				}
				else
				{
					$valuePlaceholder[$key] = $one;
				}
			}

			if ($isComplex)
			{
				$field['VALUE'] = $value;
				$value = $valuePlaceholder;
			}
		}
		else if (is_array($value))
		{
			$field['VALUE'] = $value;
			$value = 'PLACEHOLDER';
		}

		return [$field, $value];
	}

	protected function buildNavString($queryParameters, $totalCount):void
	{
		$viewList = $this->viewAdapter->listing();
		$gridId = $this->getGridId();
		$resultClass = $this->viewAdapter->resultClass();
		$iterator = new $resultClass([], $gridId);
		$navTitle = '';//$this->getLang('NAV_TITLE');

		if (isset($queryParameters['limit']))
		{
			$page = floor($queryParameters['offset'] / $queryParameters['limit']) + 1;
			$totalPages = ceil($totalCount / $queryParameters['limit']);

			$iterator->NavStart([
				'nPageSize' => $queryParameters['limit'],
				'bShowAll' => false,
				'iNumPage' => $page,
			]);
			$iterator->NavRecordCount = $totalCount;
			$iterator->NavPageCount = max($totalPages, $page);
			$iterator->NavPageNomer = $page;
		}
		else
		{
			$iterator->NavStart();
		}

		$navHtml = $iterator->GetNavPrint($navTitle);

		$viewList->NavText($navHtml);
	}

	protected function buildGroupActions():void
	{
		$groupActions = $this->getGroupActions();

		if (!empty($groupActions))
		{
			$viewList = $this->viewAdapter->listing();
			$viewList->AddGroupActionTable($groupActions, [
				'disable_action_target' => true,
			]);
		}
	}

	protected function getGroupActions():array
	{
		return [];
	}

	protected function showProlog():void
	{
		$viewList = $this->viewAdapter->listing();

		$viewList->BeginPrologContent();

		if ($this->isExternalAction())
		{
			global $APPLICATION;

			$APPLICATION->RestartBuffer();
		}

		if ($this->hasErrors())
		{
			$this->showErrors();
		}

		if ($this->hasWarnings())
		{
			$this->showWarnings();
		}

		if ($this->hasMessages())
		{
			$this->showMessages();
		}

		if ($this->isExternalAction())
		{
			die();
		}

		$this->showButtons();

		$viewList->EndPrologContent();
	}

	public function isExternalAction():bool
	{
		return $this->request->get('externalAction') !== null;
	}

	public function showButtons():void
	{
		foreach ($this->buttons as $button)
		{
			echo $button;
		}
	}

	protected function checkListMode():void
	{
		$this->viewAdapter->listing()->CheckListMode();
	}

	protected function showFilter():void
	{
		$this->viewAdapter->showFilter($this->getFields());
	}

	public function showErrors() : void
	{
		$this->viewAdapter->showErrors($this->errors);
	}

	protected function showList():void
	{
		$this->viewAdapter->listing()->DisplayList([
			'ACTION_PANEL' => false, // move to ui adapter in future
			'SHOW_TOTAL_COUNTER' => false,
		]);
	}
}
