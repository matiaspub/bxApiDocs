<?
IncludeModuleLangFile(__FILE__);

abstract class CBXAllShortUri
{
	private static $httpStatusCodes = array(
		301 => "301 Moved Permanently",
		302 => "302 Found",
		/*303 => "303 See Other",
		307 => "307 Temporary Redirect"*/
	);

	protected static $arErrors = array();

	public static function GetErrors()
	{
		return self::$arErrors;
	}

	protected static function AddError($error)
	{
		self::$arErrors[] = $error;
	}

	protected static function ClearErrors()
	{
		self::$arErrors = array();
	}

	public static function Update($id, $arFields)
	{
		global $DB;

		self::ClearErrors();

		$id = intval($id);
		if ($id <= 0)
		{
			self::AddError(GetMessage("MN_SU_NO_ID"));
			return false;
		}

		if (!self::ParseFields($arFields, $id))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_short_uri", $arFields);

		$strSql =
			"UPDATE b_short_uri SET ".
			"	".$strUpdate.", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = ".$id;
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $id;
	}

	public static function GetShortUri($uri)
	{
		$uriCrc32 = self::Crc32($uri);

		$dbResult = CBXShortUri::GetList(array(), array("URI_CRC" => $uriCrc32));
		while ($arResult = $dbResult->Fetch())
		{
			if ($arResult["URI"] == $uri)
				return "/".$arResult["SHORT_URI"];
		}

		$arFields = array(
			"URI" => $uri,
			"SHORT_URI" => self::GenerateShortUri(),
			"STATUS" => 301,
		);

		$id = CBXShortUri::Add($arFields);

		if ($id)
			return "/".$arFields["SHORT_URI"];

		return "";
	}

	public static function GetUri($shortUri)
	{
		$shortUri = trim($shortUri);

		$ar = @parse_url($shortUri);
		if (isset($ar["path"]))
			$shortUri = $ar["path"];

		$shortUri = trim($shortUri, "/");

		$uriCrc32 = self::Crc32($shortUri);

		$dbResult = CBXShortUri::GetList(array(), array("SHORT_URI_CRC" => $uriCrc32));
		while ($arResult = $dbResult->Fetch())
		{
			if ($arResult["SHORT_URI"] == $shortUri)
				return array("URI" => $arResult["URI"], "STATUS" => $arResult["STATUS"], "ID" => $arResult["ID"]);
		}

		return null;
	}

