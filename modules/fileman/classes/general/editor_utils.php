<?
IncludeModuleLangFile(__FILE__);
class CEditorUtils
{
	public static function RenderComponents($arParams)
	{
		global $USER;
		$bLPA = !$USER->CanDoOperation('edit_php');
		$startCount = CEditorUtils::StartFetchCSS(); // Start fetch CSS files
		$arParams['name'] = addslashes($arParams['name']);
		$arParams['template'] = addslashes($arParams['template']);
		$arParams['siteTemplateId'] = addslashes($arParams['siteTemplateId']);

		if ($arParams['siteTemplateId'] && !defined("SITE_TEMPLATE_ID"))
			// define("SITE_TEMPLATE_ID", $arParams['siteTemplateId']);

		// Report only errors
		error_reporting(E_ERROR);

		if ($arParams['name']) // one component by name
			$s = CEditorUtils::_RenderOneComponent($arParams, $bLPA);
		elseif ($arParams['source']) // all components from content
			$s = CEditorUtils::_RenderAllComponents($arParams, $bLPA);

		CEditorUtils::GetCSS($startCount); // Echo path to css

		// Cut out all scripts
		$s = preg_replace("/<script[^>]*?>[\s|\S]*?<\/script>/is", '', $s);
		// Cut out <div class="bx-component-panel"> .... </div>
		$s = preg_replace("/<div[^>]*?class=\"bx-component-panel\"[^>]*?>[^<]*?<table[\s|\S]*?<\/table>[^<]*?<\/div>/is", '', $s);

		echo $s;
	}

