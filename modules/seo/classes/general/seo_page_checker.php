<?
IncludeModuleLangFile(__FILE__);
class CSeoPageChecker
{
	var $__site;
	var $__url;
	var $__lang;
	var $__server_name;

	var $__bCheckErrors = true;

	var $__getter;

	var $__result_headers;
	var $__result_data;
	var $__result_extended = array();

	var $__result_meta = array('KEYWORDS' => '', 'DESCRIPTION' => '');

	var $__result_errors = array();

	var $__index;
	var $__index_total_len;

	var $pcre_backtrack_limit = false;

	var $__qualifier_links_count = 100;

	var $bError = false;
	var $bSearch = false;

	public function CSeoPageChecker($site, $url, $get = true, $check_errors = true)
	{
		global $APPLICATION;

		if (CModule::IncludeModule('search'))
			$this->bSearch = true;
		else
			$APPLICATION->ThrowException(GetMessage('SEO_ERROR_NO_SEARCH')); // don't return false or set bError!

		$this->__bCheckErrors = $check_errors;

		$this->__site = $site;

		$dbRes = CSite::GetByID($this->__site);
		if ($arRes = $dbRes->Fetch())
		{
			$this->__lang = $arRes['LANGUAGE_ID'];
			$this->__server_name = $arRes['SERVER_NAME'];

			if (strlen($this->__server_name) <= 0)
				$this->__server_name = COption::GetOptionString('main', 'server_name', '');

			if (strlen($this->__server_name) > 0)
			{
				$this->__url = (CMain::IsHTTPS() ? "https://" : "http://")
					.CBXPunycode::ToASCII($this->__server_name, $e = null)
					.$url;

				return $get ? $this->GetHTTPData() : true;
			}
			else
			{
				$this->bError = true;
				$APPLICATION->ThrowException(str_replace('#SITE_ID#', $this->__site, GetMessage('SEO_ERROR_NO_SERVER_NAME')));
				return false;
			}
		}

		return false;
	}

	public function GetHTTPData()
	{
		global $APPLICATION;
		$this->__getter = new CHTTP();
		$this->__getter->http_timeout = 25;
		$this->__getter->setFollowRedirect(true);

		if ($this->__getter->HTTPQuery('GET', $this->__url))
		{
			$this->__result_data = $this->__getter->result;
			$this->__result_headers = $this->__getter->headers;

			$this->_PrepareData();

			unset($this->__getter);
			$this->bError = false;
			return true;
		}

		unset($this->__getter);
		$this->bError = true;
		return false;
	}

	public function __prepareText($text)
	{
		$res = array();
		if ($this->bSearch)
			$res = stemming(CSearch::KillTags($text), $this->__lang);
		else
			$res = array();

		return $res;
	}

