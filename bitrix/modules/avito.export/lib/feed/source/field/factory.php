<?php
namespace Avito\Export\Feed\Source\Field;

use Avito\Export\Assert;

class Factory
{
	protected $typeMap = [
		'S' => StringField::class,
		'N' => NumberField::class,
		'F' => FileField::class,
		'L' => EnumField::class,
		'DateTime' => DateField::class,
		'Boolean' => BooleanField::class,
	];

	public function make(array $field) : Field
	{
		$type = $field['TYPE'] ?? 'S';
		$class = $this->typeMap[$type] ?? $this->typeMap['S'];

		Assert::isSubclassOf($class, Field::class);

		return new $class($field);
	}
}