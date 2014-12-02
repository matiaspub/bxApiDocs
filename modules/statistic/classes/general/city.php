<?
/*.
	require_module 'standard';
	require_module 'bitrix_main';
	require_module 'bitrix_statistic_include';
.*/
IncludeModuleLangFile(__FILE__);

class CCityLookup
{

	public $ip_addr = "";
	public $ip_number = "";
	public $country_code = "";
	public $country_short_name = "";
	public $country_full_name = "";
	public $region_name = "";
	public $city_name = "";
	public $city_id = "";
	public $charset = "";

	/**
	 * @param array[string]string $arDBRecord
	 * @return void
	*/
	public function __construct($arDBRecord = /*.(array[string]string).*/array())
	{
		if(is_array($arDBRecord))
		{
			if(array_key_exists("IPA", $arDBRecord)) $this->ip_addr = $arDBRecord["IPA"];
			if(array_key_exists("IPN", $arDBRecord)) $this->ip_number = $arDBRecord["IPN"];
			if(array_key_exists("COC", $arDBRecord)) $this->country_code = $arDBRecord["COC"];
			if(array_key_exists("COS", $arDBRecord)) $this->country_short_name = $arDBRecord["COS"];
			if(array_key_exists("COF", $arDBRecord)) $this->country_full_name = $arDBRecord["COF"];
			if(array_key_exists("REN", $arDBRecord)) $this->region_name = $arDBRecord["REN"];
			if(array_key_exists("CIN", $arDBRecord)) $this->city_name = $arDBRecord["CIN"];
			if(array_key_exists("CID", $arDBRecord)) $this->city_id = $arDBRecord["CID"];
		}
		else
		{
			$ip = $this->ip_addr = $_SERVER["REMOTE_ADDR"];
			$this->ip_number = ip2number($ip);
		}
	}

	/**
	 * @param array[string]string $arDBRecord
	 * @return CCityLookup
	*/
	public static function OnCityLookup($arDBRecord = /*.(array[string]string).*/array())
	{
		return new CCityLookup($arDBRecord);
	}

	/**
	 * @return array[string]string
	*/
	public function ArrayForDB()
	{
		$ar = /*.(array[string]string).*/array();
		if(strlen($this->ip_addr) > 0) $ar["IPA"] = $this->ip_addr;
		if(strlen($this->ip_number) > 0) $ar["IPN"] = $this->ip_number;
		if(strlen($this->country_code) > 0) $ar["COC"] = $this->country_code;
		if(strlen($this->country_short_name) > 0) $ar["COS"] = $this->country_short_name;
		if(strlen($this->country_full_name) > 0) $ar["COF"] = $this->country_full_name;
		if(strlen($this->region_name) > 0) $ar["REN"] = $this->region_name;
		if(strlen($this->city_name) > 0) $ar["CIN"] = $this->city_name;
		if(strlen($this->city_id) > 0) $ar["CID"] = $this->city_id;
		return $ar;
	}

	/**
	 * @return array[string][string]string
	*/
	public function GetFullInfo()
	{
		return array(
			"IP_ADDR" => array(
				"TITLE" => GetMessage("STAT_CITY_IP_ADDR"),
				"VALUE~" => $this->ip_addr,
				"VALUE" => htmlspecialcharsbx($this->ip_addr),
			),
			"COUNTRY_CODE" => array(
				"TITLE" => GetMessage("STAT_CITY_COUNTRY_CODE"),
				"VALUE~" => $this->country_code,
				"VALUE" => htmlspecialcharsbx($this->country_code),
			),
			"COUNTRY_NAME" => array(
				"TITLE" => GetMessage("STAT_CITY_COUNTRY_NAME"),
				"VALUE~" => $this->country_full_name,
				"VALUE" => htmlspecialcharsbx($this->country_full_name),
			),
			"REGION_NAME" => array(
				"TITLE" => GetMessage("STAT_CITY_REGION_NAME"),
				"VALUE~" => $this->region_name,
				"VALUE" => htmlspecialcharsbx($this->region_name),
			),
			"CITY_NAME" => array(
				"TITLE" => GetMessage("STAT_CITY_CITY_NAME"),
				"VALUE~" => $this->city_name,
				"VALUE" => htmlspecialcharsbx($this->city_name),
			),
		);
	}

	/**
	 * @return array[string]mixed
	*/
	public static function GetDescription()
	{
		$res = /*.(array[string]mixed).*/array();
		$res["ID"] = "CCityLookup";
		$res["DESCRIPTION"] = "";
		$res["IS_INSTALLED"] = false;
		$res["CAN_LOOKUP_COUNTRY"] = true;
		$res["CAN_LOOKUP_CITY"] = false;
		return $res;
	}

	/**
	 * @return bool
	*/
	public static function IsInstalled()
	{
		return false;
	}

	/**
	 * @return void
	*/
	public function Lookup()
	{
		$this->country_code = "N0";
		$this->country_short_name = "N00";
		$this->country_full_name = "NA";
	}
}

