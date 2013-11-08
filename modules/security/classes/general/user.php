<?
IncludeModuleLangFile(__FILE__);
class CSecurityUser
{
	const BX_SECURITY_SYNC_WINDOW = 15000;

	/**
	 * @param array $arParams
	 * @return bool
	 */
	public static function onBeforeUserLogin($arParams)
	{
		/**
		* @global CMain $APPLICATION
		* @global CDataBase $DB
		*/
		global $DB, $APPLICATION;
		$userId = self::getUserIdForLogin($arParams["LOGIN"]);
		$userInfo = self::getSecurityUserInfo($userId, true);
		if(!$userId || !$userInfo)
		{
			//user not found or not use OTP
			return true;
		}

		$isSuccess = false;
		if(preg_match("/(\\d{6})$/", $arParams["PASSWORD"], $arMatch))
		{
			$bin_secret = pack('H*', $userInfo["SECRET"]);
			$sync = $arMatch[1];
			$cnt = intval($userInfo["COUNTER"])+1;
			$window = COption::GetOptionInt("security", "hotp_user_window");

			$i = 0;
			while($i < $window)
			{
				if(CSecurityUser::HOTP($bin_secret, $cnt) == $sync)
				{
					$isSuccess = true;
					$arParams["PASSWORD"] = substr($arParams["PASSWORD"], 0, -6);
					$DB->Query("UPDATE b_sec_user SET COUNTER = ".$cnt." WHERE USER_ID = ".$userId);
					break;
				}
				$cnt++;
				$i++;
			}
		}

		if(!$isSuccess)
		{
			$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
			return false;
		}

		return true;
	}

	/**
	 * @param int $pUserId
	 * @param bool $pActiveOnly
	 * @return bool|array
	 */
	public static function getSecurityUserInfo($pUserId, $pActiveOnly = false)
	{
		/**	 @global CDataBase $DB */
		global $DB;
		$queryWhere = "USER_ID = ".intval($pUserId);
		if($pActiveOnly)
			$queryWhere .= " AND ACTIVE='Y'";

		$rsKey = $DB->Query("SELECT * from b_sec_user WHERE ".$queryWhere);
		$arKey = $rsKey->Fetch();
		return $arKey;
	}

	/**
	 * @param string $pLogin
	 * @return int
	 */
	protected static function getUserIdForLogin($pLogin)
	{
		$selectedFields = array("ID");
		$filter = array("LOGIN_EQUAL_EXACT" => $pLogin, "EXTERNAL_AUTH_ID" => "");
		$dbUser = CUser::GetList($by = 'ID', $order = 'ASC', $filter, array("FIELDS" => $selectedFields));
		$userId = 0;
		if($dbUser)
		{
			$userInfo = $dbUser->Fetch();
			$userId = $userInfo["ID"];
		}
		return $userId;
	}

