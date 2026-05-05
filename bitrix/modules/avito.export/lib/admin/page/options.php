<?php

namespace Avito\Export\Admin\Page;

use Avito\Export\Config;
use Avito\Export\Concerns;
use Avito\Export\Psr\Logger;
use Avito\Export\Utils;
use Bitrix\Main;
use CAdminMessage;

class Options extends TabForm
{
	use Concerns\HasLocale;

	protected $options;

	public function getTabsId() : string
	{
		return Config::LANG_PREFIX . 'OPTIONS';
	}

	public function getTabs() : array
	{
		return [
			[
				'DIV' => 'COMMON',
				'TAB' => self::getLocale('TAB_COMMON'),
			],
			[
				'DIV' => 'PERMISSIONS',
				'TAB' => self::getLocale('TAB_PERMISSIONS'),
			],
		];
	}

	public function getFieldGroups() : array
	{
		return [
			'PUSH_API' => self::getLocale('GROUP_PUSH_API')
		];
	}

	public function getFields($tab = null) : array
	{
		$options = $this->getOptions();

		if ($tab === null) { return $options; }

		return array_filter($options, static function(array $option) use ($tab) {
			return $option['TAB'] === $tab;
		});
	}

	public function getOptions() : array
	{
		if ($this->options === null)
		{
			$this->options = $this->loadOptions();
		}

		return $this->options;
	}

