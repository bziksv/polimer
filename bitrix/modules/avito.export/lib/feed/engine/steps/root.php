<?php
namespace Avito\Export\Feed\Engine\Steps;

use Avito\Export\Feed;
use Avito\Export\Watcher;
use Avito\Export\Concerns;
use Bitrix\Main\ORM\Entity;

class Root extends Step
{
	use Concerns\HasLocale;

	public const TYPE = 'root';

	public function getName() : string
	{
		return static::TYPE;
	}

	public function getTitle() : string
	{
		return self::getLocale('TITLE', null, 'offer');
	}

	public function getFormatVersion() : string
	{
		return '3';
	}

	public function getTag(): string
	{
		return 'Ads';
	}

	public function getTagProps(): string
	{
		return ' formatVersion="' . $this->getFormatVersion() . '" target="Avito.ru" crm_version="BitrixAvitoModule"';
	}

	public function clear($isStrict = false) : void
	{
		$this->getWriter()->remove($isStrict);
	}

	public function start($action, $offset = null): void
	{
		if (
			$action === Watcher\Engine\Controller::ACTION_FULL
			|| $action === Feed\Engine\Controller::ACTION_RESTART
		)
		{
			$contents = $this->getContents();
			$this->getWriter()->write($contents);
		}
		else
		{
			$this->getWriter()->copy();
		}
	}

	public function finalize(): void
	{
		$this->getWriter()->finalize();
	}

	private function getContents(): string
	{
		return
			$this->getHeader() .
			'<' . $this->getTag() . $this->getTagProps() . '>'
			. '</' . $this->getTag() . '>';
	}

	private function getHeader(): string
	{
		return '<?xml version="1.0" encoding="' . LANG_CHARSET . '" ?>';
	}

	protected function getStorageDataEntity() : ?Entity
	{
		return null;
	}

	public function getParentTag() : string
	{
		return '';
	}
}