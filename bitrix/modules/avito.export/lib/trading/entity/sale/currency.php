<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Currency as BitrixCurrency;
use Bitrix\Main;

class Currency
{
	use Concerns\HasLocale;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function id() : string
	{
		$variants = [ 'RUB', 'RUR' ];
		$result = null;

		foreach ($variants as $variant)
		{
			if (BitrixCurrency\CurrencyManager::isCurrencyExist($variant))
			{
				$result = $variant;
				break;
			}
		}

		if ($result === null)
		{
			throw new Main\SystemException(self::getLocale('ID_NOT_FOUND', [
				'#SUPPORTED#' => implode(' ' . self::getLocale('OR') . ' ', $variants),
			]));
		}

		return $result;
	}

	public function format(float $value) : string
	{
		return (string)\CCurrencyLang::CurrencyFormat($value, $this->id());
	}
}