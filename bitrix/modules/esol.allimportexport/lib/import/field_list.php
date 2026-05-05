<?php
namespace Bitrix\EsolAie\Import;

use Bitrix\Main\Entity,
	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__FILE__).'/../field_list.php');

class FieldList {
	protected static $instances = array();
	protected $eFields = null;
	protected $entity = null;
	protected $entityClass = null;
	protected $params = array();
	
	public static function getInstance($entity='false')
	{
		if (!isset(static::$instances[$entity]))
			static::$instances[$entity] = new static($entity);

		return static::$instances[$entity];
	}
	
	public function __construct($entity)
	{
		$this->entity = $entity;
		$this->entityClass = \Bitrix\EsolAie\Runner::GetEntityClassByKey($entity);
		$this->checkEntityModule($this->entityClass);
	}
	
	public function checkEntityModule($entityClass)
	{
		$module = ToLower(current(explode('\\', substr($entityClass, strlen('\Bitrix\\')))));
		if($module) Loader::includeModule($module);
	}
	
	public function setProfileParams($params=array())
	{
		$this->params = $params;
		if(is_callable(array($this->entityClass, 'setProfileParams')))
		{
			call_user_func(array($this->entityClass, 'setProfileParams'), $params);
		}
	}
	
	public function getEntityOptions()
	{
		$arParams = array();
		if(is_callable(array($this->entityClass, 'getEntityOptions')))
		{
			$arParams = call_user_func(array($this->entityClass, 'getEntityOptions'));
			if(!is_array($arParams)) $arParams = array();
		}
		return $arParams;
	}
	
	public function GetEntityClass()
	{
		return $this->entityClass;
	}
	
	public function GetEntityRelTitles()
	{
		$entityClass = $this->entityClass;
		if(is_callable(array($entityClass, 'getRelTitles')))
		{
			$arTitles = $entityClass::getRelTitles();
		}
		else $arTitles = array();
		return $arTitles;
	}
	
	public function GetReferenceFieldTitle($field, $arFieldTitles)
	{
		$title = $field->getTitle();
		/*$arReference = $field->getReference();
		if(count($arReference)==1)
		{
			$key = current(array_keys($arReference));
			if(preg_match('/^=this\.([^\.]+)$/i', $key, $m) && isset($arFieldTitles[$m[1]]) && strlen($arFieldTitles[$m[1]]) > 0)
			{
				$title = $arFieldTitles[$m[1]];
			}
		}*/
		$arEntityTitles = array(
			'\Bitrix\Iblock\Iblock' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK"),
			'\Bitrix\Catalog\Discount' => Loc::getMessage("ESOL_AE_FL_ENTITY_CATALOG_DISCOUNT"),
			'\Bitrix\Sale\Internals\Discount' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_DISCOUNT"),
			'\Bitrix\Iblock\Property' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK_PROPERTY"),
			'\Bitrix\Sale\Internals\StatusLang' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_STATUS_LANG"),
			'\Bitrix\Sale\Internals\Basket' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_BASKET"),
			'\Bitrix\Sale\Internals\Payment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_PAYMENT"),
			'\Bitrix\Sale\Internals\Shipment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_SHIPMENT"),
			'\Bitrix\Sale\Internals\OrderCoupons' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDERCOUPONS"),
			'\Bitrix\Sale\Internals\OrderDiscountData' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDERDISCOUNTDATA"),
			'\Bitrix\Sale\Internals\Fuser' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_FUSER"),
			'\Bitrix\Main\User' => Loc::getMessage("ESOL_AE_FL_ENTITY_USER"),
			'\Bitrix\Sale\Internals\Order' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDER"),
			'\Bitrix\Main\UserGroup' => Loc::getMessage("ESOL_AE_FL_ENTITY_USER_GROUP"),
			'\Bitrix\Main\Group' => Loc::getMessage("ESOL_AE_FL_ENTITY_GROUP"),
			'\Bitrix\Sale\Internals\OrderPropsValue' => Loc::getMessage("ESOL_AE_FL_ENTITY_ORDERPROPSVALUE"),
			'\Bitrix\EsolAie\Entity\SaleUserAccount' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_USER_ACCOUNT"),
			'\Bitrix\Sale\Internals\UserProps' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_USER_PROPS"),
			'\Bitrix\Sale\Internals\DiscountCoupon' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_DISCOUNT_COUPON"),
			'\Bitrix\Sale\Internals\DiscountGroup' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_DISCOUNT_GROUP")
		);
		$entityName = $field->getRefEntityName();
		if(isset($arEntityTitles[$entityName]) && strlen($arEntityTitles[$entityName]) > 0)
		{
			$title = $arEntityTitles[$entityName].' ['.$title.']';
		}
		
		return $title;
	}
	
