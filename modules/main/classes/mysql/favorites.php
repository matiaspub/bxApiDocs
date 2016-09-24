<?
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/favorites.php");

class CFavorites extends CAllFavorites
{
	public static function GetList($aSort=array(), $arFilter=Array())
	{
		$err_mess = (CFavorites::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || $val=="NOT_REF") continue;
				switch(strtoupper($key))
				{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("F.ID",$val,"N");
					break;
				case "USER_ID":
					$arSqlSearch[] = "F.USER_ID = ".intval($val);
					break;
				case "MENU_FOR_USER":
					$arSqlSearch[] = "(F.USER_ID=".intval($val)." OR F.COMMON='Y')";
					break;
				case "COMMON":
					$arSqlSearch[] = "F.COMMON = '".$DB->ForSql($val,1)."'";
					break;
				case "LANGUAGE_ID":
					$arSqlSearch[] = "F.LANGUAGE_ID = '".$DB->ForSql($val,2)."'";
					break;
				case "DATE1":
					$arSqlSearch[] = "F.TIMESTAMP_X >= FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y"),"d.m.Y")."')";
					break;
				case "DATE2":
					$arSqlSearch[] = "F.TIMESTAMP_X <= FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y")." 23:59:59","d.m.Y")."')";
					break;
				case "MODIFIED":
					$arSqlSearch[] = GetFilterQuery("UM.ID, UM.LOGIN, UM.LAST_NAME, UM.NAME", $val);
					break;
				case "MODIFIED_ID":
					$arSqlSearch[] = "F.MODIFIED_BY = ".intval($val);
					break;
				case "CREATED":
					$arSqlSearch[] = GetFilterQuery("UC.ID, UC.LOGIN, UC.LAST_NAME, UC.NAME", $val);
					break;
				case "CREATED_ID":
					$arSqlSearch[] = "F.CREATED_BY = ".intval($val);
					break;
				case "KEYWORDS":
					$arSqlSearch[] = GetFilterQuery("F.COMMENTS", $val);
					break;
				case "NAME":
					$arSqlSearch[] = GetFilterQuery("F.NAME", $val);
					break;
				case "URL":
					$arSqlSearch[] = GetFilterQuery("F.URL", $val);
					break;
				case "MODULE_ID":
					$arSqlSearch[] = "F.MODULE_ID='".$DB->ForSql($val,50)."'";
					break;
				case "MENU_ID":
					$arSqlSearch[] = "F.MENU_ID='".$DB->ForSql($val,255)."'";
					break;

				}
			}
		}

		$sOrder = "";
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($key))
			{
				case "ID":		$sOrder .= ", F.ID ".$ord; break;
				case "LANGUAGE_ID":	$sOrder .= ", F.LANGUAGE_ID ".$ord; break;
				case "COMMON":	$sOrder .= ", F.COMMON ".$ord; break;
				case "USER_ID":	$sOrder .= ", F.USER_ID ".$ord; break;
				case "TIMESTAMP_X":	$sOrder .= ", F.TIMESTAMP_X ".$ord; break;
				case "MODIFIED_BY":	$sOrder .= ", F.MODIFIED_BY ".$ord; break;
				case "NAME":	$sOrder .= ", F.NAME ".$ord; break;
				case "URL":	$sOrder .= ", F.URL ".$ord; break;
				case "SORT":		$sOrder .= ", F.C_SORT ".$ord; break;
				case "MODULE_ID":		$sOrder .= ", F.MODULE_ID ".$ord; break;
				case "MENU_ID":		$sOrder .= ", F.MENU_ID ".$ord; break;
			}
		}
		if (strlen($sOrder)<=0)
			$sOrder = "F.ID DESC";
		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				F.ID, F.C_SORT, F.NAME, F.MENU_ID, F.URL, F.MODIFIED_BY, F.CREATED_BY, F.MODULE_ID, F.LANGUAGE_ID,
				F.COMMENTS, F.COMMON, F.USER_ID, UM.LOGIN AS M_LOGIN, UC.LOGIN as C_LOGIN, U.LOGIN, F.CODE_ID,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X,
				".$DB->DateToCharFunction("F.DATE_CREATE")."	DATE_CREATE,
				".$DB->Concat($DB->IsNull("UM.NAME", "''"), "' '", $DB->IsNull("UM.LAST_NAME", "''"))." as M_USER_NAME,
				".$DB->Concat($DB->IsNull("UC.NAME", "''"), "' '", $DB->IsNull("UC.LAST_NAME", "''"))." as C_USER_NAME,
				".$DB->Concat($DB->IsNull("U.NAME", "''"), "' '", $DB->IsNull("U.LAST_NAME", "''"))." as USER_NAME
			FROM
				b_favorite F
				LEFT JOIN b_user UM ON (UM.ID = F.MODIFIED_BY)
				LEFT JOIN b_user UC ON (UC.ID = F.CREATED_BY)
				LEFT JOIN b_user U ON (U.ID = F.USER_ID)
			WHERE
			".$strSqlSearch."
			".$strSqlOrder;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}
}
