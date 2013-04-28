<?
if (!defined("CACHED_b_bitrixcloud_option"))
	// define("CACHED_b_bitrixcloud_option", 36000);

global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses("bitrixcloud", array(
	"CBitrixCloudOption" => "classes/".$db_type."/option.php",
	"CBitrixCloudWebService" => "classes/general/webservice.php",
	"CBitrixCloudCDNWebService" => "classes/general/cdn_webservice.php",
	"CBitrixCloudCDNConfig" => "classes/general/cdn_config.php",
	"CBitrixCloudCDN" => "classes/general/cdn.php",
	"CBitrixCloudCDNQuota" => "classes/general/cdn_quota.php",
	"CBitrixCloudCDNClasses" => "classes/general/cdn_class.php",
	"CBitrixCloudCDNClass" => "classes/general/cdn_class.php",
	"CBitrixCloudCDNServerGroups" => "classes/general/cdn_server.php",
	"CBitrixCloudCDNServerGroup" => "classes/general/cdn_server.php",
	"CBitrixCloudCDNLocations" => "classes/general/cdn_location.php",
	"CBitrixCloudCDNLocation" => "classes/general/cdn_location.php",
	"CBitrixCloudBackupWebService" => "classes/general/backup_webservice.php",
	"CBitrixCloudBackup" => "classes/general/backup.php",
));

if(CModule::IncludeModule('clouds'))
{
	CModule::AddAutoloadClasses("bitrixcloud", array(
		"CBitrixCloudBackupBucket" => "classes/general/backup_bucket.php",
	));
}

class CBitrixCloudException extends Exception
{
	protected $error_code = "";
	protected $debug_info = "";
	static public function __construct($message = "", $error_code = "", $debug_info = "")
	{
		parent::__construct($message);
		$this->error_code = $error_code;
		$this->debug_info = $debug_info;
	}
	static final public function getErrorCode()
	{
		return $this->error_code;
	}
	static final public function getDebugInfo()
	{
		return $this->debug_info;
	}
}
?>