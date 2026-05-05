<?php
namespace Avito\Export\Admin\Page\Grid;

use Avito\Export\Concerns;
use Avito\Export\Admin\View;

class AdminAdapter implements GridAdapter
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	protected $gridId;
	protected $filterValues = [];

	public function __construct(string $gridId)
	{
		$this->gridId = $gridId;
	}

	public function listing() : \CAdminList
	{
		return $this->once('list', function() {
			return new \CAdminList($this->gridId, $this->sorting());
		});
	}

	public function sorting() : \CAdminSorting
	{
		return $this->once('sort', function() {
			return new \CAdminSorting($this->gridId);
		});
	}

	public function resultClass() : string
	{
		return \CAdminResult::class;
	}

	public function filterFieldId(string $code) : string
	{
		return 'find_' . mb_strtolower($code);
	}

	public function initFilter(array $fields) : array
	{
		$request = $this->listing()->getFilter();
		$queryFilter = [];

		foreach ($fields as $code => $field)
		{
			if (!isset($field['FILTERABLE']) || $field['FILTERABLE'] === false) { continue; }

			$requestKey = $this->filterFieldId($code);
			$baseType = $field['USER_TYPE']['BASE_TYPE'] ?? 'string';

			if ($baseType === 'datetime')
			{
				$fromRequestKey = $requestKey . '_from';
				$toRequestKey = $requestKey . '_to';
				$fromRequest = isset($request[$fromRequestKey]) ? (string)$request[$fromRequestKey] : '';
				$toRequest = isset($request[$toRequestKey]) ? (string)$request[$toRequestKey] : '';

				if ($fromRequest !== '')
				{
					$queryFilter['>=' . $code] = $fromRequest;
					$this->setFilterValue($fromRequestKey, $fromRequest);
				}

				if ($toRequest !== '')
				{
					$queryFilter['<=' . $code] = $toRequest;
					$this->setFilterValue($toRequestKey, $toRequest);
				}
			}
			else
			{
				$requestValue = $request[$requestKey];

				if ($requestValue !== null && $requestValue !== '')
				{
					$compare = is_string($field['FILTERABLE']) ? $field['FILTERABLE'] : '=';

					$queryFilter[$compare . $code] = $requestValue;
					$this->setFilterValue($requestKey, $requestValue);
				}
			}
		}

		return $queryFilter;
	}

	protected function setFilterValue(string $code, $value) : void
	{
		$this->filterValues[$code] = $value;
	}

	protected function getFilterValue(string $code)
	{
		return $this->filterValues[$code] ?? null;
	}

	public function showFilter(array $fields) : void
	{
		global $APPLICATION;

		$fields = array_filter($fields, static function($field) {
			return isset($field['FILTERABLE']) && $field['FILTERABLE'] !== false;
		});

		if (empty($fields)) { return; }

		$viewFilter = $this->createViewFilter($fields);
		$baseUrl = $APPLICATION->GetCurPage();
		$hiddenValues = [
			'lang' => LANGUAGE_ID,
		];

		echo '<form method="get" action="' . htmlspecialcharsbx($baseUrl) . '">';

		foreach ($hiddenValues as $hiddenKey => $hiddenValue)
		{
			echo '<input type="hidden" name="' . htmlspecialcharsbx($hiddenKey) . '" value="'
				. htmlspecialcharsbx($hiddenValue) . '" />';
		}

		$viewFilter->Begin();

		foreach ($fields as $code => $field)
		{
			echo sprintf(
				'<tr><td>%s</td><td>%s</td></tr>',
				htmlspecialcharsbx($field['LIST_COLUMN_LABEL']),
				$this->showFilterField($code, $field)
			);
		}

		$viewFilter->Buttons([
			'url' => $baseUrl,
			'table_id' => $this->gridId,
		]);

		$viewFilter->End();

		echo '</form>';
	}

	protected function createViewFilter(array $fields) : \CAdminFilter
	{
		$result = new \CAdminFilter(
			$this->gridId . '_filter',
			$this->filterPopupFields($fields)
		);
		$result->SetDefaultRows(
			$this->filterDefaultFields($fields)
		);

		return $result;
	}

	protected function filterDefaultFields(array $fields) : array
	{
		$result = [];
		$filterIndex = 0;

		foreach ($fields as $field)
		{
			if (!isset($field['FILTERABLE']) || $field['FILTERABLE'] === false) { continue; }

			if (!empty($field['FILTER_DEFAULT']))
			{
				$result[] = $filterIndex;
			}

			++$filterIndex;
		}

		return $result;
	}

	protected function filterPopupFields(array $fields) : array
	{
		$result = [];

		foreach ($fields as $field)
		{
			if (!isset($field['FILTERABLE']) || $field['FILTERABLE'] === false) { continue; }

			$result[] = $field['NAME'];
		}

		return $result;
	}

	protected function showFilterField(string $code, array $field) : string
	{
		$inputName = $this->filterFieldId($code);
		$type = $field['USER_TYPE']['BASE_TYPE'];

		switch ($type)
		{
			case 'datetime':
				$fromKey = $inputName . '_from';
				$toKey = $inputName . '_to';
				$from = $this->getFilterValue($fromKey);
				$to = $this->getFilterValue($toKey);

				$result = CalendarPeriod($fromKey, $from, $toKey, $to, 'Y');
				break;

			case 'enum':
				$result = View\Select::edit($field['VALUES'], $this->getFilterValue($inputName), [
					'name' => $inputName,
					'id' => $inputName,
				], [
					'ALLOW_NO_VALUE' => true,
					'CAPTION_NO_VALUE' => self::getLocale('FILTER_ENUM_ANY'),
				]);
				break;

			default:
				$value = $this->getFilterValue($inputName);

				$result = sprintf(
					'<input type="text" name="%s" value="%s">',
					$inputName,
					$value
				);
				break;
		}

		return $result;
	}

	public function showErrors(array $errors) : void
	{
		if (empty($errors)) { return; }

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => implode('<br />', $errors),
			'HTML' => true,
		]);
	}
}