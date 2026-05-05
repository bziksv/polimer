<?php
namespace Avito\Export\Admin\Property\FormCategory;

interface Behavior
{
	public function title();

	public function variants(array $property) : array;

	public function options(array $property, string $field) : array;

	public function value(array $form) : string;

	public function elementValues(string $propertyId, array $elementIds) : array;

	public function saveValues(string $propertyId, array $elementIds, string $value) : void;
}