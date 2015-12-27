<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
IncludeModuleLangFile(__FILE__);
function Error($error)
{
	global $MESS;
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/lang/".LANGUAGE_ID."/errors.php");
	$msg = $MESS[$error["MSG"]];
	echo "Error: ".$msg;
}


/**
 * <b>forumTextParser</b> - класс, предназначенный для форматирования сообщений форума. Этот класс - потомок класса TextParser, с расширениями для парсинга файлов и спойлеров. Осуществляет замену спецсимволов и заказных тегов на реальные HTML- теги, обработку ссылок, отображение смайлов. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/forumtextparser/index.php
 * @author Bitrix
 */
class forumTextParser extends CTextParser
{
	/* @deprecated */ var $image_params = array();
	/* @deprecated */ var $pathToUser = "";
	public $imageWidth = 300;
	public $imageHeight = 300;
	public $maxStringLen = 60;
	public $imageHtmlWidth = 0;
	public $imageHtmlHeight = 0;
	public $imageTemplate = "popup_image";
	public $component = null;
	public $smilesGallery = 0;
	public $arFilesIDParsed = array();


	public function forumTextParser($lang = false, $pathToSmiles = '', $type=false, $mode = 'full')
	{
		$this->CTextParser();
		$this->arFiles = array();
		$this->arFilesParsed = array();
		$this->serverName = (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0 ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		$this->serverName = (strlen($this->serverName) > 0 ? $this->serverName : $_SERVER["SERVER_NAME"]);

		$this->arUserfields = array();
		$this->ajaxPage = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("bxajaxid", "logout"));
		$this->userPath = "";
		$this->userNameTemplate = str_replace(array("#NOBR#","#/NOBR#"), "", CSite::GetDefaultNameFormat());
		$this->smilesGallery = \COption::GetOptionInt("forum", "smile_gallery_id", 0);

		if ($mode == 'full')
		{
			AddEventHandler("main", "TextParserBeforeTags", Array(&$this, "ParserSpoiler"));
			AddEventHandler("main", "TextParserAfterTags", Array(&$this, "ParserFile"));
			AddEventHandler("main", "TextParserAfterTags", Array(&$this, "ParserUser"));
		}
	}

	static function GetFeatures($arForum)
	{
		static $arFeatures = array("HTML", "ANCHOR", "BIU", "IMG", "VIDEO", "LIST", "QUOTE", "CODE", "FONT", "SMILES", "UPLOAD", "NL2BR", "SMILES", "TABLE", "ALIGN");
		$result = array();
		if (is_array($arForum))
		{
			foreach ($arFeatures as $feature)
			{
				$result[$feature] = ((isset($arForum['ALLOW_'.$feature]) && $arForum['ALLOW_'.$feature] == 'Y') ? 'Y' : 'N');
			}
		}
		return $result;
	}

	static function GetEditorButtons($arParams)
	{
		$result = array();
		$arEditorFeatures = array(
			"ALLOW_QUOTE" => array('Quote'),
			'ALLOW_ANCHOR' => array('CreateLink'),
			"ALLOW_VIDEO" => array('InputVideo'),
			"ALLOW_UPLOAD" => array('UploadFile'),
			"ALLOW_MENTION" => array('MentionUser')
		);
		if (isset($arParams['forum']) && is_array($arParams['forum']))
		{
			$res = array_intersect_key($arParams['forum'], $arEditorFeatures);
			foreach ($res as $featureName => $val)
			{
				if ($val != 'N')
					$result = array_merge($result, $arEditorFeatures[$featureName]);
			}
		}
		return $result;
	}

	static function GetEditorToolbar($arParams)
	{
		static $arEditorFeatures = array(
			"ALLOW_BIU" => array('Bold', 'Italic', 'Underline', 'Strike', 'Spoiler'),
			"ALLOW_FONT" => array('ForeColor','FontList', 'FontSizeList'),
			"ALLOW_QUOTE" => array('Quote'),
			"ALLOW_CODE" => array('Code'),
			'ALLOW_ANCHOR' => array('CreateLink', 'DeleteLink'),
			"ALLOW_IMG" => array('Image'),
			"ALLOW_VIDEO" => array('InputVideo'),
			"ALLOW_TABLE" => array('Table'),
			"ALLOW_ALIGN" => array('Justify'),
			"ALLOW_LIST" => array('InsertOrderedList', 'InsertUnorderedList'),
			"ALLOW_SMILES" => array('SmileList'),
			//"ALLOW_UPLOAD" => array('UploadFile'),
			//"ALLOW_NL2BR" => array(''),
		);
		$result = array();

		if (isset($arParams['mode']) && ($arParams['mode'] == 'full'))
		{
			foreach ($arEditorFeatures as $featureName => $toolbarIcons)
			{
				$result = array_merge($result, $toolbarIcons);
			}
		}
		elseif (isset($arParams['forum']))
		{
			foreach ($arEditorFeatures as $featureName => $toolbarIcons)
			{
				if (isset($arParams['forum'][$featureName]) && ($arParams['forum'][$featureName] == 'Y'))
					$result = array_merge($result, $toolbarIcons);
			}
		}

		$result = array_merge($result, array('MentionUser', 'UploadFile', 'RemoveFormat', 'Source'));
		if (LANGUAGE_ID == 'ru')
			$result[] = 'Translit';

		return $result;
	}

	public function convert($text, $allow = array(), $type = "html", $arFiles = false)
	{
		$text = str_replace(array("\013", "\014"), "", $text);

		$this->imageWidth = ($this->image_params["width"] > 0 ? $this->image_params["width"] : ($this->imageWidth > 0 ? $this->imageWidth : 300));
		$this->imageHeight = ($this->image_params["height"] > 0 ? $this->image_params["height"] : ($this->imageHeight > 0 ? $this->imageHeight : 300));
		
		$this->userPath = (empty($this->userPath) && !empty($this->pathToUser) ? $this->pathToUser : $this->userPath);

		$this->type = $type;
		$allow = (is_array($allow) ? $allow : array());
		if (!empty($this->arUserfields))
			$allow["USERFIELDS"] = $this->arUserfields;
		if (sizeof($allow)>0)
		{
			if (!isset($allow['TABLE']))
				$allow['TABLE']=$allow['BIU'];

			$this->allow = array_merge((is_array($this->allow) ? $this->allow : array()), $allow);
		}
		$this->parser_nofollow = COption::GetOptionString("forum", "parser_nofollow", "Y");
		$this->link_target = COption::GetOptionString("forum", "parser_link_target", "_blank");

		if ($arFiles !== false)
			$this->arFiles = is_array($arFiles) ? $arFiles : array($arFiles);
		$this->arFilesIDParsed = array();

		$text = str_replace(array("\013", "\014"), array(chr(34), chr(39)), $this->convertText($text));
		return $text;
	}
	public function convert4mail($text, $arFiles = false)
	{
		$text = CTextParser::convert4mail($text);

		if ($arFiles !== false)
			$this->arFiles = is_array($arFiles) ? $arFiles : array($arFiles);
		$this->arFilesIDParsed = array();
		if (!empty($this->arFiles))
			$this->ParserFile($text, $this, "mail");
		if (preg_match("/\\[cut(([^\\]])*)\\]/is".BX_UTF_PCRE_MODIFIER, $text, $matches))
		{
			$text = preg_replace(
				array("/\\[cut(([^\\]])*)\\]/is".BX_UTF_PCRE_MODIFIER,
					"/\\[\\/cut\\]/is".BX_UTF_PCRE_MODIFIER),
				array("\001\\1\002",
					"\003"),
				$text);
			while (preg_match("/(\001([^\002]*)\002([^\001\002\003]+)\003)/is".BX_UTF_PCRE_MODIFIER, $text, $arMatches))
				$text = preg_replace(
					"/(\001([^\002]*)\002([^\001\002\003]+)\003)/is".BX_UTF_PCRE_MODIFIER,
					"\n>================== CUT ===================\n\\3\n>==========================================\n",
					$text);
			$text = preg_replace(
				array("/\001([^\002]+)\002/",
					"/\001\002/",
					"/\003/"),
				array("[cut\\1]",
					"[cut]",
					"[/cut]"),
				$text);
		}
		return $text;
	}
	public static function ParserSpoiler(&$text, &$obj)
	{
		$matches = array();
		if (method_exists($obj, "convert_spoiler_tag") && preg_match("/\[(cut|spoiler)/is".BX_UTF_PCRE_MODIFIER, $text, $matches))
		{
			$text = preg_replace(
				array(
					"/\[(cut|spoiler)(([^\]])*)\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/(cut|spoiler)\]/is".BX_UTF_PCRE_MODIFIER
				),
				array(
					"\001\\2\002",
					"\003"),
				$text);
			$arMatches = array();
			while (preg_match("/(\001([^\002]*)\002([^\001\002\003]+)\003)/is".BX_UTF_PCRE_MODIFIER, $text, $arMatches))
				$text = preg_replace_callback("/\001([^\002]*)\002([^\001\002\003]+)\003/is".BX_UTF_PCRE_MODIFIER, array($this, "convert_spoiler_tag"), $text);
			$text = preg_replace(
				array("/\001([^\002]+)\002/",
					"/\001\002/",
					"/\003/"),
				array("[spoiler\\1]",
					"[spoiler]",
					"[/spoiler]"),
				$text);
		}
	}

