<?
IncludeModuleLangFile(__FILE__);
/*
geoiprecord Object
(
	[country_code] => RU
	[country_code3] => RUS
	[country_name] => Russian Federation
	[region] => 23
	[city] => Kaliningrad
	[postal_code] =>
	[latitude] => -122.2372
	[longitude] => 69.82
	[area_code] =>
	[dma_code] =>
)
*/
class CCityLookup_geoip_pure extends CCityLookup
{
	var $country_avail = false;
	var $city_avail = false;

	var $postal_code = false;
	var $latitude = false;
	var $longitude = false;

	public static function OnCityLookup($arDBRecord = false)
	{
		return new CCityLookup_geoip_pure($arDBRecord);
	}

	public function __construct($arDBRecord = false)
	{
		parent::__construct($arDBRecord);
		if(!$arDBRecord)
		{
			if(function_exists("geoip_open") && defined("GEOIP_DATABASE_FILE"))
			{
				$gi = geoip_open(GEOIP_DATABASE_FILE, defined("GEOIP_MODE")? GEOIP_MODE: GEOIP_STANDARD);
				if($gi)
				{
					$this->country_avail = function_exists("geoip_country_code_by_addr");
					$this->city_avail = function_exists("geoip_record_by_addr");
					geoip_close($gi);
				}
				$this->is_installed = $this->country_avail || $this->city_avail;
			}
			$this->charset = "iso-8859-1";
		}
		else
		{
			if(array_key_exists("XPOST", $arDBRecord)) $this->postal_code = $arDBRecord["XPOST"];
			if(array_key_exists("XLAT", $arDBRecord)) $this->latitude = $arDBRecord["XLAT"];
			if(array_key_exists("XLON", $arDBRecord)) $this->longitude = $arDBRecord["XLON"];
		}
	}

	public function ArrayForDB()
	{
		$ar = parent::ArrayForDB();
		if($this->postal_code) $ar["XPOST"] = $this->postal_code;
		if($this->latitude) $ar["XLAT"] = $this->latitude;
		if($this->longitude) $ar["XLON"] = $this->longitude;
		return $ar;
	}

	public function GetFullInfo()
	{
		$ar = parent::GetFullInfo();
		$ar["POSTAL_CODE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_PHP_POSTAL_CODE"),
			"VALUE~" => $this->postal_code,
			"VALUE" => htmlspecialcharsbx($this->postal_code),
		);
		$ar["LONGITUDE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_PHP_LONGITUDE"),
			"VALUE~" => $this->longitude,
			"VALUE" => htmlspecialcharsbx($this->longitude),
		);
		$ar["LATITUDE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_PHP_LATITUDE"),
			"VALUE~" => $this->latitude,
			"VALUE" => htmlspecialcharsbx($this->latitude),
		);
		return $ar;
	}


	public function GetDescription()
	{
		return array(
			"CLASS" => "CCityLookup_geoip_pure",
			"DESCRIPTION" => GetMessage("STAT_CITY_GEOIP_PHP_DESCR"),
			"IS_INSTALLED" => $this->is_installed,
			"CAN_LOOKUP_COUNTRY" => $this->country_avail || $this->city_avail,
			"CAN_LOOKUP_CITY" => $this->city_avail,
		);
	}

	public function IsInstalled()
	{
		return $this->is_installed;
	}

	public function Lookup()
	{
		$gi = geoip_open(GEOIP_DATABASE_FILE, defined("GEOIP_MODE")? GEOIP_MODE: GEOIP_STANDARD);
		if($gi)
		{
			if($this->city_avail)
			{
				$record = geoip_record_by_addr($gi, $_SERVER['REMOTE_ADDR']);
				$this->country_code = $record->country_code;
				$this->country_short_name = $record->country_code3;
				$this->country_full_name = $record->country_name;
				$this->region_name = $record->region;
				$this->city_name = $record->city;
				//Extended info
				$this->postal_code = $record->postal_code;
				$this->latitude = $record->latitude;
				$this->longitude = $record->longitude;
			}
			elseif($this->country_avail)
			{
				$this->country_code = geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']);
			}
			geoip_close($gi);
		}
	}
}
?>
