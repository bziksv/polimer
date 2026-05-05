<?php
namespace Avito\Export\Chat\Setup;

use Avito\Export\Concerns;
use Avito\Export\Exchange;

class Settings extends Exchange\Setup\SettingsSkeleton
{
	use Concerns\HasLocale;

	protected $settingsBridge;

	public function __construct(Exchange\Setup\SettingsBridge $settingsBridge, array $values)
	{
		parent::__construct($values);

		$this->settingsBridge = $settingsBridge;
	}

	public function commonSettings() : Exchange\Setup\Settings
	{
		return $this->settingsBridge->commonSettings();
	}

	public function fields(array $sites = null) : array
	{
		return [];
	}
}