<?php
namespace Avito\Export\Feed\Source;

use Avito\Export\Feed;

abstract class FetcherSkeleton implements Fetcher
{
	public function modules() : array
	{
		return [];
	}

	public function order() : int
	{
		return 500;
	}

	public function extend(array $fields, Data\SourceSelect $sources, Context $context) : void
	{
		// nothing by default
	}

	public function select(array $fields) : array
	{
		return [];
	}

	public function filter(array $conditions, Context $context) : array
	{
		return [];
	}

	public function fields(Context $context) : array
	{
		return [];
	}
}