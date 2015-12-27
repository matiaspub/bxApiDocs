<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CSocNetGroupSubject</b> - класс для работы с темами рабочих групп социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/index.php
 * @author Bitrix
 */
class CAllSocNetGroupSubject
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "SITE_ID") || $ACTION=="ADD") 
			&& (
				(is_array($arFields["SITE_ID"]) && count($arFields["SITE_ID"]) <= 0)
				||
				(!is_array($arFields["SITE_ID"]) && strlen($arFields["SITE_ID"]) <= 0)
			)
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GS_EMPTY_SITE_ID"), "EMPTY_SITE_ID");
			return false;
		}
		elseif (is_set($arFields, "SITE_ID"))
		{
			if(!is_array($arFields["SITE_ID"]))
				$arFields["SITE_ID"] = array($arFields["SITE_ID"]);

			foreach($arFields["SITE_ID"] as $v)
			{
				$dbResult = CSite::GetByID($v);
				if (!$dbResult->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $v, GetMessage("SONET_GS_ERROR_NO_SITE")), "ERROR_NO_SITE");
					return false;
				}
			}
		}

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GS_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}

		if (is_set($arFields, "SORT") || $ACTION=="ADD")
			$arFields["SORT"] = (intVal($arFields["SORT"]) > 0 ? intVal($arFields["SORT"]) : 100);
		
		return True;
	}

	
	/**
	* <p>Метод удаляет тему рабочих групп. Если есть группы с этой темой, то тема удалена не будет.</p>
	*
	*
	* @param int $id  Код темы.</bod
	*
	* @return bool <p>True в случае успешного удаления и false - в противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		$bCanDelete = true;
		$dbResult = CSocNetGroup::GetList(
			array(),
			array("SUBJECT_ID" => $ID)
		);
		if ($arResult = $dbResult->Fetch())
			$bCanDelete = false;

		if (!$bCanDelete)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GS_NOT_EMPTY_SUBJECT"), "NOT_EMPTY_SUBJECT");
			return false;
		}

		$bSuccess = $DB->Query("DELETE FROM b_sonet_group_subject_site WHERE SUBJECT_ID = ".$ID."", true);

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_group_subject WHERE ID = ".$ID."", true);

		if (CACHED_b_sonet_group_subjects != false)
			$CACHE_MANAGER->CleanDir("b_sonet_group_subjects");

		return $bSuccess;
	}
	
	
	/**
	* <p>Изменяет параметры темы.</p>
	*
	*
	* @param int $id  Код темы.</bod
	*
	* @param array $arFields  Массив новых параметров темы. Ключами массива являются названия
	* полей темы, а значениями - их значения. Допустимые ключи: <b>SITE_ID</b> -
	* код сайта, <b>NAME</b> - название.
	*
	* @return int <p>Возвращается код измененной темы или false в случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetGroupSubject::CheckFields("UPDATE", $arFields, $ID))
			return false;
		else
		{
			$arSiteID = Array();
			if(is_set($arFields, "SITE_ID"))
			{
				if(is_array($arFields["SITE_ID"]))
					$arSiteID = $arFields["SITE_ID"];
				else
					$arSiteID[] = $arFields["SITE_ID"];

				$arFields["SITE_ID"] = false;
				$str_SiteID = "''";
				foreach($arSiteID as $v)
				{
					$arFields["SITE_ID"] = $v;
					$str_SiteID .= ", '".$DB->ForSql($v)."'";
				}
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_sonet_group_subject", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_group_subject SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			if(count($arSiteID)>0)
			{
				$strSql = "DELETE FROM b_sonet_group_subject_site WHERE SUBJECT_ID=".$ID;
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$strSql =
					"INSERT INTO b_sonet_group_subject_site(SUBJECT_ID, SITE_ID) ".
					"SELECT ".$ID.", LID ".
					"FROM b_lang ".
					"WHERE LID IN (".$str_SiteID.") ";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}

			if (CACHED_b_sonet_group_subjects != false)
				$CACHE_MANAGER->CleanDir("b_sonet_group_subjects");
		}
		else
			$ID = False;

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Возвращает параметры темы. Не порождает запросов к базе данных.</p>
	*
	*
	* @param int $id  Код темы.</bod
	*
	* @return array <p>Массив параметров темы. Массив имеет ключи:<br><b>ID</b> - код
	* темы,<br><b>SITE_ID</b> - код сайта,<br><b>NAME</b> - название. </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/GetByID.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$dbResult = CSocNetGroupSubject::GetList(Array(), Array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
			return $arResult;

		return False;
	}

	public static function GetSite($subject_id)
	{
		global $DB;
		$strSql = "SELECT L.*, SGSS.* FROM b_sonet_group_subject_site SGSS, b_lang L WHERE L.LID=SGSS.SITE_ID AND SGSS.SUBJECT_ID=".IntVal($subject_id);
		return $DB->Query($strSql);
	}
}
?>