	public function _PrepareData()
	{
		if($this->pcre_backtrack_limit === false)
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		$text_len = function_exists('mb_strlen') ? mb_strlen($this->__result_data, 'latin1') : strlen($this->__result_data);
		$text_len++;
		if($this->pcre_backtrack_limit > 0 && $this->pcre_backtrack_limit < $text_len)
		{
			@ini_set("pcre.backtrack_limit", $text_len);
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		}

		if($this->__bCheckErrors && $this->pcre_backtrack_limit > 0 && $this->pcre_backtrack_limit < $text_len)
		{
			$this->__result_errors[] = array(
				'CODE' => 'SEO_PCRE',
				'TYPE' => 'NOTE',
				'DETAIL' => array(
					'#PCRE_BACKTRACK_LIMIT#' => $this->pcre_backtrack_limit,
					'#TEXT_LEN#' => $text_len,
				)
			);
		}

		$this->__index = array('TOTAL' => array(), 'BOLD' => array(), 'ITALIC' => array(), 'LINK' => array(), 'DESCRIPTION' => array(), 'KEYWORDS' => array());

		// replace all images on their not empty ALT or TITLE attributes
		$this->__result_data = preg_replace('/<img[^>]*(alt|title)=\"([^\"]*)\".*?>/is', '\\2', $this->__result_data);

		if ($this->__bCheckErrors && ($img_cnt = preg_match('/<img.*?>/is', $this->__result_data)))
		{
			$this->__result_errors[] = array(
				'CODE' => 'SEO_IMG_NO_ALT',
				'TYPE' => 'NOTE',
				'DETAIL' => array(
					'#COUNT#' => $img_cnt
				)
			);
		}

		// get full words index
		$this->__index['TOTAL'] = $this->__prepareText($this->__result_data);

		// get bold words index
		$arRes = array();
		if(preg_match_all("/<(b|strong)>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['BOLD'] = $arRes[0];
			$this->__index['BOLD'] = $this->__prepareText(implode(" ", $arRes[2]));
		}

		// get italic words index
		if(preg_match_all("/<(i|em)>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['ITALIC'] = $arRes[0];
			$this->__index['ITALIC'] = $this->__prepareText(implode(" ", $arRes[2]));
		}

		// get noindex tags
		if(preg_match_all("/<(noindex)>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['NOINDEX'] = $arRes[0];
			$this->__index['NOINDEX'] = $this->__prepareText(implode(" ", $arRes[2]));
		}
		// get link words index
		if(preg_match_all("/<(a) ([^>]*)>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['LINK'] = $arRes[0];
			$this->__index['LINK'] = $this->__prepareText(implode(" ", $arRes[3]));

			$this->__result_extended['NOFOLLOW'] = array();
			$this->__result_extended['LINK_EXTERNAL'] = array();
			$this->__index['LINK_EXTERNAL'] = array();

			foreach ($arRes[2] as $key => $attrs)
			{
				if (false !== strpos($attrs, 'rel="nofollow"'))
					$this->__result_extended['NOFOLLOW'][] = $arRes[0][$key];
				if (false !== ($pos = strpos($attrs, 'href="')))
				{
					$pos1 = strpos($attrs, '"', $pos + 6);
					$url = substr($attrs, $pos, $pos1-$pos);

					if ($this->IsOuterUrl($url))
					{
						$this->__index['LINK_EXTERNAL'] = array_merge($this->__index['LINK_EXTERNAL'], $this->__prepareText($arRes[3][$key]));
						$this->__result_extended['LINK_EXTERNAL'][] = $arRes[0][$key];
					}
				}
			}

			if ($this->__bCheckErrors && count($arRes[0]) > $this->__qualifier_links_count)
			{
				$this->__result_errors[] = array(
					'CODE' => 'SEO_LINKS_COUNT',
					'TYPE' => 'NOTE',
					'DETAIL' => array(
						'#COUNT#' => count($arRes[0]),
						'#COUNT_EXTERNAL#' => count($this->__result_extended['LINK_EXTERNAL']),
						'#QUALIFIER#' => $this->__qualifier_links_count,
					)
				);
			}

		}

		// get meta description words index
		if(preg_match('/<meta.*?name=\"description\".*?content=\"([^\"]+)\"[^>]*>/i', $this->__result_data, $arRes))
		{
			$this->__result_meta['DESCRIPTION'] = $arRes[1];
			$this->__result_extended['META_DESCRIPTION'] = $arRes[0];
			$this->__index['DESCRIPTION'] = $this->__prepareText($this->__result_meta['DESCRIPTION']);
		}
		else
		{
			$this->__result_errors[] = array(
				'CODE' => 'SEO_META_NO_DESCRIPTION',
				'TYPE' => 'NOTE',
				'DETAIL' => array()
			);
		}

		// get meta keywords words index
		if(preg_match('/<meta.*?name=\"keywords\".*?content=\"([^\"]+)\"[^>]*>/i', $this->__result_data, $arRes))
		{
			$this->__result_meta['KEYWORDS'] = $arRes[1];
			$this->__result_extended['META_KEYWORDS'] = $arRes[0];
			$this->__index['KEYWORDS'] = $this->__prepareText($this->__result_meta['KEYWORDS']);
		}
		else
		{
			$this->__result_errors[] = array(
				'CODE' => 'SEO_META_NO_KEYWORDS',
				'TYPE' => 'NOTE',
				'DETAIL' => array()
			);
		}

		// get titles words index
		if(preg_match("/<(title)>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['TITLE'] = $arRes[0];
			$this->__index['TITLE'] = $this->__prepareText($arRes[2]);
		}

		if(preg_match_all("/<(h[\d]{1}).*?>.*?<\\/\\1>/is", $this->__result_data, $arRes))
		{
			$this->__result_extended['H'] = $arRes[0];
		}

		if(preg_match_all("/<(h1).*?>(.*?)<\\/\\1>/is", $this->__result_data, $arRes))
		{
			if ($this->__bCheckErrors && count($arRes[0]) > 1)
			{
				$this->__result_errors[] = array(
					'CODE' => 'SEO_H1_UNIQUE',
					'TYPE' => 'NOTE',
					'DETAIL' => array(
						'#COUNT#' => count($arRes[0]),
						'#VALUES#' => htmlspecialcharsbx('"'.implode('", "', $arRes[2]).'"'),
					)
				);
			}

			$this->__index['H1'] = $this->__prepareText(implode(" ", $arRes[2]));
		}
		elseif ($this->__bCheckErrors)
		{
			$this->__result_errors[] = array(
				'CODE' => 'SEO_H1_ABSENT',
				'TYPE' => 'NOTE',
				'DETAIL' => array()
			);
		}

		if ($this->__bCheckErrors)
		{
			foreach(GetModuleEvents('seo', 'onPageCheck', true) as $arEvent)
			{
				if (!ExecuteModuleEventEx($arEvent, array(
					'QUERY' => array(
						'URL' => $this->__url,
						'LANG' => $this->__lang,
						'SERVER_NAME' => $this->__server_name,
						'SITE' => $this->__site,
					),
					'DATA' => array(
						'HEADERS' => $this->__result_headers,
						'BODY' => $this->__result_data,
					),
					'META' => $this->__result_meta,
					'INDEX' => $this->__index,
				)) && ($ex = $GLOBALS['APPLICATION']->GetException()))
				{
					$this->__result_errors[] = array(
						'CODE' => $ex->GetId(),
						'TYPE' => 'NOTE',
						'TEXT' => $ex->GetString(),
					);
				}
			}
		}
	}

	public function _GetContrast($word)
	{
		if (null == $this->__index_total_len)
			$this->__index_total_len = array_sum($this->__index['TOTAL']);

		$logDocLength = log($this->__index_total_len < 20 ? 20 : $this->__index_total_len);

		$count = intval($this->__index['TOTAL'][$word]);

		return log($count+1)/$logDocLength;
	}

	public function GetStatistics()
	{
		if (!is_array($this->__index))
			return false;

		if (null == $this->__index_total_len)
			$this->__index_total_len = array_sum($this->__index['TOTAL']);

		return array(
			'URL' => $this->__url,
			'TOTAL_LENGTH' => function_exists('mb_strlen') ? mb_strlen($this->__result_data, 'latin1') : strlen($this->__result_data),
			'TOTAL_WORDS_COUNT' => $this->__index_total_len ? $this->__index_total_len : '-',
			'UNIQUE_WORDS_COUNT' => $this->__index_total_len ? count($this->__index['TOTAL']) : '-',
			'META_KEYWORDS' => $this->__result_meta['KEYWORDS'],
			'META_DESCRIPTION' => $this->__result_meta['DESCRIPTION'],
		);
	}

	public function GetURL()
	{
		return $this->__url;
	}

	public function CheckKeyword($keyword, $bStemmed = false)
	{
		if (!is_array($this->__index))
			return false;

		if (is_array($keyword))
		{
			$arResult = array();

			foreach ($keyword as $key => $word)
			{
				$arResult[$key] = $this->CheckKeyword($bStemmed ? $key : $word, $bStemmed);
			}
			return $arResult;
		}

		if (!$bStemmed && $this->bSearch)
			$keyword = stemming($keyword, $this->__lang);

		if (is_array($keyword))
			return $this->CheckKeyword($keyword, true);

		$arResult = array(
			'TOTAL' => intval($this->__index['TOTAL'][$keyword]),
			'BOLD' => intval($this->__index['BOLD'][$keyword]),
			'ITALIC' => intval($this->__index['ITALIC'][$keyword]),
			'LINK' => intval($this->__index['LINK'][$keyword]),
			'LINK_EXTERNAL' => intval($this->__index['LINK_EXTERNAL'][$keyword]),
			'DESCRIPTION' => intval($this->__index['DESCRIPTION'][$keyword]),
			'KEYWORDS' => intval($this->__index['KEYWORDS'][$keyword]),
			'TITLE' => intval($this->__index['TITLE'][$keyword]),
			'H1' => intval($this->__index['H1'][$keyword]),

			'CONTRAST' => $this->_GetContrast($keyword),
		);

		return $arResult;
	}

	public function GetExtendedData()
	{
		return array_merge(array('HEADERS' => $this->__result_headers), $this->__result_extended);
	}

	public function GetErrors()
	{
		$arResult = false;

		if (count($this->__result_errors) > 0)
		{
			$arResult = array();

			foreach ($this->__result_errors as $arError)
			{
				$arResult[] = array(
					'CODE' => $arError['CODE'],
					'TYPE' => $arError['TYPE'],
					'TEXT' => isset($arError['TEXT']) ? $arError['TEXT'] : str_replace(array_keys($arError['DETAIL']), array_values($arError['DETAIL']), GetMessage($arError['CODE'].'_ERROR')),
				);
			}
		}

		return $arResult;
	}

	public static function IsOuterUrl($url)
	{
		if (strncmp($url, '#', 1) === 0) return false;
		if (strncmp($url, 'mailto:', 7) === 0) return false;
		if (strncmp($url, 'javascript:', 11) === 0) return false;

		$pos = strpos($url, '://');
		if ($pos === false) return false;

		static $arDomainNames = null;

		if (null == $arDomainNames)
		{
			$arDomainNames = array($_SERVER['SERVER_NAME']);

			$dbRes = CSite::GetList($by = 'sort', $order = 'asc', array('ACTIVE' => 'Y'));
			while ($arSite = $dbRes->Fetch())
			{
				if ($arSite['DOMAINS'])
					$arDomainNames = array_merge($arDomainNames, explode("\r\n", $arSite['DOMAINS']));
			}

			$arDomainNames = array_values(array_unique($arDomainNames));
		}

		$url = substr($url, $pos+3);
		$pos = strpos($url, '/');

		if ($pos === false)
		{
			$pos = strlen($url);
		}

		$domain = substr($url, 0, $pos);
		if (substr($domain, 0, 4) == 'www.')
		{
			$domain = substr($domain, 4);
		}

		if ($domain)
			return !in_array($domain, $arDomainNames);

		return false;
	}
}
?>