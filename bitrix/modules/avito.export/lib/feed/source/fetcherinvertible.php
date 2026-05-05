<?php
namespace Avito\Export\Feed\Source;

use Avito\Export\Feed;

interface FetcherInvertible
{
	public function elements(array $values, string $field, Context $context) : array;
}