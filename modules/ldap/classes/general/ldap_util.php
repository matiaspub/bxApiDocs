<?php

IncludeModuleLangFile(__FILE__);

class CLdapUtil
{
	function GetSynFields()
	{
		static $arSyncFields =	false;
		if(!is_array($arSyncFields))
		{
			// "Field in CUser"=>Array("NAME" => "Name in Bitrix CMS", "AD"=>"Default attribute in AD", "LDAP"=>"Default Attribute in LDAP")
			$arSyncFields = Array(
				"ACTIVE"				=>Array("NAME" => GetMessage("LDAP_FIELD_ACTIVE"), "AD"=>"UserAccountControl&2"),
				"EMAIL"					=>Array("NAME" => GetMessage("LDAP_FIELD_EMAIIL"), "AD"=>"mail", "LDAP"=>"email"),
				"NAME"					=>Array("NAME" => GetMessage("LDAP_FIELD_NAME"), "AD"=>"givenName", "LDAP"=>"cn"),
				"LAST_NAME"				=>Array("NAME" => GetMessage("LDAP_FIELD_LAST_NAME"), "AD"=>"sn", "LDAP"=>"sn"),
				"SECOND_NAME"			=>Array("NAME" => GetMessage("LDAP_FIELD_SECOND_NAME")),
				"PERSONAL_GENDER"		=>Array("NAME" => GetMessage("LDAP_FIELD_GENDER")),
				"PERSONAL_BIRTHDAY"		=>Array("NAME" => GetMessage("LDAP_FIELD_BIRTHDAY")),
				"PERSONAL_PROFESSION"	=>Array("NAME" => GetMessage("LDAP_FIELD_PROF")),
				"PERSONAL_PHOTO"		=>Array("NAME" => GetMessage("LDAP_FIELD_PHOTO"), "AD"=>"thumbnailPhoto", "LDAP"=>"jpegPhoto"),
				"PERSONAL_WWW"			=>Array("NAME" => GetMessage("LDAP_FIELD_WWW"), "AD"=>"wWWHomePage"),
				"PERSONAL_ICQ"			=>Array("NAME" => "ICQ"),
				"PERSONAL_PHONE"		=>Array("NAME" => GetMessage("LDAP_FIELD_PHONE"), "AD"=>"homePhone"),
				"PERSONAL_FAX"			=>Array("NAME" => GetMessage("LDAP_FIELD_FAX")),
				"PERSONAL_MOBILE"		=>Array("NAME" => GetMessage("LDAP_FIELD_MOB"), "AD"=>"mobile"),
				"PERSONAL_PAGER"		=>Array("NAME" => GetMessage("LDAP_FIELD_PAGER")),
				"PERSONAL_STREET"		=>Array("NAME" => GetMessage("LDAP_FIELD_STREET"), "AD"=>"streetAddress"),
				"PERSONAL_MAILBOX"		=>Array("NAME" => GetMessage("LDAP_FIELD_MAILBOX"), "AD"=>"postOfficeBox"),
				"PERSONAL_CITY"			=>Array("NAME" => GetMessage("LDAP_FIELD_CITY"), "AD"=>"l"),
				"PERSONAL_STATE"		=>Array("NAME" => GetMessage("LDAP_FIELD_STATE"), "AD"=>"st"),
				"PERSONAL_ZIP"			=>Array("NAME" => GetMessage("LDAP_FIELD_ZIP"), "AD"=>"postalCode"),
				"PERSONAL_COUNTRY"		=>Array("NAME" => GetMessage("LDAP_FIELD_COUNTRY"), "AD"=>"c"),
				//"PERSONAL_NOTES"		=>Array("NAME" => "Personal notes"),
				"WORK_COMPANY"			=>Array("NAME" => GetMessage("LDAP_FIELD_COMPANY"), "AD"=>"company"),
				"WORK_DEPARTMENT"		=>Array("NAME" => GetMessage("LDAP_FIELD_DEP"), "AD"=>"department"),
				"WORK_POSITION"			=>Array("NAME" => GetMessage("LDAP_FIELD_POS"), "AD"=>"title"),
				//"WORK_WWW"			=>Array("NAME" => "Company web page"),
				"WORK_PHONE"			=>Array("NAME" => GetMessage("LDAP_FIELD_WORK_PHONE"), "AD"=>"telephoneNumber"),
				"WORK_FAX"				=>Array("NAME" => GetMessage("LDAP_FIELD_WORK_FAX"), "AD"=>"facsimileTelephoneNumber"),
				"WORK_PAGER"			=>Array("NAME" => GetMessage("LDAP_FIELD_WORK_PAGER")),
				//"WORK_STREET"			=>Array("NAME" => "Work address"),
				//"WORK_MAILBOX"		=>Array("NAME" => ""),
				//"WORK_CITY"			=>Array("NAME" => ""),
				//"WORK_STATE"			=>Array("NAME" => ""),
				//"WORK_ZIP"			=>Array("NAME" => ""),
				//"WORK_COUNTRY"		=>Array("NAME" => ""),
				//"WORK_PROFILE"		=>Array("NAME" => ""),
				//"WORK_NOTES"			=>Array("NAME" => "Additional notes"),
				"ADMIN_NOTES"			=>Array("NAME" => GetMessage("LDAP_FIELD_ADMIN_NOTES"), "AD"=>"description"),
			);

			$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
			foreach($arRes as $pr_id=>$pr_v)
				if($pr_v["EDIT_FORM_LABEL"]!='')
					$arSyncFields[$pr_id] = Array("NAME"=>$pr_v["EDIT_FORM_LABEL"]);
		}

		return $arSyncFields;
	}

