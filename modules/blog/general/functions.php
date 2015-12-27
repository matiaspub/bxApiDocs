<?
IncludeModuleLangFile(__FILE__);
class blogTextParser extends CTextParser
{
	public function blogTextParser($strLang = False, $pathToSmile = false)
	{
		$this->CTextParser();
		global $CACHE_MANAGER;
		if ($strLang===False)
			$strLang = LANGUAGE_ID;

		if(strlen($pathToSmile) <= 0)
			$pathToSmile = "/bitrix/images/blog/smile/";
		$this->pathToSmile = $pathToSmile;
		$this->imageWidth = COption::GetOptionString("blog", "image_max_width", 600);
		$this->imageHeight = COption::GetOptionString("blog", "image_max_height", 1000);
		$this->showedImages = array();
		$this->ajaxPage = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("bxajaxid", "logout"));
		$this->blogImageSizeEvents = GetModuleEvents("blog", "BlogImageSize", true);
		$this->arUserfields = array();
		$arSmiles = array();

		$this->smiles = array();
		if($CACHE_MANAGER->Read(60*60*24*365, "b_blog_smile"))
		{
			$arSmiles = $CACHE_MANAGER->Get("b_blog_smile");
		}
		else
		{
			$db_res = CBlogSmile::GetList(array("SORT" => "ASC"), array("SMILE_TYPE" => "S"/*, "LANG_LID" => $strLang*/), false, false, Array("LANG_LID", "ID", "IMAGE", "DESCRIPTION", "TYPING", "SMILE_TYPE", "SORT"));
			while ($res = $db_res->Fetch())
			{
				$tok = strtok($res["TYPING"], " ");
				while ($tok)
				{
					$arSmiles[$res["LANG_LID"]][] = array(
										"TYPING" => $tok,
										"IMAGE"  => stripslashes($res["IMAGE"]),
										"DESCRIPTION" => stripslashes($res["NAME"]));
					$tok = strtok(" ");
				}
			}

			public function sortlen($a, $b) 
			{
				if (strlen($a["TYPING"]) == strlen($b["TYPING"]))
					return 0;

				return (strlen($a["TYPING"]) > strlen($b["TYPING"])) ? -1 : 1;
			}

			if(!empty($arSmiles))
			{
				foreach ($arSmiles as $LID => $arSmilesLID)
				{
					uasort($arSmilesLID, 'sortlen');
					$arSmiles[$LID] = $arSmilesLID;
				}
			}

			$CACHE_MANAGER->Set("b_blog_smile", $arSmiles);
		}
		$this->smiles = $arSmiles[$strLang];
	}

	public function convert($text, $bPreview = True, $arImages = array(), $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N", "SHORT_ANCHOR" => "N"), $arParams = Array())
	{
		if(!is_array($arParams) && strlen($arParams) > 0)
			$type = $arParams;
		elseif(is_array($arParams))
			$type = $arParams["type"];

		if(IntVal($arParams["imageWidth"]) > 0)
			$this->imageWidth = IntVal($arParams["imageWidth"]);
		if(IntVal($arParams["imageHeight"]) > 0)
			$this->imageHeight = IntVal($arParams["imageHeight"]);
		if(strlen($arParams["pathToUser"]) > 0)
			$this->pathToUser = $arParams["pathToUser"];
		$this->parser_nofollow = COption::GetOptionString("blog", "parser_nofollow", "N");

		$this->type = ($type == "rss" ? "rss" : "html");
		$this->isSonetLog = $arParams["isSonetLog"];

		$this->allow = array(
			"HTML" => ($allow["HTML"] == "Y" ? "Y" : "N"),
			"NL2BR" => ($allow["NL2BR"] == "Y" ? "Y" : "N"),
			"CODE" => ($allow["CODE"] == "N" ? "N" : "Y"),
			"VIDEO" => ($allow["VIDEO"] == "N" ? "N" : "Y"),
			"ANCHOR" => ($allow["ANCHOR"] == "N" ? "N" : "Y"),
			"BIU" => ($allow["BIU"] == "N" ? "N" : "Y"),
			"IMG" => ($allow["IMG"] == "N" ? "N" : "Y"),
			"QUOTE" => ($allow["QUOTE"] == "N" ? "N" : "Y"),
			"FONT" => ($allow["FONT"] == "N" ? "N" : "Y"),
			"LIST" => ($allow["LIST"] == "N" ? "N" : "Y"),
			"SMILES" => ($allow["SMILES"] == "N" ? "N" : "Y"),
			"TABLE" => ($allow["TABLE"] == "N" ? "N" : "Y"),
			"ALIGN" => ($allow["ALIGN"] == "N" ? "N" : "Y"),
			"CUT_ANCHOR" => ($allow["CUT_ANCHOR"] == "Y" ? "Y" : "N"),
			"SHORT_ANCHOR" => ($allow["SHORT_ANCHOR"] == "Y" ? "Y" : "N"),
			"USER" => ($allow["USER"] == "N" ? "N" : "Y"),
			"TAG" => ($allow["TAG"] == "N" ? "N" : "Y"),
			"USERFIELDS" => (is_array($allow["USERFIELDS"]) ? $allow["USERFIELDS"] : array())
		);
		if (!empty($this->arUserfields))
			$this->allow["USERFIELDS"] = array_merge($this->allow["USERFIELDS"], $this->arUserfields);

		$this->arImages = $arImages;
		$this->bPreview = $bPreview;

		static $firstCall = true;
		if ($firstCall)
		{
			$firstCall = false;
			AddEventHandler("main", "TextParserBefore", Array("blogTextParser", "ParserCut"));
			AddEventHandler("main", "TextParserBefore", Array("blogTextParser", "ParserBlogImageBefore"));
			AddEventHandler("main", "TextParserAfterTags", Array("blogTextParser", "ParserBlogImage"));
			AddEventHandler("main", "TextParserAfterTags", Array("blogTextParser", "ParserUser"));
			AddEventHandler("main", "TextParserAfterTags", Array("blogTextParser", "ParserTag"));
			AddEventHandler("main", "TextParserAfter", Array("blogTextParser", "ParserCutAfter"));
			AddEventHandler("main", "TextParserVideoConvert", Array("blogTextParser", "blogConvertVideo"));
		}

		$text = $this->convertText($text);
		return trim($text);
	}

	public static function ParserCut(&$text, &$obj)
	{
		if ($obj->bPreview)
		{
			$text = preg_replace("#^(.*?)<cut[\s]*(/>|>).*?$#is", "\\1", $text);
			$text = preg_replace("#^(.*?)\[cut[\s]*(/\]|\]).*?$#is", "\\1", $text);
		}
		else
		{
			$text = preg_replace("#<cut[\s]*(/>|>)#is", "[cut]", $text);
		}
	}
	public static function ParserCutAfter(&$text, &$obj)
	{
		if (!$obj->bPreview)
		{
			$text = preg_replace("#\[cut[\s]*(/\]|\])#is", "<a name=\"cut\"></a>", $text);
		}
	}

	public static function ParserBlogImageBefore(&$text, &$obj)
	{
		$text = preg_replace("/\[img([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER, "[imag id=\\1 \\2 \\3]", $text);
	}

	public static function ParserBlogImage(&$text, &$obj)
	{
		if(is_callable(array($obj, 'convert_blog_image')))
		{
			$text = preg_replace_callback(
				"/\[imag([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
				array($obj, "convertBlogImage"),
				$text
			);
		}
	}

	public function convertBlogImage($matches)
	{
		return $this->convert_blog_image($matches[1], $matches[2], $matches[3]);
	}

	public function convertBlogImageMail($matches)
	{
		return $this->convert_blog_image('', $matches[2], '', 'mail');
	}

	public static function ParserUser(&$text, &$obj)
	{
		if($obj->allow["USER"] != "N" && is_callable(array($obj, 'convert_blog_user')))
		{
			$text = preg_replace_callback(
				"/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER,
				array($obj, "convertBlogUser"),
				$text
			);
		}
	}

	public function convertBlogUser($matches)
	{
		return $this->convert_blog_user($matches[1], $matches[2]);
	}

	public static function ParserTag(&$text, &$obj)
	{
		if($obj->allow["TAG"] != "N" && is_callable(array($obj, 'convert_blog_tag')))
		{
			$text = preg_replace_callback(
				"/\[tag(?:[^\]])*\](.+?)\[\/tag\]/is".BX_UTF_PCRE_MODIFIER,
				array($obj, "convertBlogTag"),
				$text
			);
		}
	}

	public function convertBlogTag($matches)
	{
		return $this->convert_blog_tag($matches[1]);
	}

	public function convert_blog_user($userId = 0, $name = "")
	{
		$userId = IntVal($userId);
		if($userId <= 0)
		{
			return;
		}
		$anchor_id = RandString(8);
		return '<a class="blog-p-user-name'.(is_array($GLOBALS["arExtranetUserID"]) && in_array($userId, $GLOBALS["arExtranetUserID"]) ? ' feed-extranet-mention' : '').'" id="bp_'.$anchor_id.'" href="'.CComponentEngine::MakePathFromTemplate($this->pathToUser, array("user_id" => $userId)).'">'.$name.'</a>'.
			(
				!$this->bMobile
					? '<script type="text/javascript">BX.tooltip(\''.$userId.'\', "bp_'.$anchor_id.'", "'.CUtil::JSEscape($this->ajaxPage).'");</script>'
					: ''
			);
	}
	public static function convert_blog_tag($name = "")
	{
		if(strlen($name) <= 0)
			return;
		return "TAG [".$name."]";
	}

	public function convert4mail($text, $arImages = Array())
	{
		$text = Trim($text);
		if (strlen($text)<=0) return "";
		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\[(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\[\/(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\<WBR[\s\/]?\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[(\/?)(color|font|size|left|right|center)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[url\](\S+?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1)";

		$arPattern[] = "/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1)";

		$arPattern[] = "/\[img([^\]]*)\](.+?)\[\/img\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\2)";

		$arPattern[] = "/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\[(\/?)list\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";

		$arPattern[] = "/\[table\](.*?)\[\/table\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1\n";
		$arPattern[] = "/\[tr\](.*?)\[\/tr\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1\n";
		$arPattern[] = "/\[td\](.*?)\[\/td\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1\t";

		$arPattern[] = "/\[user([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2";

		$arPattern[] = "/\[DOCUMENT([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";
		

		$text = preg_replace($arPattern, $arReplace, $text);
		$text = str_replace("&shy;", "", $text);

		$serverName = ((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		if (strlen($serverName) <=0)
				$serverName = $_SERVER["SERVER_NAME"];

		$this->arImages = $arImages;
		$this->serverName = $serverName;

		$text = preg_replace_callback(
			"/\[img([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
			array($this, "convertBlogImageMail"),
			$text
		);

		return $text;
	}

	public function convert_blog_image($p1 = "", $imageId = "", $p2 = "", $type = "html")
	{
		$imageId = IntVal($imageId);
		if($imageId <= 0)
			return;

		$res = "";
		if(IntVal($this->arImages[$imageId]) > 0)
		{
			$this->showedImages[] = $imageId;
			if($f = CBlogImage::GetByID($imageId))
			{
				if(COption::GetOptionString("blog", "use_image_perm", "N") == "N")
				{
					if($db_img_arr = CFile::GetFileArray($this->arImages[$imageId]))
					{
						if(substr($db_img_arr["SRC"], 0, 1) == "/")
							$strImage = $this->serverName.$db_img_arr["SRC"];
						else
							$strImage = $db_img_arr["SRC"];
						$sourceImage = $strImage;

						$strPar = "";
						preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $width);
						preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $height);
						$width = intval($width[1]);
						$height = intval($height[1]);

						if($width <= 0)
						{
							preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $width);
							$width = intval($width[1]);
						}
						if($height <= 0)
						{
							preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $height);
							$height = intval($height[1]);
						}

						if(IntVal($width) <= 0)
							$width = $db_img_arr["WIDTH"];
						if(IntVal($height) <= 0)
							$height= $db_img_arr["HEIGHT"];

						if($width > $this->imageWidth || $height > $this->imageHeight)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$db_img_arr,
								array("width" => $this->imageWidth, "height" => $this->imageHeight),
								BX_RESIZE_IMAGE_PROPORTIONAL,
								true
							);
							if(substr($arFileTmp["src"], 0, 1) == "/")
								$strImage = $this->serverName.$arFileTmp["src"];
							else
								$strImage = $arFileTmp["src"];
							$width = $arFileTmp["width"];
							$height = $arFileTmp["height"];
						}

						$strPar = ' width="'.$width.'" height="'.$height.'"';
						$strImage = preg_replace("'(?<!:)/+'s", "/", $strImage);
						$sourceImage = preg_replace("'(?<!:)/+'s", "/", $sourceImage);

						if(strlen($this->authorName) > 0)
							$strPar .= " data-bx-title=\"".$this->authorName."\"";

						if ($this->isSonetLog)
						{
							$strImage = preg_replace("'(?<!:)/+'s", "/", $strImage);
							$res = '[IMG]'.$strImage.'[/IMG]';
						}
						else
						{

							if($type == "mail")
								$res = htmlspecialcharsbx($f["TITLE"])." (IMAGE: ".$strImage." )";
							else
								$res = '<img src="'.$strImage.'" title="" alt="'.htmlspecialcharsbx($f["TITLE"]).'" border="0"'.$strPar.' data-bx-image="'.$sourceImage.'" />';
						}
					}
				}
				else
				{
					preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $width);
					preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $height);
					$width = intval($width[1]);
					$height = intval($height[1]);

					if($width <= 0)
					{
						preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $width);
						$width = intval($width[1]);
					}
					if($height <= 0)
					{
						preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $height);
						$height = intval($height[1]);
					}

					if(IntVal($width) <= 0)
						$width = $this->imageWidth;
					if(IntVal($height) <= 0)
						$height = $this->imageHeight;

					if($width > $this->imageWidth)
						$width = $this->imageWidth;
					if($height > $this->imageHeight)
						$height = $this->imageHeight;

					$strImage = $this->serverName."/bitrix/components/bitrix/blog/show_file.php?fid=".$imageId."&width=".$width."&height=".$height;
					$sourceImage = $this->serverName."/bitrix/components/bitrix/blog/show_file.php?fid=".$imageId;

					$db_img_arr = CFile::GetFileArray($this->arImages[$imageId]);

					CFile::ScaleImage($db_img_arr["WIDTH"], $db_img_arr["HEIGHT"], Array("width" => $width, "height" => $height), BX_RESIZE_IMAGE_PROPORTIONAL, $bNeedCreatePicture, $arSourceSize, $arDestinationSize);

					if ($this->isSonetLog)
					{
						$strImage = preg_replace("'(?<!:)/+'s", "/", $strImage);
						$res = '[IMG]'.$strImage.'[/IMG]';
					}
					else
					{
						if($type == "mail")
							$res = htmlspecialcharsbx($f["TITLE"])." (IMAGE: ".$strImage." )";
						else
						{
							$strPar = ' width="'.$arDestinationSize["width"].'" height="'.$arDestinationSize["height"].'"';
							if(strlen($this->authorName) > 0)
								$strPar .= " data-bx-title=\"".$this->authorName."\"";

							$res = '<img src="'.$strImage.'" title="" alt="'.htmlspecialcharsbx($f["TITLE"]).'" border="0" data-bx-image="'.$sourceImage.'"'.$strPar.' />';
							if(!empty($this->blogImageSizeEvents))
							{
								foreach($this->blogImageSizeEvents as $arEvent)
									ExecuteModuleEventEx($arEvent, Array(&$res, $strImage, $db_img_arr, $f, $arDestinationSize));
							}
						}
					}
				}
				return $res;
			}
		}
		return $res;
	}

	public function convert_to_rss($text, $arImages = Array(), $arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N"), $bPreview = true, $arParams = Array())
	{
		$arParams["type"] = "rss";
		$text = $this->convert($text, $bPreview, $arImages, $arAllow, $arParams);
		return trim($text);
	}

	public function convert_open_tag($marker = "quote")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$this->{$marker."_open"}++;
		if ($this->type == "rss")
			return "\n====".$marker."====\n";
		return "<div class='blog-post-".$marker."' title=\"".GetMessage("BLOG_".ToUpper($marker))."\"><table class='blog".$marker."'><tr><td>";
	}

	public static function blogConvertVideo(&$arParams)
	{
		$video = "";
		$bEvents = false;
		foreach(GetModuleEvents("blog", "videoConvert", true) as $arEvent)
		{
			
			$video = ExecuteModuleEventEx($arEvent, Array(&$arParams));
			$bEvents = true;
		}

		if(!$bEvents)
		{
			ob_start();
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:player", "",
				Array(
					"PLAYER_TYPE" => "auto",
					"USE_PLAYLIST" => "N",
					"PATH" => $arParams["PATH"],
					"WIDTH" => $arParams["WIDTH"],
					"HEIGHT" => $arParams["HEIGHT"],
					"PREVIEW" => $arParams["PREVIEW"],
					"LOGO" => "",
					"FULLSCREEN" => "Y",
					"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
					"SKIN" => "bitrix.swf",
					"CONTROLBAR" => "bottom",
					"WMODE" => "transparent",
					"HIDE_MENU" => "N",
					"SHOW_CONTROLS" => "Y",
					"SHOW_STOP" => "N",
					"SHOW_DIGITS" => "Y",
					"CONTROLS_BGCOLOR" => "FFFFFF",
					"CONTROLS_COLOR" => "000000",
					"CONTROLS_OVER_COLOR" => "000000",
					"SCREEN_COLOR" => "000000",
					"AUTOSTART" => "N",
					"REPEAT" => "N",
					"VOLUME" => "90",
					"DISPLAY_CLICK" => "play",
					"MUTE" => "N",
					"HIGH_QUALITY" => "Y",
					"ADVANCED_MODE_SETTINGS" => "N",
					"BUFFER_LENGTH" => "10",
					"DOWNLOAD_LINK" => "",
					"DOWNLOAD_LINK_TARGET" => "_self"),
					null,
					array(
						"HIDE_ICONS" => "Y"
					)
				);
			$video = ob_get_contents();
			ob_end_clean();
		}
		return $video;
	}

	public static function killAllTags($text)
	{
		if (method_exists("CTextParser", "clearAllTags"))
			return CTextParser::clearAllTags($text);

		$text = strip_tags($text);
		$text = preg_replace(
			array(
				"/\<(\/)(quote|code)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/)(code|quote|video|td|tr|th|table|tbody|thead|file|document|disk)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(\*)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
				),
			" ",
			$text);
		$text = preg_replace(
			array(
				"/\<(\/?)(quote|code|font|color|video)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(b|u|i|s|list|code|quote|font|color|url|img|video|td|tr|th|tbody|thead|table|file|document|disk|user|left|right|center|justify)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER
				),
			"",
			$text);
		return $text;
	}

}
class CBlogTools
{
	public static function htmlspecialcharsExArray($array)
	{
		$res = Array();
		if(!empty($array) && is_array($array))
		{
			foreach($array as $k => $v)
			{
				if(is_array($v))
				{
					foreach($v as $k1 => $v1)
					{
						$res[$k1] = htmlspecialcharsex($v1);
						$res['~'.$k1] = $v1;
					}
				}
				else
				{
					if (preg_match("/[;&<>\"]/", $v))
						$res[$k] = htmlspecialcharsex($v);
					else
						$res[$k] = $v;
					$res['~'.$k] = $v;
				}
			}
		}
		return $res;
	}

