<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CTextParser
{
	var $type = "html";
	var $serverName = "";
	var $preg;

	public function CTextParser()
	{
		$this->pathToSmile = '';
		$this->imageWidth = 800;
		$this->imageHeight = 800;
		$this->MaxStringLen = 60;
		$this->AllowImgExt = "gif|jpg|jpeg|png";
		$this->arFontSize = array(
			1 => 40, //"xx-small"
			2 => 60, //"x-small"
			3 => 80, //"small"
			4 => 100, //"medium"
			5 => 120, //"large"
			6 => 140, //"x-large"
			7 => 160, //"xx-large"
		);
		$this->word_separator = "\\s.,;:!?\\#\\-\\*\\|\\[\\]\\(\\)\\{\\}";
		$this->parser_nofollow = "N";
		$this->link_target = "_blank";
		$this->MaxAnchorLength = 40;
		$this->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N", "SHORT_ANCHOR" => "N", "ALIGN" => "Y");
		$this->authorName = '';

		$this->smiles = CSmile::getByType();
		$this->smiles = CSmile::prepareForParser($this->smiles);
	}

	public function convertText($text)
	{
		$text = preg_replace("#([?&;])PHPSESSID=([0-9a-zA-Z]{32})#is", "\\1PHPSESSID1=", $text);

		$this->type = ($this->type == "rss" ? "rss" : "html");
		$this->serverName = "";
		if($this->type == "rss")
		{
			$dbSite = CSite::GetByID(SITE_ID);
			$arSite = $dbSite->Fetch();
			$serverName = $arSite["SERVER_NAME"];
			if (strlen($serverName) <=0)
			{
				if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
					$serverName = SITE_SERVER_NAME;
				else
					$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
			}
			$this->serverName = "http://".$serverName;
		}

		$this->preg = array("counter" => 0, "pattern" => array(), "replace" => array());

		foreach(GetModuleEvents("main", "TextParserBefore", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->allow["CODE"]=="Y")
		{
			$text = preg_replace(
				"#(\\[code(?:\\s+[^\\]]*\\]|\\]))(.+?)(\\[/code(?:\\s+[^\\]]*\\]|\\]))#ise".BX_UTF_PCRE_MODIFIER,
				"'[code]'.\$this->pre_convert_code_tag('\\2').'[/code]'",
			$text);

			$text = preg_replace(
				"#(<code(?:\\s+[^>]*>|>))(.+?)(</code(?:\\s+[^>]*>|>))#ise".BX_UTF_PCRE_MODIFIER,
				"'[code]'.\$this->pre_convert_code_tag('\\2').'[/code]'",
			$text);
		}

		if ($this->allow["HTML"] != "Y")
		{
			if ($this->allow["ANCHOR"]=="Y")
			{
				$text = preg_replace(
					array(
						"#<a[^>]+href\\s*=\\s*('|\")(.+?)(?:\\1)[^>]*>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href(\\s*=\\s*)([^'\">]+)>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
					"[url=\\2]\\3[/url]", $text
				);
			}
			if ($this->allow["BIU"]=="Y")
			{
				$replaced = 0;
				do
				{
					$text = preg_replace(
						"/<([busi])[^>a-z]*>(.+?)<\\/(\\1)[^>a-z]*>/is".BX_UTF_PCRE_MODIFIER,
						"[\\1]\\2[/\\1]",
					$text, -1, $replaced);
				}
				while($replaced > 0);
			}
			if ($this->allow["IMG"]=="Y")
			{
				$text = preg_replace(
					"#<img[^>]+src\\s*=[\\s'\"]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\\/[-_/=:.a-z0-9@{}&?]+)+)[\\s'\"]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
					"[img]\\1[/img]", $text
				);
			}
			if ($this->allow["FONT"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<font[^>]+size\\s*=[\\s'\"]*([0-9]+)[\\s'\"]*[^>]*\\>(.+?)\\<\\/font[^>]*\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<font[^>]+color\\s*=[\\s'\"]*(\\#[a-f0-9]{6})[^>]*\\>(.+?)\\<\\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<font[^>]+face\\s*=[\\s'\"]*([a-z\\s\\-]+)[\\s'\"]*[^>]*>(.+?)\\<\\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER
					),
					array(
						"[size=\\1]\\2[/size]",
						"[color=\\1]\\2[/color]",
						"[font=\\1]\\2[/font]"
					),
					$text
				);
			}
			if ($this->allow["LIST"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<ul((\\s[^>]*)|(\\s*))\\>(.+?)<\\/ul([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<ol((\\s[^>]*)|(\\s*))\\>(.+?)<\\/ol([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<li((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
					),
					array(
						"[list]\\4[/list]",
						"[list=1]\\4[/list]",
						"[*]",
					),
					$text
				);
			}

			if ($this->allow["TABLE"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<table((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/table([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<tr((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/tr([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<td((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/td([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<th((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/th([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
					),
					array(
						"[table]",
						"[/table]",
						"[tr]",
						"[/tr]",
						"[td]",
						"[/td]",
						"[th]",
						"[/th]",
					),
					$text
				);
			}

			if ($this->allow["QUOTE"]=="Y")
			{
				$text = preg_replace("#<(/?)quote(.*?)>#is", "[\\1quote]", $text);
			}

			if ($this->allow['NL2BR'] == 'Y')
			{
				$text = preg_replace("#<br(.*?)>#is", "\n", $text);
			}

			if (strlen($text)>0)
			{
				$text = str_replace(
					array("<", ">", '"'),
					array("&lt;", "&gt;", "&quot;"),
					$text
				);
			}
		}
		if ($this->allow["ANCHOR"]=="Y")
		{
			$word_separator = str_replace(array("\\]", "\\["), "", $this->word_separator);
			$text = preg_replace(
				"/(?<=^|[".$word_separator."]|\\s)(?<!\\[nomodify\\]|<nomodify>)((http|https|news|ftp|aim|mailto):\\/\\/[._:a-z0-9@-].*?)(?=[\\s'\"{}\\[\\]]|&quot;|\$)/is".BX_UTF_PCRE_MODIFIER,
				"[url]\\1[/url]", $text
			);
		}

		foreach(GetModuleEvents("main", "TextParserBeforeTags", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		$text = preg_replace("/<\\/?nomodify>/i".BX_UTF_PCRE_MODIFIER, "", $text);

		foreach ($this->allow as $tag => $val)
		{
			if ($val != "Y")
				continue;

			if (strpos($text, "<nomodify>") !== false)
			{
				$text = preg_replace(
					"/<nomodify>(.*?)<\\/nomodify>/ies".BX_UTF_PCRE_MODIFIER,
					"\$this->defended_tags('\\1', 'replace')",
					$text
				);
			}

			switch ($tag)
			{
				case "CODE":
					$text = preg_replace(
						"/\\[code[^\\]]*\\](.*?)\\[\\/code[^\\]]*\\]/ies".BX_UTF_PCRE_MODIFIER,
						"\$this->convert_code_tag('\\1')",
						$text
					);
					break;
				case "VIDEO":
					$text = preg_replace("/\\[video([^\\]]*)\\](.+?)\\[\\/video[\\s]*\\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_video('\\1', '\\2')", $text);
					break;
				case "IMG":
					$text = preg_replace("/\\[img([^\\]]*)\\](.+?)\\[\\/img\\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_image_tag('\\2', '\\1')", $text);
					break;
				case "ANCHOR":
					$arUrlPatterns = array(
						"/\\[url\\](.*?)\\[\\/url\\]/ie".BX_UTF_PCRE_MODIFIER,
						"/\\[url\\s*=\\s*(
							(?:
								[^\\[\\]]++
								|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\]
							)+
							)\\s*\\](.*?)\\[\\/url\\]/iexs".BX_UTF_PCRE_MODIFIER,
					);

					if($this->allow["CUT_ANCHOR"] != "Y")
					{
						$text = preg_replace($arUrlPatterns,
							array(
								"\$this->convert_anchor_tag('\\1', '\\1', '')",
								"\$this->convert_anchor_tag('\\1', '\\2', '')"
							),
							$text
						);
					}
					else
					{
						$text = preg_replace($arUrlPatterns, "", $text);
					}
					break;

				case "BIU":
					$replaced  = 0;
					do
					{
						$text = preg_replace(
							"/\\[([busi])\\](.+?)\\[\\/(\\1)\\]/is".BX_UTF_PCRE_MODIFIER,
							"<\\1>\\2</\\1>",
						$text, -1, $replaced);
					}
					while($replaced > 0);
					break;
				case "LIST":
					while (preg_match("/\\[list\\s*=\\s*(1|a)\\s*\\](.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							array(
								"/\\[list\\s*=\\s*1\\s*\\](\\s*\\n*)(.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER,
								"/\\[list\\s*=\\s*a\\s*\\](\\s*\\n*)(.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER,
								"/\\[\\*\\]/".BX_UTF_PCRE_MODIFIER,
							),
							array(
								"<ol>\\2</ol>",
								"<ol type=\"a\">\\2</ol>",
								"<li>",
							),
							$text
						);
					}
					while (preg_match("/\\[list\\](.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							array(
								"/\\[list\\](\\s*\\n*)(.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER,
								"/\\[\\*\\]/".BX_UTF_PCRE_MODIFIER,
								),
							array(
								"<ul>\\2</ul>",
								"<li>",
								),
							$text
						);
					}
					break;
				case "FONT":
					while (preg_match("/\\[size\\s*=\\s*([^\\]]+)\\](.+?)\\[\\/size\\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\\[size\\s*=\\s*([^\\]]+)\\](.+?)\\[\\/size\\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('size', '\\1', '\\2')", $text);
					while (preg_match("/\\[font\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/font\\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\\[font\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/font\\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('font', '\\1', '\\2')", $text);
					while (preg_match("/\\[color\\s*=\\s*([^\\]]+)\\](.+?)\\[\\/color\\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\\[color\\s*=\\s*([^\\]]+)\\](.+?)\\[\\/color\\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('color', '\\1', '\\2')", $text);
					break;
				case "TABLE":
					while (preg_match("/\\[table\\](.+?)\\[\\/table\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							array(
								"/\\[table\\](\\s*\\n*)(.*?)\\[\\/table\\]/is".BX_UTF_PCRE_MODIFIER,
								"/\\[tr\\](.*?)\\[\\/tr\\](\\s*\\n*)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[td\\](.*?)\\[\\/td\\]/is".BX_UTF_PCRE_MODIFIER,
								"/\\[th\\](.*?)\\[\\/th\\]/is".BX_UTF_PCRE_MODIFIER,
								),
							array(
								"<table class=\"data-table\">\\2</table>",
								"<tr>\\1</tr>",
								"<td>\\1</td>",
								"<th>\\1</th>",
								),
							$text
						);
					}
					break;
				case "ALIGN":
					$text = preg_replace(
						array(
							"/\\[left\\](.*?)\\[\\/left\\]/is".BX_UTF_PCRE_MODIFIER,
							"/\\[right\\](.*?)\\[\\/right\\]/is".BX_UTF_PCRE_MODIFIER,
							"/\\[center\\](.*?)\\[\\/center\\]/is".BX_UTF_PCRE_MODIFIER,
							"/\\[justify\\](.*?)\\[\\/justify\\]/is".BX_UTF_PCRE_MODIFIER,
						),
						array(
							"<div align=\"left\">\\1</div>",
							"<div align=\"right\">\\1</div>",
							"<div align=\"center\">\\1</div>",
							"<div align=\"justify\">\\1</div>",
						),
						$text
					);
					break;
				case "QUOTE":
					while (preg_match("/\\[quote[^\\]]*\\](.*?)\\[\\/quote[^\\]]*\\]/ies".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							"/\\[quote[^\\]]*\\](.*?)\\[\\/quote[^\\]]*\\]/ies".BX_UTF_PCRE_MODIFIER,
							"\$this->convert_quote_tag('\\1')",
							$text
						);
					}
					break;

			}
		}

		if (strpos($text, "<nomodify>") !== false)
		{
			$text = preg_replace(
				"/<nomodify>(.*?)<\\/nomodify>/ies".BX_UTF_PCRE_MODIFIER,
				"\$this->defended_tags('\\1', 'replace')",
			$text);
		}

		foreach(GetModuleEvents("main", "TextParserAfterTags", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->allow["HTML"] != "Y" || $this->allow['NL2BR'] == 'Y')
		{
			$text = str_replace("\n", "<br />", $text);

			$text = preg_replace(array(
				"/\\<br \\/\\>(\\<\\/table[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<thead[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/thead[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tfoot[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tfoot[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tbody[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tbody[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tr[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tr[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<td[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/td[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
			),
			"\\1", $text);
		}

		$text = str_replace(
			array(
				"(c)", "(C)",
				"(tm)", "(TM)", "(Tm)", "(tM)",
				"(r)", "(R)"),
			array(
				"&#169;", "&#169;",
				"&#153;", "&#153;", "&#153;", "&#153;",
				"&#174;", "&#174;"),
			$text);

		if ($this->allow["HTML"] != "Y" && $this->MaxStringLen > 0)
		{
			$text = preg_replace(
				array(
					"/(\\&\\#\\d{1,3}\\;)/is".BX_UTF_PCRE_MODIFIER,
					"/(?<=^|\\>)([^\\<\\[]+)(?=\\<\\[|$)/ies".BX_UTF_PCRE_MODIFIER,
					"/(\\<\019((\\&\\#\\d{1,3}\\;))\\>)/is".BX_UTF_PCRE_MODIFIER,),
				array(
					"<\019\\1>",
					"\$this->part_long_words('\\1')",
					"\\2"),
				$text);
		}

		if (strpos($text, "<nosmile>") !== false)
		{
			$text = preg_replace(
				"/<nosmile>(.*?)<\\/nosmile>/ies".BX_UTF_PCRE_MODIFIER,
				"\$this->defended_tags('\\1', 'replace')",
			$text);
		}

		if ($this->allow["SMILES"]=="Y" && count($this->smiles) > 0)
		{
			$arPattern = array();
			$arReplace = array();

			$pre = "[^\\w&]";
			foreach ($this->smiles as $row)
			{
				if(preg_match("/\\w\$/", $row["TYPING"]))
					$pre .= "|".preg_quote($row["TYPING"], "/");
			}

			foreach ($this->smiles as $row)
			{
				$code = str_replace(array("'", "<", ">"), array("\\'", "&lt;", "&gt;"), $row["TYPING"]);
				$patt = preg_quote($code, "/");
				$code = preg_quote(str_replace(array("\x5C"), array("&#092;"), $code));

				$image = preg_quote(str_replace("'", "\\'", $row["IMAGE"]));
				$description = preg_quote(htmlspecialcharsbx(str_replace(array("\x5C"), array("&#092;"), $row["DESCRIPTION"]), ENT_QUOTES), "/");
				$width = intval($row["IMAGE_WIDTH"]);
				$height = intval($row["IMAGE_HEIGHT"]);
				$descriptionDecode = $row["DESCRIPTION_DECODE"] == 'Y'? true: false;

				$arPattern[] = "/(?<=".$pre.")$patt(?=.\\W|\\W.|\\W$)/ei".BX_UTF_PCRE_MODIFIER;
				$arReplace[] = "\$this->convert_emoticon('$code', '$image', '$description', '$width', '$height', '$descriptionDecode')";
			}

			if (!empty($arPattern))
				$text = preg_replace($arPattern, $arReplace, ' '.$text.' ');
		}

		foreach(GetModuleEvents("main", "TextParserBeforePattern", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->preg["counter"] > 0)
			$text = str_replace($this->preg["pattern"], $this->preg["replace"], $text);

		foreach(GetModuleEvents("main", "TextParserAfter", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		return trim($text);
	}

	public function defended_tags($text, $tag = 'replace')
	{
		$text = str_replace("\\\"", "\"", $text);
		switch ($tag)
		{
			case "replace":
				$this->preg["pattern"][] = "<\017#".$this->preg["counter"].">";
				$this->preg["replace"][] = $text;
				$text = "<\017#".$this->preg["counter"].">";
				$this->preg["counter"]++;
				break;
		}
		return $text;
	}

	public static function convert4mail($text)
	{
		$text = Trim($text);
		if (strlen($text)<=0) return "";
		
		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\\[(code|quote)(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\\[\\/(code|quote)(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\\<WBR[\\s\\/]?\\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\\[b\\](.+?)\\[\\/b\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\\[i\\](.+?)\\[\\/i\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\\[u\\](.+?)\\[\\/u\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\\[s\\](.+?)\\[\\/s\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\\[(\\/?)(color|font|size)([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\\[url\\](\\S+?)\\[\\/url\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1 )";

		$arPattern[] = "/\\[url\\s*=\\s*(\\S+?)\\s*\\](.*?)\\[\\/url\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1 )";

		$arPattern[] = "/\\[img\\](.+?)\\[\\/img\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\\[video([^\\]]*)\\](.+?)\\[\\/video[\\s]*\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\\[(\\/?)list\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";
		$text = preg_replace($arPattern, $arReplace, $text);

		$text = str_replace("&shy;", "", $text);
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
		if (strlen($path) <= 0)
			return "";

		AddEventHandler("main", "TextParserVideoConvert", array("CTextParser", "TextParserConvertVideo"), 1000);

		$width = ""; $height = ""; $preview = "";
		preg_match("/width\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);

		preg_match("/preview\\='([^']+)'/is".BX_UTF_PCRE_MODIFIER, $params, $preview);
		if (empty($preview))
			preg_match("/preview\\=\"([^\"]+)\"/is".BX_UTF_PCRE_MODIFIER, $params, $preview);

		$width = intval($width[1]);
		$width = ($width > 0 ? $width : 400);
		$height = intval($height[1]);
		$height = ($height > 0 ? $height : 300);
		$preview = trim($preview[1]);
		$preview = (strlen($preview) > 0 ? $preview : "");

		$arFields = array(
			"PATH" => $path,
			"WIDTH" => $width,
			"HEIGHT" => $height,
			"PREVIEW" => $preview,
		);
		$video = '';
		foreach(GetModuleEvents("main", "TextParserVideoConvert", true) as $arEvent)
			$video = ExecuteModuleEventEx($arEvent, array(&$arFields));

		if(strlen($video) > 0)
			return "<nomodify>".$video."</nomodify>";
		return false;
	}

	public function convert_emoticon($code = "", $image = "", $description = "", $width = "", $height = "", $descriptionDecode = false)
	{
		if (strlen($code)<=0 || strlen($image)<=0)
			return '';
		$code = stripslashes($code);
		$description = stripslashes($description);
		$image = stripslashes($image);
		if ($descriptionDecode)
			$description = htmlspecialcharsback($description);

		$alt = "<\018#".$this->preg["counter"].">";
		$this->preg["pattern"][] = $alt;
		$this->preg["replace"][] = '<img src="'.$this->serverName.$this->pathToSmile.$image.'" border="0" data-code="'.$code.'" alt="'.$code.'" '.(intval($width)>0? 'width="'.intval($width).'"':'').' '.(intval($height)>0? 'height="'.intval($height).'"':'').' title="'.$description.'" class="bx-smile" />';
		$this->preg["counter"]++;

		return $alt;
	}

	public static function pre_convert_code_tag ($text = "")
	{
		if (strlen($text)<=0)
			return '';

		$text = str_replace(
			array("&", "<", ">", "[", "]", "\\\""),
			array("&#38;", "&#60;", "&#62;", "&#91;", "&#93;", "\""),
			$text
		);

		$text = preg_replace(
			"/(?<=^|[^a-z])((http|https|news|ftp|aim|mailto):\\/\\/[._:a-z0-9@-].*?)(?=[\\s'\"\\[\\]{}]|&quot;|\\[\\/?nomodify\\]|<\\/?nomodify>)/is".BX_UTF_PCRE_MODIFIER,
			"[nomodify]\\1[/nomodify]",
			$text
		);
		return $text;
	}

	public function convert_code_tag($text = "")
	{
		if (strlen($text)<=0)
			return '';

		$text = str_replace(array("[nomodify]", "[/nomodify]"), "", $text);

		$text = str_replace(
			array("<", ">", "\\r", "\\n", "\\", "[", "]", "  ", "\t"),
			array("&#60;", "&#62;", "&#92;r", "&#92;n", "&#92;", "&#91;", "&#93;", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"),
			$text
		);

		$text = stripslashes($text);

		$this->preg["pattern"][] = "<\017#".$this->preg["counter"].">";
		$this->preg["replace"][] = $this->convert_open_tag('code')."<pre>".$text."</pre>".$this->convert_close_tag('code');
		$text = "<\017#".$this->preg["counter"].">";
		$this->preg["counter"]++;

		return $text;
	}

	public function convert_quote_tag($text = "")
	{
		if (strlen($text)<=0)
			return '';

		$text = str_replace("\\\"", "\"", $text);

		return $this->convert_open_tag('quote').$text.$this->convert_close_tag('quote');
	}

	public function convert_open_tag($marker = "quote")
	{
		$marker = (strtolower($marker) == "code" ? "code" : "quote");

		$this->{$marker."_open"}++;
		if ($this->type == "rss")
			return "\n====".$marker."====\n";
		return "<div class='".$marker."'><table class='".$marker."'><tr><td>";
	}

	public function convert_close_tag($marker = "quote")
	{
		$marker = (strtolower($marker) == "code" ? "code" : "quote");

		if ($this->{$marker."_open"} == 0)
		{
			$this->{$marker."_error"}++;
			return '';
		}
		$this->{$marker."_closed"}++;

		if ($this->type == "rss")
			return "\n=============\n";
		return "</td></tr></table></div>";
	}

	public function convert_image_tag($url = "", $params = "")
	{
		$url = trim($url);
		if (strlen($url)<=0)
			return '';

		preg_match("/width\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);
		$width = intval($width[1]);
		$height = intval($height[1]);

		$bErrorIMG = false;
		if (!$bErrorIMG && !preg_match("/^(http|https|ftp|\\/)/i".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = true;

		$url = htmlspecialcharsbx($url);
		if ($bErrorIMG)
			return "[img]".$url."[/img]";

		$strPar = "";
		if($width > 0)
		{
			if($width > $this->imageWidth)
			{
				$height = IntVal($height * ($this->imageWidth / $width));
				$width = $this->imageWidth;
			}
		}
		if($height > 0)
		{
			if($height > $this->imageHeight)
			{
				$width = IntVal($width * ($this->imageHeight / $height));
				$height = $this->imageHeight;
			}
		}
		if($width > 0)
			$strPar = " width=\"".$width."\"";
		if($height > 0)
			$strPar .= " height=\"".$height."\"";

		if(strlen($this->serverName) <= 0 || preg_match("/^(http|https|ftp)\\:\\/\\//i".BX_UTF_PCRE_MODIFIER, $url))
			return '<img src="'.$url.'" border="0"'.$strPar.' data-bx-image="'.$url.'" />';
		else
			return '<img src="'.$this->serverName.$url.'" border="0"'.$strPar.' data-bx-image="'.$this->serverName.$url.'" />';
	}

	public function convert_font_attr($attr, $value = "", $text = "")
	{
		if (strlen($text)<=0)
			return "";

		$text = str_replace("\\\"", "\"", $text);

		if (strlen($value)<=0)
			return $text;

		if ($attr == "size")
		{
			$count = count($this->arFontSize);
			if ($count <= 0)
				return $text;
			$value = intVal($value > $count ? ($count - 1) : $value);
			return '<span style="font-size:'.$this->arFontSize[$value].'%;">'.$text.'</span>';
		}
		elseif ($attr == 'color')
		{
			$value = preg_replace("/[^\\w#]/", "" , $value);
			return '<span style="color:'.$value.'">'.$text.'</span>';
		}
		elseif ($attr == 'font')
		{
			$value = preg_replace("/[^\\w\\s\\-\\,]/", "" , $value);
			return '<span style="font-family:'.$value.'">'.$text.'</span>';
		}
		return '';
	}

	// Only for public using
	public function wrap_long_words($text="")
	{
		if ($this->MaxStringLen > 0 && !empty($text))
		{
			$text = str_replace(array(chr(11), chr(12), chr(34), chr(39)), array("", "", chr(11), chr(12)), $text);
			$text = preg_replace("/(?<=^|\\>)([^\\<]+)(?=\\<|$)/ies".BX_UTF_PCRE_MODIFIER, "\$this->part_long_words('\\1')", $text);
			$text = str_replace(array(chr(11), chr(12)), array(chr(34), chr(39)), $text);
		}
		return $text;
	}

	public function part_long_words($str)
	{
		$word_separator = $this->word_separator;
		if (($this->MaxStringLen > 0) && (strlen(trim($str)) > 0))
		{
			$str = str_replace(
				array(chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8),
					"&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;",
					chr(34), chr(39)),
				array("", "", "", "", "", "", "", "",
					chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6),
					chr(7), chr(8)),
				$str
			);
			$str = preg_replace(
				"/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/ise".BX_UTF_PCRE_MODIFIER,
				"\$this->cut_long_words('\\2')",
				$str
			);
			$str = str_replace(
				array(chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", chr(34), chr(39), "<WBR/>", "<WBR/>", "&shy;"),
				$str
			);
		}
		return $str;
	}

	public function cut_long_words($str)
	{
		if (($this->MaxStringLen > 0) && (strlen($str) > 0))
			$str = preg_replace("/([^ \n\r\t\x01]{".$this->MaxStringLen."})/is".BX_UTF_PCRE_MODIFIER, "\\1<WBR/>&shy;", $str);
		return $str;
	}

	public function convert_anchor_tag($url, $text, $pref="")
	{
		if(strlen(trim($text)) <= 0)
			$text = $url;

		$bShortUrl = ($this->allow["SHORT_ANCHOR"] == "Y") ? true : false;

		$text = str_replace("\\\"", "\"", $text);
		$end = "";
		if (preg_match("/([\\.,\\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, $url, $match))
		{
			$end = $match[1];
			$url = preg_replace("/([\\.,\\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $url);
			$text = preg_replace("/([\\.,\\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $text);
		}

		if (preg_match("/\\[\\/(quote|code|img|imag|video)/i", $url))
			return $url;

		$url = preg_replace(
			array(
				"/&amp;/".BX_UTF_PCRE_MODIFIER,
				"/javascript:/i".BX_UTF_PCRE_MODIFIER,
				"/[".chr(12)."\\']/".BX_UTF_PCRE_MODIFIER
			),
			array(
				"&",
				"java script&#58; ",
				"%27"
			),
			$url
		);
		if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\\:/i".BX_UTF_PCRE_MODIFIER, $url))
			$url = "http://".$url;

		if (preg_match("/^<img\\s+src/i".BX_UTF_PCRE_MODIFIER, $text))
			$bShortUrl = false;

		$text = preg_replace(
			array("/&amp;/i".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array("&", "javascript&#58; "),
			$text
		);

		if ($bShortUrl && strlen($text) < $this->MaxAnchorLength)
			$bShortUrl = false;
		if ($bShortUrl && !preg_match("/^(http|ftp|https|news):\\/\\//i".BX_UTF_PCRE_MODIFIER, $text))
			$bShortUrl = false;

		if ($bShortUrl)
		{
			preg_match("/^(http|ftp|https|news):\\/\\/(\\S+)$/i".BX_UTF_PCRE_MODIFIER, $text, $matches);
			$uri_type = $matches[1];
			$stripped = $matches[2];

			if(strlen($stripped) > $this->MaxAnchorLength)
				$text = $uri_type.'://'.substr($stripped, 0, $this->MaxAnchorLength-10).'...'.substr($stripped, -10);
			else
				$text = $uri_type.'://'.$stripped;
		}

		$url = htmlspecialcharsbx(htmlspecialcharsback($url));

		return $pref.($this->parser_nofollow == "Y" ? '<noindex>' : '').'<a href="'.$url.'" target="'.$this->link_target.'"'.($this->parser_nofollow == "Y" ? ' rel="nofollow"' : '').'>'.$text.'</a>'.($this->parser_nofollow == "Y" ? '</noindex>' : '').$end;
	}

	public static function TextParserConvertVideo($arParams)
	{
		global $APPLICATION;

		if(empty($arParams) || strlen($arParams["PATH"]) <= 0)
			return false;

		ob_start();
		if (
			defined("BX_MOBILE_LOG") 
			&& BX_MOBILE_LOG === true
		)
		{
			?><div onclick="return BX.eventCancelBubble(event);"><?
		}
		$APPLICATION->IncludeComponent(
			"bitrix:player", "",
			array(
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
				"DOWNLOAD_LINK_TARGET" => "_self"
			),
			null,
			array(
				"HIDE_ICONS" => "Y"
			)
		);
		if (
			defined("BX_MOBILE_LOG") 
			&& BX_MOBILE_LOG === true
		)
		{
			?></div><?
		}
		$video = ob_get_contents();
		ob_end_clean();
		return $video;
	}

	public static function strip_words($string, $count)
	{
		$splice_pos = null;

		$ar = preg_split("/(<.*?>|\\s+)/s", $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($ar as $i => $s)
		{
			if(substr($s, 0, 1) != "<")
			{
				$count -= strlen($s);
				if($count <= 0)
				{
					$splice_pos = $i;
					break;
				}
			}
		}

		if(isset($splice_pos))
		{
			array_splice($ar, $splice_pos+1);
			return implode('', $ar);
		}
		else
		{
			return $string;
		}
	}

	public static function closetags($html)
	{
		preg_match_all("#<([a-z0-9]+)([^>]*)(?<!/)>#i".BX_UTF_PCRE_MODIFIER, $html, $result);
		$openedtags = $result[1];

		preg_match_all("#</([a-z0-9]+)>#i".BX_UTF_PCRE_MODIFIER, $html, $result);
		$closedtags = $result[1];
		$len_opened = count($openedtags);

		if(count($closedtags) == $len_opened)
			return $html;

		$openedtags = array_reverse($openedtags);

		for($i = 0; $i < $len_opened; $i++)
		{
			if (!in_array($openedtags[$i], $closedtags))
				$html .= '</'.$openedtags[$i].'>';
			else
				unset($closedtags[array_search($openedtags[$i], $closedtags)]);
		}

		return $html;
	}

	public function html_cut($html, $size)
	{
		$symbols = strip_tags($html);
		$symbols_len = strlen($symbols);

		if($symbols_len < strlen($html))
		{
			$strip_text = $this->strip_words($html, $size);

			if($symbols_len > $size)
				$strip_text = $strip_text."...";

			$final_text = $this->closetags($strip_text);
		}
		elseif($symbols_len > $size)
			$final_text = substr($html, 0, $size)."...";
		else
			$final_text = $html;

		return $final_text;
	}
}
