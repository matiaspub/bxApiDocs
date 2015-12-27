<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/group_subject.php");


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
class CSocNetGroupSubject extends CAllSocNetGroupSubject
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	
	/**
	* <p>Метод добавляет новую тему.</p>
	*
	*
	* @param array $arFields  Массив параметров новой темы. Ключами массива являются названия
	* полей темы, а значениями - их значения. Допустимые ключи:<br><b>SITE_ID</b>
	* - код сайта,<br><b>NAME</b> - название.
	*
	* @return int <p>Возвращается код созданной темы или false в случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetGroupSubject::CheckFields("ADD", $arFields))
			return false;
		else
		{
			$arSiteID = array();
			if(array_key_exists("SITE_ID", $arFields))
			{
				if(is_array($arFields["SITE_ID"]))
					foreach($arFields["SITE_ID"] as $site_id)
						$arSiteID[$site_id] = $DB->ForSQL($site_id);
				else
					$arSiteID[$arFields["SITE_ID"]] = $DB->ForSQL($arFields["SITE_ID"]);
			}
		}

		if(empty($arSiteID))
			unset($arFields["SITE_ID"]);
		else
			$arFields["SITE_ID"] = end($arSiteID);

		$arInsert = $DB->PrepareInsert("b_sonet_group_subject", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$ID = false;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_sonet_group_subject(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			if($ID > 0 && !empty($arSiteID))
			{
				$DB->Query("
					DELETE FROM b_sonet_group_subject_site WHERE SUBJECT_ID = ".$ID."
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$DB->Query("
					INSERT INTO b_sonet_group_subject_site(SUBJECT_ID, SITE_ID)
					SELECT ".$ID.", LID
					FROM b_lang
					WHERE LID IN ('".implode("', '", $arSiteID)."')
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}

			if (CACHED_b_sonet_group_subjects != false)
				$CACHE_MANAGER->CleanDir("b_sonet_group_subjects");
		}

		return $ID;
	}

	
	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Возвращает список тем в соответствии с фильтром.</p>
	*
	*
	* @param array $arOrder = array("SORT" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи: <b>ID<b>,
	* <b>SITE_ID</b>, <b>NAME</b>. </b></b>
	*
	* @param AS $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимые
	* поля: <b>ID<b>, <b>SITE_ID</b>, <b>NAME</b>. </b></b>
	*
	* @param I $D  Массив, задающий группировку результирующего списка. Если
	* параметр содержит массив названий полей, то по этим полям будет
	* произведена группировка. Если параметр содержит пустой массив,
	* то метод вернет количество записей, удовлетворяющих фильтру. По
	* умолчанию параметр равен false - не группировать.
	*
	* @param DES $C  Массив, задающий условия выбора для организации постраничной
	* навигации.
	*
	* @param array $arFilter = array() Массив, задающий выбираемые поля. Содержит список полей, которые
	* должны быть возвращены методом. Если массив пустой, то выбираются
	* поля <b>ID<b>, <b>SITE_ID</b>, <b>NAME</b>. </b></b>
	*
	* @param array $arGroupBy = false Массив, задающий выбираемые поля. Содержит список полей, которые
	* должны быть возвращены методом. Если массив пустой, то выбираются
	* поля <b>ID<b>, <b>SITE_ID</b>, <b>NAME</b>. </b></b>
	*
	* @param array $arNavStartParams = false 
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Возвращается объект типа CDBResult, содержащий список записей,
	* удовлетворяющих фильтру.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroupsubject/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("SORT" => "ASC", "ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $CACHE_MANAGER;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SITE_ID", "NAME", "SORT");

		$bShouldBeCached = false;
		$cacheId = "";
		if (CACHED_b_sonet_group_subjects != false)
		{
			if ($arSelectFields == false && $arNavStartParams == false && $arGroupBy == false)
			{
				$bFilterByID = array_key_exists("ID", $arFilter);
				$bFilterBySite = array_key_exists("SITE_ID", $arFilter);
				if (count($arFilter) == 1 && ($bFilterByID || $bFilterBySite))
				{
					$bShouldBeCached = true;
					$cacheId = "b_sonet_group_subjects".md5(serialize($arOrder));
					if ($CACHE_MANAGER->Read(CACHED_b_sonet_group_subjects, $cacheId, "b_sonet_group_subjects"))
					{
						$arResult = $CACHE_MANAGER->Get($cacheId);

						$arReturnValue = array();
						for ($i = 0; $i < count($arResult); $i++)
						{
							if ($bFilterByID && $arResult[$i]["ID"] == $arFilter["ID"])
								$arReturnValue[] = $arResult[$i];
							if (
								$bFilterBySite 
								&& (
									(is_array($arFilter["SITE_ID"]) && in_array($arResult[$i]["SITE_ID"], $arFilter["SITE_ID"]))
									|| 
									(!is_array($arFilter["SITE_ID"]) && $arResult[$i]["SITE_ID"] == $arFilter["SITE_ID"])
								)
							)
								$arReturnValue[] = $arResult[$i];
						}

						$res = new CDBResult;
						$res->InitFromArray($arResult);
						return $res;
					}
				}
			}
		}

		static $arFields1 = array(
			"ID" => Array("FIELD" => "S.ID", "TYPE" => "int"),
			"NAME" => Array("FIELD" => "S.NAME", "TYPE" => "string"),
			"SORT" => Array("FIELD" => "S.SORT", "TYPE" => "int"),
		);

		if (array_key_exists("SITE_ID", $arFilter))
		{
			$arFields["SITE_ID"] = Array("FIELD" => "SGSS.SITE_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sonet_group_subject_site SGSS ON S.ID = SGSS.SUBJECT_ID");
			$strDistinct = " DISTINCT ";
			foreach ($arSelectFields as $i => $strFieldTmp)
				if ($strFieldTmp == "SITE_ID")
					unset($arSelectFields[$i]);

			foreach ($arOrder as $by => $order)
				if (!in_array($by, $arSelectFields))
					$arSelectFields[] = $by;
		}
		else
		{
			$arFields["SITE_ID"] = Array("FIELD" => "S.SITE_ID", "TYPE" => "string");
			$strDistinct = " ";
		}

		$arFields = array_merge($arFields1, $arFields);

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", $strDistinct, $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_group_subject S ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_group_subject S ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_group_subject S ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			//$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if (CACHED_b_sonet_group_subjects == false || !$bShouldBeCached)
			{
				$dbRes = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}
			else
			{
				$arResult = array();
				$dbRes = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				while ($ar = $dbRes->Fetch())
					$arResult[] = $ar;

				$CACHE_MANAGER->Set($cacheId, $arResult);

				$dbRes = new CDBResult;
				$dbRes->InitFromArray($arResult);
			}
		}

		return $dbRes;
	}
}
?>