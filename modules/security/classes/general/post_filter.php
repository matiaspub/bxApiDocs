<?php

class CSecurityXSSDetect
{
	const SCRIPT_MARK = '<!-- deleted by bitrix WAF -->';
	private $quotes = array();

	private $action = "filter";
	private $doLog = false;

	/** @var CSecurityXSSDetectVariables */
	private $variables = null;

	public function __construct($options = array())
	{
		if(isset($options["action"]))
		{
			$this->setAction($options["action"]);
		}
		else
		{
			$this->setAction(COption::GetOptionString("security", "filter_action"));
		}

		if(isset($options["log"]))
		{
			$this->setLog($options["log"]);
		}
		else
		{
			$this->setLog(COption::GetOptionString("security", "filter_log"));
		}
	}

	/**
	 * @param $content
	 */
	public static function OnEndBufferContent(&$content)
	{
		if (CSecuritySystemInformation::isCliMode())
			return;

		if (CSecurityFilterMask::Check(SITE_ID, $_SERVER["REQUEST_URI"]))
			return;

		$filter = new CSecurityXSSDetect();
		$content = $filter->process($content);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function process($content)
	{
		if(!preg_match('#</script#' ,$content))
		{
			// Probably does not include the scripts
			return $content;
		}

		$this->variables = new CSecurityXSSDetectVariables();
		$this->extractVariablesFromArray("\$_GET", $_GET);
		$this->extractVariablesFromArray("\$_POST", $_POST);
		$this->extractVariablesFromArray("\$_COOKIE", $_COOKIE);
		if(!$this->variables->isEmpty())
			return $this->filter($content);
		else
			return $content;
	}

	/**
	 * @return array
	 */
	public function getQuotes()
	{
		return $this->quotes;
	}

	/**
	 * @param string $string
	 * @param bool $isSaveQuotes
	 * @return mixed
	 */
	public function removeQuotedStrings($string, $isSaveQuotes = true)
	{
		// http://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
		if($isSaveQuotes)
		{
			$this->quotes = array();
			return preg_replace_callback('/(
				"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"                           # match double quoted string
				|
				\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'                       # match single quoted string
				|
				(?s:\\/\\*.*?\\*\\/)                                     # multiline comments
				|
				\\/\\/.*?(?:\\n|$)                                       # singleline comments
				|
				string.replace\\(\\/[^\\/\\\\]*(?:\\\\.[^\\/\\\\]*)*\\/  # an JS regexp
			)/x', array($this, "pushQuote"), $string);
		}
		else
		{
			return preg_replace('/(
				"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"                           # match double quoted string
				|
				\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'                       # match single quoted string
				|
				(?s:\\/\\*.*?\\*\\/)                                     # multiline comments
				|
				\\/\\/.*?(?:\\n|$)                                       # singleline comments
				|
				string.replace\\(\\/[^\\/\\\\]*(?:\\\\.[^\\/\\\\]*)*\\/  # an JS regexp
			)/x', '', $string);
		}
	}

	/**
	 * @param string $action
	 */
	protected function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @param string $log - only Y/N
	 */
	protected function setLog($log)
	{
		if(is_string($log) && $log == "Y")
		{
			$this->doLog = true;
		}
		else
		{
			$this->doLog = false;
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $sourceScript
	 * @return mixed
	 */
	protected function logVariable($name, $value, $sourceScript)
	{
		if(defined("ANTIVIRUS_CREATE_TRACE"))
			$this->CreateTrace($name, $value, $sourceScript);

		return CSecurityEvent::getInstance()->doLog("SECURITY", "SECURITY_FILTER_XSS2", $name, $value);
	}

	/**
	 * @param $var_name
	 * @param $str
	 * @param $script
	 */
	protected function CreateTrace($var_name, $str, $script)
	{
		$cache_id = md5($var_name.'|'.$str);
		$fn = $_SERVER["DOCUMENT_ROOT"]."/bitrix/cache/virus.db/".$cache_id.".flt";
		if(!file_exists($fn))
		{
			CheckDirPath($fn);
			$f = fopen($fn, "wb");

			fwrite($f, $var_name.": ".$str);
			fwrite($f, "\n------------\n".$script);
			fwrite($f, "\n------------------------------\n\$_SERVER:\n");
			foreach($_SERVER as $k=>$v)
				fwrite($f, $k." = ".$v."\n");

			fclose($f);
			@chmod($fn, BX_FILE_PERMISSIONS);
		}
	}

	/**
	 * @param string $quote
	 * @return string
	 */
	protected function pushQuote($quote)
	{
		$this->quotes[] = $quote[0];
		return "";
	}

	/**
	 * @param string $string
	 * @param array $patterns
	 * @return bool
	 */
	protected static function isFoundInString($string, $patterns)
	{
		foreach($patterns as $pattern)
		{
			if(isset($pattern["variable_len"]))
				$isFound = strlen($string) > $pattern["variable_len"] && preg_match($pattern["pattern"], $string);
			else
				$isFound = preg_match($pattern, $string);

			if($isFound)
				return true;
		}
		return false;
	}

	/**
	 * @param string $body
	 * @return bool
	 */
	protected function isDangerBody($body)
	{
		if(self::isFoundInString($body ,$this->variables->getQuoteSearchPattern()))
		{
			return true;
		}
		else
		{
			$bodyWithoutQuotes = $this->removeQuotedStrings($body, false);
			if(self::isFoundInString($bodyWithoutQuotes, $this->variables->getSearchPattern()))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $body
	 * @return string
	 */
	protected function getFilteredScriptBody($body)
	{
		if($this->isDangerBody($body))
		{
//                if($this->mIsLogNeeded)
//			      {
//                    $this->logVariable($var_name, $value, $str);
//                }
			if($this->action !== "none")
			{
				$body = self::SCRIPT_MARK;
			}
		}

		return $body;
	}

	/**
	 * @param array $strs
	 * @return string
	 */
	protected function getFilteredScript($strs)
	{
		if(trim($strs[2]) === "")
			return $strs[0];
		else
			return $strs[1].$this->getFilteredScriptBody($strs[2]).$strs[3];
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function filter($string)
	{
		$stringLen = CUtil::BinStrlen($string) * 2;
		CUtil::AdjustPcreBacktrackLimit($stringLen);

		return preg_replace_callback("/(<script[^>]*>)(.*?)(<\\/script[^>]*>)/is", array($this, "getFilteredScript"), $string);
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	protected function addVariable($name, $value)
	{
		if(!is_string($value))
			return;
		if(strlen($value) <= 2)
			return; //too short
		if(preg_match("/^[^,;\'\"+\-*\/\{\}\[\]\(\)&\\|=\\\\]*\$/D", $value))
			return; //there is no potantially dangerous code
		if(preg_match("/^[,0-9_-]*\$/D", $value))
			return; //there is no potantially dangerous code
		if($name === '$_COOKIE[__utmz]' && preg_match("/^[0-9.]++(utm[a-z]{3}=\(?([a-z\/0-1.]++|\(not provided\))\)?\|?)++\$/iD", $value))
			return; //there is no potantially dangerous code, google analytics

		$this->variables->addVariable($name, str_replace(chr(0), "", $value));
	}

	/**
	 * @param string $name
	 * @param array $array
	 */
	protected function extractVariablesFromArray($name, $array)
	{
		if(is_array($array))
		{
			foreach($array as $key => $value)
				$this->addVariable($name."[".$key."]", $value);
		}
	}

}