	public static function GetEntityForRels($relEntityClass)
	{
		if(substr($relEntityClass, -5)!=='Table') $relEntityClass = $relEntityClass.'Table';
		if($relEntityClass=='\Bitrix\Iblock\ElementTable') $relEntityClass = '\Bitrix\Iblock\EsolIEElementTable';
		return $relEntityClass;
	}
	
	public function GetRelsByField($field, $entityClass=false)
	{
		if($field=='ID') return array();
		$arParts = explode('.', $field);
		$field = array_shift($arParts);
		$restField = implode('.', $arParts);
		$arRelFields = array();
		if(!$entityClass) $entityClass = $this->GetEntityClass();
		if(is_callable(array($entityClass, 'getMap')))
		{
			$arMap = $entityClass::getMap();
			if(is_callable(array($entityClass, 'getImportExtraFields')))
			{
				$arMap = $arMap + $entityClass::getImportExtraFields();
			}
			foreach($arMap as $mk=>$mf)
			{
				/*if((is_array($mf) && $mk==$field && $mf['primary']) 
					|| (is_object($mf) && is_callable(array($mf, 'getName')) && $mf->getName()==$field && is_callable(array($mf, 'isPrimary')) && $mf->isPrimary())) return array();*/
				
				$curFieldName = '';
				if($mf instanceof Entity\ReferenceField)
				{
					$arRef = $mf->getReference();
					$curFieldName = $mf->getName();
					$refDataClass = $mf->getRefEntity()->getDataClass();
					$relEntityClass = $mf->getDataType();
				}
				elseif(is_array($mf) && array_key_exists('reference', $mf) && is_array($mf['reference']))
				{
					$arRef = $mf['reference'];
					$curFieldName = $mk;
					$refDataClass = $mf['data_type'];
					$relEntityClass = $mf['data_type'];
				}
				if(strlen($curFieldName) > 0)
				{
					if(strlen($restField) > 0)
					{
						if($curFieldName==$field)
						{
							return $this->GetRelsByField($restField, $refDataClass);
						}
						continue;
					}
					if(is_array($arRef) && count($arRef)==1 && (array_key_exists('=this.'.$field, $arRef) || in_array('this.'.$field, $arRef)))
					{
						$relEntityClass = self::GetEntityForRels($relEntityClass);
						if($relEntity = \Bitrix\EsolAie\Runner::GetEntityKeyByClass($relEntityClass))
						{
							$fl2 = self::getInstance($relEntity);
							$arRelFields = $arRelFields + $fl2->GetEntityFieldsForFilter();
						}
						else
						{
							$fl2 = self::getInstance($this->entity);
							$arRelFields = $arRelFields + $fl2->GetEntityFieldsForFilter($relEntityClass);
						}
						foreach($arRelFields as $k=>$v)
						{
							if(strpos($k, '.')!==false) unset($arRelFields[$k]);
						}
					}
				}
				//if(!empty($arRelFields)) break;
			}
		}
		return $arRelFields;
	}
	
	public function GetRelTableByField($field, $relField='', $entityClass=false)
	{
		$arParts = explode('.', $field);
		$field = array_shift($arParts);
		$restField = implode('.', $arParts);
		$arRelFields = array();
		if(!$entityClass) $entityClass = $this->GetEntityClass();
		if(is_callable(array($entityClass, 'getMap')))
		{
			$arMap = $entityClass::getMap();
			if(is_callable(array($entityClass, 'getImportExtraFields')))
			{
				$arMap = $arMap + $entityClass::getImportExtraFields();
			}
			foreach($arMap as $mk=>$mf)
			{
				$curFieldName = '';
				if($mf instanceof Entity\ReferenceField)
				{
					$arRef = $mf->getReference();
					$curFieldName = $mf->getName();
					$refDataClass = $mf->getRefEntity()->getDataClass();
					$relEntityClass = $mf->getDataType();
				}
				elseif(is_array($mf) && array_key_exists('reference', $mf) && is_array($mf['reference']))
				{
					$arRef = $mf['reference'];
					$curFieldName = $mk;
					$refDataClass = $mf['data_type'];
					$relEntityClass = $mf['data_type'];
				}
				if(strlen($curFieldName) > 0)
				{
					if(strlen($restField) > 0)
					{
						if($curFieldName==$field)
						{
							return $this->GetRelTableByField($restField, $relField, $refDataClass);
						}
						continue;
					}
					if(count($arRef)==1 && (array_key_exists('=this.'.$field, $arRef) || in_array('this.'.$field, $arRef)))
					{
						$relEntityClass = self::GetEntityForRels($relEntityClass);
						if($relEntity = \Bitrix\EsolAie\Runner::GetEntityKeyByClass($relEntityClass))
						{
							$fl2 = self::getInstance($relEntity);
							$arRelFields = $fl2->GetEntityFieldsForFilter();
						}
						else
						{
							$fl2 = self::getInstance($this->entity);
							$arRelFields = $fl2->GetEntityFieldsForFilter($relEntityClass);
						}
						$primary = '';
						foreach($arRelFields as $k=>$v)
						{
							if($v['primary'] && strpos($k, '.')===false) $primary = $k;
						}
						if(strlen($primary) > 0)
						{
							$arRet = array('NAME'=>$relEntityClass, 'PRIMARY'=>$primary);
							if(strlen($relField) > 0)
							{
								if(array_key_exists($relField, $arRelFields))
								{
									return $arRet;
								}
							}
							else
							{
								return $arRet;
							}
						}
					}
				}
			}
		}
		return false;
	}
	
