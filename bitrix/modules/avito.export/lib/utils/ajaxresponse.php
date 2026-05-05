<?php

namespace Avito\Export\Utils;

use Bitrix\Main;

class AjaxResponse
{
	public static function sendJson(array $data) : void
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		/** @var Main\Application $application */
		$application = Main\Application::getInstance();
		$jsonResponse = new Main\Engine\Response\Json($data);
		$currentResponse = Main\Application::getInstance()->getContext()->getResponse();

		$jsonResponse = static::copyHeaders($currentResponse, $jsonResponse);

		$application->end(0, $jsonResponse);
	}

	protected static function copyHeaders(Main\HttpResponse $from, Main\HttpResponse $to) : Main\HttpResponse
	{
		if (method_exists($from, 'copyHeadersTo'))
		{
			return $from->copyHeadersTo($to);
		}

		$httpHeaders = $to->getHeaders();
		$status = $to->getStatus();
		$previousStatus = $from->getStatus();
		$ignored = [
			'content-encoding',
			'content-length',
			'content-type',
		];

		foreach ($from->getHeaders() as $headerName => $values)
		{
			$nameLower = mb_strtolower($headerName);

			if (in_array($nameLower, $ignored, true)) { continue; }

			if ($status && $headerName === $previousStatus) { continue; }

			if ($httpHeaders->get($headerName)) { continue; }

			$httpHeaders->add($headerName, $values);
		}

		foreach ($from->getCookies() as $cookie)
		{
			$to->addCookie($cookie, false);
		}

		return $to;
	}
}