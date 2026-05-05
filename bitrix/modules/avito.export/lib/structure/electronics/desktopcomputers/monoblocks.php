<?php
namespace Avito\Export\Structure\Electronics\DesktopComputers;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class MonoBlocks implements Category, CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_SUB_TYPE;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\XmlCascade('electronics/monobloki.xml');
	}

	public function children() : array
	{
		return [];
	}
}