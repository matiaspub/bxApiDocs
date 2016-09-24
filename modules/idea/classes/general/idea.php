<?
Class CIdeaManagment
{
	//User Fields Id
	const UFStatusField = 'UF_STATUS';
	const UFAnswerIdField = 'UF_ANSWER_ID';
	const UFCategroryCodeField = 'UF_CATEGORY_CODE';
	const UFOriginalIdField = 'UF_ORIGINAL_ID';
	//End -> User Fields Id

	//Instance
	protected static $Instance = NULL;
	public static function getInstance()
	{
		if(self::$Instance == NULL)
			self::$Instance = new self;

		return self::$Instance;
	}
	private function __clone(){}
	private function __construct(){}

	static public function IsAvailable()
	{
		return CModule::IncludeModule('blog') && CModule::IncludeModule('iblock');
	}

	static public function GetUserFieldsArray()
	{
		return array(
			self::UFStatusField,
			self::UFAnswerIdField,
			self::UFCategroryCodeField,
			self::UFOriginalIdField,
		);
	}

	//xAlias
	static public function Idea($IdeaId = false)
	{
		return CIdeaManagmentIdea::GetInstance($IdeaId);
	}
	//xAlias
	static public function IdeaComment($CommentId = false)
	{
		return new CIdeaManagmentIdeaComment($CommentId);
	}
	//xAlias
	static public function Notification($arNotification = array())
	{
		return new CIdeaManagmentNotify($arNotification);
	}

	/*************TOOLS**********/
	public function GetRSS($BlogCode, $type = "rss2.0", $numPosts = 10, $siteID = SITE_ID, $arPathTemplates = Array(), $arFilterExt = array())
	{
		if(!$this->IsAvailable())
			return false;

		global $USER;
		//Post CNT
		$numPosts = IntVal($numPosts);
		//RSS type
		$type = ToLower(preg_replace("/[^a-zA-Z0-9.]/is", "", $type));
		if(!in_array($type, array("rss2.0", "atom.03", "rss.92")))
			$type = "rss.92";


		//Prepare Extended filter
		if(!is_array($arFilterExt))
			$arFilterExt = array();

		$arSettings = array(
			"BLOG_CODE" => $BlogCode,
			"NOW" => date("r"),
			"NOW_ISO" => date("Y-m-d\TH:i:s").substr(date("O"), 0, 3).":".substr(date("O"), -2, 2),
			"SERVER_NAME" => "",
			"CHARSET" => "",
			"LANGUAGE" => "",
			"RSS" => "", //RSS Content
			"RSS_TYPE" => $type,
			"CURRENT_USER_ID" => $USER->IsAuthorized() ?$USER->GetID() :0,
			"CATEGORIES" => CIdeaManagment::getInstance()->Idea()->GetCategoryList(),
		);


		//Get Settings if possible
		if ($arSite = CSite::GetList(($s = "sort"), ($o = "asc"), array("LID" => SITE_ID))->Fetch())
		{
			$arSettings["SERVER_NAME"] = $arSite["SERVER_NAME"];
			$arSettings["CHARSET"] = $arSite["CHARSET"];
			$arSettings["LANGUAGE"] = $arSite["LANGUAGE_ID"];
		}
		//Get Server Name
		if (strlen($arSettings["SERVER_NAME"]) == 0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
				$arSettings["SERVER_NAME"] = SITE_SERVER_NAME;
			else
				$arSettings["SERVER_NAME"] = COption::GetOptionString("main", "server_name", "");
		}
		//Get Site Charset
		if (strlen($arSettings["CHARSET"]) == 0)
		{
			if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
				$arSettings["CHARSET"] = SITE_CHARSET;
			else
				$arSettings["CHARSET"] = "windows-1251";
		}

		$arSettings["BLOG_URL"] = "http://".$arSettings["SERVER_NAME"];
		if(!empty($arPathTemplates) && strlen($arPathTemplates["INDEX"])>0)
			$arSettings["BLOG_URL"] .= $arPathTemplates["INDEX"];
		if(!empty($arPathTemplates) && strlen($arPathTemplates["CUSTOM_TITLE"])>0)
			$arSettings["BLOG_NAME"] = htmlspecialcharsbx($arPathTemplates["CUSTOM_TITLE"]);
		else
			$arSettings["BLOG_NAME"] = "\"".htmlspecialcharsbx($arSite["NAME"])."\" (".$arSettings["SERVER_NAME"].")";

		//Prepare Head Type part
		if ($arSettings["RSS_TYPE"] == "rss.92")
		{
			$arSettings["RSS"] .= "<"."?xml version=\"1.0\" encoding=\"".$arSettings["CHARSET"]."\"?".">\n\n";
			$arSettings["RSS"] .= "<rss version=\".92\">\n";
			$arSettings["RSS"] .= " <channel>\n";
			$arSettings["RSS"] .= "	<title>".$arSettings["BLOG_NAME"]."</title>\n";
			$arSettings["RSS"] .= "	<link>".$arSettings["BLOG_URL"]."</link>\n";
			$arSettings["RSS"] .= "	<guid>".$arSettings["BLOG_URL"]."</guid>\n";
			$arSettings["RSS"] .= "	<language>".$arSettings["LANGUAGE"]."</language>\n";
			$arSettings["RSS"] .= "	<docs>http://backend.userland.com/rss092</docs>\n";
			$arSettings["RSS"] .= "\n";
		}
		elseif ($arSettings["RSS_TYPE"] == "rss2.0")
		{
			$arSettings["RSS"] .= "<"."?xml version=\"1.0\" encoding=\"".$arSettings["CHARSET"]."\"?".">\n\n";
			$arSettings["RSS"] .= "<rss version=\"2.0\">\n";
			$arSettings["RSS"] .= " <channel>\n";
			$arSettings["RSS"] .= "	<title>".$arSettings["BLOG_NAME"]."</title>\n";
			$arSettings["RSS"] .= "	<description>".$arSettings["BLOG_NAME"]."</description>\n";
			$arSettings["RSS"] .= "	<link>".$arSettings["BLOG_URL"]."</link>\n";
			$arSettings["RSS"] .= "	<language>".$arSettings["LANGUAGE"]."</language>\n";
			$arSettings["RSS"] .= "	<docs>http://backend.userland.com/rss2</docs>\n";
			$arSettings["RSS"] .= "	<pubDate>".$arSettings["NOW"]."</pubDate>\n";
			$arSettings["RSS"] .= "\n";
		}
		elseif ($arSettings["RSS_TYPE"] == "atom.03")
		{
			$atomID = "tag:".htmlspecialcharsbx($arSettings["SERVER_NAME"]).",".date("Y-m-d");

			$arSettings["RSS"] .= "<"."?xml version=\"1.0\" encoding=\"".$arSettings["CHARSET"]."\"?".">\n\n";
			$arSettings["RSS"] .= "<feed version=\"0.3\" xmlns=\"http://purl.org/atom/ns#\" xml:lang=\"".$arSettings["LANGUAGE"]."\">\n";
			$arSettings["RSS"] .= "  <title>".$arSettings["BLOG_NAME"]."</title>\n";
			$arSettings["RSS"] .= "  <tagline>".$arSettings["BLOG_URL"]."</tagline>\n";
			$arSettings["RSS"] .= "  <id>".$atomID."</id>\n";
			$arSettings["RSS"] .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$arSettings["BLOG_URL"]."\" />\n";
			$arSettings["RSS"] .= "  <copyright>Copyright (c) ".$arSettings["SERVER_NAME"]."</copyright>\n";
			$arSettings["RSS"] .= "  <modified>".$arSettings["NOW_ISO"]."</modified>\n";
			$arSettings["RSS"] .= "\n";
		}

		$arParserParams = Array(
			"imageWidth" => $arPathTemplates["IMAGE_MAX_WIDTH"],
			"imageHeight" => $arPathTemplates["IMAGE_MAX_HEIGHT"],
		);
		//Text Parser
		$parser = new blogTextParser();

		//SELECT
		$arSelFields = array("ID", "TITLE", "DETAIL_TEXT", "DATE_PUBLISH", "AUTHOR_ID", "BLOG_USER_ALIAS", "BLOG_ID", "DETAIL_TEXT_TYPE", "BLOG_URL", "BLOG_OWNER_ID", "BLOG_SOCNET_GROUP_ID", "BLOG_GROUP_SITE_ID", "CODE", self::UFCategroryCodeField);
		//WHERE
		$arFilter = array(
				"<=DATE_PUBLISH" => ConvertTimeStamp(false, "FULL", false),
				"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
				"BLOG_ENABLE_RSS" => "Y",
				"MICRO" => "N",
		);
		if(intval($arSettings["BLOG_CODE"]) === $arSettings["BLOG_CODE"])
			$arFilter["BLOG_ID"] = $arSettings["BLOG_CODE"];
		else
			$arFilter["BLOG_URL"] = $arSettings["BLOG_CODE"];
		//Extend standart filter
		$arFilter = array_merge($arFilter, $arFilterExt);


		CTimeZone::Disable();
		$dbPosts = CBlogPost::GetList(
				array("DATE_PUBLISH" => "DESC"),
				$arFilter,
				false,
				array("nTopCount" => $numPosts),
				$arSelFields
		);
		CTimeZone::Enable();

		while($arPost = $dbPosts->Fetch())
		{
			//Can read
			if (CBlogPost::GetBlogUserPostPerms($arPost["ID"], $arSettings["CURRENT_USER_ID"]) < BLOG_PERMS_READ)
				continue;

			$arAuthorUser = $USER->GetByID($arPost["AUTHOR_ID"])->Fetch();
			$author = CBlogUser::GetUserName($arPost["BLOG_USER_ALIAS"], $arAuthorUser["NAME"], $arAuthorUser["LAST_NAME"], $arAuthorUser["LOGIN"], $arAuthorUser["SECOND_NAME"]);

			$title = str_replace(
				array("&", "<", ">", "\""),
				array("&amp;", "&lt;", "&gt;", "&quot;"),
				$author.": ".$arPost["TITLE"]
			);

			//Idea Images
			$arImages = Array();
			$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arPost["BLOG_ID"], "IS_COMMENT" => "N"));
			while ($arImage = $res->Fetch())
				$arImages[$arImage['ID']] = $arImage['FILE_ID'];

			$arDate = ParseDateTime($arPost["DATE_PUBLISH"], CSite::GetDateFormat("FULL", $arPost["BLOG_GROUP_SITE_ID"]));
			$date = date("r", mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));

			if(!empty($arPathTemplates))
				$url = htmlspecialcharsbx("http://".$arSettings["SERVER_NAME"].CComponentEngine::MakePathFromTemplate($arPathTemplates["BLOG_POST"], array("blog" => $arPost["BLOG_URL"], "post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arPathTemplates["ALLOW_POST_CODE"]), "user_id"=>$arPost["BLOG_OWNER_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			else
				$url = htmlspecialcharsbx("http://".$arSettings["SERVER_NAME"].CBlogPost::PreparePath(htmlspecialcharsbx($arPost["BLOG_URL"]), $arPost["ID"], $arPost["BLOG_GROUP_SITE_ID"]));

			$category = "";
			if(isset($arPost[self::UFCategroryCodeField]) && is_array($arSettings["CATEGORIES"][ToUpper($arPost[self::UFCategroryCodeField])]))
				$category = htmlspecialcharsbx($arSettings["CATEGORIES"][ToUpper($arPost[self::UFCategroryCodeField])]["NAME"]);

			if(strlen($arPathTemplates["USER"]) > 0)
				$authorURL = htmlspecialcharsbx("http://".$arSettings["SERVER_NAME"].CComponentEngine::MakePathFromTemplate($arPathTemplates["USER"], array("user_id"=>$arPost["AUTHOR_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			else
				$authorURL = htmlspecialcharsbx("http://".$arSettings["SERVER_NAME"].CBlogUser::PreparePath($arPost["AUTHOR_ID"], $arPost["BLOG_GROUP_SITE_ID"]));

			if($arPost["DETAIL_TEXT_TYPE"] == "html")
				$IdeaText = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "QUOTE" => "Y", "CODE" => "Y"), true, $arParserParams);
			else
				$IdeaText = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, false, true, $arParserParams);

			$IdeaText .= "<br /><a href=\"".$url."\">".GetMessage("BLG_GB_RSS_DETAIL")."</a>";
			$IdeaText = "<![CDATA[".$IdeaText."]]>";

			if ($arSettings["RSS_TYPE"] == "rss.92")
			{
				$arSettings["RSS"] .= "	<item>\n";
				$arSettings["RSS"] .= "	  <title>".$title."</title>\n";
				$arSettings["RSS"] .= "	  <description>".$IdeaText."</description>\n";
				$arSettings["RSS"] .= "	  <link>".$url."</link>\n";
				$arSettings["RSS"] .= "	</item>\n";
				$arSettings["RSS"] .= "\n";
			}
			elseif ($arSettings["RSS_TYPE"] == "rss2.0")
			{
				$arSettings["RSS"] .= "	<item>\n";
				$arSettings["RSS"] .= "	  <title>".$title."</title>\n";
				$arSettings["RSS"] .= "	  <description>".$IdeaText."</description>\n";
				$arSettings["RSS"] .= "	  <link>".$url."</link>\n";
				$arSettings["RSS"] .= "	  <guid>".$url."</guid>\n";
				$arSettings["RSS"] .= "	  <pubDate>".$date."</pubDate>\n";
				if(strlen($category) > 0)
					$arSettings["RSS"] .= "	  <category>".$category."</category>\n";
				$arSettings["RSS"] .= "	</item>\n";
				$arSettings["RSS"] .= "\n";
			}
			elseif ($arSettings["RSS_TYPE"] == "atom.03")
			{
				$atomID = "tag:".htmlspecialcharsbx($arSettings["SERVER_NAME"]).":".$arBlog["URL"]."/".$arPost["ID"];

				$timeISO = mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]);
				$dateISO = date("Y-m-d\TH:i:s", $timeISO).substr(date("O", $timeISO), 0, 3).":".substr(date("O", $timeISO), -2, 2);

				$titleRel = htmlspecialcharsbx($arPost["TITLE"]);

				$arSettings["RSS"] .= "<entry>\n";
				$arSettings["RSS"] .= "  <title type=\"text/html\">".$title."</title>\n";
				$arSettings["RSS"] .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$url."\"/>\n";
				$arSettings["RSS"] .= "  <issued>".$dateISO."</issued>\n";
				$arSettings["RSS"] .= "  <modified>".$arSettings["NOW_ISO"]."</modified>\n";
				$arSettings["RSS"] .= "  <id>".$atomID."</id>\n";
				$arSettings["RSS"] .= "  <content type=\"text/html\" mode=\"escaped\" xml:lang=\"".$arSettings["LANGUAGE"]."\" xml:base=\"".$arSettings["BLOG_URL"]."\">\n";
				$arSettings["RSS"] .= $IdeaText."\n";
				$arSettings["RSS"] .= "  </content>\n";
				$arSettings["RSS"] .= "  <link rel=\"related\" type=\"text/html\" href=\"".$url."\" title=\"".$titleRel."\"/>\n";
				$arSettings["RSS"] .= "  <author>\n";
				$arSettings["RSS"] .= "	<name>".htmlspecialcharsbx($author)."</name>\n";
				$arSettings["RSS"] .= "	<url>".$authorURL."</url>\n";
				$arSettings["RSS"] .= "  </author>\n";
				$arSettings["RSS"] .= "</entry>\n";
				$arSettings["RSS"] .= "\n";
			}
		}

		if ($arSettings["RSS_TYPE"] == "rss.92")
			$arSettings["RSS"] .= "  </channel>\n</rss>";
		elseif ($arSettings["RSS_TYPE"] == "rss2.0")
			$arSettings["RSS"] .= "  </channel>\n</rss>";
		elseif ($arSettings["RSS_TYPE"] == "atom.03")
			$arSettings["RSS"] .= "\n\n</feed>";

		return $arSettings["RSS"];
	}

	//DEPRECATED!!! DON'T USE!!! Will Be Removed
	//Alias
	public function GetCategoryList($CategoryIB = false)
	{
		if($CategoryIB>0)
			$this->SetCategoryListId($CategoryIB);
		return CIdeaManagment::getInstance()->Idea()->GetCategoryList();
	}
	//Alias
	static public function SetCategoryListId($ID)
	{
		CIdeaManagment::getInstance()->Idea()->SetCategoryListId($ID);
		return $this;
	}
	//Alias
	static public function GetStatusList()
	{
		return CIdeaManagment::getInstance()->Idea()->GetStatusList();
	}
	//Alias
	public static function GetCategorySequenceByCode($CODE, $arCategoryList = false)
	{
		return CIdeaManagment::getInstance()->Idea()->GetCategorySequence($CODE);
	}
}
?>