class CStatRegion
{
	/**
	 * @param array[string]string $arOrder
	 * @param array[string]string $arFilter
	 * @return CDBResult
	*/
	public static function GetList($arOrder = /*.(array[string]string).*/array(), $arFilter = /*.(array[string]string).*/array())
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		$arQueryOrder = array();
		if(is_array($arOrder))
		{
			foreach($arOrder as $strColumn => $strDirection)
			{
				$strColumn = strtoupper($strColumn);
				$strDirection = strtoupper($strDirection)==="ASC"? "ASC": "DESC";
				switch($strColumn)
				{
					case "COUNTRY_ID":
					case "COUNTRY_SHORT_NAME":
					case "COUNTRY_NAME":
					case "REGION_NAME":
						$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
						break;
					case "REGION":
						$arQueryOrder["COUNTRY_ID"] = "COUNTRY_ID ".$strDirection;
						$arQueryOrder["REGION_NAME"] = "REGION_NAME ".$strDirection;
						break;
					default:
						break;
				}
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"COUNTRY_ID" => array(
				"TABLE_ALIAS" => "R",
				"FIELD_NAME" => "R.COUNTRY_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"COUNTRY_SHORT_NAME" => array(
				"TABLE_ALIAS" => "C",
				"FIELD_NAME" => "C.SHORT_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"COUNTRY_NAME" => array(
				"TABLE_ALIAS" => "C",
				"FIELD_NAME" => "C.NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"REGION_NAME" => array(
				"TABLE_ALIAS" => "R",
				"FIELD_NAME" => "R.REGION",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
		));

		$strSql = "
			SELECT
				R.COUNTRY_ID
				,C.SHORT_NAME COUNTRY_SHORT_NAME
				,C.NAME COUNTRY_NAME
				,R.REGION REGION_NAME
			FROM
				b_stat_city R
				INNER JOIN b_stat_country C on C.ID = R.COUNTRY_ID
		";

		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		if(strlen($strQueryWhere) > 0)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}
		$strSql .= "
			GROUP BY
				R.COUNTRY_ID, R.REGION, C.SHORT_NAME, C.NAME
			";

		if(count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}

class CCity
{
	private $lookup_class = "";
	private $lookup = /*.(CCityLookup).*/null;
	private $country_code = "";
	private $city_id = "";

	/**
	 * @param string $dbRecord
	 * @return void
	*/
	public function __construct($dbRecord = "")
	{
		if(strlen($dbRecord) > 0)
			$arDBRecord = unserialize($dbRecord);
		else
			$arDBRecord = false;

		if(is_array($arDBRecord))
		{
			$this->lookup_class = $arDBRecord["LC"];
			if(!$this->lookup_class || !class_exists(strtolower($this->lookup_class)))
				$this->lookup_class = "CCityLookup";

			$this->lookup = call_user_func_array(array($this->lookup_class, "OnCityLookup"), array($arDBRecord["LD"]));

			$this->country_code = $arDBRecord["CC"];
			$this->city_id = $arDBRecord["CI"];
		}
		else
		{
			$this->lookup_class = $this->GetHandler();
			if(!$this->lookup_class || !class_exists(strtolower($this->lookup_class)))
				$this->lookup_class = "CCityLookup";

			$ob = call_user_func_array(array($this->lookup_class, "OnCityLookup"), array());

			if(!$ob || !$ob->IsInstalled())
			{
				$this->lookup_class = "CCityLookup";
				$this->lookup = new CCityLookup;
			}
			else
			{
				$this->lookup = $ob;
			}
		}
	}

	/**
	 * @param array[string]string $arOrder
	 * @param array[string]string $arFilter
	 * @return CDBResult
	*/
	public static function GetList($arOrder = /*.(array[string]string).*/array(), $arFilter = /*.(array[string]string).*/array())
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		if(!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection)==="ASC"? "ASC": "DESC";
			switch($strColumn)
			{
				case "COUNTRY_ID":
				case "COUNTRY_SHORT_NAME":
				case "COUNTRY_NAME":
				case "REGION_NAME":
				case "CITY_NAME":
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
					break;
				case "CITY":
					$arQueryOrder["COUNTRY_ID"] = "COUNTRY_ID ".$strDirection;
					$arQueryOrder["REGION_NAME"] = "REGION_NAME ".$strDirection;
					$arQueryOrder["CITY_NAME"] = "CITY_NAME ".$strDirection;
					break;
				default:
					break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"CITY_ID" => array(
				"TABLE_ALIAS" => "CITY",
				"FIELD_NAME" => "CITY.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => "",
			),
			"COUNTRY_ID" => array(
				"TABLE_ALIAS" => "CITY",
				"FIELD_NAME" => "CITY.COUNTRY_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"COUNTRY_SHORT_NAME" => array(
				"TABLE_ALIAS" => "C",
				"FIELD_NAME" => "C.SHORT_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"COUNTRY_NAME" => array(
				"TABLE_ALIAS" => "C",
				"FIELD_NAME" => "C.NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"REGION_NAME" => array(
				"TABLE_ALIAS" => "CITY",
				"FIELD_NAME" => "CITY.REGION",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
			"CITY_NAME" => array(
				"TABLE_ALIAS" => "CITY",
				"FIELD_NAME" => "CITY.NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "",
			),
		));

