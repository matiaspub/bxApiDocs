<?
IncludeModuleLangFile(__FILE__);

class CCityLookup_stat_table extends CCityLookup
{
	var $country_avail = false;
	var $city_avail = false;

	public static function OnCityLookup($arDBRecord = false)
	{
		return new CCityLookup_stat_table($arDBRecord);
	}

	public function __construct($arDBRecord = false)
	{
		parent::__construct($arDBRecord);
		$DB = CDatabase::GetModuleConnection('statistic');

		if(!$arDBRecord)
		{
			$country_recs = COption::GetOptionString("statistic", "COUNTRY_INDEX_LOADED", "N");
			if($country_recs !== "Y")
			{
				$rs = $DB->Query(CStatistics::DBTopSql("SELECT /*TOP*/ * FROM b_stat_country", 1));
				if($rs->Fetch())
				{
					$country_recs = "Y";
					COption::SetOptionString("statistic", "COUNTRY_INDEX_LOADED", "Y");
				}
			}

			$this->country_avail = $country_recs === "Y";


			if($this->country_avail)
			{
				$city_recs = COption::GetOptionString("statistic", "CITY_INDEX_LOADED", "N");
				if($city_recs !== "Y")
				{
					$rs = $DB->Query(CStatistics::DBTopSql("SELECT /*TOP*/ * FROM b_stat_city_ip", 1));
					if($rs->Fetch())
						COption::SetOptionString("statistic", "CITY_INDEX_LOADED", "Y");
				}
				$this->city_avail = COption::GetOptionString("statistic", "CITY_INDEX_LOADED", "N") === "Y";
			}

			$this->is_installed = $this->country_avail;
		}
	}

	public function GetFullInfo()
	{
		if(!$this->country_full_name && !$this->region_name && !$this->city_name)
		{
			if($this->city_id > 0)
			{
				$DB = CDatabase::GetModuleConnection('statistic');
				$rs = $DB->Query("
					SELECT
						C.NAME COUNTRY_NAME,
						CITY.REGION REGION_NAME,
						CITY.NAME CITY_NAME
					from
						b_stat_city CITY
						INNER JOIN b_stat_country C on C.ID = CITY.COUNTRY_ID
					WHERE
						CITY.ID = ".intval($this->city_id));
				$ar = $rs->Fetch();
				if($ar)
				{
					$this->country_full_name = $ar["COUNTRY_NAME"];
					$this->region_name = $ar["REGION_NAME"];
					$this->city_name = $ar["CITY_NAME"];
				}
			}
		}
		return parent::GetFullInfo();
	}

	public static function GetDescription()
	{
		return array(
			"CLASS" => "CCityLookup_stat_table",
			"DESCRIPTION" => GetMessage("STAT_CITY_TABLE_DESCR", array("#WIZARD_HREF#"=>"javascript:WizardWindow.Open('bitrix:statistic.locations','".bitrix_sessid()."')")),
			"IS_INSTALLED" => true,
			"CAN_LOOKUP_COUNTRY" => $this->country_avail,
			"CAN_LOOKUP_CITY" => $this->city_avail,
		);
	}

	public static function IsInstalled()
	{
		return true;
	}

	public function Lookup()
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		if($this->city_avail && $this->ip_number)
		{
			$rs = $DB->Query("
				SELECT *
				FROM b_stat_city_ip
				WHERE START_IP = (
					SELECT MAX(START_IP)
					FROM b_stat_city_ip
					WHERE START_IP <= ".$this->ip_number."
				)
				AND END_IP >= ".$this->ip_number."
			", true);

			if($rs)
			{
				$ar = $rs->Fetch();
				if($ar)
				{
					$this->country_code = $ar["COUNTRY_ID"];
					$this->city_id = $ar["CITY_ID"];
				}
			}
			else
			{
				//Here is mysql 4.0 version which does not supports subqueries
				//and not smart to optimeze query
				$rs = $DB->Query("
					SELECT START_IP
					FROM b_stat_city_ip
					WHERE START_IP <= ".$this->ip_number."
					ORDER BY START_IP DESC
					LIMIT 1
				");
				$ar = $rs->Fetch();
				if($ar && strlen($ar["START_IP"]) > 0)
				{
					$rs = $DB->Query("
						SELECT *
						FROM b_stat_city_ip
						WHERE START_IP = ".$ar["START_IP"]."
						AND END_IP >= ".$this->ip_number."
					");
					$ar = $rs->Fetch();
					if($ar)
					{
						$this->country_code = $ar["COUNTRY_ID"];
						$this->city_id = $ar["CITY_ID"];
					}
				}
			}
		}
		if(!$this->country_code && $this->country_avail)
		{
			$this->country_code = i2c_get_country();
		}
	}
}
?>
