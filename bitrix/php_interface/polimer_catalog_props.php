<?php
/**
 * Публичные характеристики каталога: скрываем служебные/маркетплейс-свойства.
 * Используется в карточке товара и на странице сравнения.
 */

if (!function_exists('polimerGetHiddenCatalogPropCodes'))
{
	function polimerGetHiddenCatalogPropCodes()
	{
		static $codes = null;
		if ($codes !== null)
		{
			return $codes;
		}

		$codes = array(
			// Avito / Ozon / маркетплейсы / служебные выгрузки
			'_1_AVITO',
			'_1_OZON_NAZVANIE',
			'_1_VYGRUZKA_MARKETY',
			'_2',
			'_2_OZON_SKLAD',
			'_4_TEST',
			'_4_TIP_OZON',
			'ALT_N_NASOS_DRENAZHNYY_ALT_N_250A_KORPUS_IZ_NERZHA', // BoilerType
			'AVITO_AIRCONDITIONERSUBTYPE',
			'AVITO_AIRCONDITIONERTYPE',
			'AVITO_CARTRIDGETYPE',
			'AVITO_DEVICETYPE',
			'AVITO_GATEAUTOMATIONBRAND',
			'AVITO_GATEAUTOMATIONGATECONSTRUCTION',
			'AVITO_GATEAUTOMATIONTYPE',
			'AVITO_GOODSSUBTYPE',
			'AVITO_HEATINGSUBTYPE',
			'AVITO_LENGTH_DLINA_V_SM',
			'AVITO_LUMBERHEIGHT',
			'AVITO_LUMBERLENGTH',
			'AVITO_LUMBERTYPE',
			'AVITO_LUMBERWIDTH',
			'AVITO_MATERIALSUBTYPE',
			'AVITO_MATERIALTYPE',
			'AVITO_MODEL',
			'AVITO_MODES',
			'AVITO_POWERTYPE',
			'AVITO_PRODUCTTYPE',
			'AVITO_THICKNESS_TOLSHCHINA_V_MM',
			'AVITO_TOOLSUBTYPE',
			'AVITO_WIDTH_SHIRINA_V_SM',
			'CHECK_SIZE',
			'HEIGHT_MARKETPLACE',
			'LONG_MARKETPLACE',
			'MATERIAL_KORPUSA', // Avito_ProductSubType
			'MATERIAL_OZON',
			'MEZHOSEVOE_RASSTOYANIE', // ЭХК. Авито название
			'MODEL', // Avito_PriceType
			'MOUNTINGTYPE',
			'NAIMENOVANIE_SAYT01',
			'NAIMENOVANIE_SAYT02',
			'NAIMENOVANIE_SAYT03',
			'OZ_ARTICLE',
			'OZ_AUTOSALES',
			'OZ_EXPORT',
			'OZ_SKU_S1',
			'PODTIP_TOVARA', // -----
			'WIDTH_MARKETPLACE',
			'YANDEKS_MARKET_PREDOPLATA',
			// Служебные / медиа / форум
			'MORE_PHOTO',
			'FILES',
			'BLOG_POST_ID',
			'BLOG_COMMENTS_CNT',
			'FORUM_MESSAGE_CNT',
			'FORUM_TOPIC_ID',
			'SALE',
			'STATAUS',
			'CML2_TRAITS',
			'CML2_TAXES',
			'CML2_ATTRIBUTES',
			'CML2_BASE_UNIT',
		);

		return $codes;
	}
}

if (!function_exists('polimerIsHiddenCatalogProp'))
{
	/**
	 * @param string $code
	 * @param string $name
	 * @return bool
	 */
	function polimerIsHiddenCatalogProp($code, $name = '')
	{
		$code = (string)$code;
		$name = trim((string)$name);

		if ($code !== '' && in_array($code, polimerGetHiddenCatalogPropCodes(), true))
		{
			return true;
		}

		if ($code !== '' && (
			stripos($code, 'AVITO_') === 0
			|| stripos($code, 'OZ_') === 0
			|| stripos($code, 'OZON') !== false
			|| stripos($code, 'MARKETPLACE') !== false
			|| stripos($code, 'VYGRUZKA') !== false
			|| stripos($code, 'NAIMENOVANIE_SAYT') === 0
		))
		{
			return true;
		}

		if ($name === '' || $name === '.' || $name === '-----')
		{
			return $name !== '';
		}

		if (preg_match('/^Avito_/iu', $name)
			|| preg_match('/авито/iu', $name)
			|| preg_match('/озон/iu', $name)
			|| preg_match('/маркетплейс/iu', $name)
			|| preg_match('/яндекс\.?\s*маркет/iu', $name)
			|| preg_match('/выгрузка/iu', $name)
			|| preg_match('/служебн/iu', $name)
			|| preg_match('/системн/iu', $name)
			|| preg_match('/наименование_сайт/iu', $name)
			|| strcasecmp($name, 'BoilerType') === 0
			|| strcasecmp($name, 'MountingType') === 0
		)
		{
			return true;
		}

		return false;
	}
}

if (!function_exists('polimerPropHasPublicValue'))
{
	/**
	 * @param mixed $value
	 * @return bool
	 */
	function polimerPropHasPublicValue($value)
	{
		if ($value === null || $value === false || $value === '')
		{
			return false;
		}
		if (is_array($value))
		{
			foreach ($value as $item)
			{
				if ($item !== null && $item !== false && $item !== '' && $item !== array())
				{
					return true;
				}
			}
			return false;
		}
		return true;
	}
}

if (!function_exists('polimerFormatPublicPropValue'))
{
	/**
	 * @param array $prop
	 * @return string
	 */
	function polimerFormatPublicPropValue(array $prop)
	{
		if (isset($prop['DISPLAY_VALUE']) && polimerPropHasPublicValue($prop['DISPLAY_VALUE']))
		{
			$value = $prop['DISPLAY_VALUE'];
			if (is_array($value))
			{
				$value = implode(', ', $value);
			}
			return html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		$value = $prop['VALUE'] ?? '';
		if (is_array($value))
		{
			$parts = array();
			foreach ($value as $item)
			{
				if ($item === null || $item === false || $item === '')
				{
					continue;
				}
				$parts[] = is_array($item) ? implode(', ', $item) : (string)$item;
			}
			return implode(', ', $parts);
		}

		return (string)$value;
	}
}

if (!function_exists('polimerFilterPublicCatalogProps'))
{
	/**
	 * Оставляет только заполненные публичные свойства.
	 *
	 * @param array $props PROPERTIES / SHOW_PROPERTIES
	 * @return array
	 */
	function polimerFilterPublicCatalogProps(array $props)
	{
		$result = array();
		foreach ($props as $code => $prop)
		{
			$propCode = (string)($prop['CODE'] ?? $code);
			$propName = (string)($prop['NAME'] ?? '');
			if (polimerIsHiddenCatalogProp($propCode, $propName))
			{
				continue;
			}
			$value = $prop['DISPLAY_VALUE'] ?? ($prop['VALUE'] ?? null);
			if (!polimerPropHasPublicValue($value) && !polimerPropHasPublicValue($prop['VALUE'] ?? null))
			{
				continue;
			}
			$result[$code] = $prop;
		}
		return $result;
	}
}