	public static function _RenderOneComponent($arParams, $bLPA)
	{
		global $APPLICATION, $USER;

		$arProperties = CEditorUtils::GetCompProperties($arParams['name'], $arParams['template'], $arParams['siteTemplateId']);
		$code = '$APPLICATION->IncludeComponent("'.$arParams['name'].'","'.$arParams['template'].'",';
		$arProperties['BX_EDITOR_RENDER_MODE'] = Array('NAME' => 'Workin in render mode *For Visual editor rendering only*', 'TYPE' => 'CHECKBOX', 'DEFAULT' => 'Y'); // Add description of the system parameter

		$arProps = $arParams['params'];
		if (!$arProps) // Get default properties
		{
			$arProps = array();
			foreach($arProperties as $key => $Prop)
				$arProps[$key] = $Prop['DEFAULT'];
		}
		else
		{
			if ($bLPA)
			{
				$arPHPparams = Array();
				CMain::LPAComponentChecker($arProps, $arPHPparams);
				$len = count($arPHPparams);

				// Replace values from 'DEFAULT' field
				for ($e = 0; $e < $len; $e++)
				{
					$par_name = $arPHPparams[$e];
					$arProps[$par_name] = isset($arProperties[$par_name]['DEFAULT']) ? $arProperties[$par_name]['DEFAULT'] : '';
				}
			}
		}

		foreach($arProps as $key => $val)
		{
			$val = trim($val);
			if ($key != addslashes($key))
			{
				unset($arProps[$key]);
				continue;
			}

			if (strtolower($val) == 'array()')
			{
				$arProps[$key] = Array();
			}
			elseif (substr(strtolower($val), 0, 6) == 'array(')
			{
				$str = array();
				$tArr = array();
				PHPParser::GetParamsRec($val, $str, $tArr);

				if (is_array($tArr))
				{
					foreach($tArr as $k => $v)
					{
						if(substr($v, 0, 2) == "={" && substr($v, -1, 1)=="}" && strlen($v)>3)
							$v = substr($v, 2, -1);
						unset($tArr[$k]);
						$tArr[addslashes($k)] = addslashes(trim($v, " \"'"));
					}
				}
				$arProps[$key] = $tArr;
			}
			else
			{
				$arProps[$key] = addslashes($val);
			}
		}

		$arProps['BX_EDITOR_RENDER_MODE'] = 'Y'; //Set system parameter
		$params = PHPParser::ReturnPHPStr2($arProps, $arParameters);
		$code .= 'Array('.$params.')';
		$code .= ');';

		ob_start();
		echo '#BX_RENDERED_COMPONENT#';
		eval($code);
		echo  '#BX_RENDERED_COMPONENT#';
		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public static function _RenderAllComponents($arParams, $bLPA)
	{
		global $APPLICATION, $USER;
		$s = '';
		$arPHP = PHPParser::ParseFile($arParams['source']);
		$l = count($arPHP);
		if ($l > 0)
		{
			$new_source = '';
			$end = 0;
			$comp_count = 0;
			ob_start();
			for ($n = 0; $n<$l; $n++)
			{
				//Trim php tags
				$src = $arPHP[$n][2];
				if (SubStr($src, 0, 5) == "<?"."php")
					$src = SubStr($src, 5);
				else
					$src = SubStr($src, 2);
				$src = SubStr($src, 0, -2);

				$comp2_begin = '$APPLICATION->INCLUDECOMPONENT(';
				if (strtoupper(substr($src, 0, strlen($comp2_begin))) != $comp2_begin)
					continue;

				$arRes = PHPParser::CheckForComponent2($arPHP[$n][2]);

				if ($arRes)
				{
					$comp_name = CMain::_ReplaceNonLatin($arRes['COMPONENT_NAME']);
					$template_name = CMain::_ReplaceNonLatin($arRes['TEMPLATE_NAME']);
					$arParams = $arRes['PARAMS'];

					$arParams['BX_EDITOR_RENDER_MODE'] = 'Y';

					if ($bLPA)
					{
						$arPHPparams = Array();
						CMain::LPAComponentChecker($arParams, $arPHPparams);
						$len = count($arPHPparams);
					}
					$br = "\r\n";
					$code = '$APPLICATION->IncludeComponent('.$br.
						"\t".'"'.$comp_name.'",'.$br.
						"\t".'"'.$template_name.'",'.$br;
					// If exist at least one parameter with php code inside
					if (count($arParams) > 0)
					{
						// Get array with description of component params
						$arCompParams = CComponentUtil::GetComponentProps($comp_name);
						$arTemplParams = CComponentUtil::GetTemplateProps($comp_name,$template_name,$template);

						$arParameters = array();
						if (isset($arCompParams["PARAMETERS"]) && is_array($arCompParams["PARAMETERS"]))
							$arParameters = $arParameters + $arCompParams["PARAMETERS"];
						if (is_array($arTemplParams))
							$arParameters = $arParameters + $arTemplParams;

						if ($bLPA)
						{
							// Replace values from 'DEFAULT'
							for ($e = 0; $e < $len; $e++)
							{
								$par_name = $arPHPparams[$e];
								$arParams[$par_name] = isset($arParameters[$par_name]['DEFAULT']) ? $arParameters[$par_name]['DEFAULT'] : '';
							}
						}

						foreach($arParams as $key => $val)
						{
							if ($key != addslashes($key))
								unset($arParams[$key]);
							else
								$arParams[$key] = addslashes($val);
						}

						//ReturnPHPStr
						$params = PHPParser::ReturnPHPStr2($arParams, $arParameters);
						$code .= "\t".'Array('.$br."\t".$params.$br."\t".')';
					}
					else
					{
						$code .=  "\t".'Array()';
					}
					$parent_comp = CMain::_ReplaceNonLatin($arRes['PARENT_COMP']);
					$arExParams_ = $arRes['FUNCTION_PARAMS'];
					$bEx = isset($arExParams_) && is_array($arExParams_) && count($arExParams_) > 0;
					if (!$parent_comp || strtolower($parent_comp) == 'false')
						$parent_comp = false;
					if ($parent_comp)
					{
						if ($parent_comp == 'true' || intVal($parent_comp) == $parent_comp)
							$code .= ','.$br."\t".$parent_comp;
						else
							$code .= ','.$br."\t\"".$parent_comp.'"';
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
						$code .= ','.$br."\tArray(".$exParams.')';
					}
					$code .= $br.');';

					echo '#BX_RENDERED_COMPONENT_'.$comp_count.'#';
					eval($code);
					echo  '#BX_RENDERED_COMPONENT_'.$comp_count.'#';
				}
				$comp_count++;
			}
			$s = ob_get_contents();
			ob_end_clean();
		}
		return $s;
	}

	public static function GetCompProperties($name, $template = '', $siteTemplateId = '', $arCurVals = array())
	{
		$stid = $siteTemplateId;
		$arProps = CComponentUtil::GetComponentProps($name, $arCurVals);
		$arTemplateProps = CComponentUtil::GetTemplateProps($name, $template, $stid, $arCurVals);
		return $arProps['PARAMETERS'] + $arTemplateProps;
	}

	public static function StartFetchCSS()
	{
		return count($GLOBALS['APPLICATION']->sPath2css);
	}

	public static function GetCSS($startCount)
	{
		global $APPLICATION;
		$arCSS = array();
		$res = '';
		$curCount = count($APPLICATION->sPath2css);
		if ($curCount <= $startCount)
			return;

		for ($i = $startCount; $i < $curCount; $i++)
		{
			$path = $APPLICATION->sPath2css[$i];
			if (!in_array($path, $arCSS))
				$arCSS[] = $path;
		}

		echo "<script>window.arUsedCSS = [];\n";
		for ($i = 0, $l = count($arCSS); $i < $l; $i++)
		{
			$path = $arCSS[$i];
			if (strpos($path, '?') !== false)
				$path = substr($path, 0, strpos($path, '?'));
			$filename = $_SERVER["DOCUMENT_ROOT"].$path;
			if (file_exists($filename))
				echo 'window.arUsedCSS.push("'.$path.'");'."\n";
		}
		echo '</script>';
	}

	public static function UnJSEscapeArray($ar)
	{
		//$APPLICATION->UnJSEscape
		foreach($ar as $key => $val)
		{
			if (is_array($val))
				$ar[$key] = CEditorUtils::UnJSEscapeArray($val);
			elseif (is_string($val))
				$ar[$key] = $GLOBALS['APPLICATION']->UnJSEscape($val);
		}
		return $ar;
	}
}
?>