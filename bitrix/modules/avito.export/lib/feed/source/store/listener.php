<?php
namespace Avito\Export\Feed\Source\Store;

use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Routine\ElementChange;
use Bitrix\Main;
use Bitrix\Catalog;

class Listener implements Source\Listener
{
	protected $storeProductCache = [];

	public function onStoreProductUpdate(int $iblockId, ?int $offerIblockId, $amountId, $fields): void
	{
		$productId = $fields['PRODUCT_ID'] ?? $this->storeProductId($amountId);

		if(
			ElementChange::needRegister($productId)
			&& ElementChange::isTargetElement($iblockId, $offerIblockId, (int)$productId)
		)
		{
			ElementChange::register((int)$productId, $iblockId);
		}
	}

	/** @noinspection PhpUnused */
	public function onTableAfterUpdate(int $iblockId, ?int $offerIblockId, Main\Event $event): void
	{
		$this->onStoreProductUpdate(
			$iblockId,
			$offerIblockId,
			$event->getParameter('id'),
			$event->getParameter('fields')
		);
	}

	public function onBeforeStoreProductDelete(int $iblockId, ?int $offerIblockId, $amountId): void
	{
		$productId = $this->storeProductId($amountId);

		if(
			ElementChange::needRegister($productId)
			&& ElementChange::isTargetElement($iblockId, $offerIblockId, (int)$productId)
		)
		{
			ElementChange::register((int)$productId, $iblockId);
		}
	}

	/** @noinspection PhpUnused */
	public function onTableBeforeDelete(int $iblockId, ?int $offerIblockId, Main\Event $event): void
	{
		$this->onBeforeStoreProductDelete(
			$iblockId,
			$offerIblockId,
			$event->getParameter('id')['ID']
		);
	}

	protected function storeProductId($amountId): ?int
	{
		$amountId = (int)$amountId;

		if ($amountId <= 0) { return null; }

		$result = null;

		if (isset($this->storeProductCache[$amountId]) || array_key_exists($amountId, $this->storeProductCache))
		{
			$result = $this->storeProductCache[$amountId];
		}
		else if (Main\Loader::includeModule('catalog'))
		{
			$query = Catalog\StoreProductTable::getList([
				'filter' => [ '=ID' => $amountId ],
				'select' => [ 'PRODUCT_ID' ]
			]);

			if ($row = $query->Fetch())
			{
				$result = (int)$row['PRODUCT_ID'];
			}

			$this->storeProductCache[$amountId] = $result;
		}

		return $result;
	}

	public function handlers(Source\Context $context): array
	{
		return [
			[
				'module' => 'catalog',
				'event' => 'OnStoreProductAdd',
				'method' => 'onStoreProductUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => 'OnStoreProductUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => 'OnBeforeStoreProductDelete',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => '\\Bitrix\\Catalog\\StoreProduct::OnAfterAdd',
				'method' => 'onTableAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => '\\Bitrix\\Catalog\\StoreProduct::OnAfterUpdate',
				'method' => 'onTableAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
			[
				'module' => 'catalog',
				'event' => '\\Bitrix\\Catalog\\StoreProduct::OnBeforeDelete',
				'method' => 'onTableBeforeDelete',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				]
			],
		];
	}
}