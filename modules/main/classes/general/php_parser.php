<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class PHPParser
{
	protected static $arAllStr;

	public static function ReplString($str, $arAllStr)
	{
		self::$arAllStr = $arAllStr;

		if(preg_match("'^\x01([0-9]+)\x02$'s", $str))
		{
			return preg_replace_callback("'\x01([0-9]+)\x02's", "PHPParser::getString", $str);
		}
		if(strval(floatval($str)) == $str)
		{
			return preg_replace_callback("'\x01([0-9]+)\x02's", "PHPParser::getQuotedString", $str);
		}
		elseif($str == "")
		{
			return "";
		}
		else
		{
			return "={".preg_replace_callback("'\x01([0-9]+)\x02's", "PHPParser::getQuotedString", $str)."}";
		}
	}

	public static function getString($matches)
	{
		return self::$arAllStr[$matches[1]];
	}

	public static function getQuotedString($matches)
	{
		return '"'.self::$arAllStr[$matches[1]].'"';
	}

	public static function GetParams($params)
	{
		$arParams = array();
		$sk = 0;
		$param_tmp = "";
		$params_l = strlen($params);
		for($i=0; $i<$params_l; $i++)
		{
			$ch = substr($params, $i, 1);
			if($ch=="(")
				$sk++;
			elseif($ch==")")
				$sk--;
			elseif($ch=="," && $sk==0)
			{
				$arParams[] = $param_tmp;
				$param_tmp = "";
				continue;
			}

			if($sk<0)
				break;

			$param_tmp .= $ch;
		}
		if($param_tmp!="")
			$arParams[] = $param_tmp;

		return $arParams;
	}

	public static function GetParamsRec($params, &$arAllStr, &$arResult)
	{
		if (strtolower(substr($params, 0, 6)) == 'array(')
		{
			$arParams = PHPParser::GetParams(substr($params, 6));
			foreach ($arParams as $i => $el)
			{
				$p = strpos($el, "=>");
				if ($p === false)
				{
					if(is_string($arResult))
					{
						$arResult = PHPParser::ReplString($el, $arAllStr);
					}
					else
					{
						PHPParser::GetParamsRec($el, $arAllStr, $arResult[$i]);
					}
				}
				else
				{
					$el_ind = PHPParser::ReplString(substr($el, 0, $p), $arAllStr);
					$el_val = substr($el, $p + 2);
					PHPParser::GetParamsRec($el_val, $arAllStr, $arResult[$el_ind]);
				}
			}
		}
		else
		{
			$arResult = PHPParser::ReplString($params, $arAllStr);
		}
	}

	// Parse string and check if it is a component call. Return call params array
	public static function CheckForComponent($str)
	{
		if(substr($str, 0, 5)=="<?"."php")
			$str = substr($str, 5);
		else
			$str = substr($str, 2);

		$str = substr($str, 0, -2);

		$bSlashed = false;
		$bInString = false;
		$arAllStr = array();
		$new_str = "";
		$string_tmp = "";
		$quote_ch = "";
		$i = -1;
		$length = strlen($str);
		while($i < $length-1)
		{
			$i++;
			$ch = substr($str, $i, 1);
			if(!$bInString)
			{
				if($string_tmp!="")
				{
					$arAllStr[] = $string_tmp;
					$string_tmp = "";
					$new_str .= chr(1).(count($arAllStr)-1).chr(2);
				}

				//comment
				if($ch == "/" && $i+1 < $length)
				{
					$ti = 0;
					if(substr($str, $i+1, 1)=="*" && ($ti = strpos($str, "*/", $i+2))!==false)
						$ti += 2;
					elseif(substr($str, $i+1, 1)=="/" && ($ti = strpos($str, "\n", $i+2))!==false)
						$ti += 1;

					if($ti)
						$i = $ti;

					continue;
				}

				if($ch == " " || $ch == "\r" || $ch == "\n" || $ch == "\t")
					continue;
			}

			if($bInString && $ch == "\\" && !$bSlashed)
			{
				$bSlashed = true;
				continue;
			}

			if($ch == "\"" || $ch == "'")
			{
				if($bInString)
				{
					if(!$bSlashed && $quote_ch == $ch)
					{
						$bInString = false;
						continue;
					}
				}
				else
				{
					$bInString = true;
					$quote_ch = $ch;
					continue;
				}
			}

			$bSlashed = false;
			if($bInString)
			{
				$string_tmp .= $ch;
				continue;
			}

			$new_str .= $ch;
		}

		if($pos = strpos($new_str, "("))
		{
			$func_name = substr($new_str, 0, $pos+1);
			if(preg_match("/^(\\\$[A-Z_][A-Z0-9_]*)(\\s*=\\s*)/i", $func_name, $arMatch))
			{
				$var_name = $arMatch[1];
				$func_name = substr($func_name, strlen($arMatch[0]));
			}
			else
			{
				$var_name = "";
			}
			$func_name = preg_replace("'\\\$GLOBALS\\[(\"|\\')(.+?)(\"|\\')\\]'s", "\$\\2", $func_name);
			switch(strtoupper($func_name))
			{
				case '$APPLICATION->INCLUDEFILE(':
					$params = substr($new_str, $pos+1);
					$arParams = PHPParser::GetParams($params);
					$arIncludeParams = array();

					if(preg_match("/^array\\(/i", $arParams[1]))
					{
						$arParams2 = PHPParser::GetParams(substr($arParams[1], 6));
						foreach($arParams2 as $el)
						{
							$p = strpos($el, "=>");
							$el_ind = PHPParser::ReplString(substr($el, 0, $p), $arAllStr);
							$el_val = substr($el, $p+2);
							if(preg_match("/^array\\(/i", $el_val))
							{
								$res_ar = array();
								$arParamsN = PHPParser::GetParams(substr($el_val, 6));
								foreach($arParamsN as $param)
									$res_ar[] = PHPParser::ReplString($param, $arAllStr);

								$arIncludeParams[$el_ind] = $res_ar;
							}
							else
								$arIncludeParams[$el_ind] = PHPParser::ReplString($el_val, $arAllStr);
						}
					}
					return array(
						"SCRIPT_NAME" => PHPParser::ReplString($arParams[0], $arAllStr),
						"PARAMS" => $arIncludeParams,
						"VARIABLE" => $var_name,
					);
			}
		}
		return false;
	}

	public static function GetComponentParams($instruction, $arAllStr)
	{
		if ($pos = strpos($instruction, "("))
		{
			$func_name = substr($instruction, 0, $pos + 1);
			if (preg_match("/(\\\$[A-Z_][A-Z0-9_]*)(\\s*=\\s*)/i", $func_name, $arMatch))
			{
				$var_name = $arMatch[1];
			}
			else
			{
				$var_name = "";
			}

			$params = substr($instruction, $pos + 1);
			$arParams = PHPParser::GetParams($params);

			$arIncludeParams = array();
			$arFuncParams = array();
			PHPParser::GetParamsRec($arParams[2], $arAllStr, $arIncludeParams);
			PHPParser::GetParamsRec($arParams[4], $arAllStr, $arFuncParams);

			return array(
				"COMPONENT_NAME" => PHPParser::ReplString($arParams[0], $arAllStr),
				"TEMPLATE_NAME" => PHPParser::ReplString($arParams[1], $arAllStr),
				"PARAMS" => $arIncludeParams,
				"PARENT_COMP" => $arParams[3],
				"VARIABLE" => $var_name,
				"FUNCTION_PARAMS" => $arFuncParams,
			);
		}
		return array();
	}

	public static function ParseScript($scriptContent)
	{
		$arComponents = array();
		$componentNumber = -1;

		$bInComponent = false;
		$bInPHP = false;

		$bInString = false;
		$quoteChar = "";
		$bSlashed = false;

		$string = false;
		$instruction = "";

		//mb_substr is catastrophic slow, so in UTF we use array of characters
		if(defined("BX_UTF"))
			$allChars = preg_split('//u', $scriptContent, -1, PREG_SPLIT_NO_EMPTY);
		else
			$allChars = &$scriptContent;

		$scriptContentLength = strlen($scriptContent);
		$arAllStr = array();
		$ind = -1;
		while ($ind < $scriptContentLength - 1)
		{
			$ind++;
			$ch = $allChars[$ind];

			if ($bInPHP)
			{
				if (!$bInString)
				{
					if (!$bInComponent && $instruction <> '')
					{
						if (preg_match("#\\s*((\\\$[A-Z_][A-Z0-9_]*\\s*=)?\\s*\\\$APPLICATION->IncludeComponent\\s*\\()#is", $instruction, $arMatches))
						{
							$arAllStr = array();
							$bInComponent = true;
							$componentNumber++;
							$instruction = $arMatches[1];

							$arComponents[$componentNumber] = array(
								"START" => ($ind - strlen($arMatches[1])),
								"END" => false,
								"DATA" => array()
							);
						}
					}
					if ($string !== false)
					{
						if ($bInComponent)
						{
							$arAllStr[] = $string;
							$instruction .= chr(1).(count($arAllStr) - 1).chr(2);
						}
						$string = false;
					}
					if ($ch == ";")
					{
						if ($bInComponent)
						{
							$bInComponent = false;
							$arComponents[$componentNumber]["END"] = $ind + 1;
							$arComponents[$componentNumber]["DATA"] = PHPParser::GetComponentParams(preg_replace("#[ \r\n\t]#", "", $instruction), $arAllStr);
						}
						$instruction = "";
						continue;
					}
					if ($ch == "/" && $ind < $scriptContentLength - 2)
					{
						$nextChar = $allChars[$ind + 1];
						if ($nextChar == "/")
						{
							$endPos = strpos($scriptContent, "\n", $ind + 2);

							if ($endPos === false)
								$ind = $scriptContentLength - 1;
							else
								$ind = $endPos;

							continue;
						}
						elseif ($nextChar == "*")
						{
							$endPos = strpos($scriptContent, "*/", $ind + 2);

							if ($endPos === false)
								$ind = $scriptContentLength - 1;
							else
								$ind = $endPos + 1;

							continue;
						}
					}

					if ($ch == "\"" || $ch == "'")
					{
						$bInString = true;
						$string = "";
						$quoteChar = $ch;
						continue;
					}

					if ($ch == "?" && $ind < $scriptContentLength - 2 && $allChars[$ind + 1] == ">")
					{
						$ind += 1;
						if ($bInComponent)
						{
							$bInComponent = false;
							$arComponents[$componentNumber]["END"] = $ind - 1;
							$arComponents[$componentNumber]["DATA"] = PHPParser::GetComponentParams(preg_replace("#[ \r\n\t]#", "", $instruction), $arAllStr);
						}
						$instruction = "";
						$bInPHP = false;
						continue;
					}

					$instruction .= $ch;

					if ($ch == " " || $ch == "\r" || $ch == "\n" || $ch == "\t")
						continue;
				}
				else
				{
					if ($ch == "\\" && !$bSlashed)
					{
						$bSlashed = true;
						continue;
					}
					if ($ch == $quoteChar && !$bSlashed)
					{
						$bInString = false;
						continue;
					}
					$bSlashed = false;

					$string .= $ch;
				}
			}
			else
			{
				if ($ch == "<")
				{
					if ($ind < $scriptContentLength - 5 && $allChars[$ind + 1].$allChars[$ind + 2].$allChars[$ind + 3].$allChars[$ind + 4] == "?php")
					{
						$bInPHP = true;
						$ind += 4;
					}
					elseif ($ind < $scriptContentLength - 2 && $allChars[$ind + 1] == "?")
					{
						$bInPHP = true;
						$ind += 1;
					}
				}
			}
		}
		return $arComponents;
	}

	// Components 2. Parse string and check if it is a component call. Return call params array
	public static function CheckForComponent2($str)
	{
		if (substr($str, 0, 5) == "<?"."php")
			$str = substr($str, 5);
		else
			$str = substr($str, 2);

		$str = substr($str, 0, -2);

		$bSlashed = false;
		$bInString = false;
		$arAllStr = array();
		$new_str = "";
		$string_tmp = "";
		$quote_ch = "";
		$i = -1;
		$length = strlen($str);
		while ($i < $length - 1)
		{
			$i++;
			$ch = substr($str, $i, 1);
			if (!$bInString)
			{
				if ($string_tmp != "")
				{
					$arAllStr[] = $string_tmp;
					$string_tmp = "";
					$new_str .= chr(1).(count($arAllStr) - 1).chr(2);
				}

				//comment
				if ($ch == "/" && $i+1 < $length)
				{
					$ti = 0;
					if (substr($str, $i+1, 1) == "*" && ($ti = strpos($str, "*/", $i+2)) !== false)
						$ti += 1;
					elseif (substr($str, $i+1, 1)=="/" && ($ti = strpos($str, "\n", $i+2)) !== false)
						$ti += 0;

					if ($ti)
						$i = $ti;

					continue;
				}

				if ($ch == " " || $ch == "\r" || $ch == "\n" || $ch == "\t")
					continue;
			}

			if ($bInString && $ch == "\\" && !$bSlashed)
			{
				$bSlashed = true;
				continue;
			}

			if ($ch == "\"" || $ch == "'")
			{
				if ($bInString)
				{
					if (!$bSlashed && $quote_ch == $ch)
					{
						$bInString = false;
						continue;
					}
				}
				else
				{
					$bInString = true;
					$quote_ch = $ch;
					continue;
				}
			}

			$bSlashed = false;
			if ($bInString)
			{
				$string_tmp .= $ch;
				continue;
			}

			$new_str .= $ch;
		}

		if ($pos = strpos($new_str, "("))
		{
			$func_name = substr($new_str, 0, $pos + 1);
			if (preg_match("/^(\\\$[A-Z_][A-Z0-9_]*)(\\s*=\\s*)/i", $func_name, $arMatch))
			{
				$var_name = $arMatch[1];
				$func_name = substr($func_name, strlen($arMatch[0]));
			}
			else
			{
				$var_name = "";
			}

			self::$arAllStr = $arAllStr;
			$func_name = preg_replace_callback("'\x01([0-9]+)\x02's", "PHPParser::getString", $func_name);

			$isComponent2Begin = false;
			$arIncludeComponentFunctionStrings = self::getComponentFunctionStrings();
			foreach($arIncludeComponentFunctionStrings as $functionName)
			{
				$component2Begin = strtoupper($functionName).'(';
				if(strtoupper($func_name) == $component2Begin)
				{
					$isComponent2Begin = true;
					break;
				}
			}
			if($isComponent2Begin)
			{
				$params = substr($new_str, $pos + 1);
				$arParams = PHPParser::GetParams($params);

				$arIncludeParams = array();
				$arFuncParams = array();
				PHPParser::GetParamsRec($arParams[2], $arAllStr, $arIncludeParams);
				PHPParser::GetParamsRec($arParams[4], $arAllStr, $arFuncParams);

				return array(
					"COMPONENT_NAME" => PHPParser::ReplString($arParams[0], $arAllStr),
					"TEMPLATE_NAME" => PHPParser::ReplString($arParams[1], $arAllStr),
					"PARAMS" => $arIncludeParams,
					"PARENT_COMP" => $arParams[3],
					"VARIABLE" => $var_name,
					"FUNCTION_PARAMS" => $arFuncParams,
				);
			}
		}
		return false;
	}

	// Parse file and return all PHP blocks in array
	public static function ParseFile($filesrc, $limit = false)
	{
		$arScripts = array();
		$p = 0;
		$nLen = strlen($filesrc);
		while(($p = strpos($filesrc, "<?", $p))!==false)
		{
			$i = $p+2;
			$bSlashed = false;
			$bInString = false;
			$quote_ch = "";
			while($i < $nLen-1)
			{
				$i++;
				$ch = substr($filesrc, $i, 1);
				if(!$bInString)
				{
					//comment
					if($ch == "/" && $i+1 < $nLen)
					{
						//php tag
						$posnext = strpos($filesrc, "?>", $i);
						if($posnext===false)
						{
							//no final tag
							break;
						}
						$posnext += 2;

						$ti = 0;
						if(substr($filesrc, $i+1, 1)=="*" && ($ti = strpos($filesrc, "*/", $i+2))!==false)
							$ti += 2;
						elseif(substr($filesrc, $i+1, 1)=="/" && ($ti = strpos($filesrc, "\n", $i+2))!==false)
							$ti += 1;

						if($ti)
						{
							// begin ($i) and end ($ti) of comment
							// проверим что раньше конец скрипта или конец комментария (например в одной строке "//comment ? >")
							if($ti>$posnext && substr($filesrc, $i+1, 1)!="*")
							{
								// скрипт закончился раньше комментария
								// вырежем скрипт
								$arScripts[] = array($p, $posnext, substr($filesrc, $p, $posnext-$p));
								break;
							}
							else
							{
								// комментарий закончился раньше скрипта
								$i = $ti - 1;
							}
						}
						continue;
					}

					if($ch == "?" && $i+1 < $nLen && substr($filesrc, $i+1, 1)==">")
					{
						$i = $i+2;
						$arScripts[] = array($p, $i, substr($filesrc, $p, $i-$p));
						break;
					}
				}

				if($bInString && $ch == "\\" && !$bSlashed)
				{
					$bSlashed = true;
					continue;
				}

				if($ch == "\"" || $ch == "'")
				{
					if($bInString)
					{
						if(!$bSlashed && $quote_ch == $ch)
							$bInString = false;
					}
					else
					{
						$bInString = true;
						$quote_ch = $ch;
					}
				}

				$bSlashed = false;
			}
			if($i >= $nLen)
				break;
			if ($limit && count($arScripts) == $limit)
				break;
			$p = $i;
		}
		return $arScripts;
	}

	public static function PreparePHP($str)
	{
		if(substr($str, 0, 2) == "={" && substr($str, -1, 1)=="}" && strlen($str)>3)
			return substr($str, 2, -1);

		return '"'.EscapePHPString($str).'"';
	}

	// Return PHP string of component call params
	public static function ReturnPHPStr($arVals, $arParams)
	{
		$res = "";
		$un = md5(uniqid(""));
		$i=0;
		foreach($arVals as $key=>$val)
		{
			$i++;
			$comm = (strlen($arParams[$key]["NAME"])>0?"$un|$i|// ".$arParams[$key]["NAME"]:"");
			$res .= "\r\n\t\"".$key."\"\t=>\t";
			if(is_array($val) && count($val)>1)
				$res .= "array(".$comm."\r\n";

			if(is_array($val) && count($val)>1)
			{
				$zn = '';
				foreach($val as $p)
				{
					if($zn!='') $zn.=",\r\n";
					$zn .= "\t\t\t\t\t".PHPParser::PreparePHP($p);
				}
				$res .= $zn."\r\n\t\t\t\t),";
			}
			elseif(is_array($val))
			{
				$res .= "array(".PHPParser::PreparePHP($val[0])."),".$comm;
			}
			else
				$res .= PHPParser::PreparePHP($val).",".$comm;
		}

		$max = 0;
		$lngth = array();
		for($j=1; $j<=$i; $j++)
		{
			$p = strpos($res, "$un|$j|");
			$pn = strrpos(substr($res, 0, $p), "\n");
			$l = ($p-$pn);
			$lngth[$j] = $l;
			if($max<$l)
				$max = $l;
		}

		for($j=1; $j<=$i; $j++)
			$res = str_replace($un."|$j|", str_repeat("\t", intval(($max-$lngth[$j]+7)/8)), $res);

		return trim($res, " \t,\r\n");
	}


	public static function ReturnPHPStrRec($arVal, $level, $comm="")
	{
		$result = "";
		$pref = str_repeat("\t", $level+1);
		if (is_array($arVal))
		{
			$result .= "array(".(($level==1) ? $comm : "")."\n";
			foreach ($arVal as $key => $value)
				$result .= $pref."\t".((intval($key)."|" == $key."|") ? $key : PHPParser::PreparePHP($key))." => ".PHPParser::ReturnPHPStrRec($value, $level + 1);
			$result .= $pref."),\n";
		}
		else
		{
			$result .= PHPParser::PreparePHP($arVal).",".(($level==1) ? $comm : "")."\n";
		}
		return $result;
	}

	// Components 2. Return PHP string of component call params
	public static function ReturnPHPStr2($arVals, $arParams=array())
	{
		$res = "";
		foreach($arVals as $key => $val)
		{
			$res .= "\t\t\"".EscapePHPString($key)."\" => ";
			$comm = ($arParams[$key]["NAME"] <> ''? "\t// ".$arParams[$key]["NAME"] : "");
			$res .= PHPParser::ReturnPHPStrRec($val, 1, $comm);
		}

		return trim($res, " \t,\r\n");
	}

	public static function FindComponent($component_name, $filesrc, $src_line)
	{
		/* parse source file for PHP code */
		$arComponents = PHPParser::ParseScript($filesrc);

		/* identify the component by line number */
		$arComponent = false;
		for ($i = 0, $cnt = count($arComponents); $i < $cnt; $i++)
		{
			$nLineFrom = substr_count(substr($filesrc, 0, $arComponents[$i]["START"]), "\n") + 1;
			$nLineTo = substr_count(substr($filesrc, 0, $arComponents[$i]["END"]), "\n") + 1;

			if ($nLineFrom <= $src_line && $nLineTo >= $src_line)
			{
				if ($arComponents[$i]["DATA"]["COMPONENT_NAME"] == $component_name)
				{
					$arComponent = $arComponents[$i];
					break;
				}
			}
			if ($nLineTo > $src_line)
				break;
		}
		return $arComponent;
	}

	public static function getPhpChunks($filesrc, $limit = false)
	{
		$chunks = array();
		$chunk = '';
		$php = false;
		if (function_exists("token_get_all"))
		{
			foreach(token_get_all($filesrc) as $token)
			{
				if ($php)
				{
					if (is_array($token))
					{
						$chunk .= $token[1];
						if ($token[0] === T_CLOSE_TAG)
						{
							$chunks[] = $chunk;
							$chunk = '';
							$php = false;
							if ($limit && count($chunks) == $limit)
								break;
						}
					}
					else
					{
						$chunk .= $token;
					}
				}
				else
				{
					if (is_array($token))
					{
						if ($token[0] === T_OPEN_TAG || $token[0] === T_OPEN_TAG_WITH_ECHO)
						{
							$chunk .= $token[1];
							$php = true;
						}
					}
				}
			}
		}
		else
		{
			foreach (PHPParser::ParseFile($filesrc, $limit) as $chunk)
				$chunks[] = $chunk[2];

		}

		if ($php && $chunk != '')
			$chunks[] = $chunk;

		return $chunks;
	}

	public static function getPageTitle($filesrc, $prolog = false)
	{
		if ($prolog === false)
		{
			$chunks = PHPParser::getPhpChunks($filesrc, 1);
			if (!empty($chunks))
				$prolog = &$chunks[0];
			else
				$prolog = '';
		}

		$title = false;

		if ($prolog != '')
		{
			if(preg_match("/\\\$APPLICATION->SetTitle\\s*\\(\\s*\"(.*?)(?<!\\\\)\"\\s*\\);/is", $prolog, $regs))
				$title = UnEscapePHPString($regs[1]);
			elseif(preg_match("/\\\$APPLICATION->SetTitle\\s*\\(\\s*'(.*?)(?<!\\\\)'\\s*\\);/is", $prolog, $regs))
				$title = UnEscapePHPString($regs[1]);
			elseif(preg_match("'<title[^>]*>([^>]+)</title[^>]*>'i", $prolog, $regs))
				$title = $regs[1];
		}

		if(!$title && preg_match("'<title[^>]*>([^>]+)</title[^>]*>'i", $filesrc, $regs))
			$title = $regs[1];

		return $title;
	}

	public static function getComponentFunctionStrings()
	{
		return array(
			'$APPLICATION->IncludeComponent',
			'EventMessageThemeCompiler::includeComponent'
		);
	}
}
