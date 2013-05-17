<?
class CAllCatalogLoad
{
	public static function GetList($arOrder=Array("LAST_USED"=>"DESC"), $arFilter=Array())
	{
		global $DB;
		$arSqlSearch = Array();

		if(!is_array($arFilter)) 
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for($i=0; $i<count($filter_keys); $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(strtoupper($key))
			{
			case "NAME":
				$arSqlSearch[] = "CL.NAME ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "TYPE":
				$arSqlSearch[] = "CL.TYPE ".($bInvert?"<>":"=")." '".$val."'";
				break;
			}
		}

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql = 
			"SELECT CL.NAME, CL.VALUE, CL.TYPE, CL.LAST_USED ".
			"FROM b_catalog_load CL ".
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "NAME") $arSqlOrder[] = " CL.NAME ".$order." ";
			elseif ($by == "TYPE") $arSqlOrder[] = " CL.TYPE ".$order." ";
			else
			{
				$arSqlOrder[] = " CL.LAST_USED ".$order." ";
				$by = "LAST_USED";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder); for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ", ";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	public static function Add($arFields)
	{
		global $DB;

		if ($arFields["TYPE"]!="E") $arFields["TYPE"] = "I";

		$arInsert = $DB->PrepareInsert("b_catalog_load", $arFields);

		$strSql =
			"INSERT INTO b_catalog_load(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		return $DB->Query("DELETE FROM b_catalog_load WHERE NAME = '".intval($ID)."' ", true);
	}

	public static function SetLastUsed($NAME, $TYPE)
	{
		global $DB;

		$DB->Query(
			"UPDATE b_catalog_load SET ".
			"	LAST_USED = 'N' ".
			"WHERE TYPE = '".$DB->ForSql($TYPE)."'");

		$DB->Query(
			"UPDATE b_catalog_load SET ".
			"	LAST_USED = 'Y' ".
			"WHERE NAME = '".$DB->ForSql($NAME)."' ".
			"	AND TYPE = '".$DB->ForSql($TYPE)."'");

		return true;
	}
}
?>