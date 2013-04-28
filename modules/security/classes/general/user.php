<?
IncludeModuleLangFile(__FILE__);
// define("BX_SECURITY_SYNC_WINDOW", 15000);
class CSecurityUser
{
	public static function OnBeforeUserLogin($arParams)
	{
		/**
		* @global CMain $APPLICATION
		* @global CDataBase $DB
		*/
		global $DB, $APPLICATION;

		$rsUser = CUser::GetList($by, $order, array("LOGIN_EQUAL_EXACT" => $arParams["LOGIN"]));
		while($arUser = $rsUser->Fetch())
		{
			if(!$arUser["EXTERNAL_AUTH_ID"])
				break;
		}

		if($arUser)
		{
			$USER_ID = intval($arUser["ID"]);
			$rsKey = $DB->Query("SELECT * from b_sec_user WHERE ACTIVE='Y' AND USER_ID = ".$USER_ID);
			$arKey = $rsKey->Fetch();
			if($arKey)
			{
				$bSuccess = false;
				if(preg_match("/(\\d{6})$/", $arParams["PASSWORD"], $arMatch))
				{
					$bin_secret = pack('H*', $arKey["SECRET"]);
					$sync = $arMatch[1];
					$cnt = intval($arKey["COUNTER"])+1;
					$window = COption::GetOptionInt("security", "hotp_user_window");

					$i = 0;
					while($i < $window)
					{
						if(CSecurityUser::HOTP($bin_secret, $cnt) == $sync)
						{
							$bSuccess = true;
							$arParams["PASSWORD"] = substr($arParams["PASSWORD"], 0, -6);
							$DB->Query("UPDATE b_sec_user SET COUNTER = ".$cnt." WHERE USER_ID = ".$USER_ID);
							break;
						}
						$cnt++;
						$i++;
					}

				}

				if(!$bSuccess)
				{
					$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
					return false;
				}
			}
		}

		return true;
	}

