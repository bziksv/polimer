<?php
namespace Avito\Export\Admin\Page\Grid;

use Avito\Export\Concerns;

class UiAdapter implements GridAdapter
{
	use Concerns\HasOnce;

	protected $gridId;
	protected $filters;

	public function __construct(string $gridId)
	{
		$this->gridId = $gridId;
	}

	/** @return \CAdminUiList */
	public function listing() : \CAdminList
	{
		return $this->once('list', function() {
			return new \CAdminUiList($this->gridId, $this->sorting());
		});
	}

	public function sorting() : \CAdminSorting
	{
		return $this->once('sort', function() {
			return new \CAdminUiSorting($this->gridId);
		});
	}

	public function resultClass() : string
	{
		return \CAdminUiResult::class;
	}

	public function filterFieldId(string $code) : string
	{
		return 'find_' . mb_strtolower($code);
	}

	public function initFilter(array $fields) : array
	{
		$this->filters = $this->buildFilters($fields);

		$listFilter = [];
		$fieldsMap = array_column($this->filters, 'fieldName', 'id');
		$result = [];

		$this->listing()->AddFilter($this->filters, $listFilter);

		foreach ($listFilter as $filterKey => $filterValue)
		{
			if (!preg_match('/^(.*?)(find_.+)$/', $filterKey, $matches)) { continue; }

			[, $filterCompare, $filterId] = $matches;

			if (isset($fieldsMap[$filterId]))
			{
				$filterField = $fieldsMap[$filterId];

				$result[$filterCompare . $filterField] = $filterValue;
			}
		}

		return $result;
	}

	protected function buildFilters(array $fields) : array
	{
		$result = [];

		foreach ($fields as $code => $field)
		{
			if (!isset($field['FILTERABLE']) || $field['FILTERABLE'] === false) { continue; }

			$filterId = $this->filterFieldId($code);
			$baseType = $field['USER_TYPE']['BASE_TYPE'] ?? 'string';

			$item = [
				'id' => $filterId,
				'fieldName' => $code,
				'value' => null,
				'name' => $field['LIST_COLUMN_LABEL'],
				'filterable' => '',
				'default' => !empty($field['FILTER_DEFAULT']),
			];

			if ($baseType === 'datetime')
			{
				$item['type'] = 'date';
			}
			elseif ($baseType === 'enum' && !empty($field['VALUES']))
			{
				$item['type'] = 'list';
				$previousGroup = null;
				$groupIndex = 0;

				foreach ($field['VALUES'] as $option)
				{
					if (isset($option['GROUP']) && $option['GROUP'] !== $previousGroup)
					{
						$item['items']['group-' . $groupIndex] = [
							'LEGEND' => true,
							'NAME' => $option['GROUP'],
						];

						$previousGroup = $option['GROUP'];
						++$groupIndex;
					}

					$item['items'][$option['ID']] = [
						'NAME' => $option['VALUE'],
					];
				}
			}
			else
			{
				$item['type'] = 'string';
			}

			$result[$code] = $item;
		}

		return $result;
	}

	public function showFilter(array $fields) : void
	{
		$this->listing()->DisplayFilter($this->filters);
	}

	public function showErrors(array $errors) : void
	{
		foreach ($errors as $message)
		{
			$this->listing()->AddUpdateError($message);
		}
	}
}