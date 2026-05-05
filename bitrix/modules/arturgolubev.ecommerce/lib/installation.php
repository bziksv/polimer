<?
if (!class_exists('agInstaHelperEcommerce')){
	class agInstaHelperEcommerce {
		const MODULE_ID = 'arturgolubev.ecommerce';
		
		static function IncludeAdminFile($m, $p){
			global $APPLICATION, $DOCUMENT_ROOT;
			$APPLICATION->IncludeAdminFile($m, $DOCUMENT_ROOT.$p);
		}
		
		static function addGadgetToDesctop($gadget_id){
			if(!defined("NO_INSTALL_MWATCHER") && class_exists('CUserOptions')){
				$desctops = \CUserOptions::GetOption('intranet', '~gadgets_admin_index', false, false);
				if(is_array($desctops) && !empty($desctops[0])){
					$skip = 0;
					foreach($desctops[0]['GADGETS'] as $gid => $gsett){
						if(strstr($gid, $gadget_id)) $skip = 1;
					}
					
					if(!$skip){
						foreach($desctops[0]['GADGETS'] as $gid => $gsett){
							if($gsett['COLUMN'] == 0){
								$desctops[0]['GADGETS'][$gid]['ROW']++;
							}
						}
						
						$gid_new = $gadget_id."@".rand();
						$desctops[0]['GADGETS'][$gid_new] = array('COLUMN' => 0, 'ROW' => 0);
						
						\CUserOptions::SetOption('intranet', '~gadgets_admin_index', $desctops, false, false);
					}
				}
			}
		}
		
		static function checkOrderStructure($collect_utm, $collect_client_id){
			if(!CModule::IncludeModule("sale")){
				return 0;
			}
			
			IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/installation.php");
			
			$arFieldsAdd = [];
			
			if($collect_utm == 'Y'){
				$arFieldsAdd['AGEC_UTM_SOURCE'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_UTM_SOURCE")
				];
				$arFieldsAdd['AGEC_UTM_MEDIUM'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_UTM_MEDIUM")
				];
				$arFieldsAdd['AGEC_UTM_CAMPAIGN'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_UTM_CAMPAIGN")
				];
				$arFieldsAdd['AGEC_UTM_CONTENT'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_UTM_CONTENT")
				];
				$arFieldsAdd['AGEC_UTM_TERM'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_UTM_TERM")
				];
			}

			if($collect_client_id == 'Y'){
				$arFieldsAdd['AGEC_YM_CLIENTID'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_YM_CLIENTID")
				];
				$arFieldsAdd['AGEC_GA_CLIENTID'] = [
					"TYPE" => 'TEXT',
					"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_GA_CLIENTID")
				];
			}
			
			if(count($arFieldsAdd)){
				$personTypes = [];
				
				$db_ptype = \CSalePersonType::GetList(Array("SORT" => "ASC"), Array('ACTIVE' => 'Y'));
				while ($ptype = $db_ptype->Fetch()){
					$db_propsGroup = \CSaleOrderPropsGroup::GetList(
						array("SORT" => "ASC"),
						array("PERSON_TYPE_ID" => $ptype["ID"], "NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_GROUP")),
						false, false, array()
					);
					
					if($propsGroup = $db_propsGroup->Fetch()){
						$pgid = $propsGroup["ID"];
					}else{
						$pgid = \CSaleOrderPropsGroup::Add(array(
							"PERSON_TYPE_ID" => $ptype["ID"],
							"NAME" => GetMessage("ARTURGOLUBEV_EC_INSTALL_OPROP_GROUP"),
							"SORT" => 110,
						));
					}
					
					$personTypes[$ptype["ID"]] = $pgid;
				}
				
				foreach($personTypes as $pid=>$pgid){
					$arExistProps = [];
					$db_props = \CSaleOrderProps::GetList(array("SORT" => "ASC"), array("PERSON_TYPE_ID" => $pid), false, false, array());
					while ($props = $db_props->Fetch()){
						$arExistProps[$props["CODE"]] = $props["ID"];
					}
					
					$SORT = 1000;
					foreach($arFieldsAdd as $k=>$arField){
						$SORT += 10;
						
						if($arExistProps[$k]) continue;
						
						$arField["SORT"] = $SORT;
						$arField["CODE"] = $k;
						$arField["PERSON_TYPE_ID"] = $pid;
						$arField["PROPS_GROUP_ID"] = $pgid;
						$arField["REQUIED"] = "N";
						$arField["UTIL"] = "Y";
												
						$arExistProps[$k] = \CSaleOrderProps::Add($arField);
					}
				}
			}
		}
	}
}
?>