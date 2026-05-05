<?php
namespace Avito\Export\Admin\Component\Exchange;

use Avito\Export\Data\Number;
use Avito\Export\DB;
use Avito\Export\Feed;
use Avito\Export\Glossary;
use Avito\Export\Push;
use Avito\Export\Watcher;
use Avito\Export\Exchange;
use Avito\Export\Concerns;
use Avito\Export\Admin;
use Avito\Export\Trading;
use Avito\Export\Admin\UserField;
use Avito\Export\Utils\DependField;
use Avito\Export\Utils\Field;
use Bitrix\Main;

class EditForm extends Admin\Component\Data\EditForm
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

    public const ACTION_RESET_PUSH_UNPUBLISHED = 'resetPushUnpublished';
    public const ACTION_RESET_PUSH_STOCKS = 'resetPushStocks';

    public function processPostAction($action, $data) : void
    {
        if ($action === self::ACTION_RESET_PUSH_UNPUBLISHED)
        {
            $this->processResetPushUnpublished((int)$data['PRIMARY']);
            return;
        }

        if ($action === self::ACTION_RESET_PUSH_STOCKS)
        {
            $this->processResetPushStocks((int)$data['PRIMARY']);
            return;
        }

        parent::processPostAction($action, $data);
    }

    protected function processResetPushUnpublished(int $primary) : void
    {
        $this->dropUnpublishedStocks($primary);
        $this->dropUnpublishedPrimaries($primary);
        $this->scheduleRefreshStocks($primary);
        $this->component->delayMessage(self::getLocale('RESET_PUSH_STOCKS_DONE'));
    }

    protected function dropUnpublishedStocks(int $primary) : void
    {
        $batch = new DB\Facade\BatchDelete(Push\Engine\Steps\Stamp\RepositoryTable::class);
        $batch->run([
            'filter' => [
                '=PUSH_ID' => $primary,
                '=TYPE' => Glossary::ENTITY_STOCKS,
                '=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_UNPUBLISHED,
            ],
        ]);
    }

    protected function dropUnpublishedPrimaries(int $primary) : void
    {
        $batch = new DB\Facade\BatchDelete(Push\Engine\Steps\PrimaryMap\RepositoryTable::class);
        $batch->run([
            'filter' => [
                '=PUSH_ID' => $primary,
                '=SERVICE_ID' => Push\Engine\Steps\PrimaryMap\RepositoryTable::SERVICE_ID_NULL,
            ],
        ]);
    }

    protected function processResetPushStocks(int $primary) : void
    {
        $this->dropAllStocks($primary);
        $this->dropAllPrimaries($primary);
        $this->scheduleRefreshStocks($primary);
        $this->component->delayMessage(self::getLocale('RESET_PUSH_STOCKS_DONE'));
    }

    protected function dropAllPrimaries(int $primary) : void
    {
        $batch = new DB\Facade\BatchDelete(Push\Engine\Steps\PrimaryMap\RepositoryTable::class);
        $batch->run([
            'filter' => [ '=PUSH_ID' => $primary ],
        ]);
    }

    protected function dropAllStocks(int $primary) : void
    {
        $batch = new DB\Facade\BatchDelete(Push\Engine\Steps\Stamp\RepositoryTable::class);
        $batch->run([
            'filter' => [
                '=PUSH_ID' => $primary,
                '=TYPE' => Glossary::ENTITY_STOCKS,
            ],
        ]);
    }

    protected function scheduleRefreshStocks(int $primary) : void
    {
        Watcher\Agent\Refresh::register([
            'method' => 'process',
            'interval' => 5,
            'arguments' => [Glossary::SERVICE_PUSH, $primary],
            'next_exec' => new Main\Type\DateTime(),
        ]);
    }

    public function validate($data, array $fields = null) : Main\Result
	{
		$result = parent::validate($data, $fields);
		$result = $this->validateSettings($data, $fields, $result);

		return $result;
	}

	protected function validateSettings($data, array $fields = null, Main\Result $result = null) : Main\Result
	{
		if ($result === null) { $result = new Main\ORM\Data\Result(); }
		if ($fields === null) { return $result; }

		foreach ($fields as $name => $field)
		{
			if (!preg_match('/^(COMMON|PUSH|CHAT|TRADING)_SETTINGS\[(.*)]$/', $name, $matches)) { continue; }

			[, $settingGroup, $settingName] = $matches;
			$value = $data[$settingGroup . '_SETTINGS'][$settingName] ?? null;

			if (empty($value) && $field['MANDATORY'] === 'Y')
			{
				$result->addError(new Main\Error(self::getLocale('VALIDATE_REQUIRED', [
					'#NAME#' => $field['NAME'],
				])));
			}
		}

		return $result;
	}

	public function getFields(array $select = [], $item = null) : array
	{
		$result = array_diff_key($this->loadTableFields($this->getDataClass()), array_filter([
			'ID' => true,
			'SETTINGS' => true,
			'TIMESTAMP_X' => true,
			'USE_TRADING' => !array_key_exists('TRADING', $this->settingsGroups()),
		]));

		if ($this->needSelectSettings($select))
		{
			$result += $this->loadSettingsFields((int)$item['FEED_ID']);
		}

		$result = $this->applyFieldsDefaults($result, $this->getDefaults($result));
		$result = $this->extendFeedIdFields($result);

		$result = $this->filterSelectFields($result, $select);

		return $result;
	}

	protected function needSelectSettings(array $select) : bool
	{
		if (empty($select)) { return true; }

		$result = false;

		foreach ($select as $name)
		{
			if (
				mb_strpos($name, 'COMMON_SETTINGS[') === 0
				|| mb_strpos($name, 'PUSH_SETTINGS[') === 0
				|| mb_strpos($name, 'CHAT_SETTINGS[') === 0
				|| mb_strpos($name, 'TRADING_SETTINGS[') === 0
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function getDefaults(array $fields) : array
	{
		$result = [
			'NAME' => self::getLocale('DEFAULT_NAME'),
		];

		if (!empty($fields['FEED_ID']['VALUES']))
		{
			$feedOption = reset($fields['FEED_ID']['VALUES']);

			$result['FEED_ID'] = $feedOption['ID'];
		}

		return $result;
	}

	protected function extendFeedIdFields(array $fields) : array
	{
		if (!empty($fields['FEED_ID']))
		{
			$fields['FEED_ID']['SETTINGS']['ONCHANGE'] = 'this.form.insertAdjacentHTML("beforeend", "<input type=\"hidden\" name=\"reload\" value=\"Y\" />");';
			$fields['FEED_ID']['SETTINGS']['ONCHANGE'] .= 'this.form.submit();';

			if (empty($fields['FEED_ID']['VALUES']))
			{
				throw new Main\SystemException(self::getLocale('FEED_ENUM_EMPTY', [
					'#FEED_URL#' => 'avito_export_feed_edit.php?' . http_build_query([
						'lang' => 'ru',
					]),
				]));
			}
		}

		return $fields;
	}

	protected function loadSettingsFields(int $feedId) : array
	{
		return $this->once('loadSettingsFields-' . $feedId, function() use ($feedId) {
			$feedSites = $this->feedSites($feedId);
			$groupDependMap = [
				'PUSH' => 'USE_PUSH',
				'TRADING' => 'USE_TRADING',
				'CHAT' => 'USE_CHAT',
			];
			$result = [];

			foreach ($this->settingsGroups() as $group => $settings)
			{
				if ($settings instanceof Trading\Setup\Settings)
				{
					$this->preloadTradingEnvironment($feedSites);
				}

				$groupFields = [];

				foreach ($settings->fields($feedSites) as $name => $field)
				{
					$fullName = sprintf('%s_SETTINGS[%s]', $group, $name);

					if (isset($field['DEPEND']))
					{
						$newDepend = [];

						foreach ($field['DEPEND'] as $dependName => $depend)
						{
							$newDependName = sprintf('%s_SETTINGS[%s]', $group, $dependName);

							$newDepend[$newDependName] = $depend;
						}

						$field['DEPEND'] = $newDepend;
					}

					if (isset($groupDependMap[$group]))
					{
						$field['DEPEND'] = ($field['DEPEND'] ?? []) + [
							$groupDependMap[$group] => [
								'RULE' => DependField::RULE_ANY,
								'VALUE' => UserField\BooleanType::VALUE_Y,
							],
						];
					}

					$groupFields[$fullName] = $this->extendField($fullName, $field);
				}

				$result += $groupFields;
			}

			return $result;
		});
	}

	/** @return Exchange\Setup\SettingsInterface[] */
	protected function settingsGroups() : array
	{
		return $this->once('settingsGroups', function() {
			$settings = new Exchange\Setup\SettingsBridge();

			return array_filter([
				'COMMON' => $settings->commonSettings(),
				'PUSH' => $settings->pushSettings(),
				'CHAT' => $settings->chatSettings(),
				'TRADING' => $settings->tradingSettings(),
			]);
		});
	}

	protected function preloadTradingEnvironment(array $feedSites) : void
	{
		$environment = Trading\Entity\Registry::environment();
		$anonymousUser = $environment->anonymousUser();
		$stored = \CUserOptions::GetOption($this->getComponentParam('FORM_ID'), 'TRADING_USER');

		if (Number::cast($stored) === null && $anonymousUser->id() === null)
		{
			$installResult = $anonymousUser->install((string)reset($feedSites));

			\CUserOptions::SetOption($this->getComponentParam('FORM_ID'), 'TRADING_USER', (int)$installResult->getId()); // create only on first form open
		}
	}

	protected function feedSites(int $feedId) : ?array
	{
		try
		{
			$feed = Feed\Setup\Model::getById($feedId);
			$result = $feed->allSites();
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = [];
		}

		return $result;
	}

	public function extend($data, array $select = []) : array
	{
		if (!empty($data)) { return $data; }

		$fields = $this->getComponentResult('FIELDS');

		foreach ($fields as $name => $field)
		{
			if (empty($field['SETTINGS']['DEFAULT_VALUE'])) { continue; }

			Field::setChainValue($data, $name, $field['SETTINGS']['DEFAULT_VALUE'], Field::GLUE_BRACKET);
		}

		return $data;
	}

	protected function beforeAdd(Main\ORM\Objectify\EntityObject $model) : void
	{
		/** @var Exchange\Setup\Model $model */
		$model->setTimestampX(new Main\Type\DateTime());
	}

	protected function afterAdd(Main\ORM\Objectify\EntityObject $model) : void
	{
		/** @var Exchange\Setup\Model $model */
		$model->activate();
	}

	protected function beforeUpdate(Main\ORM\Objectify\EntityObject $model) : void
	{
		/** @var Exchange\Setup\Model $model */
		$newSettings = $model->settingsBridge();
		$oldSettings = $model->remindSettingsBridge();

		$model->activate();
		$model->setTimestampX(new Main\Type\DateTime());

		if ((string)$newSettings->commonSettings()->value('OAUTH_TOKEN') !== (string)$oldSettings->commonSettings()->value('OAUTH_TOKEN'))
		{
			$this->resetEngine($model);
		}
	}

	protected function resetEngine(Exchange\Setup\Model $model) : void
	{
		Exchange\Setup\RepositoryTable::deleteLog($model->getId());
		Exchange\Setup\RepositoryTable::deletePushEngineRows($model->getId());

		$model->getPush()->refreshStart(true);
	}
}
