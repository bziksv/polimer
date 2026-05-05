<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

if(!class_exists('\Sotbit\Seometa\ChpuSeoDataTable') && class_exists('\Sotbit\Seometa\Orm\ChpuSeoDataTable'))
{
	class ChpuSeoDataTable extends \Sotbit\Seometa\Orm\ChpuSeoDataTable
	{
	}
}

class EsolIEChpuSeoDataTable extends \Sotbit\Seometa\ChpuSeoDataTable
{	
	
	/*public static function Add(array $arFields)
	{
		$result = parent::Add($arFields);
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$result = parent::Update($ID, $arFields);
		return $result;
	}*/
}