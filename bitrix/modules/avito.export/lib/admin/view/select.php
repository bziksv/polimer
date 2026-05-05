<?php
namespace Avito\Export\Admin\View;

use Avito\Export\Concerns;

class Select
{
	use Concerns\HasLocale;

	/** @noinspection HtmlUnknownAttribute */
	public static function edit(array $variants, $selected = null, array $attributes = [], array $settings = []) : string
	{
		$canBeEmpty = $settings['ALLOW_NO_VALUE'] ?? false;
		$result = sprintf('<select %s>', Attributes::stringify($attributes));
		$lastGroup = null;

		if ($canBeEmpty)
		{
			$result .= sprintf('<option value="">%s</option>', $settings['CAPTION_NO_VALUE'] ?? self::getLocale('NO_VALUE'));
		}

		foreach ($variants as $index => $variant)
		{
			if (isset($variant['GROUP']) && $variant['GROUP'] !== $lastGroup)
			{
				if ($lastGroup !== null) { $result .= '</optgroup>'; }

				$result .= sprintf('<optgroup label="%s">', htmlspecialcharsbx($variant['GROUP']));
				$lastGroup = $variant['GROUP'];
			}

			$variant = static::sanitizeVariant($variant);
			/** @noinspection TypeUnsafeArraySearchInspection */
			$isSelected = (is_array($selected) ? in_array($variant['ID'], $selected) : (string)$selected === (string)$variant['ID']);
			$attributes = [];

			if ($isSelected)
			{
				$attributes[] = 'selected';
			}

			if (!empty($variant['DISABLED']))
			{
				$attributes[] = 'disabled';
			}

			if ($variant['DEPTH'] === 0 && $index > 0)
			{
				$result .= '<option disabled>---------------------------</option>';
			}

			$result .= sprintf(
				'<option value="%s" %s>%s</option>',
				htmlspecialcharsbx($variant['ID']),
				implode(' ', $attributes),
				htmlspecialcharsbx($variant['VALUE'])
			);
		}

		if ($lastGroup !== null) { $result .= '</optgroup>'; }

		$result .= '</select>';

		return $result;
	}

	protected static function sanitizeVariant($variant) : array
	{
		if (!is_array($variant))
		{
			return  [
				'ID' => $variant,
				'VALUE' => $variant,
			];
		}

		if (!isset($variant['ID']))
		{
			$variant['ID'] = $variant['VALUE'];
		}

		if (!isset($variant['VALUE']))
		{
			$variant['VALUE'] = $variant['ID'];
		}

		return $variant;
	}
}