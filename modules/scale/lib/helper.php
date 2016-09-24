<?
namespace Bitrix\Scale;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
* Class Helper
* @package Bitrix\Scale
*/
class Helper
{
	const BX_ENV_MIN_VERSION = "5.0-44";

	public static function checkBxEnvVersion($version = false)
	{
		if(!$version)
			$version = getenv('BITRIX_VA_VER');

		return version_compare($version, self::BX_ENV_MIN_VERSION , '>=');
	}

	public static function nbsp($str)
	{
		return str_replace(" ", "&nbsp;",$str);
	}

	public static function getAvailabilityPage($minutes)
	{
		if(intval($minutes) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("minutes");

		$now = time();

		$contents = file_get_contents(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/scale/server_off.html');

		$contents = str_replace(
			"##SITE_NAME##",
			\CUtil::JSEscape(\COption::GetOptionString("main","site_name", $_SERVER["SERVER_NAME"])),
			$contents
		);

		$contents = str_replace(
			"##CHARSET##",
			LANG_CHARSET,
			$contents
		);

		$contents = str_replace(
			"##AVAILABLE_MESSAGE##",
			Loc::getMessage("SCALE_HLP_AV_MESSAGE"),
			$contents
		);

		$contents = str_replace(
			"##AVAILABLE_DATETIME##",
			($now+60*$minutes)*1000,
			$contents
		);

		$contents = str_replace(
			"##SERVER_NOW##",
			$now*1000,
			$contents
		);

		$contents = str_replace(
			"##HOURS##",
			Loc::getMessage("SCALE_HLP_AV_HOURS")." ",
			$contents
		);

		$contents = str_replace(
			"##MINS##",
			Loc::getMessage("SCALE_HLP_AV_MINS")." ",
			$contents
		);

		$contents = str_replace(
			"##SECS##",
			Loc::getMessage("SCALE_HLP_AV_SECS")." ",
			$contents
		);

		return $contents;
	}

	public static function modifyDbconn($DBHost, $DBName, $DBLogin, $DBPassword)
	{
		if(strlen($DBHost) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("DBHost");
		if(strlen($DBName) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("DBName");
		if(strlen($DBLogin) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("DBLogin");

		$filename = \Bitrix\Main\Application::getDocumentRoot()."/bitrix/php_interface/dbconn.php";
		$file = new \Bitrix\Main\IO\File($filename);

		if(!$file->isExists())
			return false;

		$content = file_get_contents($filename);

		if(strlen($content) <=0)
			return false;

		file_put_contents(\Bitrix\Main\Application::getDocumentRoot()."/bitrix/php_interface/dbconn.php.bak", $content);

		$content = preg_replace('/(\$DBHost\s*=\s*(\"|\')+)(.*)((\"|\')+;)/','${1}'.$DBHost.'${4}',$content);
		$content = preg_replace('/(\$DBName\s*=\s*(\"|\')+)(.*)((\"|\')+;)/','${1}'.$DBName.'${4}',$content);
		$content = preg_replace('/(\$DBLogin\s*=\s*(\"|\')+)(.*)((\"|\')+;)/','${1}'.$DBLogin.'${4}',$content);
		$content = preg_replace('/(\$DBPassword\s*=\s*(\"|\')+)(.*)((\"|\')+;)/','${1}'.$DBPassword.'${4}',$content);

		return file_put_contents($filename, $content);
	}

	public static function modifySettings($DBHost, $DBName, $DBLogin, $DBPassword)
	{
		$filename = $_SERVER['DOCUMENT_ROOT']."/bitrix/.settings-test.php";

		if (!file_exists($filename))
			return true;

		ob_start();
		$settings = include($filename);
		ob_end_clean();

		if (!is_array($settings))
			return false;

		if(!isset($settings['connections']['value']['default']) || !is_array($settings['connections']['value']['default']))
			return true;

		$settings['connections']['value']['default']['host'] = $DBHost;
		$settings['connections']['value']['default']['database'] = $DBName;
		$settings['connections']['value']['default']['login'] = $DBLogin;
		$settings['connections']['value']['default']['password'] = $DBPassword;

		$data = var_export($settings, true);

		rename($filename, $_SERVER['DOCUMENT_ROOT']."/bitrix/.settings-test.php.bak");
		file_put_contents($filename, "<"."?php\nreturn ".$data.";\n");

		return true;
	}

	public static function generatePass($length = 20)
	{
		$chars="abcdefghiknrstyzABCDEFGHKNQRSTYZ1234567890";
		$charsCount=strlen($chars);
		$result="";

		for($i=0; $i<$length; $i++)
			$result .= substr($chars, rand(1, $charsCount) - 1, 1);

		return $result;
	}

	public static function isExtraDbExist($hostname)
	{
		$dbList = ServersData::getDbList($hostname);
		$connection = \Bitrix\Main\Application::getConnection();
		$currentDb = $connection->getDbName();
		$dbCount = count($dbList);
		if($dbCount > 1
			||($dbCount == 1
				&& !in_array($currentDb, $dbList)
			)
		)
		{
			$result = true;
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	public static function getNetworkInterfaces()
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-node -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["pool_interfaces"]))
				$result = $arData["params"]["pool_interfaces"];

			if(is_array($result))
			{
				foreach($result as $iface => $ip)
					$result[$iface] = $iface." (".$ip.")";
			}
		}

		return $result;
	}
}
