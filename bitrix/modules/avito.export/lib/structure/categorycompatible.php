<?php
namespace Avito\Export\Structure;

interface CategoryCompatible
{
	public function oldNames() : array;
}