	public static function Deactivate($USER_ID)
	{
		/** @global CDataBase $DB */
		global $DB;
		$res = $DB->Query("
			UPDATE b_sec_user SET ACTIVE = 'N'
			WHERE USER_ID = ".intval($USER_ID)."
		");
		return $res;
	}

	public static function Delete($USER_ID)
	{
		/** @global CDataBase $DB */
		global $DB;
		$res = $DB->Query("
			DELETE FROM b_sec_user
			WHERE USER_ID = ".intval($USER_ID)."
		");
		return $res;
	}

	public static function GetSyncCounter($bin_secret, $sync1, $sync2, &$aMsg)
	{
		if(CSecurityUser::HOTP($bin_secret, 0) === false)
		{
			$aMsg[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_SYNC_ERROR2"));
			return 0;
		}

		if(!$sync1)
			$aMsg[] = array("id"=>"security_SYNC1", "text" => GetMessage("SECURITY_USER_ERROR_PASS1_EMPTY"));
		elseif(!preg_match("/^\d{6}$/", $sync1))
			$aMsg[] = array("id"=>"security_SYNC1", "text" => GetMessage("SECURITY_USER_ERROR_PASS1_INVALID"));

		if(!$sync2)
			$aMsg[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_PASS2_EMPTY"));
		elseif(!preg_match("/^\d{6}$/", $sync2))
			$aMsg[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_PASS2_INVALID"));

		$cnt = 0;
		for($i = 0; $i < BX_SECURITY_SYNC_WINDOW; $i++)
		{
			if(
				CSecurityUser::HOTP($bin_secret, $cnt) == $sync1
				&& CSecurityUser::HOTP($bin_secret, $cnt+1) == $sync2
			)
			{
				$cnt++;
				break;
			}
			$cnt++;
		}

		if($i == BX_SECURITY_SYNC_WINDOW)
		{
			$aMsg[] = array("id"=>"security_SECRET", "text" => GetMessage("SECURITY_USER_ERROR_SYNC_ERROR"));
			$cnt = 0;
		}

		return $cnt;
	}

	public static function Update($arFields)
	{
		/**
		 * @global CMain $APPLICATION
		 * @global CDataBase $DB
		 */
		global $DB, $APPLICATION;
		$aMsg = array();

		$USER_ID = intval($arFields["USER_ID"]);
		if($USER_ID)
		{
			if($arFields["ACTIVE"]!=="Y")
			{
				CSecurityUser::Deactivate($USER_ID);
			}
			else
			{
				$secret = substr(trim($arFields["SECRET"]), 0, 64);
				if(strlen($secret) <= 0)
				{
					CSecurityUser::Delete($USER_ID);
				}
				else
				{
					$rsKey = $DB->Query("SELECT * from b_sec_user WHERE USER_ID = ".$USER_ID);
					$arKey = $rsKey->Fetch();
					if($arKey && ($arKey["SECRET"] == $secret))
						$cnt = intval($arKey["COUNTER"]);
					else
						$cnt = 0;

					$sync1 = trim($arFields["SYNC1"]);
					$sync2 = trim($arFields["SYNC2"]);

					if($sync1 || $sync2)
					{
						$bin_secret = pack('H*', $secret);
						$cnt = CSecurityUser::GetSyncCounter($bin_secret, $sync1, $sync2, $aMsg);
					}

					if($arKey)
					{
						$DB->Query("
							UPDATE b_sec_user SET
								ACTIVE = 'Y',
								SECRET = '".$DB->ForSQL($secret)."',
								COUNTER = ".$cnt."
							WHERE USER_ID = ".$USER_ID."
						");
					}
					else
					{
						$DB->Query("
							INSERT INTO b_sec_user (
								USER_ID, ACTIVE, SECRET, COUNTER
							) VALUES (
								".$USER_ID.", 'Y', '".$DB->ForSQL($secret)."', ".$cnt.")
						");
					}
				}
			}
		}

		if(count($aMsg) > 0)
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;

	}

	public static function OnUserDelete($ID)
	{
		/** @global CDataBase $DB */
		global $DB;
		$DB->Query("DELETE from b_sec_user WHERE USER_ID = ".intval($ID));
		return true;
	}

	public static function hmacsha1($data, $key)
	{
		if(function_exists("hash_hmac"))
			return hash_hmac("sha1", $data, $key);

		if(strlen($key)>64)
			$key=pack('H*', sha1($key));

		$key = str_pad($key, 64, chr(0x00));
		$ipad = str_repeat(chr(0x36), 64);
		$opad = str_repeat(chr(0x5c), 64);
		$hmac = pack('H*', sha1(($key^$opad).pack('H*', sha1(($key^$ipad).$data))));
		return bin2hex($hmac);
	}

	public static function hmacsha256($data, $key)
	{
		if(function_exists("hash_hmac"))
			return hash_hmac("sha256", $data, $key);
		else
			return false;
	}

	public static function HOTP($secret, $cnt, $digits = 6)
	{
		if(CUtil::BinStrlen($secret) <= 25)
			$sha_hash = CSecurityUser::hmacsha1(pack("NN", 0, $cnt), $secret);
		else
			$sha_hash = CSecurityUser::hmacsha256(pack("NN", 0, $cnt), $secret);

		if($sha_hash !== false)
		{
			$dwOffset = hexdec(substr($sha_hash, -1, 1));
			$dbc1 = hexdec(substr($sha_hash, $dwOffset * 2, 8 ));
			$dbc2 = $dbc1 & 0x7fffffff;
			$hotp = $dbc2 % pow(10, $digits);
			return $hotp;
		}
		else
		{
			return false;
		}
	}

	public static function IsActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnBeforeUserLogin", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityUser"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false)
	{
		if($bActive)
		{
			if(!CSecurityUser::IsActive())
			{
				RegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin", "100");
				$f = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php", "w");
				fwrite($f, "<?include(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/security/options_user_settings_1.php\");?>");
				fclose($f);
			}
		}
		else
		{
			if(CSecurityUser::IsActive())
			{
				UnRegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin");
				unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php");
			}
		}
	}

}
?>