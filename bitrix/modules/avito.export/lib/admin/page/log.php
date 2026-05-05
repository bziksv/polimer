<?php
namespace Avito\Export\Admin\Page;

use Avito\Export\Concerns;
use Avito\Export\Config;
use Avito\Export\Logger;
use Avito\Export\Glossary;
use Avito\Export\DB;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Iblock;

class Log extends TableGrid
{
	use Concerns\HasLocale;

	public function getGridId() : string
	{
		return Config::LANG_PREFIX . 'LOGS';
	}

	protected function getTableEntity() : ORM\Entity
	{
		return Logger\Table::getEntity();
	}

	protected function loadFields() : array
	{
		$result = array_diff_key($this->loadTableFields(), [
			'SIGN' => true,
		]);
		$result += [
			'ORDER_ID' => [
				'TYPE' => 'string',
				'LIST_COLUMN_LABEL' => self::getLocale('FIELD_ORDER_ID'),
				'SELECTABLE' => false,
				'FILTERABLE' => true,
			],
			'OFFER_ID' => [
				'TYPE' => 'string',
				'LIST_COLUMN_LABEL' => self::getLocale('FIELD_OFFER_ID'),
				'SELECTABLE' => false,
				'FILTERABLE' => true,
			],
		];

		return $this->extendFields($result, [
			'SETUP_TYPE' => [
				'DEFAULT' => true,
			],
			'SETUP_ID' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
				'FILTER_DEFAULT' => true,
			],
			'ENTITY_TYPE' => [
				'FILTERABLE' => true,
			],
			'ENTITY_ID' => [
				'LIST_COLUMN_LABEL' => self::getLocale('FIELD_ENTITY_ID'),
				'DEFAULT' => true,
				'FILTERABLE' => false,
			],
			'REGION_ID' => [
				'LIST_COLUMN_LABEL' => self::getLocale('FIELD_REGION_ID'),
				'DEFAULT' => true,
				'FILTERABLE' => true,
			],
			'LEVEL' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
				'FILTER_DEFAULT' => true,
			],
			'MESSAGE' => [
				'DEFAULT' => true,
				'FILTERABLE' => '%',
			],
			'CONTEXT' => [
				'TYPE' => 'logContext',
				'DEFAULT' => true,
			],
			'TIMESTAMP_X' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
			],
		], [
			'FILTERABLE' => false,
			'SELECTABLE' => true,
			'DEFAULT' => false,
		]);
	}

	protected function defaultSort() : array
	{
		return [
			'TIMESTAMP_X' => 'DESC',
		];
	}

	protected function useExcelExport() : bool
	{
		return true;
	}

	protected function loadItems(array $queryParameters = []) : array
	{
		$queryParameters = $this->sanitizeQueryParameters($queryParameters);

		if (isset($queryParameters['select']))
		{
			$queryParameters['select'][] = 'SETUP_TYPE';
			$queryParameters['select'][] = 'ENTITY_TYPE';
			$queryParameters['select'][] = 'SIGN';
		}

		$result = parent::loadItems($queryParameters);
		$result = $this->beatifyItemsIblockElement($result);

		return $result;
	}

	protected function loadTotalCount(array $queryParameters = []) : int
	{
		$queryParameters = $this->sanitizeQueryParameters($queryParameters);

		return parent::loadTotalCount($queryParameters);
	}

	protected function sanitizeQueryParameters(array $queryParameters) : array
	{
		$queryParameters = $this->sanitizeQuerySetupId($queryParameters);
		$queryParameters = $this->sanitizeQueryElementFilter($queryParameters);

		return $queryParameters;
	}

	protected function sanitizeQuerySetupId(array $queryParameters) : array
	{
		$setupIdKeys = [ '=SETUP_ID', 'SETUP_ID' ];

		foreach ($setupIdKeys as $setupIdKey)
		{
			if (
				isset($queryParameters['filter'][$setupIdKey])
				&& mb_strpos($queryParameters['filter'][$setupIdKey], ':') !== false
			)
			{
				[$type, $id] = explode(':', $queryParameters['filter'][$setupIdKey], 2);

				$queryParameters['filter'] = [
						'=SETUP_TYPE' => $type,
						'=SETUP_ID' => $id,
					] + array_diff_key($queryParameters['filter'], [
						$setupIdKey => true,
					]);
			}
		}

		return $queryParameters;
	}

	protected function sanitizeQueryElementFilter(array $queryParameters) : array
	{
		$fieldMap = [
			'OFFER_ID' => [
				Glossary::ENTITY_OFFER,
				Glossary::ENTITY_PRICE,
				Glossary::ENTITY_STOCKS,
			],
			'ORDER_ID' => Glossary::ENTITY_ORDER,
		];

		foreach ($fieldMap as $name => $entityType)
		{
			$variants = [ $name, '=' . $name ];

			foreach ($variants as $variant)
			{
				if (!isset($queryParameters['filter'][$variant])) { continue; }

				$queryParameters['filter'] = [
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_ID' => $queryParameters['filter'][$variant],
				] + array_diff_key($queryParameters['filter'], [
					$variant => true,
				]);
			}
		}

		return $queryParameters;
	}

	protected function prepareItem(array $row) : array
	{
		$row['SETUP_ID'] = $row['SETUP_TYPE'] . ':' . $row['SETUP_ID'];

		return $row;
	}

	protected function beatifyItemsIblockElement(array $items) : array
	{
		$offerTypes = [
			Glossary::ENTITY_OFFER => true,
			Glossary::ENTITY_STOCKS => true,
			Glossary::ENTITY_PRICE => true,
		];
		$offerItems = array_filter($items, static function(array $item) use ($offerTypes) { return isset($offerTypes[$item['ENTITY_TYPE']]); });
		$offerIds =
			array_column($offerItems, 'ENTITY_ID', 'ENTITY_ID')
			+ array_column($offerItems, 'REGION_ID', 'REGION_ID');
		$offerIds = array_unique(array_filter($offerIds));
		$offers = $this->loadIblockElementDisplayNames($offerIds);

		foreach ($items as &$item)
		{
			// region

			$item['REGION_ID'] = (!empty($item['REGION_ID']) && isset($offers[$item['REGION_ID']]))
				? $offers[$item['REGION_ID']]
				: '';

			// offer

			if (isset($offerTypes[$item['ENTITY_TYPE']], $offers[$item['ENTITY_ID']]))
			{
				$item['ENTITY_ID'] = $offers[$item['ENTITY_ID']];
			}
		}
		unset($item);

		return $items;
	}

	protected function loadIblockElementDisplayNames(array $offerIds) : array
	{
		if (!Loader::includeModule('iblock')) { return []; }

		$result = [];

		foreach (array_chunk($offerIds, 500) as $offerChunk)
		{
			$query = Iblock\ElementTable::getList([
				'filter' => [ '=ID' => $offerChunk ],
				'select' => [ 'ID', 'NAME' ],
			]);

			while ($row = $query->fetch())
			{
				$result[$row['ID']] = sprintf('[%s] %s', $row['ID'], $row['NAME']);
			}
		}

		return $result;
	}

	protected function getActionsBuild($item): array
	{
		return [
			[
				'TYPE' => 'DELETE',
				'ACTION' => 'delete',
				'TEXT' => self::getLocale('ACTION_DELETE'),
				'PRIMARY' => [ 'SETUP_ID', 'SIGN' ],
			],
		];
	}

	protected function handleAction($action, $data) : void
	{
		if ($action === 'delete')
		{
			$this->processLogDelete($data);
		}
		else
		{
			parent::handleAction($action, $data);
		}
	}

	protected function processLogDelete($data) : void
	{
		if (empty($data['ID'])) { return; }

		$filter = [];

		foreach ($data['ID'] as $id)
		{
			[$type, $typeId, $sign] = explode(':', $id, 3);

			$filter[] = [
				'=SETUP_TYPE' => $type,
				'=SETUP_ID' => $typeId,
				'=SIGN' => $sign,
			];
		}

		foreach (array_chunk($filter, 500) as $filterChunk)
		{
			$batch = new DB\Facade\BatchDelete($this->getTableEntity()->getDataClass());
			$batch->run([
				'filter' => $filterChunk,
			]);
		}
	}

	public function setTitle() : void
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(self::getLocale('TITLE'));
	}

	public function renderPage() : void
	{
		global $APPLICATION;

		if ($this->hasAjaxRequest()) { $APPLICATION->RestartBuffer(); }

		if ($this->hasRequestAction())
		{
			$this->processAction();
		}

		$this->checkReadAccess();
		$this->loadModules();

		$this->show();

		if ($this->hasAjaxRequest()) { die(); }
	}
}
