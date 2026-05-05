<?php

namespace Avito\Export\Trading\Entity\SaleCrm;

use Avito\Export\Concerns;
use Avito\Export\Trading\Entity as TradingEntity;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm;

class AdminExtension extends TradingEntity\Sale\AdminExtension
{
	use Concerns\HasLocale;

	/** @noinspection PhpUnused */
	public static function onEntityDetailsTabsInitialized(Main\Event $event) : ?Main\EventResult
	{
		$orderId = static::eventOrderId($event);

		if ($orderId === null) { return null; }

		$tradingLink = static::tradingLink($orderId);

		if ($tradingLink === null) { return null; }

		$tabs = (array)$event->getParameter('tabs');
		$tabs[] = [
			'id' => mb_strtolower(static::TAB_SET_ID . '_VIEW'),
			'name' => parent::getLocale('NAME'),
			'loader' => [
				'serviceUrl' => static::tabUrl($orderId, $tradingLink, [
					'template' => 'bitrix24',
				]),
			],
		];

		return new Main\EventResult(Main\EventResult::SUCCESS, [
			'tabs' => $tabs,
		]);
	}

	protected static function eventOrderId(Main\Event $event) : ?int
	{
		if (!defined('\CCrmOwnerType::Order')) { return null; }

		$typeId = (int)$event->getParameter('entityTypeID');
		$entityId = $event->getParameter('entityID');

		if ($typeId === \CCrmOwnerType::Order)
		{
			$result = (int)$entityId;
		}
		else
		{
			$result = static::searchBindingOrder($typeId, $entityId);
		}

		return $result;
	}

	protected static function searchBindingOrder($typeId, $dealId) : ?int
	{
		if (!defined('ENTITY_CRM_ORDER_ENTITY_BINDING') || $typeId !== \CCrmOwnerType::Deal) { return null; }

		/** @var Crm\Order\EntityBinding $bindingClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
		$bindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);
		$result = null;

		if (
			is_subclass_of($bindingClassName, Crm\Order\EntityBinding::class)
			|| strtolower(Crm\Order\EntityBinding::class) === strtolower(ltrim($bindingClassName, '\\'))
		)
		{
			$filter = [
				'=OWNER_ID' => $dealId,
				'=OWNER_TYPE_ID' => $typeId,
			];
		}
		else
		{
			$filter = [
				'=DEAL_ID' => $dealId
			];
		}

		$query = $bindingClassName::getList([
			'filter' => $filter,
			'select' => [ 'ORDER_ID' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ORDER_ID'];
		}

		return $result;
	}

	public function handlers() : array
	{
		return [
			[
				'module' => 'crm',
				'event' => 'onEntityDetailsTabsInitialized',
			],
		];
	}
}
