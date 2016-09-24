<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);


/**
 * Класс для работы с логом.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventlog/index.php
 * @author Bitrix
 */
class CEventLog
{
	const SEVERITY_SECURITY = 1;
	const SEVERITY_ERROR = 2;
	const SEVERITY_WARNING = 3;
	const SEVERITY_INFO = 4;
	const SEVERITY_DEBUG = 5;

	
	/**
	* <p>Метод добавляет запись в лог. Нестатический метод.</p>
	*
	*
	* @param mixed $SEVERITY  Степень важности записи. Доступны значения: SECURITY, SECURITY, ERROR, WARNING,
	* INFO, DEBUG для иного система установит UNKNOWN.
	*
	* @param SEVERIT $AUDIT_TYPE_ID  Идентификатор события, к которому относится запись.
	*
	* @param AUDIT_TYPE_I $MODULE_ID  Модуль, к которому относится запись
	*
	* @param MODULE_I $ITEM_ID  ID объекта, в связи с которым происходит добавление (пользователь,
	* элемент ИБ, ID сообщения).
	*
	* @param ITEM_I $DESCRIPTION = false Описание записи лога, или техническая информация.
	* Необязательный. По умолчанию - <i>false</i>.
	*
	* @param mixed $SITE_ID = false Идентификатор сайта, к которому относится запись в логе.
	* Необязательный. По умолчанию - <i>false</i>.
	*
	* @return int 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventlog/log.php
	* @author Bitrix
	*/
	public static function Log($SEVERITY, $AUDIT_TYPE_ID, $MODULE_ID, $ITEM_ID, $DESCRIPTION = false, $SITE_ID = false)
	{
		return CEventLog::Add(array(
			"SEVERITY" => $SEVERITY,
			"AUDIT_TYPE_ID" => $AUDIT_TYPE_ID,
			"MODULE_ID" => $MODULE_ID,
			"ITEM_ID" => $ITEM_ID,
			"DESCRIPTION" => $DESCRIPTION,
			"SITE_ID" => $SITE_ID,
		));
	}

	
	/**
	* <p>Метод добавляет событие для записи в логе событий. Нестатический метод.</p>
	*
	*
	* @param array $fields  Поля добавляемого события. Значения: <ul> <li> <b>SEVERITY</b> - степень
	* важности записи. Доступны значения: SECURITY или WARNING, для иного
	* система установит UNKNOWN.</li> <li> <b>AUDIT_TYPE_ID</b> - собственный ID типа
	* события.</li>  <li> <b>MODULE_ID</b> - модуль, с которого происходит запись в
	* лог.</li>  <li> <b>ITEM_ID</b> - ID объекта, в связи с которым происходит
	* добавление (пользователь, элемент ИБ, ID сообщения, ...)</li> <li>
	* <b>REMOTE_ADDR</b> - IP, с которого обратились.</li>  <li> <b>USER_AGENT</b> - браузер.</li>
	* <li> <b>REQUEST_URI</b> - URL страницы.</li> <li> <b>SITE_ID</b> - ID сайта, к которому
	* относится добавляемое событие.</li>  <li> <b>USER_ID</b> - ID пользователя.</li>
	* <li> <b>GUEST_ID</b> - ID пользователя из модуля статистики</li> <li> <b>DESCRIPTION</b> -
	* собственно описание записи лога, или техническая информация.</li>  
	* </ul>
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* CEventLog::Add(array(
	*          "SEVERITY" =&gt; "SECURITY",
	*          "AUDIT_TYPE_ID" =&gt; "MY_OWN_TYPE",
	*          "MODULE_ID" =&gt; "main",
	*          "ITEM_ID" =&gt; 123,
	*          "DESCRIPTION" =&gt; "Какое-то описание",
	*       ));
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventlog/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $USER, $DB;
		static $arSeverity = array(
			"SECURITY" => self::SEVERITY_SECURITY,
			"ERROR" => self::SEVERITY_ERROR,
			"WARNING" => self::SEVERITY_WARNING,
			"INFO" => self::SEVERITY_INFO,
			"DEBUG" => self::SEVERITY_DEBUG,
		);

		$url = preg_replace("/(&?sessid=[0-9a-z]+)/", "", $_SERVER["REQUEST_URI"]);
		$SITE_ID = defined("ADMIN_SECTION") && ADMIN_SECTION==true ? false : SITE_ID;

		$arFields = array(
			"SEVERITY" => array_key_exists($arFields["SEVERITY"], $arSeverity)? $arFields["SEVERITY"]: "UNKNOWN",
			"AUDIT_TYPE_ID" => strlen($arFields["AUDIT_TYPE_ID"]) <= 0? "UNKNOWN": $arFields["AUDIT_TYPE_ID"],
			"MODULE_ID" => strlen($arFields["MODULE_ID"]) <= 0? "UNKNOWN": $arFields["MODULE_ID"],
			"ITEM_ID" => strlen($arFields["ITEM_ID"]) <= 0? "UNKNOWN": $arFields["ITEM_ID"],
			"REMOTE_ADDR" => $_SERVER["REMOTE_ADDR"],
			"USER_AGENT" => $_SERVER["HTTP_USER_AGENT"],
			"REQUEST_URI" => $url,
			"SITE_ID" => strlen($arFields["SITE_ID"]) <= 0 ? $SITE_ID : $arFields["SITE_ID"],
			"USER_ID" => is_object($USER) && ($USER->GetID() > 0)? $USER->GetID(): false,
			"GUEST_ID" => (isset($_SESSION) && array_key_exists("SESS_GUEST_ID", $_SESSION) && $_SESSION["SESS_GUEST_ID"] > 0? $_SESSION["SESS_GUEST_ID"]: false),
			"DESCRIPTION" => $arFields["DESCRIPTION"],
		);

		return $DB->Add("b_event_log", $arFields, array("DESCRIPTION"), "", false, "", array("ignore_dml"=>true));
	}

	//Agent
	public static function CleanUpAgent()
	{
		global $DB;
		$cleanup_days = COption::GetOptionInt("main", "event_log_cleanup_days", 7);
		if($cleanup_days > 0)
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$DB->Query("DELETE FROM b_event_log WHERE TIMESTAMP_X <= ".$DB->CharToDateFunction(ConvertTimeStamp($date, "FULL")));
		}
		return "CEventLog::CleanUpAgent();";
	}

	
	/**
	* <p>Метод возвращает отфильтрованный и отсортированный список записей в логе. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = Array Массив для сортировки результата. Массив вида array("поле
	* сортировки"=&gt;"направление сортировки" [, ...]). Поле для сортировки
	* может принимать значения:  <ul> <li> <b>ID</b> - идентификатор записи;</li>
	* <li> <b>TIMESTAMP_X</b> - Время в Unix-формате.</li>   </ul> Направление сортировки
	* может принимать значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li>
	* <b>desc</b> - по убыванию.</li>   </ul>
	*
	* @param mixed $intID  Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]), может
	* принимать значения: <ul> <li> <b>SEVERITY</b> - степень важности записи.
	* Доступны значения: SECURITY или WARNING, для иного система установит
	* UNKNOWN.</li> <li> <b>AUDIT_TYPE_ID</b> - собственный ID типа события.</li>  <li> <b>MODULE_ID</b>
	* - модуль, с которого происходит запись в лог.</li>  <li> <b>ITEM_ID</b> - ID
	* объекта, в связи с которым происходит добавление (пользователь,
	* элемент ИБ, ID сообщения)</li> <li> <b>REMOTE_ADDR</b> - IP, с которого
	* обратились.</li>  <li> <b>USER_AGENT</b> - браузер.</li> <li> <b>REQUEST_URI</b> - URL
	* страницы.</li> <li> <b>SITE_ID</b> - ID сайта, к которому относится
	* добавляемое событие.</li>  <li> <b>USER_ID</b> - ID пользователя.</li> <li>
	* <b>GUEST_ID</b> - ID пользователя из модуля статистики.</li> <li> <b>DESCRIPTION</b> -
	* собственно описание записи лога, или техническая информация.</li>  
	* </ul>
	*
	* @param I $DESC  Массив настроек постраничной навигации.
	*
	* @param  $arFilter = array() 
	*
	* @param array $arNavParams = false 
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* 
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventlog/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = array(), $arNavParams = false)
	{
		global $DB;
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";

		$arSqlSearch = array();
		$arSqlOrder = array();

		$arFields = array("ID", "TIMESTAMP_X", "AUDIT_TYPE_ID", "MODULE_ID", "SEVERITY", "ITEM_ID", "SITE_ID", "REMOTE_ADDR", "USER_AGENT", "REQUEST_URI", "USER_ID", "GUEST_ID");
		$arOFields = array(
			"ID" => "L.ID",
			"TIMESTAMP_X" => "L.TIMESTAMP_X",
		);

		foreach($arFilter as $key => $val)
		{
			if(is_array($val))
			{
				if(count($val) <= 0)
					continue;
			}
			elseif(strlen($val) <= 0)
			{
				continue;
			}
			$key = strtoupper($key);
			switch($key)
			{
				case "ID":
					$arSqlSearch[] = "L.ID=".IntVal($val);
					break;
				case "TIMESTAMP_X_1":
					$arSqlSearch[] = "L.TIMESTAMP_X >= ".$DB->CharToDateFunction($DB->ForSql($val), "FULL");
					break;
				case "TIMESTAMP_X_2":
					$arSqlSearch[] = "L.TIMESTAMP_X <= ".$DB->CharToDateFunction($DB->ForSql($val), "FULL");
					break;
				case "=AUDIT_TYPE_ID":
					$arValues = array();
					if(is_array($val))
					{
						foreach($val as $value)
						{
							$value = trim($value);
							if(strlen($value))
								$arValues[$value] = $DB->ForSQL($value);
						}
					}
					elseif(is_string($val))
					{
						$value = trim($val);
						if(strlen($value))
							$arValues[$value] = $DB->ForSQL($value);
					}
					if(!empty($arValues))
						$arSqlSearch[] = "L.AUDIT_TYPE_ID in ('".implode("', '", $arValues)."')";
					break;
				case "=MODULE_ITEM":
					if(is_array($val))
					{
						$arSqlSearch2 = array();
						foreach($val as $value)
						{
							$arSqlSearchTmp = array();
							foreach($value as $item2 => $value2)
							{
								if (in_array($item2, $arFields))
									$arSqlSearchTmp[] = "L.".$item2." = '".$DB->ForSQL($value2)."'";
							}
							if(count($arSqlSearchTmp) > 0)
								$arSqlSearch2[] = implode(" AND ", $arSqlSearchTmp);
						}
						if(count($arSqlSearch2) > 0)
							$arSqlSearch[] = "(".implode(" OR ", $arSqlSearch2).")";
					}
					break;
				case "SEVERITY":
				case "AUDIT_TYPE_ID":
				case "MODULE_ID":
				case "ITEM_ID":
				case "SITE_ID":
				case "REMOTE_ADDR":
				case "USER_AGENT":
				case "REQUEST_URI":
					$arSqlSearch[] = GetFilterQuery("L.".$key, $val);
					break;
				case "USER_ID":
				case "GUEST_ID":
					$arSqlSearch[] = "L.".$key." = ".intval($val)."";
					break;
			}
		}

		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if (array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
					$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
					$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[$by] = $arOFields[$by]." ".$order;
			}
		}

		$strSql = "
			FROM
				b_event_log L
		";

		if(!empty($arSqlSearch))
			$strSql .=  " WHERE ".implode(" AND ", $arSqlSearch);

		if(is_array($arNavParams))
		{
			$res_cnt = $DB->Query("SELECT count(1) C".$strSql);
			$res_cnt = $res_cnt->Fetch();
			$cnt = $res_cnt["C"];

			if(!empty($arSqlOrder))
				$strSql .=  " ORDER BY ".implode(", ", $arSqlOrder);

			$res = new CDBResult();
			$res->NavQuery("
				SELECT
					ID
					,".$DB->DateToCharFunction("L.TIMESTAMP_X")." as TIMESTAMP_X
					,SEVERITY
					,AUDIT_TYPE_ID
					,MODULE_ID
					,ITEM_ID
					,REMOTE_ADDR
					,USER_AGENT
					,REQUEST_URI
					,SITE_ID
					,USER_ID
					,GUEST_ID
					,DESCRIPTION
			".$strSql, $cnt, $arNavParams);

			return $res;
		}
		else
		{
			if(!empty($arSqlOrder))
				$strSql .=  " ORDER BY ".implode(", ", $arSqlOrder);

			return $DB->Query("SELECT L.*, ".$DB->DateToCharFunction("L.TIMESTAMP_X")." as TIMESTAMP_X".$strSql, false, $err_mess.__LINE__);
		}
	}
}

class CEventMain
{
	public static function MakeMainObject()
	{
		$obj = new CEventMain;
		return $obj;
	}

	public static function GetFilter()
	{
		$arFilter = array();
		if(COption::GetOptionString("main", "event_log_register", "N") === "Y" || COption::GetOptionString("main", "event_log_user_delete", "N") === "Y" || COption::GetOptionString("main", "event_log_user_edit", "N") === "Y" || COption::GetOptionString("main", "event_log_user_groups", "N") === "Y")
		{
			$arFilter["USERS"] = GetMessage("LOG_TYPE_USERS");
		}
		return  $arFilter;
	}

	public static function GetAuditTypes()
	{
		return array(
			"USER_REGISTER" => "[USER_REGISTER] ".GetMessage("LOG_TYPE_NEW_USERS"),
			"USER_DELETE" => "[USER_DELETE] ".GetMessage("LOG_TYPE_USER_DELETE"),
			"USER_EDIT" => "[USER_EDIT] ".GetMessage("LOG_TYPE_USER_EDIT"),
			"USER_GROUP_CHANGED" => "[USER_GROUP_CHANGED] ".GetMessage("LOG_TYPE_USER_GROUP_CHANGED"),
			"BACKUP_ERROR" => "[BACKUP_ERROR] ".GetMessage("LOG_TYPE_BACKUP_ERROR"),
			"BACKUP_SUCCESS" => "[BACKUP_SUCCESS] ".GetMessage("LOG_TYPE_BACKUP_SUCCESS"),
			"SITE_CHECKER_SUCCESS" => "[SITE_CHECKER_SUCCESS] ".GetMessage("LOG_TYPE_SITE_CHECK_SUCCESS"),
			"SITE_CHECKER_ERROR" => "[SITE_CHECKER_ERROR] ".GetMessage("LOG_TYPE_SITE_CHECK_ERROR"),
		);
	}

	public static function GetEventInfo($row, $arParams)
	{
		$DESCRIPTION = unserialize($row["DESCRIPTION"]);
		$userURL = $EventPrint = "";
		$rsUser = CUser::GetByID($row['ITEM_ID']);
		if($arUser = $rsUser->GetNext())
			$userURL = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['USER_PATH'], array("user_id" => $row['ITEM_ID'], "SITE_ID" => ""));
		$EventName = $DESCRIPTION["user"];
		switch($row['AUDIT_TYPE_ID'])
		{
			case "USER_REGISTER":
				$EventPrint = GetMessage("LOG_USER_REGISTER");
				break;
			case "USER_DELETE":
				$EventPrint = GetMessage("LOG_USER_DELETE");
				break;
			case "USER_EDIT":
				$EventPrint = GetMessage("LOG_USER_EDIT");
				break;
			case "USER_GROUP_CHANGED":
				$EventPrint = GetMessage("LOG_USER_GROUP_CHANGED");
				break;
		}

		return array(
			"eventType" => $EventPrint,
			"eventName" => $EventName,
			"eventURL" => $userURL,
		);
	}

	public static function GetFilterSQL($var)
	{
		$ar[] = array("AUDIT_TYPE_ID" => "USER_REGISTER");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_DELETE");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_EDIT");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_GROUP_CHANGED");
		return $ar;
	}
}
