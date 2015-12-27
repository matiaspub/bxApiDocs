<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_TRACKBACK"] = Array();

class CAllBlogTrackback
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;

		if ((is_set($arFields, "BLOG_ID") || $ACTION=="ADD") && IntVal($arFields["BLOG_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GT_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
			return false;
		}
		elseif (is_set($arFields, "BLOG_ID"))
		{
			$arResult = CBlog::GetByID($arFields["BLOG_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["BLOG_ID"], GetMessage("BLG_GT_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				return false;
			}
		}

		if ((is_set($arFields, "POST_ID") || $ACTION=="ADD") && IntVal($arFields["POST_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GT_EMPTY_POST_ID"), "EMPTY_POST_ID");
			return false;
		}
		elseif (is_set($arFields, "POST_ID"))
		{
			$arResult = CBlogPost::GetByID($arFields["POST_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["POST_ID"], GetMessage("BLG_GT_ERROR_NO_POST")), "ERROR_NO_POST");
				return false;
			}
		}

		if (is_set($arFields, "POST_DATE") && (!$DB->IsDate($arFields["POST_DATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GT_ERROR_POST_DATE"), "ERROR_POST_DATE");
			return false;
		}

		if ((is_set($arFields, "TITLE") || $ACTION=="ADD") && strlen($arFields["TITLE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GT_EMPTY_TITLE"), "EMPTY_TITLE");
			return false;
		}

		if ((is_set($arFields, "URL") || $ACTION=="ADD") && strlen($arFields["URL"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GT_EMPTY_URL"), "EMPTY_URL");
			return false;
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$arTrackback = CBlogTrackback::GetByID($ID);
		if ($arTrackback)
			CBlogPost::Update($arTrackback["POST_ID"], array("=NUM_TRACKBACKS" => "NUM_TRACKBACKS - 1"));

		unset($GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_blog_trackback WHERE ID = ".$ID."", true);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID]) && is_array($GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID]) && is_set($GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT T.ID, T.TITLE, T.URL, T.PREVIEW_TEXT, T.BLOG_NAME, T.BLOG_ID, T.POST_ID, ".
				"	".$DB->DateToCharFunction("T.POST_DATE", "FULL")." as POST_DATE ".
				"FROM b_blog_trackback T ".
				"WHERE T.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_TRACKBACK"]["BLOG_TRACKBACK_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}


	//*************** SEND / RECEIVE PINGS *********************/
	public static function SendPing($postID, $arPingUrls = array())
	{
		$postID = IntVal($postID);

		if (count($arPingUrls) <= 0)
			return False;

		$arPost = CBlogPost::GetByID($postID);
		if ($arPost)
		{
			$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
			$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);

			$title = urlencode($arPost["TITLE"]);
			$excerpt = urlencode(substr($arPost["DETAIL_TEXT"], 0, 255));
			$blogName = urlencode($arBlog["NAME"]);

			$serverName = "";
			$charset = "";
			$dbSite = CSite::GetList(($b = "sort"), ($o = "asc"), array("LID" => $arGroup["SITE_ID"]));
			if ($arSite = $dbSite->Fetch())
			{
				$serverName = $arSite["SERVER_NAME"];
				$charset = $arSite["CHARSET"];
			}

			if (strlen($serverName) <= 0)
			{
				if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
					$serverName = SITE_SERVER_NAME;
				else
					$serverName = COption::GetOptionString("main", "server_name", "");
			}

			if (strlen($charset) <= 0)
			{
				if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
					$charset = SITE_CHARSET;
				else
					$charset = "windows-1251";
			}

			$url = urlencode("http://".$serverName.CBlogPost::PreparePath($arBlog["URL"], $postID, $arGroup["SITE_ID"]));


			foreach($arPingUrls as $pingUrl)
			{
				$pingUrl = str_replace("http://", "", $pingUrl);
				$pingUrl = str_replace("https://", "", $pingUrl);
				$arPingUrl = explode("/", $pingUrl);

				$host = trim($arPingUrl[0]);
				unset($arPingUrl[0]);
				$path = "/".trim(implode("/", $arPingUrl));

				$arHost = explode(":", $host);
				$port = ((count($arHost) > 1) ? $arHost[1] : 80);
				$host = $arHost[0];

				if (!empty($path) && !empty($host))
				{
					$query = "title=".$title."&url=".$url."&excerpt=".$excerpt."&blog_name=".$blogName;
					$fp = @fsockopen($host, $port, $errnum, $errstr, 30);
					if ($fp)
					{ 
						fputs($fp, "POST {$path} HTTP/1.1\r\n");
						fputs($fp, "Host: {$host}\r\n");
						fputs($fp, "Content-type: application/x-www-form-urlencoded; charset=\"".$charset."\"\r\n");
						fputs($fp, "User-Agent: bitrixBlog\r\n");
						fputs($fp, "Content-length: ".strlen($query)."\r\n");
						fputs($fp, "Connection: close\r\n\r\n");
						fputs($fp, $query."\r\n\r\n");
						fclose($fp);
					}
				}
			}
		}
	}

	public static function GetPing($blogUrl, $postID, $arParams = array())
	{
		global $DB;

		$blogUrl = Trim($blogUrl);
		$postID = IntVal($postID);

		$bSuccess = True;

		$arPost = CBlogPost::GetByID($postID);
		if (!$arPost)
		{
			CBlogTrackback::SendPingResponce(1, "Invalid target post");
			$bSuccess = False;
		}

		if ($bSuccess)
		{
			if ($arPost["ENABLE_TRACKBACK"] != "Y" || COption::GetOptionString("blog","enable_trackback", "Y") != "Y")
			{
				CBlogTrackback::SendPingResponce(1, "Trackbacks disabled");
				$bSuccess = False;
			}
		}
		
		if ($bSuccess)
		{
			$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
			if (!$arBlog || $arBlog["URL"] != $blogUrl)
			{
				CBlogTrackback::SendPingResponce(1, "Invalid target blog");
				$bSuccess = False;
			}
		}

		if ($bSuccess)
		{
			if (!isset($arParams["title"]) || strlen($arParams["title"]) <= 0
				|| !isset($arParams["url"]) || strlen($arParams["url"]) <= 0)
			{
				CBlogTrackback::SendPingResponce(1, "Missing required fields");
				$bSuccess = False;
			}
		}

		if ($bSuccess)
		{
			if (!isset($arParams["excerpt"]))
				$arParams["excerpt"] = $arParams["title"];

			if (!isset($arParams["blog_name"]))
				$arParams["blog_name"] = "";
		}

		if ($bSuccess)
		{
			$serverCharset = "";
			$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);

			$dbSite = CSite::GetList(($b = "sort"), ($o = "asc"), array("LID" => $arGroup["SITE_ID"]));
			if ($arSite = $dbSite->Fetch())
				$serverCharset = $arSite["CHARSET"];

			if (strlen($serverCharset) <= 0)
			{
				if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
					$serverCharset = SITE_CHARSET;
				else
					$serverCharset = "windows-1251";
			}

			preg_match("/charset=(\")*(.*?)(\")*(;|$)/", $_SERVER["CONTENT_TYPE"], $charset);
			$charset = preg_replace("#[^[:space:]a-zA-Z0-9\-]#is", "", $charset[2]);
			if(strlen($charset)<=0) $charset = "utf-8";
			
			if ($charset != $serverCharset)
			{
				$arParams["title"] = $GLOBALS["APPLICATION"]->ConvertCharset($arParams["title"], $charset, $serverCharset);
				$arParams["url"] = $GLOBALS["APPLICATION"]->ConvertCharset($arParams["url"], $charset, $serverCharset);
				$arParams["excerpt"] = $GLOBALS["APPLICATION"]->ConvertCharset($arParams["excerpt"], $charset, $serverCharset);
				$arParams["blog_name"] = $GLOBALS["APPLICATION"]->ConvertCharset($arParams["blog_name"], $charset, $serverCharset);
			}

			$arFields = array(
				"TITLE" => $arParams["title"],
				"URL" => $arParams["url"],
				"PREVIEW_TEXT" => $arParams["excerpt"],
				"BLOG_NAME" => $arParams["blog_name"],
				"=POST_DATE" => $DB->CurrentTimeFunction(),
				"BLOG_ID" => $arPost["BLOG_ID"],
				"POST_ID" => $arPost["ID"]
			);
			$dbTrackback = CBlogTrackback::GetList(array(), array("BLOG_ID" => $arPost["BLOG_ID"], "POST_ID" => $arPost["ID"], "URL" => $arParams["url"]));
			if ($arTrackback = $dbTrackback->Fetch())
			{
				if (!CBlogTrackback::Update($arTrackback["ID"], $arFields))
				{
					if ($ex = $GLOBALS["APPLICATION"]->GetException())
						$errorMessage = $ex->GetString().".<br>";
					else
						$errorMessage = "Unknown error".".<br>";
					CBlogTrackback::SendPingResponce(1, $errorMessage);
				}
			}
			else
			{
				if (!CBlogTrackback::Add($arFields))
				{
					if ($ex = $GLOBALS["APPLICATION"]->GetException())
						$errorMessage = $ex->GetString().".<br>";
					else
						$errorMessage = "Unknown error".".<br>";
					CBlogTrackback::SendPingResponce(1, $errorMessage);
				}
			}

			CBlogTrackback::SendPingResponce(0, "Ping accepted");
		}

		return $bSuccess;
	}

	public static function SendPingResponce($error = 0, $text = "")
	{
		header("Content-type: text/xml");
		echo "<"."?xml version=\"1.0\" encoding=\"".SITE_CHARSET."\"?".">\n";
		echo "<response>\n";
		echo "<error>".htmlspecialcharsbx($error)."</error>\n";
		echo "<message>".htmlspecialcharsbx($text)."</message>\n";
		echo "</response>";
	}
}
?>