		$strSql = "
			SELECT
				CITY.ID CITY_ID
				,CITY.COUNTRY_ID
				,C.SHORT_NAME COUNTRY_SHORT_NAME
				,C.NAME COUNTRY_NAME
				,CITY.REGION REGION_NAME
				,CITY.NAME CITY_NAME
			FROM
				b_stat_city CITY
				INNER JOIN b_stat_country C on C.ID = CITY.COUNTRY_ID
		";
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		if(strlen($strQueryWhere) > 0)
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

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	/**
	 * @return string
	*/
	public function ForSQL()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		return $DB->ForSQL(serialize(array(
			"LC" => $this->lookup_class,
			"LD" => $this->lookup->ArrayForDB(),
			"CC" => $this->country_code,
			"CI" => $this->city_id,
		)));
	}

	public function GetFullInfo()
	{
		$this->GetCityID();
		return $this->lookup->GetFullInfo();
	}

	public static function GetHandler()
	{
		$selected = COption::GetOptionString("statistic", "IP_LOOKUP_CLASS", "");
		if(!$selected)
		{
			$arResolvers = array();
			foreach(GetModuleEvents("statistic", "OnCityLookup", true) as $arEvent)
			{
				$ob = ExecuteModuleEventEx($arEvent);
				$ar = $ob->GetDescription();
				$arResolvers[$ar["CLASS"]] = $ob;
			}
			if(!array_key_exists($selected, $arResolvers))
			{
				foreach($arResolvers as $ID => $ob)
				{
					if($ob->IsInstalled())
					{
						$selected = $ID;
						break;
					}
				}
			}
			COption::SetOptionString("statistic", "IP_LOOKUP_CLASS", $selected);
		}
		return $selected;
	}

	public function GetCountryCode()
	{
		if(!$this->country_code)
		{
			$this->lookup->Lookup();
			$this->country_code = $this->lookup->country_code;
		}
		return $this->country_code? $this->country_code: "N0";
	}

	public function Recode($str)
	{
		if($str && $this->lookup->charset)
		{
			global $APPLICATION;
			$str = $APPLICATION->ConvertCharset($str, $this->lookup->charset, LANG_CHARSET);
		}
		return $str;
	}

	public function GetCityID()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$country_code = $this->GetCountryCode();

		if(!$this->city_id)
			$this->city_id = $this->lookup->city_id;

		if(!$this->city_id)
		{
			$city_name = $this->Recode($this->lookup->city_name);
			$region_name = $this->Recode($this->lookup->region_name);

			$rs = $DB->Query("
				SELECT ID
				FROM b_stat_city
				WHERE COUNTRY_ID = '".$DB->ForSQL($country_code, 2)."'
				AND ".($region_name? "REGION = '".$DB->ForSQL($region_name, 255)."'": "REGION IS NULL")."
				AND ".($city_name? "NAME = '".$DB->ForSQL($city_name, 255)."'": "NAME IS NULL")."
			");
			$ar = $rs->Fetch();
			if($ar)
			{
				$this->city_id = $ar["ID"];
			}
			else
			{
				$rs = $DB->Query("
					SELECT ID
					FROM b_stat_country
					WHERE ID = '".$DB->ForSQL($country_code, 2)."'
				");
				$ar = $rs->Fetch();
				if(!$ar)
				{
					$country_short_name = $this->Recode($this->lookup->country_short_name);
					$country_full_name = $this->Recode($this->lookup->country_full_name);
					$DB->Query("
						INSERT INTO b_stat_country (ID, SHORT_NAME, NAME) VALUES (
							'".$DB->ForSql($country_code, 2)."',
							".($country_short_name? "'".$DB->ForSql($country_short_name, 3)."'": "'N00'").",
							".($country_full_name? "'".$DB->ForSql($country_full_name, 50)."'": "'NA'")."
						)
					");
				}
				$this->city_id = $DB->Add("b_stat_city", array(
					"COUNTRY_ID" => $country_code,
					"REGION" => $region_name ? $region_name: false,
					"NAME" => $city_name ? $city_name: false,
				));
			}
		}
		return $this->city_id > 0? intval($this->city_id): "";
	}

	public static function GetGraphArray($arFilter, &$arLegend, $sort = false, $top = 0)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $arCityColor;
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if(is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "COUNTRY_ID":
						if ($val!="NOT_REF")
							$arSqlSearch[] = GetFilterQuery("C.COUNTRY_ID",$val,"N");
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<=".$DB->CharToDateFunction($val." 23:59:59", "FULL");
						break;
				}
			}
		}
		$arrDays = array();
		$arLegend = array();
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				".$DB->DateFormatToDB("DD", "D.DATE_STAT")." DAY,
				".$DB->DateFormatToDB("MM", "D.DATE_STAT")." MONTH,
				".$DB->DateFormatToDB("YYYY", "D.DATE_STAT")." YEAR,
				D.CITY_ID,
				D.SESSIONS,
				D.NEW_GUESTS,
				D.HITS,
				D.C_EVENTS,
				C.NAME,
				C.SESSIONS TOTAL_SESSIONS,
				C.NEW_GUESTS TOTAL_NEW_GUESTS,
				C.HITS TOTAL_HITS,
				C.C_EVENTS TOTAL_C_EVENTS
			FROM
				b_stat_city_day D
				INNER JOIN b_stat_city C ON (C.ID = D.CITY_ID)
			WHERE
				".$strSqlSearch."
			ORDER BY
				D.DATE_STAT, D.CITY_ID
		";

