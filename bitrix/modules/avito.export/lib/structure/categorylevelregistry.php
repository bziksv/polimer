<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;

class CategoryLevelRegistry
{
	use Concerns\HasOnceStatic;

	public static function tags() : array
	{
		return static::onceStatic('tags', static function() {
			$reflection = new \ReflectionClass(CategoryLevel::class);

			return array_values($reflection->getConstants());
		});
	}
}