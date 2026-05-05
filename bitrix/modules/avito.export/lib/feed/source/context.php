<?php
namespace Avito\Export\Feed\Source;

use Bitrix\Main;
use Avito\Export\Concerns;

class Context
{
	use Concerns\HasOnce;

	protected $iblockId;
	protected $siteId;
	protected $regionIblockId;
	protected $variables = [];

	public function __construct(int $iblockId, string $siteId = null, int $regionIblockId = null)
	{
		$this->iblockId = $iblockId;
		$this->siteId = $siteId;
		$this->regionIblockId = $regionIblockId > 0 ? $regionIblockId : null;
	}

	public function regionIblockId() : ?int
	{
		return $this->regionIblockId;
	}

	public function extend(array $variables) : void
	{
		$this->variables = $variables + $this->variables;
	}

	public function variable(string $name)
	{
		return $this->variables[$name] ?? null;
	}

	public function iblockId() : int
	{
		return $this->iblockId;
	}

	public function siteId() : ?string
	{
		return $this->siteId;
	}

	public function hasOffers() : bool
	{
		return $this->offerIblockId() > 0;
	}

	public function hasCatalog() : bool
	{
		return $this->catalogInfo() !== null;
	}

	public function offerIblockId() : ?int
	{
		$catalogInfo = $this->catalogInfo();
		$iblockId = (int)($catalogInfo['IBLOCK_ID'] ?? 0);

		return $iblockId > 0 && $iblockId !== $this->iblockId ? $iblockId : null;
	}

	public function offerPropertyId() : ?int
	{
		$catalogInfo = $this->catalogInfo();
		$propertyId = (int)($catalogInfo['SKU_PROPERTY_ID'] ?? 0);

		return $propertyId > 0 ? $propertyId : null;
	}

	public function productIblockId() : ?int
	{
		$catalogInfo = $this->catalogInfo();

		if (empty($catalogInfo['PRODUCT_IBLOCK_ID'])) { return null; }

		return $catalogInfo['CATALOG_TYPE'] === \CCatalogSku::TYPE_OFFERS ? (int)$catalogInfo['PRODUCT_IBLOCK_ID'] : null;
	}

	protected function catalogInfo() : ?array
	{
		return $this->once('catalogInfo', function() {
			if (!Main\Loader::includeModule('catalog')) { return null; }

			$catalog = \CCatalogSku::GetInfoByIBlock($this->iblockId);

			if ($catalog === false) { return null; }

			return $catalog;
		});
	}
}