	public function GetEntityFieldsDirect($entityClass=false, $allowRefs = true)
	{
		if($entityClass===false) $entityClass = $this->entityClass;
		$this->checkEntityModule($entityClass);
		
		if(!$entityClass || !class_exists($entityClass)) return array();
		@ob_start();
		
		if(is_callable(array($entityClass, 'getIEFields')))
		{
			$arFields = $entityClass::getIEFields('import');
		}
		else
		{
			$arFields = $entityClass::getMap();
		}
		if(is_callable(array($entityClass, 'getImportExtraFields')))
		{
			$arFields = $arFields + $entityClass::getImportExtraFields();
		}
		
		$arAllowImportFields = array();
		if(is_callable(array($entityClass, 'getRelAllowImportFields')))
		{
			$arAllowImportFields = $entityClass::getRelAllowImportFields();
		}
		
		
		$arOldFields = $arFields;
		$arFields = array();
		$arFieldTitles = array();
		foreach($arOldFields as $k=>$v)
		{
			$key = $k;
			if(is_object($v)) $fieldTitle = $v->getTitle();
			else $fieldTitle = $v['title'];
			if(is_numeric($key))
			{
				if(is_object($v) && is_callable(array($v, 'getColumnName'))
					&& (/*exception for expression field*/ !is_callable(array($v, 'getValueField')) || $v->getValueField())) $key = $v->getColumnName();
				else $key = $fieldTitle;
			}
			$arFieldTitles[$key] = $fieldTitle;
			$arFields[$key] = $v;
		}
		
		if($allowRefs) $arRelTitles = $this->GetEntityRelTitles();
		else $arRelTitles = array();
		
		$arRefs = array();
		foreach($arFields as $k=>$v)
		{
			$v2 = $v;
			if(is_object($v))
			{
				if($v2 instanceof Entity\ReferenceField)
				{
					if($allowRefs)
					{
						$arRefs[$k] = array(
							'items' => $this->GetEntityFieldsDirect($v2->getRefEntity()->getDataClass(), false),
							'title' => (isset($arRelTitles[$k]) ? $arRelTitles[$k] : $this->GetReferenceFieldTitle($v2, $arFieldTitles))
						);
					}
					elseif(array_key_exists($v2->getName(), $arAllowImportFields))
					{
						$af = $arAllowImportFields[$v2->getName()];
						if(is_array($af) && !empty($af) /*&& in_array($v2['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text', 'date', 'datetime', 'enum', 'float'))*/)
						{
							$arRefs[$k] = array(
								'items' => $this->GetEntityFieldsDirect($v2->getRefEntity()->getDataClass(), false),
								'title' => (isset($arRelTitles[$k]) ? $arRelTitles[$k] : $this->GetReferenceFieldTitle($v2, $arFieldTitles))
							);
							if(is_array($arRefs[$k]['items']))
							{
								foreach($arRefs[$k]['items'] as $k3=>$v3)
								{
									if(!in_array($k3, $af) && !isset($af[$k3])) unset($arRefs[$k]['items'][$k3]);
									elseif(isset($af[$k3]) && is_array($af[$k3]))
									{
										$arRefs[$k]['items'][$k3]['uf_settings'] = $af[$k3];
									}
								}
							}
							if(!is_array($arRefs[$k]['items']) && empty($arRefs[$k]['items'])) unset($arRefs[$k]);
						}
					}
					unset($arFields[$k]);
					continue;
				}
				
				if(is_callable(array($v2, 'getDataType'))
					&& !($v2 instanceof Entity\ExpressionField))
				{
					$arFields[$k] = $v = array(
						'data_type' => $v2->getDataType(),
						'title' => $v2->getTitle(),
						'private' => (is_callable(array($v2, 'isPrivate')) ? $v2->isPrivate() : false)
					);
					if(is_callable(array($v2, 'isPrimary')) && $v2->isPrimary()) $arFields[$k]['primary'] = 1;
					if(is_callable(array($v2, 'isSerialized')) && $v2->isSerialized()) $arFields[$k]['serialized'] = 1;
				}
				else
				{
					unset($arFields[$k]);
					continue;
				}
			}
			else
			{
				if($v2['reference'])
				{
					$af = (isset($v2['ALLOW_IMPORT_FIELDS']) ? $v2['ALLOW_IMPORT_FIELDS'] : false);
					if(is_array($af) && !empty($af) /*&& in_array($v2['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text', 'date', 'datetime', 'enum', 'float'))*/)
					{
						$refField = new Entity\ReferenceField($k, $v2['data_type'], $v2['reference']);
						$arRefs[$k] = array(
							'items' => $this->GetEntityFieldsDirect($refField->getRefEntity()->getDataClass(), false),
							'title' => (isset($arRelTitles[$k]) ? $arRelTitles[$k] : $this->GetReferenceFieldTitle($refField, $arFieldTitles))
						);
						if(is_array($arRefs[$k]['items']))
						{
							foreach($arRefs[$k]['items'] as $k3=>$v3)
							{
								if(!in_array($k3, $af) && !isset($af[$k3])) unset($arRefs[$k]['items'][$k3]);
								elseif(isset($af[$k3]) && is_array($af[$k3]))
								{
									$arRefs[$k]['items'][$k3]['uf_settings'] = $af[$k3];
								}
							}
						}
						if(!is_array($arRefs[$k]['items']) && empty($arRefs[$k]['items'])) unset($arRefs[$k]);
					}
					unset($arFields[$k]);
					continue;
				}
				elseif($v2['expression'])
				{
					unset($arFields[$k]);
					continue;
				}
			}
			if(!isset($arFields[$k]['title']) || strlen($arFields[$k]['title'])==0 || $arFields[$k]['title']==$k)
			{
				$arFields[$k]['title'] = $k;
				if(strlen(Loc::getMessage($entityClass."_ESOL_AE_".$k)) > 0)
				{
					$arFields[$k]['title'] = Loc::getMessage($entityClass."_ESOL_AE_".$k);
				}
				elseif(strlen(Loc::getMessage(str_replace('\EsolIE', '\\', $entityClass)."_ESOL_AE_".$k)) > 0)
				{
					$arFields[$k]['title'] = Loc::getMessage(str_replace('\EsolIE', '\\', $entityClass)."_ESOL_AE_".$k);
				}
			}
			if(in_array($v['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text')) && (!array_key_exists('private', $v) || !$v['private']))
			{
				$arFields[$k]['uid'] = 'Y';
			}
		}

		$entityObj = new $entityClass();
		$conn = $entityObj->getEntity()->getConnection();
		$tblName = $entityObj->getTableName();
		$res0 = $conn->query("SHOW FULL COLUMNS FROM `" . $tblName . "`");
		while($f0 = $res0->fetch())
		{
			if(isset($arFields[$f0['Field']]) && $f0['Null']=='NO' && strlen($f0['Default'])==0 && $f0['Key']!='PRI')
			{
				$arFields[$f0['Field']]['required'] = 1;
			}
		}
		
		foreach($arRefs as $k=>$v)
		{
			$arFields[$k] = $v;
		}
		@ob_end_clean();
		return $arFields;
	}
	
