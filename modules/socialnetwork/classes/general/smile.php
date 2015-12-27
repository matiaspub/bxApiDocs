<?

/**
 * <b>CSocNetSmile</b> - класс для работы со смайлами социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/index.php
 * @author Bitrix
 */
class CAllSocNetSmile
{
	public static function PrintSmilesList($num_cols, $strLang = False, $strPath2Icons = False, $cacheTime = False)
	{
		$res_str = "";
		$arSmile = array();
		$return_array = intVal($num_cols) > 0 ? false : true;
		if ($strLang === False)
			$strLang = LANGUAGE_ID;
		if ($strPath2Icons === False)
			$strPath2Icons = "/bitrix/images/socialnetwork/smile/";
		$cache = new CPHPCache;
		$cache_id = "socialnetwork_smiles_".$strLang.preg_replace("/[^a-z0-9]/is", "_", $strPath2Icons);
		
		$cache_path = "/".SITE_ID."/socialnetwork/smiles/";
		if ($cacheTime > 0 && $cache->InitCache($cacheTime, $cache_id, $cache_path))
		{
			$res = $cache->GetVars();
			$arSmile = $res["arSmile"];
		}
		
		if (empty($arSmile))
		{
			$db_res = CSocNetSmile::GetList(array("SORT"=>"ASC"), array("TYPE"=>"S", "LID"=>LANGUAGE_ID));
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					$arSmile[] = $res;
				}
				while ($res = $db_res->Fetch());
			}
			if ($cacheTime > 0)
			{
				$cache->StartDataCache($cacheTime, $cache_id, $cache_path);
				$cache->EndDataCache(array("arSmile"=>$arSmile));
			}
		}
		
		if ($return_array)
			return $arSmile;
		
		$res_str = "";
		$ind = 0;
		foreach ($arSmile as $res)
		{
			if ($ind == 0) {$res_str .= "<tr align=\"center\">";}
			$res_str .= "<td width=\"".IntVal(100/$num_cols)."%\">";
			$strTYPING = strtok($res['TYPING'], " ");
			$res_str .= "<img src=\"".$strPath2Icons.$res['IMAGE']."\" alt=\"".$res['NAME']."\" title=\"".$res['NAME']."\" border=\"0\"";
			if (IntVal($res['IMAGE_WIDTH'])>0) {$res_str .= " width=\"".$res['IMAGE_WIDTH']."\"";}
			if (IntVal($res['IMAGE_HEIGHT'])>0) {$res_str .= " height=\"".$res['IMAGE_HEIGHT']."\"";}
			$res_str .= " onclick=\"if(emoticon){emoticon('".$strTYPING."');}\" name=\"smile\"  id='".$strTYPING."' ";
			$res_str .= "/>&nbsp;</td>\n";
			$ind++;
			if ($ind >= $num_cols)
			{
				$ind = 0;
				$res_str .= "</tr>";
			}
		}
		if ($ind < $num_cols)
		{
			for ($i=0; $i<$num_cols-$ind; $i++)
			{
				$res_str .= "<td> </td>";
			}
		}
		
		return $res_str;
	}

	//---------------> User insert, update, delete
	public static function CheckFields($ACTION, &$arFields)
	{
		if ((is_set($arFields, "SMILE_TYPE") || $ACTION=="ADD") && $arFields["SMILE_TYPE"]!="I" && $arFields["SMILE_TYPE"]!="S") return False;
		if ((is_set($arFields, "IMAGE") || $ACTION=="ADD") && strlen($arFields["IMAGE"])<=0) return False;

		if ((is_set($arFields, "SORT") || $ACTION=="ADD") && IntVal($arFields["SORT"])<=0) $arFields["SORT"] = 150;

		if (is_set($arFields, "LANG") || $ACTION=="ADD")
		{
			for ($i = 0; $i<count($arFields["LANG"]); $i++)
			{
				if (!is_set($arFields["LANG"][$i], "LID") || strlen($arFields["LANG"][$i]["LID"])<=0) return false;
				if (!is_set($arFields["LANG"][$i], "NAME") || strlen($arFields["LANG"][$i]["NAME"])<=0) return false;
			}

			$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
			while ($arLang = $db_lang->Fetch())
			{
				$bFound = False;
				for ($i = 0; $i<count($arFields["LANG"]); $i++)
				{
					if ($arFields["LANG"][$i]["LID"]==$arLang["LID"])
						$bFound = True;
				}
				if (!$bFound) return false;
			}
		}

		return True;
	}

	
	/**
	* <p>Метод удаляет смайл.</p>
	*
	*
	* @param int $id  Код смайла
	*
	* @return bool <p>True в случае успешного выполнения и false - в противном случае.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/Delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_sonet_smile_lang WHERE SMILE_ID = ".$ID, True);
		$DB->Query("DELETE FROM b_sonet_smile WHERE ID = ".$ID, True);
		$CACHE_MANAGER->Clean("b_sonet_smile");

		return true;
	}

	
	/**
	* <p>Возвращает массив языконезависимых параметров смайла.</p>
	*
	*
	* @param int $id  Код смайла.
	*
	* @return array <p>Массив с ключами:<br><b>ID</b> - код смайла,<br><b>SORT</b> - индекс
	* сортировки,<br><b>SMILE_TYPE</b> - тип смайла,<br><b>TYPING</b> - написание
	* смайла,<br><b>IMAGE</b> - изображение,<br><b>IMAGE_WIDTH</b> - ширина
	* изображения,<br><b>IMAGE_HEIGHT</b> - высота изображения,<br><b>DESCRIPTION</b> -
	* описание.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql = 
			"SELECT FR.ID, FR.SORT, FR.SMILE_TYPE, FR.TYPING, FR.IMAGE, FR.CLICKABLE, ".
			"	FR.DESCRIPTION, FR.IMAGE_WIDTH, FR.IMAGE_HEIGHT ".
			"FROM b_sonet_smile FR ".
			"WHERE FR.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Возвращает параметры смайла.</p>
	*
	*
	* @param int $id  Код смайла.
	*
	* @param string $strLang  Код языка.
	*
	* @return array <p>Массив параметров смайла, содержащий ключи:<br><b>ID</b> - код
	* смайла,<br><b>SORT</b> - индекс сортировки,<br><b>SMILE_TYPE</b> - тип
	* смайла,<br><b>TYPING</b> - написание смайла,<br><b>IMAGE</b> - изображение
	* смайла,<br><b>IMAGE_WIDTH</b> - ширина изображения,<br><b>IMAGE_HEIGHT</b> - высота
	* изображения,<br><b>NAME</b> - название смайла,<br><b>DESCRIPTION</b> - описание
	* смайла.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/GetByIDEx.php
	* @author Bitrix
	*/
	public static function GetByIDEx($ID, $strLang)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql = 
			"SELECT FR.ID, FR.SORT, FR.SMILE_TYPE, FR.TYPING, FR.IMAGE, FR.CLICKABLE, ".
			"	FRL.LID, FRL.NAME, FR.DESCRIPTION, FR.IMAGE_WIDTH, FR.IMAGE_HEIGHT ".
			"FROM b_sonet_smile FR ".
			"	LEFT JOIN b_sonet_smile_lang FRL ON (FR.ID = FRL.SMILE_ID AND FRL.LID = '".$DB->ForSql($strLang)."') ".
			"WHERE FR.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Возвращает языкозависимые параметры смайла.</p>
	*
	*
	* @param int $id  Идентификатор смайла.
	*
	* @param string $strLang  Код языка.
	*
	* @return array <p>Массив языкозависимых параметров смайлов с ключами:<br> ID - код,<br>
	* NAME - название. </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/getlangbyid.php
	* @author Bitrix
	*/
	public static function GetLangByID($SMILE_ID, $strLang)
	{
		global $DB;

		$SMILE_ID = IntVal($SMILE_ID);
		$strSql = 
			"SELECT FRL.ID, FRL.SMILE_ID, FRL.LID, FRL.NAME ".
			"FROM b_sonet_smile_lang FRL ".
			"WHERE FRL.SMILE_ID = ".$SMILE_ID." ".
			"	AND FRL.LID = '".$DB->ForSql($strLang)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}
}
?>