	public static function ResizeImage($aFile, $sizeX, $sizeY)
	{
		$arFile = CFile::ResizeImageGet($aFile, array("width"=>$sizeX, "height"=>$sizeY));

		if(is_array($arFile))
			return $arFile["src"];
		else
			return false;
	}

	public static function GetDateTimeFormat()
	{
		$timestamp = mktime(7,30,45,2,22,2007);
		return array(
				"d-m-Y H:i:s" => date("d-m-Y H:i:s", $timestamp),//"22-02-2007 7:30",
				"m-d-Y H:i:s" => date("m-d-Y H:i:s", $timestamp),//"02-22-2007 7:30",
				"Y-m-d H:i:s" => date("Y-m-d H:i:s", $timestamp),//"2007-02-22 7:30",
				"d.m.Y H:i:s" => date("d.m.Y H:i:s", $timestamp),//"22.02.2007 7:30",
				"m.d.Y H:i:s" => date("m.d.Y H:i:s", $timestamp),//"02.22.2007 7:30",
				"j M Y H:i:s" => date("j M Y H:i:s", $timestamp),//"22 Feb 2007 7:30",
				"M j, Y H:i:s" => date("M j, Y H:i:s", $timestamp),//"Feb 22, 2007 7:30",
				"j F Y H:i:s" => date("j F Y H:i:s", $timestamp),//"22 February 2007 7:30",
				"F j, Y H:i:s" => date("F j, Y H:i:s", $timestamp),//"February 22, 2007",
				"d.m.y g:i A" => date("d.m.y g:i A", $timestamp),//"22.02.07 1:30 PM",
				"d.m.y G:i" => date("d.m.y G:i", $timestamp),//"22.02.07 7:30",
				"d.m.Y H:i:s" => date("d.m.Y H:i:s", $timestamp),//"22.02.2007 07:30",
			);
	}