	public function GetEntityFields($className=false)
	{	
		if(!$className)
		{
			if(!isset($this->eFields))
			{
				$this->eFields = $this->GetEntityFieldsDirect(false, false);
			}
			return $this->eFields;
		}
		else return $this->GetEntityFieldsDirect($className, false);
	}
	
	public function GetPrimaryField()
	{
		$arPrimaryFields = array();
		$arFields = $this->GetEntityFields();
		foreach($arFields as $k=>$v)
		{
			if($v['primary']) $arPrimaryFields[] = $k;
		}
		if(empty($arPrimaryFields) && array_key_exists('ID', $arFields)) $arPrimaryFields[] = 'ID';
		if(count($arPrimaryFields)==1) return current($arPrimaryFields);
		else return $arPrimaryFields;
	}
	
	public function GetRequiredField()
	{
		$arRequiredFields = array();
		$arFields = $this->GetEntityFields();
		foreach($arFields as $k=>$v)
		{
			if($v['required']) $arRequiredFields[$k] = $v;
		}
		return $arRequiredFields;
	}
	
	public function GetEntityFieldsForFilter($className=false)
	{
		$arFields = $this->GetEntityFields($className);
		$arRels = array();
		foreach($arFields as $k=>$v)
		{
			if(isset($v['items']) && is_array($v['items']))
			{
				foreach($v['items'] as $k2=>$v2)
				{
					if(strlen($v2['title'])==0) continue;
					$v2['title'] = $v['title'].' / '.$v2['title'];
					$arRels[$k.'.'.$k2] = $v2;
				}
				unset($arFields[$k]);
			}
		}
		foreach($arRels as $k=>$v)
		{
			$arFields[$k] = $v;
		}
		return $arFields;
	}
	
