<?
IncludeModuleLangFile(__FILE__);

class CClusterGroup
{
	public function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields, 0))
			return false;

		$ID = $DB->Add("b_cluster_group", $arFields);

		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB, $APPLICATION;
		$aMsg = array();
		$ID = intval($ID);

		$rsWebNodes = CClusterWebnode::GetList(array(), array("=GROUP_ID"=>$ID));
		if($rsWebNodes->Fetch())
			$aMsg[] = array("text" => GetMessage("CLU_GROUP_HAS_WEBNODE"));

		$rsDBNodes = CClusterDBNode::GetList(array() ,array("=GROUP_ID"=>$group_id));
		if($rsWebNodes->Fetch())
			$aMsg[] = array("text" => GetMessage("CLU_GROUP_HAS_DBNODE"));

		/*TODO: memcache check*/

		if(empty($aMsg))
		{
			$res = $DB->Query("DELETE FROM b_cluster_group WHERE ID = ".$ID, false, '', array('fixed_connection'=>true));
		}
		else
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return $res;
	}

	public function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if($ID <= 0)
			return false;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_cluster_group", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSql = "
				UPDATE b_cluster_group SET
				".$strUpdate."
				WHERE ID = ".$ID."
			";
			if(!$DB->Query($strSql, false, '', array('fixed_connection'=>true)))
				return false;
		}

		return true;
	}

	public static function CheckFields(&$arFields, $ID)
	{
		global $APPLICATION;
		$aMsg = array();

		unset($arFields["ID"]);

		$arFields["NAME"] = trim($arFields["NAME"]);
		if(strlen($arFields["NAME"]) <= 0)
		{
			$aMsg[] = array("id" => "NAME", "text" => GetMessage("CLU_GROUP_EMPTY_NAME"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function GetList($arOrder=false, $arFilter=false, $arSelect=false)
	{
		global $DB;

		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array(
				"ID",
				"NAME",
			);

		if(!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection)=="ASC"? "ASC": "DESC";
			switch($strColumn)
			{
				case "ID":
				case "NAME":
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
					break;
			}
		}

		$arQuerySelect = array();
		foreach($arSelect as $strColumn)
		{
			$strColumn = strtoupper($strColumn);
			switch($strColumn)
			{
				case "ID":
				case "NAME":
					$arQuerySelect[$strColumn] = "g.".$strColumn;
					break;
			}
		}
		if(count($arQuerySelect) < 1)
			$arQuerySelect = array("ID"=>"w.ID");

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "g",
				"FIELD_NAME" => "g.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		);
		$obQueryWhere->SetFields($arFields);

		if(!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$bDistinct = $obQueryWhere->bDistinctReqired;

		$strSql = "
			SELECT ".($bDistinct? "DISTINCT": "")."
			".implode(", ", $arQuerySelect)."
			FROM
				b_cluster_group g
			".$obQueryWhere->GetJoins()."
		";

		if($strQueryWhere)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}

		if(count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql, false, '', array('fixed_connection'=>true));
	}

	public static function GetArrayByID($ID)
	{
		$rs = CClusterGroup::GetList(array(), array("=ID"=>$ID));
		return $rs->Fetch();
	}
}
?>