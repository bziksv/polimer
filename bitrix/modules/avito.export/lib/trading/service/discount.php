<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Trading\Service;

use Avito\Export\Concerns;

class Discount
{
	use Concerns\HasLocale;

	public const TYPE_PROMOCODE = 'promocode';

	protected $service;

	public function __construct(Container $container)
	{
		$this->service = $container;
	}

	public function typeTitle(string $type) : string
	{
		return self::getLocale('TYPE_' . mb_strtoupper($type), null, $type);
	}
}