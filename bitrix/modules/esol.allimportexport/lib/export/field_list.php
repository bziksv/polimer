<?php
namespace Bitrix\EsolAie\Export;

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
	protected $entityClassAlt = null;
	protected $entityTitle = null;
	protected $minimizeFields = false;
	
	public static function getInstance($entity='false', $fromList='false')
	{
		$key = $entity.'_'.$fromList;
		if (!isset(static::$instances[$key]))
			static::$instances[$key] = new static($entity, $fromList);

		return static::$instances[$key];
	}
	
	public function __construct($entity, $fromList=false)
	{
		$this->entity = $entity;
		if($fromList) $this->minimizeFields = true;
		/*$this->entityClass = \Bitrix\EsolAie\Runner::GetEntityClassByKey($entity);
		$this->entityClassAlt = \Bitrix\EsolAie\Runner::GetEntityClassAltByKey($entity);*/
		$arItem = \Bitrix\EsolAie\Runner::GetEntityByKey($entity);
		$this->entityClass = $arItem['CLASS'];
		$this->entityClassAlt = (isset($arItem['CLASS_ALT']) ? $arItem['CLASS_ALT'] : $arItem['CLASS']);
		$this->entityTitle = $arItem['TITLE'];
	}
	
	public function GetEntityClass()
	{
		return $this->entityClass;
	}
	
	public function GetEntityTitle()
	{
		return $this->entityTitle;
	}
	
	public function GetEntityRelTitles($entityClass='')
	{
		if(strlen($entityClass)==0)$entityClass = $this->entityClassAlt;
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
			'\Bitrix\Iblock\EsolIEProperty' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK_PROPERTY"),
			'\Bitrix\Iblock\SectionProperty' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK_SECTION_PROPERTY"),
			'\Bitrix\Iblock\Section' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK_SECTION"),
			'\Bitrix\Iblock\Element' => Loc::getMessage("ESOL_AE_FL_ENTITY_IBLOCK_ELEMENT"),
			'\Bitrix\Catalog\Product' => Loc::getMessage("ESOL_AE_FL_ENTITY_CATALOG_PRODUCT"),
			'\Bitrix\Catalog\EsolIEProduct' => Loc::getMessage("ESOL_AE_FL_ENTITY_CATALOG_PRODUCT"),
			'\Bitrix\Sale\Internals\StatusLang' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_STATUS_LANG"),
			'\Bitrix\Sale\Internals\Basket' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_BASKET"),
			'\Bitrix\Sale\Internals\EsolIESaleBasket' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_BASKET"),
			'\Bitrix\Sale\Internals\Product' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_PRODUCT"),
			'\Bitrix\Sale\Internals\EsolIEProduct' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_PRODUCT"),
			'\Bitrix\Sale\Internals\ShipmentItem' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_SHIPMENT_ITEM"),
			'\Bitrix\Sale\Internals\Payment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_PAYMENT"),
			'\Bitrix\Sale\Internals\EsolIEPayment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_PAYMENT"),
			'\Bitrix\Sale\Internals\Shipment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_SHIPMENT"),
			'\Bitrix\Sale\Internals\EsolIEShipment' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_SHIPMENT"),
			'\Bitrix\Sale\Internals\OrderCoupons' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDERCOUPONS"),
			'\Bitrix\Sale\Internals\OrderDiscountData' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDERDISCOUNTDATA"),
			'\Bitrix\Sale\Internals\Fuser' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_FUSER"),
			'\Bitrix\Main\User' => Loc::getMessage("ESOL_AE_FL_ENTITY_USER"),
			'\Bitrix\Main\EsolIEUser' => Loc::getMessage("ESOL_AE_FL_ENTITY_USER"),
			'\Bitrix\Sale\Internals\Order' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDER"),
			'\Bitrix\Sale\Internals\EsolIEOrder' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_ORDER"),
			'\Bitrix\Main\UserGroup' => Loc::getMessage("ESOL_AE_FL_ENTITY_USER_GROUP"),
			'\Bitrix\Main\Group' => Loc::getMessage("ESOL_AE_FL_ENTITY_GROUP"),
			'\Bitrix\Sale\Internals\OrderPropsValue' => Loc::getMessage("ESOL_AE_FL_ENTITY_ORDERPROPSVALUE"),
			'\Bitrix\Sale\Internals\EsolIEOrderPropsValue' => Loc::getMessage("ESOL_AE_FL_ENTITY_ORDERPROPSVALUE"),
			'\Bitrix\EsolAie\Entity\StoreDocs' => Loc::getMessage("ESOL_AE_FL_ENTITY_STORE_DOCS"),
			'\Bitrix\EsolAie\Entity\StoreDocsElement' => Loc::getMessage("ESOL_AE_FL_ENTITY_STORE_DOCS_ELEMENT"),
			'\Bitrix\Sale\Location\Name\EsolIEGroup' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_LOCATION_GROUP_NAME"),
			'\Bitrix\Sale\Location\Name\Location' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_LOCATION_NAME"),
			'\Bitrix\Sale\Location\Type' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_LOCATION_TYPE"),
			'\Bitrix\EsolAie\Entity\SaleUserAccount' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_USER_ACCOUNT"),
			'\Bitrix\Sale\Internals\UserProps' => Loc::getMessage("ESOL_AE_FL_ENTITY_SALE_USER_PROPS"),
			'\Bitrix\Catalog\Price' => Loc::getMessage("ESOL_AE_FL_ENTITY_CATALOG_PRICE"),
			'\Bitrix\Sale\Delivery\Services' => Loc::getMessage("ESOL_AE_FL_ENTITY_DELIVERY_SERVICE"),
			'\Bitrix\Sale\Internals\PaySystemAction' => Loc::getMessage("ESOL_AE_FL_ENTITY_PAYSYSTEM_ACTION"),
			'\Bitrix\Crm\Status' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_DEAL_STATUS"),
			'\Bitrix\Crm\Company' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_COMPANY"),
			'\Bitrix\Crm\Contact' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_CONTACT"),
			'\Bitrix\Crm\EsolIEDealUts' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_DEAL_UTS"),
			'\Bitrix\Crm\EsolIELeadUts' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_DEAL_LEAD"),
			'\Bitrix\Crm\EsolIECompanyUts' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_DEAL_COMPANY"),
			'\Bitrix\Crm\EsolIEContactUts' => Loc::getMessage("ESOL_AE_FL_ENTITY_CRM_DEAL_CONTACT"),
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
	
	public function GetEntityAllowRefs($entityClass)
	{
		$arRefs = array(
			'\Bitrix\Sale\Internals\BasketTable'=>array('PRODUCT', 'IE_IBLOCK_ELEMENT'),
			'\Bitrix\Sale\Internals\EsolIESaleBasketTable'=>array('PRODUCT', 'IE_IBLOCK_ELEMENT')
		);
		if(array_key_exists($entityClass, $arRefs)) return $arRefs[$entityClass];
		else return false;
	}
	
	public function GetEntityFieldsDirect($entityClass=false, $allowRefs = true, $level = 0)
	{
		$entityClassAlt = \Bitrix\EsolAie\Entity\Utils::GetAltClass($entityClass);
		if($entityClass===false)
		{
			$entityClass = $this->entityClass;
			$entityClassAlt = $this->entityClassAlt;
		}
		$arAllowRefs = array();
		if(is_array($allowRefs))
		{
			$arAllowRefs = $allowRefs;
			$allowRefs = true;
		}
		$module = ToLower(current(explode('\\', substr($entityClass, strlen('\Bitrix\\')))));
		if($module) Loader::includeModule($module);
		
		if(!$entityClass || !class_exists($entityClass)) return array();
		@ob_start();
		
		if($level > 0 && is_callable(array($entityClassAlt, 'getIEFieldsRel')))
		{
			$arFields = $entityClassAlt::getIEFieldsRel();
		}
		elseif(is_callable(array($entityClassAlt, 'getIEFields')))
		{
			$arFields = $entityClassAlt::getIEFields();
		}
		else
		{
			$arFields = $entityClass::getMap();
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
		
		if($allowRefs) $arRelTitles = $this->GetEntityRelTitles($entityClassAlt);
		else $arRelTitles = array();
		
		$arRefs = array();
		foreach($arFields as $k=>$v)
		{
			if(strpos($k, 'HIDDEN_FIELD'))
			{
					unset($arFields[$k]);
					continue;
			}
			$v2 = $v;
			if(is_object($v))
			{
				if($v2 instanceof \Bitrix\Main\ORM\Fields\Relations\OneToMany)
				{
					unset($arFields[$k]);
					continue;
				}
				
				if($v2 instanceof Entity\ReferenceField)
				{
					if($allowRefs)
					{
						$arRefs[$k] = array(
							'items' => $this->GetEntityFieldsDirect($v2->getRefEntity()->getDataClass(), $this->GetEntityAllowRefs($v2->getRefEntity()->getDataClass()), $level + 1),
							'title' => (isset($arRelTitles[$k]) ? $arRelTitles[$k]/*.' ['.$k.']'*/ : $this->GetReferenceFieldTitle($v2, $arFieldTitles)),
							'class' => $v2->getRefEntity()->getDataClass()
						);
					}
					if(is_array($v2->getReference()) && ($arKeys = preg_grep('/^=this\./', array_keys($v2->getReference()))) && count($arKeys) > 0)
					{
						$key = substr(current($arKeys), 6);
						if(isset($arFields[$key])) $arFields[$key]['rel_class'] = $v2->getRefEntity()->getDataClass();
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
					//if($allowRefs && in_array($v2['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text', 'date', 'datetime', 'enum', 'float')))
					if($allowRefs)
					{
						if(!class_exists($v2['data_type']))
						{
							$module = ToLower(current(explode('\\', substr($entityClass, strlen('\Bitrix\\')))));
							if(strlen($module) > 0) Loader::includeModule($module);
						}
						if(!class_exists($v2['data_type']))
						{
							$v2['data_type'] = \Bitrix\Main\Entity\Base::normalizeEntityClass('\\'.ltrim($v2['data_type'], '\\'));
						}
						if(!class_exists($v2['data_type']))
						{
							if(strpos($entityClass, '\\')!==false)
							{
								$v2['data_type'] = \Bitrix\Main\Entity\Base::normalizeEntityClass(preg_replace('/\\\[^\\\]*$/', '\\'.ltrim($v2['data_type'], '\\'), (is_callable(array($entityClass, 'GetParentClass')) ? $entityClass::GetParentClass() : $entityClass)));
							}
						}
						
						if(class_exists($v2['data_type']))
						{
							$refField = new Entity\ReferenceField($k, $v2['data_type'], $v2['reference']);
							$arRefs[$k] = array(
								'items' => $this->GetEntityFieldsDirect($refField->getRefEntity()->getDataClass(), (isset($v2['ALLOW_REFS']) ? $v2['ALLOW_REFS'] : false), $level + 1),
								'title' => (isset($arRelTitles[$k]) ? $arRelTitles[$k]/*.' ['.$k.']'*/ : $this->GetReferenceFieldTitle($refField, $arFieldTitles))
							);
						}
					}
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
			if(in_array($v['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text')))
			{
				$arFields[$k]['uid'] = 'Y';
			}
			if((isset($arFields[$k]['primary']) && $arFields[$k]['primary']) || $level > 0)
			{
				$link = $this->GetEntityLink($entityClass);
				if($link) $arFields[$k]['page_url'] = $link;
			}
		}

		foreach($arRefs as $k=>$v)
		{
			if(!empty($arAllowRefs) && !in_array($k, $arAllowRefs)) continue;
			$arFields[$k] = $v;
		}
		@ob_end_clean();
		return $arFields;
	}
	
	public function GetEntityLink($entityClass)
	{
		$arLinks = array(
			'\Bitrix\Sale\Internals\PaymentTable' => 'sale_order_payment_edit.php?order_id=#ORDER_ID#&payment_id=#ID#&lang='.LANG,
			'\Bitrix\Sale\Internals\OrderTable' => 'sale_order_edit.php?ID=#ID#&lang='.LANG,
			'\Bitrix\Main\UserTable' => 'user_edit.php?ID=#ID#&lang='.LANG,
			'\Bitrix\Sale\Internals\PaySystemActionTable' => 'sale_pay_system_edit.php?ID=#ID#&lang='.LANG,
			'\Bitrix\Sale\Internals\OrderTable' => 'sale_order_edit.php?ID=#ID#&lang='.LANG
		);
		if(isset($arLinks[$entityClass])) return $arLinks[$entityClass];
		if(isset($arLinks[str_replace('\EsolIE', '\\', $entityClass)])) return $arLinks[str_replace('\EsolIE', '\\', $entityClass)];
		else return '';
	}
	
	public function GetEntityFields()
	{	
		if(!isset($this->eFields))
		{
			$this->eFields = $this->GetEntityFieldsDirect();
		}
		return $this->eFields;
	}
	
	public function GetEntityFieldsForFilter()
	{
		$arFields = $this->GetEntityFields();
		$arRels = array();
		foreach($arFields as $k=>$v)
		{
			if(isset($v['items']) && is_array($v['items']))
			{
				foreach($v['items'] as $k2=>$v2)
				{
					if(strlen($v2['title'])==0) continue;
					$v2['title'] = $v['title'].' / '.$v2['title'];
					if(isset($v2['items']) && is_array($v2['items']))
					{
						foreach($v2['items'] as $k3=>$v3)
						{
							if(strlen($v3['title'])==0) continue;
							$v3['title'] = $v2['title'].' / '.$v3['title'];
							$arRels[$k.'.'.$k2.'.'.$k3] = $v3;
						}
					}
					else
					{
						$arRels[$k.'.'.$k2] = $v2;
					}
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
					$this->GetFieldsDirect($arGroups, $ar['items'], ($group!='__BASE__' ? $group.'.' : '').$k, $ar['title']);
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
	
	public function GetSortableFields()
	{
		if(!$this->aSortableFields)
		{
			$this->aSortableFields = array();
			foreach($this->GetEntityFieldsForFilter() as $k=>$ar)
			{
				if(in_array($ar['data_type'], array('string', 'integer', 'boolean', 'datetime', 'text', 'date', 'datetime', 'enum', 'float')))
				{
					$this->aSortableFields[] = $k;
				}
			}
		}
	
		return $this->aSortableFields;
	}
	
	public function IsPictureField($field, $checkType=false)
	{
		$arFieldParts = explode('.', $field);
		if(strpos(end($arFieldParts), 'PICTURE')!=false)
		{
			if($checkType)
			{
				$arFields = $this->GetEntityFields();
				while(count($arFieldParts) > 1 && ($arFields = $arFields[array_shift($arFieldParts)]) && is_array($arFields) && is_array($arFields['items']))
				{
					$arFields = $arFields['items'];
				}
				if(is_array($arFields) && ($arField = $arFields[array_shift($arFieldParts)]) && is_array($arField) && $arField['data_type']=='integer')
				{
					return true;
				}
			}
			else return true;
		}
		return false;
	}
	
	public function ShowSelectFields($fname, $value="", $arParams=array())
	{
		$arGroups = $this->GetFields();
		if($arParams['MULTIPLE']){?><select name="<?echo $fname;?>" multiple><?}
		else{?><select name="<?echo $fname;?>"><option value=""><?echo Loc::getMessage("KDA_EE_CHOOSE_FIELD");?></option><?}
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
}
?>