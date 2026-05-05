<?php

namespace Avito\Export\Admin\Page;

use Bitrix\Main;
use Avito\Export;

class Feeds extends TableGrid
{
	use Export\Concerns\HasLocale;

	protected function getTableEntity() : Main\ORM\Entity
	{
		return Export\Feed\Setup\RepositoryTable::getEntity();
	}

	protected function handleAction($action, $data):void
	{
		if ($action === 'delete')
		{
			$this->processFeedsDelete($data);
		}
		else
		{
			parent::handleAction($action, $data);
		}
	}

	protected function processFeedsDelete($data): void
	{
		if (empty($data['ID'])) { return; }

		foreach ($data['ID'] as $feedId)
		{
			$this->deleteFeed($feedId);
		}
	}

	protected function deleteFeed($feedId): void
	{
		/** @var Export\Feed\Setup\Model $feed */
		$feed = $this->getTableEntity()->wakeUpObject($feedId);
		$feed->fill();

		$feed->deactivate();
		$feed->delete();
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
		return Export\Config::LANG_PREFIX . 'FEEDS1';
	}

	protected function getContextMenu():array
	{
		return [
			[
				'TEXT' => self::getLocale('BUTTON_ADD'),
				'ICON' => 'btn_new',
				'LINK' => BX_ROOT . '/admin/avito_export_feed_edit.php?' . http_build_query([
					'lang' => LANGUAGE_ID,
				]),
			],
		];
	}

	protected function loadFields() : array
	{
		$result = array_diff_key($this->loadTableFields(), [
			'FILTER' => true,
			'TAGS' => true,
			'TIMESTAMP_X' => true,
		]);

		$result = $this->extendFields($result, [
			'ID' => [
				'DEFAULT' => true,
				'FILTERABLE' => true,
			],
			'NAME' => [
				'DEFAULT' => true,
				'FILTERABLE' => '%',
			],
			'SITE' => [
				'DEFAULT' => true,
			],
			'IBLOCK' => [
				'DEFAULT' => true,
			],
			'HTTPS' => [
				'DEFAULT' => true,
			],
			'FILE_NAME' => [
				'DEFAULT' => true,
				'TYPE' => 'file',
				'SETTINGS' => [
					'RESTORE_URL' =>
						BX_ROOT . '/admin/avito_export_feed_run.php?'
						. http_build_query([ 'lang' => LANGUAGE_ID ])
						. '&id=#ID#',
				],
			],
		], [
			'FILTERABLE' => false,
			'SELECTABLE' => true,
			'DEFAULT' => false,
		]);

		return $result;
	}

	protected function getActionsBuild($item): array
	{
		return [
			[
				'TYPE' => 'RUN',
				'TEXT' => self::getLocale('ACTION_RUN'),
				'URL' => $this->getRunUrl($item['ID']),
			],
			[
				'TYPE' => 'EDIT',
				'TEXT' => self::getLocale('ACTION_EDIT'),
				'URL' => $this->getEditUrl($item['ID']),
			],
			[
				'TYPE' => 'COPY',
				'TEXT' => self::getLocale('ACTION_COPY'),
				'URL' => $this->getCopyUrl($item['ID']),
			],
			[
				'TYPE' => 'DELETE',
				'ACTION' => 'delete',
				'TEXT' => self::getLocale('ACTION_DELETE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => self::getLocale('ACTION_DELETE_CONFIRM'),
			]
		];
	}

	protected function prepareItem(array $row) : array
	{
		$row['EDIT_URL'] = $this->getEditUrl($row['ID']);

		return $row;
	}

	protected function getEditUrl($feedId): string
	{
		return BX_ROOT . '/admin/avito_export_feed_edit.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'id' => $feedId,
		]);
	}

	protected function getCopyUrl($feedId): string
	{
		return BX_ROOT . '/admin/avito_export_feed_edit.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'id' => $feedId,
			'copy' => 'Y'
		]);
	}

	protected function getRunUrl($feedId) : string
	{
		return BX_ROOT . '/admin/avito_export_feed_run.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'id' => $feedId,
		]);
	}
}