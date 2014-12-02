<?
IncludeModuleLangFile(__FILE__);

class CSecurityIPRule
{
	static $bActive = null;

	public function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if(!$this->CheckFields($arFields, 0))
			return false;

		if(!array_key_exists("RULE_TYPE", $arFields))
			$arFields["RULE_TYPE"] = "M";

		if(!array_key_exists("ADMIN_SECTION", $arFields))
			$arFields["ADMIN_SECTION"] = "Y";

		if(!array_key_exists("ACTIVE", $arFields))
			$arFields["ACTIVE"] = "Y";

		if(!array_key_exists("SORT", $arFields))
			$arFields["SORT"] = 500;

		$ID = $DB->Add("b_sec_iprule", $arFields);

		if($ID > 0)
		{
			if(array_key_exists("INCL_MASKS", $arFields))
			{
				if(array_key_exists("EXCL_MASKS", $arFields))
					$this->UpdateRuleMasks($ID, $arFields["INCL_MASKS"], $arFields["EXCL_MASKS"]);
				else
					$this->UpdateRuleMasks($ID, $arFields["INCL_MASKS"], false);
			}
			else
			{
				if(array_key_exists("EXCL_MASKS", $arFields))
					$this->UpdateRuleMasks($ID, false, $arFields["EXCL_MASKS"]);
			}

			if(array_key_exists("INCL_IPS", $arFields))
			{
				if(array_key_exists("EXCL_IPS", $arFields))
					$this->UpdateRuleIPs($ID, $arFields["INCL_IPS"], $arFields["EXCL_IPS"]);
				else
					$this->UpdateRuleIPs($ID, $arFields["INCL_IPS"], false);
			}
			else
			{
				if(array_key_exists("EXCL_IPS", $arFields))
					$this->UpdateRuleIPs($ID, false, $arFields["EXCL_IPS"]);
			}
		}

		COption::RemoveOption("security", "iprules_count");
		CSecurityIPRule::SetActive(CSecurityIPRule::GetActiveCount() > 0);
		if(CACHED_b_sec_iprule !== false)
			$CACHE_MANAGER->CleanDir("b_sec_iprule");

		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intval($ID);

		$DB->StartTransaction();


		$res = $DB->Query("DELETE FROM b_sec_iprule_incl_mask WHERE IPRULE_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$res = $DB->Query("DELETE FROM b_sec_iprule_excl_mask WHERE IPRULE_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$res = $DB->Query("DELETE FROM b_sec_iprule_incl_ip WHERE IPRULE_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$res = $DB->Query("DELETE FROM b_sec_iprule_excl_ip WHERE IPRULE_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$res = $DB->Query("DELETE FROM b_sec_iprule WHERE ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$DB->Commit();
		else
			$DB->Rollback();

		COption::RemoveOption("security", "iprules_count");
		CSecurityIPRule::SetActive(CSecurityIPRule::GetActiveCount() > 0);
		if(CACHED_b_sec_iprule !== false)
			$CACHE_MANAGER->CleanDir("b_sec_iprule");

		return $res;
	}

	public function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intval($ID);

		if($ID <= 0)
			return false;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sec_iprule", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSql = "
				UPDATE b_sec_iprule SET
				".$strUpdate."
				WHERE ID = ".$ID."
			";
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}

		if(array_key_exists("INCL_MASKS", $arFields))
		{
			if(array_key_exists("EXCL_MASKS", $arFields))
				$this->UpdateRuleMasks($ID, $arFields["INCL_MASKS"], $arFields["EXCL_MASKS"]);
			else
				$this->UpdateRuleMasks($ID, $arFields["INCL_MASKS"], false);
		}
		else
		{
			if(array_key_exists("EXCL_MASKS", $arFields))
				$this->UpdateRuleMasks($ID, false, $arFields["EXCL_MASKS"]);
		}

		if(array_key_exists("INCL_IPS", $arFields))
		{
			if(array_key_exists("EXCL_IPS", $arFields))
				$this->UpdateRuleIPs($ID, $arFields["INCL_IPS"], $arFields["EXCL_IPS"]);
			else
				$this->UpdateRuleIPs($ID, $arFields["INCL_IPS"], false);
		}
		else
		{
			if(array_key_exists("EXCL_IPS", $arFields))
				$this->UpdateRuleIPs($ID, false, $arFields["EXCL_IPS"]);
		}

		COption::RemoveOption("security", "iprules_count");
		CSecurityIPRule::SetActive(CSecurityIPRule::GetActiveCount() > 0);
		if(CACHED_b_sec_iprule !== false)
			$CACHE_MANAGER->CleanDir("b_sec_iprule");

		return true;
	}

