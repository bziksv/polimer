<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class FileDownload extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function show()
	{
		$url = $this->getUrl();
		$setup = $this->getSetup();
		$options = $setup->wakeupService()->getOptions();

		list($contentType, $contents) = Market\Api\Partner\File\Facade::download($options, $url);

		$this->submitFile($contentType, $contents);
	}

	protected function getUrl()
	{
		$url = (string)$this->request->get('url');

		if ($url === '')
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_URL_NOT_DEFINED');
			throw new Main\SystemException($message);
		}

		$uri = new Main\Web\Uri($url);
		$allowedHosts = [
			Market\Api\Glossary::MARKET_API_HOST,
		];

		if (
			$uri->getScheme() !== 'https'
			|| !in_array(mb_strtolower((string)$uri->getHost()), $allowedHosts, true)
		)
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_URL_NOT_ALLOWED');
			throw new Main\SystemException($message);
		}

		return $uri->getUri();
	}

	protected function getSetup()
	{
		$setupId = $this->getSetupId();
		$setup = Market\Trading\Setup\Model::loadById($setupId);

		if (!$setup->isActive())
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_SETUP_INACTIVE');
			throw new Main\SystemException($message);
		}

		return $setup;
	}

	protected function getSetupId()
	{
		$setupId = (int)$this->request->get('setup');

		if ($setupId <= 0)
		{
			$message = static::getLang('UI_TRADING_FILE_DOWNLOAD_SETUP_ID_NOT_DEFINED');
			throw new Main\SystemException($message);
		}

		return $setupId;
	}

	protected function submitFile($type, $contents)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		while (ob_get_level()) { ob_end_clean(); }
		header('Content-type: ' . $this->sanitizeContentType($type));
		// Содержимое приходит из внешнего API: отдаём его как вложение и запрещаем
		// браузеру угадывать тип, чтобы ответ ни при каком Content-Type не отрендерился
		// как HTML в origin магазина.
		header('Content-Disposition: attachment');
		header('X-Content-Type-Options: nosniff');
		echo $contents;
		die();
	}

	/**
	 * Content-Type из ответа внешнего API не должен попадать в заголовок как есть.
	 *
	 * @param string $type
	 * @return string
	 */
	protected function sanitizeContentType($type)
	{
		$type = trim((string)$type);
		$type = (string)strtok($type, ';'); // отбрасываем параметры вида charset
		$type = trim($type);

		if ($type === '' || !preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#i', $type))
		{
			return 'application/octet-stream';
		}

		return $type;
	}
}