<?php
namespace Avito\Export\Feed\Source;

interface FetcherMergable
{
	public function merge(array $sourceValues, array $mergeMap) : array;
}