	public static function ParserFile(&$text, &$obj, $type="html")
	{
		if (method_exists($obj, "convert_attachment"))
		{
			$tmpType = $obj->type;
			$obj->type = $type;
			$text = preg_replace_callback("/\[file([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER, array($this, "convert_attachment"), $text);
			$obj->type = $tmpType;
		}
	}

	public static function ParserUser(&$text, &$obj)
	{
		if($obj->allow["USER"] != "N" && is_callable(array($obj, 'convert_user')))
		{
			$text = preg_replace_callback("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, array($obj, "convert_user"), $text);
		}
	}

	public function convert_user($userId = 0, $name = "")
	{
		if (is_array($userId))
		{
			$name = $userId[2];
			$userId = $userId[1];
		}
		$userId = intval($userId);
		if($userId > 0)
		{
			$anchor_id = RandString(8);
			return
				'<a class="blog-p-user-name'.(is_array($GLOBALS["arExtranetUserID"]) && in_array($userId, $GLOBALS["arExtranetUserID"]) ? ' feed-extranet-mention' : '').'" id="bp_'.$anchor_id.'" href="'.CComponentEngine::MakePathFromTemplate($this->userPath,
					array(
						"user_id" => $userId,
						"USER_ID" => $userId,
						"uid" => $userId,
						"UID" => $userId)).'">'.$name.'</a>'.
				(
					!$this->bMobile
						? '<script type="text/javascript">if(!!BX[\'tooltip\']){BX.tooltip(\''.$userId.'\', "bp_'.$anchor_id.'", "'.CUtil::JSEscape($this->ajaxPage).'");}</script>' 
						: ''
				);
		}
		return "";
	}

	public static function convert_spoiler_tag($text, $title="")
	{
		if (is_array($text))
		{
			$title = $text[1];
			$text = $text[2];
		}
		if (empty($text))
			return "";
		$title = htmlspecialcharsbx(trim(htmlspecialcharsback($title), " =\"\'"));
		$result = $GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:forum.interface",
			"spoiler",
			Array(
				"TITLE" => $title,
				"TEXT" => $text,
				"RETURN" => "Y"
			),
			null,
			array("HIDE_ICONS" => "Y"));
		return str_replace(array(chr(34), chr(39)), array("\013", "\014"), $result);
	}

	public function convert_open_tag($marker = "quote")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");

		$this->{$marker."_open"}++;
		if ($this->type == "rss")
			return "\n====".$marker."====\n";

		if ($this->bMobile)
		{
			return "<div class='blog-post-".$marker."' title=\"".($marker == "quote" ? GetMessage("FRM_QUOTE") : GetMessage("FRM_CODE"))."\"><table class='blog".$marker."'><tr><td>";
		}
		else
		{
			return '<table class="forum-'.$marker.'"><thead><tr><th>'.($marker == "quote" ? GetMessage("FRM_QUOTE") : GetMessage("FRM_CODE")).'</th></tr></thead><tbody><tr><td>';
		}
	}

	public function convert_close_tag($marker = "quote")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");

		if ($this->{$marker."_open"} == 0)
		{
			$this->{$marker."_error"}++;
			return "";
		}
		$this->{$marker."_closed"}++;

		if ($this->type == "rss")
			return "\n=============\n";

		if ($this->bMobile)
		{
			return "</td></tr></table></div>";
		}
		else
		{
			return "</td></tr></tbody></table>";
		}

	}

	public function convert_image_tag($url = "", $params="")
	{
		$url = trim($url);
		if (empty($url)) return "";
		$type = (strtolower($this->type) == "rss" ? "rss" : "html");

		$bErrorIMG = !preg_match("/^(http|https|ftp|\/)/i".BX_UTF_PCRE_MODIFIER, $url);

		$url = str_replace(array("<", ">", "\""), array("%3C", "%3E", "%22"), $url);
		// to secure from XSS [img]http://ya.ru/[url]http://onmouseover=prompt(/XSS/)//[/url].jpg[/img]

		if ($bErrorIMG)
			return "[img]".$url."[/img]";

		if ($type != "html")
			return '<img src="'.$url.'" alt="'.GetMessage("FRM_IMAGE_ALT").'" border="0" />';

		$width = 0; $height = 0;
		if (preg_match_all("/width\=(?P<width>\d+)|height\=(?P<height>\d+)/is".BX_UTF_PCRE_MODIFIER, $params, $matches)):
			$width = intval(!empty($matches["width"][0]) ? $matches["width"][0] : $matches["width"][1]);
			$height = intval(!empty($matches["height"][0]) ? $matches["height"][0] : $matches["height"][1]);
		endif;
		$result = $GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:forum.interface",
			$this->imageTemplate,
			Array(
				"URL" => $url,
				"SIZE" => array("width" => $width, "height" => $height),
				"MAX_SIZE" => array("width" => $this->imageWidth, "height" => $this->imageHeight),
				"HTML_SIZE"=> array("width" => $this->imageHtmlWidth, "height" => $this->imageHtmlHeight),
				"CONVERT" => "N",
				"FAMILY" => "FORUM",
				"RETURN" => "Y"
			),
			$this->component,
			array("HIDE_ICONS" => "Y"));
		return $this->defended_tags($result, 'replace');
	}

	public function convert_attachment($fileID = "", $p = "", $type = "", $text = "")
	{
		if (is_array($fileID))
		{
			$text = $fileID[0];
			$p = $fileID[3];
			$fileID = $fileID[2];
		}

		$fileID = intval($fileID);
		$type = strtolower(empty($type) ? $this->type : $type);
		$type = (in_array($type, array("html", "mail", "bbcode", "rss")) ? $type : "html");

		$this->arFiles = (is_array($this->arFiles) ? $this->arFiles : array($this->arFiles));
		if ($fileID <= 0 || (!array_key_exists($fileID, $this->arFiles) && !in_array($fileID, $this->arFiles)))
			return $text;

		if (!array_key_exists($fileID, $this->arFiles) && in_array($fileID, $this->arFiles)): // array(fileID10, fileID12, fileID14)
			unset($this->arFiles[array_search($fileID, $this->arFiles)]);
			$this->arFiles[$fileID] = $fileID; // array(fileID10 => fileID10, fileID12 => fileID12, fileID14 => fileID14)
		endif;

		if (!is_array($this->arFiles[$fileID]))
			$this->arFiles[$fileID] = CFile::GetFileArray($fileID); // array(fileID10 => array about file, ....)

		if (!is_array($this->arFiles[$fileID])): // if file does not exist
			unset($this->arFiles[$fileID]);
			return $text;
		endif;

		if (!array_key_exists($fileID, $this->arFilesParsed) || empty($this->arFilesParsed[$fileID][$type]))
		{
			$arFile = $this->arFiles[$fileID];
			if ($type == "html" || $type == "rss")
			{
				$width = 0; $height = 0;
				if (preg_match_all("/width\=(?P<width>\d+)|height\=(?P<height>\d+)/is".BX_UTF_PCRE_MODIFIER, $p, $matches)):
					$width = intval(!empty($matches["width"][0]) ? $matches["width"][0] : $matches["width"][1]);
					$height = intval(!empty($matches["height"][0]) ? $matches["height"][0] : $matches["height"][1]);
				endif;
				$arFile[$type] = $GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:forum.interface",
						"show_file",
						Array(
							"FILE" => $arFile,
							"SHOW_MODE" => ($type == "html" ? "THUMB" : "RSS"),
							"SIZE" => array("width" => $width, "height" => $height),
							"MAX_SIZE" => array("width" => $this->imageWidth, "height" => $this->imageHeight),
							"HTML_SIZE"=> array("width" => $this->imageHtmlWidth, "height" => $this->imageHtmlHeight),
							"CONVERT" => "N",
							"NAME_TEMPLATE" => $this->userNameTemplate,
							"FAMILY" => "FORUM",
							"SINGLE" => "Y",
							"RETURN" => "Y"),
						$this->component,
						array("HIDE_ICONS" => "Y"));
			}
			else
			{
				$path = '/bitrix/components/bitrix/forum.interface/show_file.php?fid='.$arFile["ID"];
				$bIsImage = (CFile::CheckImageFile(CFile::MakeFileArray($fileID)) === null);
//				$path = ($bIsImage && !empty($arFile["SRC"]) ? $arFile["SRC"] : !$bIsImage && !empty($arFile["URL"]) ? $arFile["URL"] : $path);
				$path = preg_replace("'(?<!:)/+'s", "/", (substr($path, 0, 1) == "/" ? CHTTP::URN2URI($path, $this->serverName) : $path));
				switch ($type)
				{
					case "bbcode":
							$arFile["bbcode"] = ($bIsImage ? '[IMG]'.$path.'[/IMG]' : '[URL='.$path.']'.$arFile["ORIGINAL_NAME"].'[/URL]');
						break;
					case "mail":
							$arFile["mail"] = $arFile["ORIGINAL_NAME"].($bIsImage ? " (IMAGE: ".$path.")" : " (URL: ".$path.")");
						break;
				}
			}
			$this->arFilesParsed[$fileID] = $arFile;
		}
		$this->arFilesIDParsed[] = $fileID;
		return $this->arFilesParsed[$fileID][$type];
	}

	public function convert_to_rss(
		$text,
		$arImages = Array(),
		$arAllow = Array(
			"HTML" => "N",
			"ANCHOR" => "Y",
			"BIU" => "Y",
			"IMG" => "Y",
			"QUOTE" => "Y",
			"CODE" => "Y",
			"FONT" => "Y",
			"LIST" => "Y",
			"SMILES" => "Y",
			"NL2BR" => "N",
			"TABLE" => "Y"))
	{
		if (empty($arAllow))
			$arAllow = array(
				"HTML" => "N",
				"ANCHOR" => "Y",
				"BIU" => "Y",
				"IMG" => "Y",
				"QUOTE" => "Y",
				"CODE" => "Y",
				"FONT" => "Y",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "N",
				"TABLE" => "Y"
			);
		$text = preg_replace(
			array(
				"#^(.+?)<cut[\s]*(/>|>).*?$#is".BX_UTF_PCRE_MODIFIER,
				"#^(.+?)\[cut[\s]*(/\]|\]).*?$#is".BX_UTF_PCRE_MODIFIER),
			"\\1", $text);

		return $this->convert($text, $arAllow, "rss", $arImages);
	}
}

