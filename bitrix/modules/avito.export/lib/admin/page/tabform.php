<?php

namespace Avito\Export\Admin\Page;

abstract class TabForm extends Form
{
	public function showTabs():void
	{
		$tabs = $this->getTabs();
		$view = new \CAdminTabControl($this->getTabsId(), $tabs);

		$this->showFormProlog();
		$view->Begin();

		foreach ($tabs as $tab)
		{
			/** @noinspection DisconnectedForeachInstructionInspection */
			$view->BeginNextTab();
			$this->showTab($tab['DIV']);
		}

		$view->Buttons();
		$this->showFormButtons();

		$view->End();
		$this->showFormEpilog();
	}

	abstract public function getTabs();

	abstract public function getTabsId();

	protected function showTab($tab):void
	{
		$this->showFields($tab);
	}

	protected function getFormId() : string
	{
		$tabsId = $this->getTabsId();

		return $tabsId . '_form';
	}
}
