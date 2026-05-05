<?php

namespace Avito\Export\Admin\Page;

use Bitrix\Main;
use Avito\Export\Admin;
use Avito\Export\Config;
use Avito\Export\Exchange;
use Avito\Export\Concerns;

class Chat extends Page
{
	use Concerns\HasLocale;

	/** @var Exchange\Setup\Model[] */
	protected $setupCollection = [];
	/** @var Exchange\Setup\Model */
	protected $setup;

	public function getGridId() : string
	{
		return Config::LANG_PREFIX . 'CHAT';
	}

    public function renderPage(string $view = null) : void
    {
	    $this->loadSetupCollection();
	    $this->resolveSetup();
		$this->resolveChat();

	    $this->checkReadAccess();
	    $this->loadModules();

		if ($view !== 'window')
		{
			$this->showSetupSelector();
		}

	    $this->show();
    }

	protected function loadSetupCollection() : void
	{
		$query = Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_CHAT' => true ],
		]);

		while ($exchange = $query->fetchObject())
		{
			$this->setupCollection[$exchange->getId()] = $exchange;
		}
	}

	/** @noinspection DuplicatedCode */
	protected function resolveSetup() : void
	{
		$requested = $this->request->get('setup');
		$stored = \CUserOptions::GetOption($this->getGridId(), 'SETUP');

		if ($requested !== null)
		{
			$requested = (int)$requested;

			if (!isset($this->setupCollection[$requested]))
			{
				throw new Admin\Exception\UserException(self::getLocale('REQUESTED_SETUP_NOT_FOUND', [
					'#ID#' => $requested,
				]));
			}

			if ($requested !== (int)$stored)
			{
				\CUserOptions::SetOption($this->getGridId(), 'SETUP', $requested);
			}

			$this->setup = $this->setupCollection[$requested];
		}
		else if (isset($stored, $this->setupCollection[$stored]))
		{
			$this->setup = $this->setupCollection[$stored];
		}
		else
		{
			if (empty($this->setupCollection))
			{
				throw new Admin\Exception\UserException(
					self::getLocale('SETUP_MISSING'),
					self::getLocale('SETUP_MISSING_DETAILS', [
						'#URL#' => Admin\Path::moduleUrl('exchange', [ 'lang' => LANGUAGE_ID ]),
					])
				);
			}

			$this->setup = reset($this->setupCollection);
		}
	}

	protected function resolveChat() : void
	{
		if (!$this->setup->getUseChat())
		{
			throw new Admin\Exception\UserException(self::getLocale('ENABLE_CHAT'));
		}
	}

	/** @noinspection HtmlUnknownTarget */
	protected function showSetupSelector() : void
	{
		if (count($this->setupCollection) <= 1) { return; }

		global $APPLICATION;

		echo '<div style="margin-bottom: 10px;">';

		foreach ($this->setupCollection as $setup)
		{
			if ($setup === $this->setup)
			{
				echo sprintf(
					' <span class="adm-btn adm-btn-active">%s</span>',
					sprintf('[%s] %s', $setup->getId(), $setup->getName())
				);
			}
			else
			{
				echo sprintf(
					' <a class="adm-btn" href="%s">%s</a>',
					htmlspecialcharsbx($APPLICATION->GetCurPageParam(http_build_query([ 'setup' => $setup->getId() ]), [ 'setup' ])),
					sprintf('[%s] %s', $setup->getId(), $setup->getName())
				);
			}
		}

		echo '</div>';
	}

	protected function show() : void
	{
		global $APPLICATION;

		$layout = Main\Application::getInstance()->getContext()->getRequest()->get('view') ?: null;

		$APPLICATION->IncludeComponent('avito.export:admin.chat', '', [
			'SETUP_ID' => $this->setup->getId(),
			'USER_ID' => $this->setup->settingsBridge()->commonSettings()->token()->getServiceId(),
			'LAYOUT' => $layout,
		]);
	}
}