//===========================
/**
 * @deprecated Use forumTextParser
 */
class textParser
{
	var $smiles = array();
	var $preg_smiles = array();
	var $allow_img_ext = "gif|jpg|jpeg|png";
	var $image_params = array(
		"width" => 300,
		"height" => 300,
		"template" => "popup_image");
	var $LAST_ERROR  = "";
	var $path_to_smile  = false;
	var $quote_error = 0;
	var $quote_open = 0;
	var $quote_closed = 0;
	var $MaxStringLen = 125;
	var $code_error = 0;
	var $code_open = 0;
	var $code_closed = 0;
	var $CacheTime = false;
	var $arFontSize = array(
		0 => 40, //"xx-small"
		1 => 60, //"x-small"
		2 => 80, //"small"
		3 => 100, //"medium"
		4 => 120, //"large"
		5 => 140, //"x-large"
		7 => 160); //"xx-large"
	var $word_separator = "\s.,;:!?\#\-\*\|\[\]\(\)\{\}";
	var $preg = array("counter" => 0, "pattern" => array(), "replace" => array());

	public function textParser($strLang = False, $pathToSmile = false)
	{
		global $DB;
		static $arSmiles = array();

		$strLang = ($strLang === false ? LANGUAGE_ID : $strLang);
		$pathToSmile = ($pathToSmile === false ? "/bitrix/images/forum/smile/" : $pathToSmile);
		$id = md5($pathToSmile."|".$pathToSmile);

		if (!is_set($arSmiles, $id))
		{
			$arCollection = $arPattern = $arReplacement = array();
			$db_res = CForumSmile::GetByType("S", $strLang);
			foreach ($db_res as $key => $val)
			{
				$tok = strtok($val["TYPING"], " ");
				while ($tok)
				{
					$row = array(
						"TYPING" => $tok,
						"IMAGE"  => stripslashes($val["IMAGE"]),
						"DESCRIPTION" => stripslashes($val["NAME"]));

					$tok = str_replace(array(chr(34), chr(39), "<", ">"), array("\013", "\014", "&lt;", "&gt;"), $tok);
					$code = preg_quote(str_replace(array("\x5C"), array("&#092;"), $tok));
					$patt = preg_quote($tok, "/");

					$image = preg_quote($row["IMAGE"]);
					$description = preg_quote(htmlspecialcharsbx($row["DESCRIPTION"], ENT_QUOTES), "/");

					$arReplacement[] = "\$this->convert_emoticon('$code', '$image', '$description')";
					$arPattern[] = "/(?<=[^\w&])$patt(?=.\W|\W.|\W$)/ei".BX_UTF_PCRE_MODIFIER;

					$arCollection[] = $row;
					$tok = strtok(" ");
				}
			}
			$arSmiles[$id] = array(
				"smiles" => $arCollection,
				"pattern" => $arPattern,
				"replace" => $arReplacement);
		}
		$this->smiles = $arSmiles[$id]["smiles"];
		$this->preg_smiles = array(
			"pattern" => $arSmiles[$id]["pattern"],
			"replace" => $arSmiles[$id]["replace"]);
		$this->path_to_smile = "";
	}

