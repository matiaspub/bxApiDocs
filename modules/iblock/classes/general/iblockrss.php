<?

/**
 * <b>CIBlockRSS</b> - класс для работы с RSS лентами.    <br>
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php
 * @author Bitrix
 */
class CAllIBlockRSS
{
	public static function GetRSSNodes()
	{
		return array("title", "link", "description", "enclosure", "enclosure_length", "enclosure_type", "category", "pubDate");
	}

	public static function Delete($IBLOCK_ID)
	{
		global $DB;
		$IBLOCK_ID = IntVal($IBLOCK_ID);
		$DB->Query("DELETE FROM b_iblock_rss WHERE IBLOCK_ID = ".$IBLOCK_ID);
	}

	public static function GetNodeList($IBLOCK_ID)
	{
		global $DB;
		$IBLOCK_ID = IntVal($IBLOCK_ID);
		$arCurNodesRSS = array();
		$db_res = $DB->Query(
			"SELECT NODE, NODE_VALUE ".
			"FROM b_iblock_rss ".
			"WHERE IBLOCK_ID = ".$IBLOCK_ID);
		while ($db_res_arr = $db_res->Fetch())
		{
			$arCurNodesRSS[$db_res_arr["NODE"]] = $db_res_arr["NODE_VALUE"];
		}
		return $arCurNodesRSS;
	}

	
	/**
	* <p>Загружает xml c указанного адреса и разбирает его в массив. В качестве значения user-agent'а используется "BitrixSMRSS". После загрузки xml будет конвертирован в кодировку текущего сайта. Если во время работы метода возникли ошибки, то массив результата будет пустым. Нестатический метод.   <br></p>   <p></p> <div class="note"> <b>Примечание</b>: xml кешируется на время указанное в элементе ttl или если время не указано, то на один час.</div>
	*
	*
	* @param string $SITE  IP-адрес или доменное имя сайта.
	*
	* @param string $PORT  Номер порта, к которому будет выполнено подключение. HTTP порт по
	* умолчанию -  80.
	*
	* @param string $PATH  Объединяются через знак вопроса ("?") и передаются HTTP команде GET.
	*
	* @param string $QUERY_STR  Требуется указать true, если новости находятся вне элемента channel.
	*
	* @param bool $bOutChannel = false 
	*
	* @return array <p>Массив представления xml.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arRSS = CIBlockRSS::GetNewsEx('www.1c-bitrix.ru', '80', '/bitrix/rss.php', 'ID=news_sm&amp;LANG=ru&amp;TYPE=news&amp;LIMIT=5');<br>print_r($arRSS);<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS::</a><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/formatarray.php">FormatArray</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php
	* @author Bitrix
	*/
	public static function GetNewsEx($SITE, $PORT, $PATH, $QUERY_STR, $bOutChannel = False)
	{
		global $APPLICATION;

		$text = "";

		$cacheKey = md5($SITE.$PORT.$PATH.$QUERY_STR);

		$bValid = False;
		$bUpdate = False;
		if ($db_res_arr = CIBlockRSS::GetCache($cacheKey))
		{
			$bUpdate = True;
			if (strlen($db_res_arr["CACHE"])>0)
			{
				if ($db_res_arr["VALID"]=="Y")
				{
					$bValid = True;
					$text = $db_res_arr["CACHE"];
				}
			}
		}

		if (!$bValid)
		{
			$http = new \Bitrix\Main\Web\HttpClient(array(
				"socketTimeout" => 120,
			));
			$http->setHeader("User-Agent", "BitrixSMRSS");
			$text = $http->get($SITE.":".$PORT.$PATH.(strlen($QUERY_STR) > 0? "?".$QUERY_STR: ""));

			if ($text)
			{
				$rss_charset = "windows-1251";
				if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $text, $matches))
				{
					$rss_charset = Trim($matches[1]);
				}
				else
				{
					$headers = $http->getHeaders();
					$ct = $headers->get("Content-Type");
					if (preg_match("#charset=([a-zA-Z0-9-]+)#m", $ct, $match))
						$rss_charset = $match[1];
				}

				$text = preg_replace("/<!DOCTYPE.*?>/i", "", $text);
				$text = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $text);
				$text = $APPLICATION->ConvertCharset($text, $rss_charset, SITE_CHARSET);
			}
		}

		if ($text != "")
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");
			$objXML = new CDataXML();
			$res = $objXML->LoadString($text);
			if($res !== false)
			{
				$ar = $objXML->GetArray();
				if (!$bOutChannel)
				{
					if (
						is_array($ar) && isset($ar["rss"])
						&& is_array($ar["rss"]) && isset($ar["rss"]["#"])
						&& is_array($ar["rss"]["#"]) && isset($ar["rss"]["#"]["channel"])
						&& is_array($ar["rss"]["#"]["channel"]) && isset($ar["rss"]["#"]["channel"][0])
						&& is_array($ar["rss"]["#"]["channel"][0]) && isset($ar["rss"]["#"]["channel"][0]["#"])
					)
						$arRes = $ar["rss"]["#"]["channel"][0]["#"];
					else
						$arRes = array();
				}
				else
				{
					if (
						is_array($ar) && isset($ar["rss"])
						&& is_array($ar["rss"]) && isset($ar["rss"]["#"])
					)
						$arRes = $ar["rss"]["#"];
					else
						$arRes = array();
				}

				$arRes["rss_charset"] = strtolower(SITE_CHARSET);

				if (!$bValid)
				{
					$ttl = (strlen($arRes["ttl"][0]["#"]) > 0)? IntVal($arRes["ttl"][0]["#"]): 60;
					CIBlockRSS::UpdateCache($cacheKey, $text, array("minutes" => $ttl), $bUpdate);
				}
			}
			return $arRes;
		}
		else
		{
			return array();
		}
	}

	public static function GetNews($ID, $LANG, $TYPE, $SITE, $PORT, $PATH, $LIMIT = 0)
	{
		if (IntVal($ID)>0)
		{
			$ID = IntVal($ID);
		}
		else
		{
			$ID = Trim($ID);
		}
		$LANG = Trim($LANG);
		$TYPE = Trim($TYPE);
		$LIMIT = IntVal($LIMIT);

		return CIBlockRSS::GetNewsEx($SITE, $PORT, $PATH, "ID=".$ID."&LANG=".$LANG."&TYPE=".$TYPE."&LIMIT=".$LIMIT);
	}

	
	/**
	* <p>Метод преобразует результат метода <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php">GetNewsEx</a> в более приемлемое представление. Нестатический метод.</p>
	*
	*
	* @param array $arRes  Массив описания xml. Результат работы метода <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php">GetNewsEx.</a>
	*
	* @param array $bOutChannel = false Параметр должен быть синхронизирован с одноименным метода <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php">GetNewsEx</a>.
	*
	* @return array <p>Массив следующего вида:</p><ul> <li>title - заголовок rss ленты;</li>     <li>link
	* - ссылка;</li>     <li>description - описание;</li>     <li>lastBuildDate - время в rss
	* формате (см. <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/xmldate2dec.php">XMLDate2Dec</a>);</li>     <li>ttl -
	* время действия в минутах;</li>     <li>image - описание картинки:</li>     <ul>
	* <li>title - заголовок;</li>         <li>url;</li>         <li>link - ссылка;</li>         <li>width -
	* ширина;</li>         <li>height - высота;</li>    </ul> <li>item - массив элементами
	* которого являются нововсти:</li>     <ul> <li>title - заголовок новости;       
	* <br> </li>         <li>link - ссылка;        <br> </li>         <li>description - подробное
	* описание;        <br> </li>         <li>enclosure - вложение (не обязательно):</li>        
	* <ul> <li>url;</li>             <li>length;</li>             <li>type;</li>             <li>width - не
	* обязательно;</li>             <li>height - не обязательно;</li>      </ul> <li>category -
	* категория;        <br> </li>         <li>pubDate - время в rss формате (см. <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/xmldate2dec.php">XMLDate2Dec</a>);</li>    </ul> </ul>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arXML = CIBlockRSS::GetNewsEx('www.1c-bitrix.ru', '80', '/bitrix/rss.php', 'ID=news_sm&amp;LANG=ru&amp;TYPE=news&amp;LIMIT=5');<br>if(count($arXML) &gt; 0)<br>{<br>    $arRSS = CIBlockRSS::FormatArray($arXML);<br>    print_r($arRSS);<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php">GetNewsEx</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/xmldate2dec.php">XMLDate2Dec</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/formatarray.php
	* @author Bitrix
	*/
	public static function FormatArray(&$arRes, $bOutChannel=false)
	{
		if (!$bOutChannel)
		{
			if(is_array($arRes["title"][0]["#"]))
				$arRes["title"][0]["#"] = $arRes["title"][0]["#"]["cdata-section"][0]["#"];
			if(is_array($arRes["link"][0]["#"]))
				$arRes["link"][0]["#"] = $arRes["link"][0]["#"]["cdata-section"][0]["#"];
			if(is_array($arRes["description"][0]["#"]))
				$arRes["description"][0]["#"] = $arRes["description"][0]["#"]["cdata-section"][0]["#"];

			$arResult = array(
				"title" => $arRes["title"][0]["#"],
				"link" => $arRes["link"][0]["#"],
				"description" => $arRes["description"][0]["#"],
				"lastBuildDate" => $arRes["lastBuildDate"][0]["#"],
				"ttl" => $arRes["ttl"][0]["#"],
			);

			if ($arRes["image"])
			{
				if(is_array($arRes["image"][0]["#"]))
				{
					$arResult["image"]["title"] = $arRes["image"][0]["#"]["title"][0]["#"];
					$arResult["image"]["url"] = $arRes["image"][0]["#"]["url"][0]["#"];
					$arResult["image"]["link"] = $arRes["image"][0]["#"]["link"][0]["#"];
					$arResult["image"]["width"] = $arRes["image"][0]["#"]["width"][0]["#"];
					$arResult["image"]["height"] = $arRes["image"][0]["#"]["height"][0]["#"];
				}
				elseif(is_array($arRes["image"][0]["@"]))
				{
					$arResult["image"]["title"] = $arRes["image"][0]["@"]["title"];
					$arResult["image"]["url"] = $arRes["image"][0]["@"]["url"];
					$arResult["image"]["link"] = $arRes["image"][0]["@"]["link"];
					$arResult["image"]["width"] = $arRes["image"][0]["@"]["width"];
					$arResult["image"]["height"] = $arRes["image"][0]["@"]["height"];
				}
			}

			foreach($arRes["item"] as $i => $arItem)
			{
				if(!is_array($arItem) || !is_array($arItem["#"]))
					continue;

				if(is_array($arItem["#"]["title"][0]["#"]))
					$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];

				if(is_array($arItem["#"]["description"][0]["#"]))
					$arItem["#"]["description"][0]["#"] = $arItem["#"]["description"][0]["#"]["cdata-section"][0]["#"];
				elseif(is_array($arItem["#"]["encoded"][0]["#"]))
					$arItem["#"]["description"][0]["#"] = $arItem["#"]["encoded"][0]["#"]["cdata-section"][0]["#"];
				$arResult["item"][$i]["description"] = $arItem["#"]["description"][0]["#"];

				if(is_array($arItem["#"]["title"][0]["#"]))
					$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];
				$arResult["item"][$i]["title"] = $arItem["#"]["title"][0]["#"];

				if(is_array($arItem["#"]["link"][0]["#"]))
					$arItem["#"]["link"][0]["#"] = $arItem["#"]["link"][0]["#"]["cdata-section"][0]["#"];
				$arResult["item"][$i]["link"] = $arItem["#"]["link"][0]["#"];

				if ($arItem["#"]["enclosure"])
				{
					$arResult["item"][$i]["enclosure"]["url"] = $arItem["#"]["enclosure"][0]["@"]["url"];
					$arResult["item"][$i]["enclosure"]["length"] = $arItem["#"]["enclosure"][0]["@"]["length"];
					$arResult["item"][$i]["enclosure"]["type"] = $arItem["#"]["enclosure"][0]["@"]["type"];
					if ($arItem["#"]["enclosure"][0]["@"]["width"])
					{
						$arResult["item"][$i]["enclosure"]["width"] = $arItem["#"]["enclosure"][0]["@"]["width"];
					}
					if ($arItem["#"]["enclosure"][0]["@"]["height"])
					{
						$arResult["item"][$i]["enclosure"]["height"] = $arItem["#"]["enclosure"][0]["@"]["height"];
					}
				}
				$arResult["item"][$i]["category"] = $arItem["#"]["category"][0]["#"];
				$arResult["item"][$i]["pubDate"] = $arItem["#"]["pubDate"][0]["#"];

				$arRes["item"][$i] = $arItem;
			}
		}
		else
		{
			$arResult = array(
				"title" => $arRes["channel"][0]["#"]["title"][0]["#"],
				"link" => $arRes["channel"][0]["#"]["link"][0]["#"],
				"description" => $arRes["channel"][0]["#"]["description"][0]["#"],
				"lastBuildDate" => $arRes["channel"][0]["#"]["lastBuildDate"][0]["#"],
				"ttl" => $arRes["channel"][0]["#"]["ttl"][0]["#"],
			);

			if ($arRes["image"])
			{
				$arResult["image"]["title"] = $arRes["image"][0]["#"]["title"][0]["#"];
				$arResult["image"]["url"] = $arRes["image"][0]["#"]["url"][0]["#"];
				$arResult["image"]["link"] = $arRes["image"][0]["#"]["link"][0]["#"];
				$arResult["image"]["width"] = $arRes["image"][0]["#"]["width"][0]["#"];
				$arResult["image"]["height"] = $arRes["image"][0]["#"]["height"][0]["#"];
			}

			foreach($arRes["item"] as $i => $arItem)
			{
				if(!is_array($arItem) || !is_array($arItem["#"]))
					continue;

				if(is_array($arItem["#"]["title"][0]["#"]))
					$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];

				if(is_array($arItem["#"]["description"][0]["#"]))
					$arItem["#"]["description"][0]["#"] = $arItem["#"]["description"][0]["#"]["cdata-section"][0]["#"];
				elseif(is_array($arItem["#"]["encoded"][0]["#"]))
					$arItem["#"]["description"][0]["#"] = $arItem["#"]["encoded"][0]["#"]["cdata-section"][0]["#"];
				$arResult["item"][$i]["description"] = $arItem["#"]["description"][0]["#"];

				$arResult["item"][$i]["title"] = $arItem["#"]["title"][0]["#"];
				$arResult["item"][$i]["link"] = $arItem["#"]["link"][0]["#"];
				if ($arItem["#"]["enclosure"])
				{
					$arResult["item"][$i]["enclosure"]["url"] = $arItem["#"]["enclosure"][0]["@"]["url"];
					$arResult["item"][$i]["enclosure"]["length"] = $arItem["#"]["enclosure"][0]["@"]["length"];
					$arResult["item"][$i]["enclosure"]["type"] = $arItem["#"]["enclosure"][0]["@"]["type"];
					if ($arItem["#"]["enclosure"][0]["@"]["width"])
					{
						$arResult["item"][$i]["enclosure"]["width"] = $arItem["#"]["enclosure"][0]["@"]["width"];
					}
					if ($arItem["#"]["enclosure"][0]["@"]["height"])
					{
						$arResult["item"][$i]["enclosure"]["height"] = $arItem["#"]["enclosure"][0]["@"]["height"];
					}
				}
				$arResult["item"][$i]["category"] = $arItem["#"]["category"][0]["#"];
				$arResult["item"][$i]["pubDate"] = $arItem["#"]["pubDate"][0]["#"];

				$arRes["item"][$i] = $arItem;
			}
		}
		return $arResult;
	}

	
	/**
	* <p>Преобразует дату из rss формата в формат "DD.MM.YYYY". Нестатический метод.</p>   <p></p> <div class="note"> <b>Примечание</b>: под rss форматом даты понимается формат, описанный в rfc 822.</div>
	*
	*
	* @param string $dateXML  rss дата/время.
	*
	* @param string $dateFormat = "DD.MM.YYYY" Формат даты. Необязательный параметр. По умолчанию используется
	* формат  "DD.MM.YYYY".
	*
	* @return string <p>строка.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arXML = CIBlockRSS::GetNewsEx('www.1c-bitrix.ru', '80', '/bitrix/rss.php', 'ID=news_sm&amp;LANG=ru&amp;TYPE=news&amp;LIMIT=5');<br>if(count($arXML) &gt; 0)<br>{<br>    $arRSS = CIBlockRSS::FormatArray($arXML);<br>    foreach($arRSS["item"] as $arItem)<br>    {<br>        echo $arItem["title"].":".CIBlockRSS::XMLDate2Dec($arItem["pubDate"]);<br>    }<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://www.w3.org/Protocols/rfc822/" >http://www.w3.org/Protocols/rfc822/</a></li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/getnewsex.php">GetNewsEx</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/index.php">CIBlockRSS</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/formatarray.php">FormatArray</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockrss/xmldate2dec.php
	* @author Bitrix
	*/
	public static function XMLDate2Dec($date_XML, $dateFormat = "DD.MM.YYYY")
	{
		static $MonthChar2Num = Array("","jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");

		if(preg_match("/(\\d+)\\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\\s+(\\d+)/i", $date_XML, $match))
			$timestamp = mktime(0, 0, 0, array_search(strtolower($match[2]), $MonthChar2Num), $match[1], $match[3]);
		else
			$timestamp = time();

		return  date(CDatabase::DateFormatToPHP($dateFormat), $timestamp);
	}

	public static function ExtractProperties($str, &$arProps, &$arItem)
	{
		reset($arProps);
		while (list($key, $val) = each($arProps))
			$str = str_replace("#".$key."#", $val["VALUE"], $str);
		reset($arItem);
		while (list($key, $val) = each($arItem))
			$str = str_replace("#".$key."#", $val, $str);
		return $str;
	}

	public static function GetRSS($ID, $LANG, $TYPE, $LIMIT_NUM = false, $LIMIT_DAY = false, $yandex = false)
	{
		echo "<"."?xml version=\"1.0\" encoding=\"".LANG_CHARSET."\"?".">\n";
		echo "<rss version=\"2.0\"";
		echo ">\n";

		$dbr = CIBlockType::GetList(array(), array(
			"=ID" => $TYPE,
		));
		$arType = $dbr->Fetch();
		if ($arType && ($arType["IN_RSS"] == "Y"))
		{
			$dbr = CIBlock::GetList(array(), array(
				"type" => $TYPE,
				"LID" => $LANG,
				"ACTIVE" => "Y",
				"ID" => $ID,
			));
			$arIBlock = $dbr->Fetch();
			if ($arIBlock && ($arIBlock["RSS_ACTIVE"] == "Y"))
			{
				echo CIBlockRSS::GetRSSText($arIBlock, $LIMIT_NUM, $LIMIT_DAY, $yandex);
			}
		}

		echo "</rss>\n";
	}
}
?>