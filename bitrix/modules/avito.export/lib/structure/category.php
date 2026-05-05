<?php
namespace Avito\Export\Structure;

use Avito\Export\Dictionary;

interface Category
{
	public function name() : string;

	public function dictionary() : Dictionary\Dictionary;

	/** @return Category[] */
	public function children() : array;
}