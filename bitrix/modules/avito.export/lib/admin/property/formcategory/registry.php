<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Avito\Export\Assert;

class Registry
{
	public const PRODUCT_SECTION = 'productSection';
	public const PRODUCT_ELEMENT = 'productElement';
	public const SECTION = 'section';
	public const ELEMENT = 'element';
	public const SECTION_SELF = 'sectionSelf';
	public const MASSIVE_EDIT = 'massiveEdit';

	public static function types() : array
	{
		return [
			static::PRODUCT_SECTION,
			static::PRODUCT_ELEMENT,
			static::SECTION,
			static::ELEMENT,
			static::SECTION_SELF,
		];
	}

	public static function make(string $type) : Behavior
	{
		$className = __NAMESPACE__ . '\\' . ucfirst($type);

		Assert::classExists($className);
		Assert::isSubclassOf($className, Behavior::class);

		return new $className;
	}
}