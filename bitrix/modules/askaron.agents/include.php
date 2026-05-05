<?php
###################################################
# askaron.agents module
# Copyright (c) 2011-2023 Askaron Systems ltd.
# http://askaron.ru
# mailto:mail@askaron.ru
###################################################

class CAskaronAgents
{
	public static function OnPageStartHandler()
	{
//      Moved to OnAfterEpilog
//		if ( COption::GetOptionString("main", "check_agents", "Y") !== "Y")
//		{
//			$path = "/bitrix/modules/main/tools/cron_events.php";
//			if ( self::CheckPath( $path ) )
//			{
//				self::CheckAgents();
//			}
//		}
	}

	public static function OnAfterEpilog()
	{
		if ( COption::GetOptionString("main", "check_agents", "Y") !== "Y")
		{
			$path = "/bitrix/modules/main/tools/cron_events.php";
			if ( self::CheckPath( $path ) )
			{
				self::CheckAgents();
			}
		}
	}

	private static function CheckPath($path)
	{
		global $APPLICATION;

		$result = false;
		$curPage = $APPLICATION->GetCurPage(true);

		$result =
			($_SERVER["SCRIPT_FILENAME"] === $_SERVER["DOCUMENT_ROOT"].$path)
				||
			( $curPage === $path )
				||
			(
				( php_sapi_name() == 'cli' )
					&&
				( mb_substr( $curPage, mb_strlen( $curPage ) - mb_strlen( $path )  ) == $path )
			);

		return $result;
	}

	private static function CheckAgents()
	{
		if (version_compare(SM_VERSION, "22.100.100") >= 0)
		{
			// new >= 22.100.100

			//For a while agents will execute only on primary cluster group
			if((defined("NO_AGENT_CHECK") && NO_AGENT_CHECK===true) || (defined("BX_CLUSTER_GROUP") && BX_CLUSTER_GROUP !== 1))
				return null;

			return \Askaron\Agents\Agent::ExecuteAgents();
		}
		else
		{
			// old
			global $CACHE_MANAGER;

			//For a while agents will execute only on primary cluster group
			if((defined("NO_AGENT_CHECK") && NO_AGENT_CHECK===true) || (defined("BX_CLUSTER_GROUP") && BX_CLUSTER_GROUP !== 1))
				return null;


			if(CACHED_b_agent !== false && $CACHE_MANAGER->Read(CACHED_b_agent, ($cache_id = "agents"), "agents"))
			{
				$saved_time = $CACHE_MANAGER->Get($cache_id);
				if(time() < $saved_time)
					return "";
			}

			return \CAgent::ExecuteAgents("");
		}
	}
}
?>