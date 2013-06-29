<?
class CAllSearchTitle extends CDBResult
{
	var $_arPhrase = array();
	var $_arStemFunc;

	public function __construct()
	{
		$this->_arStemFunc = stemming_init(LANGUAGE_ID);
	}

	public function Fetch()
	{
		$r = parent::Fetch();

		if($r)
		{
			if(strlen($r["SITE_URL"])>0)
				$r["URL"] = $r["SITE_URL"];

			if(substr($r["URL"], 0, 1)=="=")
			{
				$events = GetModuleEvents("search", "OnSearchGetURL");
				while ($arEvent = $events->Fetch())
					$r["URL"] = ExecuteModuleEventEx($arEvent, array($r));
			}

			$r["URL"] = str_replace(
				array("#LANG#", "#SITE_DIR#", "#SERVER_NAME#"),
				array($r["DIR"], $r["DIR"], $r["SERVER_NAME"]),
				$r["URL"]
			);
			$r["URL"] = preg_replace("'(?<!:)/+'s", "/", $r["URL"]);

			$r["NAME"] = htmlspecialcharsex($r["TITLE"]);

			$preg_template = "/(^|[^".$this->_arStemFunc["pcre_letters"]."])(".str_replace("/", "\\/", implode("|", array_map('preg_quote', array_keys($this->_arPhrase)))).")/i".BX_UTF_PCRE_MODIFIER;
			if(preg_match_all($preg_template, ToUpper($r["NAME"]), $arMatches, PREG_OFFSET_CAPTURE))
			{
				$c = count($arMatches[2]);
				if(defined("BX_UTF"))
				{
					for($j = $c-1; $j >= 0; $j--)
					{
						$prefix = mb_substr($r["NAME"], 0, $arMatches[2][$j][1], 'latin1');
						$instr  = mb_substr($r["NAME"], $arMatches[2][$j][1], mb_strlen($arMatches[2][$j][0], 'latin1'), 'latin1');
						$suffix = mb_substr($r["NAME"], $arMatches[2][$j][1] + mb_strlen($arMatches[2][$j][0], 'latin1'), mb_strlen($r["NAME"], 'latin1'), 'latin1');
						$r["NAME"] = $prefix."<b>".$instr."</b>".$suffix;
					}
				}
				else
				{
					for($j = $c-1; $j >= 0; $j--)
					{
						$prefix = substr($r["NAME"], 0, $arMatches[2][$j][1]);
						$instr  = substr($r["NAME"], $arMatches[2][$j][1], strlen($arMatches[2][$j][0]));
						$suffix = substr($r["NAME"], $arMatches[2][$j][1]+strlen($arMatches[2][$j][0]));
						$r["NAME"] = $prefix."<b>".$instr."</b>".$suffix;
					}
				}
			}
		}

		return $r;
	}

	public static function MakeFilterUrl($prefix, $arFilter)
	{
		if(!is_array($arFilter))
		{
			return "&".urlencode($prefix)."=".urlencode($arFilter);
		}
		else
		{
			$url = "";
			foreach($arFilter as $key => $value)
			{
				$url .= CSearchTitle::MakeFilterUrl($prefix."[".$key."]", $value);
			}
			return $url;
		}
	}
}
?>