	public static function UpdateRuleMasks($IPRULE_ID, $arInclMasks = false, $arExclMasks = false)
	{
		global $DB, $CACHE_MANAGER;
		$IPRULE_ID = intval($IPRULE_ID);
		if(!$IPRULE_ID)
			return false;

		$arLikeSearch = array("?", "*", ".");
		$arLikeReplace = array("_",  "%", "\\.");
		$arPregSearch = array("\\", ".",  "?", "*",   "'");
		$arPregReplace = array("/",  "\.", ".", ".*?", "\'");

		if(is_array($arInclMasks))
		{
			$res = $DB->Query("DELETE FROM b_sec_iprule_incl_mask WHERE IPRULE_ID = ".$IPRULE_ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{

				$added = array();
				$i = 10;
				foreach($arInclMasks as $mask)
				{
					$mask = trim($mask);
					if($mask && !array_key_exists($mask, $added))
					{
						$arMask = array(
							"ID" => 1,
							"IPRULE_ID" => $IPRULE_ID,
							"RULE_MASK" => $mask,
							"SORT" => $i,
							"LIKE_MASK" => str_replace($arLikeSearch, $arLikeReplace, $mask),
							"PREG_MASK" => str_replace($arPregSearch, $arPregReplace, $mask),
						);
						$DB->Add("b_sec_iprule_incl_mask", $arMask);
						$i += 10;
						$added[$mask] = true;
					}
				}

				if(CACHED_b_sec_iprule !== false)
					$CACHE_MANAGER->CleanDir("b_sec_iprule");
			}
		}

		if(is_array($arExclMasks))
		{
			$res = $DB->Query("DELETE FROM b_sec_iprule_excl_mask WHERE IPRULE_ID = ".$IPRULE_ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{

				$added = array();
				$i = 10;
				foreach($arExclMasks as $mask)
				{
					$mask = trim($mask);
					if($mask && !array_key_exists($mask, $added))
					{
						$arMask = array(
							"ID" => 1,
							"IPRULE_ID" => $IPRULE_ID,
							"RULE_MASK" => $mask,
							"SORT" => $i,
							"LIKE_MASK" => str_replace($arLikeSearch, $arLikeReplace, $mask),
							"PREG_MASK" => str_replace($arPregSearch, $arPregReplace, $mask),
						);
						$DB->Add("b_sec_iprule_excl_mask", $arMask);
						$i += 10;
						$added[$mask] = true;
					}
				}

				if(CACHED_b_sec_iprule !== false)
					$CACHE_MANAGER->CleanDir("b_sec_iprule");
			}
		}

		return true;
	}

	public static function UpdateRuleIPs($IPRULE_ID, $arInclIPs=false, $arExclIPs=false)
	{
		global $DB, $CACHE_MANAGER;
		$IPRULE_ID = intval($IPRULE_ID);
		if(!$IPRULE_ID)
			return false;

		if(is_array($arInclIPs))
		{
			$res = $DB->Query("DELETE FROM b_sec_iprule_incl_ip WHERE IPRULE_ID = ".$IPRULE_ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{
				$added = array();
				$i = 10;
				foreach($arInclIPs as $ip)
				{
					$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
					if($ip && !array_key_exists($ip, $added))
					{
						$ar = explode("-", $ip);
						$ip1 = self::ip2number($ar[0]);
						$ip2 = self::ip2number($ar[1]);
						if($ip2 <= 0)
							$ip2 = $ip1;
						$arIP = array(
							"ID" => 1,
							"IPRULE_ID" => $IPRULE_ID,
							"RULE_IP" => $ip,
							"SORT" => $i,
							"~IP_START" => $ip1,
							"~IP_END" => $ip2,
						);
						$DB->Add("b_sec_iprule_incl_ip", $arIP);
						$i += 10;
						$added[$ip] = true;
					}
				}

				if(CACHED_b_sec_iprule !== false)
					$CACHE_MANAGER->CleanDir("b_sec_iprule");

			}
		}

		if(is_array($arExclIPs))
		{
			$res = $DB->Query("DELETE FROM b_sec_iprule_excl_ip WHERE IPRULE_ID = ".$IPRULE_ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{
				$added = array();
				$i = 10;
				foreach($arExclIPs as $ip)
				{
					$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
					if($ip && !array_key_exists($ip, $added))
					{
						$ar = explode("-", $ip);
						$ip1 = self::ip2number($ar[0]);
						$ip2 = self::ip2number($ar[1]);
						if($ip2 <= 0)
							$ip2 = $ip1;
						$arIP = array(
							"ID" => 1,
							"IPRULE_ID" => $IPRULE_ID,
							"RULE_IP" => $ip,
							"SORT" => $i,
							"~IP_START" => $ip1,
							"~IP_END" => $ip2,
						);
						$DB->Add("b_sec_iprule_excl_ip", $arIP);
						$i += 10;
						$added[$ip] = true;
					}
				}

				if(CACHED_b_sec_iprule !== false)
					$CACHE_MANAGER->CleanDir("b_sec_iprule");

			}
		}

		return true;
	}

	protected static function ip2number($ip)
	{
		$ip = trim($ip);
		if(strlen($ip) > 0)
			$res = doubleval(sprintf("%u", ip2long(trim($ip))));
		else
			$res = 0;
		return $res;
	}

	public function CheckIP($arInclIPs=false, $arExclIPs=false)
	{
		global $DB, $APPLICATION;

		$bFound = false;

		$ip2check = self::ip2number($_SERVER["REMOTE_ADDR"]);
		if($ip2check > 0 && is_array($arInclIPs))
		{
			foreach($arInclIPs as $id => $ip)
			{
				$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
				if($ip)
				{
					$ar = explode("-", $ip);
					$ip1 = self::ip2number($ar[0]);
					$ip2 = self::ip2number($ar[1]);
					if($ip2 <= 0)
						$ip2 = $ip1;
					if($ip2check >= $ip1 && $ip2check <= $ip2)
					{
						$bFound = true;
						break;
					}
				}
			}
		}

		if($bFound && $ip2check > 0 && is_array($arExclIPs))
		{
			foreach($arExclIPs as $id => $ip)
			{
				$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
				if($ip)
				{
					$ar = explode("-", $ip);
					$ip1 = self::ip2number($ar[0]);
					$ip2 = self::ip2number($ar[1]);
					if($ip2 <= 0)
						$ip2 = $ip1;
					if($ip2check >= $ip1 && $ip2check <= $ip2)
					{
						$bFound = false;
						break;
					}
				}
			}
		}

		if($bFound)
		{
			if(COption::GetOptionString("security", "ipcheck_allow_self_block")==="Y")
				$text = GetMessage("SECURITY_IPRULE_ERROR_SELF_BLOCK", array("#IP#" => htmlspecialcharsex($_SERVER["REMOTE_ADDR"])));
			else
				$text = GetMessage("SECURITY_IPRULE_ERROR_SELF_BLOCK_2", array("#IP#" => htmlspecialcharsex($_SERVER["REMOTE_ADDR"])));

			$e = new CAdminException(array(
				array(
					"id"=>"IPS[".htmlspecialcharsex($id)."]",
					"text"=>$text,
				),
			));
			$APPLICATION->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return true;
		}
		else
		{
			$this->LAST_ERROR = "";
			return false;
		}
	}

	public function CheckFields(&$arFields, $ID)
	{
		global $DB, $APPLICATION;

		$this->LAST_ERROR = "";
		$aMsg = array();

		if(array_key_exists("RULE_TYPE", $arFields))
		{
			if($arFields["RULE_TYPE"] !== "A")
				$arFields["RULE_TYPE"] = "M";

		}

		if(array_key_exists("SORT", $arFields))
		{
			if(intval($arFields["SORT"]) <= 0)
				$arFields["SORT"] = 500;
		}

		if(array_key_exists("NAME", $arFields))
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
		}

		if(array_key_exists("ACTIVE_FROM", $arFields))
		{
			$arFields["ACTIVE_FROM_TIMESTAMP"] = MakeTimeStamp($arFields["ACTIVE_FROM"], CSite::GetDateFormat());
		}

		if(array_key_exists("ACTIVE_TO", $arFields))
		{
			$arFields["ACTIVE_TO_TIMESTAMP"] = MakeTimeStamp($arFields["ACTIVE_TO"], CSite::GetDateFormat());
		}

		if(array_key_exists("ACTIVE", $arFields))
		{
			$arFields["ACTIVE"] = $arFields["ACTIVE"] === "Y"? "Y": "N";
		}

		if(array_key_exists("ADMIN_SECTION", $arFields))
		{
			$arFields["ADMIN_SECTION"] = $arFields["ADMIN_SECTION"] === "Y"? "Y": "N";
		}

		if(array_key_exists("INCL_IPS", $arFields) && is_array($arFields["INCL_IPS"]))
		{
			foreach($arFields["INCL_IPS"] as $id => $ip)
			{
				$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
				if($ip)
				{
					$ar = explode("-", $ip);
					$ip1 = self::ip2number($ar[0]);
					if($ip1 <= 0)
						$aMsg[] = array("id"=>"INCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP", array("#IP#" => htmlspecialcharsex($ar[0]))));
					if(count($ar) > 1)
					{
						$ip2 = self::ip2number($ar[1]);
						if($ip2 <= 0)
							$aMsg[] = array("id"=>"INCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP", array("#IP#" => htmlspecialcharsex($ar[1]))));
						elseif($ip2 < $ip1)
							$aMsg[] = array("id"=>"INCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP_RANGE", array("#END_IP#" => htmlspecialcharsex($ar[1]), "#START_IP#" => htmlspecialcharsex($ar[0]))));
						break;
					}
				}
			}
		}

		if(array_key_exists("EXCL_IPS", $arFields) && is_array($arFields["EXCL_IPS"]))
		{
			foreach($arFields["EXCL_IPS"] as $id => $ip)
			{
				$ip = preg_replace("/[\\s]/".BX_UTF_PCRE_MODIFIER, "", $ip);
				if($ip)
				{
					$ar = explode("-", $ip);
					$ip1 = self::ip2number($ar[0]);
					if($ip1 <= 0)
						$aMsg[] = array("id"=>"EXCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP", array("#IP#" => htmlspecialcharsex($ar[0]))));
					if(count($ar) > 1)
					{
						$ip2 = self::ip2number($ar[1]);
						if($ip2 <= 0)
							$aMsg[] = array("id"=>"EXCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP", array("#IP#" => htmlspecialcharsex($ar[1]))));
						elseif($ip2 < $ip1)
							$aMsg[] = array("id"=>"EXCL_IPS[".htmlspecialcharsex($id)."]", "text"=>GetMessage("SECURITY_IPRULE_ERROR_WONG_IP_RANGE", array("#END_IP#" => htmlspecialcharsex($ar[1]), "#START_IP#" => htmlspecialcharsex($ar[0]))));
						break;
					}
				}
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}
		return true;
	}

	public static function GetRuleInclMasks($IPRULE_ID)
	{
		global $DB;
		$IPRULE_ID = intval($IPRULE_ID);
		$res = array();
		if($IPRULE_ID)
		{
			$rs = $DB->Query("SELECT RULE_MASK FROM b_sec_iprule_incl_mask WHERE IPRULE_ID = ".$IPRULE_ID." ORDER BY SORT");
			while($ar = $rs->Fetch())
				$res[] = $ar["RULE_MASK"];
		}
		return $res;
	}

	public static function DeleteRuleExclFiles($files)
	{
		global $DB;
		if (!is_array($files))
			$files = array($files);

		foreach ($files as $file) 
				$DB->Query("DELETE FROM b_sec_iprule_excl_mask WHERE RULE_MASK = '".$DB->ForSQL($file)."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function AddRuleExclFiles($files)
	{
		if (empty($files))
			return;
	
		$exclToUpdate = array();
		if (!is_array($files))
			$files = array($files);

		foreach ($files as $file)
		{
			$rsIPRule = CSecurityIPRule::GetList(array("ID"), array(
					"PATH" => $file,
					"ACTIVE" => "Y",
					), array("ID" => "ASC"));

			$masks = array();
			while ($arIPRule = $rsIPRule->Fetch())
			{

				if (array_key_exists($arIPRule["ID"], $exclToUpdate))
					$masks = array_merge($exclToUpdate[$arIPRule["ID"]],$masks);
				else
					$masks = array($file);

				$exclToUpdate[$arIPRule["ID"]]= $masks;
			}
		}

		foreach ($exclToUpdate as $rule_id => $excl_mask) 
		{
			$masks=CSecurityIPRule::GetRuleExclMasks($rule_id);
			$masks = array_unique(array_merge($masks,$excl_mask));
			CSecurityIPRule::UpdateRuleMasks($rule_id,false,$masks);
		}
	}

	public static function GetRuleExclFiles($files)
	{
		global $DB;
		$res=array();
		if (!is_array($files))
			$files = array($files);
		
		if (!empty($files))
		{
			$files=array_map(array($DB,'ForSQL'),$files);
			$masks=implode("','", $files);
			$rs = $DB->Query("SELECT IPRULE_ID FROM b_sec_iprule_excl_mask WHERE RULE_MASK IN ('".$masks."')", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
				$res[] = $ar["IPRULE_ID"];
		}
		return $res;
	}

	public static function GetRuleExclMasks($IPRULE_ID)
	{
		global $DB;
		$IPRULE_ID = intval($IPRULE_ID);
		$res = array();
		if($IPRULE_ID)
		{
			$rs = $DB->Query("SELECT RULE_MASK FROM b_sec_iprule_excl_mask WHERE IPRULE_ID = ".$IPRULE_ID." ORDER BY SORT");
			while($ar = $rs->Fetch())
				$res[] = $ar["RULE_MASK"];
		}
		return $res;
	}

	public static function GetRuleInclIPs($IPRULE_ID)
	{
		global $DB;
		$IPRULE_ID = intval($IPRULE_ID);
		$res = array();
		if($IPRULE_ID)
		{
			$rs = $DB->Query("SELECT RULE_IP FROM b_sec_iprule_incl_ip WHERE IPRULE_ID = ".$IPRULE_ID." ORDER BY SORT");
			while($ar = $rs->Fetch())
				$res[] = $ar["RULE_IP"];
		}
		return $res;
	}

	public static function GetRuleExclIPs($IPRULE_ID)
	{
		global $DB;
		$IPRULE_ID = intval($IPRULE_ID);
		$res = array();
		if($IPRULE_ID)
		{
			$rs = $DB->Query("SELECT RULE_IP FROM b_sec_iprule_excl_ip WHERE IPRULE_ID = ".$IPRULE_ID." ORDER BY SORT");
			while($ar = $rs->Fetch())
				$res[] = $ar["RULE_IP"];
		}
		return $res;
	}

	public static function GetList($arSelect, $arFilter, $arOrder)
	{
		global $DB;

		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array(
				"ID",
				"RULE_TYPE",
				"ACTIVE",
				"ADMIN_SECTION",
				"SITE_ID",
				"SORT",
				"NAME",
				"ACTIVE_FROM",
				"ACTIVE_TO",
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
				case "RULE_TYPE":
				case "ACTIVE":
				case "ADMIN_SECTION":
				case "SITE_ID":
				case "SORT":
				case "NAME":
				case "ACTIVE_FROM":
				case "ACTIVE_TO":
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
				case "RULE_TYPE":
				case "ACTIVE":
				case "ADMIN_SECTION":
				case "SITE_ID":
				case "SORT":
				case "NAME":
				case "ACTIVE_FROM_TIMESTAMP":
				case "ACTIVE_TO_TIMESTAMP":
					$arQuerySelect[$strColumn] = "r.".$strColumn;
					break;
				case "ACTIVE_FROM":
				case "ACTIVE_TO":
					$arQuerySelect[$strColumn] = $DB->DateToCharFunction("r.".$strColumn, "FULL")." AS ".$strColumn;
					break;
			}
		}
		if(count($arQuerySelect) < 1)
			$arQuerySelect = array("ID"=>"r.ID");

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"RULE_TYPE" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.RULE_TYPE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ACTIVE" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.ACTIVE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ADMIN_SECTION" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.ADMIN_SECTION",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"SITE_ID" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.SITE_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"SORT" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.SORT",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"NAME" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ACTIVE_FROM" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.ACTIVE_FROM",
				"FIELD_TYPE" => "datetime",
				"JOIN" => false,
			),
			"ACTIVE_TO" => array(
				"TABLE_ALIAS" => "r",
				"FIELD_NAME" => "r.ACTIVE_TO",
				"FIELD_TYPE" => "datetime",
				"JOIN" => false,
			),
		);
		$obQueryWhere->SetFields($arFields);

		if(!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$bDistinct = $obQueryWhere->bDistinctReqired;

		$strMaskJoin = "";
		if(array_key_exists("PATH", $arFilter))
		{
			$path = trim($arFilter["PATH"]);
			if($path)
			{
				$bDistinct = true;
				$strMaskJoin = "
					INNER JOIN b_sec_iprule_incl_mask im on im.IPRULE_ID = r.ID
					LEFT JOIN b_sec_iprule_excl_mask em on em.IPRULE_ID = r.ID AND '".$DB->ForSQL($path)."' like em.LIKE_MASK
				";
				$strMaskWhere = "('".$DB->ForSQL($path)."' like im.LIKE_MASK AND em.IPRULE_ID is null)";

				if($strQueryWhere)
					$strQueryWhere = "(".$strQueryWhere.") AND ".$strMaskWhere;
				else
					$strQueryWhere = $strMaskWhere;
			}
		}

		$strIPJoin = "";
		if(array_key_exists("IP", $arFilter))
		{
			$ip = self::ip2number($arFilter["IP"]);
			if($ip > 0)
			{
				$bDistinct = true;
				$strIPJoin = "
					INNER JOIN b_sec_iprule_incl_ip ii on ii.IPRULE_ID = r.ID
					LEFT JOIN b_sec_iprule_excl_ip ei on ei.IPRULE_ID = r.ID AND ".$ip." between ei.IP_START AND ei.IP_END
				";
				$strIPWhere = "(".$ip." between ii.IP_START AND ii.IP_END AND ei.IPRULE_ID is null)";
				if($strQueryWhere)
					$strQueryWhere = "(".$strQueryWhere.") AND ".$strIPWhere;
				else
					$strQueryWhere = $strIPWhere;
			}
		}

		$strSql = "
			SELECT ".($bDistinct? "DISTINCT": "")."
			".implode(", ", $arQuerySelect)."
			FROM
				b_sec_iprule r
				".$strMaskJoin."
				".$strIPJoin."
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
		//echo "<pre>",htmlspecialcharsbx($strSql),"</pre><hr>";
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetActiveCount()
	{
		$c = COption::GetOptionInt("security", "iprules_count", -1);
		if($c < 0)
		{
			global $DB;
			$rs = $DB->Query("SELECT count(*) CNT FROM b_sec_iprule WHERE ACTIVE='Y'");
			$ar = $rs->Fetch();
			COption::SetOptionInt("security", "iprules_count", $ar["CNT"]);
			$c = COption::GetOptionInt("security", "iprules_count", -1);
		}
		return $c;
	}

	public static function IsActive()
	{
		if(isset(self::$bActive) && self::$bActive === true)
			return true;

		$bActive = false;
		foreach(GetModuleEvents("main", "OnPageStart", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityIPRule"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false, $end_time = 0)
	{
		if($bActive)
		{
			if(!CSecurityIPRule::IsActive())
				RegisterModuleDependences("main", "OnPageStart", "security", "CSecurityIPRule", "OnPageStart", "2");
		}
		else
		{
			if(CSecurityIPRule::IsActive())
				UnRegisterModuleDependences("main", "OnPageStart", "security", "CSecurityIPRule", "OnPageStart");
		}

		self::$bActive = $bActive;
	}

	public static function CheckAntiFile($retun_message = false)
	{
		$file = COption::GetOptionString("security", "ipcheck_disable_file", "");
		$res = (strlen($file) > 0) && file_exists($_SERVER["DOCUMENT_ROOT"].$file) && is_file($_SERVER["DOCUMENT_ROOT"].$file);

		if($retun_message)
		{
			if($res)
				return new CAdminMessage(GetMessage("SECURITY_IPRULE_IPCHECK_DISABLE_FILE_WARNING"));
			else
				return false;
		}
		else
		{
			return $res;
		}
	}

	public static function OnPageStart($use_query = false)
	{
		//ToDo: good candidate for refactoring
		global $DB, $CACHE_MANAGER;

		if(
			!CSecuritySystemInformation::isCliMode()
			&& CSecurityIPRule::GetActiveCount()
		)
		{
			if(CSecurityIPRule::CheckAntiFile())
				return;

			$bMatch = false;

			$uri = $_SERVER['REQUEST_URI'];
			if (($pos = strpos($uri, '?')) !== false)
				$uri = substr($uri, 0, $pos);

			$uri = urldecode($uri);
			$uri = preg_replace('#/+#', '/', $uri);
			//Block any invalid uri
			if (!static::isValidUri($uri))
				include($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/security_403.php'); //die inside

			//Normalize on Windows, because my. == my
			if (CSecuritySystemInformation::isRunOnWin())
				$uri = preg_replace('#(. )+[/\\\]+#', '/', $uri);

			$ip2check = CSecurityIPRule::ip2number($_SERVER["REMOTE_ADDR"]);

			if(!$use_query && CACHED_b_sec_iprule !== false)
			{
				$cache_id = "b_sec_iprule";
				if($CACHE_MANAGER->Read(CACHED_b_sec_iprule, $cache_id, "b_sec_iprule"))
				{
					$arRules = $CACHE_MANAGER->Get($cache_id);
				}
				else
				{
					$arRules = array();

					$rs = $DB->Query("
						SELECT
							r.ID,
							r.ADMIN_SECTION,
							r.SITE_ID,
							r.ACTIVE_FROM_TIMESTAMP,
							r.ACTIVE_TO_TIMESTAMP
						FROM
							b_sec_iprule r
						WHERE
							r.ACTIVE='Y'
							AND (
								r.ACTIVE_TO IS NULL
								OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction()."
							)
					");
					while($ar = $rs->Fetch())
					{
						$ar["ACTIVE_FROM_TIMESTAMP"] = intval($ar["ACTIVE_FROM_TIMESTAMP"]);
						$ar["ACTIVE_TO_TIMESTAMP"] = intval($ar["ACTIVE_TO_TIMESTAMP"]);
						$ar["INCL_MASKS"] = array();
						$ar["EXCL_MASKS"] = array();
						$ar["INCL_IPS"] = array();
						$ar["EXCL_IPS"] = array();
						$arRules[$ar["ID"]] = $ar;
					}

					$rs = $DB->Query("
						SELECT
							im.IPRULE_ID,
							im.PREG_MASK
						FROM
							b_sec_iprule r
							INNER JOIN b_sec_iprule_incl_mask im on im.IPRULE_ID = r.ID
						WHERE
							r.ACTIVE='Y'
							AND (
								r.ACTIVE_TO IS NULL
								OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction()."
							)
					");
					while($ar = $rs->Fetch())
						if(array_key_exists($ar["IPRULE_ID"], $arRules))
							$arRules[$ar["IPRULE_ID"]]["INCL_MASKS"][] = $ar["PREG_MASK"];

					foreach($arRules as $ID => $ar)
						if(count($ar["INCL_MASKS"]) <= 0)
							unset($arRules[$ID]);

					$rs = $DB->Query("
						SELECT
							em.IPRULE_ID,
							em.PREG_MASK
						FROM
							b_sec_iprule r
							INNER JOIN b_sec_iprule_excl_mask em on em.IPRULE_ID = r.ID
						WHERE
							r.ACTIVE='Y'
							AND (
								r.ACTIVE_TO IS NULL
								OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction()."
							)
					");
					while($ar = $rs->Fetch())
						if(array_key_exists($ar["IPRULE_ID"], $arRules))
							$arRules[$ar["IPRULE_ID"]]["EXCL_MASKS"][] = $ar["PREG_MASK"];

					$rs = $DB->Query("
						SELECT
							ii.IPRULE_ID,
							ii.IP_START,
							ii.IP_END
						FROM
							b_sec_iprule r
							INNER JOIN b_sec_iprule_incl_ip ii on ii.IPRULE_ID = r.ID
						WHERE
							r.ACTIVE='Y'
							AND (
								r.ACTIVE_TO IS NULL
								OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction()."
							)
					");
					while($ar = $rs->Fetch())
						if(array_key_exists($ar["IPRULE_ID"], $arRules))
							$arRules[$ar["IPRULE_ID"]]["INCL_IPS"][] = array(
								doubleval($ar["IP_START"]),
								doubleval($ar["IP_END"]),
							);

					foreach($arRules as $ID => $ar)
						if(count($ar["INCL_IPS"]) <= 0)
							unset($arRules[$ID]);

					$rs = $DB->Query("
						SELECT
							ei.IPRULE_ID,
							ei.IP_START,
							ei.IP_END
						FROM
							b_sec_iprule r
							INNER JOIN b_sec_iprule_excl_ip ei on ei.IPRULE_ID = r.ID
						WHERE
							r.ACTIVE='Y'
							AND (
								r.ACTIVE_TO IS NULL
								OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction()."
							)
					");
					while($ar = $rs->Fetch())
						if(array_key_exists($ar["IPRULE_ID"], $arRules))
							$arRules[$ar["IPRULE_ID"]]["EXCL_IPS"][] = array(
								doubleval($ar["IP_START"]),
								doubleval($ar["IP_END"]),
							);

					$CACHE_MANAGER->Set($cache_id, $arRules);
				}

				foreach($arRules as $arRule)
				{
					//Check if this rule is active
					if(
						($arRule["ACTIVE_FROM_TIMESTAMP"] <= 0 || $arRule["ACTIVE_FROM_TIMESTAMP"] <= time())
						&& ($arRule["ACTIVE_TO_TIMESTAMP"] <= 0 || $arRule["ACTIVE_TO_TIMESTAMP"] >= time())
					)
					{
						$bMatch = true;
					}
					else
					{
						$bMatch = false;
					}

					//Check if site does match
					if($bMatch)
					{
						if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
							$bMatch = $arRule["ADMIN_SECTION"] == "Y";
						else
							$bMatch = (!$arRule["SITE_ID"] || $arRule["SITE_ID"] == SITE_ID);
					}
					else
					{
						continue;
					}

					//Check if IP in blocked
					if($bMatch)
					{
						$bMatch = false;
						foreach($arRule["INCL_IPS"] as $arIP)
						{
							if($ip2check >= $arIP[0] && $ip2check <= $arIP[1])
							{
								$bMatch = true;
								break;
							}
						}
						//IP is in blocked range so check if it is exluded
						if($bMatch)
						{
							foreach($arRule["EXCL_IPS"] as $arIP)
							{
								if($ip2check >= $arIP[0] && $ip2check <= $arIP[1])
								{
									$bMatch = false;
									break;
								}
							}
						}
					}
					else
					{
						continue;
					}

					//IP does match to blocking condition let's check path
					if($bMatch)
					{
						$bMatch = false;
						foreach($arRule["INCL_MASKS"] as $mask)
						{
							if(preg_match("#^".$mask."$#", $uri))
							{
								$bMatch = true;
								break;
							}
						}
						//Check path for exclusion
						if($bMatch)
						{
							foreach($arRule["EXCL_MASKS"] as $mask)
							{
								if(preg_match("#^".$mask."$#", $uri))
								{
									$bMatch = false;
									break;
								}
							}
						}
					}
					else
					{
						continue;
					}

					//Found blocking rule
					if($bMatch)
						break;
				}
			}
			else
			{
				$strSql = "
					SELECT r.ID
					FROM
						b_sec_iprule r
						INNER JOIN b_sec_iprule_incl_mask im on im.IPRULE_ID = r.ID
						LEFT  JOIN b_sec_iprule_excl_mask em on em.IPRULE_ID = r.ID AND '".$DB->ForSQL($uri)."' like em.LIKE_MASK
						INNER JOIN b_sec_iprule_incl_ip   ii on ii.IPRULE_ID = r.ID
						LEFT  JOIN b_sec_iprule_excl_ip   ei on ei.IPRULE_ID = r.ID AND ".$ip2check." between ei.IP_START and ei.IP_END
					WHERE
						r.ACTIVE = 'Y'
						AND (r.ACTIVE_FROM IS NULL OR r.ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")
						AND (r.ACTIVE_TO IS NULL OR r.ACTIVE_TO >= ".$DB->CurrentTimeFunction().")
						".(defined("ADMIN_SECTION") && ADMIN_SECTION===true?
							"AND r.ADMIN_SECTION = 'Y'":
							"AND (r.SITE_ID IS NULL OR r.SITE_ID = '".$DB->ForSQL(SITE_ID)."')"
						)."
						AND '".$DB->ForSQL($uri)."' like im.LIKE_MASK
						AND em.IPRULE_ID is null
						AND ".$ip2check." between ii.IP_START and ii.IP_END
						AND ei.IPRULE_ID is null
				";
				//echo "<pre>".htmlspecialcharsbx($strSql)."</pre>";
				$rs = $DB->Query($strSql);

				if($arRule = $rs->Fetch())
					$bMatch = true;
				else
					$bMatch = false;
			}

			if($bMatch)
				include($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/security_403.php");

		}
	}

	protected static function isValidUri($uri)
	{
		if (trim($uri) == '')
			return false;

		if (strpos($uri, "\0") !== false)
			return false;

		if (strpos($uri, '/') !== 0)
			return false;

		if (CHTTP::isPathTraversalUri($uri))
			return false;

		return true;
	}

	public static function CleanUpAgent()
	{
		$agentName = "CSecurityIPRule::CleanUpAgent();";
		$cleanupDays = 2;
		$activeTo = ConvertTimeStamp(time() - $cleanupDays*24*60*60, "FULL");
		if(!$activeTo)
			return $agentName;

		$rs = CSecurityIPRule::GetList(
			array("ID"),
			array(
				"=RULE_TYPE" => "A",
				"<=ACTIVE_TO" => $activeTo,
			),
			array("ID"=>"ASC")
		);
		while($ar = $rs->Fetch())
		{
			CSecurityIPRule::Delete($ar["ID"]);
		}
		return $agentName;
	}
}
?>