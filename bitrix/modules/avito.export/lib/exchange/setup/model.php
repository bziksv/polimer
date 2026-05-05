<?php
namespace Avito\Export\Exchange\Setup;

use Bitrix\Main;
use Avito\Export\Chat;
use Avito\Export\Push;
use Avito\Export\Trading;
use Avito\Export\Concerns;

class Model extends EO_Repository
{
	use Concerns\HasLocale;

	protected $push;
	protected $trading;
	protected $chat;
	protected $settings;

	public static function getById(int $exchangeId) : Model
	{
		/** @var Model $model */
		$model = static::$dataClass::getById($exchangeId)->fetchObject();

		if ($model === null)
		{
			throw new Main\ObjectNotFoundException(self::getLocale('NOT_FOUND', [
				'#ID#' => $exchangeId,
			]));
		}

		return $model;
	}

	/** @noinspection PhpCastIsUnnecessaryInspection */
	public function activate() : void
	{
		$this->togglePush((bool)$this->getUsePush());
		$this->toggleTrading((bool)$this->getUseTrading());
		$this->toggleChat((bool)$this->getUseChat());
	}

	public function deactivate() : void
	{
		$this->togglePush(false);
		$this->toggleTrading(false);
		$this->toggleChat(false);
	}

	public function getPush() : Push\Setup\Model
	{
		if ($this->push === null)
		{
			$this->push = new Push\Setup\Model(
				$this,
				$this->settingsBridge()->pushSettings()
			);
		}

		return $this->push;
	}

	protected function togglePush(bool $direction) : void
	{
		$push = $this->getPush();

		if ($direction)
		{
			$push->activate();
		}
		else
		{
			$push->deactivate();
		}
	}

	public function getChat() : Chat\Setup\Model
	{
		if ($this->chat === null)
		{
			$this->chat = new Chat\Setup\Model(
				$this,
				$this->settingsBridge()->chatSettings()
			);
		}

		return $this->chat;
	}

	protected function toggleChat(bool $direction) : void
	{
		if ($direction)
		{
			$this->getChat()->activate();
		}
		else
		{
			$this->getChat()->deactivate();
		}
	}

	public function getTrading() : ?Trading\Setup\Model
	{
		if ($this->trading === null && $this->settingsBridge()->tradingSettings() !== null)
		{
			$this->trading = new Trading\Setup\Model(
				$this,
				$this->settingsBridge()->tradingSettings()
			);
		}

		return $this->trading;
	}

	protected function toggleTrading(bool $direction) : void
	{
		$trading = $this->getTrading();

		if ($trading === null) { return; }

		if ($direction)
		{
			$trading->activate();
		}
		else
		{
			$trading->deactivate();
		}
	}

	/** @noinspection PhpCastIsUnnecessaryInspection */
	public function remindSettingsBridge() : SettingsBridge
	{
		if (
			!$this->isCommonSettingsFilled()
			|| !$this->isPushSettingsFilled()
			|| !$this->isTradingSettingsFilled()
		)
		{
			$this->fill();
		}

		return new SettingsBridge(
			(array)$this->remindActualCommonSettings(),
			(array)$this->remindActualPushSettings(),
			(array)$this->remindActualTradingSettings()
		);
	}

	/** @noinspection PhpCastIsUnnecessaryInspection */
	public function settingsBridge() : SettingsBridge
	{
		if ($this->settings === null)
		{
			$this->settings = new SettingsBridge(
				(array)$this->getCommonSettings(),
				(array)$this->getPushSettings(),
				(array)$this->getTradingSettings(),
				(array)$this->getChatSettings()
			);
		}

		return $this->settings;
	}
}