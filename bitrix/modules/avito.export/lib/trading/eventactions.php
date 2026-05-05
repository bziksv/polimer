<?php
namespace Avito\Export\Trading;

class EventActions
{
	public const ORDER_BEFORE_SAVE = 'onBeforeOrderSave';
	public const ORDER_AFTER_SAVE = 'onAfterOrderSave';
	public const ORDER_UNKNOWN_ITEMS = 'onOrderUnknownItems';
}