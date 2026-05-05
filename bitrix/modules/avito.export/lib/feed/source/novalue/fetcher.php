<?php
namespace Avito\Export\Feed\Source\NoValue;

use Avito\Export\Feed\Source;

class Fetcher extends Source\FetcherSkeleton
{
	public function listener() : Source\Listener
	{
		return new Listener();
	}

	public function title() : string
	{
		return 'No value';
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		return [];
	}
}