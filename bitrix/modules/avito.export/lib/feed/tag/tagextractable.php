<?php

namespace Avito\Export\Feed\Tag;

interface TagExtractable
{
	public function extract($value, array $tagLink, Format $format) : array;
}