<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;

class ForBusiness implements Category, CategoryCompatible
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function oldNames() : array
	{
		return explode('||', self::getLocale('OLD_NAMES'));
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			return [
				new ForBusiness\ReadyBusiness(),
				new ForBusiness\EquipmentForBusiness(),
			];
		});
	}
}