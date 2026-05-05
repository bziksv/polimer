<?php
namespace Avito\Export\Feed\Source\Element;

use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Routine\ElementChange;

class Listener implements Source\Listener
{
	/** @noinspection PhpUnused */
	public function onAfterIBlockElementUpdate(int $iblockId, ?int $offerIblockId, $fields) : void
	{
		if (empty($fields['RESULT'])) { return; }

		if (
			ElementChange::needRegister($fields['ID'])
			&& ElementChange::isTargetIblock($iblockId, $offerIblockId, $fields['IBLOCK_ID'])
		)
		{
			ElementChange::register((int)$fields['ID'], $iblockId);
		}
	}

	/** @noinspection PhpUnused */
	public function onAfterIBlockElementDelete(int $iblockId, ?int $offerIblockId, $fields) : void
	{
		if (
			ElementChange::needRegister($fields['ID'])
			&& ElementChange::isTargetIblock($iblockId, $offerIblockId, $fields['IBLOCK_ID'])
		)
		{
			ElementChange::register($fields['ID'], $iblockId);
		}
	}

	public function handlers(Source\Context $context) : array
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'onAfterIBlockElementAdd',
				'method' => 'onAfterIBlockElementUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			],
			[
				'module' => 'iblock',
				'event' => 'onAfterIBlockElementUpdate',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			],
			[
				'module' => 'iblock',
				'event' => 'onAfterIBlockElementDelete',
				'arguments' => [
					$context->iblockId(),
					$context->offerIblockId(),
				],
			]
		];
	}
}