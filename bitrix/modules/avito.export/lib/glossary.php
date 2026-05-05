<?php
/** @noinspection PhpUnused */
namespace Avito\Export;

class Glossary
{
	public const SERVICE_FEED = 'feed';
	public const SERVICE_PUSH = 'push';
	public const SERVICE_TRADING = 'trading';
	public const SERVICE_CHAT = 'chat';

	public const ENTITY_OFFER = 'offer';
	public const ENTITY_STOCKS = 'stocks';
	public const ENTITY_STOCKS_LIMIT = 'stocksLimit';
	public const ENTITY_PRICE = 'price';
	public const ENTITY_ORDER = 'order';
	public const ENTITY_AGENT = 'agent';
	public const ENTITY_TOKEN = 'token';
	public const ENTITY_MESSAGE = 'message';
	public const ENTITY_AVITO_ID = 'avitoId';

	/** @deprecated */
	public const FIELD_STOCKS = 'stocks';
}