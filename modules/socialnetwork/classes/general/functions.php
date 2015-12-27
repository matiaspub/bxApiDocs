<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CSocNetTextParser</b> - класс, предназначенный для форматирования сообщений социальной сети. Осуществляет замену спецсимволов и заказных тегов на реальные HTML-теги, обработку ссылок, отображение смайлов. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettextparser/index.php
 * @author Bitrix
 */
class CSocNetTextParser
{
	var $smiles = array();
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

	var $matchNum = 0;
	var $matchNum2 = 0;
	var $matchType = "html";
	var $matchType2 = "";
	var $matchType3 = "";
	var $matchType4 = "";

	public function CSocNetTextParser($strLang = False, $pathToSmile = false)
	{
		global $DB, $CACHE_MANAGER;
		static $arSmiles = array();

		$this->smiles = array();
		if ($strLang === False)
			$strLang = LANGUAGE_ID;
		$this->path_to_smile = $pathToSmile;

		if($CACHE_MANAGER->Read(604800, "b_sonet_smile"))
		{
			$arSmiles = $CACHE_MANAGER->Get("b_sonet_smile");
		}
		else
		{
			$db_res = CSocNetSmile::GetList(array("SORT" => "ASC"), array("SMILE_TYPE" => "S"/*, "LANG_LID" => $strLang*/), false, false, Array("LANG_LID", "ID", "IMAGE", "DESCRIPTION", "TYPING", "SMILE_TYPE", "SORT"));
			while ($res = $db_res->Fetch())
			{
				$tok = strtok($res['TYPING'], " ");
				while ($tok !== false)
				{
					$arSmiles[$res['LANG_LID']][] = array(
						'TYPING' => $tok,
						'IMAGE'  => stripslashes($res['IMAGE']), // stripslashes is not needed here
						'DESCRIPTION' => stripslashes($res['NAME']) // stripslashes is not needed here
					);
					$tok = strtok(" ");
				}
			}

			public function sonet_sortlen($a, $b) 
			{
				if (strlen($a["TYPING"]) == strlen($b["TYPING"])) 
				{
					return 0;
				}
				return (strlen($a["TYPING"]) > strlen($b["TYPING"])) ? -1 : 1;
			}

			foreach ($arSmiles as $LID => $arSmilesLID)
			{
				uasort($arSmilesLID, 'sonet_sortlen');
				$arSmiles[$LID] = $arSmilesLID;
			}

			$CACHE_MANAGER->Set("b_sonet_smile", $arSmiles);
		}
		$this->smiles = $arSmiles[$strLang];
	}

	
	/**
	* <p>Метод форматирования сообщения.</p>
	*
	*
	* @param string $text  Исходное сообщение. </ht
	*
	* @param bool $bPreview = true Необязательный параметр. По умолчанию равен true.
	*
	* @param array $arImages = array() Массив картинок сообщения.
	*
	* @param array $allow = array() Массив параметров для форматирования сообщения, со значениями
	* <i>Y</i> или <i>N</i>: <ul> <li> <b>HTML</b> - в тексте могут содержаться любые HTML
	* теги, </li> <li> <b>ANCHOR</b> - разрешен тег &lt;a&gt;, </li> <li> <b>BIU</b> - разрешены
	* теги &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, </li> <li> <b>IMG</b> - разрешен тег &lt;img&gt;, </li> <li>
	* <b>QUOTE</b> - разрешен тег цитирования &lt;quote&gt;, </li> <li> <b>CODE</b> - разрешен
	* тег показа кода &lt;code&gt;, </li> <li> <b>FONT</b> - разрешен тег &lt;font&gt;, </li> <li>
	* <b>LIST</b> - разрешены теги &lt;ul&gt;, &lt;li&gt;, </li> <li> <b>SMILES</b> - показ
	* смайликов в виде картинок, </li> <li> <b>NL2BR</b> - заменять переводы
	* каретки на тег &lt;br&gt; при разрешении принимать любые HTML теги, </li>
	* <li> <b>VIDEO</b> - разрешена вставка видео, </li> </ul>
	*
	* @param string $type = html Тип сообщения. Необязательный параметр. По умолчанию принимает
	* значение html.
	*
	* @return string <p>Метод возвращает отформатированную строку сообщения.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $parser = new CSocNetTextParser(LANGUAGE_ID, "/bitrix/images/socialnetwork/smile/");
	* $parser-&gt;MaxStringLen = 20;
	* $message = $parser-&gt;convert($draftMessage);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettextparser/convert.php
	* @author Bitrix
	*/
	public function convert($text, $bPreview = True, $arImages = array(), $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y"), $type = "html")	//, "KEEP_AMP" => "N"
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
		if ($allow["HTML"] != "Y")
		{
			if ($allow["CODE"]=="Y")
			{
				$text = str_replace(array("\001", "\002", chr(5), chr(6), "'", "\""), array("", "", "", "", chr(5), chr(6)), $text);
				$text = preg_replace(
					array(
						"#<code(\s+[^>]*>|>)(.+?)</code(\s+[^>]*>|>)#is".BX_UTF_PCRE_MODIFIER,
						"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
						"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[code]\\2[/code]",
						"\001",
						"\002",
					),
					$text
				);
				$this->matchNum = 2;
				$text = preg_replace_callback(
					"/(?<=[\001])(([^\002]+))(?=([\002]))/is".BX_UTF_PCRE_MODIFIER, 
					array($this, "pre_convert_code_tag_callback"), 
					$text
				);
				$text = preg_replace(
					array(
						"/\001/",
						"/\002/"),
					array(
						"[code]",
						"[/code]"
					), $text
				);
				$text = str_replace(array(chr(5), chr(6)), array("'", "\""), $text);
			}
			if ($allow["ANCHOR"]=="Y")
			{
				$text = preg_replace(
					array(
						"#<a[^>]+href\s*=\s*[\"]+(([^\"])+)[\"]+[^>]*>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*[\']+(([^\'])+)[\']+[^>]*>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*(([^\'\"\>])+)>(.+?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
					"[url=\\1]\\3[/url]", $text);
			}
			if ($allow["BIU"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<b([^>]*)\>(.+?)\<\/b([^>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<u([^>]*)\>(.+?)\<\/u([^>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<s([^>a-z]*)\>(.+?)\<\/s([^>a-z]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<i([^>]*)\>(.+?)\<\/i([^>]*)>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[b]\\2[/b]",
						"[u]\\2[/u]",
						"[s]\\2[/s]",
						"[i]\\2[/i]"),
					$text);
			}
			if ($allow["IMG"]=="Y")
			{
				$text = preg_replace(
					"#<img[^>]+src\s*=[\s\"']*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\/[-_/=:.a-z0-9@{}&?%]+)+)[\s\"']*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
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
						"/\<font[^>]+size\s*=[\s\"']*([0-9]+)[\s\"']*[^>]*\>(.+?)\<\/font[^>]*\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+color\s*=[\s\"']*(\#[a-f0-9]{6})[^>]*\>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+face\s*=[\s\"']*([a-z\s\-]+)[\s\"']*[^>]*>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[size=\\1]\\2[/size]",
						"[color=\\1]\\2[/color]",
						"[font=\\1]\\2[/font]"),
					$text);
			}
			if ($allow["LIST"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<ul((\s[^>]*)|(\s*))\>(.+?)<\/ul([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<li((\s[^>]*)|(\s*))\>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[list]\\4[/list]",
						"[*]"),
					$text);
			}
			if (strLen($text)>0)
			{
				$text = str_replace(
					array("<", ">", "\""),
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
			$text = preg_replace("'(?<=^|[".$word_separator."]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\"\s\'\[\]\{\}\(\)])*)'is",
				"[url]\\1[/url]", $text);
		}
		if ($allow["CODE"] == "Y")
		{
			$text = preg_replace(
				array(
					"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER
				),
				array(
					"\001",
					"\002"
				), 
				$text
			);
			$this->matchNum = 2;
			$this->matchType = $type;
			$text = preg_replace_callback(
				"/(\001)([^\002]+)(\002)/is".BX_UTF_PCRE_MODIFIER, 
				array($this, "convert_code_tag_callback"), 
				$text
			);
			$text = preg_replace(
				array(
					"/\001/",
					"/\002/"
				),
				array(
					"[code]",
					"[/code]"
				), 
				$text
			);			
		}
		if ($allow["QUOTE"] == "Y")
		{
			$this->matchNum = 1;
			$this->matchType = $type;
			$text = preg_replace_callback(
				"#(\[quote([^\]])*\](.*)\[/quote([^\]])*\])#is", 
				array($this, "convert_quote_tag_callback"),
				$text
			);			
		}
		if ($allow["IMG"]=="Y")
		{
			$this->matchNum = 1;
			$this->matchType = $type;
			$text = preg_replace_callback(
				"#\[img\](.+?)\[/img\]#i",
				array($this, "convert_image_tag_callback"),
				$text
			);
		}
		if ($allow["ANCHOR"] == "Y")
		{
			$this->matchNum = 1;
			$this->matchNum2 = 1;
			$text = preg_replace_callback(
				"/\[url\]([^\]]+?)\[\/url\]/i".BX_UTF_PCRE_MODIFIER,
				array($this, "convert_anchor_tag_callback"),
				$text
			);
			$this->matchNum = 1;
			$this->matchNum2 = 2;
			$text = preg_replace_callback(
				"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/i".BX_UTF_PCRE_MODIFIER,
				array($this, "convert_anchor_tag_callback"),
				$text
			);
		}
		if ($allow["BIU"]=="Y")
		{
			$text = preg_replace(
				array(
					"/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER),
				array(
					"<b>\\1</b>",
					"<i>\\1</i>",
					"<s>\\1</s>",
					"<u>\\1</u>"), $text);
		}
		if ($allow["LIST"]=="Y")
		{
			$text = preg_replace(
				array(
					"/\[list\](.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\*\]/".BX_UTF_PCRE_MODIFIER),
				array(
					"<ul>\\1</ul>",
					"<li>"),
				$text);
		}
		if ($allow["FONT"]=="Y")
		{
			while (preg_match("/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/is".BX_UTF_PCRE_MODIFIER, $text))
			{
				$this->matchNum = 1;
				$this->matchNum2 = 2;
				$this->matchType = 'size';
				$text = preg_replace_callback(
					"/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/is".BX_UTF_PCRE_MODIFIER, 
					array($this, "convert_font_attr_callback"),
					$text
				);				
			}
			while (preg_match("/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/is".BX_UTF_PCRE_MODIFIER, $text))
			{
				$this->matchNum = 1;
				$this->matchNum2 = 2;
				$this->matchType = 'font';
				$text = preg_replace_callback(
					"/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/is".BX_UTF_PCRE_MODIFIER,
					array($this, "convert_font_attr_callback"),
					$text
				);
			}
			while (preg_match("/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/is".BX_UTF_PCRE_MODIFIER, $text))
			{
				$this->matchNum = 1;
				$this->matchNum2 = 2;
				$this->matchType = 'color';
				$text = preg_replace_callback(
					"/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/is".BX_UTF_PCRE_MODIFIER, 
					array($this, "convert_font_attr_callback"),
					$text
				);
			}
		}

		$text = str_replace(
			array(
				"(c)", "(C)",
				"(tm)", "(TM)", "(Tm)", "(tM)",
				"(r)", "(R)",
				"\n"
			),
			array(
				"&copy;", "&copy;",
				"&#153;", "&#153;", "&#153;", "&#153;",
				"&reg;", "&reg;",
				"<br />"
			), 
			$text
		);
		if ($this->MaxStringLen > 0)
		{
			$this->matchNum = 1;
			$text = preg_replace_callback(
				"/(?<=^|\>)([^\<]+)(?=\<|$)/is".BX_UTF_PCRE_MODIFIER, 
				array($this, "part_long_words_callback"),
				$text
			);
		}
		if ($allow["SMILES"]=="Y")
		{
			if (count($this->smiles) > 0)
			{
				if ($this->path_to_smile !== false)
				{
					$path_to_smile = $this->path_to_smile;
				}
				else
				{
					$path_to_smile = "/bitrix/images/socialnetwork/smile/";
				}

				$arSmiles = array();
				$arQuoted = array();
				foreach ($this->smiles as $a_id => $row)
				{
					if(strlen($row["TYPING"]) <= 0 || strlen($row["IMAGE"]) <= 0)
						continue;
					$typing = htmlspecialcharsbx($row["TYPING"]);
					$arSmiles[$typing] = '<img src="'.$path_to_smile.$row["IMAGE"].'" border="0" alt="smile'.$typing.'" title="'.htmlspecialcharsbx($row["DESCRIPTION"]).'" />';
					$arQuoted[] = preg_quote($typing, "/");
				}
				$ar = preg_split("/(?<=[\s>])(".implode("|", $arQuoted).")/".BX_UTF_PCRE_MODIFIER, " ".$text, -1, PREG_SPLIT_DELIM_CAPTURE);
				$text = "";
				foreach($ar as $piece)
				{
					if(array_key_exists($piece, $arSmiles))
						$text .= $arSmiles[$piece];
					else
						$text .= $piece;
				}
			}
		}
		if ($allow["VIDEO"] == "Y")
		{
			while (preg_match("/\[video(.+?)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER, $text))
			{
				$this->matchNum = 1;
				$this->matchNum2 = 2;
				$text = preg_replace_callback(
					"/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER,
					array($this, "convert_video_callback"),
					$text
				);
			}
		}
		return trim($text);
	}

	public static function killAllTags($text)
	{
		if (method_exists("CTextParser", "clearAllTags"))
			return CTextParser::clearAllTags($text);
		$text = strip_tags($text);
		$text = preg_replace(
			array(
				"/\<(\/?)(quote|code|font|color)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(b|u|i|list|code|quote|font|color|url|img)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER),
			"",
			$text);
		return $text;
	}

	
	/**
	* <p>Метод форматирования сообщения для отправки по электронной почте.</p>
	*
	*
	* @param string $text  Текст сообщения.
	*
	* @return string <p>Метод возвращает отформатированную строку сообщения.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettextparser/convert4mail.php
	* @author Bitrix
	*/
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

		$arPattern[] = "/\[(\/?)(color|font|size)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[url\](\S+?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1 )";

		$arPattern[] = "/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1 )";

		$arPattern[] = "/\[img\](.+?)\[\/img\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\[(\/?)list\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";
		$text = preg_replace($arPattern, $arReplace, $text);
		$text = str_replace("&shy;", "", $text);

		return $text;
	}

	public static function convert_video($params, $path)
	{
		if (strLen($path) <= 0)
			return "";

		preg_match("/width\=([0-9]+)/is", $params, $width);
		preg_match("/height\=([0-9]+)/is", $params, $height);
		$width = intval($width[1]);
		$width = ($width > 0 ? $width : 400);
		$height = intval($height[1]);
		$height = ($height > 0 ? $height : 300);

		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:player", "",
			Array(
				"PLAYER_TYPE" => "auto",
				"USE_PLAYLIST" => "N",
				"PATH" => $path,
				"WIDTH" => $width,
				"HEIGHT" => $height,
				"PREVIEW" => "",
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
		return $video;
	}

	private function convert_video_callback($m)
	{
		return $this->convert_video($m[$this->matchNum], $m[$this->matchNum2]);	
	}

	public function convert_emoticon($code = "", $image = "", $description = "", $servername = "")
	{
		if (strlen($code)<=0 || strlen($image)<=0) return;
		$code = stripslashes($code);
		$description = stripslashes($description);
		$image = stripslashes($image);
		if ($this->path_to_smile !== false)
			return '<img src="'.$servername.$this->path_to_smile.$image.'" border="0" alt="smile'.$code.'" title="'.$description.'" />';
		return '<img src="'.$servername.'/bitrix/images/socialnetwork/smile/'.$image.'" border="0" alt="smile'.$code.'" title="'.$description.'" />';
	}

	private function convert_emoticon_callback($m)
	{
		return $this->convert_emoticon($this->matchType, $this->matchType2, $this->matchType3, $this->matchType4);	
	}

	public static function pre_convert_code_tag ($text = "")
	{
		if (strLen($text) <= 0)
		{
			return;
		}
		$text = str_replace(
			array("&", "<", ">", "[", "]"), 
			array("&amp;", "&lt;", "&gt;", "&#91;", "&#93;"), 
			$text
		);
		return $text;
	}

	private function pre_convert_code_tag_callback($m)
	{
		return $this->pre_convert_code_tag($m[$this->matchNum]);
	}	

	public function convert_code_tag($text = "", $type = "html")
	{
		if (strLen($text)<=0) return;
		$type = ($type == "rss" ? "rss" : "html");
		$text = str_replace(array("<", ">", "\\r", "\\n", "\\"), array("&lt;", "&gt;", "&#92;r", "&#92;n", "&#92;"), $text);
		$text = stripslashes($text);
		$text = str_replace(array("  ", "\t", ), array("&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"), $text);
		$txt = $text;

		$this->matchType = 'code';
		$this->matchType2 = $type;
		$txt = preg_replace_callback(
			"/\[code\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "convert_open_tag_callback"),
			$txt
		);
		$this->matchType = 'code';
		$txt = preg_replace_callback(
			"/\[\/code\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "convert_close_tag_callback"),
			$txt
		);			

		if (
			($this->code_open == $this->code_closed) 
			&& ($this->code_error == 0)
		)
		{
			return $txt;
		}
		return $text;
	}
	
	private function convert_code_tag_callback($m)
	{
		return $this->convert_code_tag('[code]'.$m[$this->matchNum].'[/code]', $this->matchType);
	}

	public function convert_quote_tag($text = "", $type = "html")
	{
		if (strlen($text)<=0) return;
		$txt = $text;
		$type = ($type == "rss" ? "rss" : "html");

		$this->matchType = 'quote';
		$this->matchType2 = $type;
		$txt = preg_replace_callback(
			"/\[quote([^\]])*\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "convert_open_tag_callback"),
			$txt
		);
		$this->matchType = 'quote';
		$txt = preg_replace_callback(
			"/\[\/quote([^\]])*\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "convert_close_tag_callback"),
			$txt
		);			

		if (
			($this->quote_open == $this->quote_closed) 
			&& ($this->quote_error == 0)
		)
		{
			return $txt;
		}
		return $text;
	}
	
	private function convert_quote_tag_callback($m)
	{
		return $this->convert_quote_tag($m[$this->matchNum], $this->matchType);
	}

	public function convert_open_tag($marker = "quote", $type = "html")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$type = ($type == "rss" ? "rss" : "html");

		$this->{$marker."_open"}++;
		if ($type == "rss")
			return "\n====".$marker."====\n";
		return "<table class='sonet-".$marker."'><thead><tr><th>".($marker == "quote" ? GetMessage("SONET_QUOTE") : GetMessage("SONET_CODE"))."</th></tr></thead><tbody><tr><td>";
	}

	private function convert_open_tag_callback($m)
	{
		return $this->convert_open_tag($this->matchType, $this->matchType2);
	}

	public function convert_close_tag($marker = "quote")
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
		return "</td></tr></tbody></table>";
	}

	private function convert_close_tag_callback($m)
	{
		return $this->convert_close_tag($this->matchType);
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
		if (preg_match("/[?&;]/".BX_UTF_PCRE_MODIFIER, $url)) $bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/$extension(\||\$)/".BX_UTF_PCRE_MODIFIER, $this->allow_img_ext)) $bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/^(http|https|ftp|\/)/i".BX_UTF_PCRE_MODIFIER, $url)) $bErrorIMG = True;

		if ($bErrorIMG)
		{
			return "[img]".$url."[/img]";
		}

		return '<img src="'.$url.'" alt="'.GetMessage("FRM_IMAGE_ALT").'" border="0" />';
	}

	private function convert_image_tag_callback($m)
	{
		return $this->convert_image_tag($m[$this->matchNum], $this->matchType);
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
			return "<span style='font-size:".$this->arFontSize[$value]."%;'>".$text."</span>";
		}
		else if ($attr == 'color')
		{
			$value = preg_replace("/[^\w#]/", "" , $value);
			return "<font color='".$value."'>".$text."</font>";
		}
		else if ($attr == 'font')
		{
			$value = preg_replace("/[^\w]/", "" , $value);
			return "<font face='".$value."'>".$text."</font>";
		}
	}
	
	private function convert_font_attr_callback($m)
	{
		return $this->convert_font_attr($this->matchType, $m[$this->matchNum], $m[$this->matchNum2]);
	}

	// Only for public using
	public function wrap_long_words($text="")
	{
		if (
			$this->MaxStringLen > 0 
			&& !empty($text)
		)
		{
			$text = str_replace(array(chr(7), chr(8), chr(34), chr(39)), array("", "", chr(7), chr(8)), $text);
			$this->matchNum = 1;
			$text = preg_replace_callback(
				"/(?<=^|\>)([^\<]+)(?=\<|$)/is".BX_UTF_PCRE_MODIFIER, 
				array($this, "part_long_words_callback"),
				$text
			);
			$text = str_replace(array(chr(7), chr(8)), array(chr(34), chr(39)), $text);
		}
		return $text;
	}

	public function part_long_words($str)
	{
		$word_separator = $this->word_separator;
		if (($this->MaxStringLen > 0) && (strLen(trim($str)) > 0))
		{
			$str = str_replace(
				array(chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), "&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;"),
				array("", "", "", "", "", "", chr(5), "<", ">", chr(6), chr(1), chr(2), chr(3), chr(4)),
				$str
			);
			$this->matchNum = 2;
			$str = preg_replace_callback(
				"/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/is".BX_UTF_PCRE_MODIFIER, 
				array($this, "cut_long_words_callback"),
				$str
			);
			$str = str_replace(
				array(chr(5), "<", ">", chr(6), chr(1), chr(2), chr(3), chr(4), "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", "<WBR/>", "<WBR/>", "&shy;"),
				$str
			);
		}
		return $str;
	}
	
	private function part_long_words_callback($m)
	{
		return $this->cut_long_words($m[$this->matchNum]);
	}

	public function cut_long_words($str)
	{
		if (
			($this->MaxStringLen > 0) 
			&& (strLen($str) > 0)
		)
		{
			$str = preg_replace(
				"/([^ \n\r\t\x01]{".$this->MaxStringLen."})/is".BX_UTF_PCRE_MODIFIER, 
				"\\1<WBR/>&shy;", 
				$str
			);
		}
		return $str;
	}
	
	public function cut_long_words_callback($m)
	{
		return $this->cut_long_words($m[$this->matchNum]);
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
			array("/&amp;/".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array("&", "java script&#58; ") , $url);
		if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
			$url = 'http://'.$url;
		if (!preg_match("/^((http|https|news|ftp|aim):\/\/[-_:.a-z0-9@]+)*([^\"\'])+$/i".BX_UTF_PCRE_MODIFIER, $url))
			return $pref.$text." (".$url.")".$end;

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

		return $pref."<a href='".$url."' target='_blank'>".$text."</a>".$end;
	}

	public function convert_anchor_tag_callback($m)
	{
		return $this->convert_anchor_tag($m[$this->matchNum], $m[$this->matchNum2], '');
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

		if ($bAllowSmiles == "Y")
		{
			if (count($this->smiles) > 0)
			{
				foreach ($this->smiles as $a_id => $row)
				{
					$code  = preg_quote(str_replace("'", "\\'", $row["TYPING"]), "/");
					$image = preg_quote(str_replace("'", "\\'", $row["IMAGE"]));
					$description = preg_quote(htmlspecialcharsbx($row["DESCRIPTION"], ENT_QUOTES), "/");

					$this->matchType = $code;
					$this->matchType2 = $image;
					$this->matchType3 = $description;
					$this->matchType4 = "http://".$arParams["SERVER_NAME"];

					$text = preg_replace_callback(
						"/(?<=[^\w&])$code(?=.\W|\W.|\W$)/i", 
						array($this, "convert_emoticon_callback"), 
						$text
					);

				}
			}
		}
		return trim($text);
	}

	public static function strip_words($string, $count)
	{
		$result = "";
		$counter_plus  = true;
		$counter = 0;
		$string_len = strlen($string);
		for($i=0; $i<$string_len; ++$i)
		{
			$char = substr($string, $i, 1);
			if($char == '<')
				$counter_plus = false;
			if($char == '>' && substr($string, $i+1, 1) != '<')
			{
				$counter_plus = true;
				$counter--;
			}
			$result .= $char;
			if ($counter_plus)
				$counter++;
			if($counter >= $count)
			{
				$pos_space = strpos($string, " ", $i);
				$pos_tag = strpos($string, "<", $i);
				if ($pos_space == false)
				{
					$pos = strrpos($result, " ");
					$result = substr($result, 0, strlen($result)-($i-$pos+1));
				}
				else
				{
					$pos = min($pos_space, $pos_tag);
					if ($pos != $i)
					{
						$dop_str = substr($string, $i+1, $pos-$i-1);
						$result .= $dop_str;
					}
					else
						$result = substr($result, 0, strlen($result)-1);
				}
				break;
			}
		}
		return $result;
	}

	public static function closetags($html)
	{
		$arNoClose = array('br','hr','img','area','base','basefont','col','frame','input','isindex','link','meta','param');

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
			{
				if (!in_array($openedtags[$i], $arNoClose))
					$html .= '</'.$openedtags[$i].'>';
			}
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
		else
			$final_text = substr($html, 0, $size);

		return $final_text;
	}

}


/**
 * <b>CSocNetTools</b> - вспомогательный класс модуля социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettools/index.php
 * @author Bitrix
 */
class CSocNetTools
{
	
	/**
	* <p>Метод возвращает параметры изображения, заданного его идентификатором. При необходимости осуществляется масштабирование изображения. В случае отсутствия изображения возвращается изображение заданное как изображение по-умолчанию.</p>
	*
	*
	* @param int $imageID  Идентификатор изображения.
	*
	* @param int $imageSize  Размер изображения. В случае, если оригинальное изображение хотя
	* бы по одному измерению больше указанного размера, осуществляется
	* автоматическое масштабирование.
	*
	* @param string $defaultImage  Ссылка на изображение "по-умолчанию". Используется, если
	* изображение не найдено.
	*
	* @param int $defaultImageSize  Размер изображения "по-умолчанию".
	*
	* @param string $imageUrl  Ссылка, на которую браузер переходит при клике на изображении.
	* Может быть не задана.
	*
	* @param string $showImageUrl  Флаг, имеющий значение true, если необходимо показывать ссылку.
	* Иначе - false.
	*
	* @param string $urlParams = false Дополнительные параметры ссылки (тега <i>a</i>).
	*
	* @return array <p>Метод возвращает массив с ключами FILE и IMG. В ключе FILE содержится
	* массив, описывающий изображение (аналогичен массиву,
	* возвращаемому метолом CFile::GetFileArray). В ключе IMG содержится готовая
	* для вывода строка HTML, показывающая изображение.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$arImage = CSocNetTools::InitImage($personalPhoto, 150, "/bitrix/images/socialnetwork/nopic_user_150.gif", 150, "", false);<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettools/csocnettools_initimage.php
	* @author Bitrix
	*/
	public static function InitImage($imageID, $imageSize, $defaultImage, $defaultImageSize, $imageUrl, $showImageUrl, $urlParams=false)
	{
		$imageFile = false;
		$imageImg = "";

		$imageSize = intval($imageSize);
		if($imageSize <= 0)
			$imageSize = 100;

		$defaultImageSize = intval($defaultImageSize);
		if($defaultImageSize <= 0)
			$defaultImageSize = 100;

		$imageUrl = trim($imageUrl);
		$imageID = intval($imageID);

		if($imageID > 0)
		{
			$imageFile = CFile::GetFileArray($imageID);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $imageSize, "height" => $imageSize),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false
				);
				$imageImg = CFile::ShowImage($arFileTmp["src"], $imageSize, $imageSize, "border=0", "", ($imageUrl == ''));
			}
		}
		if($imageImg == '')
			$imageImg = "<img src=\"".$defaultImage."\" width=\"".$defaultImageSize."\" height=\"".$defaultImageSize."\" border=\"0\" alt=\"\" />";

		if($imageUrl <> '' && $showImageUrl)
			$imageImg = "<a href=\"".$imageUrl."\"".($urlParams !== false? ' '.$urlParams:'').">".$imageImg."</a>";

		return array("FILE" => $imageFile, "IMG" => $imageImg);
	}

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
					$res[$k] = htmlspecialcharsex($v);
					$res['~'.$k] = $v;
				}
			}
		}
		return $res;
	}

	
	/**
	* <p>Метод осуществляет масштабирование изображения, заданного в виде идентификатора или в виде массива, совпадающего по структуре с массивом, возвращаемым методом CFile::GetByID. Если размеры изображения превышают заданные, то осуществляется масштабирование.</p> <p><b>Примечание</b>: возможное примечание.</p>
	*
	*
	* @param mixed $aFile  Идентификатор изображения или в массив, совпадающий по структуре
	* с массивом, возвращаемым методом CFile::GetByID.
	*
	* @param int $sizeX  Масштабируемый размер по горизонтали.
	*
	* @param int $sizeY  Масштабируемый размер по вертикали.
	*
	* @return string <p>Метод возвращает путь к масштабируемому изображению
	* относительно корня сайта. В случае ошибки возвращается false.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettools/csocnettools_resizeimage.php
	* @author Bitrix
	*/
	public static function ResizeImage($aFile, $sizeX, $sizeY)
	{
		$result = CFile::ResizeImageGet($aFile, array("width" => $sizeX, "height" => $sizeY));
		if(is_array($result))
			return $result["src"];
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

	
	/**
	* <p>Подготавливает день рождения для вывода.</p>
	*
	*
	* @param date $datetime  Дата рождения
	*
	* @param char $gender  Пол. Допустимые значения: M - мужской, F - женский, X - средний.
	*
	* @param char $showYear = "N" Показывать ли год рождения. Допустимые значения: Y - показывать, M -
	* показывать только для мужского пола, N - не показывать.
	*
	* @return array <p>Метод возвращает массив с ключами: DATE - отформатированный день
	* рождения, MONTH - месяц рождения, DAY - день в месяце.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnettools/csocnettools_birthday.php
	* @author Bitrix
	*/
	public static function Birthday($datetime, $gender, $showYear = "N")
	{
		if (StrLen($datetime) <= 0)
			return false;

		$arDateTmp = ParseDateTime($datetime, CSite::GetDateFormat('SHORT'));

		$day = IntVal($arDateTmp["DD"]);
		if (isset($arDateTmp["M"]))
		{
			if (is_numeric($arDateTmp["M"]))
			{
				$month = IntVal($arDateTmp["M"]);
			}
			else
			{
				$month = GetNumMonth($arDateTmp["M"], true);
				if (!$month)
					$month = intval(date('m', strtotime($arDateTmp["M"])));
			}
		}
		elseif (isset($arDateTmp["MMMM"]))
		{
			if (is_numeric($arDateTmp["MMMM"]))
			{
				$month = intval($arDateTmp["MMMM"]);
			}
			else
			{
				$month = GetNumMonth($arDateTmp["MMMM"]);
				if (!$month)
					$month = intval(date('m', strtotime($arDateTmp["MMMM"])));
			}
		}
		else
		{
			$month = IntVal($arDateTmp["MM"]);
		}
		$arDateTmp["MM"] = $month;
		
		$year = IntVal($arDateTmp["YYYY"]);

		if (($showYear == 'Y') || ($showYear == 'M' && $gender == 'M'))
			$date_template = GetMessage("SONET_BIRTHDAY_DAY_TEMPLATE");
		else
			$date_template = GetMessage("SONET_BIRTHDAY_DAY_TEMPLATE_WO_YEAR");

		$val = str_replace(
			array("#DAY#", "#MONTH#", "#MONTH_LOW#", "#YEAR#"),
			array($day, GetMessage("MONTH_".$month."_S"), ToLower(GetMessage("MONTH_".$month."_S")), $year),
			$date_template
		);

		return array(
			"DATE" => $val,
			"MONTH" => Str_Pad(IntVal($arDateTmp["MM"]), 2, "0", STR_PAD_LEFT),
			"DAY" => Str_Pad(IntVal($arDateTmp["DD"]), 2, "0", STR_PAD_LEFT)
		);
	}

	public static function GetDefaultNameTemplates()
	{
		return array(
			'#NOBR##LAST_NAME# #NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_JOHN'),
			'#NOBR##LAST_NAME# #NAME##/NOBR# #SECOND_NAME#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_JOHN_LLOYD'),
			'#LAST_NAME#, #NOBR##NAME# #SECOND_NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_COMMA_JOHN_LLOYD'),
			'#NAME# #SECOND_NAME# #LAST_NAME#' => GetMessage('SONET_NAME_TEMPLATE_JOHN_LLOYD_SMITH'),
			'#NOBR##NAME_SHORT# #SECOND_NAME_SHORT# #LAST_NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_J_L_SMITH'),
			'#NOBR##NAME_SHORT# #LAST_NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_J_SMITH'),
			'#NOBR##LAST_NAME# #NAME_SHORT##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_J'),
			'#NOBR##LAST_NAME# #NAME_SHORT# #SECOND_NAME_SHORT##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_J_L'),
			'#NOBR##LAST_NAME#, #NAME_SHORT##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_COMMA_J'),
			'#NOBR##LAST_NAME#, #NAME_SHORT# #SECOND_NAME_SHORT##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_SMITH_COMMA_J_L'),
			'#NOBR##NAME# #LAST_NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_JOHN_SMITH'),
			'#NOBR##NAME# #SECOND_NAME_SHORT# #LAST_NAME##/NOBR#' => GetMessage('SONET_NAME_TEMPLATE_JOHN_L_SMITH'),
		);
	}

	public static function GetMyGroups()
	{
		$arGroupsMy = array();
		$dbRequests = CSocNetUserToGroup::GetList(
			array(),
			array(
				"USER_ID" 		=> $GLOBALS["USER"]->GetID(),
				"<=ROLE" 		=> SONET_ROLES_USER,
				"GROUP_ACTIVE"	=> "Y"
			),
			false,
			false,
			array("GROUP_ID")
		);
		while ($arRequests = $dbRequests->Fetch())
			$arGroupsMy[] = $arRequests["GROUP_ID"];

		return $arGroupsMy;
	}

	public static function GetGroupUsers($group_id)
	{
		if (intval($group_id) <= 0)
			return false;

		$arGroupUsers = array();
		$dbRequests = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" 		=> $group_id,
				"<=ROLE" 		=> SONET_ROLES_USER,
				"USER_ACTIVE"	=> "Y"
			),
			false,
			false,
			array("USER_ID")
		);
		while ($arRequests = $dbRequests->Fetch())
			$arGroupUsers[] = $arRequests["USER_ID"];

		return $arGroupUsers;
	}

	public static function IsMyGroup($entity_id)
	{
		$is_my = false;
		$dbRequests = CSocNetUserToGroup::GetList(
			array(),
			array(
				"USER_ID" 		=> $GLOBALS["USER"]->GetID(),
				"GROUP_ID" 		=> $entity_id,
				"<=ROLE" 		=> SONET_ROLES_USER,
			)
		);
		if ($arRequests = $dbRequests->Fetch())
			$is_my = true;

		return $is_my;
	}

	public static function GetMyUsers($user_id = false)
	{
		if (!$user_id)
			$user_id = $GLOBALS["USER"]->GetID();

		$arUsersMy = false;
		if (CSocNetUser::IsFriendsAllowed())
		{
			$arUsersMy = array();
			$dbFriends = CSocNetUserRelations::GetRelatedUsers($user_id, SONET_RELATIONS_FRIEND);
			if ($dbFriends)
				while ($arFriends = $dbFriends->Fetch())
				{
					$pref = (($user_id == $arFriends["FIRST_USER_ID"]) ? "SECOND" : "FIRST");
					$arUsersMy[] = $arFriends[$pref."_USER_ID"];
				}
		}
		return $arUsersMy;
	}

	public static function IsMyUser($entity_id)
	{
		$is_my = false;
		if (
			CSocNetUser::IsFriendsAllowed()
			&& CSocNetUserRelations::IsFriends($GLOBALS["USER"]->GetID(), $entity_id)
		)
			$is_my = true;

		return $is_my;
	}

	public static function HasLogEventCreatedBy($event_id)
	{
		return CSocNetLogTools:: HasLogEventCreatedBy($event_id);
	}

	public static function InitGlobalExtranetArrays($SITE_ID = SITE_ID)
	{
		if (
			!isset($GLOBALS["arExtranetGroupID"])
			|| !isset($GLOBALS["arExtranetUserID"])
		)
		{
			$GLOBALS["arExtranetGroupID"] = array();
			$GLOBALS["arExtranetUserID"] = array();

			if($GLOBALS["USER"]->IsAuthorized())
			{
				$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
				$cache_id = 'sonet_ex_gr_'.$SITE_ID;
				$obCache = new CPHPCache;
				$cache_dir = '/bitrix/sonet_log_sg';

				if($obCache->InitCache($ttl, $cache_id, $cache_dir))
				{
					$tmpVal = $obCache->GetVars();
					$GLOBALS["arExtranetGroupID"] = $tmpVal['EX_GROUP_ID'];
					$GLOBALS["arExtranetUserID"] = $tmpVal['EX_USER_ID'];
					unset($tmpVal);
				}
				elseif (CModule::IncludeModule("extranet"))
				{
					global $CACHE_MANAGER;
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->StartTagCache($cache_dir);
					}

					if (!CExtranet::IsExtranetSite())
					{
						$dbGroupTmp = CSocNetGroup::GetList(
							array(),
							array(
								"SITE_ID" => CExtranet::GetExtranetSiteID()
							),
							false,
							false,
							array("ID")
						);
						while($arGroupTmp = $dbGroupTmp->Fetch())
						{
							$GLOBALS["arExtranetGroupID"][] = $arGroupTmp["ID"];
							if (defined("BX_COMP_MANAGED_CACHE"))
							{
								$CACHE_MANAGER->RegisterTag('sonet_group_'.$arGroupTmp["ID"]);
							}
						}

						if (defined("BX_COMP_MANAGED_CACHE"))
						{
							$CACHE_MANAGER->RegisterTag('sonet_group');
						}
					}

					$rsUsers = CUser::GetList(
						($by="ID"),
						($order="asc"),
						array(
							"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID()),
							"UF_DEPARTMENT" => false
						),
						array("FIELDS" => array("ID"))
					);
					while($arUser = $rsUsers->Fetch())
					{
						$GLOBALS["arExtranetUserID"][] = $arUser["ID"];
						if (defined("BX_COMP_MANAGED_CACHE"))
						{
							$CACHE_MANAGER->RegisterTag('sonet_user2group');
						}
					}

					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->EndTagCache();
					}

					if($obCache->StartDataCache())
					{
						$obCache->EndDataCache(array(
							'EX_GROUP_ID' => $GLOBALS["arExtranetGroupID"],
							'EX_USER_ID' => $GLOBALS["arExtranetUserID"]
						));
					}
				}
			}
		}
	}

	public static function GetSubordinateGroups($userID = false)
	{
		static $arSubordinateGroupsByUser = array();

		$userID = intval($userID);
		if ($userID <= 0)
		{
			$userID = $GLOBALS["USER"]->GetID();
		}

		if ($userID <= 0)
		{
			return array();
		}

		if (isset($arSubordinateGroupsByUser[$userID]))
		{
			$arUserSubordinateGroups = $arSubordinateGroupsByUser[$userID];
		}
		else
		{
			$arUserSubordinateGroups = Array(2);
			$arUserGroups_u = CUser::GetUserGroup($userID);
			for ($j = 0,$len = count($arUserGroups_u); $j < $len; $j++)
			{
				$arSubordinateGroups = CGroup::GetSubordinateGroups($arUserGroups_u[$j]);
				$arUserSubordinateGroups = array_merge ($arUserSubordinateGroups, $arSubordinateGroups);
			}
			$arUserSubordinateGroups = array_unique($arUserSubordinateGroups);

			$arSubordinateGroupsByUser[$userID] = $arUserSubordinateGroups;
		}

		return $arUserSubordinateGroups;
	}
}

