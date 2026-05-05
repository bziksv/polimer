<?php

namespace Avito\Export\Admin\UserField;

use Bitrix\Main;
use Avito\Export\Utils\Field;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Trading\Entity as TradingEntity;

class OrderPropertyType extends EnumerationType
{
	use Concerns\HasLocale;

	public static function GetList($userField) : \CDBResult
	{
		$personType = static::userFieldPersonType($userField);
		$variants = static::variants($personType);
		$variants = static::markDefaultVariant($userField, $variants);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		$htmlId = Helper\Attributes::nameToId($htmlControl['NAME']);
		$userField = array_replace_recursive($userField, [
			'SETTINGS' => [
				'ATTRIBUTES' => [ 'id' => $htmlId ],
			],
		]);

		$result = parent::GetEditFormHTML($userField, $htmlControl);
		$result .= static::reloadScript($userField, $htmlId);

		return $result;
	}

	protected static function reloadScript(array $userField, string $htmlId) : string
	{
		/** @noinspection SpellCheckingInspection */
		Main\UI\Extension::load('avitoexport.admin.orderproperty');

		/** @noinspection BadExpressionStatementJS */
		return sprintf(<<<SCRIPT
			<script>
				new BX.AvitoExport.Admin.OrderProperty('#%s', %s)
			</script>
SCRIPT
			,
			$htmlId,
			Main\Web\Json::encode([
				'refreshUrl' => static::refreshUrl(),
				'personTypeId' => static::userFieldPersonType($userField),
				'personTypeElement' => sprintf('select[name="%s"]', static::userFieldPersonTypeField($userField)),
			])
		);
	}

	protected static function refreshUrl() : string
	{
		return Admin\Path::toolsUrl('orderProperty/enum', [
			'lang' => LANGUAGE_ID,
		]);
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

	public static function variants(?int $personTypeId) : array
	{
		$environment = TradingEntity\Registry::environment();

		return $environment->property()->variants($personTypeId);
	}

    protected static function markDefaultVariant(array $userField, array $variants) : array
    {
        $userFieldCode = $userField['CODE'];
		$userFieldMatched = $userField['SETTINGS']['DEFAULT_VALUES_MAP'] ?? [];
		$foundDefault = false;
		$matchedDefault = null;

        foreach ($variants as $variantKey => &$variant)
        {
            if ($variant['CODE'] === $userFieldCode)
            {
                $variant['DEF'] = 'Y';
	            $foundDefault = true;
                break;
            }

			if ($matchedDefault === null && in_array($variant['CODE'], $userFieldMatched, true))
			{
				$matchedDefault = $variantKey;
			}
        }
        unset($variant);

		if (!$foundDefault && $matchedDefault !== null)
		{
			$variants[$matchedDefault]['DEF'] = 'Y';
		}

        return $variants;
    }
}