		$rsD = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($arD = $rsD->Fetch())
		{
			$arrDays[$arD["DATE_STAT"]]["D"] = $arD["DAY"];
			$arrDays[$arD["DATE_STAT"]]["M"] = $arD["MONTH"];
			$arrDays[$arD["DATE_STAT"]]["Y"] = $arD["YEAR"];
			$arrDays[$arD["DATE_STAT"]][$arD["CITY_ID"]]["SESSIONS"] = $arD["SESSIONS"];
			$arrDays[$arD["DATE_STAT"]][$arD["CITY_ID"]]["NEW_GUESTS"] = $arD["NEW_GUESTS"];
			$arrDays[$arD["DATE_STAT"]][$arD["CITY_ID"]]["HITS"] = $arD["HITS"];
			$arrDays[$arD["DATE_STAT"]][$arD["CITY_ID"]]["C_EVENTS"] = $arD["C_EVENTS"];

			$arLegend[$arD["CITY_ID"]]["CITY_ID"] = intval($arD["CITY_ID"]);
			$arLegend[$arD["CITY_ID"]]["NAME"] = $arD["NAME"];
			$arLegend[$arD["CITY_ID"]]["SESSIONS"] += $arD["SESSIONS"];
			$arLegend[$arD["CITY_ID"]]["NEW_GUESTS"] += $arD["NEW_GUESTS"];
			$arLegend[$arD["CITY_ID"]]["HITS"] += $arD["HITS"];
			$arLegend[$arD["CITY_ID"]]["C_EVENTS"] += $arD["C_EVENTS"];

			$arLegend[$arD["CITY_ID"]]["TOTAL_SESSIONS"] = $arD["TOTAL_SESSIONS"];
			$arLegend[$arD["CITY_ID"]]["TOTAL_NEW_GUESTS"] = $arD["TOTAL_NEW_GUESTS"];
			$arLegend[$arD["CITY_ID"]]["TOTAL_HITS"] = $arD["TOTAL_HITS"];
			$arLegend[$arD["CITY_ID"]]["TOTAL_C_EVENTS"] = $arD["TOTAL_C_EVENTS"];
		}

		if($sort)
		{
			CStatisticSort::Sort($arLegend, $sort);
		}

		if($top)
		{
			$totals = array(
				"CITY_ID" => 0,
				"NAME" => GetMessage("STAT_CITY_OTHER"),
				"SESSIONS" => 0,
				"NEW_GUESTS" => 0,
				"HITS" => 0,
				"C_EVENTS" => 0,
				"TOTAL_SESSIONS" => 0,
				"TOTAL_NEW_GUESTS" => 0,
				"TOTAL_HITS" => 0,
				"TOTAL_C_EVENTS" => 0,
			);
			$i = 0;
			while(count($arLegend) > $top)
			{
				$i++;
				$tail = array_pop($arLegend);
				$totals["SESSIONS"] += $tail["SESSIONS"];
				$totals["NEW_GUESTS"] += $tail["NEW_GUESTS"];
				$totals["HITS"] += $tail["HITS"];
				$totals["C_EVENTS"] += $tail["C_EVENTS"];
				$totals["TOTAL_SESSIONS"] += $tail["TOTAL_SESSIONS"];
				$totals["TOTAL_NEW_GUESTS"] += $tail["TOTAL_NEW_GUESTS"];
				$totals["TOTAL_HITS"] += $tail["TOTAL_HITS"];
				$totals["TOTAL_C_EVENTS"] += $tail["TOTAL_C_EVENTS"];
			}
			if($i)
				$arLegend[0] = $totals;

			foreach($arrDays as $DATE_STAT => $arDate)
			{
				foreach($arDate as $CITY_ID => $arCity)
				{
					if(intval($CITY_ID) > 0)
					{
						if(!array_key_exists($CITY_ID, $arLegend))
						{
							$arrDays[$DATE_STAT][0]["CITY_ID"] = 0;
							$arrDays[$DATE_STAT][0]["NAME"] = GetMessage("STAT_CITY_OTHER");
							$arrDays[$DATE_STAT][0]["SESSIONS"] += $arCity["SESSIONS"];
							$arrDays[$DATE_STAT][0]["NEW_GUESTS"] += $arCity["NEW_GUESTS"];
							$arrDays[$DATE_STAT][0]["HITS"] += $arCity["HITS"];
							$arrDays[$DATE_STAT][0]["C_EVENTS"] += $arCity["C_EVENTS"];
							unset($arrDays[$DATE_STAT][$CITY_ID]);
						}
					}
				}
			}
		}

