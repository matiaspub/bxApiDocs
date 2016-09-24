<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


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
class CAllAgent
{
	
	/**
	* <p>Метод регистрирует новую функцию-агента. Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $name  PHP строка для запуска функции-агента.
	*
	* @param string $module = "" Идентификатор модуля. Необходим для подключения файлов
	* модуля.<br>Необязательный. По умолчанию пустой.
	*
	* @param string $period = "N" <p>Если значение - "Y", то очередная дата запуска агента (<i>next_exec</i>)
	* будет рассчитываться как:</p> <pre bgcolor="#323232" style="padding:5px;"><i>next_exec</i> = <i>next_exec</i> + <i>interval</i></pre>
	* 	Т.е. при очередном запуске, если прошло уже больше времени чем
	* указано в параметре <i>interval</i>, агент сначала будет запускаться
	* ровно столько раз сколько он должен был запуститься (т.е. столько
	* раз сколько он "пропустил"), а затем, когда <i>next_exec</i> достигнет либо
	* превысит текущую дату, он будет в дальнейшем запускаться с
	* периодичностью указанной в параметре <i>interval</i>. Как правило,
	* подобное используется в агентах которые должны гарантированно
	* запуститься определённое количество раз. 	 	<p>Если значение - "N", то
	* очередная дата запуска агента (<i>next_exec</i>) будет рассчитываться
	* как:</p> <pre bgcolor="#323232" style="padding:5px;"><i>next_exec</i> = дата последнего запуска + <i>interval</i></pre>Т.е.
	* агент после первого запуска будет в дальнейшем запускаться с
	* периодичностью указанной в параметре <i>interval</i>. 	Параметр
	* необязательный, по умолчанию - "N".
	*
	* @param int $interval = 86400 Интервал (в секундах), с какой периодичностью запускать агента.<br>
	* Необязательный. По умолчанию - 86400 (1 сутки).
	*
	* @param string $datecheck = "" Дата первой проверки "не пора ли запустить агент" в формате
	* текущего языка.<br> Необязательный. По умолчанию - текущее время.
	*
	* @param string $active = "Y" Активность агента (Y|N).<br> Необязательный. По умолчанию - "Y"
	* (активен).
	*
	* @param string $next_exec = "" Дата первого запуска агента в формате текущего
	* языка.<br>Необязательный. По умолчанию - текущее время.
	*
	* @param int $sort = 100 Индекс сортировки позволяющий указать порядок запуска данного
	* агента относительно других агентов для которых подошло время
	* запуска.<br>Необязательный. По умолчанию - 100.
	*
	* @return mixed <p>При успешном выполнении, возвращает ID вновь добавленного
	* агента, иначе - <i>false</i>. Если агент ничего не возвращает, он
	* удаляется. Как правило он должен вернуть вызов самого себя.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // добавим агент модуля "Статистика"
	* <b>CAgent::AddAgent</b>(
	*     "CStatistic::CleanUpStatistics_2();", // имя функции
	*     "statistic",                          // идентификатор модуля
	*     "N",                                  // агент не критичен к кол-ву запусков
	*     86400,                                // интервал запуска - 1 сутки
	*     "07.04.2005 20:03:26",                // дата первой проверки на запуск
	*     "Y",                                  // агент активен
	*     "07.04.2005 20:03:26",                // дата первого запуска
	*     30);
	* ?&gt;
	* &lt;?
	* // добавим агент модуля "Техподдержка"
	* <b>CAgent::AddAgent</b>(
	*     "CTicket::AutoClose();",  // имя функции
	*     "support",                // идентификатор модуля
	*     "N",                      // агент не критичен к кол-ву запусков
	*     86400,                    // интервал запуска - 1 сутки
	*     "",                       // дата первой проверки - текущее
	*     "Y",                      // агент активен
	*     "",                       // дата первого запуска - текущее
	*     30);
	* ?&gt;
	* &lt;?
	* // добавим произвольный агент не принадлежащий ни одному модулю
	* <b>CAgent::AddAgent</b>("My_Agent_Function();");
	* ?&gt;
	* 
	* &lt;?
	* // файл /bitrix/php_interface/init.php
	* 
	* function My_Agent_Function()
	* {
	*    // выполняем какие-либо действия
	*    return "My_Agent_Function();";
	* }
	* ?&gt;
	* &lt;?
	* // добавим произвольный агент принадлежащий модулю
	* // с идентификатором my_module
	* 
	* <b>CAgent::AddAgent</b>(
	*    "CMyModule::Agent007(1)", 
	*    "my_module", 
	*    "Y", 
	*     86400);
	* ?&gt;
	* 
	* &lt;?
	* // данный агент будет запущен ровно 7 раз с периодичностью раз в сутки, 
	* // после чего будет удален из таблицы агентов.
	* 
	* Class CMyModule
	* {
	*    function Agent007($cnt=1)
	*    {
	*       echo "Hello!";
	*       if($cnt&gt;=7)
	*          return "";
	*       return "CMyModule::Agent007(".($cnt+1).")";
	*    }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" >Агенты</a>
	* </li> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removeagent.php">CAgent::RemoveAgent</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removemoduleagents.php">CAgent::RemoveModuleAgents</a></li>
	* <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2823" >Структура
	* файлов</a></li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/addagent.php
	* @author Bitrix
	*/
	public static function AddAgent(
		$name, // PHP function name
		$module = "", // module
		$period = "N", // check for agent execution count in period of time
		$interval = 86400, // time interval between execution
		$datecheck = "", // first check for execution time
		$active = "Y", // is the agent active or not
		$next_exec = "", // first execution time
		$sort = 100, // order
		$user_id = false, // user
		$existError = true // return error, if agent already exist
	)
	{
		global $DB, $APPLICATION;

		$z = $DB->Query("
			SELECT ID
			FROM b_agent
			WHERE NAME = '".$DB->ForSql($name, 2000)."'
			AND USER_ID".($user_id? " = ".(int)$user_id: " IS NULL")
		);
		if (!($agent = $z->Fetch()))
		{
			$arFields = array(
				"MODULE_ID" => $module,
				"SORT" => $sort,
				"NAME" => $name,
				"ACTIVE" => $active,
				"AGENT_INTERVAL" => $interval,
				"IS_PERIOD" => $period,
				"USER_ID" => $user_id,
			);
			$next_exec = (string)$next_exec;
			if ($next_exec != '')
				$arFields["NEXT_EXEC"] = $next_exec;

			$ID = CAgent::Add($arFields);
			return $ID;
		}
		else
		{
			if (!$existError)
				return $agent['ID'];

			$e = new CAdminException(array(
				array(
					"id" => "agent_exist",
					"text" => ($user_id
						? Loc::getMessage("MAIN_AGENT_ERROR_EXIST_FOR_USER", array('#AGENT#' => $name, '#USER_ID#' => $user_id))
						: Loc::getMessage("MAIN_AGENT_ERROR_EXIST_EXT", array('#AGENT#' => $name))
					)
				)
			));
			$APPLICATION->throwException($e);
			return false;
		}
	}

	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (CAgent::CheckFields($arFields))
		{
			if (!is_set($arFields, "NEXT_EXEC"))
				$arFields["~NEXT_EXEC"] = $DB->GetNowDate();

			if (CACHED_b_agent !== false)
				$CACHE_MANAGER->CleanDir("agents");

			$ID = $DB->Add("b_agent", $arFields);
			foreach (GetModuleEvents("main", "OnAfterAgentAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(
					$arFields,
				));

			return $ID;
		}
		return false;
	}

	
	/**
	* <p>Метод удаляет функцию-агента из таблицы зарегистрированных агентов. Нестатический метод.</p>
	*
	*
	* @param string $name  Функция-агент.
	*
	* @param string $module = "" Идентификатор модуля. Необязательный. По умолчанию - главный
	* модуль ("main").
	*
	* @param string $user_id = false Идентификатор пользователя. Необязательный.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* <b>CAgent::RemoveAgent</b>("CCatalog::PreGenerateXML(\"yandex\");", "catalog");
	* if ($bNeedAgent)
	* {
	*     CAgent::AddAgent("CCatalog::PreGenerateXML(\"yandex\");", "catalog", "N", 24*60*60, "", "Y");
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436"
	* >Агенты</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removemoduleagents.php">CAgent::RemoveModuleAgents</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/delete.php">CAgent::Delete</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removeagent.php
	* @author Bitrix
	*/
	public static function RemoveAgent($name, $module = "", $user_id = false)
	{
		global $DB;

		if (trim($module) == '')
			$module = "AND (MODULE_ID is null or ".$DB->Length("MODULE_ID")." = 0)";
		else
			$module = "AND MODULE_ID = '".$DB->ForSql($module, 50)."'";

		$strSql = "
				DELETE FROM b_agent
				WHERE NAME = '".$DB->ForSql($name, 2000)."'
				".$module."
				AND  USER_ID".($user_id ? " = ".(int)$user_id : " IS NULL");

		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

	
	/**
	* <p>Метод удаляет функцию-агент из таблицы зарегистрированных агентов. Нестатический метод.</p> <p> </p>
	*
	*
	* @param mixed $intid  ID функции-агента.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if (<b>CAgent::Delete</b>(34)) echo "Агент #34 успешно удален.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436"
	* >Агенты</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removeagent.php">CAgent::RemoveAgent</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removemoduleagents.php">CAgent::RemoveModuleAgents</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/delete.php
	* @author Bitrix
	*/
	public static function Delete($id)
	{
		global $DB;
		$id = intval($id);

		if ($id <= 0)
			return false;

		$DB->Query("DELETE FROM b_agent WHERE ID = ".$id, false, "FILE: ".__FILE__."<br>LINE: ");

		return true;
	}

	
	/**
	* <p>Метод удаляет все функции-агенты указанного модуля из таблицы зарегистрированных агентов. Нестатический метод.</p>
	*
	*
	* @param string $module  Идентификатор модуля.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* <b>CAgent::RemoveModuleAgents</b>("statistic");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436"
	* >Агенты</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removeagent.php">CAgent::RemoveAgent</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cagent/delete.php">CAgent::Delete</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cagent/removemoduleagents.php
	* @author Bitrix
	*/
	public static function RemoveModuleAgents($module)
	{
		global $DB;

		if (strlen($module) > 0)
		{
			$strSql = "DELETE FROM b_agent WHERE MODULE_ID='".$DB->ForSql($module,255)."'";
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
	}

	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ign_name = false;

		$ID = intval($ID);

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";
		if(is_set($arFields, "IS_PERIOD") && $arFields["IS_PERIOD"]!="Y")
			$arFields["IS_PERIOD"]="N";
		if(!is_set($arFields, "NAME"))
			$ign_name = true;

		if(CAgent::CheckFields($arFields, $ign_name))
		{
			if(CACHED_b_agent !== false)
				$CACHE_MANAGER->CleanDir("agents");

			$strUpdate = $DB->PrepareUpdate("b_agent", $arFields);
			$strSql = "UPDATE b_agent SET ".$strUpdate." WHERE ID=".$ID;
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			return $res;
		}

		return false;
	}

	public static function GetById($ID)
	{
		return CAgent::GetList(Array(), Array("ID"=>IntVal($ID)));
	}

	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = array())
	{
		global $DB;
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";

		$arSqlSearch = array();
		$arSqlOrder = array();

		$arOFields = array(
			"ID" => "A.ID",
			"ACTIVE" => "A.ACTIVE",
			"IS_PERIOD" => "A.IS_PERIOD",
			"NAME" => "A.NAME",
			"MODULE_ID" => "A.MODULE_ID",
			"USER_ID" => "A.USER_ID",
			"LAST_EXEC" => "A.LAST_EXEC",
			"AGENT_INTERVAL" => "A.AGENT_INTERVAL",
			"NEXT_EXEC" => "A.NEXT_EXEC",
			"SORT" => "A.SORT"
		);

		if(!is_array($arFilter))
			$filter_keys = array();
		else
			$filter_keys = array_keys($arFilter);

		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			$key = strtoupper($filter_keys[$i]);
			if(strlen($val)<=0 || ($key=="USER_ID" && $val!==false && $val!==null))
				continue;

			switch($key)
			{
				case "ID":
					$arSqlSearch[] = "A.ID=".(int)$val;
					break;
				case "ACTIVE":
					$t_val = strtoupper($val);
					if($t_val == "Y" || $t_val == "N")
						$arSqlSearch[] = "A.ACTIVE='".$t_val."'";
					break;
				case "IS_PERIOD":
					$t_val = strtoupper($val);
					if($t_val=="Y" || $t_val=="N")
						$arSqlSearch[] = "A.IS_PERIOD='".$t_val."'";
					break;
				case "NAME":
					$arSqlSearch[] = "A.NAME LIKE '".$DB->ForSQLLike($val)."'";
					break;
				case "=NAME":
					$arSqlSearch[] = "A.NAME = '".$DB->ForSQL($val)."'";
					break;
				case "MODULE_ID":
					$arSqlSearch[] = "A.MODULE_ID = '".$DB->ForSQL($val)."'";
					break;
				case "USER_ID":
					$arSqlSearch[] = "A.USER_ID ".(IntVal($val)<=0?"IS NULL":"=".IntVal($val));
					break;
				case "LAST_EXEC":
					$arr = ParseDateTime($val, CLang::GetDateFormat());
					if($arr)
					{
						$date2 = mktime(0, 0, 0, $arr["MM"], $arr["DD"]+1, $arr["YYYY"]);
						$arSqlSearch[] = "A.LAST_EXEC>=".$DB->CharToDateFunction($DB->ForSql($val), "SHORT")." AND A.LAST_EXEC<".$DB->CharToDateFunction(ConvertTimeStamp($date2), "SHORT");
					}
					break;
				case "NEXT_EXEC":
					$arr = ParseDateTime($val);
					if($arr)
					{
						$date2 = mktime(0, 0, 0, $arr["MM"], $arr["DD"]+1, $arr["YYYY"]);
						$arSqlSearch[] = "A.NEXT_EXEC>=".$DB->CharToDateFunction($DB->ForSql($val), "SHORT")." AND A.NEXT_EXEC<".$DB->CharToDateFunction(ConvertTimeStamp($date2), "SHORT");
					}
					break;
			}
		}

		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if (isset($arOFields[$by]))
			{
				if ($order != "ASC")
					$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
					$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[] = $arOFields[$by]." ".$order;
			}
		}

		$strSql = "SELECT A.ID, A.MODULE_ID, A.USER_ID, B.LOGIN, B.NAME as USER_NAME, B.LAST_NAME, A.SORT, ".
			"A.NAME, A.ACTIVE, ".
			$DB->DateToCharFunction("A.LAST_EXEC")." as LAST_EXEC, ".
			$DB->DateToCharFunction("A.NEXT_EXEC")." as NEXT_EXEC, ".
			"A.AGENT_INTERVAL, A.IS_PERIOD ".
			"FROM b_agent A LEFT JOIN b_user B ON(A.USER_ID = B.ID)";
		$strSql .= (count($arSqlSearch)>0) ? " WHERE ".implode(" AND ", $arSqlSearch) : "";
		$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	public static function CheckFields(&$arFields, $ign_name = false)
	{
		global $DB, $APPLICATION;

		$errMsg = array();

		if(!$ign_name && (!is_set($arFields, "NAME") || strlen(trim($arFields["NAME"])) <= 2))
			$errMsg[] = array("id" => "NAME", "text" => Loc::getMessage("MAIN_AGENT_ERROR_NAME"));

		if(
			array_key_exists("NEXT_EXEC", $arFields)
			&& (
				$arFields["NEXT_EXEC"] == ""
				|| !$DB->IsDate($arFields["NEXT_EXEC"], false, LANG, "FULL")
			)
		)
		{
			$errMsg[] = array("id" => "NEXT_EXEC", "text" => Loc::getMessage("MAIN_AGENT_ERROR_NEXT_EXEC"));
		}

		if(
			array_key_exists("DATE_CHECK", $arFields)
			&& $arFields["DATE_CHECK"] <> ""
			&& !$DB->IsDate($arFields["DATE_CHECK"], false, LANG, "FULL")
		)
		{
			$errMsg[] = array("id" => "DATE_CHECK", "text" => Loc::getMessage("MAIN_AGENT_ERROR_DATE_CHECK"));
		}

		if(
			array_key_exists("LAST_EXEC", $arFields)
			&& $arFields["LAST_EXEC"] <> ""
			&& !$DB->IsDate($arFields["LAST_EXEC"], false, LANG, "FULL")
		)
		{
			$errMsg[] = array("id" => "LAST_EXEC", "text" => Loc::getMessage("MAIN_AGENT_ERROR_LAST_EXEC"));
		}

		if($arFields["MODULE_ID"] <> '')
			if(!IsModuleInstalled($arFields["MODULE_ID"]))
				$errMsg[] = array("id" => "MODULE_ID", "text" => Loc::getMessage("MAIN_AGENT_ERROR_MODULE"));

		if(!empty($errMsg))
		{
			$e = new CAdminException($errMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}
}
