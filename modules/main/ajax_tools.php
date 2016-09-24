<?
IncludeModuleLangFile(__FILE__);

define ('BX_AJAX_PARAM_ID', 'bxajaxid');

class CAjax
{
	public static function Init()
	{
		// old version should be here because of compatibility
		global $APPLICATION;

		$APPLICATION->SetTemplateCSS('ajax/ajax.css');
		$APPLICATION->AddHeadScript('/bitrix/js/main/ajax.js');
	}

	public static function GetComponentID($componentName, $componentTemplate, $additionalID)
	{
		$aTrace = Bitrix\Main\Diag\Helper::getBackTrace(0, DEBUG_BACKTRACE_IGNORE_ARGS);

		$trace_count = count($aTrace);
		$trace_current = $trace_count-1;
		for ($i = 0; $i<$trace_count; $i++)
		{
			if (strtolower($aTrace[$i]['function']) == 'includecomponent' && (($c = strtolower($aTrace[$i]['class'])) == 'callmain' || $c == 'cmain'))
			{
				$trace_current = $i;
				break;
			}
		}

		$sSrcFile = strtolower(str_replace("\\", "/", $aTrace[$trace_current]["file"]));
		$iSrcLine = intval($aTrace[$trace_current]["line"]);

		$bSrcFound = false;

		if($iSrcLine > 0 && $sSrcFile <> "")
		{
			// try to covert absolute path to file within DOCUMENT_ROOT
			$doc_root = rtrim(str_replace(Array("\\\\", "//", "\\"), Array("\\", "/", "/"), realpath($_SERVER["DOCUMENT_ROOT"])), "\\/");
			$doc_root = strtolower($doc_root);

			if(strpos($sSrcFile, $doc_root."/") === 0)
			{
				//within
				$sSrcFile = substr($sSrcFile, strlen($doc_root));
				$bSrcFound = true;
			}
			else
			{
				//outside
				$sRealBitrix = strtolower(str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]."/bitrix")));

