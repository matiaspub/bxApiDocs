<?php

/**
 * Class LPA
 *
 * Limited PHP Access.
 */
class LPA
{
	public static function PrepareContent($filesrc)
	{
		$arPHP = PHPParser::ParseFile($filesrc);
		$l = count($arPHP);
		if ($l > 0)
		{
			$new_filesrc = '';
			$end = 0;
			$php_count = 0;
			for ($n = 0; $n<$l; $n++)
			{
				$start = $arPHP[$n][0];
				$new_filesrc .= substr($filesrc,$end,$start-$end);
				$end = $arPHP[$n][1];

				//Trim php tags
				$src = $arPHP[$n][2];
				if (substr($src, 0, 5) == "<?"."php")
					$src = substr($src, 5);
				else
					$src = substr($src, 2);
				$src = substr($src, 0, -2);

				//If it's Component 2, keep the php code. If it's component 1 or ordinary PHP - than replace code by #PHPXXXX# (XXXX - count of PHP scripts)
				$isComponent2Begin = false;
				$arIncludeComponentFunctionStrings = PHPParser::getComponentFunctionStrings();
				foreach($arIncludeComponentFunctionStrings as $functionName)
				{
					$comp2_begin = strtoupper($functionName).'(';
					if(strtoupper(substr($src,0, strlen($comp2_begin))) == $comp2_begin)
					{
						$isComponent2Begin = true;
						break;
					}
				}
				if ($isComponent2Begin)
					$new_filesrc .= $arPHP[$n][2];
				else
					$new_filesrc .= '#PHP'.str_pad(++$php_count, 4, "0", STR_PAD_LEFT).'#';
			}
			$new_filesrc .= substr($filesrc,$end);
			$filesrc = $new_filesrc;
		}

		return $filesrc;
	}