class CSocNetAllowed
{
	private static $arAllowedEntityTypes = array();
	private static $arAllowedEntityTypesDesc = array();
	private static $arAllowedFeatures = array();
	private static $arAllowedLogEvents = array();

	/* --- entity types --- */

	public static function RunEventForAllowedEntityType()
	{
		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc = array();

		$newAllowedEntityTypes = array();

		$events = GetModuleEvents("socialnetwork", "OnFillSocNetAllowedSubscribeEntityTypes");
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$newAllowedEntityTypes));
		}

		foreach($newAllowedEntityTypes as $entityType)
		{
			self::AddAllowedEntityType($entityType);
		}

		foreach($arSocNetAllowedSubscribeEntityTypesDesc as $entityTypeDescCode => $arEntityTypeDesc)
		{
			self::AddAllowedEntityTypeDesc($entityTypeDescCode, $arEntityTypeDesc);
		}

		unset($arSocNetAllowedSubscribeEntityTypesDesc);
	}

	public static function AddAllowedEntityType($entityType)
	{
		if (is_array($entityType))
		{
			foreach ($entityType as $tmp)
			{
				self::AddAllowedEntityType($tmp);
			}
			return true;
		}

		$entityType = trim($entityType);
		if (
			strlen($entityType) <= 0
			|| in_array($entityType, self::$arAllowedEntityTypes)
			|| !preg_match('/^[a-zA-Z0-9]+$/', $entityType)
		)
		{
			return false;
		}

		if (
			$entityType == SONET_SUBSCRIBE_ENTITY_GROUP
			&& !CBXFeatures::IsFeatureEnabled("Workgroups")
		)
		{
			return false;
		}

		self::$arAllowedEntityTypes[] = $entityType;
	}

	public static function GetAllowedEntityTypes()
	{
		self::RunEvents();
		return self::$arAllowedEntityTypes;
	}

	/* --- entity types desc --- */

	public static function AddAllowedEntityTypeDesc($entityTypeDescCode, $arEntityTypeDesc)
	{
		$entityTypeDescCode = trim($entityTypeDescCode);

		if (
			strlen($entityTypeDescCode) <= 0
			|| array_key_exists($entityTypeDescCode, self::$arAllowedEntityTypesDesc)
			|| !is_array($arEntityTypeDesc)
		)
		{
			return false;
		}

		if (
			$entityTypeDescCode == SONET_SUBSCRIBE_ENTITY_GROUP
			&& !CBXFeatures::IsFeatureEnabled("Workgroups")
		)
		{
			return false;
		}

		self::$arAllowedEntityTypesDesc[$entityTypeDescCode] = $arEntityTypeDesc;
	}

	public static function GetAllowedEntityTypesDesc()
	{
		self::RunEvents();
		return self::$arAllowedEntityTypesDesc;
	}

	/* --- features --- */

	public static function RunEventForAllowedFeature()
	{
		$newAllowedFeatures = array();

		$events = GetModuleEvents("socialnetwork", "OnFillSocNetFeaturesList");
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$newAllowedFeatures, SITE_ID));
		}	

		foreach($newAllowedFeatures as $strFeatureCode => $arFeature)
		{
			self::AddAllowedFeature($strFeatureCode, $arFeature);
		}
	}

	public static function AddAllowedFeature($strFeatureCode, $arFeature)
	{
		$strFeatureCode = trim($strFeatureCode);

		if (
			strlen($strFeatureCode) <= 0
			|| !is_array($arFeature)
		)
		{
			return false;
		}

		if (
			!CBXFeatures::IsFeatureEnabled("Workgroups")
			&& array_key_exists("subscribe_events", $arFeature)
		)
		{
			foreach ($arFeature["subscribe_events"] as $event_id_tmp => $arEventTmp)
			{
				if (
					array_key_exists("ENTITIES", $arEventTmp)
					&& array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arEventTmp["ENTITIES"])
				)
				{
					unset($arFeature["subscribe_events"][$event_id_tmp]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP]);
				}
			}
		}

		if (!array_key_exists($strFeatureCode, self::$arAllowedFeatures))
		{
			self::$arAllowedFeatures[$strFeatureCode] = $arFeature;
		}
		else
		{
			if (
				array_key_exists("operations", $arFeature)
				&& is_array($arFeature["operations"])
			)
			{
				if (!array_key_exists("operations", self::$arAllowedFeatures[$strFeatureCode]))
				{
					self::$arAllowedFeatures[$strFeatureCode]["operations"] = array();
				}

				foreach ($arFeature["operations"] as $strOpCode => $arOperation)
				{
					if (is_array($arOperation))
					{
						if (!array_key_exists($strOpCode, self::$arAllowedFeatures[$strFeatureCode]["operations"]))
						{
							self::$arAllowedFeatures[$strFeatureCode]["operations"][$strOpCode] = array();
						}

						foreach ($arOperation as $key => $value)
						{
							self::$arAllowedFeatures[$strFeatureCode]["operations"][$strOpCode][$key] = $value;
						}
					}
				}
			}

			if (
				array_key_exists("subscribe_events", $arFeature)
				&& is_array($arFeature["subscribe_events"])
			)
			{
				if (!array_key_exists("subscribe_events", self::$arAllowedFeatures[$strFeatureCode]))
				{
					self::$arAllowedFeatures[$strFeatureCode]["subscribe_events"] = array();
				}

				foreach ($arFeature["subscribe_events"] as $strEventCode => $arEvent)
				{
					if (is_array($arEvent))
					{
						self::$arAllowedFeatures[$strFeatureCode]["subscribe_events"][$strEventCode] = $arEvent;
					}
				}
			}
		}
	}

	public static function UpdateAllowedFeature($strFeatureCode, $arFeature)
	{
		$strFeatureCode = trim($strFeatureCode);

		if (
			strlen($strFeatureCode) <= 0
			|| !array_key_exists($strFeatureCode, self::$arAllowedFeatures)
			|| !is_array($arFeature)
		)
		{
			return false;
		}

		if (
			!CBXFeatures::IsFeatureEnabled("Workgroups")
			&& array_key_exists("subscribe_events", $arFeature)
		)
		{
			foreach ($arFeature["subscribe_events"] as $event_id_tmp => $arEventTmp)
			{
				if (
					array_key_exists("ENTITIES", $arEventTmp)
					&& array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arEventTmp["ENTITIES"])
				)
				{
					unset($arFeature["subscribe_events"][$event_id_tmp]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP]);
				}
			}
		}

		self::$arAllowedFeatures[$strFeatureCode] = $arFeature;
	}

	public static function GetAllowedFeatures()
	{
		self::RunEvents();
		return self::$arAllowedFeatures;
	}

	/* --- log events --- */

	public static function RunEventForAllowedLogEvent()
	{
		$newAllowedLogEvent = array();

		$events = GetModuleEvents("socialnetwork", "OnFillSocNetLogEvents");
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$newAllowedLogEvent));
		}

		foreach($newAllowedLogEvent as $strEventCode => $arLogEvent)
		{
			self::AddAllowedLogEvent($strEventCode, $arLogEvent);
		}
	}

	public static function AddAllowedLogEvent($strEventCode, $arLogEvent)
	{
		$strEventCode = trim($strEventCode);

		if (
			strlen($strEventCode) <= 0
			|| array_key_exists($strEventCode, self::$arAllowedLogEvents)
			|| !is_array($arLogEvent)
		)
		{
			return false;
		}
		
		if (!CBXFeatures::IsFeatureEnabled("Workgroups"))
		{
			if ($strEventCode == "system_groups")
			{
				return false;
			}

			if (
				array_key_exists("ENTITIES", $arLogEvent)
				&& array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arLogEvent["ENTITIES"])
			)
			{
				unset($arLogEvent["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP]);
			}

			if ($strEventCode == "system")
			{
				foreach($arLogEvent["FULL_SET"] as $i => $event_id_tmp)
				{
					if ($event_id_tmp == "system_groups")
					{
						unset($arLogEvent["FULL_SET"][$i]);
					}
				}
			}
		}

		if (!CBXFeatures::IsFeatureEnabled("Friends"))
		{
			if ($strEventCode == "system_friends")
			{
				return false;
			}

			if ($strEventCode == "system")
			{
				foreach($arLogEvent["FULL_SET"] as $i => $event_id_tmp)
				{
					if ($event_id_tmp == "system_friends")
					{
						unset($arLogEvent["FULL_SET"][$i]);
					}
				}
			}
		}

		self::$arAllowedLogEvents[$strEventCode] = $arLogEvent;
	}
	
	public static function GetAllowedLogEvents()
	{
		self::RunEvents();
		return self::$arAllowedLogEvents;
	}

	function RunEvents()
	{
		static $bAlreadyRun;

		if (!$bAlreadyRun)
		{
			self::RunEventForAllowedEntityType();
			self::RunEventForAllowedFeature();
			self::RunEventForAllowedLogEvent();
			$bAlreadyRun = true;
		}
	}
}

?>