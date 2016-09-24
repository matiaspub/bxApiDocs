<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/agent.php");


/**
 * <b>CAgent</b> - класс для работы с функциями-агентами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/index.php
 * @author Bitrix
 */
class CAgent extends CAllAgent
{
	
	/**
	* <p>Выполняет функцию-агента, время запуска которой наступило. Метод автоматически вызывается вначале каждой страницы и не требует ручного запуска. Нестатический метод. </p> <p> </p>
	*
	*
	* @return mixed 
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" >Агенты</a>
	* </li></ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/checkagents.php
	* @author Bitrix
	*/
	public static function CheckAgents()
	{
		global $CACHE_MANAGER;

		//For a while agents will execute only on primary cluster group
		if((defined("NO_AGENT_CHECK") && NO_AGENT_CHECK===true) || (defined("BX_CLUSTER_GROUP") && BX_CLUSTER_GROUP !== 1))
			return null;

		$agents_use_crontab = COption::GetOptionString("main", "agents_use_crontab", "N");
		$str_crontab = "";
		if($agents_use_crontab=="Y" || (defined("BX_CRONTAB_SUPPORT") && BX_CRONTAB_SUPPORT===true))
		{
			if(defined("BX_CRONTAB") && BX_CRONTAB===true)
				$str_crontab = " AND IS_PERIOD='N' ";
			else
				$str_crontab = " AND IS_PERIOD='Y' ";
		}

		if(CACHED_b_agent !== false && $CACHE_MANAGER->Read(CACHED_b_agent, ($cache_id = "agents".$str_crontab), "agents"))
		{
			$saved_time = $CACHE_MANAGER->Get($cache_id);
			if(time() < $saved_time)
				return "";
		}

		return CAgent::ExecuteAgents($str_crontab);
	}

	public static function ExecuteAgents($str_crontab)
	{
		global $DB, $CACHE_MANAGER, $pPERIOD;

		if(defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION"))
		{
			if(CMain::ForkActions(array("CAgent", "ExecuteAgents"), array($str_crontab)))
				return "";
		}

		$saved_time = 0;
		$cache_id = "agents".$str_crontab;
		if(CACHED_b_agent !== false && $CACHE_MANAGER->Read(CACHED_b_agent, $cache_id, "agents"))
		{
			$saved_time = $CACHE_MANAGER->Get($cache_id);
			if(time() < $saved_time)
				return "";
		}

		$uniq = CMain::GetServerUniqID();

		$strSql = "
			SELECT 'x'
			FROM b_agent
			WHERE
				ACTIVE = 'Y'
				AND NEXT_EXEC <= now()
				AND (DATE_CHECK IS NULL OR DATE_CHECK <= now())
				".$str_crontab."
			LIMIT 1
		";

		$db_result_agents = $DB->Query($strSql);
		if($db_result_agents->Fetch())
		{
			$db_lock = $DB->Query("SELECT GET_LOCK('".$uniq."_agent', 0) as L");
			$ar_lock = $db_lock->Fetch();
			if($ar_lock["L"]=="0")
				return "";
		}
		else
		{
			if(CACHED_b_agent !== false)
			{
				$rs = $DB->Query("SELECT UNIX_TIMESTAMP(MIN(NEXT_EXEC))-UNIX_TIMESTAMP(NOW()) DATE_DIFF FROM b_agent WHERE ACTIVE='Y' ".$str_crontab."");
				$ar = $rs->Fetch();
				if(!$ar || $ar["DATE_DIFF"] < 0)
					$date_diff = 0;
				elseif($ar["DATE_DIFF"] > CACHED_b_agent)
					$date_diff = CACHED_b_agent;
				else
					$date_diff = $ar["DATE_DIFF"];

				if($saved_time > 0)
				{
					$CACHE_MANAGER->Clean($cache_id, "agents");
					$CACHE_MANAGER->Read(CACHED_b_agent, $cache_id, "agents");
				}
				$CACHE_MANAGER->Set($cache_id, intval(time()+$date_diff));
			}

			return "";
		}

		$strSql=
			"SELECT ID, NAME, AGENT_INTERVAL, IS_PERIOD, MODULE_ID ".
			"FROM b_agent ".
			"WHERE ACTIVE='Y' ".
			"	AND NEXT_EXEC<=now() ".
			"	AND (DATE_CHECK IS NULL OR DATE_CHECK<=now()) ".
			$str_crontab.
			" ORDER BY RUNNING ASC, SORT desc";

		$db_result_agents = $DB->Query($strSql);
		$ids = '';
		$agents_array = array();
		while($db_result_agents_array = $db_result_agents->Fetch())
		{
			$agents_array[] = $db_result_agents_array;
			$ids .= ($ids <> ''? ', ':'').$db_result_agents_array["ID"];
		}
		if($ids <> '')
		{
			$strSql = "UPDATE b_agent SET DATE_CHECK=DATE_ADD(IF(DATE_CHECK IS NULL, now(), DATE_CHECK), INTERVAL 600 SECOND) WHERE ID IN (".$ids.")";
			$DB->Query($strSql);
		}

		$DB->Query("SELECT RELEASE_LOCK('".$uniq."_agent')");

		$logFunction = (defined("BX_AGENTS_LOG_FUNCTION") && function_exists(BX_AGENTS_LOG_FUNCTION)? BX_AGENTS_LOG_FUNCTION : false);

		for($i = 0, $n = count($agents_array); $i < $n; $i++)
		{
			$arAgent = $agents_array[$i];

			if ($logFunction)
				$logFunction($arAgent, "start");

			@set_time_limit(0);
			ignore_user_abort(true);

			if(strlen($arAgent["MODULE_ID"])>0 && $arAgent["MODULE_ID"]!="main")
			{
				if(!CModule::IncludeModule($arAgent["MODULE_ID"]))
					continue;
			}

			//update the agent to the running state - if it fails it'll go to the end of the list on the next try
			$DB->Query("UPDATE b_agent SET RUNNING='Y' WHERE ID=".$arAgent["ID"]);

			//these vars can be assigned within agent code
			$pPERIOD = $arAgent["AGENT_INTERVAL"];

			CTimeZone::Disable();

			global $USER;
			unset($USER);
			try
			{
				$eval_result = "";
				$e = eval("\$eval_result=".$arAgent["NAME"]);
			}
			catch (Exception $e)
			{
				CTimeZone::Enable();

				$application = \Bitrix\Main\Application::getInstance();
				$exceptionHandler = $application->getExceptionHandler();
				$exceptionHandler->writeToLog($e);

				continue;
			}
			unset($USER);

			CTimeZone::Enable();

			if ($logFunction)
				$logFunction($arAgent, "finish", $eval_result, $e);

			if($e === false)
			{
				continue;
			}
			elseif(strlen($eval_result)<=0)
			{
				$strSql = "DELETE FROM b_agent WHERE ID=".$arAgent["ID"];
			}
			else
			{
				$strSql = "
					UPDATE b_agent SET
						NAME='".$DB->ForSQL($eval_result, 2000)."',
						LAST_EXEC=now(),
						NEXT_EXEC=DATE_ADD(".($arAgent["IS_PERIOD"]=="Y"? "NEXT_EXEC" : "now()").", INTERVAL ".$pPERIOD." SECOND),
						DATE_CHECK=NULL,
						RUNNING='N'
					WHERE ID=".$arAgent["ID"];
			}
			$DB->Query($strSql);
		}
		return null;
	}
}
