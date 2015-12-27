<?
IncludeModuleLangFile(__FILE__);
// define("FORUM_SystemFolder", 4);
//*****************************************************************************************************************
//	PM
//************************************!****************************************************************************
class CAllForumPrivateMessage
{
	
	/**
	* <p>Создает новое сообщение с параметрами, указанными в массиве <i>arFields</i>. Возвращает код созданного сообщения.</p>
	*
	*
	* @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	* <br><br><i>field</i> - название поля; <br><i>value</i> - значение поля. <br><br> Поля
	* перечислены в списке полей таблицы <a
	* href="http://dev.1c-bitrix.ru/api_help/forum/fields.php#cforumprivatemessage">"Приватное
	* сообщение"</a>. Обязательные поля должны быть заполнены.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = Array(
	* 	"AUTHOR_ID"    =&gt; $AUTHOR_ID,
	* 	"POST_DATE"    =&gt; $POST_DATE,   
	* 	"POST_SUBJ"    =&gt; $POST_SUBJ,   
	* 	"POST_MESSAGE" =&gt; $POST_MESSAGE,
	* 	"USER_ID"      =&gt; $USER_ID,     
	* 	"FOLDER_ID"    =&gt; 1,   
	* 	"IS_READ"      =&gt; "N",     
	* 	"USE_SMILES"   =&gt; ($USE_SMILES=="Y") ? "Y" : "N",
	* 	"AUTHOR_NAME"  =&gt; $AUTHOR_NAME 
	* );
	* $ID = CForumPrivateMessage::Send($arFields);
	* if (IntVal($ID)&lt;=0)
	*   echo "Error!";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li>таблица <a
	* href="http://dev.1c-bitrix.ru/api_help/forum/fields.php#cforumprivatemessage">"Приватное
	* сообщение"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/send.php
	* @author Bitrix
	*/
	public static function Send($arFields = array())
	{
		global $DB;
		$version = COption::GetOptionString("forum", "UsePMVersion", "2");
		if(!CForumPrivateMessage::CheckFields($arFields))
			return false;

		$arFields["RECIPIENT_ID"] = $arFields["USER_ID"];
		$arFields["IS_READ"] = $arFields["IS_READ"]!="Y" ? "N" : "Y";
		$arFields["USE_SMILES"] = $arFields["USE_SMILES"]!="Y" ? "N" : "Y";
		$arFields["FOLDER_ID"] = intval($arFields["FOLDER_ID"])<=0 ? 1 : intval($arFields["FOLDER_ID"]);
		$arFields["REQUEST_IS_READ"] = $arFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";

		foreach (GetModuleEvents("forum", "onBeforePMSend", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}

		if(!isset($arFields["POST_DATE"]))
			$arFields["~POST_DATE"] = $DB->GetNowFunction();

		if ($version == 2 && $arFields["COPY_TO_OUTBOX"] == "Y")
		{
			$arFieldsTmp = $arFields;
			$arFieldsTmp["USER_ID"] = $arFields["AUTHOR_ID"];
			$arFieldsTmp["IS_READ"] = "Y";
			$arFieldsTmp["FOLDER_ID"] = "3";
			$DB->Add("b_forum_private_message", $arFieldsTmp, Array("POST_MESSAGE"));
		}

		$result = $DB->Add("b_forum_private_message", $arFields, Array("POST_MESSAGE"));

		if ($result)
		{
			foreach (GetModuleEvents("forum", "onAfterPMSend", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($result, &$arFields));
		}

		return $result;
	}

	public static function Copy($ID, $arFields = array())
	{
		global $DB;
		$ID = intval($ID);
		$list = array();
		$list = CForumPrivateMessage::GetList(array(), array("ID"=>$ID));
		$list = $list->GetNext();

		foreach (GetModuleEvents("forum", "onBeforePMCopy", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$list, &$arFields)) === false)
				return false;
		}

		if(CForumPrivateMessage::CheckFields($arFields))
		{
			$keys = array_keys($arFields);
			foreach ($keys as $key)
				if (is_set($list, $key))
					$list[$key] = $arFields[$key];

			if(!isset($list["POST_DATE"]))
				$list["~POST_DATE"] = $DB->GetNowFunction();

			$list["IS_READ"] = "Y";
			$list["REQUEST_IS_READ"] = $list["REQUEST_IS_READ"]!="Y" ? "N" : "Y";

			unset($list["ID"]);
			unset($list["~ID"]);

			$result = $DB->Add("b_forum_private_message", $list, Array("POST_MESSAGE"));

			if ($result)
			{
				foreach (GetModuleEvents("forum", "onAfterPMCopy", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array($result, &$arFields));
			}

			return $result;
		}
		return false;
	}

	public static function Update($ID, $arFields)
	{
		global $DB, $USER;
		$ID = intval($ID);

		if (is_set($arFields, "AUTHOR_ID")&&(intVal($arFields["AUTHOR_ID"])))
			$arFields["AUTHOR_ID"] = $arFields["USER_ID"];
		if (is_set($arFields, "RECIPIENT_ID")&&(intVal($arFields["RECIPIENT_ID"])))
			$arFields["RECIPIENT_ID"] = $arFields["USER_ID"];
		if (is_set($arFields, "POST_DATE")&&(strLen(trim($arFields["POST_DATE"])) <= 0))
			$arFields["~POST_DATE"] =  $DB->GetNowFunction();
		if(is_set($arFields, "USE_SMILES") && $arFields["USE_SMILES"]!="Y")
			$arFields["USE_SMILES"]="N";
		if(is_set($arFields, "IS_READ") && $arFields["IS_READ"]!="Y")
			$arFields["IS_READ"]="N";
		if(is_set($arFields, "FOLDER_ID") && (intval($arFields["FOLDER_ID"]) < 0))
			$arFields["FOLDER_ID"] = 4;

		foreach (GetModuleEvents("forum", "onBeforePMUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields)) === false)
				return false;
		}

		if(CForumPrivateMessage::CheckFields($arFields, true))
		{
			$strUpdate = $DB->PrepareUpdate("b_forum_private_message", $arFields);
			$strSql = "UPDATE b_forum_private_message SET ".$strUpdate." WHERE ID=".$ID;
			$res = $DB->QueryBind($strSql, Array("POST_MESSAGE"=>$arFields["POST_MESSAGE"]), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			return $res;
		}
		return false;
	}

	public static function Delete($ID)
	{
		global $DB, $USER;
		$ID = IntVal($ID);

		$list = array();
		$list = CForumPrivateMessage::GetList(array(), array("ID"=>$ID));
		$arFields = $list->GetNext();

		$result = false;

		foreach (GetModuleEvents("forum", "onBeforePMDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields)) === false)
				return $result;
		}

		$eventID = "onAfterPMDelete";
		if ($arFields["FOLDER_ID"] == 4)
		{
			$DB->Query("DELETE FROM b_forum_private_message WHERE ID=".$ID);
			$result = true;
		}
		else
		{
			$eventID = "onAfterPMTrash";
			if(CForumPrivateMessage::Update($ID, array("FOLDER_ID"=>4, "IS_READ"=>"Y", "USER_ID"=>$USER->GetId())))
				$result = true;
		}

		if ($result)
		{
			foreach (GetModuleEvents("forum", $eventID, true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
		}
		return $result;
	}

	public static function MakeRead($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$version = intVal(COption::GetOptionString("forum", "UsePMVersion", "2"));
		if($ID>0)
		{
			$db_res = CForumPrivateMessage::GetListEx(array(), array("ID" => $ID));
			if ($db_res && ($resFields = $db_res->Fetch()) && ($resFields["IS_READ"] != "Y"))
			{
				foreach (GetModuleEvents("forum", "onBeforePMMakeRead", true) as $arEvent)
				{
					if (ExecuteModuleEventEx($arEvent, array($ID, &$resFields)) === false)
						return false;
				}

				$strSql = "UPDATE b_forum_private_message SET IS_READ='Y' WHERE ID=".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($version == 1 && ($resFields["IS_READ"] == "N"))
				{
					$resFields = array_merge($resFields, array("USER_ID"=>$resFields["AUTHOR_ID"], "FOLDER_ID"=>3, "IS_READ"=>"Y"));
					$resFields["REQUEST_IS_READ"] = $resFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";
					if(CForumPrivateMessage::CheckFields($resFields, "E"))
					{
						unset($resFields["ID"]);
						return $DB->Add("b_forum_private_message", $resFields, Array("POST_MESSAGE"));
					}
				}
			}
		}
		return false;
	}

	public static function CheckPermissions($ID)
	{
		global $USER, $APPLICATION;

		if(CForumUser::IsAdmin())
			return true;

		$dbr = CForumPrivateMessage::GetByID($ID);
		if($arRes = $dbr->Fetch())
		{
			if((intVal($arRes["USER_ID"]) == $USER->GetID()) ||
				((intVal($arRes["AUTHOR_ID"]) == intVal($USER->GetID())) && ($arRes["IS_READ"]=="N")))
			return true;
		}
		return false;
	}

	public static function CheckFields(&$arFields, $update = false)
	{
		global $APPLICATION, $USER;
		$strError = "";
		if ((CForumPrivateMessage::PMSize($USER->GetId()) < COption::GetOptionInt("forum", "MaxPrivateMessages", 100)))
		{
			if((is_set($arFields, "USER_ID")&&(strlen($arFields["USER_ID"])<=0)))
			$strError .= GetMessage("PM_ERR_USER_EMPTY");
			if((is_set($arFields, "POST_SUBJ"))&&(strlen(trim($arFields["POST_SUBJ"]))<=0))
			$strError .= GetMessage("PM_ERR_SUBJ_EMPTY");
			if((is_set($arFields, "POST_MESSAGE"))&&(strlen(trim($arFields["POST_MESSAGE"]))<=0))
			$strError .= GetMessage("PM_ERR_TEXT_EMPTY");
		}
		else
		{
			$strError = GetMessage("PM_ERR_NO_SPACE");
			if ($update)
				return true;
		}
		if($strError)
		{
			$APPLICATION->ThrowException($strError);
			return false;
		}
		$arFields["REQUEST_IS_READ"] = $arFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";
		if(is_set($arFields, "FOLDER_ID") && intval($arFields["FOLDER_ID"]) == 4)
			$arFields["IS_READ"]="Y";
		return true;
	}

	function GetByID($ID)
	{
		global $DB;
		static $arMessage = array();
		$result = false;
		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;
		if (!is_set($arMessage, $ID))
		{
			$db_res = CForumPrivateMessage::GetList(Array(), Array("ID"=>$ID));
			if ($db_res && $result = $db_res->Fetch())
				$arMessage[$ID] = $result;
			else
				$arMessage[$ID] = false;
		}
		$result = $arMessage[$ID];
		if (is_array($result))
		{
			$db_res = new CDBResult;
			$db_res->InitFromArray(array($ID => $result));
			return $db_res;
		}
		return $result;
	}

	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter, $bCnt=false)
	{
		global $DB;

		$arSql = array();
		$orSql = array();
		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			switch($key)
			{
				case "OWNER_ID":
				$orSql = array("M.AUTHOR_ID=".intVal($val), "M.FOLDER_ID=1", "M.IS_READ='N'");
				break;
				case "ID":
				case "FOLDER_ID":
				case "AUTHOR_ID":
				case "RECIPIENT_ID":
				case "USER_ID":
				$arSql[] = "M.".$key."=".intVal($val);
				break;
				case "POST_SUBJ":
				case "POST_MESSAGE":
				$arSql[] = "M.".$key."='".$DB->ForSQL($val)."'";
				break;
				case "USE_SMILES":
				case "IS_READ":
				$t_val = strtoupper($val);
				if($t_val=="Y" || $t_val=="N")
				$arSql[] = "M.".strtoupper($key)."='".$t_val."'";
				break;
			}
		}
		$arOFields = array(
		"ID" => "M.ID",
		"AUTHOR_ID"	=> "M.AUTHOR_ID",
		"POST_DATE"	=> "M.POST_DATE",
		"POST_SUBJ"	=> "M.POST_SUBJ",
		"POST_MESSAGE"	=> "M.POST_MESSAGE",
		"USER_ID"	=> "M.USER_ID",
		"FOLDER_ID"	=> "M.FOLDER_ID",
		"IS_READ"	=> "M.IS_READ",
		"USE_SMILES"	=> "M.USE_SMILES",
		"AUTHOR_NAME"=>"AUTHOR_NAME"
		);
		$arSqlOrder = array();
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if(array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
				$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
				$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[] = $arOFields[$by]." ".$order;
			}
		}

		if (!$bCnt)
		{
			$strSql =
			"SELECT M.ID, M.AUTHOR_ID, FU.LOGIN AS AUTHOR_NAME, M.RECIPIENT_ID, ".
			"	".$DB->DateToCharFunction("M.POST_DATE", "FULL")." as POST_DATE, ".
			"	M.POST_SUBJ, M.POST_MESSAGE, M.FOLDER_ID, M.IS_READ, M.USER_ID, M.USE_SMILES, M.REQUEST_IS_READ ".
			"FROM b_forum_private_message M ".
			"LEFT JOIN b_user FU ON(M.AUTHOR_ID = FU.ID)";

			$strSql .= (count($arSql)>0) ? " WHERE (".implode(" AND ", $arSql).")" : "";
			$strSql .= (count($orSql)>0) ? " OR (".implode(" AND ", $orSql).")" : "";
			$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$strSqlTmp =
			"SELECT COUNT(M.ID) AS CNT, COUNT(M1.ID) AS CNT_NEW ".
			"FROM b_forum_private_message M ".
			//"LEFT JOIN b_user FU ON(M.AUTHOR_ID = FU.ID)".
			"LEFT JOIN b_forum_private_message M1 ON (M.ID = M1.ID AND M1.IS_READ!='Y')";

			$strSql = $strSqlTmp . ((count($arSql)>0) ? " WHERE (".implode(" AND ", $arSql).")" : "");
			//$strSql .= (count($orSql)>0) ? " OR (".implode(" AND ", $orSql).")" : "";
			$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arResult = array();
			if ($dbRes && ($res = $dbRes->GetNext()))
			{
				$arResult["CNT"] = intVal($res["CNT"]);
				$arResult["CNT_NEW"] = intVal($res["CNT_NEW"]);
			}

			if (!empty($orSql))
			{
				$strSql = $strSqlTmp;
				$arSql = $orSql;
				$strSql .= ((count($arSql)>0) ? " WHERE (".implode(" AND ", $arSql).")" : "");
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$arResult = array();
				if ($dbRes && ($res = $dbRes->GetNext()))
				{
					$arResult["CNT"] += intVal($res["CNT"]);
					$arResult["CNT_NEW"] += intVal($res["CNT"]);
				}
			}
			$dbRes = new CDBResult;
			$dbRes->InitFromArray(array($arResult));
		}

		return $dbRes;
	}

	public static function PMSize($USER_ID, $CountMess = false)
	{
		$USER_ID = intVal($USER_ID);
		if (COption::GetOptionString("forum", "UsePMVersion", "2") == 2)
			$count = CForumPrivateMessage::GetList(array(), array("USER_ID"=>$USER_ID), true);
		else
			$count = CForumPrivateMessage::GetList(array(), array("USER_ID"=>$USER_ID, "OWNER_ID"=>$USER_ID), true);

		$count = $count->GetNext();
		if ($CountMess)
		{
			$ratio = $count["CNT"]/$CountMess;
			return ($ratio < 1 ? $ratio : 1);
		}
		return $count["CNT"];
	}

	public static function GetNewPM($FOLDER_ID = false)
	{
		global $DB, $USER;
		$FOLDER_ID = ($FOLDER_ID === false ? 1 : intVal($FOLDER_ID));
		static $PMessageCache = array();
		if (!is_set($PMessageCache, $FOLDER_ID))
		{
			$strSql =
			"SELECT COUNT(PM.ID) as UNREAD_PM ".
			"FROM b_forum_private_message PM ".
			"WHERE PM.USER_ID = ".$USER->GetID()." ".
			($FOLDER_ID <= 0 ? "" : "	AND PM.FOLDER_ID = ".$FOLDER_ID." ").
			"	AND PM.IS_READ = 'N'";

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($db_res && $res = $db_res->Fetch())
				$PMessageCache[$FOLDER_ID] = $res;
			else
				$PMessageCache[$FOLDER_ID] = 0;
		}
		return $PMessageCache[$FOLDER_ID];
	}

}
//*****************************************************************************************************************
//	PM. Folders.
//************************************!****************************************************************************
class CALLForumPMFolder
{
	public static function Add($title)
	{
		global $DB, $USER, $APPLICATION;
		$res = CForumPMFolder::GetList(array(), array("TITLE"=>$title, "USER_ID"=>$USER->GetId()));
		if ($resFolder = $res->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("PM_ERR_FOLDER_EXIST"));
			return false;
		}
		return $DB->Add("b_forum_pm_folder", array("TITLE"=>$title, "USER_ID"=>$USER->GetId(), "SORT"=>"0"));
	}

	public static function Update($ID, $arFields = array())
	{
		global $DB, $USER, $APPLICATION;
		$ID = intval($ID);

		$res = CForumPMFolder::GetList(array(), array("TITLE"=>$arFields["TITLE"], "USER_ID"=>$USER->GetId()));
		while ($resFolder = $res->GetNext())
		{
			if($resFolder["ID"]!=$ID)
			{
				$APPLICATION->ThrowException(GetMessage("PM_ERR_FOLDER_EXIST"));
				return  false;
			}
		}
		$strUpdate = $DB->PrepareUpdate("b_forum_pm_folder", $arFields);
		$strSql = "UPDATE b_forum_pm_folder SET ".$strUpdate." WHERE ID=".$ID;
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;

	}

	public static function GetByID($ID)
	{
		global $DB;

		$strSql = "SELECT F.ID, F.USER_ID, F.SORT, F.TITLE FROM b_forum_pm_folder F WHERE F.ID=".intval($ID);
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $dbRes;
	}

	public static function GetList($arOrder = array("SORT" => "DESC", "TITLE"=>"DESC"), $arFilter, $bCnt=false)
	{
		global $DB;

		$arSqlSearch = array();
		$sAddJoin = '';
		$filter_keys = (is_array($arFilter) ? array_keys($arFilter) : array());

		for($i = 0; $i < count($filter_keys); $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
				case "USER_ID":
					$sAddJoin = "F.USER_ID=FPM.USER_ID AND ";
					$arSqlSearch[] = "F.USER_ID=".intVal($val);
					break;
				case "ID":
				case "SORT":
					$arSqlSearch[] = "F.".$key."=".intVal($val);
					break;
				case "TITLE":
					$arSqlSearch[] = "F.".$key."='".$DB->ForSQL($val)."'";
					break;
			}
		}

		$arOFields = array(
			"ID" => "F.ID",
			"USER_ID"	=> "F.USER_ID",
			"SORT"	=> "F.SORT",
			"TITLE"	=> "F.TITLE");
		$arSqlOrder = array();
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if(array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
				$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
				$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[] = $arOFields[$by]." ".$order;
			}
		}

		if (!$bCnt)
			$strSql =
			"SELECT F.ID, F.USER_ID, F.SORT, F.TITLE, COUNT(FPM.ID) AS CNT, COUNT(FPM1.ID) AS CNT_NEW ".
			"FROM b_forum_pm_folder F
			LEFT JOIN b_forum_private_message FPM ON(".$sAddJoin."F.ID = FPM.FOLDER_ID)
			LEFT JOIN b_forum_private_message FPM1 ON(FPM.ID = FPM1.ID AND FPM1.IS_READ != 'Y')";
		else
			$strSql =
			"SELECT COUNT(F.ID) AS CNT ".
			"FROM b_forum_pm_folder F ";

		$strSql .= (count($arSqlSearch)>0) ? " WHERE ".implode(" AND ", $arSqlSearch) : "";
		if(!$bCnt)
			$strSql .= " GROUP BY F.ID, F.USER_ID, F.SORT, F.TITLE";
		$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $dbRes;
	}

	public static function CheckPermissions($ID)
	{
		global $USER, $APPLICATION;
		$ID = intVal($ID);
		if(CForumUser::IsAdmin())
			return true;
		$dbr = CForumPMFolder::GetByID($ID);
		if($arRes = $dbr->Fetch())
		{
			if(($arRes["USER_ID"]==$USER->GetID())||($arRes["USER_ID"]==0))
			return true;
		}
		return false;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID < FORUM_SystemFolder)
		return false;

		$DB->Query("DELETE FROM b_forum_private_message WHERE FOLDER_ID=".$ID);
		return $DB->Query("DELETE FROM b_forum_pm_folder WHERE ID=".$ID);
	}
}
?>