	public static function Process($filesrc = false, $old_filesrc = false)
	{
		if ($filesrc === false)
			return '';

		// Find all php fragments in $filesrc and:
		// 	1. Kill all non-component 2.0 fragments
		// 	2. Get and check params of components
		$arPHP = PHPParser::ParseFile($filesrc);
		$l = count($arPHP);
		if ($l > 0)
		{
			$new_filesrc = '';
			$end = 0;
			for ($n = 0; $n<$l; $n++)
			{
				$start = $arPHP[$n][0];
				$new_filesrc .= self::EncodePHPTags(substr($filesrc,$end,$start-$end));
				$end = $arPHP[$n][1];

				//Trim php tags
				$src = $arPHP[$n][2];
				if (substr($src, 0, 5) == "<?php")
					$src = '<?'.substr($src, 5);

				//If it's Component 2 - we handle it's params, non components2 will be somehow erased
				$success = false;
				$isComponent2Begin = false;
				$component2FunctionName = '';
				$arIncludeComponentFunctionStrings = PHPParser::getComponentFunctionStrings();
				foreach($arIncludeComponentFunctionStrings as $functionName)
				{
					$comp2_begin = '<?'.strtoupper($functionName).'(';
					if(strtoupper(substr($src,0, strlen($comp2_begin))) == $comp2_begin)
					{
						$isComponent2Begin = true;
						$component2FunctionName = $functionName;
						break;
					}
				}
				if ($isComponent2Begin)
				{
					$arRes = PHPParser::CheckForComponent2($src);

					if ($arRes)
					{
						$success = true;
						$comp_name = $arRes['COMPONENT_NAME'];
						$template_name = $arRes['TEMPLATE_NAME'];
						$arParams = $arRes['PARAMS'];
						$arPHPparams = array();
						self::ComponentChecker($arParams, $arPHPparams);
						$len = count($arPHPparams);
						$br = "\r\n";
						$code = $component2FunctionName.'('.$br.
							"\t".'"'.EscapePHPString($comp_name).'",'.$br.
							"\t".'"'.EscapePHPString($template_name).'",'.$br;
						// If exist at least one parameter with php code inside
						if (count($arParams) > 0)
						{
							// Get array with description of component params
							$arCompParams = CComponentUtil::GetComponentProps($comp_name);
							$arTemplParams = CComponentUtil::GetTemplateProps($comp_name, $template_name);

							$arParameters = array();
							if (isset($arCompParams["PARAMETERS"]) && is_array($arCompParams["PARAMETERS"]))
								$arParameters = $arParameters + $arCompParams["PARAMETERS"];
							if (is_array($arTemplParams))
								$arParameters = $arParameters + $arTemplParams;

							// Replace values from 'DEFAULT'
							for ($e = 0; $e < $len; $e++)
							{
								$par_name = $arPHPparams[$e];
								$arParams[$par_name] = isset($arParameters[$par_name]['DEFAULT']) ? $arParameters[$par_name]['DEFAULT'] : '';
							}

							//ReturnPHPStr
							$params = PHPParser::ReturnPHPStr2($arParams, $arParameters);
							$code .= "\t".'array('.$br."\t".$params.$br."\t".')';
						}
						else
						{
							$code .=  "\t".'array()';
						}
						$parent_comp = $arRes['PARENT_COMP'];
						$arExParams_ = $arRes['FUNCTION_PARAMS'];

						$bEx = isset($arExParams_) && is_array($arExParams_) && count($arExParams_) > 0;

						if (!$parent_comp || strtolower($parent_comp) == 'false')
							$parent_comp = false;
						if ($parent_comp)
						{
							if ($parent_comp == 'true' || is_numeric($parent_comp))
								$code .= ','.$br."\t".$parent_comp;
							else
								$code .= ','.$br."\t\"".EscapePHPString($parent_comp).'"';
						}
						if ($bEx)
						{
							if (!$parent_comp)
								$code .= ','.$br."\tfalse";

							$arExParams = array();
							foreach ($arExParams_ as $k => $v)
							{
								$k = CMain::_ReplaceNonLatin($k);
								$v = CMain::_ReplaceNonLatin($v);
								if (strlen($k) > 0 && strlen($v) > 0)
									$arExParams[$k] = $v;
							}
							$exParams = PHPParser::ReturnPHPStr2($arExParams);
							$code .= ','.$br."\tarray(".$exParams.')';
						}
						$code .= $br.');';
						$code = '<?'.$code.'?>';
						$new_filesrc .= $code;
					}
				}
				if(!$success)
				{
					$new_filesrc .= "<??>";
				}
			}
			$new_filesrc .= self::EncodePHPTags(substr($filesrc, $end));
			$filesrc = $new_filesrc;
		}
		else
		{
			$filesrc = self::EncodePHPTags($filesrc);
		}

		if (strpos($filesrc, '#PHP') !== false && $old_filesrc !== false) // We have to handle php fragments
		{
			// Get array of PHP scripts from old saved file
			$arPHP = PHPParser::ParseFile($old_filesrc);
			$arPHPscripts = array();
			$l = count($arPHP);
			if ($l > 0)
			{
				$new_filesrc = '';
				$end = 0;
				for ($n = 0; $n < $l; $n++)
				{
					$start = $arPHP[$n][0];
					$new_filesrc .= substr($old_filesrc, $end, $start - $end);
					$end = $arPHP[$n][1];
					$src = $arPHP[$n][2];
					$src = substr($src, (substr($src, 0, 5) == "<?"."php") ? 5 : 2, -2); // Trim php tags

					$isComponent2Begin = false;
					$arIncludeComponentFunctionStrings = PHPParser::getComponentFunctionStrings();
					foreach($arIncludeComponentFunctionStrings as $functionName)
					{
						$comp2_begin = strtoupper($functionName).'(';
						if(strtoupper(substr($src,0, strlen($comp2_begin))) == $comp2_begin)
						{
							$isComponent2Begin = true;
							break;
						}
					}
					if (!$isComponent2Begin)
						$arPHPscripts[] = $src;
				}
			}

			// Ok, so we already have array of php scripts lets check our new content
			// LPA-users CAN delete PHP fragments and swap them but CAN'T add new or modify existent:
			while (preg_match('/#PHP\d{4}#/i'.BX_UTF_PCRE_MODIFIER, $filesrc, $res))
			{
				$php_begin = strpos($filesrc, $res[0]);
				$php_fr_num = intval(substr($filesrc, $php_begin + 4, 4)) - 1; // Number of PHP fragment from #PHPXXXX# conctruction

				if (isset($arPHPscripts[$php_fr_num]))
				{
					$code = '<?'.$arPHPscripts[$php_fr_num].'?>';
				}
				else
				{
					$code = '<??>';
				}
				$filesrc = substr($filesrc, 0, $php_begin).$code.substr($filesrc, $php_begin + 9);
			}
		}

		return $filesrc;
	}

	public static function EncodePHPTags($str)
	{
		$str = str_replace(array("<?","?>", "<%", "%>"),array("&lt;?","?&gt;","&lt;%","%&gt;"), $str);

		static $pattern = "/(<script[^>]*language\\s*=\\s*)('|\"|)php('|\"|)([^>]*>)/i";
		$str = preg_replace($pattern, "&lt;??&gt;", $str);

		return $str;
	}

	public static function ComponentChecker(&$arParams, &$arPHPparams, $parentParamName = false)
	{
		//all php fragments wraped by ={}
		foreach ($arParams as $param_name => $paramval)
		{
			if (substr($param_name, 0, 2) == '={' && substr($param_name, -1) == '}')
			{
				$key = substr($param_name, 2, -1);
				if (strval($key) !== strval(intval($key)))
				{
					unset($arParams[$param_name]);
					continue;
				}
			}
			if (is_array($paramval))
			{
				self::ComponentChecker($paramval, $arPHPparams, ($parentParamName !== false? $parentParamName : $param_name));
				$arParams[$param_name] = $paramval;
			}
			elseif (substr($paramval, 0, 2) == '={' && substr($paramval, -1) == '}')
			{
				$arPHPparams[] = ($parentParamName !== false? $parentParamName : $param_name);
			}
		}
	}
}
