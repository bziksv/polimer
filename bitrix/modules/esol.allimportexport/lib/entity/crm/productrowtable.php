<?php
namespace Bitrix\Crm;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEProductRowTable extends \Bitrix\Crm\ProductRowTable
{
	public static function getMap(): array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		$arMap['IE_PRODUCT'] = array(
			'data_type' => '\Bitrix\Catalog\EsolIEProductTable',
			'reference' => array(
				'=this.PRODUCT_ID' => 'ref.ID',
			)
		);
		
		return $arMap;
	}
}