	public static function ConvertADDate($d)
	{
		if(preg_match('#(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\.(\d)Z#', $d, $dt))
			return gmmktime($dt[4], $dt[5], $dt[6], $dt[2], $dt[3], $dt[1]);
		return false;
	}

	public static function ByteXOR($a,$b,$l)
	{
		$c="";
		for($i=0; $i<$l; $i++)

			$c .= $a[$i]^$b[$i];
		return($c);
	}

	public static function BinMD5($val)
	{
		return(pack("H*",md5($val)));
	}

	public static function Decrypt($str, $key=false)
	{
		if($key===false)

		$key = COption::GetOptionString("main", "pwdhashadd", "ldap");
		$key1 = CLdapUtil::BinMD5($key);
		$str = base64_decode($str);
		$res = '';
		while($str)
		{
			if (function_exists('mb_substr'))
			{
				$m = mb_substr($str, 0, 16, "ASCII");
				$str = mb_substr($str, 16, mb_strlen($str,"ASCII")-16, "ASCII");
			}
			else
			{
				$m = substr($str, 0, 16);
				$str = substr($str, 16);
			}

			$m = CLdapUtil::ByteXOR($m, $key1, 16);
			$res .= $m;
			$key1 = CLdapUtil::BinMD5($key.$key1.$m);
		}
		return $res;
	}

	public static function Crypt($str, $key=false)
	{
		if($key===false)
			$key = COption::GetOptionString("main", "pwdhashadd", "ldap");
		$key1 = CLdapUtil::BinMD5($key);
		$res = '';
		while($str)
		{
			if (function_exists('mb_substr'))
			{
				$m = mb_substr($str, 0, 16, "ASCII");
				$str = mb_substr($str, 16, mb_strlen($str,"ASCII")-16, "ASCII");
			}
			else
			{
				$m = substr($str, 0, 16);
				$str = substr($str, 16);
			}

			$res .= CLdapUtil::ByteXOR($m, $key1, 16);
			$key1 = CLdapUtil::BinMD5($key.$key1.$m);
		}
		return(base64_encode($res));
	}

	public static function MkOperationFilter($key)
	{
		if(substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$cOperationType = "N";
		}
		elseif(substr($key, 0, 1)=="?")
		{
			$key = substr($key, 1);
			$cOperationType = "?";
		}
		elseif(substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$cOperationType = "GE";
		}
		elseif(substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$cOperationType = "G";
		}
		elseif(substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$cOperationType = "LE";
		}
		elseif(substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$cOperationType = "L";
		}
		else
			$cOperationType = "E";

		return Array("FIELD"=>$key, "OPERATION"=>$cOperationType);
	}