				if(strpos($sSrcFile, substr($sRealBitrix, 0, -6)) === 0)
				{
					$sSrcFile = substr($sSrcFile, strlen($sRealBitrix) - 7);
					$bSrcFound = true;
				}
				else
				{
					// special hack
					$sRealBitrixModules = strtolower(str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules")));
					if(strpos($sSrcFile, $sRealBitrixModules) === 0)
					{
						$sSrcFile = "/bitrix/modules".substr($sSrcFile, strlen($sRealBitrixModules));
						$bSrcFound = true;
					}
					else
					{
						// next special hack
						$matches = array();
						if (preg_match("#(?<=/modules/)[^/]+(?=/install/components/bitrix/)#", $sSrcFile, $matches))
						{
							$sRealBitrixComponentsDir = strtolower(str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$matches[0]."/install/components/bitrix")));
							if(strpos($sSrcFile, $sRealBitrixComponentsDir) === 0)
							{
								$sSrcFile = "/bitrix/components/bitrix".substr($sSrcFile, strlen($sRealBitrixComponentsDir));
								$bSrcFound = true;
							}
						}
					}
				}
			}
		}

		if (!$bSrcFound)
			return false;

		$session_string = $sSrcFile.'|'.$iSrcLine.'|'.$componentName;

		if (strlen($componentTemplate) > 0)
			$session_string .= '|'.$componentTemplate;
		else
			$session_string .= '|.default';

		$session_string .= '|'.$additionalID;

		return md5($session_string);
	}

	public static function GetSession()
	{
		if (is_set($_REQUEST, BX_AJAX_PARAM_ID))
			return $_REQUEST[BX_AJAX_PARAM_ID];
		else
			return false;
	}

	public static function GetSessionParam($ajax_id = false)
	{
		if (!$ajax_id) $ajax_id = CAjax::GetSession();
		if (!$ajax_id) return '';
		else return BX_AJAX_PARAM_ID.'='.$ajax_id;
	}

	public static function AddSessionParam($url, $ajax_id = false)
	{
		$url_anchor = strstr($url, '#');
		if ($url_anchor !== false)
			$url = substr($url, 0, -strlen($url_anchor));

		$url .= strpos($url, "?") === false ? '?' : '&';
		$url .= CAjax::GetSessionParam($ajax_id);

		if (is_set($_REQUEST['AJAX_CALL'])) $url .= '&AJAX_CALL=Y';

		if ($url_anchor !== false)
			$url .= $url_anchor;

		return $url;
	}

	// $text = htmlspecialcharred
	public static function GetLinkEx($real_url, $public_url, $text, $container_id, $additional = '')
	{
		if (!$public_url)
		{
			$public_url = $real_url;
		}

		return sprintf(
			'<a href="%s" onclick="BX.ajax.insertToNode(\'%s\', \'%s\'); return false;" %s>%s</a>',

			htmlspecialcharsbx($public_url),
			CUtil::JSEscape(htmlspecialcharsbx($real_url)),
			CUtil::JSEscape(htmlspecialcharsbx($container_id)),
			$additional,
			$text
		);
	}

	// $text - no htmlspecialchar
	public static function GetLink($url, $text, $container_id, $additional = '')
	{
		return CAjax::GetLinkEx($url, false, htmlspecialcharsbx($text), htmlspecialcharsbx($container_id), $additional);
	}

	public static function GetForm($form_params, $container_id, $ajax_id, $bReplace = true, $bShadow = true)
	{
		static $rndGenerator = null;
		if (!$rndGenerator)
			$rndGenerator = new \Bitrix\Main\Type\RandomSequence("BX_AJAX");
		$rnd = $rndGenerator->randString(6);
		return '
<form '.trim($form_params).'><input type="hidden" name="'.BX_AJAX_PARAM_ID.'" id="'.BX_AJAX_PARAM_ID.'_'.$ajax_id.'_'.$rnd.'" value="'.$ajax_id.'" /><input type="hidden" name="AJAX_CALL" value="Y" /><script type="text/javascript">
function _processform_'.$rnd.'(){
	var obForm = top.BX(\''.BX_AJAX_PARAM_ID.'_'.$ajax_id.'_'.$rnd.'\').form;
	top.BX.bind(obForm, \'submit\', function() {'.CAjax::GetFormEventValue($container_id, $bReplace, $bShadow, '"').'});
	top.BX.removeCustomEvent(\'onAjaxSuccess\', _processform_'.$rnd.');
}
if (top.BX(\''.BX_AJAX_PARAM_ID.'_'.$ajax_id.'_'.$rnd.'\'))
	_processform_'.$rnd.'();
else
	top.BX.addCustomEvent(\'onAjaxSuccess\', _processform_'.$rnd.');
</script>';
	}

	public static function ClearForm($form_params, $ajax_id = false)
	{
		$form_params = str_replace(CAjax::GetSessionParam($ajax_id), '', $form_params);

		return '<form '.trim($form_params).'>';
	}

	public static function GetFormEvent($container_id)
	{
		return 'onsubmit="BX.ajax.submitComponentForm(this, \''.htmlspecialcharsbx(CUtil::JSEscape($container_id)).'\', true);"';
	}

	public static function GetFormEventValue($container_id, $bReplace = true, $bShadow = true, $event_delimiter = '\'')
	{
		$delimiter = $event_delimiter == '\'' ? '"' : '\'';
		return 'BX.ajax.submitComponentForm(this, '.$delimiter.CUtil::JSEscape($container_id).$delimiter.', true)';
		//return 'jsAjaxUtil.'.($bReplace ? 'Insert' : 'Append').'FormDataToNode(this, '.$delimiter.$container_id.$delimiter.', '.($bShadow ? 'true' : 'false').')';
	}

	public static function encodeURI($str)
	{
		//$str = 'view'.$str;
		return $str;
	}

	public static function decodeURI($str)
	{
		global $APPLICATION;

		$pos = strpos($str, 'view');
		if ($pos !== 0)
		{
			$APPLICATION->ThrowException(GetMessage('AJAX_REDIRECTOR_BAD_URL'));
			return false;
		}

		$str = str_replace(array("\r", "\n"), "", substr($str, 4));

		if (preg_match("'^(/bitrix/|http://|https://|ftp://)'i", $str))
		{
			$APPLICATION->ThrowException(GetMessage('AJAX_REDIRECTOR_BAD_URL'));
			return false;
		}

		return $str;
	}
}
?>