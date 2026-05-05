<?php
namespace Avito\Export\Exchange\Setup;

use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Push;
use Avito\Export\Trading;
use Bitrix\Main;

class Settings extends SettingsSkeleton
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function token() : Api\OAuth\Token
	{
		return $this->once('token', function() {
			$query = Api\OAuth\TokenTable::getByPrimary([
				'CLIENT_ID' => $this->oauthClientId(),
				'SERVICE_ID' => (int)$this->requireValue('OAUTH_TOKEN'),
			]);
			$token = $query->fetchObject();

			Assert::notNull($token, 'token');

			return $token;
		});
	}

	public function oauthClientId() : string
	{
		return (string)$this->requireValue('CLIENT_ID');
	}

	public function oauthClientSecret() : string
	{
		return (string)$this->requireValue('CLIENT_PASSWORD');
	}

	public function fields() : array
	{
		return $this->oauthFields();
	}

	/** @noinspection PhpCastIsUnnecessaryInspection */
	protected function oauthFields() : array
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$callbackUrl = 'https://' . $this->normalizeHost((string)$request->getHttpHost()) . '/bitrix/tools/avito.export/oauth/callback.php';

		return [
			'CLIENT_ID' => [
				'TYPE' => 'string',
				'MANDATORY' => 'Y',
				'NAME' => self::getLocale('CLIENT_ID'),
				'GROUP' => self::getLocale('GROUP_OAUTH'),
				'GROUP_DESCRIPTION' => self::getLocale('GROUP_OAUTH_INTRO', [
					'#SITE_NAME#' => htmlspecialcharsbx(Main\Config\Option::get('main', 'site_name', $request->getHttpHost())),
					'#CALLBACK_URL#' => htmlspecialcharsbx($callbackUrl),
				]),
				'INTRO' => self::getLocale('CLIENT_ID_INTRO'),
			],
			'CLIENT_PASSWORD' => [
				'TYPE' => 'string',
				'MANDATORY' => 'Y',
				'NAME' => self::getLocale('CLIENT_PASSWORD'),
				'INTRO' => self::getLocale('CLIENT_PASSWORD_INTRO'),
			],
			'OAUTH_TOKEN' => [
				'TYPE' => 'oauthToken',
				'MANDATORY' => 'Y',
				'NAME' => self::getLocale('OAUTH_TOKEN'),
				'INTRO' => self::getLocale('OAUTH_TOKEN_INTRO'),
			],
		];
	}

	protected function normalizeHost(string $host) : string
	{
		return preg_replace('/:\d+$/', '', $host);
	}
}