<?php
namespace Avito\Export\Trading\Service;

class UrlManager
{
	public function orderView(int $orderId) : string
	{
		return 'https://pro.avito.ru/orders/' . urlencode($orderId);
	}

	public function chatUrl(string $chatId) : string
	{
		return 'https://pro.avito.ru/messenger/' . urlencode($chatId);
	}

	public function offerPage(int $avitoId) : string
	{
		return 'https://www.avito.ru/' . urlencode($avitoId);
	}
}