	public static function DeleteDoubleBR($text)
	{
		if(strpos($text, "<br />\r<br />") !== false)
		{
			$text = str_replace("<br />\r<br />", "<br />", $text);
			return CBlogTools::DeleteDoubleBR($text);
		}
		if(strpos($text, "<br /><br />") !== false)
		{
			$text = str_replace("<br /><br />", "<br />", $text);
			return CBlogTools::DeleteDoubleBR($text);
		}

		if(strpos($text, "<br />") == 0 && strpos($text, "<br />") !== false)
		{
			$text = substr($text, 6);
		}
		return $text;
	}

	public static function blogUFfileEdit($arResult, $arParams)
	{
		$result = false;
		if (strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_POST_DOC') === 0 || strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_DOC') === 0)
		{
			$componentParams = array(
				'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
				'INPUT_VALUE' => $arResult["VALUE"],
				'MAX_FILE_SIZE' => intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']),
				'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
				'MODULE_ID' => 'uf',
				'ALLOW_UPLOAD' => 'A',
			);

			$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:main.file.input', 'drag_n_drop', $componentParams, false, Array("HIDE_ICONS" => "Y"));

			$result = true;
		}
		return $result;
	}

	public static function blogUFfileShow($arResult, $arParams)
	{
		$result = false;
		if ($arParams['arUserField']['FIELD_NAME'] == 'UF_BLOG_POST_DOC' || strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_DOC') === 0)
		{
			if (sizeof($arResult['VALUE']) > 0)
			{
				?>
					<div class="feed-com-files">
						<div class="feed-com-files-title"><?=GetMessage('BLOG_FILES')?></div>
						<div class="feed-com-files-cont">
				<?
			}
			foreach ($arResult['VALUE'] as $fileID)
			{
				$arFile = CFile::GetFileArray($fileID);
				if($arFile)
				{
					$name = $arFile['ORIGINAL_NAME'];
					$ext = '';
					$dotpos = strrpos($name, ".");
					if (($dotpos !== false) && ($dotpos+1 < strlen($name)))
						$ext = substr($name, $dotpos+1);
					if (strlen($ext) < 3 || strlen($ext) > 5)
						$ext = '';
					$arFile['EXTENSION'] = $ext;
					$arFile['LINK'] = "/bitrix/components/bitrix/blog/show_file.php?bp_fid=".$fileID;
					$arFile["FILE_SIZE"] = CFile::FormatSize($arFile["FILE_SIZE"]);
					?>
						<div id="wdif-doc-<?=$arFile['ID']?>" class="feed-com-file-wrap">
							<div class="feed-con-file-name-wrap">
							<div class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($arFile['EXTENSION'])?>"></div>
							<a target="_blank" href="<?=htmlspecialcharsbx($arFile['LINK'])?>" class="feed-com-file-name"><?=htmlspecialcharsbx($arFile['ORIGINAL_NAME'])?></a>
							<span class="feed-con-file-size">(<?=$arFile['FILE_SIZE']?>)</span>
							</div>
						</div>
					<?
				}
			}
			if (sizeof($arResult['VALUE']) > 0)
			{
				?>
						</div>
					</div>
				<?
			}
			$result = true;
		}
		return $result;
	}
}
?>
