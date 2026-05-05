<?php
namespace Avito\Export\Feed\Source\Product;

use Bitrix\Main;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Routine\ElementChange;

class Listener implements Source\Listener
{
	public function onAfterUpdate(int $iblockId, ?int $offerIblockId, Main\Event $event) : void
	{
		$elementId = $event->getParameter('id');

		if (!Source\Routine\ElementChange::needRegister($elementId)) { return; }

		$externalData = $event->getParameter('external_fields');
		$elementIblockId = $externalData['IBLOCK_ID'] ?? null;
		$isTarget = $elementIblockId !== null
			? ElementChange::isTargetIblock($iblockId, $offerIblockId, $elementIblockId)
			: ElementChange::isTargetElement($iblockId, $offerIblockId, $elementId);

		if ($isTarget)
		{
			Source\Routine\ElementChange::register($elementId, $iblockId);
		}
	}

	public function handlers(Source\Context $context) : array
	{
		return [
			[
				'module' => 'catalog',
				'event' => 'Bitrix\\Catalog\\Model\\Product::OnAfterAdd',
				'method' => 'onAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			],
			[
				'module' => 'catalog',
				'event' => 'Bitrix\\Catalog\\Model\\Product::OnAfterUpdate',
				'method' => 'onAfterUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			]
		];
	}
}