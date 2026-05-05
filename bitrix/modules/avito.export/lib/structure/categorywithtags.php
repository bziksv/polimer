<?php
namespace Avito\Export\Structure;

use Avito\Export\Feed\Tag\Tag;

interface CategoryWithTags
{
	/** @return Tag[] */
	public function tags() : array;
}