		$total = count($arLegend);
		foreach($arLegend as $key => $arr)
		{
			if (strlen($arCountryColor[$key])>0)
			{
				$color = $arCountryColor[$key];
			}
			else
			{
				$color = GetNextRGB($color_getnext, $total);
				$color_getnext = $color;
			}
			$arr["COLOR"] = $color;
			$arLegend[$key] = $arr;
		}

		return $arrDays;
	}

	public static function FindFiles($type = 'country', $path = '/bitrix/modules/statistic/ip2country')
	{
		$arFiles = array();
		$handle = opendir($_SERVER["DOCUMENT_ROOT"].$path);
		if($handle)
		{
			while(false!==($fname = readdir($handle)))
			{
				if (is_file($_SERVER["DOCUMENT_ROOT"].$path."/".$fname) && $fname!="." && $fname!="..")
				{
					$ext = substr(strtolower($fname), -4);
					if($ext === ".csv" || $ext === ".txt")
					{
						$arFiles[] = $fname;
					}
				}
			}
			closedir($handle);
		}

		$arResult = array();
		foreach($arFiles as $file)
		{
			$fp = fopen($_SERVER["DOCUMENT_ROOT"].$path."/".$file, "r");
			if($fp)
			{
				switch(CCity::GetCSVFormatType($fp))
				{
				case "MAXMIND-IP-COUNTRY":
					if($type == 'country')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "MAXMIND-IP-COUNTRY",
						);
					break;
				case "IP-TO-COUNTRY":
					if($type == 'country')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "IP-TO-COUNTRY",
						);
					break;
				case "MAXMIND-IP-LOCATION":
					if($type == 'city')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "MAXMIND-IP-LOCATION",
						);
					break;
				case "MAXMIND-CITY-LOCATION":
					if($type == 'city')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "MAXMIND-CITY-LOCATION",
						);
					break;
				case "IPGEOBASE":
					if($type == 'city')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "IPGEOBASE",
						);
					break;
				case "IPGEOBASE2":
					if($type == 'city')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "IPGEOBASE2",
						);
					break;
				case "IPGEOBASE2-CITY":
					if($type == 'city')
						$arResult[] = array(
							"FILE" => $file,
							"SIZE" => filesize($_SERVER["DOCUMENT_ROOT"].$path."/".$file),
							"SOURCE" => "IPGEOBASE2-CITY",
						);
					break;
				}
				fclose($fp);
			}
		}
		return $arResult;
	}

	public static function GetCSVFormatType($fp)
	{
		$line = trim(fgets($fp, 1024), " \t\n\r");
		if(preg_match('/maxmind/i', $line))
			$line = trim(fgets($fp, 1024), " \t\n\r");

		if(
			preg_match('/^"(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})","(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})","(\d+)","(\d+)","..",".*?"$/', $line, $match)
			&& (ip2number($match[1]) == $match[3])
			&& (ip2number($match[2]) == $match[4])
		)
		{
			return "MAXMIND-IP-COUNTRY";
		}
		elseif(
			preg_match('/^"\d+","\d+","..","...",".*?"$/', $line)
		)
		{
			return "IP-TO-COUNTRY";
		}
		elseif(
			preg_match('/^startIpNum,endIpNum,locId$/', $line)
		)
		{
			return "MAXMIND-IP-LOCATION";
		}
		elseif(
			preg_match('/^locId,country,region,city,postalCode,latitude,longitude,metroCode,areaCode$/', $line)
		)
		{
			return "MAXMIND-CITY-LOCATION";
		}
		elseif(
			preg_match('/^(\d+)\t(\d+)\t(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) - (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\t(..)\t(.+?)\t(.+?)\t(.+?)\t(.+?)\t(.+)$/', $line, $match)
			&& (ip2number($match[3]) == $match[1])
			&& (ip2number($match[4]) == $match[2])
		)
		{
			return "IPGEOBASE";
		}
		elseif(
			preg_match('/^(\d+)\t(\d+)\t(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) - (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\t(..)/', $line, $match)
			&& (ip2number($match[3]) == $match[1])
			&& (ip2number($match[4]) == $match[2])
		)
		{
			return "IPGEOBASE2";
		}
		elseif(
			preg_match('/^\d+\t(.+?)\t(.+?)\t(.+?)\t([0-9.]+?)\t([0-9.]+?)$/', $line)
		)
		{
			return "IPGEOBASE2-CITY";
		}
		else
		{
			return "UNKNOWN";
		}
	}

	public static function ResolveIPRange($newStartIP, $newEndIP)
	{
		global $DB;

		$rsConflictIP = $DB->Query("
			select * from b_stat_city_ip
			where start_ip between ".$newStartIP." and ".$newEndIP."
			union
			select * from b_stat_city_ip
			where  end_ip between ".$newStartIP." and ".$newEndIP."
			union
			select * from b_stat_city_ip
			where START_IP = (
				SELECT MAX(START_IP)
				FROM b_stat_city_ip
				WHERE START_IP <= ".$newStartIP."
			)
			AND END_IP >= ".$newStartIP."
		");

		$arToUpdate = false;
		while($arConflictIP = $rsConflictIP->Fetch())
		{
			//Exact match
			if(
				$arConflictIP["START_IP"] == $newStartIP
				&& $arConflictIP["END_IP"] == $newEndIP
			)
			{
				$arToUpdate = $arConflictIP;
			}
			//Left overlap
			elseif(
				$newStartIP <= $arConflictIP["START_IP"]
				&& $newEndIP < $arConflictIP["END_IP"]
			)
			{
				//Move conflict to the right
				$rs = $DB->Query("
					UPDATE b_stat_city_ip
					SET START_IP = '".($newEndIP+1)."'
					WHERE START_IP  = '".$arConflictIP["START_IP"]."'
				", true);
				//Delete if there is new conflict raises
				if(!$rs)
					$rs = $DB->Query("
						DELETE FROM b_stat_city_ip
						WHERE START_IP  = '".$arConflictIP["START_IP"]."'
					");
			}
			//Full overlap
			elseif(
				$newStartIP <= $arConflictIP["START_IP"]
				&& $arConflictIP["END_IP"] < $newEndIP
			)
			{
				//Delete
				$rs = $DB->Query("
					DELETE FROM b_stat_city_ip
					WHERE START_IP  = '".$arConflictIP["START_IP"]."'
				");
			}
			//Right overlap
			elseif(
				$arConflictIP["START_IP"] < $newStartIP
				&& $arConflictIP["END_IP"] <= $newEndIP
			)
			{
				//Move conflict to the left
				$rs = $DB->Query("
					UPDATE b_stat_city_ip
					SET END_IP = '".($newStartIP-1)."'
					WHERE START_IP  = '".$arConflictIP["START_IP"]."'
				");
			}
			//Inside
			else/*if(
				$arConflictIP["START_IP"] < $newStartIP
				&& $newEndIP < $arConflictIP["END_IP"]
			)*/
			{
				//Split
				$rs = $DB->Query("
					UPDATE b_stat_city_ip
					SET END_IP = '".($newStartIP-1)."'
					WHERE START_IP  = '".$arConflictIP["START_IP"]."'
				");
				$rs = $DB->Query("
					INSERT INTO b_stat_city_ip
					(START_IP, END_IP, COUNTRY_ID, CITY_ID)
					VALUES
					('".($newEndIP+1)."', '".$arConflictIP["END_IP"]."', '".$arConflictIP["COUNTRY_ID"]."', '".$arConflictIP["CITY_ID"]."')
				", true);
				//Delete if there is new conflict raises
				if(!$rs)
					$rs = $DB->Query("
						DELETE FROM b_stat_city_ip
						WHERE START_IP  = '".$arConflictIP["START_IP"]."'
					");
			}
		}

		return $arToUpdate;
	}


	public static function LoadCSV($file_name, $step, &$file_position)
	{
		global $APPLICATION;
		$DB = CDatabase::GetModuleConnection('statistic');
		$arCache = array();
		$arLookupCache = array();
		$arCountryCache = array();

		$fp = fopen($_SERVER["DOCUMENT_ROOT"].$file_name, "rb");
		if(!$fp)
			return "Y";

		$file_format = CCity::GetCSVFormatType($fp);
		$file_position = intval($file_position);
		fseek($fp, $file_position);

		if($step > 0)
			$end_time = getmicrotime() + $step;
		else
			$end_time = 0;

		switch($file_format)
		{
		case "MAXMIND-IP-COUNTRY":
			$delimiter = ",";
			$char_set = LANG_CHARSET;
			$table_name = "b_stat_country";
			$arFieldsMap = array(
				"ID" => array("key" => true, "index" => 4, "type" => "varchar", "len" => 2, "default" => "NA"),
				"NAME" => array("index" => 5, "type" => "varchar", "len" => 50, "default" => "N00", "enc" => true),
			);
			break;
		case "IP-TO-COUNTRY":
			$delimiter = ",";
			$char_set = LANG_CHARSET;
			$table_name = "b_stat_country";
			$arFieldsMap = array(
				"ID" => array("key" => true, "index" => 2, "type" => "varchar", "len" => 2, "default" => "NA"),
				"SHORT_NAME" => array("index" => 3, "type" => "varchar", "len" => 3, "default" => "N00"),
				"NAME" => array("index" => 4, "type" => "varchar", "len" => 50, "enc" => true),
			);
			break;
		case "MAXMIND-IP-LOCATION":
			$delimiter = ",";
			$char_set = LANG_CHARSET;
			$table_name = "b_stat_city_ip";
			$arFieldsMap = array(
				"START_IP" => array("key" => true,"index" => 0, "type" => "ipnum"),
				"END_IP" => array("index" => 1, "type" => "ipnum"),
				"XML_ID" => array("index" => 2, "type" => "lookup"),
				"COUNTRY_ID" => array("index" => -1, "type" => "varchar"),
				"CITY_ID" => array("index" => -2, "type" => "number"),
			);
			//Some files need to skip first line
			if($file_position <= 0)
			{
				fgets($fp, 4096);
				fgets($fp, 4096);
			}
			break;
		case "MAXMIND-CITY-LOCATION":
			$delimiter = ",";
			$char_set = "iso-8859-1";
			$table_name = "b_stat_city";
			$arFieldsMap = array(
				"COUNTRY_ID" => array("index" => 1, "type" => "varchar", "len" => 2, "default" => "NA"),
				"REGION" => array("index" => 2, "type" => "varchar", "len" => 255, "enc" => true),
				"NAME" => array("index" => 3, "type" => "varchar", "len" => 255, "enc" => true),
				"XML_ID" => array("key" => true, "index" => 0, "type" => "varchar", "len" => 255),
			);
			//Some files need to skip first line
			if($file_position <= 0)
			{
				fgets($fp, 4096);
				fgets($fp, 4096);
			}
			break;
		case "IPGEOBASE":
			$delimiter = "\t";
			$char_set = "Windows-1251";
			$table_name = "b_stat_city_ip";
			$arFieldsMap = array(
				"START_IP" => array("key" => true,"index" => 0, "type" => "ipnum"),
				"END_IP" => array("index" => 1, "type" => "ipnum"),
				"COUNTRY_ID" => array("index" => 3, "type" => "varchar", "len" => 2, "default" => "NA"),
				"XML_ID" => array("index" => array(3, 4, 5), "type" => "upsert", "enc" => true,
					"master" => array(
						"COUNTRY_ID" => array("index" => 3, "type" => "varchar", "len" => 2, "default" => "NA"),
						"REGION" => array("index" => 5, "type" => "varchar", "len" => 255, "enc" => true),
						"NAME" => array("index" => 4, "type" => "varchar", "len" => 255, "enc" => true),
					),
				),
				"CITY_ID" => array("index" => -1, "type" => "number"),
			);
			break;
		case "IPGEOBASE2":
			$delimiter = "\t";
			$char_set = "Windows-1251";
			$table_name = "b_stat_city_ip";
			$arFieldsMap = array(
				"START_IP" => array("key" => true,"index" => 0, "type" => "ipnum"),
				"END_IP" => array("index" => 1, "type" => "ipnum"),
				"COUNTRY_ID" => array("index" => 3, "type" => "varchar", "len" => 2, "default" => "NA", "update_city" => 4),
				"XML_ID" => array("index" => 4, "type" => "lookup"),
				"CITY_ID" => array("index" => -1, "type" => "number"),
			);
			break;
		case "IPGEOBASE2-CITY":
			$delimiter = "\t";
			$char_set = "Windows-1251";
			$table_name = "b_stat_city";
			$arFieldsMap = array(
				"COUNTRY_ID" => array("skip_update" => true, "index" => 100, "type" => "varchar", "len" => 2, "default" => "NA"),
				"REGION" => array("index" => 2, "type" => "varchar", "len" => 255, "enc" => true),
				"NAME" => array("index" => 1, "type" => "varchar", "len" => 255, "enc" => true),
				"XML_ID" => array("key" => true, "index" => 0, "type" => "varchar", "len" => 255),
			);
			break;
		default:
			return "Y";
		}

		$bConv = $char_set != LANG_CHARSET;

		while(!feof($fp))
		{
			//$arr = fgetcsv($fp, 4096, $delimiter);
			$arr = fgets($fp, 4096);
			if($bConv && preg_match("/[^a-zA-Z0-9 \t\n\r]/", $arr))
				$arr = $APPLICATION->ConvertCharset($arr, $char_set, LANG_CHARSET);
			$arr = preg_split("/".$delimiter."/".BX_UTF_PCRE_MODIFIER, $arr);

			$arAllSQLFields = array();
			$arFields = array();
			$strUpdate = "";
			$strWhere = "";
			$strInsert1 = "";
			$strInsert2 = "";
			$bEmptyKey = false;
			foreach($arFieldsMap as $FIELD_ID => $arField)
			{
				if(is_array($arField["index"]))
				{
					$value = "";
					foreach($arField["index"] as $index)
						$value .= trim($arr[$index], "\" \n\r\t");
					$value = md5($value);
				}
				else
				{
					$value = trim($arr[$arField["index"]], "\" \n\r\t");
				}
				//if($bConv && $arField["enc"] && preg_match("/[^a-zA-Z0-9 \t\n\r]/", $value))
				//	$value = $APPLICATION->ConvertCharset($value, $char_set, LANG_CHARSET);
				if(!$value && $arField["default"])
					$value = $arField["default"];

				if($arField["type"] == "upsert")
				{
					if(!array_key_exists($value, $arLookupCache))
					{
						$rs = $DB->Query("SELECT ID as CITY_ID FROM b_stat_city WHERE XML_ID = '".$DB->ForSQL($value)."'");
						$ar = $rs->Fetch();
						if(!$ar)
						{
							$arNewMaster = array(
								"XML_ID" => $value,
							);
							foreach($arField["master"] as $MASTER_FIELD_ID => $arMasterField)
							{
								$m_value = trim($arr[$arMasterField["index"]], "\"");
								//if($bConv && $arMasterField["enc"] && preg_match("/[^a-zA-Z0-9 \t\n\r]/", $m_value))
								//	$m_value = $APPLICATION->ConvertCharset($m_value, $char_set, LANG_CHARSET);
								if(!$m_value && $arMasterField["default"])
									$m_value = $arMasterField["default"];
								$arNewMaster[$MASTER_FIELD_ID] = $m_value;
							}
							$ar = array("CITY_ID" => $DB->Add("b_stat_city", $arNewMaster));
						}
						$arLookupCache[$value] = $ar;
					}
					foreach($arLookupCache[$value] as $key => $val)
						$arr[$arFieldsMap[$key]["index"]] = $val;
					continue;
				}

				if($arField["type"] == "lookup")
				{
					if(!array_key_exists($value, $arLookupCache))
					{
						$rs = $DB->Query("SELECT COUNTRY_ID, ID as CITY_ID FROM b_stat_city WHERE XML_ID = '".$DB->ForSQL($value)."'");
						$ar = $rs->Fetch();
						if(!$ar)
							$ar = array("COUNTRY_ID" => "NA", "CITY_ID" => 0);
						$arLookupCache[$value] = $ar;
					}
					foreach($arLookupCache[$value] as $key => $val)
						$arr[$arFieldsMap[$key]["index"]] = $val;
					continue;
				}

				if(
					$FIELD_ID === "COUNTRY_ID"
					&& !array_key_exists($value, $arCountryCache)
					&& strlen($value) > 0
				)
				{
					$cid = $DB->ForSQL($value, 2);
					$rs = $DB->Query("SELECT ID FROM b_stat_country WHERE ID = '".$cid."'");
					if(!$rs->Fetch())
						$DB->Query("insert into b_stat_country (ID) values ('".$cid."')");
					$arCountryCache[$value] = true;
				}

				if(
					$FIELD_ID === "COUNTRY_ID"
					&& isset($arField["update_city"])
					&& strlen($value) > 0
				)
				{
					$city_id = $DB->ForSQL(trim($arr[$arField["update_city"]], "\" \n\r\t"));
					$cid = $DB->ForSQL($value, 2);
					$rs = $DB->Query("UPDATE b_stat_city SET COUNTRY_ID = '".$cid."' WHERE XML_ID = '".$city_id."'");
				}

				switch($arField["type"])
				{
					case "varchar":
						$sql_value = "'".$DB->ForSQL($value, $arField["len"])."'";
						break;
					case "ipnum":
					case "number":
						$sql_value = preg_replace("/[^0-9]/", "", $value);
						break;
					default:
						$sql_value = "'".$DB->ForSQL($value)."'";
						break;
				}

				$arAllSQLFields[$FIELD_ID] = $sql_value;

				if($arField["key"])
				{
					if($value)
					{
						if($strWhere)
							$strWhere .= " AND ";
						$strWhere .= $FIELD_ID." = ".$sql_value;
					}
					else
					{
						$bEmptyKey = true;
					}
				}
				else
				{
					$arFields[$FIELD_ID] = $value;
					if($strUpdate)
						$strUpdate .= ", ";
					$strUpdate .= $FIELD_ID." = ".$sql_value;
				}

				if($strInsert1)
					$strInsert1 .= ", ";
				$strInsert1 .= $FIELD_ID;

				if($strInsert2)
					$strInsert2 .= ", ";
				$strInsert2 .= $sql_value;
			}

			if(!$bEmptyKey && $strWhere && $strUpdate && !array_key_exists($strWhere, $arCache))
			{
				if($table_name == "b_stat_city_ip" && $arAllSQLFields["START_IP"] && $arAllSQLFields["END_IP"])
				{
					$arToUpdate = CCity::ResolveIPRange($arAllSQLFields["START_IP"], $arAllSQLFields["END_IP"]);
				}
				else
				{
					$rs = $DB->Query("SELECT * FROM $table_name WHERE $strWhere");
					$arToUpdate = $rs->Fetch();
				}
				if($arToUpdate)
				{
					$bNeedUpdate = false;
					foreach($arFields as $UPD_FIELD_ID => $upd_value)
					{
						if(!isset($arFieldsMap[$UPD_FIELD_ID]["skip_update"]))
						{
							if($upd_value != $arToUpdate[$UPD_FIELD_ID])
							{
								$bNeedUpdate = true;
								break;
							}
						}
					}
					if($bNeedUpdate)
						$DB->Query("UPDATE $table_name SET $strUpdate WHERE $strWhere");
				}
				else
				{
					$DB->Query("INSERT INTO $table_name ($strInsert1) VALUES ($strInsert2)");
				}
				$arCache[$strWhere] = true;
			}

			if($end_time && getmicrotime() > $end_time)
			{
				$file_position = ftell($fp);
				return "N";
			}
		}
		$file_position = ftell($fp);
		return "Y";
	}
}
?>