	public function GetFieldsDirect(&$arGroups, $arFields, $group, $title)
	{
		$arGroups[$group] = array(
			'title' => $title,
			'items' => array()
		);
		foreach($arFields as $k=>$ar)
		{
			if(is_array($ar['items']))
			{
				if(!empty($ar['items']))
				{
					$this->GetFieldsDirect($arGroups, $ar['items'], $k, $ar['title']);
				}
			}
			else
			{
				$key = $k;
				if($group!='__BASE__') $key = $group.'.'.$key;
				$arGroups[$group]['items'][$key] = $ar["title"];
			}
		}
	}
	
	public function GetFields()
	{
		if(!$this->aFields)
		{
			$arGroups = array();
			$this->GetFieldsDirect($arGroups, $this->GetEntityFields(), '__BASE__', Loc::getMessage("ESOL_AE_FL_BASE_GROUP"));
			$this->aFields = $arGroups;
		}
	
		return $this->aFields;
	}
	
	public function GetFieldNames()
	{		
		if(!$this->arFieldNames)
		{
			$this->arFieldNames = array();
			$arFields = $this->GetEntityFieldsForFilter();
			foreach($arFields as $k=>$v)
			{
				$this->arFieldNames[$k] = $v['title'];
			}
		}

		return $this->arFieldNames;
	}
	
	public function GetSortableFields()
	{
		if(!$this->aSortableFields)
		{
			$this->aSortableFields = array();
			foreach($this->GetEntityFields() as $k=>$ar)
			{
				if(in_array($ar['data_type'], array('integer', 'string', 'boolean')))
				{
					$this->aSortableFields[] = $k;
				}
			}
		}
	
		return $this->aSortableFields;
	}
	
	public function ShowSelectFields($fname, $value="", $arParams=array())
	{
		$arGroups = $this->GetFields();
		if($arParams['MULTIPLE']){?><select name="<?echo $fname;?>" multiple><?}
		else{?><select name="<?echo $fname;?>"><option value=""><?echo Loc::getMessage("ESOL_AE_FL_CHOOSE_FIELD");?></option><?}
		foreach($arGroups as $k2=>$v2)
		{
			?><optgroup label="<?echo $v2['title']?>"><?
			foreach($v2['items'] as $k=>$v)
			{
				?><option value="<?echo $k; ?>" <?if($k==$value){echo 'selected';}?>><?echo htmlspecialcharsbx($v); ?></option><?
			}
			?></optgroup><?
		}
		?></select><?
	}
	
	public function GetSelectUidFields($val=false)
	{
		$hash = md5(serialize($val));
		if(!$this->UidFields) $this->UidFields = array();
		
		if(!$this->UidFields[$hash])
		{
			ob_start();
			foreach($this->GetEntityFields() as $k=>$ar)
			{
				if($ar['uid']=="Y")
				{
					?><option value="<?echo $k; ?>" <?if((is_array($val) && in_array($k, $val)) || $k==$val){echo 'selected';}?>><?echo htmlspecialcharsbx($ar['title']); ?></option><?
				}
			}
			$this->UidFields[$hash] = ob_get_clean();
		}
		return $this->UidFields[$hash];
	}
	
	public function ShowSelectUidFields($fname, $val=false)
	{
		$fields = $this->GetSelectUidFields($val);
		?><select name="<?echo $fname;?>" class="chosen" multiple><?echo $fields;?></select><?
	}
}
?>