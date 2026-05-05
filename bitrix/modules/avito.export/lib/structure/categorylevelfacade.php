<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;

class CategoryLevelFacade
{
	use Concerns\HasOnceStatic;

	public static function tags() : array
	{
		return static::onceStatic('tags', static function() {
			return array_values((new \ReflectionClass(CategoryLevel::class))->getConstants());
		});
	}
}