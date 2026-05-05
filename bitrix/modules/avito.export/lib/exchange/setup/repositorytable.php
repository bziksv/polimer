<?php
namespace Avito\Export\Exchange\Setup;

use Avito\Export\DB;
use Avito\Export\Feed;
use Avito\Export\Push;
use Avito\Export\Glossary;
use Avito\Export\Concerns;
use Avito\Export\Admin\UserField;
use Avito\Export\Logger;
use Bitrix\Main;
use Bitrix\Main\ORM;

class RepositoryTable extends DB\Table
{
	use Concerns\HasLocale;

	public static function getCollectionClass() : string
	{
		return Collection::class;
	}

	public static function getObjectClass() : string
	{
		return Model::class;
	}

	public static function getTableName() : string
	{
		return 'avito_export_exchange';
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new ORM\Fields\StringField('NAME', [
				'required' => true,
			]),

			new ORM\Fields\IntegerField('FEED_ID', [
				'required' => true,
			]),
			new ORM\Fields\Relations\Reference('FEED', Feed\Setup\RepositoryTable::class, ORM\Query\Join::on('this.FEED_ID', 'ref.ID')),

			new ORM\Fields\ArrayField('COMMON_SETTINGS'),

			new ORM\Fields\BooleanField('USE_PUSH'),

			new ORM\Fields\ArrayField('PUSH_SETTINGS'),

			new ORM\Fields\BooleanField('USE_TRADING'),

			new ORM\Fields\ArrayField('TRADING_SETTINGS'),

			new ORM\Fields\BooleanField('USE_CHAT'),

			new ORM\Fields\ArrayField('CHAT_SETTINGS'),

			new ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$tableFields = $connection->getTableFields($tableName);

		if (!isset($tableFields['USE_CHAT']))
		{
			$useChatField = static::getEntity()->getField('USE_CHAT');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('USE_CHAT'),
				$sqlHelper->getColumnTypeByField($useChatField)
			));
		}

		if (!isset($tableFields['CHAT_SETTINGS']))
		{
			$chatSettingsField = static::getEntity()->getField('CHAT_SETTINGS');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('CHAT_SETTINGS'),
				$sqlHelper->getColumnTypeByField($chatSettingsField)
			));
		}
	}

	public static function getMapDescription():array
	{
		self::includeLocale();

		$result = parent::getMapDescription();
		$result['FEED_ID'] = static::extendFeedIdField($result['FEED_ID']);
		$result['USE_PUSH'] = static::extendUsePushField($result['USE_PUSH']);
		$result['USE_CHAT'] = static::extendUseChatField($result['USE_CHAT']);
		$result['USE_TRADING'] = static::extendUseTradingField($result['USE_TRADING']);

		return $result;
	}

	protected static function extendFeedIdField(array $field) : array
	{
		$field['USER_TYPE'] = UserField\Registry::description('enumeration');
		$field['VALUES'] = [];

		$query = Feed\Setup\RepositoryTable::getList([
			'select' => ['ID', 'NAME'],
		]);

		while ($feed = $query->fetch())
		{
			$field['VALUES'][] = [
				'ID' => $feed['ID'],
				'VALUE' => '[' . $feed['ID'] . '] ' . $feed['NAME'],
			];
		}

		return $field;
	}

	protected static function extendUsePushField(array $field) : array
	{
		$field['SETTINGS']['DEFAULT_VALUE'] = UserField\BooleanType::VALUE_Y;

		return $field;
	}

	protected static function extendUseChatField(array $field) : array
	{
		$field['HELP_MESSAGE'] = self::getLocale('USE_CHAT_HELP');
		$field['SETTINGS']['DEFAULT_VALUE'] = UserField\BooleanType::VALUE_Y;

		return $field;
	}

	protected static function extendUseTradingField(array $field) : array
	{
		$field['SETTINGS']['DEFAULT_VALUE'] = UserField\BooleanType::VALUE_Y;

		return $field;
	}

	public static function onDelete(ORM\Event $event) : void
	{
		$primary = $event->getParameter('primary');

		if (empty($primary['ID'])) { return; }

		static::deleteLog($primary['ID']);
		static::deletePushEngineRows($primary['ID']);
	}

	public static function deleteLog(int $exchangeId) : void
	{
		$batch = new DB\Facade\BatchDelete(Logger\Table::class);

		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => [ Glossary::SERVICE_PUSH, Glossary::SERVICE_TRADING ],
				'=SETUP_ID' => $exchangeId,
			],
		]);
	}

	public static function deletePushEngineRows(int $exchangeId) : void
	{
		$tables = [
			Push\Engine\Steps\Stamp\RepositoryTable::class,
			Push\Engine\Steps\PrimaryMap\RepositoryTable::class,
		];

		foreach ($tables as $table)
		{
			$batch = new DB\Facade\BatchDelete($table);

			$batch->run([
				'filter' => [ '=PUSH_ID' => $exchangeId ],
			]);
		}
	}
}
