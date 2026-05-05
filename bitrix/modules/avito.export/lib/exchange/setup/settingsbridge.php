<?php
namespace Avito\Export\Exchange\Setup;

use Avito\Export\Concerns;
use Avito\Export\Push;
use Avito\Export\Chat;
use Avito\Export\Trading;
use Bitrix\Main;

class SettingsBridge
{
	use Concerns\HasLocale;

	protected $commonSettings;
	protected $pushSettings;
	protected $chatSettings;
	protected $tradingSettings;

	public function __construct(array $commonValues = [], array $pushValues = [], array $tradingValues = [], array $chatValues = [])
	{
		$this->commonSettings = new Settings($commonValues);
		$this->pushSettings = new Push\Setup\Settings($this, $pushValues);
		$this->tradingSettings = $this->makeTradingSettings($tradingValues);
		$this->chatSettings = new Chat\Setup\Settings($this, $chatValues);
	}

	public function commonSettings() : Settings
	{
		return $this->commonSettings;
	}

	public function pushSettings() : Push\Setup\Settings
	{
		return $this->pushSettings;
	}

	public function chatSettings() : Chat\Setup\Settings
	{
		return $this->chatSettings;
	}

	public function tradingSettings() : ?Trading\Setup\Settings
	{
		return $this->tradingSettings;
	}

	protected function makeTradingSettings(array $tradingValues) : ?Trading\Setup\Settings
	{
		try
		{
			$result = new Trading\Setup\Settings($this, $tradingValues);
		}
		catch (Main\NotSupportedException $exception)
		{
			$result = null;
		}

		return $result;
	}
}