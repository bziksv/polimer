<?php
namespace Avito\Export\Feed\Source\OfferProperty;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Context;

class Fetcher extends Source\ElementProperty\Fetcher
	implements Source\FetcherMergable
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		return [
			'OFFER' => $this->makeFilter($conditions, $this->contextIblockId($context)),
		];
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		if (!$context->hasOffers()) { return []; }

		$offers = Source\Routine\Values::offerElements($elements);

		return $this->propertyValues(
			$context->offerIblockId(),
			array_values(array_column($offers, 'ID', 'ID')),
			$select
		);
	}

	protected function contextIblockId(Context $context) : ?int
	{
		return $context->offerIblockId();
	}

	public function merge(array $sourceValues, array $mergeMap) : array
	{
		return Source\Routine\ValueMerger::merge($sourceValues, $mergeMap);
	}
}