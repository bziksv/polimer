<?php
namespace Bitrix\EsolAie\Export;

use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Filter {
	protected static $sectionStruct = array();
	protected static $propTypes = array();
	
	public function __construct($entityId=false)
	{
		$this->entityId = $entityId;
	}
	
	public function SetFilter(&$arFullFilter, $arFields, $fl)
	{
		if(!is_array($arFields)) $arFields = array();
		$arFilter = array();
		$arSubFilters = array();
		
		$arFieldsKeys = array_keys($fl->GetEntityFieldsForFilter());
		
		foreach($arFields as $ffKey=>$arField)
		{
			$group = false;
			if(strpos($ffKey, '_')!==false)
			{
				$group = true;
				$ffSubKey = preg_replace('/_[^_]*$/', '', $ffKey);
				if(!array_key_exists($ffSubKey, $arSubFilters)) $arSubFilters[$ffSubKey] = array();
				$ffEndKey = count($arSubFilters[$ffSubKey]);
				if($arField['FIELD']=='GROUP')
				{
					$f = &$arSubFilters[$ffSubKey];
				}
				else
				{
					$arSubFilters[$ffSubKey][$ffEndKey] = array();
					$f = &$arSubFilters[$ffSubKey][$ffEndKey];
				}
			}
			else $f = &$arFilter;
			
			if(strpos($arField['COND'], 'LAST_N_DAYS')!==false)
			{
				$time = time() - $this->GetFloatVal($arField['VALUE'])*24*60*60;
				if($time > 0) $arField['VALUE'] = ConvertTimeStamp($time, 'FULL');
			}
			
			$fieldName = '';
			if($arField['FIELD']=='GROUP')
			{
				if(!array_key_exists($ffKey, $arSubFilters)) $arSubFilters[$ffKey] = array();
				$arSubFilters[$ffKey]['LOGIC'] = ($arField['COND']=='ALL' ? 'AND' : 'OR');
				$f[] = &$arSubFilters[$ffKey];
				continue;
			}
			else
			{
				$fieldName = $arField['FIELD'];
			}
			if(strlen($fieldName)==0 || !in_array($fieldName, $arFieldsKeys)) continue;
			
			$key = $fieldName;
			$val = $arField['VALUE'];
			
			if($arField['COND']=='EQ'){$key = '='.$key;}
			elseif($arField['COND']=='NEQ'){$key = '!='.$key;}
			elseif($arField['COND']=='LT'){$key = '<'.$key;}
			elseif($arField['COND']=='LEQ' || $arField['COND']=='NOT_LAST_N_DAYS'){$key = '<='.$key;}
			elseif($arField['COND']=='GT'){$key = '>'.$key;}
			elseif($arField['COND']=='GEQ' || $arField['COND']=='LAST_N_DAYS'){$key = '>='.$key;}
			elseif($arField['COND']=='CONTAINS'){$key = '%'.$key;}
			elseif($arField['COND']=='NOT_CONTAINS'){$key = '!%'.$key;}
			elseif($arField['COND']=='BEGIN_WITH'){$val = $val.'%';}
			elseif($arField['COND']=='ENDS_WITH'){$val = '%'.$val;}
			elseif($arField['COND']=='EMPTY'){$val = false;}
			elseif($arField['COND']=='NOT_EMPTY'){$key = '!'.$key; $val = false;}
			elseif(in_array($arField['COND'], array('DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR'))){$this->SetDateField($val, $key, $arField);}
			
			if(isset($f[$key]))
			{
				if(!is_array($f[$key]) || !array_key_exists('LOGIC', $f[$key])) $f[$key] = array('LOGIC'=>'AND', array($key => $f[$key]));
				$f[$key][] = array($key => $val);
			}
			else
			{
				if($group && is_array($val) && array_key_exists('LOGIC', $val)) $f = $val;
				else $f[$key] = $val;
			}
		}

		foreach($arFilter as $k=>$v)
		{
			if(!is_numeric($k) && is_array($v) && isset($v['LOGIC']))
			{
				unset($arFilter[$k]);
				$arFilter[] = $v;
			}
		}

		$arFullFilter = array_merge($arFilter, $arFullFilter);
	}
	
	public function PreparePropValue($value, $propId)
	{
		if(is_array($value))
		{
			foreach($value as $k=>$v)
			{
				if($v==='') $value[$k] = false;
			}
		}
		elseif($value==='') $value = false;
		
		/*Date check*/
		if(in_array(self::GetPropType($propId), array('S:Date', 'S:DateTime')) && !is_array($value)
			&& preg_match('/^'.preg_quote(preg_replace('/\d/', '0', $value), '/').'/', preg_replace('/\w/', '0', CSite::GetDateFormat('FULL'))) 
			)
		{
			$value = ConvertDateTime($value, 'YYYY-MM-DD HH:MI:SS');
		}
		/*/Date check*/
		
		return $value;
	}
	
	public static function GetPropType($propId)
	{
		if(!array_key_exists($propId, self::$propTypes))
		{
			$type = '';
			if(class_exists('\Bitrix\Iblock\PropertyTable') && ($arProp = \Bitrix\Iblock\PropertyTable::getList(array('filter'=>array('ID'=>$propId), 'select'=>array('PROPERTY_TYPE', 'USER_TYPE')))->Fetch()))
			{
				$type = $arProp['PROPERTY_TYPE'];
				if(strlen($arProp['USER_TYPE']) > 0) $type .= ':'.$arProp['USER_TYPE'];
			}
			self::$propTypes[$propId] = $type;
		}
		return self::$propTypes[$propId];
	}
	
	public function SetDateField(&$val, $key, $arField)
	{
		$time = time();
		$d1 = $d2 = (int)date('j', $time);
		$m1 = $m2 = (int)date('n', $time);
		$y1 = $y2 = (int)date('Y', $time);
		$x1 = $x2 = false;
		$ratio = 1;
		
		if($arField['COND']=='DAY')
		{
			$x1 = &$d1;
			$x2 = &$d2;
		}
		elseif($arField['COND']=='WEEK')
		{
			$x1 = &$d1;
			$x2 = &$d2;
			$ratio = 7;
			$x1 = $x1 - (int)date('N', $time) + 1;
			$x2 = $x2 - (int)date('N', $time) + 7;
		}
		elseif($arField['COND']=='MONTH')
		{
			$x1 = &$m1;
			$x2 = &$m2;
			$x2 = $x2 + 1;
			$d1 = 1;
			$d2 = 0;
		}
		elseif($arField['COND']=='QUARTER')
		{
			$x1 = &$m1;
			$x2 = &$m2;
			$ratio = 3;
			$q = ceil($x1/3);
			$x1 = ($q-1)*3 + 1;
			$x2 = ($q-1)*3 + 4;
			$d1 = 1;
			$d2 = 0;
		}
		elseif($arField['COND']=='YEAR')
		{
			$x1 = &$y1;
			$x2 = &$y2;
			$d1 = 1;
			$d2 = 31;
			$m1 = 1;
			$m2 = 12;
		}
		
		if($val=='previous') {$x1 = $x1 - $ratio; $x2 = $x2 - $ratio;}
		elseif($val=='next') {$x1 = $x1 + $ratio; $x2 = $x2 + $ratio;}
		
		$v1 = ConvertTimeStamp(mktime(0, 0, 0, $m1, $d1, $y1), "PART");
		$v2 = ConvertTimeStamp(mktime(23, 59, 59, $m2, $d2, $y2), "FULL");
		if(strpos($key, 'PROPERTY_')!==false)
		{
			$v1 = ConvertDateTime($v1, 'YYYY-MM-DD HH:MI:SS');
			$v2 = ConvertDateTime($v2, 'YYYY-MM-DD HH:MI:SS');
		}
		$val = array(
			'LOGIC'=>'AND',
			array('>='.$key => $v1),
			array('<='.$key => $v2)
		);
	}
	
	public function GetFloatVal($val)
	{
		$val = floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
		return $val;
	}
	
	public function GetFieldType(&$arField)
	{
		if(!isset($arField['data_type'])) return;
		$type = ToLower($arField['data_type']);
		if($type=='datetime') $type = 'date';
		elseif($arField['rel_class'] && is_callable(array($arField['rel_class'], 'getTableName')))
		{
			$tblName = call_user_func(array($arField['rel_class'], 'getTableName'));
			if(in_array($tblName, array('b_sale_status_lang')))
			{
				$type = 'list';
			}
		}
		$arField['data_type'] = $type;
		//return $type;
	}
	
	public function ShowSelectFilterFields($fl, $arFilter)
	{
		$arFields = array();
		$arGroups = $fl->GetEntityFields();
		?><select name="S_FIELD"><option value=""><?echo Loc::getMessage("KDA_EE_CHOOSE_FIELD");?></option><option value="GROUP"><?echo Loc::getMessage("KDA_EE_FI_GROUP_COND");?></option><?
		$this->ShowSelectFilterFieldsOptions($arFields, $arGroups, '', Loc::getMessage("KDA_EE_GENERAL_FIELDS"));
		?></select><?
		foreach($arFilter as $k=>$v)
		{
			if(isset($arFields[$v['FIELD']]) && isset($arFields[$v['FIELD']]['data_type']) && in_array($arFields[$v['FIELD']]['data_type'], array('list')))
			{
				$arValues = $this->GetListValues($v['FIELD'], $fl);
				echo '<input type="hidden" name="FVALS_'.htmlspecialcharsbx($v['FIELD']).'" value="'.htmlspecialcharsbx(\Bitrix\EsolAie\Utils::PhpToJSObject($arValues)).'">';
			}
		}
	}
	
	public function ShowSelectFilterFieldsOptions(&$arFields, $arGroups, $key, $title)
	{
		$groupOption = '';
		foreach($arGroups as $k2=>$v2)
		{
			if($v2['data_type'] && !$v2['items'])
			{
				$this->GetFieldType($v2);
				$k2 = (strlen($key) > 0 ? $key.'.' : '').$k2;
				$arFields[$k2] = $v2;
				$groupOption .= '<option value="'.$k2.'" '.($k2==$value ? 'selected' : '').' data-type="'.ToUpper(htmlspecialcharsbx($v2['data_type'])).'">'.htmlspecialcharsbx($v2['title']).'</option>';
			}
		}
		if(strlen($groupOption) > 0) echo '<optgroup label="'.$title.'">'.$groupOption.'</optgroup>';
		foreach($arGroups as $k2=>$v2)
		{
			if($v2['items'] && is_array($v2['items']))
			{
				$this->ShowSelectFilterFieldsOptions($arFields, $v2['items'], (strlen($key) > 0 ? $key.'.' : '').$k2, $v2['title']);
			}
		}
	}
	
	public function ShowFilterBlock($fid, $arFilter, $fl)
	{
		if(!is_array($arFilter)) $arFilter = array();
		?>
		<div class="kda-ee-sheet-cfilter" id="<?echo $fid;?>">
			<div class="kda-ee-sheet-cfilter-hidden">
				<input type="hidden" name="OLD_FILTER" value="<?echo (count($arFilter) > 0 ? htmlspecialcharsbx(\Bitrix\EsolAie\Utils::PhpToJSObject($arFilter)) : '');?>">
				<input type="hidden" name="ENTITY_ID" value="<?echo htmlspecialcharsbx($this->entityId);?>">
				<?$this->ShowSelectFilterFields($fl, $arFilter);?>
			</div>
			<div class="kda-ee-cfilter-field-list"></div>
			<a class="kda-ee-cfilter-add-field" href="javascript:void(0)"><?echo Loc::getMessage('KDA_EE_FILTER_ADD_FIELD');?></a>
		</div>
		<?
	}
	
	public function GetSectionsStruct(&$arValues, &$arSections, $pName, $key)
	{
		if(!array_key_exists($key, $arSections) || !is_array($arSections[$key])) return;
		$pName2 = (strlen($pName) > 0 ? $pName.' > ' : '');
		foreach($arSections[$key] as $k=>$v)
		{
			$arValues[$v['ID']] = $pName2.$v['NAME'];
			$this->GetSectionsStruct($arValues, $arSections, $pName2.$v['NAME'], $v['ID']);
		}
	}
	
	public function GetListValues($field, $fl)
	{
		$arValues = array();
		$allowNew = false;
		$ajaxMode = false;
		$inputHTML = $inputFromHTML = '';
		
		$arGroups = $fl->GetEntityFieldsForFilter();
		if(isset($arGroups[$field]) && ($className = $arGroups[$field]['rel_class']) && is_callable(array($className, 'getTableName')))
		{
			$tblName = call_user_func(array($className, 'getTableName'));
			if($tblName=='b_sale_status_lang')
			{
				$dbRes = call_user_func(array($className, 'getList'), array('filter'=>array('LID'=>LANGUAGE_ID), 'select'=>array('STATUS_ID', 'NAME')));
				while($arr = $dbRes->Fetch())
				{
					$arValues[$arr['STATUS_ID']] = '['.$arr['STATUS_ID'].'] '.$arr['NAME'];
				}
			}
		}
		
		$arNewValues = array();
		foreach($arValues as $k=>$v)
		{
			$arNewValues[] = array('key'=>$k, 'value'=>$v);
		}
		
		if(strlen($inputHTML) > 0 && class_exists('\Bitrix\Main\Page\Asset') && class_exists('\Bitrix\Main\Page\AssetShowTargetType'))
		{
			$inputHTML = \Bitrix\Main\Page\Asset::getInstance()->GetJs(\Bitrix\Main\Page\AssetShowTargetType::TEMPLATE_PAGE).\Bitrix\Main\Page\Asset::getInstance()->GetCss(\Bitrix\Main\Page\AssetShowTargetType::TEMPLATE_PAGE).$inputHTML;
		}
		
		return array(
			'allownew' => ($allowNew ? 1 : 0), 
			'ajaxmode' => ($ajaxMode ? 1 : 0), 
			'inputhtml' => $inputHTML,
			'inputfromhtml' => $inputFromHTML,
			'values' => $arNewValues
		);
	}
}
?>