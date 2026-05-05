<?php
namespace Avito\Export\Admin\Page;

use Avito\Export;
use Avito\Export\Concerns;
use Avito\Export\Push;
use Avito\Export\Exchange;
use Bitrix\Main;

class ExchangeGrid extends TableGrid
{
	use Concerns\HasLocale;

	protected function getTableEntity() : Main\ORM\Entity
	{
		return Exchange\Setup\RepositoryTable::getEntity();
	}

	protected function handleAction($action, $data) : void
	{
		if ($action === 'delete')
		{
			$this->processActionDelete($data);
		}
		else
		{
			parent::handleAction($action, $data);
		}
	}

	protected function processActionDelete($data) : void
	{
		if (empty($data['ID'])) { return; }

		foreach ((array)$data['ID'] as $pushId)
		{
			$this->deleteExchange($pushId);
		}
	}

	protected function deleteExchange($exchangeId) : void
	{
		/** @var Exchange\Setup\Model $exchange */
		$exchange = $this->getTableEntity()->wakeUpObject($exchangeId);
		$exchange->fill();
		$exchange->deactivate();
		$exchange->delete();
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

	public function getGridId() : string
	{
		return Export\Config::LANG_PREFIX . 'EXCHANGE';
	}

	protected function getContextMenu() : array
	{
		return [
			[
				'TEXT' => self::getLocale('BUTTON_ADD'),
				'ICON' => 'btn_new',
				'LINK' => BX_ROOT . '/admin/avito_export_exchange_edit.php?' . http_build_query([
					'lang' => LANGUAGE_ID,
				]),
			],
		];
	}

	protected function loadFields() : array
	{
		$result = array_diff_key($this->loadTableFields(), [
			'COMMON_SETTINGS' => true,
			'PUSH_SETTINGS' => true,
			'TRADING_SETTINGS' => true,
		]);
		$result += $this->loadSettingsFields();

		return $this->extendFields($result, [
			'ID' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
			],
			'NAME' => [
				'DEFAULT' => true,
				'FILTERABLE' => '%',
			],
			'FEED_ID' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
			],
			'USE_PUSH' => [
				'DEFAULT' => true,
			],
			'USE_TRADING' => [
				'DEFAULT' => true,
			],
			'COMMON_SETTINGS[OAUTH_TOKEN]' => [
				'DEFAULT' => true,
				'SORTABLE' => false,
			],
			'PUSH_SETTINGS[QUANTITY_FIELD]' => [
				'DEFAULT' => true,
				'SORTABLE' => false,
			],
		], [
			'FILTERABLE' => false,
			'SELECTABLE' => true,
			'DEFAULT' => false,
		]);
	}

	protected function loadSettingsFields() : array
	{
		$settingsBridge = new Exchange\Setup\SettingsBridge();
		$groups = [
			'COMMON_SETTINGS' => $settingsBridge->commonSettings(),
			'PUSH_SETTINGS' => $settingsBridge->pushSettings(),
			'TRADING_SETTINGS' => $settingsBridge->tradingSettings(),
		];
		$result = [];

		foreach ($groups as $group => $settings)
		{
			if ($settings === null) { continue; }

			foreach ($settings->fields() as $name => $field)
			{
				$fullName = sprintf('%s[%s]', $group, $name);

				$result[$fullName] = $this->extendField($fullName, $field);
			}
		}

		return $result;
	}

	protected function loadItems(array $queryParameters = []) : array
	{
		return parent::loadItems(array_diff_key($queryParameters, [
			'select' => true,
		]));
	}

	protected function prepareItem(array $row) : array
	{
		$row['EDIT_URL'] = $this->getEditUrl($row['ID']);
		$settingFields = [
			'COMMON_SETTINGS',
			'PUSH_SETTINGS',
			'TRADING_SETTINGS',
		];

		foreach ($settingFields as $settingField)
		{
			if (empty($row[$settingField])) { continue; }

			foreach ($row[$settingField] as $name => $value)
			{
				$settingName = sprintf('%s[%s]', $settingField, $name);

				$row[$settingName] = $value;
			}

		}

		return $row;
	}

	protected function getActionsBuild($item) : array
	{
		return [
			[
				'TYPE' => 'EDIT',
				'TEXT' => self::getLocale('ACTION_EDIT'),
				'URL' => $this->getEditUrl($item['ID']),
			],
			[
				'TYPE' => 'DELETE',
				'ACTION' => 'delete',
				'TEXT' => self::getLocale('ACTION_DELETE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => self::getLocale('ACTION_DELETE_CONFIRM'),
			],
		];
	}

	protected function getEditUrl($feedId) : string
	{
		return BX_ROOT . '/admin/avito_export_push_edit.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'id' => $feedId,
		]);
	}
}
