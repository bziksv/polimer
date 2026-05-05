<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

class PrimaryType extends StringType
{
	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$result = parent::getAdminListViewHtml($userField, $additionalParameters);
		$result = static::modifyUrlFieldValue($result, $userField);

		return $result;
	}

	/** @noinspection HtmlUnknownTarget */
	protected static function modifyUrlFieldValue(string $value, ?array $userField) : string
	{
		if (empty($userField['SETTINGS']['URL_FIELD'])) { return $value; }

		$urlField = (string)$userField['SETTINGS']['URL_FIELD'];
		$url = (string)($userField['ROW'][$urlField] ?? '');

		if ($url === '') { return $value; }

		return sprintf(
			'<a href="%s">%s</a>',
			htmlspecialcharsbx($url),
			$value
		);
	}
}