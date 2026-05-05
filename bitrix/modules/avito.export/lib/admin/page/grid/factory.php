<?php
namespace Avito\Export\Admin\Page\Grid;

class Factory
{
	public static function makeAdapter(string $gridId) : GridAdapter
	{
		$hasUi = (
			\class_exists(\CAdminUiList::class)
			&& \class_exists(\CAdminUiListActionPanel::class)
		);

		return $hasUi ? new UiAdapter($gridId) : new AdminAdapter($gridId);
	}
}