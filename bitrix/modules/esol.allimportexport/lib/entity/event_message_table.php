<?php
namespace Bitrix\Main\Mail\Internal;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEEventMessageTable extends \Bitrix\Main\Mail\Internal\EventMessageTable
{
	public static function SaveMessageSite($site, $ID)
	{
		$arSites = array_diff(is_array($site) ? $site : preg_split('/[^A-Za-z0-9]+/', trim($site)), array(''));
		EventMessageSiteTable::delete($ID);
		foreach($arSites as $siteId)
		{
			EventMessageSiteTable::add(array('EVENT_MESSAGE_ID'=>$ID, 'SITE_ID'=>$siteId));
		}
		/*$dbRes = EventMessageSiteTable::getList(array('filter'=>array('EVENT_MESSAGE_ID'=>$ID)));
		while($arr = $dbRes->Fetch())
		{
			if(!in_array($arr['SITE_ID'], $arSites))
			{
				EventMessageSiteTable::delete(array('EVENT_MESSAGE_ID'=>$ID, 'SITE_ID'=>$arr['SITE_ID']));
			}
			else $arSites = array_diff($arSites, array($arr['SITE_ID']));
		}
		foreach($arSites as $siteId)
		{
			EventMessageSiteTable::add(array('EVENT_MESSAGE_ID'=>$ID, 'SITE_ID'=>$siteId));
		}*/
	}
	
	public static function Add(array $arFields)
	{
		$result = parent::Add($arFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
			if(isset($arFields['LID'])) self::SaveMessageSite($arFields['LID'], $ID);
		}
		
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$result = parent::Update($ID, $arFields);
		if($result->isSuccess())
		{
			if(isset($arFields['LID'])) self::SaveMessageSite($arFields['LID'], $ID);
		}
		
		return $result;
	}
}