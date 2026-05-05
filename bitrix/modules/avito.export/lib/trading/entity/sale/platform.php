<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Main;

class Platform
{
	use Concerns\HasLocale;

	public const CODE = 'avito_export';

	protected $environment;
	/** @var Internals\Platform */
	protected $systemPlatform;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
		$this->systemPlatform = Internals\Platform::getInstanceByCode(static::CODE);
	}

	public function id()
	{
		return $this->systemPlatform->getId();
	}

	public function isInstalled() : bool
	{
		return (
			$this->systemPlatform->isInstalled()
			&& $this->systemPlatform->isActive()
		);
	}

	public function install() : Main\Result
	{
		$installResult = $this->applyInstall();

		if (!$installResult->isSuccess())
		{
			return $installResult;
		}

		return $this->applyActivate();
	}

	protected function applyInstall() : Main\Result
	{
		if ($this->systemPlatform->isInstalled()) { return new Main\Result(); }

		return $this->systemPlatform->installExtended([
			'NAME' => self::getLocale('NAME'),
		]);
	}

	protected function applyActivate() : Main\Result
	{
		$result = new Main\Result();

		if (!$this->systemPlatform->setActive())
		{
			$result->addError(new Main\Error(self::getLocale('ACTIVATE_FAILED')));
		}

		return $result;
	}
}