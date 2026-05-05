<?php

namespace Avito\Export\Admin\UserField;

use Bitrix\Main;
use Avito\Export\Utils\Field;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Trading\Entity as TradingEntity;

class BuyerProfileType extends EnumerationType
{
	use Concerns\HasLocale;

	public static function GetList($userField) : \CDBResult
	{
		$userId = static::userFieldUserId($userField);
		$personType = static::userFieldPersonType($userField);
		$variants = static::variants($userId, $personType);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		if (!isset($userField['SETTINGS']['CAPTION_NO_VALUE']))
		{
			$userField['SETTINGS']['CAPTION_NO_VALUE'] = self::getLocale('CAPTION_NO_VALUE');
		}

		$result = parent::GetEditFormHTML($userField, $htmlControl);
		$result .= ' ';
		$result .= static::editButton($userField, $htmlControl['NAME']);

		return $result;
	}

	protected static function editButton($userField, $name) : string
	{
		$htmlId = Helper\Attributes::nameToId($name) . '_REFRESH';

		return
			static::editButtonHtml($htmlId)
			. static::editButtonScript($userField, $htmlId);
	}

	protected static function editButtonHtml(string $htmlId) : string
	{
		return sprintf(
			'<input type="button" value="%s" id="%s" />',
			htmlspecialcharsbx(self::getLocale('EDIT')),
			htmlspecialcharsbx($htmlId)
		);
	}

	protected static function editButtonScript(array $userField, string $htmlId) : string
	{
		/** @noinspection SpellCheckingInspection */
		Main\UI\Extension::load('avitoexport.admin.buyerprofile');

		/** @noinspection BadExpressionStatementJS */
		return sprintf(<<<SCRIPT
			<script>
				new BX.AvitoExport.Admin.BuyerProfile('#%s', %s)
			</script>
SCRIPT
			,
			$htmlId,
			Main\Web\Json::encode([
				'editUrl' => static::editUrl(),
				'refreshUrl' => static::refreshUrl(),
				'personTypeId' => static::userFieldPersonType($userField),
				'personTypeElement' => sprintf('select[name="%s"]', static::userFieldPersonTypeField($userField)),
				'userId' => static::userFieldUserId($userField),
			])
		);
	}

	protected static function refreshUrl() : string
	{
		return Admin\Path::toolsUrl('buyerProfile/enum', [
			'lang' => LANGUAGE_ID,
		]);
	}

	protected static function editUrl() : string
	{
		return Admin\Path::toolsUrl('buyerProfile/edit', [
			'lang' => LANGUAGE_ID,
		]);
	}

	protected static function userFieldUserId($userField) : ?int
	{
		return isset($userField['SETTINGS']['USER_ID']) ? (int)$userField['SETTINGS']['USER_ID'] : null;
	}

	protected static function userFieldPersonType(array $userField)
	{
		$fieldPersonType = static::userFieldPersonTypeField($userField);
		$rowValue = isset($fieldPersonType, $userField['ROW'])
			? Field::getChainValue($userField['ROW'], $fieldPersonType, Field::GLUE_BRACKET)
			: null;

		return $rowValue ?? $userField['SETTINGS']['PERSON_TYPE_DEFAULT'] ?? null;
	}

	protected static function userFieldPersonTypeField($userField) : ?string
	{
		return $userField['SETTINGS']['PERSON_TYPE_FIELD'] ?? null;
	}

	public static function variants(?int $userId, int $personType) : array
	{
		if ($userId === null) { return []; }

		$environment = TradingEntity\Registry::environment();

		return $environment->buyerProfile()->variants($userId, $personType);
	}
}