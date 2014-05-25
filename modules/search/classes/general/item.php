<?
class CSearchItem extends CDBResult
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arSelect = array())
	{
		global $DB;

		$strSearchContentAlias = "sc";
		$sqlSelect = array();
		$sqlOrder = array();
		$sqlWhere = "";

		if(!is_array($arSelect))
			$arSelect = array();

		if(is_array($arOrder))
		{
			foreach($arOrder as $key => $ord)
			{
				$ord = strtoupper($ord) <> "ASC"? "DESC": "ASC";
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$sqlOrder[$key] = $strSearchContentAlias.".".$key." ".$ord;
						$arSelect[] = "ID";
						break;
				}
			}
		}

		$arSelect[] = "ID";
		$arSelect[] = "SITE_ID";
		$arSelect[] = "MODULE_ID";
		$arSelect[] = "ITEM_ID";
		$arSelect[] = "PARAM1";
		$arSelect[] = "PARAM2";

		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			switch($field)
			{
				case "ID":
				case "MODULE_ID":
				case "ITEM_ID":
				case "BODY":
				case "PARAM1":
				case "PARAM2":
				case "CUSTOM_RANK":
				case "USER_ID":
				case "ENTITY_TYPE_ID":
				case "ENTITY_ID":
				case "TITLE":
				case "TAGS":
					$sqlSelect[$field] = $strSearchContentAlias.".".$field;
					break;
				case "URL":
					$sqlSelect[$field] = $strSearchContentAlias.".".$field;
					$sqlSelect["SITE_URL"] = "scsite.".$field." SITE_URL";
					break;
				case "SITE_ID":
					$sqlSelect["SITE_ID"] = "scsite.".$field;
					break;
			}
		}

		if(is_array($arFilter))
		{
			$obQueryWhere = new CSQLWhere;
			$obQueryWhere->SetFields(array(
				"MODULE_ID" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".MODULE_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"ITEM_ID" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".ITEM_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"PARAM1" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".PARAM1",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"PARAM2" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".PARAM2",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"USER_ID" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".USER_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "int",
					"JOIN" => false,
				),
				"ENTITY_TYPE_ID" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".ENTITY_TYPE_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"ENTITY_ID" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".ENTITY_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => false,
				),
				"DATE_FROM" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".DATE_FROM",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "datetime",
					"JOIN" => false,
				),
				"DATE_TO" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".DATE_TO",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "datetime",
					"JOIN" => false,
				),
				"DATE_CHANGE" => array(
					"TABLE_ALIAS" => $strSearchContentAlias,
					"FIELD_NAME" => $strSearchContentAlias.".DATE_CHANGE",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "datetime",
					"JOIN" => false,
				),
				"SITE_ID" => array(
					"TABLE_ALIAS" => "scsite",
					"FIELD_NAME" => "scsite.SITE_ID",
					"MULTIPLE" => "N",
					"FIELD_TYPE" => "string",
					"JOIN" => true,
				),
			));
			$sqlWhere = $obQueryWhere->GetQuery($arFilter);
		}

		$strSql = "SELECT
			".implode(", ", $sqlSelect)."
			FROM
				b_search_content ".$strSearchContentAlias."
				INNER JOIN b_search_content_site scsite on scsite.SEARCH_CONTENT_ID = ".$strSearchContentAlias.".ID
			".($sqlWhere? "WHERE ".$sqlWhere: "")."
			".(!empty($sqlOrder)? "ORDER BY ".implode(", ", $sqlOrder): "")."
		";

		$res = $DB->Query($strSql);
		$res = new CSearchItem($res);
		return $res;
	}

	function Fetch()
	{
		static $arSite = array();

		$r = parent::Fetch();
		if($r)
		{
			$site_id = $r["SITE_ID"];
			if(!isset($arSite[$site_id]))
			{
				$rsSite = CSite::GetList($b, $o, array("ID"=>$site_id));
				$arSite[$site_id] = $rsSite->Fetch();
			}
			$r["DIR"] = $arSite[$site_id]["DIR"];
			$r["SERVER_NAME"] = $arSite[$site_id]["SERVER_NAME"];

			if(strlen($r["SITE_URL"])>0)
				$r["URL"] = $r["SITE_URL"];

			if(substr($r["URL"], 0, 1)=="=")
			{
				foreach(GetModuleEvents("search", "OnSearchGetURL", true) as $arEvent)
					$r["URL"] = ExecuteModuleEventEx($arEvent, array($r));
			}

			$r["URL"] = str_replace(
				array("#LANG#", "#SITE_DIR#", "#SERVER_NAME#"),
				array($r["DIR"], $r["DIR"], $r["SERVER_NAME"]),
				$r["URL"]
			);
			$r["URL"] = preg_replace("'(?<!:)/+'s", "/", $r["URL"]);

			unset($r["SITE_URL"]);
		}

		return $r;
	}

}
?>