	protected function loadOptions(): array
	{
		$cronConfigured = Utils\Agent::cronConfigured();
		$disabled = array_filter([
			'agent_time_limit_cli' => !$cronConfigured,
			'agent_time_limit' => $cronConfigured,
		]);

		return $this->extendFields(array_diff_key([
			'agent_time_limit_cli' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('AGENT_TIME_LIMIT_CLI'),
				'DEFAULT' => 30,
			],
			'agent_time_limit' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('AGENT_TIME_LIMIT'),
				'DEFAULT' => 5,
			],
			'export_changes_limit' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('EXPORT_CHANGES_LIMIT'),
				'DEFAULT' => 500,
			],
			'export_log_level' => [
				'TYPE' => 'enumeration',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('LEVEL_LOG'),
				'VALUES' => [
					[
						'ID' => Logger\LogLevel::INFO,
						'VALUE' => self::getLocale('LEVEL_LOG_TYPE_INFO'),
					],
					[
						'ID' => Logger\LogLevel::ERROR,
						'VALUE' => self::getLocale('LEVEL_LOG_TYPE_ERROR'),
					],
					[
						'ID' => Logger\LogLevel::CRITICAL,
						'VALUE' => self::getLocale('LEVEL_LOG_TYPE_CRITICAL'),
					],
				],
				'MULTIPLE' => 'N',
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => Logger\LogLevel::INFO,
				],
			],
			'export_writer_temp_memory' => [
				'TYPE' => 'boolean',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('WRITER_TEMP_MEMORY'),
			],
			'massive_edit_elements_limit' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('MASSIVE_EDIT_ELEMENTS_LIMIT'),
				'DEFAULT' => 20000,
			],
			'discounts_user_group' => [
				'TYPE' => 'enumeration',
				'TAB' => 'COMMON',
				'NAME' => self::getLocale('DISCOUNTS_USER_GROUP'),
				'VALUES' => $this->getUserGroupVariants(),
				'MULTIPLE' => 'N',
			],
			'push_repeat_failed' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'GROUP' => 'PUSH_API',
				'NAME' => self::getLocale('PUSH_REPEAT_FAILED'),
				'UNIT' => self::getLocale('PUSH_REPEAT_UNIT'),
				'DEFAULT' => 1,
				'SETTINGS' => [
					'SIZE' => 3,
				],
			],
			'push_repeat_unpublished' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'GROUP' => 'PUSH_API',
				'NAME' => self::getLocale('PUSH_REPEAT_UNPUBLISHED'),
				'UNIT' => self::getLocale('PUSH_REPEAT_UNIT_HOUR'),
				'DEFAULT' => 2,
				'SETTINGS' => [
					'SIZE' => 3,
				],
			],
			'push_repeat_ready' => [
				'TYPE' => 'string',
				'TAB' => 'COMMON',
				'GROUP' => 'PUSH_API',
				'NAME' => self::getLocale('PUSH_REPEAT_READY'),
				'UNIT' => self::getLocale('PUSH_REPEAT_UNIT'),
				'DEFAULT' => 7,
				'SETTINGS' => [
					'SIZE' => 3,
				],
			],
		], $disabled));
	}

	protected function getUserGroupVariants() : array
	{
		$query = Main\GroupTable::getList([
			'select' => [ 'ID', 'NAME' ],
			'filter' => [ '=ACTIVE' => 'Y' ],
			'order' => [ 'ID' => 'ASC' ]
		]);

		return array_map(static function ($row) {
			return [
				'ID' => $row['ID'],
				'VALUE' => sprintf('%s [%s]', $row['NAME'], $row['ID'])
			];
		}, $query->fetchAll());
	}

	public function getFieldValue($fieldCode)
	{
		$value = $this->getOptionValue($fieldCode);

		if ($value === null)
		{
			$fields = $this->getOptions();
			$field = $fields[$fieldCode] ?? null;
			$value = $field['DEFAULT'] ?? null;
		}

		return $value;
	}

	public function getOptionValue($name)
	{
		return Config::getOption($name);
	}

	public function processRequest() : void
	{
		$this->processOptionsRequest();
		$this->processPermissionsRequest();
	}

	protected function processOptionsRequest() : void
	{
		$options = $this->getOptions();

		$emptyRequiredFields = [];

		foreach ($options as $optionCode => $option)
		{
			$optionRequest = $this->request->getPost($optionCode);

			$isRequired = (isset($option['REQUIRED']) && (bool)$option['REQUIRED'] === true);

			if ($isRequired && empty(trim($optionRequest)))
			{
				$emptyRequiredFields[] = '"' . $option['NAME'] . '"';

				continue;
			}

			if (
				$optionCode === 'LIMIT_PROCESS_DISCOUNT'
				&& (string)((int)$optionRequest) !== trim($optionRequest)
			)
			{
				throw new Main\SystemException(self::getLocale('LIMIT_TYPE_ERROR', [
					'#FIELD_NAME#' => $option['NAME'],
				]));
			}

			Config::setOption($optionCode, $optionRequest);
		}

		if (!empty($emptyRequiredFields))
		{
			$stringForError = implode(', ', $emptyRequiredFields);

			throw new Main\SystemException(self::getLocale('FIELD_REQUIRED_ERROR', [
				'#FIELD_REQUIRED_ERROR#' => $stringForError,
			]));
		}
	}

	protected function processPermissionsRequest() : void
	{
		ob_start();
		$this->includePermissions(true);
		ob_end_clean();
	}

	protected function showTab($tab) : void
	{
		if ($tab === 'PERMISSIONS')
		{
			$this->showPermissions();
		}
		else
		{
			parent::showTab($tab);
		}
	}

	protected function showPermissions() : void
	{
		$this->includePermissions();
	}

	/**
	 * @noinspection PhpUnusedLocalVariableInspection
	 * @noinspection PhpIncludeInspection
	 */
	protected function includePermissions($update = false) : void
	{
		global $APPLICATION;
		global $USER;
		global $GROUPS;
		global $RIGHTS;
		global $SITES;
		global $REQUEST_METHOD;

		$module_id = Config::getModuleName();

		if ($update)
		{
			$Update = '1'; // need inside main module
		}

		require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
	}

	public function renderPage() : void
	{
		Main\UI\Extension::load('avitoexport.admin.style');

		$isAllowDisplay = false;

		try
		{
			$this->checkReadAccess();
			$this->loadModules();
			$isAllowDisplay = true;

			if ($this->hasRequest())
			{
				$this->checkSession();
				$this->checkSaveAccess();
				$this->processRequest();
				$this->refreshPage();
			}
		}
		catch (Main\SystemException $exception)
		{
			$message = new CAdminMessage($exception->getMessage());
			echo $message->Show();
		}

		if ($isAllowDisplay)
		{
			$this->showTabs();
		}
	}
}
