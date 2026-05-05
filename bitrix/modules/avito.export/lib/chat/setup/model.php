<?php
namespace Avito\Export\Chat\Setup;

use Avito\Export\Config;
use Bitrix\Main;
use Avito\Export\Api;
use Avito\Export\Exchange;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Data;
use Avito\Export\Psr;

class Model
{
	protected $exchange;
	protected $settings;

	public static function getById(int $exchangeId) : Model
	{
		return Exchange\Setup\Model::getById($exchangeId)->getChat();
	}

	public function __construct(Exchange\Setup\Model $exchange, Settings $settings)
	{
		$this->exchange = $exchange;
		$this->settings = $settings;
	}

	public function getId() : ?int
	{
		return $this->exchange->getId();
	}

	public function getExchange() : Exchange\Setup\Model
	{
		return $this->exchange;
	}

	public function getSettings() : Settings
	{
		return $this->settings;
	}

	public function makeLogger() : Psr\Logger\LoggerInterface
	{
		$result = new Logger\Logger(Glossary::SERVICE_CHAT, $this->getId());
		$result->allowTouch();

		return $result;
	}

	public function activate() : void
	{
		Main\Config\Option::set('avito.export', 'enable_chat', 'Y');
		$this->installWebhook();
	}

	public function deactivate() : void
	{
		$this->disableChat();
		$this->uninstallWebhook();
	}

	protected function disableChat() : void
	{
		$enable = false;

		$query = Exchange\Setup\RepositoryTable::getList([
			'filter' => [
				'USE_CHAT' => true,
				'!=ID' => $this->exchange->getId(),
			]
		]);

		if ($query->fetch()) { $enable = true; }

		if (!$enable)
		{
			Main\Config\Option::set('avito.export', 'enable_chat', 'N');
		}
	}

	protected function installWebhook() : void
	{
		$callbackUrl = $this->webhookUrl();
		$storedUrl = $this->storedWebhookUrl();
		$token = $this->settings->commonSettings()->token();

		if (DevelopmentBlocker::isTarget())
		{
			$request = Main\Application::getInstance()->getContext()->getRequest();
			$host = $request->getHttpHost();
			$host = Data\Url::normalizeHost($host);

			if (!Data\SiteDomain::isKnown($host)) { return; }

			$callbackUrl = Data\Url::replaceHost($callbackUrl, $host, $request->isHttps());

			if ($storedUrl !== null && Data\Url::similar($callbackUrl, $storedUrl))
			{
				Api\Messenger\WebhookFacade::unsubscribe($storedUrl, $token);
			}

			DevelopmentBlocker::install($this->getId());
		}
		else if ($storedUrl !== null && $callbackUrl !== $storedUrl)
		{
			Api\Messenger\WebhookFacade::unsubscribe($storedUrl, $token);
		}

		if (Api\Messenger\WebhookFacade::isSubscribed($callbackUrl, $token)) { return; }

		Api\Messenger\WebhookFacade::subscribe($callbackUrl, $token);
        $this->saveWebhookUrl($callbackUrl);
	}

	protected function webhookUrl() : string
	{
		$domain = $this->exchange->fillFeed()->getPrimaryDomain() ?? Data\SiteDomain::defaultUrl();

		return rtrim($domain, '/') . '/bitrix/tools/avito.export/chat/callback.php?setupId=' . $this->getId();
	}

	protected function saveWebhookUrl(string $url) : void
	{
		if ($this->storedWebhookUrl() !== $url)
		{
			Config::setOption($this->webhookOptionName(), $url);
		}
	}

	protected function storedWebhookUrl() : ?string
	{
		$name = $this->webhookOptionName();
		$stored = (string)Config::getOption($name);

		return ($stored !== '' ? $stored : null);
	}

	protected function releaseWebhookUrl() : void
	{
		$name = $this->webhookOptionName();

		Config::removeOption($name);
	}

	protected function webhookOptionName() : string
	{
		return 'chat_webhook_url_' . $this->getId();
	}

	public function uninstallWebhook() : void
	{
		$storedUrl = $this->storedWebhookUrl();

		if ($storedUrl === null) { return; }

		Api\Messenger\WebhookFacade::unsubscribe($storedUrl, $this->settings->commonSettings()->token());
		$this->releaseWebhookUrl();
		DevelopmentBlocker::uninstall($this->getId());
	}
}
