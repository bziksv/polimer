<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;

class Index implements Category
{
	use Concerns\HasOnce;

	public function name() : string
	{
		return 'index';
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			return [
                new PersonalBelongings(),
				new Transportation(),
				new ForHomeAndGarden(),
				new Animals(),
				new HobbiesAndRecreation(),
				new Electronics(),
				new ForBusiness(),
			];
		});
	}
}