	public static function FilterCreate($fname, $vals, $type, $cOperationType=false, $bSkipEmpty = true)
	{
		return CLdapUtil::FilterCreateEx($fname, $vals, $type, $bFullJoin, $cOperationType, $bSkipEmpty);
	}

	public static function FilterCreateEx($fname, $vals, $type, &$bFullJoin, $cOperationType=false, $bSkipEmpty = true)
	{
		global $DB;
		if(!is_array($vals))
			$vals=Array($vals);

		if(count($vals)<1)
			return "";
		if(is_bool($cOperationType))
		{
			if($cOperationType===true)
				$cOperationType = "N";
			else
				$cOperationType = "E";
		}

		if($cOperationType=="G")
			$strOperation = ">";
		elseif($cOperationType=="GE")
			$strOperation = ">=";
		elseif($cOperationType=="LE")
			$strOperation = "<=";
		elseif($cOperationType=="L")
			$strOperation = "<";
		else
			$strOperation = "=";

		$bFullJoin = false;
		$bWasLeftJoin = false;

		$res = Array();
		for($i=0; $i<count($vals); $i++)
		{
			$val = $vals[$i];
			if(!$bSkipEmpty || strlen($val)>0 || (is_bool($val) && $val===false))
			{
				switch ($type)
				{
				case "string_equal":
					if($cOperationType=="?")
					{
						if(strlen($val)>0)
							$res[] = GetFilterQuery($fname, $val, "N");
					}
					else
					{
						if(strlen($val)<=0)
							$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
						else
							$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".CLdapUtil::_Upper($fname).$strOperation.CLdapUtil::_Upper("'".$DB->ForSql($val)."'").")";
					}
					break;
				case "string":
					if($cOperationType=="?")
					{
						if(strlen($val)>0)
						{
							$sr = GetFilterQuery($fname, $val, "Y", array(), "N");
							if($sr != "0")
								$res[] = $sr;
						}
					}
					else
					{
						if(strlen($val)<=0)
							$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
						else
							if($strOperation=="=")
								$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".($DB->type=="ORACLE"?CLdapUtil::_Upper($fname)." LIKE ".CLdapUtil::_Upper("'".$DB->ForSqlLike($val)."'")." ESCAPE '\\'" : $fname." ".($strOperation=="="?"LIKE":$strOperation)." '".$DB->ForSqlLike($val)."'").")";
							else
								$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".($DB->type=="ORACLE"?CLdapUtil::_Upper($fname)." ".$strOperation." ".CLdapUtil::_Upper("'".$DB->ForSql($val)."'")." " : $fname." ".$strOperation." '".$DB->ForSql($val)."'").")";
					}
					break;
				case "date":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
					break;
				case "number":
					if($cOperationType=="?")
					{
						$res[] = GetFilterQuery($fname, $val);
					}
					else
					{
						if(strlen($val)<=0)
							$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
						else
							$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." '".DoubleVal($val)."')";
					}
					break;
				case "number_above":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
				}