	public static function SetLastUsed($id)
	{
		global $DB;

		$strSql =
			"UPDATE b_short_uri SET ".
			"	NUMBER_USED = NUMBER_USED + 1, ".
			"	LAST_USED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = ".intval($id);
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function Delete($id)
	{
		global $DB, $APPLICATION;

		self::ClearErrors();

		$id = intval($id);
		if ($id <= 0)
		{
			self::AddError(GetMessage("MN_SU_NO_ID"));
			return false;
		}

		foreach(GetModuleEvents("main", "OnBeforeShortUriDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($id)) === false)
			{
				if(($ex = $APPLICATION->GetException()))
					$err = $ex->GetString();
				else
					$err = GetMessage("MN_SU_DELETE_ERROR");
				self::AddError($err);
				return false;
			}
		}

		$fl = $DB->Query("DELETE FROM b_short_uri WHERE ID = ".$id, true);

		if (!$fl)
		{
			self::AddError(GetMessage("MN_SU_DELETE_ERROR"));
			return false;
		}

		return true;
	}

	public static function Crc32($str)
	{
		$c = crc32($str);
		if ($c > 0x7FFFFFFF)
			$c = -(0xFFFFFFFF - $c + 1);
		return $c;
	}

	protected static function ParseFields(&$arFields, $id = 0)
	{
		$id = intval($id);
		$updateMode = ($id > 0 ? true : false);
		$addMode = !$updateMode;

		if (is_set($arFields, "URI") || $addMode)
		{
			$arFields["URI"] = trim($arFields["URI"]);
			if (strlen($arFields["URI"]) <= 0)
			{
				self::AddError(GetMessage("MN_SU_NO_URI"));
				return false;
			}

			$arFields["URI_CRC"] = self::Crc32($arFields["URI"]);
		}

		if (is_set($arFields, "SHORT_URI") || $addMode)
		{
			$arFields["SHORT_URI"] = trim($arFields["SHORT_URI"]);
			if (strlen($arFields["SHORT_URI"]) <= 0)
			{
				self::AddError(GetMessage("MN_SU_NO_SHORT_URI"));
				return false;
			}

			$ar = @parse_url($arFields["SHORT_URI"]);
			if (isset($ar["path"]))
				$arFields["SHORT_URI"] = $ar["path"];

			//$arFields["SHORT_URI"] = @parse_url($arFields["SHORT_URI"], PHP_URL_PATH);
			$arFields["SHORT_URI"] = trim($arFields["SHORT_URI"], "/");
			if (strlen($arFields["SHORT_URI"]) <= 0)
			{
				self::AddError(GetMessage("MN_SU_WRONG_SHORT_URI"));
				return false;
			}

			$arFields["SHORT_URI_CRC"] = self::Crc32($arFields["SHORT_URI"]);
		}

		if (is_set($arFields, "STATUS") || $addMode)
		{
			$arFields["STATUS"] = intval($arFields["STATUS"]);
			if ($arFields["STATUS"] <= 0)
			{
				self::AddError(GetMessage("MN_SU_NO_STATUS"));
				return false;
			}
			elseif (!array_key_exists($arFields["STATUS"], self::$httpStatusCodes))
			{
				self::AddError(GetMessage("MN_SU_WRONG_STATUS"));
				return false;
			}
		}

		if (is_set($arFields, "NUMBER_USED") || $addMode)
		{
			$arFields["NUMBER_USED"] = intval($arFields["NUMBER_USED"]);
			if ($arFields["NUMBER_USED"] <= 0)
				$arFields["NUMBER_USED"] = 0;
		}

		return true;
	}
	
	static public function GetHttpStatusCodeText($code)
	{
		$code = intval($code);

		if (array_key_exists($code, self::$httpStatusCodes))
			return self::$httpStatusCodes[$code];

		return "";
	}

	public static function SelectBox($fieldName, $value, $defaultValue = "", $field = "class=\"typeselect\"")
	{
		$s = '<select name="'.$fieldName.'" '.$field.'>'."\n";
		$s1 = "";
		$found = false;
		foreach (self::$httpStatusCodes as $code => $codeText)
		{
			$found = ($code == $value);
			$m = GetMessage("MN_SU_HTTP_STATUS_".$code);
			$s1 .= '<option value="'.$code.'"'.($found ? ' selected':'').'>'.(empty($m) ? htmlspecialcharsex($codeText) : htmlspecialcharsex($m)).'</option>'."\n";
		}
		if (strlen($defaultValue) > 0)
			$s .= "<option value='' ".($found ? "" : "selected").">".htmlspecialcharsex($defaultValue)."</option>";
		return $s.$s1.'</select>';
	}

	public static function GenerateShortUri()
	{
		do
		{
			$uri = "~".randString(5);
			$bNew = true;
			$uriCrc32 = self::Crc32($uri);

			$dbResult = CBXShortUri::GetList(array(), array("SHORT_URI_CRC" => $uriCrc32));
			while ($arResult = $dbResult->Fetch())
			{
				if ($arResult["SHORT_URI"] == $uri)
				{
					$bNew = false;
					break;
				}
			}
		}
		while (!$bNew);

		return $uri;
	}

	public static function CheckUri()
	{
		if ($arUri = static::GetUri(Bitrix\Main\Context::getCurrent()->getRequest()->getDecodedUri()))
		{
			static::SetLastUsed($arUri["ID"]);
			if (CModule::IncludeModule("statistic"))
			{
				CStatEvent::AddCurrent("short_uri_redirect", "", "", "", "", $arUri["URI"], "N", SITE_ID);
			}
			LocalRedirect($arUri["URI"], true, static::GetHttpStatusCodeText($arUri["STATUS"]));
			return true;
		}
		return false;
	}
}

/*
 * create table b_short_uri
 * (
 *      ID int(18) not null auto_increment,
 *      URI varchar(250) not null,
 *      URI_CRC int(18) not null,
 *      SHORT_URI varbinary(250) not null,
 *      SHORT_URI_CRC int(18) not null,
 *      STATUS int(18) not null default 301,
 *      MODIFIED timestamp not null,
 *      LAST_USED timestamp null,
 *      NUMBER_USED int(18) not null default 0,
 *      primary key (ID),
 *      index ux_b_short_uri_1 (SHORT_URI_CRC),
 *      index ux_b_short_uri_2 (URI_CRC)
 * )
 * */
?>