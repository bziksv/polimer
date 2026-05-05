<?php
namespace Askaron\Agents;

class Agent extends \CAgent
{
	// \Askaron\Agents\Agent::ExecuteAgents(); - executes all agents
	protected static function OnCron()
	{
		return null;
	}

//	protected static function OnCron()
//	{
//		if (COption::GetOptionString('main', 'agents_use_crontab', 'N') == 'Y' || (defined('BX_CRONTAB_SUPPORT') && BX_CRONTAB_SUPPORT === true))
//		{
//			return (defined('BX_CRONTAB') && BX_CRONTAB === true);
//		}
//		return null;
//	}

//	static public function ExecuteAgents()
//	{
//		parent::ExecuteAgents();
//	}
}