				// we need this conditions to do INNER JOIN
				if(strlen($val)>0 && $cOperationType!="N")
					$bFullJoin = true;
				else
					$bWasLeftJoin = true;
			}
		}

		$strResult = "";
		for($i=0; $i<count($res); $i++)
		{
			if($i>0)
				$strResult .= ($cOperationType=="N"?" AND ":" OR ");
			$strResult .= "(".$res[$i].")";
		}
		if($strResult!="")
			$strResult = "(".$strResult.")";

		if($bFullJoin && $bWasLeftJoin && $cOperationType!="N")
			$bFullJoin = false;

		return $strResult;
	}

	public static function _Upper($str)
	{
		global $DB;
		return ($DB->type=="ORACLE"?"UPPER(".$str.")":$str);
	}

	// gets department list from system (iblock) for displaying in select box
	public static function getDepartmentListFromSystem($arFilter = Array())
	{
		if (!IsModuleInstalled('intranet')) {
			return false;
		}

		$l=false;
		if (CModule::IncludeModule('iblock'))
		{
			$iblockId=COption::GetOptionInt("intranet","iblock_structure",false,false);
			if ($iblockId)
			{
				$arFilter["IBLOCK_ID"] = $iblockId;
				$arFilter["CHECK_PERMISSIONS"]="N";
				$l = CIBlockSection::GetTreeList($arFilter);
			}
		}
		return $l;
	}

	public static function SetDepartmentHead($userId, $sectionId)
	{
		//echo "Setting ".$userId." as head of ".$sectionId;

		$iblockId=COption::GetOptionInt("intranet","iblock_structure",false,false);

		if ($iblockId && $sectionId && $userId && CModule::IncludeModule('iblock'))
		{
			/*$perm = CIBlock::GetPermission($iblockId);
			if ($perm >= 'W')
			{*/
				$obS = new CIBlockSection();
				if ($obS->Update($sectionId, array('UF_HEAD' => $userId), false, false))
				{
					return true;
				}
				else //if ($obS->LAST_ERROR)
				{
					// update error
					return false;
				}
			/*}
			else
			{
				// access denied
				return false;
			}*/
		}
		else
		{
			// bad data
			return false;
		}
	}

	public static function OnAfterUserAuthorizeHandler()
	{
		if(defined("LDAP_NO_PORT_REDIRECTION"))
			return false;

		global $USER;

		if($USER->IsAuthorized())
		{
			$authNet = COption::GetOptionString("ldap", 'bitrixvm_auth_net', '');

			if (trim($authNet))
				if(self::IsIpFromNet($_SERVER['REMOTE_ADDR'],$authNet)===false)
					return false;

			$backUrl = isset($_GET['back_url']) ? $_GET['back_url'] : "/";

			if ($_SERVER['SERVER_PORT'] == '8890')
				LocalRedirect('http://'.$_SERVER["SERVER_NAME"].$backUrl);
			if ($_SERVER['SERVER_PORT'] == '8891')
				LocalRedirect('https://'.$_SERVER["SERVER_NAME"].$backUrl);
		}

		return true;
	}

	public static function OnEpilogHandler()
	{
		return self::bitrixVMAuthorize();
	}

	public static function bitrixVMAuthorize()
	{
		if(defined("LDAP_NO_PORT_REDIRECTION"))
			return false;

		global $USER, $APPLICATION;

		if(!$USER->IsAuthorized())
		{
			$authNet = COption::GetOptionString("ldap", 'bitrixvm_auth_net', '');

			if (trim($authNet))
				if(self::IsIpFromNet($_SERVER['REMOTE_ADDR'],$authNet)===false)
					return false;

			$backUrl=strlen($APPLICATION->GetCurPage())>1 ? "?back_url=".rawurlencode($APPLICATION->GetCurUri()) : "";

			if ($_SERVER['SERVER_PORT'] == '80')
				LocalRedirect('http://'.$_SERVER["SERVER_NAME"].':8890/'.$backUrl, true);
			elseif (($_SERVER['SERVER_PORT'] == '443'))
				LocalRedirect('https://'.$_SERVER["SERVER_NAME"].':8891/'.$backUrl, true);
		}

		return true;
	}

	public static function isBitrixVMAuthSupported()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$hndl = $eventManager->findEventHandlers("main", "OnEpilog", array("ldap"));
		return !empty($hndl);
	}

	public static function SetBitrixVMAuthSupport($setOption=false, $netAndMask=false)
	{
		RegisterModuleDependences("main", "OnAfterUserAuthorize", 'ldap', 'CLdapUtil', 'OnAfterUserAuthorizeHandler');
		RegisterModuleDependences("main", "OnEpilog", 'ldap', 'CLdapUtil', 'OnEpilogHandler');

		if($setOption)
			COption::SetOptionString("ldap", "bitrixvm_auth_support", "Y");

		if($netAndMask)
			COption::SetOptionString("ldap", "bitrixvm_auth_net", $netAndMask);
	}

	public static function UnSetBitrixVMAuthSupport($unSetOption=false)
	{
		UnRegisterModuleDependences("main", "OnAfterUserAuthorize", 'ldap', 'CLdapUtil', 'OnAfterUserAuthorizeHandler');
		UnRegisterModuleDependences("main", "OnEpilog", 'ldap', 'CLdapUtil', 'OnEpilogHandler');

		if($unSetOption)
			COption::SetOptionString("ldap", "bitrixvm_auth_support", "N");
	}

	/**
	 * decides if ip address is from given network/mask;network1/mask1;network2/mask2;...
	 * @param str $ip - valid ip address  - xxx.xxx.xxx.xxx
	 * @param str @netAndMask - valid mask/network - xxx.xxx.xxx.xxx/xxx.xxx.xxx.xxx;xxx.xxx.xxx.xxx/xxx.xxx.xxx.xxx;... or xxx.xxx.xxx.xxx/xx;xxx.xxx.xxx.xxx/xx;...
	 * @return bool true - if in, bool false - not in, or bad params
	 */
	public static function IsIpFromNet($ip,$netsAndMasks)
	{

		$arNetsMasks = explode(";",$netsAndMasks);

		if(!is_array($arNetsMasks) || empty($arNetsMasks))
			return false;

		foreach ($arNetsMasks as $netAndMask)
		{
			$netAndMask = trim($netAndMask);

			if(!$netAndMask)
				continue;

			if((!preg_match("#^(\d{1,3}\.){3,3}(\d{1,3})/(\d{1,3}\.){3,3}(\d{1,3})$#",$netAndMask) && !preg_match("#^(\d{1,3}\.){3,3}(\d{1,3})/(\d{1,3})$#",$netAndMask)) || !preg_match("#^(\d{1,3}\.){3,3}(\d{1,3})$#",$ip))
				continue;

			$arNetAndMask = explode("/", $netAndMask);

			$net = $arNetAndMask[0];

			if(strpos($arNetAndMask[1],".") !== false) 										//xxx.xxx.xxx.xxx/xxx.xxx.xxx.xxx
				$mask = $arNetAndMask[1];
			else 																			//xxx.xxx.xxx.xxx/xx -> xxx.xxx.xxx.xxx/xxx.xxx.xxx.xxx
				$mask=long2ip('11111111111111111111111111111111'<<(32-$arNetAndMask[1]));

			$newNet = long2ip(ip2long($ip) & ip2long($mask));

			if($newNet == $net)
				return true;
			else
				continue;
		}

		return false;
	}

	/**
	 * Returns image file extension/type/format
	 * @param str $signature - first 12 bytes of the file.
	 * @return mix (str type if success or bool false in opposite case)
	 */
	public static function GetImgTypeBySignature($signature)
	{
		if($signature == "")
			return false;

		$signature = substr($signature,0,12);

		$arSigs = array(
			"GIF" => "gif",
			"\xff\xd8\xff" => "jpg",
			"\x89\x50\x4e" => "png",
			"FWS" => "swf",
			"CWS" => "swc",
			"8BPS" => "psd",
			"BM" => "bmp",
			"\xff\x4f\xff" => "jpc",
			"II\x2a\x00" => "tif",
			"MM\x00\x2a" => "tif",
			"FORM" => "iff",
			"\x00\x00\x01\x00" => "ico",
			"\x0d\x0a\x87\x0a" => "jp2"
			);

		foreach ($arSigs as $sig => $type)
			if(preg_match("/^".$sig."/x", $signature))
				return $type;

		return false;
	}

	public static function isLdapPaginationAviable()
	{
		return function_exists("ldap_control_paged_result");
	}

	/**
	 * Returns true id defined net range for redirection
	 * on ntlm authorization ports 8890, 8891
	 * @return bool
	 */
	public static function isNtlmRedirectNetRangeDefined()
	{
		$authNet = COption::GetOptionString("ldap", 'bitrixvm_auth_net', '');
		return strlen(trim($authNet)) > 0;
	}

	/**
	 * @param string $serverPort Server port.
	 * @return bool|string Port for outlook connection.
	 */
	public static function getTargetPort($serverPort = false)
	{
		if($serverPort === false)
			$serverPort = $_SERVER["SERVER_PORT"];

		$result = false;

		$vmAuth = COption::GetOptionString("ldap", "bitrixvm_auth_support","N") == "Y";
		$useNtlm = COption::GetOptionString("ldap", "use_ntlm","N") == "Y";
		$isNtlmOn = $vmAuth && $useNtlm;

		if($serverPort == "80")
			$result = $isNtlmOn ? "8890" : "80";
		elseif($serverPort == "443")
			$result = $isNtlmOn ? "8891" : "443";

		return $result;
	}
}
?>