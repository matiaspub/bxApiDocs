<?

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/rating_rule.php");

class CRatingRule
{
	// get specified rating rule
	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatingRule::err_mess())."<br>Function: GetByID<br>Line: ";

		if($ID<=0)
			return false;

		return ($DB->Query("
			SELECT
				PR.*,
				".$DB->DateToCharFunction("PR.CREATED")." CREATED,
				".$DB->DateToCharFunction("PR.LAST_MODIFIED")." LAST_MODIFIED
			FROM
				b_rating_rule PR
			WHERE
				ID=".$ID,
			false, $err_mess.__LINE__));
	}

	// get rating rule list
	public static function GetList($aSort=array(), $arFilter=Array())
	{
		global $DB;

		$arSqlSearch = Array();
		$strSqlSearch = "";
		$err_mess = (CRatingRule::err_mess())."<br>Function: GetList<br>Line: ";

		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || $val=="NOT_REF") continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case "ID":
						$arSqlSearch[] = GetFilterQuery("PR.ID",$val,"N");
					break;
					case "ACTIVE":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "PR.ACTIVE = '".$val."'";
					break;
					case "NAME":
						$arSqlSearch[] = GetFilterQuery("PR.NAME", $val);
					break;
					case "ENTITY_ID":
						$arSqlSearch[] = GetFilterQuery("PR.ENTITY_ID", $val);
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
				case "ID":		$sOrder .= ", PR.ID ".$ord; break;
				case "NAME":	$sOrder .= ", PR.NAME ".$ord; break;
				case "CREATED":	$sOrder .= ", PR.CREATED ".$ord; break;
				case "LAST_MODIFIED":	$sOrder .= ", PR.LAST_MODIFIED ".$ord; break;
				case "LAST_APPLIED":	$sOrder .= ", PR.LAST_APPLIED ".$ord; break;
				case "ACTIVE":	$sOrder .= ", PR.ACTIVE ".$ord; break;
				case "CALCULATION_METHOD":	$sOrder .= ", PR.CALCULATION_METHOD ".$ord; break;
				case "ENTITY_TYPE_ID":	$sOrder .= ", PR.ENTITY_TYPE_ID ".$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = "PR.ID DESC";

		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				PR.ID, PR.NAME, PR.ACTIVE, PR.ENTITY_TYPE_ID,
				".$DB->DateToCharFunction("PR.CREATED")." CREATED,
				".$DB->DateToCharFunction("PR.LAST_APPLIED")." LAST_APPLIED,
				".$DB->DateToCharFunction("PR.LAST_MODIFIED")." LAST_MODIFIED
			FROM
				b_rating_rule PR
			WHERE
			".$strSqlSearch."
			".$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	//Addition rating rule
	public static function Add($arFields)
	{
		global $DB;

		$err_mess = (CRatingRule::err_mess())."<br>Function: Add<br>Line: ";

		// check only general field
		if(!CRatingRule::__CheckFields($arFields))
			return false;

		if (!(isset($arFields['ENTITY_TYPE_ID']) && strlen($arFields['ENTITY_TYPE_ID']) > 0))
			return false;

		$arRatingRuleConfigs = CRatingRule::GetRatingRuleConfigs($arFields["ENTITY_TYPE_ID"]);
		
		$bHideAction = isset($arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['HIDE_ACTION']) 
							&& $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['HIDE_ACTION'] == true? true: false;
		
		$conditionModuleId = $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['MODULE'];
		$arFields_i = Array(
			"ACTIVE"					=> $arFields["ACTIVE"] == 'Y' ? 'Y' : 'N',
			"NAME"					=> $arFields["NAME"],
			"ENTITY_TYPE_ID"		=> $arFields["ENTITY_TYPE_ID"],
			"CONDITION_NAME"		=> $arFields["CONDITION_NAME"],
			"CONDITION_MODULE"	=> strlen($conditionModuleId)>0? $conditionModuleId: 'main',
			"CONDITION_CLASS"		=> $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['CLASS'],
			"CONDITION_METHOD"	=> $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['METHOD'],
			"ACTION_NAME"			=> $bHideAction? 'empty': $arFields["ACTION_NAME"],			
			"ACTIVATE_CLASS"		=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['CLASS'],
			"ACTIVATE_METHOD"		=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['METHOD'],
			"DEACTIVATE_CLASS"	=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['CLASS'],
			"DEACTIVATE_METHOD"	=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['METHOD'],
			"~CREATED"				=> $DB->GetNowFunction(),
			"~LAST_MODIFIED"		=> $DB->GetNowFunction(),
		);
		$ID = $DB->Add("b_rating_rule", $arFields_i);

		// queries modules and give them to inspect the field settings
		$db_events = GetModuleEvents("main", "OnAfterAddRatingRule");
		while($arEvent = $db_events->Fetch())
			$arFields = ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		$arFields_u = Array(
			"CONDITION_CONFIG" => "'".$DB->ForSQL(serialize($arFields["CONDITION_CONFIG"]))."'",
			"ACTION_CONFIG" => $bHideAction? "'a:0:{}'": "'".$DB->ForSQL(serialize($arFields["ACTION_CONFIG"]))."'",
		);
		$DB->Update("b_rating_rule", $arFields_u, "WHERE ID = ".$ID);
		
		CAgent::AddAgent("CRatingRule::Apply($ID);", "main", "N", $arRatingRuleConfigs['CONDITION'][$arFields["CONDITION_NAME"]]['REFRESH_TIME'], "", "Y", "");

		return $ID;
	}

	//Update rating
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatingRule::err_mess())."<br>Function: Update<br>Line: ";

		// check only general field
		if(!CRatingRule::__CheckFields($arFields))
			return false;

		if (isset($arFields["ENTITY_TYPE_ID"]) && strlen($arFields['ENTITY_TYPE_ID']) > 0)
		{
			$arRatingRuleConfigs = CRatingRule::GetRatingRuleConfigs($arFields["ENTITY_TYPE_ID"]);
			
			$bHideAction = isset($arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['HIDE_ACTION']) 
					&& $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['HIDE_ACTION'] == true? true: false;
			
			$conditionModuleId = $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['MODULE'];
			$arFields_u = Array(
				"ACTIVE"					=> $arFields["ACTIVE"] == 'Y' ? 'Y' : 'N',
				"NAME"					=> $arFields["NAME"],
				"ENTITY_TYPE_ID"		=> $arFields["ENTITY_TYPE_ID"],
				"CONDITION_NAME"		=> $arFields["CONDITION_NAME"],
				"CONDITION_MODULE"	=> strlen($conditionModuleId)>0? $conditionModuleId: 'main',
				"CONDITION_CLASS"		=> $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['CLASS'],
				"CONDITION_METHOD"	=> $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['METHOD'],
				"ACTION_NAME"			=> $bHideAction? 'empty': $arFields["ACTION_NAME"],				
				"ACTIVATE_CLASS"		=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['CLASS'],
				"ACTIVATE_METHOD"		=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['METHOD'],
				"DEACTIVATE_CLASS"	=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['CLASS'],
				"DEACTIVATE_METHOD"	=> $bHideAction? 'empty': $arRatingRuleConfigs['ACTION_CONFIG'][$arFields["ACTION_NAME"]]['METHOD'],
				"~LAST_MODIFIED"		=> $DB->GetNowFunction(),
			);
		}
		else
		{
			$arFields_u = Array(
				"ACTIVE"				=> $arFields['ACTIVE'] == 'Y' ? 'Y' : 'N',
				"NAME"					=> $arFields["NAME"],
				"~LAST_MODIFIED"		=> $DB->GetNowFunction(),
			);
			unset($arFields["CONDITION_CONFIG"]);
			unset($arFields["ACTION_CONFIG"]);
		}
		$strUpdate = $DB->PrepareUpdate("b_rating_rule", $arFields_u);
		if($strUpdate!="")
		{
			$strSql = "UPDATE b_rating_rule SET ".$strUpdate." WHERE ID=".$ID;
			if(!$DB->Query($strSql, false, $err_mess.__LINE__))
				return false;
		}

		if (isset($arFields["CONDITION_CONFIG"]))
		{
			// queries modules and give them to inspect the field settings
			$db_events = GetModuleEvents("main", "OnAfterUpdateRatingRule");
			while($arEvent = $db_events->Fetch())
				$arFields = ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			$arFields_u = Array(
				"CONDITION_CONFIG" => "'".$DB->ForSQL(serialize($arFields["CONDITION_CONFIG"]))."'",
				"ACTION_CONFIG" => $bHideAction? "'a:0:{}'": "'".$DB->ForSQL(serialize($arFields["ACTION_CONFIG"]))."'",
			);
			$DB->Update("b_rating_rule", $arFields_u, "WHERE ID = ".$ID);
		}
		
		CAgent::RemoveAgent("CRatingRule::Apply($ID);", "main");
		CAgent::AddAgent("CRatingRule::Apply($ID);", "main", "N", $arRatingRuleConfigs['CONDITION_CONFIG'][$arFields["CONDITION_NAME"]]['REFRESH_TIME'], "", "Y", "");

		return true;
	}

	// delete rating rule
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatingRule::err_mess())."<br>Function: Delete<br>Line: ";

		$db_events = GetModuleEvents("main", "OnBeforeDeleteRatingRule");
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		$DB->Query("DELETE FROM b_rating_rule WHERE ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_rule_vetting WHERE RULE_ID=$ID", false, $err_mess.__LINE__);

		CAgent::RemoveAgent("CRatingRule::Apply($ID);", "main");
		
		return true;
	}

	// start rating rule action
	public static function Apply($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatingRule::err_mess())."<br>Function: Apply<br>Line: ";

		$ratingRule = CRatingRule::GetByID($ID);
		$arConfigs = $ratingRule->Fetch();
		if ($arConfigs['ACTIVE'] == 'Y')
		{
			$arConfigs['CONDITION_CONFIG'] = unserialize(htmlspecialcharsback($arConfigs['CONDITION_CONFIG']));
			$arConfigs['ACTION_CONFIG']	 = unserialize(htmlspecialcharsback($arConfigs['ACTION_CONFIG']));
			
			$arConfigs['CONDITION_MODULE'] = isset($arConfigs['CONDITION_MODULE']) && strlen($arConfigs['CONDITION_MODULE'])> 0? $arConfigs['CONDITION_MODULE']: 'main';
			if(CModule::IncludeModule(strtolower($arConfigs['CONDITION_MODULE']))) {
				if (method_exists($arConfigs['CONDITION_CLASS'],  $arConfigs['CONDITION_METHOD']))
					call_user_func(array($arConfigs['CONDITION_CLASS'], $arConfigs['CONDITION_METHOD']), $arConfigs);

				$DB->Query("DELETE FROM b_rating_rule_vetting WHERE RULE_ID = $ID AND APPLIED = 'Y'", false, $err_mess.__LINE__);
			}
			if (method_exists($arConfigs['ACTIVATE_CLASS'],  $arConfigs['ACTIVATE_METHOD']))
				call_user_func(array($arConfigs['ACTIVATE_CLASS'], $arConfigs['ACTIVATE_METHOD']), $arConfigs);

			$DB->Query("UPDATE b_rating_rule SET LAST_APPLIED = ".$DB->GetNowFunction()." WHERE ID = $ID", false, $err_mess.__LINE__);	
		}
		return "CRatingRule::Apply($ID);";
	}

	// get vetting list
	public static function GetVetting($arFilter, $arSort)
	{
		global $DB;
		$err_mess = (CRatingRule::err_mess())."<br>Function: GetVetting<br>Line: ";

		$arSqlSearch = array();
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || $val=="NOT_REF") continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case "RULE_ID":
						$arSqlSearch[] = GetFilterQuery("RULE_ID",$val,"N");
					break;
					case "ENTITY_TYPE_ID":
						$arSqlSearch[] = GetFilterQuery("ENTITY_TYPE_ID",$val,"N");
					break;
					case "ENTITY_ID":
						$arSqlSearch[] = GetFilterQuery("ENTITY_ID",$val,"N");
					break;
					case "APPLIED":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "APPLIED = '".$val."'";
					break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$sOrder = "";
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($key))
			{
				case "ID": $sOrder .= ", ID ".$ord; break;
				case "RULE_ID":	$sOrder .= ", RULE_ID ".$ord; break;
				case "ENTITY_TYPE_ID": $sOrder .= ", ENTITY_TYPE_ID ".$ord; break;
				case "ENTITY_ID": $sOrder .= ", ENTITY_ID ".$ord; break;
				case "APPLIED":	$sOrder .= ", APPLIED ".$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = "ID ASC";
		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSql = "SELECT * FROM b_rating_rule_vetting WHERE $strSqlSearch $strSqlOrder";
		return  $DB->Query($strSql, false, $err_mess.__LINE__);
	}

	// apply vetting list
	public static function ApplyVetting($arConfigs)
	{
		global $DB;

		$err_mess = (CRatingRule::err_mess())."<br>Function: ApplyVetting<br>Line: ";

		$ruleId = IntVal($arConfigs['ID']);

		// put a check that they are counted
		$DB->Query("UPDATE b_rating_rule_vetting SET APPLIED = 'Y' WHERE RULE_ID = $ruleId", false, $err_mess.__LINE__);

		return true;
	}

	// queries modules and get all the available objects
	public static function GetRatingRuleObjects()
	{
		$arObjects = array();

		$db_events = GetModuleEvents("main", "OnGetRatingRuleObjects");
		while($arEvent = $db_events->Fetch())
		{
			$arConfig = ExecuteModuleEventEx($arEvent);
			foreach ($arConfig as $OBJ_TYPE)
				if (!in_array($OBJ_TYPE, $arObjects))
					$arObjects[] = $OBJ_TYPE;
		}
		return $arObjects;
	}

	// queries modules and assemble an array of settings
	public static function GetRatingRuleConfigs($objectType = null, $withRuleType = true)
	{
		$arConfigs = array();

		$db_events = GetModuleEvents("main", "OnGetRatingRuleConfigs");
		while($arEvent = $db_events->Fetch())
		{
			$arConfig = ExecuteModuleEventEx($arEvent);
			if (is_null($objectType))
			{
				foreach ($arConfig as $OBJ_TYPE => $TYPE_VALUE)
					foreach ($TYPE_VALUE as $RULE_TYPE => $RULE_VALUE)
					   foreach ($RULE_VALUE as $VALUE)
					   		if ($withRuleType)
					   			$arConfigs[$OBJ_TYPE][$RULE_TYPE][$VALUE['ID']] = $VALUE;
					   		else
					   			$arConfigs[$OBJ_TYPE][$VALUE['ID']] = $VALUE;
			}
			else
			{
				foreach ($arConfig[$objectType] as $RULE_TYPE => $RULE_VALUE)
				{
					foreach ($RULE_VALUE as $VALUE)
						if ($withRuleType)
							$arConfigs[$RULE_TYPE][$VALUE['ID']] = $VALUE;
						else
							$arConfigs[$VALUE['ID']] = $VALUE;
				}
			}
		}
		return $arConfigs;
	}

	// check only general field
	public static function __CheckFields($arFields)
	{
		$aMsg = array();

		if(is_set($arFields, "NAME") && trim($arFields["NAME"])=="")
			$aMsg[] = array("id"=>"NAME", "text"=>GetMessage("RR_GENERAL_ERR_NAME"));
		if(is_set($arFields, "ACTIVE") && !($arFields["ACTIVE"] == 'Y' || $arFields["ACTIVE"] == 'N'))
			$aMsg[] = array("id"=>"ACTIVE", "text"=>GetMessage("RR_GENERAL_ERR_ACTIVE"));
		if(is_set($arFields, "ENTITY_TYPE_ID"))
		{
			$arObjects = CRatingRule::GetRatingRuleObjects();
			if(!in_array($arFields['ENTITY_TYPE_ID'], $arObjects))
				$aMsg[] = array("id"=>"ENTITY_TYPE_ID", "text"=>GetMessage("RR_GENERAL_ERR_ENTITY_TYPE_ID"));
		}
		if(is_set($arFields, "CONDITION_NAME") && trim($arFields["CONDITION_NAME"])=="")
			$aMsg[] = array("id"=>"CONDITION_NAME", "text"=>GetMessage("RR_GENERAL_ERR_CONDITION_NAME"));
		if(is_set($arFields, "ACTION_NAME") && trim($arFields["ACTION_NAME"])=="")
			$aMsg[] = array("id"=>"ACTION_NAME", "text"=>GetMessage("RR_GENERAL_ERR_ACTION_NAME"));

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function err_mess()
	{
		return "<br>Class: CRatingRule<br>File: ".__FILE__;
	}
}
