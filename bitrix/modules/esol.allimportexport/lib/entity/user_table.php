<?php
namespace Bitrix\Main;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEUserTable extends \Bitrix\Main\UserTable
{
	protected static $profParams = array();
	
	public static function getMap(): array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		$arMap['IE_FULL_NAME'] = array(
			'data_type' => 'string',
			'expression' => array(
				$helper->getConcatFunction("%s","' '", "%s", "' ['", "%s", "']'"),
				'LAST_NAME', 'NAME', 'LOGIN'
			)
		);
		
		$arMap['IE_USER_GROUP'] = array(
			'data_type' => '\Bitrix\Main\UserGroupTable',
			'reference' => array('=ref.USER_ID' => 'this.ID'),
			'ALLOW_REFS' => array('GROUP'),
			'ALLOW_IMPORT_FIELDS' => array('GROUP_ID'=>array('MULTIPLE'=>'Y'))
		);
		
		if(class_exists('\Bitrix\Main\UserPhoneAuthTable'))
		{
			$isFieldPhone = false;
			foreach($arMap as $k=>$v)
			{
				if(is_callable(array($v, 'getName')) && $v->getName()=='PHONE_AUTH')
				{
					$isFieldPhone = true;
				}
			}
			
			if(!$isFieldPhone)
			{
				$arMap['PHONE_AUTH'] = array(
					'data_type' => '\Bitrix\Main\UserPhoneAuthTable',
					'reference' => array('=ref.USER_ID' => 'this.ID')
				);	
			}
			$arMap['PHONE_AUTH.PHONE_NUMBER'] = new \Bitrix\Main\Entity\StringField(
				'PHONE_AUTH.PHONE_NUMBER', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_PHONE_NUMBER')
				)
			);
		}
		
		$arFields = array();
		$conn = \Bitrix\Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$res0 = $conn->query("SHOW FULL COLUMNS FROM ".$helper->quote(self::getTableName()));
		while($f0 = $res0->fetch())
		{
			$arFields[] = $f0['Field'];
		}
		
		if(in_array('PASSWORD_EXPIRED', $arFields) && !array_key_exists('PASSWORD_EXPIRED', $arMap))
		{
			$arMap['PASSWORD_EXPIRED'] = array(
				'data_type' => 'string', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_PASSWORD_EXPIRED')
				)
			);
		}
		
		if(Loader::includeModule('sale'))
		{
			$arMap['IE_USER_ACCOUNT'] = array(
				'data_type' => '\Bitrix\EsolAie\Entity\SaleUserAccountTable',
				'reference' => array('=ref.USER_ID' => 'this.ID'),
				'ALLOW_IMPORT_FIELDS' => array('CURRENT_BUDGET'=>array())
			);
			
			$arMap['IE_USER_SALE_PROPS'] = array(
				'data_type' => '\Bitrix\Sale\Internals\UserPropsTable',
				'reference' => array('=ref.USER_ID' => 'this.ID'),
				'ALLOW_IMPORT_FIELDS' => array('PERSON_TYPE_ID'=>array())
			);
			
			$arMap['IE_USER_ORDER'] = array(
				'data_type' => '\Bitrix\Sale\Internals\EsolIEOrderTable',
				'reference' => array('=ref.USER_ID' => 'this.ID')
			);
			
			$arMap['IE_USER_ORDER_ARCHIVE'] = array(
				'data_type' => '\Bitrix\Sale\Internals\OrderArchiveTable',
				'reference' => array('=ref.USER_ID' => 'this.ID')
			);
			
			$arMap['IE_USER_ORDER_LAST_DATE'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_LAST_DATE'),
				'data_type' => 'string',
				'expression' => array(
					'MAX(%s)', 'IE_USER_ORDER.DATE_INSERT'
				)
			);
			
			$arMap['IE_USER_ORDER_CNT'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_CNT'),
				'data_type' => 'string',
				'expression' => array(
					'COUNT(DISTINCT %s)', 'IE_USER_ORDER.ID'
				)
			);
			
			$arMap['IE_USER_ORDER_PAYED_CNT'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_PAYED_CNT'),
				'data_type' => 'string',
				'expression' => array(
					'COUNT(DISTINCT IF(%s="Y", %s, NULL))', 'IE_USER_ORDER.PAYED', 'IE_USER_ORDER.ID'
				)
			);
			
			$arMap['IE_USER_ORDER_DEDUCTED_CNT'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_DEDUCTED_CNT'),
				'data_type' => 'string',
				'expression' => array(
					'COUNT(DISTINCT IF(%s="Y", %s, NULL))', 'IE_USER_ORDER.DEDUCTED', 'IE_USER_ORDER.ID'
				)
			);
			
			$arMap['IE_USER_ORDER_SUM'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_SUM'),
				'data_type' => 'string',
				'expression' => array(
					'ROUND(SUM(IF(%s>0, %s, 0))*COUNT(DISTINCT %s)/COUNT(%s), 2)', 'IE_USER_ORDER.ID', 'IE_USER_ORDER.PRICE', 'IE_USER_ORDER.ID', 'IE_USER_ORDER.ID'
				)
			);
			
			$arMap['IE_USER_ORDER_ARCHIVE_CNT'] = array(
				'title' => Loc::getMessage('ESOL_AIE_USER_FIELD_ORDER_ARCHIVE_CNT'),
				'data_type' => 'string',
				'expression' => array(
					'COUNT(DISTINCT %s)', 'IE_USER_ORDER_ARCHIVE.ID'
				)
			);
		}
		
		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();
		unset(
			$arMap['DATE_REG_SHORT'],
			$arMap['LAST_LOGIN_SHORT'],
			$arMap['SHORT_NAME'],
			$arMap['IS_ONLINE'],
			$arMap['INDEX']
		);
		if(!$GLOBALS['USER']->IsAdmin()) unset($arMap['PASSWORD']);
		if(isset($arMap['PERSONAL_PHOTO']) && is_array($arMap['PERSONAL_PHOTO']))
		{
			$arMap['PERSONAL_PHOTO']['data_type'] = 'file';
		}
		foreach($arMap as $k=>$v)
		{
			if(is_object($v) && (
				$v instanceof \Bitrix\Main\ORM\Fields\Relations\Reference ||
				$v instanceof \Bitrix\Main\ORM\Fields\Relations\OneToMany
				))
			{
				unset($arMap[$k]);
				continue;
			}
			if(is_object($v) && $v->getName()=='PASSWORD' && !$GLOBALS['USER']->IsAdmin())
			{
				unset($arMap[$k]);
			}
			if(is_array($arMap[$k]) && !isset($arMap[$k]['title']))
			{
				$arMap[$k]['title'] = Loc::getMessage('ESOL_AIE_USER_FIELD_'.ToUpper($k));
				if(strlen($arMap[$k]['title'])==0) $arMap[$k]['title'] = $k;
			}
		}
		
		$dbRes = \CUserTypeEntity::GetList(array('SORT'=>'ASC', 'ID'=>'ASC'), array('ENTITY_ID'=>'USER', 'LANG'=>LANGUAGE_ID));
		if($dbRes->SelectedRowsCount() > 0)
		{
			while($arr = $dbRes->Fetch())
			{
				$name = trim($arr['LIST_COLUMN_LABEL']);
				if(strlen($name)==0) $name = trim($arr['EDIT_FORM_LABEL']);
				if(strlen($name)==0) $name = trim($arr['FIELD_NAME']);
				$arGroups['userfields']['items'][$arr['FIELD_NAME']] = $name;
				$arSettings = $arr['SETTINGS'];
				$arSettings['MULTIPLE'] = $arr['MULTIPLE'];
				$arMap[$arr['FIELD_NAME']] = array(
					'ID' => $arr['ID'],
					'data_type' => 'string',
					'title' => $name,
					'uf_user_type' => $arr['USER_TYPE_ID'],
					'uf_field_id' => $arr['ID'],
					'uf_settings' => $arSettings
				);
			}
		}
		
		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(PHONE_AUTH|INDEX_SELECTOR)[^\.]*$/', array_keys($arMap))));
		
		return $arMap;
	}
	
	public static function getImportExtraFields()
	{
		$arMap = array();
		$arMap['IE_PASSWORD_HASH'] = array(
			'data_type' => 'string',
		);
		return $arMap;
	}
	
	public static function getIEFieldsRel()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LOGIN' => array(
				'data_type' => 'string'
			),
			'EMAIL' => array(
				'data_type' => 'string'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("%s","' '", "UPPER(".$helper->getSubstrFunction("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'IE_FULL_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("%s","' '", "%s", " [", "%s", "]"),
					'LAST_NAME', 'NAME', 'LOGIN'
				)
			),
			'DATE_REGISTER' => array(
				'data_type' => 'datetime'
			),
		);
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['DATE_REGISTER'])) $arFields['DATE_REGISTER'] = ConvertTimeStamp(false, "FULL");
		else $arFields['DATE_REGISTER'] = ConvertTimeStamp(strtotime($arFields['DATE_REGISTER']), "FULL");
		if(!isset($arFields['PASSWORD'])) $arFields['PASSWORD'] = substr(md5(mt_rand()), 0, 8);
		$arFields['CONFIRM_PASSWORD'] = $arFields['PASSWORD'];
	}
	
	public static function SetPhoneNumber($ID, $phoneNumber)
	{
		if(class_exists('\Bitrix\Main\UserPhoneAuthTable') && $ID > 0)
		{
			$phoneNumber = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phoneNumber);				
			if($arPhone = \Bitrix\Main\UserPhoneAuthTable::getList(array('filter'=>array('USER_ID'=>$ID)))->Fetch())
			{
				if(strlen($phoneNumber) > 0)
				{
					\Bitrix\Main\UserPhoneAuthTable::update($ID, array('PHONE_NUMBER'=>$phoneNumber));
				}
				else
				{
					\Bitrix\Main\UserPhoneAuthTable::delete($ID);
				}
			}
			elseif(strlen($phoneNumber) > 0)
			{
				\Bitrix\Main\UserPhoneAuthTable::add(array('USER_ID'=>$ID, 'PHONE_NUMBER'=>$phoneNumber));
			}
		}
	}
	
	public static function SetCurrentBudget($ID, $currentBudget)
	{
		if(class_exists('\Bitrix\EsolAie\Entity\SaleUserAccountTable') && $ID > 0)
		{			
			if($arAccount = \Bitrix\EsolAie\Entity\SaleUserAccountTable::getList(array('filter'=>array('USER_ID'=>$ID)))->Fetch())
			{
				\Bitrix\EsolAie\Entity\SaleUserAccountTable::update($arAccount['ID'], array('CURRENT_BUDGET'=>$currentBudget));
			}
			elseif(strlen($currentBudget) > 0)
			{
				\Bitrix\EsolAie\Entity\SaleUserAccountTable::add(array('USER_ID'=>$ID, 'CURRENT_BUDGET'=>$currentBudget, 'CURRENCY'=>'RUB'));
			}
		}
	}
	
	public static function SetPersonTypeId($ID, $personTypeId)
	{
		if(!is_numeric($personTypeId) && class_exists('\Bitrix\Sale\Internals\PersonTypeTable'))
		{
			if($arPersonType = \Bitrix\Sale\Internals\PersonTypeTable::getList(array('filter'=>array('ENTITY_REGISTRY_TYPE'=>'ORDER', 'NAME'=>$personTypeId)))->Fetch())
			{
				$personTypeId = $arPersonType['ID'];
			}
		}
		$personTypeId = (int)$personTypeId;
		if(class_exists('\Bitrix\Sale\Internals\UserPropsTable') && $ID > 0 && $personTypeId > 0)
		{			
			if($arProfile = \Bitrix\Sale\Internals\UserPropsTable::getList(array('filter'=>array('USER_ID'=>$ID), 'order'=>array('ID'=>'ASC'), 'limit'=>1))->Fetch())
			{
				\Bitrix\Sale\Internals\UserPropsTable::update($arProfile['ID'], array('PERSON_TYPE_ID'=>$personTypeId, 'DATE_UPDATE'=>new \Bitrix\Main\Type\DateTime()));
			}
			elseif($arUser = \Bitrix\Main\UserTable::getList(array('filter'=>array('ID'=>$ID)))->Fetch())
			{
				$userName = $arUser['LOGIN'];
				if(strlen($arUser['NAME']) > 0 || strlen($arUser['LAST_NAME']) > 0) $userName = trim($arUser['LAST_NAME'].' '.$arUser['NAME']);
				\Bitrix\Sale\Internals\UserPropsTable::add(array('USER_ID'=>$ID, 'PERSON_TYPE_ID'=>$personTypeId, 'NAME'=>$userName, 'DATE_UPDATE'=>new \Bitrix\Main\Type\DateTime()));
			}
		}
	}
	
	public static function getEntityOptions()
	{
		return array(
			array(
				'CODE' => 'SEND_MESSAGE',
				'NAME' => Loc::getMessage('ESOL_AIE_USER_OPTION_SEND_MESSAGE'),
				'TYPE' => 'CHECKBOX'
			)
		);
	}
	
	public static function setProfileParams($params=array())
	{
		self::$profParams = $params;
	}
	
	public static function SetUserGroup($ID, $arGroups)
	{
		foreach($arGroups as $k=>$v)
		{
			if(!is_numeric($v) && strlen(trim($v)) > 0)
			{
				if($arGroup = \Bitrix\Main\GroupTable::getList(array('filter'=>array('LOGIC'=>'OR', array('NAME'=>trim($v)), array('STRING_ID'=>trim($v))), 'select'=>array('ID')))->Fetch())
				{
					$arGroups[$k] = $arGroup['ID'];
				}
			}
		}
		\CUser::SetUserGroup($ID, $arGroups);
	}
	
	public static function getFiles(&$arFields)
	{
		if(array_key_exists('PERSONAL_PHOTO', $arFields) && !is_array($arFields['PERSONAL_PHOTO']) && strlen($arFields['PERSONAL_PHOTO']) > 0)
		{
			$arFields['PERSONAL_PHOTO'] = \CFile::MakeFileArray($arFields['PERSONAL_PHOTO']);
		}
	}
	
	public static function Add(array $arFields)
	{
		$arUserFields = $arFields;
		$arGroups = $passHash = $phoneNumber = $currentBudget = $personTypeId = false;
		if(isset($arFields['PHONE_AUTH.PHONE_NUMBER']))
		{
			$phoneNumber = (string)$arFields['PHONE_AUTH.PHONE_NUMBER'];
			$arFields['PHONE_NUMBER'] = $phoneNumber;
			unset($arFields['PHONE_AUTH.PHONE_NUMBER']);
		}
		if(isset($arFields['IE_USER_GROUP.GROUP_ID']))
		{
			$arGroups = $arFields['IE_USER_GROUP.GROUP_ID'];
			if(!is_array($arGroups)) $arGroups = array($arGroups);
			unset($arFields['IE_USER_GROUP.GROUP_ID']);
		}
		if(isset($arFields['IE_USER_ACCOUNT.CURRENT_BUDGET']))
		{
			$currentBudget = $arFields['IE_USER_ACCOUNT.CURRENT_BUDGET'];
			unset($arFields['IE_USER_ACCOUNT.CURRENT_BUDGET']);
		}
		if(isset($arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID']))
		{
			$personTypeId = $arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID'];
			unset($arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID']);
		}
		if(isset($arFields['IE_PASSWORD_HASH']))
		{
			$passHash = $arFields['IE_PASSWORD_HASH'];
			unset($arFields['IE_PASSWORD_HASH']);
		}
		self::getFiles($arFields);
		$user = new \CUser;
		$ID = $user->Add($arFields);
		$result = new \Bitrix\Main\Entity\AddResult();
		if($ID > 0)
		{
			if($arGroups!==false) self::SetUserGroup($ID, $arGroups);
			if($passHash!==false) EsolIEUserAltTable::update($ID, array('PASSWORD'=>$passHash));
			//if($phoneNumber!==false) self::SetPhoneNumber($ID, $phoneNumber);
			if($currentBudget!==false) self::SetCurrentBudget($ID, $currentBudget);
			if($personTypeId!==false) self::SetPersonTypeId($ID, $personTypeId);
			$result->setId($ID);
			
			if(self::$profParams['SEND_MESSAGE']=='Y')
			{
				$event = new \CEvent;
				$arUserFields['USER_ID'] = $ID;
				//$event->SendImmediate("NEW_USER", \CSite::GetDefSite(), $arUserFields);
				$event->Send("NEW_USER", \CSite::GetDefSite(), $arUserFields);
			}
		}
		else $result->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$arGroups = $passHash = $phoneNumber = $currentBudget = $personTypeId = false;
		if(isset($arFields['PHONE_AUTH.PHONE_NUMBER']))
		{
			$phoneNumber = (string)$arFields['PHONE_AUTH.PHONE_NUMBER'];
			$arFields['PHONE_NUMBER'] = $phoneNumber;
			unset($arFields['PHONE_AUTH.PHONE_NUMBER']);
		}
		if(isset($arFields['IE_USER_GROUP.GROUP_ID']))
		{
			$arGroups = $arFields['IE_USER_GROUP.GROUP_ID'];
			if(!is_array($arGroups)) $arGroups = array($arGroups);
			unset($arFields['IE_USER_GROUP.GROUP_ID']);
		}
		if(isset($arFields['IE_USER_ACCOUNT.CURRENT_BUDGET']))
		{
			$currentBudget = $arFields['IE_USER_ACCOUNT.CURRENT_BUDGET'];
			unset($arFields['IE_USER_ACCOUNT.CURRENT_BUDGET']);
		}
		if(isset($arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID']))
		{
			$personTypeId = $arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID'];
			unset($arFields['IE_USER_SALE_PROPS.PERSON_TYPE_ID']);
		}
		if(isset($arFields['IE_PASSWORD_HASH']))
		{
			$passHash = $arFields['IE_PASSWORD_HASH'];
			unset($arFields['IE_PASSWORD_HASH']);
		}
		if(isset($arFields['PASSWORD'])) $arFields['CONFIRM_PASSWORD'] = $arFields['PASSWORD'];
		self::getFiles($arFields);
		$user = new \CUser;
		if($res = $user->Update($ID, $arFields))
		{
			if($arGroups!==false) self::SetUserGroup($ID, $arGroups);
			if($passHash!==false) EsolIEUserAltTable::update($ID, array('PASSWORD'=>$passHash));
			//if($phoneNumber!==false) self::SetPhoneNumber($ID, $phoneNumber);
			if($currentBudget!==false) self::SetCurrentBudget($ID, $currentBudget);
			if($personTypeId!==false) self::SetPersonTypeId($ID, $personTypeId);
		}
		$result = new \Bitrix\Main\Entity\UpdateResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
		return $result;
	}
	
	public static function Delete($ID)
	{
		$user = new \CUser;
		$res = $user->Delete($ID);
		$result = new \Bitrix\Main\Entity\DeleteResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
		return $result;
	}
}

class EsolIEUserAltTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return UserTable::getTableName();
	}
	
	public static function getMap(): array
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'PASSWORD' => array(
				'data_type' => 'string'
			),
		);
	}
}