	/**
	 * @param int $pUserId
	 * @return bool|CDBResult
	 */
	public static function deactivate($pUserId)
	{
		/** @global CDataBase $DB */
		global $DB;
		$res = $DB->Query("
			UPDATE b_sec_user SET ACTIVE = 'N'
			WHERE USER_ID = ".intval($pUserId)."
		");
		return $res;
	}

	/**
	 * @param int $pUserId
	 * @return bool|CDBResult
	 */
	public static function delete($pUserId)
	{
		/** @global CDataBase $DB */
		global $DB;
		$res = $DB->Query("
			DELETE FROM b_sec_user
			WHERE USER_ID = ".intval($pUserId)."
		");
		return $res;
	}

	/**
	 * @param string $pBinSecret
	 * @param string $pSync1
	 * @param string $pSync2
	 * @param array $pMessages
	 * @return int
	 */
	public static function getSyncCounter($pBinSecret, $pSync1, $pSync2, &$pMessages)
	{
		if(CSecurityUser::HOTP($pBinSecret, 0) === false)
		{
			$pMessages[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_SYNC_ERROR2"));
			return 0;
		}

		if(!$pSync1)
			$pMessages[] = array("id"=>"security_SYNC1", "text" => GetMessage("SECURITY_USER_ERROR_PASS1_EMPTY"));
		elseif(!preg_match("/^\d{6}$/", $pSync1))
			$pMessages[] = array("id"=>"security_SYNC1", "text" => GetMessage("SECURITY_USER_ERROR_PASS1_INVALID"));

		if(!$pSync2)
			$pMessages[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_PASS2_EMPTY"));
		elseif(!preg_match("/^\d{6}$/", $pSync2))
			$pMessages[] = array("id"=>"security_SYNC2", "text" => GetMessage("SECURITY_USER_ERROR_PASS2_INVALID"));

		$cnt = 0;
		for($i = 0; $i < self::BX_SECURITY_SYNC_WINDOW; $i++)
		{
			if(
				CSecurityUser::HOTP($pBinSecret, $cnt) == $pSync1
				&& CSecurityUser::HOTP($pBinSecret, $cnt+1) == $pSync2
			)
			{
				$cnt++;
				break;
			}
			$cnt++;
		}

		if($i == self::BX_SECURITY_SYNC_WINDOW)
		{
			$pMessages[] = array("id"=>"security_SECRET", "text" => GetMessage("SECURITY_USER_ERROR_SYNC_ERROR"));
			$cnt = 0;
		}

		return $cnt;
	}

	/**
	 * @param $arFields
	 * @return bool
	 */
	public static function update($arFields)
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
				CSecurityUser::deactivate($USER_ID);
			}
			else
			{
				$secret = substr(trim($arFields["SECRET"]), 0, 64);
				if(strlen($secret) <= 0)
				{
					CSecurityUser::delete($USER_ID);
				}
				else
				{
					$arKey = self::getSecurityUserInfo($USER_ID);
					if($arKey && ($arKey["SECRET"] == $secret))
						$cnt = intval($arKey["COUNTER"]);
					else
						$cnt = 0;

					$sync1 = trim($arFields["SYNC1"]);
					$sync2 = trim($arFields["SYNC2"]);

					if($sync1 || $sync2)
					{
						$bin_secret = pack('H*', $secret);
						$cnt = CSecurityUser::getSyncCounter($bin_secret, $sync1, $sync2, $aMsg);
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

	/**
	 * @param $pId
	 * @return bool
	 */
	public static function onUserDelete($pId)
	{
		/** @global CDataBase $DB */
		global $DB;
		$DB->Query("DELETE from b_sec_user WHERE USER_ID = ".intval($pId));
		return true;
	}

	/**
	 * @param $pData
	 * @param $pKey
	 * @return string
	 */
	protected static function hmacsha1($pData, $pKey)
	{
		if(function_exists("hash_hmac"))
			return hash_hmac("sha1", $pData, $pKey);

		if(strlen($pKey)>64)
			$pKey=pack('H*', sha1($pKey));

		$pKey = str_pad($pKey, 64, chr(0x00));
		$ipad = str_repeat(chr(0x36), 64);
		$opad = str_repeat(chr(0x5c), 64);
		$hmac = pack('H*', sha1(($pKey^$opad).pack('H*', sha1(($pKey^$ipad).$pData))));
		return bin2hex($hmac);
	}

	/**
	 * @param $pData
	 * @param $pKey
	 * @return bool|string
	 */
	protected static function hmacsha256($pData, $pKey)
	{
		if(function_exists("hash_hmac"))
			return hash_hmac("sha256", $pData, $pKey);
		else
			return false;
	}

	/**
	 * @param $pSecret
	 * @param $pCount
	 * @param int $pDigits
	 * @return bool|int
	 */
	protected static function HOTP($pSecret, $pCount, $pDigits = 6)
	{
		if(CUtil::BinStrlen($pSecret) <= 25)
			$sha_hash = self::hmacsha1(pack("NN", 0, $pCount), $pSecret);
		else
			$sha_hash = self::hmacsha256(pack("NN", 0, $pCount), $pSecret);

		if($sha_hash !== false)
		{
			$dwOffset = hexdec(substr($sha_hash, -1, 1));
			$dbc1 = hexdec(substr($sha_hash, $dwOffset * 2, 8 ));
			$dbc2 = $dbc1 & 0x7fffffff;
			$hotp = $dbc2 % pow(10, $pDigits);
			return str_pad($hotp, $pDigits, "0", STR_PAD_LEFT);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public static function isActive()
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

	/**
	 * @param bool $pActive
	 */
	public static function setActive($pActive = false)
	{
		if($pActive)
		{
			if(!CSecurityUser::isActive())
			{
				RegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin", "100");
				$f = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php", "w");
				fwrite($f, "<?include(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/security/options_user_settings_1.php\");?>");
				fclose($f);
			}
		}
		else
		{
			if(CSecurityUser::isActive())
			{
				UnRegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin");
				unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php");
			}
		}
	}

}
