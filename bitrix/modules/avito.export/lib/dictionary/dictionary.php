<?php
namespace Avito\Export\Dictionary;

interface Dictionary
{
	public function useParent() : bool;

	public function known(array $attributes) : array;

	public function attributes(array $values = []) : array;

	public function variants(string $attribute, array $values = []) : ?array;
}