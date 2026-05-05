<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Routine\ElementChange;

class Listener implements Source\Listener
{
	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function onAfterSetPropertyValues(int $iblockId, ?int $offerIblockId, $elementId, $elementIblockId, $propertyValues, $flags): void
	{
		if (
			ElementChange::needRegister($elementId)
			&& ElementChange::isTargetIblock($iblockId, $offerIblockId, (int)$elementIblockId)
		)
		{
			ElementChange::register((int)$elementId, $iblockId);
		}
	}

	public function handlers(Source\Context $context) : array
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementSetPropertyValuesEx',
				'method' => 'onAfterSetPropertyValues',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementSetPropertyValues',
				'method' => 'onAfterSetPropertyValues',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			]
		];
	}
}