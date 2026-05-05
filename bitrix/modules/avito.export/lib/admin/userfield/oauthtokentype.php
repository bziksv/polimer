<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Api;
use Avito\Export\Concerns;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

class OAuthTokenType extends EnumerationType
{
	use Concerns\HasLocale;

	public static function GetList($userField) : \CDBResult
	{
		$clientId = (string)($userField['ROW']['COMMON_SETTINGS']['CLIENT_ID'] ?? '');
		$result = new \CDBResult();

		if ($clientId === '')
		{
			$result->InitFromArray([]);
			return $result;
		}

		$query = Api\OAuth\TokenTable::getList([
			'filter' => [ '=CLIENT_ID' => $clientId ],
			'select' => [
				'SERVICE_ID',
				'NAME',
			],
		]);

		$result->InitFromArray(ArrayHelper::mapColumns($query->fetchAll(), [
			'SERVICE_ID' => 'ID',
			'NAME' => 'VALUE',
		]));

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		if (!isset($userField['SETTINGS'])) { $userField['SETTINGS'] = []; }

		$userField['SETTINGS'] += [
			'DISPLAY' => 'LIST',
			'ALLOW_NO_VALUE' => 'Y',
			'CAPTION_NO_VALUE' => self::getLocale('PLACEHOLDER'),
		];

		$select = parent::GetEditFormHTML($userField, $htmlControl);
		$inviteMessage = htmlspecialcharsbx(self::getLocale('START_BUTTON'));
		$optionsJson = Json::encode([
			'inviteUrl' => static::inviteUrl(),
		]);

		/** @noinspection SpellCheckingInspection */
		Extension::load('avitoexport.ui.input.oauthtoken');

		/** @noinspection BadExpressionStatementJS */
		return <<<HTML
			<div id="avito_oauth_token">
				{$select}
				<input class="adm-btn js-oauth-token__start" type="button" value="{$inviteMessage}" />
			</div>	
			<script>
				new BX.AvitoExport.Ui.Input.OAuthToken('#avito_oauth_token', {$optionsJson})
			</script>
HTML;
	}

	protected static function inviteUrl() : string
	{
		$request = new Api\OAuth\Invite\Request();
		$request->clientId('CLIENT_ID_HOLDER');

		return $request->fullUrl();
	}
}