	public function convert($text, $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y"), $type = "html")
	{
		global $DB;

		$text = preg_replace("#([?&;])PHPSESSID=([0-9a-zA-Z]{32})#is", "\\1PHPSESSID1=", $text);
		$type = ($type == "rss" ? "rss" : "html");

		$this->quote_error = 0;
		$this->quote_open = 0;
		$this->quote_closed = 0;
		$this->code_error = 0;
		$this->code_open = 0;
		$this->code_closed = 0;
		$this->preg = array("counter" => 0, "pattern" => array(), "replace" => array());
		$allow = array(
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
			"SMILES" => ($allow["SMILES"] == "N" ? "N" : "Y"));

		$text = str_replace(
			array("\001", "\002", "\003", "\004", "\005", "\013", "\014", chr(34), chr(39)),
			array("", "", "", "", "", "", "", "\013", "\014"), $text);

		if ($allow["HTML"] != "Y")
		{
			if ($allow["CODE"]=="Y")
			{
				$text = preg_replace(
					array(
					"#<code(\s+[^>]*>|>)(.+?)</code(\s+[^>]*>|>)#is".BX_UTF_PCRE_MODIFIER,
					"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
					"/(?<=[\001])(([^\002]+))(?=([\002]))/ise".BX_UTF_PCRE_MODIFIER,
					"/\001/",
					"/\002/"),
					array(
					"[code]\\2[/code]",
					"\001",
					"\002",
					"\$this->pre_convert_code_tag('\\2')",
					"[code]",
					"[/code]"), $text);
			}
			if ($allow["ANCHOR"]=="Y")
			{
				$text = preg_replace(
					array(
						"#<a[^>]+href\s*=\s*[\013]+(([^\013])+)[\013]+[^>]*>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*[\014]+(([^\014])+)[\014]+[^>]*>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*(([^\014\013\>])+)>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
					"[url=\\1]\\3[/url]", $text);
			}
			if ($allow["IMG"]=="Y")
			{
				$text = preg_replace(
					"#<img[^>]+src\s*=[\s\013\014]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\/[-_/=:.a-z0-9@{}&?\s%]+)+)[\s\013\014]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
					"[img]\\1[/img]", $text);
			}
			if ($allow["QUOTE"]=="Y")
			{
				//$text = preg_replace("#(<quote(.*?)>(.*)</quote(.*?)>)#is", "[quote]\\3[/quote]", $text);
				$text = preg_replace("#<(/?)quote(.*?)>#is", "[\\1quote]", $text);
			}
			if ($allow["FONT"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<font[^>]+size\s*=[\s\013\014]*([0-9]+)[\s\013\014]*[^>]*\>(.+?)\<\/font[^>]*\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+color\s*=[\s\013\014]*(\#[a-f0-9]{6})[^>]*\>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+face\s*=[\s\013\014]*([a-z\s\-]+)[\s\013\014]*[^>]*>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[size=\\1]\\2[/size]",
						"[color=\\1]\\2[/color]",
						"[font=\\1]\\2[/font]"),
					$text);
			}
			if ($allow["LIST"]=="Y")
			{
				$text = preg_replace(
					array("/\001/is", "/\002/is",
						"/\<ul((\s[^>]*)|(\s*))\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<\/ul([^>]*)\>/is".BX_UTF_PCRE_MODIFIER, ),
					array("", "",
						"\001",
						"\002"),
					$text);
				while (preg_match("/\001([^\001\002]*)\002/ise".BX_UTF_PCRE_MODIFIER, $text))
					$text = preg_replace("/\001([^\001\002]*)\002/ise".BX_UTF_PCRE_MODIFIER, "\$this->pre_convert_list('[list]\\1[/list]')", $text);
				$text = preg_replace(
					array("/\001/is", "/\002/is"),
					array("<ul>", "</ul>"),
					$text);
			}
			if ($allow["BIU"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<b([^\>]*)\>(.+?)\<\/b([^\>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<u([^\>]*)\>(.+?)\<\/u([^\>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<s([^\>]*)\>(.+?)\<\/s([^\>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<i([^\>]*)\>(.+?)\<\/i([^\>]*)>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[b]\\2[/b]",
						"[u]\\2[/u]",
						"[s]\\2[/s]",
						"[i]\\2[/i]"),
					$text);
			}

			if (preg_match("/\<cut/is".BX_UTF_PCRE_MODIFIER, $text, $matches))
			{
				$text = preg_replace(
						"/\<cut([^>]*)\>(.+?)\<\/cut>/is".BX_UTF_PCRE_MODIFIER,
						"[cut=\\1]\\2[/cut]",
					$text);
			}
			if (strLen($text)>0)
			{
				$text = str_replace(
					array("<", ">", chr(34)),
					array("&lt;", "&gt;", "&quot;"),
					$text);
			}
		}
		elseif ($allow["NL2BR"]=="Y")
		{
			$text = str_replace("\n", "<br />", $text);
		}

		if ($allow["ANCHOR"]=="Y")
		{
			$word_separator = str_replace("\]", "", $this->word_separator);
			$text = preg_replace("'(?<=^|[".$word_separator."]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\013\s\'\014\{\}\[])*)'is",
				"[url]\\1[/url]", $text);
		}

		foreach ($allow as $tag => $val)
		{
			if ($val != "Y"):
				continue;
			endif;

			if (strpos($text, "<nomodify>") !== false):
				$text = preg_replace(
					array(
						"/\001/", "/\002/",
						"/\<nomodify\>/is".BX_UTF_PCRE_MODIFIER, "/\<\/nomodify\>/is".BX_UTF_PCRE_MODIFIER,
						"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
						"/\001/", "/\002/"
						),
					array(
						"", "",
						"\001", "\002",
						"\$this->defended_tags('\\2', 'replace')",
						"<nomodify>", "</nomodify>"),
					$text);
			endif;

			switch ($tag)
			{
				case "CODE":
					$text = preg_replace(
								array(	"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
										"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
										"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
										"/\001/",
										"/\002/"),
								array(	"\001",
										"\002",
										"\$this->convert_code_tag('\\2', \$type)",
										"[code]",
										"[/code]"),
								$text);
					break;
				case "VIDEO":
					$text = preg_replace("/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_video('\\1', '\\2')", $text);
					break;
				case "QUOTE":
					$text = preg_replace("#(\[quote([^\]\<\>])*\](.*)\[/quote([^\]\<\>])*\])#ies", "\$this->convert_quote_tag('\\1', \$type)", $text);
					break;
				case "IMG":
					$text = preg_replace("#\[img\](.+?)\[/img\]#ie", "\$this->convert_image_tag('\\1', \$type)", $text);
					break;
				case "ANCHOR":
					$text = preg_replace(
								array(
										"/\[url\]( (?: [^\[\]]*? (?: \[ [^\]]+? \] )* [^\[\]]*? )*? )\[\/url\]/ixe".BX_UTF_PCRE_MODIFIER,
										"/\[url\s*=\s*( (?: [^\[\]]*? (?: \[ [^\]]+? \] )* [^\[\]]*? )* )\s*\](.*?)\[\/url\]/ixes".BX_UTF_PCRE_MODIFIER
										//              ^---------------------------------------------^ - allow not nested [] in url
								),
								array(	"\$this->convert_anchor_tag('\\1', '\\1', '' , \$type)",
										"\$this->convert_anchor_tag('\\1', '\\2', '', \$type)"
								),
								$text);
					break;
				case "BIU":
					$text = preg_replace(
								array(
									"/\[b\](.*?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[i\](.*?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[s\](.*?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[u\](.*?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER),
								array(
									"<b>\\1</b>",
									"<i>\\1</i>",
									"<s>\\1</s>",
									"<u>\\1</u>"),
								$text);
					break;
				case "LIST":
					while (preg_match("/\[list\](.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace(
								array(
									"/\[list\](.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[\*\]/".BX_UTF_PCRE_MODIFIER),
								array(
									"<ul>\\1</ul>",
									"<li>"),
								$text);
					break;
				case "FONT":
					while (preg_match("/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('size', '\\1', '\\2')", $text);
					while (preg_match("/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('font', '\\1', '\\2')", $text);
					while (preg_match("/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('color', '\\1', '\\2')", $text);
					break;
			}
		}

		if (preg_match("/\[cut/is".BX_UTF_PCRE_MODIFIER, $text, $matches))
		{
			$text = preg_replace(
				array("/\[cut(([^\]])*)\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/cut\]/is".BX_UTF_PCRE_MODIFIER),
				array("\001\\1\002",
					"\003"),
				$text);
			while (preg_match("/(\001([^\002]*)\002([^\001\002\003]+)\003)/ies".BX_UTF_PCRE_MODIFIER, $text, $arMatches))
				$text = preg_replace("/(\001([^\002]*)\002([^\001\002\003]+)\003)/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_cut_tag('\\3', '\\2')", $text);
			$text = preg_replace(
				array("/\001([^\002]+)\002/",
					"/\001\002/",
					"/\003/"),
				array("[cut\\1]",
					"[cut]",
					"[/cut]"),
				$text);
		}

		$text = str_replace(
			array(
				"\n",
				"(c)", "(C)",
				"(tm)", "(TM)", "(Tm)", "(tM)",
				"(r)", "(R)"),
			array(
				"<br />",
				"&#169;", "&#169;",
				"&#153;", "&#153;", "&#153;", "&#153;",
				"&#174;", "&#174;"),
			$text);
		$text = preg_replace("/\n/", "<br />", $text);

		if ($this->MaxStringLen > 0)
		{
			$text = preg_replace(
				array(
					"/(\&\#\d{1,3}\;)/is".BX_UTF_PCRE_MODIFIER,
					"/(?<=^|\>)([^\<]+)(?=\<|$)/ies".BX_UTF_PCRE_MODIFIER,
					"/(\<\019((\&\#\d{1,3}\;))\>)/is".BX_UTF_PCRE_MODIFIER,
					"/[\\".chr(34)."]/",
					"/[\\".chr(39)."]/"),
				array(
					"<\019\\1>",
					"\$this->part_long_words('\\1')",
					"\\2",
					"\013",
					"\014"),
				$text);
		}

		if (strpos($text, "<nosmile>") !== false):
			$text = preg_replace(
				array(
					"/\001/", "/\002/",
					"/\<nosmile\>/is".BX_UTF_PCRE_MODIFIER, "/\<\/nosmile\>/is".BX_UTF_PCRE_MODIFIER,
					"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
					"/\001/is", "/\002/is"
					),
				array(
					"", "",
					"\001", "\002",
					"\$this->defended_tags('\\2', 'replace')",
					"<nosmile>", "</nosmile>"),
				$text);
		endif;

		if ($allow["SMILES"]=="Y" && !empty($this->preg_smiles["pattern"]))
			$text = preg_replace($this->preg_smiles["pattern"], $this->preg_smiles["replace"], ' '.$text.' ');

		if ($this->preg["counter"] > 0)
			$text = preg_replace($this->preg["pattern"], $this->preg["replace"], $text);

		$text = str_replace(array("\013", "\014"), array(chr(34), chr(39)), $text);

		return trim($text);
	}

	public function defended_tags($text, $tag = 'replace')
	{
		switch ($tag)
		{
			case "replace":
				$this->preg["pattern"][] = "/\<\017\#".$this->preg["counter"]."\>/is".BX_UTF_PCRE_MODIFIER;
				$this->preg["replace"][] = str_replace("$", "\\$", $text);
				$text = "<\017#".$this->preg["counter"].">";
				$this->preg["counter"]++;
				break;
		}
		return $text;
	}

	public static function killAllTags($text)
	{
		if (method_exists("CTextParser", "clearAllTags"))
			return CTextParser::clearAllTags($text);
		$text = strip_tags($text);
		$text = preg_replace(
			array(
				"/\<(\/?)(quote|code|font|color|video|disk)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(b|u|i|s|list|code|quote|font|color|url|img|video|disk)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER),
			"",
			$text);
		return $text;
	}

	public static function convert4mail($text)
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

		$arPattern[] = "/\[(\/?)(color|font|size)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[url\](\S+?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1)";

		$arPattern[] = "/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1)";

		$arPattern[] = "/\[img[^]]*\](.+?)\[\/img\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\[(\/?)list[^\]]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";

		$arPattern[] = "/\\[user([^\\]]*)\\](.+?)\\[\\/user\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2";

		$arPattern[] = "/\\[DOCUMENT([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[\*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "- ";

		$arPattern[] = "/\[(\/?)(left|center|right|justify)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		// table

		$arPattern[] = "/\[(table)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\[\/table(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\[tr\]\s+/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[(\/?)(tr|td)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";


		$text = preg_replace($arPattern, $arReplace, $text);
		$text = str_replace("&shy;", "", $text);
		if (preg_match("/\[cut(([^\]])*)\]/is".BX_UTF_PCRE_MODIFIER, $text, $matches))
		{
			$text = preg_replace(
				array("/\[cut(([^\]])*)\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/cut\]/is".BX_UTF_PCRE_MODIFIER),
				array("\001\\1\002",
					"\003"),
				$text);
			while (preg_match("/(\001([^\002]*)\002([^\001\002\003]+)\003)/is".BX_UTF_PCRE_MODIFIER, $text, $arMatches))
				$text = preg_replace(
					"/(\001([^\002]*)\002([^\001\002\003]+)\003)/is".BX_UTF_PCRE_MODIFIER,
					"\n>================== CUT ===================\n\\3\n>==========================================\n",
					$text);
			$text = preg_replace(
				array("/\001([^\002]+)\002/",
					"/\001\002/",
					"/\003/"),
				array("[cut\\1]",
					"[cut]",
					"[/cut]"),
				$text);
		}
		$text = str_replace("&nbsp;", " ", $text);
		$text = str_replace("&quot;", "\"", $text);
		$text = str_replace("&#092;", "\\", $text);
		$text = str_replace("&#036;", "\$", $text);
		$text = str_replace("&#33;", "!", $text);
		$text = str_replace("&#91;", "[", $text);
		$text = str_replace("&#93;", "]", $text);
		$text = str_replace("&#39;", "'", $text);
		$text = str_replace("&lt;", "<", $text);
		$text = str_replace("&gt;", ">", $text);
		$text = str_replace("&nbsp;", " ", $text);
		$text = str_replace("&#124;", '|', $text);
		$text = str_replace("&amp;", "&", $text);

		return $text;
	}

	public static function convert_video($params, $path)
	{
		if (strLen($path) <= 0)
			return "";
		$width = ""; $height = ""; $preview = "";
		preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);

		preg_match("/preview\=\013([^\013]+)\013/is".BX_UTF_PCRE_MODIFIER, $params, $preview);
		if (empty($preview))
			preg_match("/preview\=\014([^\014]+)\014/is".BX_UTF_PCRE_MODIFIER, $params, $preview);
		$width = intval($width[1]);
		$width = ($width > 0 ? $width : 400);
		$height = intval($height[1]);
		$height = ($height > 0 ? $height : 300);
		$preview = trim($preview[1]);
		$preview = (strLen($preview) > 0 ? $preview : "");

		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:player", "",
			Array(
				"PLAYER_TYPE" => "auto",
				"USE_PLAYLIST" => "N",
				"PATH" => $path,
				"WIDTH" => $width,
				"HEIGHT" => $height,
				"PREVIEW" => $preview,
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
				"DOWNLOAD_LINK_TARGET" => "_self"));
		$video = ob_get_contents();
		ob_end_clean();
		return "<nomodify>".str_replace(array(chr(34), chr(39)), array("\013", "\014"), $video)."</nomodify>";
	}

	public static function convert_emoticon($code = "", $image = "", $description = "", $servername = "")
	{
		if (strlen($code)<=0 || strlen($image)<=0) return;
		$code = stripslashes($code);
		$description = stripslashes($description);
		$image = stripslashes($image);
		return '<img src="'.$servername.$image.'" border="0" alt="smile'.$code.'" title="'.$description.'" />';
	}

	public static function pre_convert_code_tag ($text = "")
	{
		if (strLen($text)<=0) return;
		$text = str_replace(
			array("&", "://", "<", ">", "[", "]", "\001", "\002"),
			array("&#38;", "&#58;&#47;&#47;", "&#60;", "&#62;", "&#91;", "&#93;", "&#91;code&#93;", "&#91;/code&#93;"), $text);
		return $text;
	}

	public static function pre_convert_list ($text = "")
	{
		return preg_replace(
			array("/\<li((\s[^>]*)|(\s*))\>/is".BX_UTF_PCRE_MODIFIER, "/\<\/(\s*)li(\s*)\>/is".BX_UTF_PCRE_MODIFIER),
			array("[*]", ""),
			$text);
	}

	public function convert_code_tag($text = "", $type = "html")
	{
		if (strLen($text)<=0) return;
		$type = ($type == "rss" ? "rss" : "html");
		$text = str_replace(
			array("<", ">", "\\r", "\\n", "\\", "[", "]", "\001", "\002", "  ", "\t"),
			array("&#60;", "&#62;", "&#92;r", "&#92;n", "&#92;", "&#91;", "&#93;", "&#91;code&#93;", "&#91;/code&#93;", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"), $text);
		$text = stripslashes($text);
//		$text = str_replace(array("  ", "\t", ), array("&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"), $text);
		if ($this->code_open == $this->code_closed && $this->code_error == 0):
			$text = "<nosmile>".str_replace(array(chr(34), chr(39)), array("\013", "\014"), $this->convert_open_tag('code', $type).$text.$this->convert_close_tag('code', $type))."</nosmile>";
		endif;
		return $text;
	}

	public function convert_quote_tag($text = "", $type = "html")
	{
		if (strlen($text)<=0) return;
		$txt = $text;
		$type = ($type == "rss" ? "rss" : "html");

		$txt = preg_replace(
			array(
				"/\[quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER,
				"/\[\/quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER),
			array(
				"\$this->convert_open_tag('quote', \$type)",
				"\$this->convert_close_tag('quote', \$type)"), $txt);

		if (($this->quote_open==$this->quote_closed) && ($this->quote_error==0))
			return str_replace(array(chr(34), chr(39)), array("\013", "\014"), $txt);
		return $text;
	}

	public function convert_open_tag($marker = "quote", $type = "html")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$type = ($type == "rss" ? "rss" : "html");

		$this->{$marker."_open"}++;
		if ($type == "rss")
			return "\n====".$marker."====\n";
		return '<table class="forum-'.$marker.'"><thead><tr><th>'.($marker == "quote" ? GetMessage("FRM_QUOTE") : GetMessage("FRM_CODE")).'</th></tr></thead><tbody><tr><td>';
	}

	public function convert_close_tag($marker = "quote", $type = "html")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$type = ($type == "rss" ? "rss" : "html");

		if ($this->{$marker."_open"} == 0)
		{
			$this->{$marker."_error"}++;
			return;
		}
		$this->{$marker."_closed"}++;
		if ($type == "rss")
			return "\n=============\n";
		return '</td></tr></tbody></table>';
	}

	public function convert_image_tag($url = "", $type = "html")
	{
		static $bShowedScript = false;
		if (strlen($url)<=0) return;
		$url = trim($url);
		$type = (strToLower($type) == "rss" ? "rss" : "html");
		$extension = preg_replace("/^.*\.(\S+)$/".BX_UTF_PCRE_MODIFIER, "\\1", $url);
		$extension = strtolower($extension);
		$extension = preg_quote($extension, "/");

		$bErrorIMG = False;
		if (strpos($url, "/bitrix/components/bitrix/forum.interface/show_file.php?fid=") === false)
		{
			if (preg_match("/[?&;]/".BX_UTF_PCRE_MODIFIER, $url))
				$bErrorIMG = True;
			if (!$bErrorIMG && !preg_match("/$extension(\||\$)/".BX_UTF_PCRE_MODIFIER, $this->allow_img_ext))
				$bErrorIMG = True;
			if (!$bErrorIMG && !preg_match("/^(http|https|ftp|\/)/i".BX_UTF_PCRE_MODIFIER, $url))
				$bErrorIMG = True;
		}
		if ($bErrorIMG)
			return "[img]".$url."[/img]";
		if ($type != "html")
			return '<img src="'.$url.'" alt="'.GetMessage("FRM_IMAGE_ALT").'" border="0" />';

		$result = $GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:forum.interface",
			$this->image_params["template"],
			Array(
				"URL" => $url,

				"WIDTH"=> $this->image_params["width"],
				"HEIGHT"=> $this->image_params["height"],
				"CONVERT" => "N",
				"FAMILY" => "FORUM",
				"SINGLE" => "Y",
				"RETURN" => "Y"
			),
			null,
			array("HIDE_ICONS" => "Y"));

		return str_replace(array(chr(34), chr(39)), array("\013", "\014"), $result);
	}

	public function convert_font_attr($attr, $value = "", $text = "")
	{
		if (strlen($text)<=0) return "";
		if (strlen($value)<=0) return $text;

		if ($attr == "size")
		{
			$count = count($this->arFontSize);
			if ($count <= 0)
				return $text;
			$value = intVal($value >= $count ? ($count - 1) : $value);
			return '<span style="font-size:'.$this->arFontSize[$value].'%;">'.$text.'</span>';
		}
		else if ($attr == 'color')
		{
			$value = preg_replace("/[^\w#]/", "" , $value);
			return '<font color="'.$value.'">'.$text.'</font>';
		}
		else if ($attr == 'font')
		{
			$value = preg_replace("/[^\w]/", "" , $value);
			return '<font face="'.$value.'">'.$text.'</font>';
		}
	}
	// Only for public using
	public function wrap_long_words($text="")
	{
		if ($this->MaxStringLen > 0 && !empty($text))
		{
			$text = str_replace(array(chr(34), chr(39)), array("\013", "\014"), $text);
			$text = preg_replace("/(?<=^|\>)([^\<]+)(?=\<|$)/ies".BX_UTF_PCRE_MODIFIER, "\$this->part_long_words('\\1')", $text);
			$text = str_replace(array("\013", "\014"), array(chr(34), chr(39)), $text);
		}
		return $text;
	}

	public function part_long_words($str)
	{
		$word_separator = $this->word_separator;
		if (($this->MaxStringLen > 0) && (strLen(trim($str)) > 0))
		{
			$str = str_replace(
				array(
					chr(1), chr(2), chr(3), chr(4), chr(5), chr(6),
					"&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;",
					chr(34), chr(39)),
				array(
					"", "", "", "", "", "",
					chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6),
					"\013", "\014"),
				$str);
			$str = preg_replace("/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/ise".BX_UTF_PCRE_MODIFIER,
				"\$this->cut_long_words('\\2')", $str);

			$str = str_replace(
				array(chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), "\013", "\014", "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", chr(34), chr(39), "<WBR/>", "<WBR/>", "&shy;"),
				$str);
		}
		return $str;
	}

	public function cut_long_words($str)
	{
		if (($this->MaxStringLen > 0) && (strLen($str) > 0))
			$str = preg_replace("/([^ \n\r\t\x01]{".$this->MaxStringLen."})/is".BX_UTF_PCRE_MODIFIER, "\\1<WBR/>&shy;", $str);
		return $str;
	}

	public static function convert_cut_tag($text, $title="")
	{
		if (empty($text))
			return "";
		$title = trim($title);
		$title = ltrim($title, "=");
		$title = trim($title);
		$result = $GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:forum.interface",
			"spoiler",
			Array(
				"TITLE" => $title,
				"TEXT" => $text,
				"RETURN" => "Y"
			),
			null,
			array("HIDE_ICONS" => "Y"));
		return str_replace(array(chr(34), chr(39)), array("\013", "\014"), $result);
	}

	public static function convert_anchor_tag($url, $text, $pref="")
	{
		$bCutUrl = True;
		$text = str_replace("\\\"", "\"", $text);
		$end = "";
		if (preg_match("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, $url, $match))
		{
			$end = $match[1];
			$url = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $url);
			$text = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $text);
		}
		if (preg_match("/\[\/(quote|code)/i", $url))
			return $url;
		$url = preg_replace(
			array(
				"/&amp;/".BX_UTF_PCRE_MODIFIER,
				"/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array(
				"&",
				"java script&#58; ") ,
			$url);
		if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\:/i".BX_UTF_PCRE_MODIFIER, $url))
			$url = 'http://'.$url;
		if (!preg_match("/^((http|https|news|ftp|aim):\/\/[-_:.a-z0-9@]+)*([^\"\013])+$/i".BX_UTF_PCRE_MODIFIER, $url))
			return $pref.$text." (".$url.") ".$end;

		if (preg_match("/^<img\s+src/i".BX_UTF_PCRE_MODIFIER, $text))
			$bCutUrl = False;

		$text = preg_replace(
			array("/&amp;/i".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array("&", "javascript&#58; "), $text);
		if ($bCutUrl && strlen($text) < 55)
			$bCutUrl = False;
		if ($bCutUrl && !preg_match("/^(http|ftp|https|news):\/\//i".BX_UTF_PCRE_MODIFIER, $text))
			$bCutUrl = False;

		if ($bCutUrl)
		{
			$stripped = preg_replace("/^(http|ftp|https|news):\/\/(\S+)$/i".BX_UTF_PCRE_MODIFIER, "\\2", $text);
			$uri_type = preg_replace("/^(http|ftp|https|news):\/\/(\S+)$/i".BX_UTF_PCRE_MODIFIER, "\\1", $text);
			$text = $uri_type.'://'.substr($stripped, 0, 30).'...'.substr($stripped, -10);
		}

		$result = $pref.'<a href="'.$url.'" target="_blank"'.
			(COption::GetOptionString("forum", "parser_nofollow", "Y") == "Y" ? ' rel="nofollow"' : '').'>'.$text.'</a>'.$end;
		return str_replace(array(chr(34), chr(39)), array("\013", "\014"), $result);
	}


	public function convert_to_rss($text, $arImages = Array(), $arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N"), $arParams = array())
	{
		global $DB;
		if (empty($arAllow))
			$arAllow = array(
				"HTML" => "N",
				"ANCHOR" => "Y",
				"BIU" => "Y",
				"IMG" => "Y",
				"QUOTE" => "Y",
				"CODE" => "Y",
				"FONT" => "Y",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "N");

		$this->quote_error = 0;
		$this->quote_open = 0;
		$this->quote_closed = 0;
		$this->code_error = 0;
		$this->code_open = 0;
		$this->code_closed = 0;
		$bAllowSmiles = $arAllow["SMILES"];
		if ($arAllow["HTML"]!="Y")
		{
			$text = preg_replace(
				array(
					"#^(.+?)<cut[\s]*(/>|>).*?$#is".BX_UTF_PCRE_MODIFIER,
					"#^(.+?)\[cut[\s]*(/\]|\]).*?$#is".BX_UTF_PCRE_MODIFIER),
				"\\1", $text);
			$arAllow["SMILES"] = "N";
			$text = $this->convert($text, $arAllow, "rss");
		}
		else
		{
			if ($arAllow["NL2BR"]=="Y")
				$text = str_replace("\n", "<br />", $text);
		}

		if (strLen($arParams["SERVER_NAME"]) <= 0)
		{
			$dbSite = CSite::GetByID(SITE_ID);
			$arSite = $dbSite->Fetch();
			$arParams["SERVER_NAME"] = $arSite["SERVER_NAME"];
			if (strLen($arParams["SERVER_NAME"]) <=0)
			{
				if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
					$arParams["SERVER_NAME"] = SITE_SERVER_NAME;
				else
					$arParams["SERVER_NAME"] = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
			}
		}

		if ($bAllowSmiles=="Y" && !empty($this->preg_smiles["pattern"]))
			$text = preg_replace($this->preg_smiles["pattern"], $this->preg_smiles["replace"], ' '.$text.' ');
		return trim($text);
	}
}

class CForumSimpleHTMLParser
{
	var $data;
	var $parse_search_needle = '/([^\[]*)(?:\[(.*)\])*/i';
	var $parse_tag = '/((\<\s*(\/)?\s*([a-z]+).*?(?:(\/)\>|\>))[^<]*)/ism';
	var $parse_beginning_spaces = '/^([\s]*)/m';
	var $replace_tag_begin = '/^\s*\w+\s*/';
	var $parse_params = '/([a-z]+)\s*=\s*(?:([^\s]*)|(?:[\'"]([^\'"])[\'"]))/im';
	var $lastError = '';

	public function __construct ($data)
	{
		$this->data = $data;
	}

	public function findTagStart($needle) // needle = input[name=input;class=red]
	{
		$offset = 0;

		$search = array();
		if (preg_match( $this->parse_search_needle, $needle, $matches ) == 0)
			return '';
		if (sizeof($matches) > 1)
			$search['TAG'] = trim($matches[1]);
		if (sizeof($matches) > 2)
		{
			$arAttr = explode(';', $matches[2]);
			foreach($arAttr as $attr)
			{
				list($attr_name, $attr_value) = explode('=', $attr);
				$search[strToUpper(trim($attr_name))] = trim($attr_value);
			}
		}
		$tmp = $this->data;
		// skip beginning spaces
		if (preg_match($this->parse_beginning_spaces, $tmp, $spaces) > 0)
		{
			$offset = strlen($spaces[1]);
			$tmp = substr($tmp, $offset);
		}

		while (strlen($tmp) > 0 && preg_match($this->parse_tag, $tmp, $matches) > 0)
		{
			$tag_name = $matches[4];
			$tag = $matches[2];
			$skip = $matches[1];
			if (strlen($skip) < 1) return false;
			if ($tag_name == $search['TAG']) // tag found
			{
				// parse params
				$params = preg_replace($this->replace_tag_begin, '', trim($tag, "<>"));
				if (preg_match_all($this->parse_params, $params, $arParams, PREG_SET_ORDER ) > 0)
				{
					// store tag params
					$arTagParams = array();
					foreach($arParams as $arParam)
						$arTagParams[strToUpper(trim($arParam[1]))] = trim(trim($arParam[2]), '"\'');
					// compare all search params
					$found = true;
					foreach($search as $key => $value)
					{
						if ($key == 'TAG') continue;
						if (!( isset($arTagParams[$key]) && $arTagParams[$key] == $value))
						{
							$found = false;
							break;
						}
					}
					if ($found)
					{
						return $offset;
					}
				}
			}
			$offset += strlen($skip);
			$tmp = substr($tmp, strlen($skip));

			// skip special tags
			while ($skip = $this->skipTags($tmp))
			{
				$offset += $skip;
				$tmp = substr($tmp, $skip);
			}
		}
		return false;
	}

	function skipTags($tmp)
	{
		static $tags_open = array('<!--', '<script');
		static $tags_close = array('-->', '</script>');
		static $n_tags = 2;
		static $tags_quoted;

		if (!is_array($tags_quoted))
		for ($i=0; $i<$n_tags;$i++)
				$tags_quoted[$i] = array('open' => preg_quote($tags_open[$i]), 'close' => preg_quote($tags_close[$i]));

		for ($i=0; $i<$n_tags;$i++)
		{
			if (preg_match('#^\s*'.$tags_quoted[$i]['open'].'#i', $tmp) < 1) continue;
			if (preg_match('#('.$tags_quoted[$i]['close'].'\s*)#im', $tmp, $matches) > 0)
			{
				$endpos = strpos($tmp, $matches[1]);
				$offset = $endpos+strlen($matches[1]);
				return $offset;
			}
		}
		return false;
	}

	public function setError($msg)
	{
		$this->lastError = $msg;
		return false;
	}

	public function findTagEnd($startIndex)
	{
		if ($startIndex === false || (intval($startIndex) == 0 && $startIndex !== 0))
			return $this->setError('E_PARSE_INVALID_INDEX');
		$tmp = substr($this->data, $startIndex);

		$this->lastError = '';
		$arStack = array();
		$offset = 0;
		$closeMistmatch = 2;
		$tag_id = 0;

		// skip beginning spaces
		if (preg_match($this->parse_beginning_spaces, $tmp, $spaces) > 0)
		{
			$offset = strlen($spaces[1]);
			$tmp = substr($tmp, $offset);
		}

		while (strlen($tmp) > 0 && preg_match($this->parse_tag, $tmp, $matches) > 0)
		{
			$tag_id++;
			$tag_name = strtoupper(trim($matches[4]));
			$tag = $matches[2];
			$skip = $matches[1];
			if (strlen($skip) < 1) return $this->setError('E_PARSE_INVALID_DOM_1');
			if ($matches[3] == '/') // close tag
			{
				if (end($arStack) == $tag_name)
					array_pop($arStack);
				else // lost close tag somewhere
				{
					$fixed = false;
					for ($i=2;$i<=$closeMistmatch+1;$i++)
					{
						if (sizeof($arStack) > $i && $arStack[sizeof($arStack)-$i] == $tag_name)
						{
							$arStack = array_slice($arStack, 0, -$i);
							$fixed = true;
						}
					}
					if (!$fixed)
					{
						return $this->setError('E_PARSE_INVALID_DOM_2');
					}
				}
			}
			elseif (isset($matches[5]) && $matches[5] == '/') // self close tag
			{
				// do nothing
			}
			elseif ($tag_name == 'LI' && end($arStack) == 'LI') // oh
			{
				// do nothing
			}
			else // open tag
			{
				$arStack[] = $tag_name;
			}
			if (sizeof($arStack) > 300)
				return $this->setError('E_PARSE_TOO_BIG_DOM_3');  // too big DOM
			elseif (sizeof($arStack) == 0) // done !
				return $offset + strlen($tag);
			else // continue
			{
				$offset += strlen($skip);
				$tmp = substr($tmp, strlen($skip));
			}
			// skip special tags
			while ($skip = $this->skipTags($tmp))
			{
				$offset += $skip;
				$tmp = substr($tmp, $skip);
			}
		}
		return $this->setError('E_PARSE_INVALID_DOM_4');  // not enough data in $data ?
	}

	public function getTagHTML($search)
	{
		$messagePost = '';
		$messageStart = $this->findTagStart($search);
		if ($messageStart === false) return '';
		$messageEnd = $this->findTagEnd($messageStart);
		if ($messageEnd !== false)
			$messagePost = substr($this->data, $messageStart, $messageEnd);
		return trim($messagePost);
	}

	public function getInnerHTML($startLabel, $endLabel, $multiple=false)
	{
		$startPos = strpos($this->data, $startLabel);
		if ($startPos === false) return '';
		$startPos += strlen($startLabel);
		$endPos = strpos($this->data, $endLabel, $startPos);
		if ($endPos === false) return '';
		return trim(substr($this->data, $startPos, $endPos-$startPos));
	}
}

class CForumCacheManager
{
	public static function CForumCacheManager()
	{
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			AddEventHandler("forum", "onAfterMessageDelete", array(&$this, "OnMessageDelete"));
			AddEventHandler("forum", "onAfterMessageUpdate", array(&$this, "OnMessageUpdate"));
			AddEventHandler("forum", "onAfterMessageAdd", array(&$this, "OnMessageAdd"));

			AddEventHandler("forum", "onAfterTopicAdd", array(&$this, "OnTopicAdd"));
			AddEventHandler("forum", "onAfterTopicUpdate", array(&$this, "OnTopicUpdate"));
			AddEventHandler("forum", "onAfterTopicDelete", array(&$this, "OnTopicDelete"));

			//AddEventHandler("forum", "onAfterForumAdd", array(&$this, "OnForumAdd"));
			AddEventHandler("forum", "onAfterForumUpdate", array(&$this, "OnForumUpdate"));
			//AddEventHandler("forum", "OnAfterForumDelete", array(&$this, "OnForumDelete"));

			AddEventHandler("main", "OnAddRatingVote", Array(&$this, "OnRate"));
			AddEventHandler("main", "OnCancelRatingVote", Array(&$this, "OnRate"));
		}
	}

	static function Compress($arDictCollection)
	{
		if (
			is_array($arDictCollection) &&
			(sizeof($arDictCollection) > 9)
		)
		{
			reset($arDictCollection);
			$arFirst = current($arDictCollection);
			$arKeys = array_keys($arFirst);
			$i = 0;

			foreach($arDictCollection as &$arDictionary)
			{
				if ($i++ === 0)
					continue;

				foreach($arKeys as $k)
				{
					if (isset($arDictionary[$k]) && ($arDictionary[$k] === $arFirst[$k]))
						unset($arDictionary[$k]);
				}
			}
		}
		return $arDictCollection;
	}

	static function Expand($arDictCollection)
	{
		if (
			is_array($arDictCollection) &&
			(sizeof($arDictCollection) > 9) &&
			is_array($arDictCollection[0])
		)
		{

			$arFirst =& $arDictCollection[0];
			$arKeys = array_keys($arFirst);
			$i = 0;

			foreach($arDictCollection as &$arDictionary)
			{
				if ($i++ === 0)
					continue;

				foreach($arKeys as $k)
				{
					if (!isset($arDictionary[$k]))
					{
						$arDictionary[$k] = $arFirst[$k];
					}
				}
			}
		}
		return $arDictCollection;
	}

	public static function SetTag($path, $tags)
	{
		global $CACHE_MANAGER;
		if (! defined("BX_COMP_MANAGED_CACHE"))
			return false;
		$CACHE_MANAGER->StartTagCache($path);
		if (is_array($tags))
		{
			foreach ($tags as $tag)
				$CACHE_MANAGER->RegisterTag($tag);
		}
		else
		{
			$CACHE_MANAGER->RegisterTag($tags);
		}
		$CACHE_MANAGER->EndTagCache();
		return true;
	}

	public static function ClearTag($type, $ID=0)
	{
		global $CACHE_MANAGER;
		static $forum = "forum_";
		static $topic = "forum_topic_";

		if ($type === "F")
			$CACHE_MANAGER->ClearByTag($forum.$ID);
		elseif ($type === "T")
			$CACHE_MANAGER->ClearByTag($topic.$ID);
		else
			$CACHE_MANAGER->ClearByTag($type);
	}

	public function OnRate($rateID, $arData)
	{
		if (!isset($arData['ENTITY_TYPE_ID']) ||
			!isset($arData['ENTITY_ID']) ||
			($arData['ENTITY_TYPE_ID'] !== 'FORUM_POST' && $arData['ENTITY_TYPE_ID'] !== 'FORUM_TOPIC'))
				return false;

		if ($arData['ENTITY_TYPE_ID'] === 'FORUM_POST')
		{
			$arMessage = CForumMessage::GetByID($arData['ENTITY_ID']);
			if ($arMessage)
				$this->ClearTag("T", $arMessage['TOPIC_ID']);
		}
		else if ($arData['ENTITY_TYPE_ID'] === 'FORUM_TOPIC')
		{
			$arTopic = CForumTopic::GetByID($arData['ENTITY_ID']);
			if ($arTopic)
				$this->ClearTag("F", $arTopic['FORUM_ID']);
			$this->ClearTag("T", $arData['ENTITY_ID']);
		}
		return true;
	}

	public function OnMessageAdd($ID, $arFields)
	{
		$this->ClearTag("T", isset($arFields["FORUM_TOPIC_ID"]) ? $arFields["FORUM_TOPIC_ID"] : $arFields["TOPIC_ID"]);
		$this->ClearTag("forum_msg_count".$arFields["FORUM_ID"]);
	}

	public function OnMessageUpdate($ID, $arFields, $arMessage = array())
	{
		$arMessage = (is_array($arMessage) ? $arMessage : array());
		$topic_id = (isset($arFields["FORUM_TOPIC_ID"]) ? $arFields["FORUM_TOPIC_ID"] : $arFields["TOPIC_ID"]);
		if (isset($arFields["APPROVED"]) && $topic_id <= 0)
			$topic_id = $arMessage["TOPIC_ID"];
		if ($topic_id > 0)
			$this->ClearTag("T", $topic_id);
		$forum_id = (isset($arFields["FORUM_ID"]) ? $arFields["FORUM_ID"] : 0);
		if (isset($arFields["APPROVED"]) && $forum_id <= 0)
			$forum_id = $arMessage["FORUM_ID"];
		if ($forum_id > 0)
			$this->ClearTag("forum_msg_count".$forum_id);
	}

	public function OnMessageDelete($ID, $arMessage)
	{
		$this->ClearTag("T", isset($arMessage["FORUM_TOPIC_ID"]) ? $arMessage["FORUM_TOPIC_ID"] : $arMessage["TOPIC_ID"]);
		$this->ClearTag("forum_msg_count".$arMessage["FORUM_ID"]);
	}

	public function OnTopicAdd($ID, $arFields)
	{
		$this->ClearTag("F", $arFields["FORUM_ID"]);
	}

	public function OnTopicUpdate($ID, $arFields)
	{
		$this->ClearTag("T", $ID);
		$this->ClearTag("F", $arFields["FORUM_ID"]);
	}

	public function OnTopicDelete(&$ID, $arTopic)
	{
		$this->ClearTag("T", $ID);
		$this->ClearTag("F", $arTopic["FORUM_ID"]);
	}

	//function OnForumAdd(&$ID, &$arFields)
	//{
	//}

	public function OnForumUpdate($ID, $arFields)
	{
		$this->ClearTag("F", $arFields["FORUM_ID"]);
	}

	//function OnForumDelete($ID)
	//{
	//}
}

class CForumAutosave
{
	private static $instance;
	private static $as;

	public function __construct()
	{
		echo CJSCore::Init(array('autosave'), true);
		$this->as = new CAutoSave();
	}

	public static function GetInstance()
	{
		if (!$GLOBALS['USER']->IsAuthorized())
			return false;
		if (COption::GetOptionString("forum", "USE_AUTOSAVE", "Y") === "N")
			return false;

		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function LoadScript($arParams)
	{
		if (!is_array($arParams))
			$arParams = array("formID" => $arParams);
		if (!isset($arParams['recoverMessage']))
			$arParams['recoverMessage'] = GetMessage('F_MESSAGE_RECOVER');

		$jsParams = CUtil::PhpToJSObject($arParams);
		$id = $this->as->GetID();
		ob_start();
?>
		<script>
		window.autosave_<?=$id?>_func = function() { ForumFormAutosave(<?=$jsParams?>); window.autosave_<?=$id?>.Prepare(); };
		if (!!window["ForumFormAutosave"])
			window.autosave_<?=$id?>_func();
		else
		{
			BX.addCustomEvent(window, 'onScriptForumAutosaveLoaded', window.autosave_<?=$id?>_func);
			BX.loadScript("<?=CUtil::GetAdditionalFileURL("/bitrix/js/forum/autosave.js")?>");
		}
		</script>
<?
		ob_end_flush();
	}

	public function Init()
	{
		return $this->as->Init(false);
	}

	public function Reset()
	{
		return $this->as->Reset();
	}
}
?>