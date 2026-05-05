<?php
namespace Avito\Export\Utils;

class DependField
{
	public const RULE_ANY = 'ANY';
	public const RULE_EXCLUDE = 'EXCLUDE';
	public const RULE_EMPTY = 'EMPTY';

	public static function checkDependencyField($rules, $values) : bool
	{
		$logicMatchAny = (isset($rules['LOGIC']) && $rules['LOGIC'] === 'OR');
		$result = !$logicMatchAny;

		foreach ($rules as $fieldName => $rule)
		{
			if ($fieldName === 'LOGIC') { continue; }

			$value = Field::getChainValue($values, $fieldName, Field::GLUE_BRACKET);

			switch ($rule['RULE'])
			{
				case static::RULE_EMPTY:
					$isDependValueEmpty = static::testValueIsEmpty($value);
					$isMatch = ($isDependValueEmpty === $rule['VALUE']);
					break;

				case static::RULE_ANY:
					$isMatch = static::applyRuleAny($rule['VALUE'], $value);
					break;

				case static::RULE_EXCLUDE:
					$isMatch = !static::applyRuleAny($rule['VALUE'], $value);
					break;

				default:
					$isMatch = true;
					break;
			}

			if ($logicMatchAny === $isMatch)
			{
				$result = $isMatch;
				break;
			}
		}

		return $result;
	}

	protected static function testValueIsEmpty($value) : bool
	{
		$result = true;

		if (is_array($value))
		{
			foreach ($value as $one)
			{
				if (!static::testValueIsEmpty($one))
				{
					$result = false;
					break;
				}
			}
		}
		else
		{
			$result = Value::isEmpty($value) || (is_scalar($value) && (string)$value === '0');
		}

		return $result;
	}

	protected static function applyRuleAny($ruleValue, $formValue) : bool
	{
		$isRuleMultiple = is_array($ruleValue);
		$isFormMultiple = is_array($formValue);

		if ($isFormMultiple && $isRuleMultiple)
		{
			$intersect = array_intersect($ruleValue, $formValue);
			$result = !empty($intersect);
		}
		else if ($isFormMultiple)
		{
			/** @noinspection TypeUnsafeArraySearchInspection */
			$result = in_array($ruleValue, $formValue);
		}
		else if ($isRuleMultiple)
		{
			/** @noinspection TypeUnsafeArraySearchInspection */
			$result = in_array($formValue, $ruleValue);
		}
		else
		{
			/** @noinspection TypeUnsafeComparisonInspection */
			$result = ($formValue == $ruleValue);
		}

		return $result;
	}
}