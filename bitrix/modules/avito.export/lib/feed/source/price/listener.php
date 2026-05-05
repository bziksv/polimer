<?php
namespace Avito\Export\Feed\Source\Price;

use Bitrix\Main;
use Bitrix\Catalog;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Routine\ElementChange;

class Listener implements Source\Listener
{
	protected $priceProductCache = [];

	/** @noinspection PhpUnused */
	public function onPriceAfterUpdate(int $iblockId, ?int $offerIblockId, Main\Event $event) : void
	{
		$fields = $event->getParameter('fields');
		$productId = $fields['PRODUCT_ID'] ?? $this->priceProductId($event->getParameter('id'));

		if(
			ElementChange::needRegister($productId)
			&& ElementChange::isTargetElement($iblockId, $offerIblockId, $productId)
		)
		{
			ElementChange::register($productId, $iblockId);
		}
	}

	/** @noinspection PhpUnused */
	public function onPriceDelete($iblockId, $offerIblockId, Main\Event $event) : void
	{
		$productId = $this->priceProductId($event->getParameter('id'));

		if(
			ElementChange::needRegister($productId)
			&& ElementChange::isTargetElement($iblockId, $offerIblockId, $productId)
		)
		{
			ElementChange::register($productId, $iblockId);
		}
	}

	protected function priceProductId($priceId) : ?int
	{
		$priceId = (int)$priceId;

		if ($priceId <= 0) { return null; }

		$result = null;

		if (isset($this->priceProductCache[$priceId]) || array_key_exists($priceId, $this->priceProductCache))
		{
			$result = $this->priceProductCache[$priceId];
		}
		else if (Main\Loader::includeModule('catalog'))
		{
			$query = Catalog\PriceTable::getList([
				'filter' => [ '=ID' => $priceId ],
				'select' => [ 'PRODUCT_ID' ],
			]);

			if ($row = $query->fetch())
			{
				$result = (int)$row['PRODUCT_ID'];
			}

			$this->priceProductCache[$priceId] = $result;
		}

		return $result;
	}

	public function handlers(Source\Context $context) : array
	{
		return [
			[
				'module' => 'catalog',
				'event' => 'Bitrix\\Catalog\\Model\\Price::OnAfterAdd',
				'method' => 'onPriceAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => 'Bitrix\\Catalog\\Model\\Price::OnAfterUpdate',
				'method' => 'onPriceAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => 'Bitrix\\Catalog\\Model\\Price::OnDelete',
				'method' => 'onPriceDelete',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
		];
	}
}