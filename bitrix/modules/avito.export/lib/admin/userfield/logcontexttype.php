<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

class LogContextType extends StringType
{
	protected static $viewCounter = 0;

	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = Helper\ComplexValue::asSingle($userField, $additionalParameters);

		if (!is_array($value))
		{
			$result = htmlspecialcharsbx((string)$value);
		}
		else if (isset($value['TRACE']))
		{
			$result = static::traceViewHtml((string)$value['TRACE']);
		}
		else
		{
			$result = static::arrayViewHtml($value);
		}

		return $result;
	}

	protected static function traceViewHtml(string $trace) : string
	{
		$preview = strtok($trace, PHP_EOL) ?: 'unknown';

		return static::modalLinkHtml($preview, $trace);
	}

	protected static function arrayViewHtml(array $array) : string
	{
		if (empty($array)) { return ''; }

		reset($array);

		return static::modalLinkHtml((string)key($array), print_r($array, true));
	}

	protected static function modalLinkHtml(string $preview, string $content) : string
	{
		/** @noinspection JSUnresolvedReference */
		return sprintf(<<<'TEXT'
			<a href="#" onclick="(new BX.CAdminDialog({ content: BX('%1$s'), width: 800, height: 700 })).Show(); return false;">%2$s</a>
			<div hidden style="display: none;">
				<pre id="%1$s" style="padding: 20px; background: #fff; border: 1px solid #ddd;">%3$s</pre>
			</div>
TEXT
			,
			'avito-log-context-' . ++static::$viewCounter,
			htmlspecialcharsbx($preview),
			htmlspecialcharsbx($content)
		);
	}
}
