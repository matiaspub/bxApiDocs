<?
IncludeModuleLangFile(__FILE__);
/*
Array
(
	[country_code] => RU
	[country_code3] => RUS
	[country_name] => Russian Federation
	[region] => 23
	[city] => Kaliningrad
	[postal_code] =>
	[latitude] => 54.7099990845
	[longitude] => 20.5
	[dma_code] => 0
	[area_code] => 0
)

*/
class CCityLookup_geoip_extension extends CCityLookup
{
	var $country_avail = false;
	var $city_avail = false;

	var $postal_code = false;
	var $latitude = false;
	var $longitude = false;

	public static function OnCityLookup($arDBRecord = false)
	{
		return new CCityLookup_geoip_extension($arDBRecord);
	}

	function __construct($arDBRecord = false)
	{
		parent::__construct($arDBRecord);
		if(!$arDBRecord)
		{
			if(function_exists("geoip_db_avail"))
			{
				$this->country_avail = geoip_db_avail(GEOIP_COUNTRY_EDITION);
				$this->city_avail = geoip_db_avail(GEOIP_CITY_EDITION_REV0) || geoip_db_avail(GEOIP_CITY_EDITION_REV1);
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

	public static function ArrayForDB()
	{
		$ar = parent::ArrayForDB();
		if($this->postal_code) $ar["XPOST"] = $this->postal_code;
		if($this->latitude) $ar["XLAT"] = $this->latitude;
		if($this->longitude) $ar["XLON"] = $this->longitude;
		return $ar;
	}

	public static function GetFullInfo()
	{
		$ar = parent::GetFullInfo();
		$ar["POSTAL_CODE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_EXT_POSTAL_CODE"),
			"VALUE~" => $this->postal_code,
			"VALUE" => htmlspecialcharsbx($this->postal_code),
		);
		$ar["LONGITUDE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_EXT_LONGITUDE"),
			"VALUE~" => $this->longitude,
			"VALUE" => htmlspecialcharsbx($this->longitude),
		);
		$ar["LATITUDE"] = array(
			"TITLE" => GetMessage("STAT_CITY_GEOIP_EXT_LATITUDE"),
			"VALUE~" => $this->latitude,
			"VALUE" => htmlspecialcharsbx($this->latitude),
		);
		return $ar;
	}

	public static function GetDescription()
	{
		return array(
			"CLASS" => "CCityLookup_geoip_extension",
			"DESCRIPTION" => GetMessage("STAT_CITY_GEOIP_EXT_DESCR"),
			"IS_INSTALLED" => $this->is_installed,
			"CAN_LOOKUP_COUNTRY" => $this->country_avail || $this->city_avail,
			"CAN_LOOKUP_CITY" => $this->city_avail,
		);
	}

	public static function IsInstalled()
	{
		return $this->is_installed;
	}

	public static function Lookup()
	{
		if($this->city_avail)
		{
			$ar = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
			$this->country_code = $ar["country_code"];
			$this->country_short_name = $ar["country_code3"];
			$this->country_full_name = $ar["country_name"];
			$this->region_name = $ar["region"];
			$this->city_name = $ar["city"];
			//Extended info
			$this->postal_code = $ar["postal_code"];
			$this->latitude = $ar["latitude"];
			$this->longitude = $ar["longitude"];
		}
		elseif($this->country_avail)
		{
			$this->country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
		}
	}
}
?>
