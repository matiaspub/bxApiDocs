<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CComponentAjax
{
	var $componentID = '';

	var $bAjaxSession = false;
	var $bIFrameMode = false;

	var $componentName;
	var $componentTemplate;
	var $arParams;

	var $arCSSList;
	var $arHeadScripts;

	var $bShadow = true;
	var $bJump = true;
	var $bStyle = true;
	var $bHistory = true;

	var $bWrongRedirect = false;

	var $buffer_start_counter;
	var $buffer_finish_counter;

	var $bRestartBufferCalled;
	var $RestartBufferHandlerId;
	var $LocalRedirectHandlerId;

	var $currentUrl = false;
	var $dirname_currentUrl = false;
	var $basename_currentUrl = false;

	var $__nav_params = null;

	public function __construct($componentName, $componentTemplate, &$arParams, $parentComponent)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;

		if ($USER->IsAdmin())
		{
			if ($_GET['bitrix_disable_ajax'] == 'N')
			{
				unset($_SESSION['bitrix_disable_ajax']);
			}

			if ($_GET['bitrix_disable_ajax'] == 'Y' || $_SESSION['bitrix_disable_ajax'] == 'Y')
			{
				$_SESSION['bitrix_disable_ajax'] = 'Y';
				return null;
			}
		}

		if ($parentComponent && $this->_checkParent($parentComponent))
			return false;

		$this->componentName = $componentName;
		$this->componentTemplate = $componentTemplate;
		$this->arParams = $arParams;

		$this->bShadow = $this->arParams['AJAX_OPTION_SHADOW'] != 'N';
		$this->bJump = $this->arParams['AJAX_OPTION_JUMP'] != 'N';
		$this->bStyle = $this->arParams['AJAX_OPTION_STYLE'] != 'N';
		$this->bHistory = $this->arParams['AJAX_OPTION_HISTORY'] != 'N';

		if (!$this->CheckSession())
			return false;

		CJSCore::Init(array('ajax'));

		$arParams['AJAX_ID'] = $this->componentID;

		if ($this->bAjaxSession)
		{
			// dirty hack: try to get breadcrumb call params
			for ($i = 0, $cnt = count($APPLICATION->buffer_content_type); $i < $cnt; $i++)
			{
				if ($APPLICATION->buffer_content_type[$i]['F'][1] == 'GetNavChain')
				{
					$this->__nav_params = $APPLICATION->buffer_content_type[$i]['P'];
				}
			}

			$APPLICATION->RestartBuffer();

			// define('PUBLIC_AJAX_MODE', 1);

			if (is_set($_REQUEST, 'AJAX_CALL'))
			{
				$this->bIFrameMode = true;
			}
		}

		if ($this->bStyle)
			$this->arCSSList = $APPLICATION->sPath2css;

		$this->arHeadScripts = $APPLICATION->arHeadScripts;

		if (!$this->bAjaxSession)
			$APPLICATION->AddBufferContent(array($this, '__BufferDelimiter'));

		$this->buffer_start_counter = count($APPLICATION->buffer_content);

		$this->LocalRedirectHandlerId = AddEventHandler('main', 'OnBeforeLocalRedirect', array($this, "LocalRedirectHandler"));
		$this->RestartBufferHandlerId = AddEventHandler('main', 'OnBeforeRestartBuffer', array($this, 'RestartBufferHandler'));

		return null;
	}

	/**
	 * @param CBitrixComponent $parent
	 * @return bool
	 */
	public function _checkParent($parent)
	{
		if ('Y' == $parent->arParams['AJAX_MODE'])
			return true;
		elseif (($parentComponent = $parent->GetParent()))
			return $this->_checkParent($parentComponent);

		return false;
	}

	public static function __BufferDelimiter()
	{
		return '';
	}

	public function __removeHandlers()
	{
		RemoveEventHandler('main', 'OnBeforeRestartBuffer', $this->RestartBufferHandlerId);
		RemoveEventHandler('main', 'OnBeforeLocalRedirect', $this->LocalRedirectHandlerId);
	}

	public function RestartBufferHandler()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->bRestartBufferCalled = true;
		//ob_end_clean();

		$APPLICATION->AddBufferContent(array($this, '__BufferDelimiter'));
		$this->buffer_start_counter = count($APPLICATION->buffer_content);

		$this->__removeHandlers();
	}

	public function LocalRedirectHandler(&$url)
	{
		if (!$this->bAjaxSession) return;

		if ($this->__isAjaxURL($url))
		{
			if (!$this->bIFrameMode)
				Header('X-Bitrix-Ajax-Status: OK');
		}
		else
		{
			if (!$this->bRestartBufferCalled)
				ob_end_clean();

			if (!$this->bIFrameMode)
				Header('X-Bitrix-Ajax-Status: Redirect');

			$this->bWrongRedirect = true;

			echo '<script type="text/javascript">'.($this->bIFrameMode ? 'top.' : 'window.').'location.href = \''.CUtil::JSEscape($url).'\'</script>';

			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
			exit();
		}

		$url = CAjax::AddSessionParam($url, $this->componentID);

		$this->__removeHandlers();
	}

	public function CheckSession()
	{
		if ($this->componentID = CAjax::GetComponentID($this->componentName, $this->componentTemplate, $this->arParams['AJAX_OPTION_ADDITIONAL']))
		{
			if ($current_session = CAjax::GetSession())
			{
				if ($this->componentID == $current_session)
				{
					$this->bAjaxSession = true;
					return true;
				}
				else
				{
					return false;
				}
			}
			return true;
		}
		return false;
	}

	public static function __GetSEFRealUrl($url)
	{
		$arResult = CUrlRewriter::GetList(array('QUERY' => $url));

		if (is_array($arResult) && count($arResult) > 0)
			return $arResult[0]['PATH'];
		else
			return false;
	}

	public function __isAjaxURL($url)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(preg_match("/^(#|mailto:|javascript:|callto:)/", $url))
			return false;

		if (strpos($url, '://') !== false)
			return false;

		$url = preg_replace('/#.*/', '', $url);

		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			if ($url == POST_FORM_ACTION_URI)
				return true;

			$test_str = '/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=';
			if (strncmp($url, $test_str, 52) === 0)
			{
				$url = urldecode(substr($url, 52));
			}

			$url = $this->__GetSEFRealUrl($url);

			if ($url === false)
				return false;
		}
		else
		{
			if (strpos($url, '?') !== false)
				$url = substr($url, 0, strpos($url, '?'));

			if (substr($url, -4) != '.php')
			{
				if (substr($url, -1) != '/')
					$url .= '/';

				$url .= 'index.php';
			}
		}

		if (!$this->currentUrl)
		{
			$currentUrl = $APPLICATION->GetCurPage();

			if ($this->arParams['SEF_MODE'] == 'Y')
				$currentUrl = $this->__getSEFRealUrl($currentUrl);

			if (strpos($currentUrl, '?') !== false)
				$currentUrl = substr($currentUrl, 0, strpos($currentUrl, '?'));

			if (substr($currentUrl, -4) != '.php')
			{
				if (substr($currentUrl, -1) != '/')
					$currentUrl .= '/';

				$currentUrl .= 'index.php';
			}

			$this->currentUrl = $currentUrl;
			$this->dirname_currentUrl = dirname($currentUrl);
			$this->basename_currentUrl = basename($currentUrl);
		}

		$dirname = dirname($url);
		if (
			(
				$dirname == $this->dirname_currentUrl
				||
				$dirname == ''
				||
				$dirname == '.'
			)
			&&
			basename($url) == $this->basename_currentUrl
		)
			return true;

		return false;
	}

	public static function _checkPcreLimit($data)
	{
		$pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		$text_len = function_exists('mb_strlen') ? mb_strlen($data, 'latin1') : strlen($data);
		$text_len++;

		if ($pcre_backtrack_limit > 0 && $pcre_backtrack_limit < $text_len)
		{
			@ini_set("pcre.backtrack_limit", $text_len);
			$pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		}

		return $pcre_backtrack_limit >= $text_len;
	}

	public function __PrepareLinks(&$data)
	{
		$add_param = CAjax::GetSessionParam($this->componentID);

		$regexp_links = '/(<a[^>]*?>.*?<\/a>)/is'.BX_UTF_PCRE_MODIFIER;
		$regexp_params = '/([\w\-]+)\s*=\s*([\"\'])(.*?)\2/is'.BX_UTF_PCRE_MODIFIER;

		$this->_checkPcreLimit($data);
		$arData = preg_split($regexp_links, $data, -1, PREG_SPLIT_DELIM_CAPTURE);

		$cData = count($arData);
		if($cData < 2)
			return;

		$arIgnoreAttributes = array('onclick' => true, 'target' => true);
		$arSearch = array(
			$add_param.'&',
			$add_param,
			'AJAX_CALL=Y&',
			'AJAX_CALL=Y'
		);
		$bDataChanged = false;

		for($iData = 1; $iData < $cData; $iData += 2)
		{
			if(!preg_match('/^<a([^>]*?)>(.*?)<\/a>$/is'.BX_UTF_PCRE_MODIFIER, $arData[$iData], $match))
				continue;

			$params = $match[1];

			if(!preg_match_all($regexp_params, $params, $arLinkParams))
				continue;

			$strAdditional = ' ';
			$url_key = -1;
			$bIgnoreLink = false;

			foreach ($arLinkParams[0] as $pkey => $value)
			{
				if ($value == '')
					continue;

				$param_name = strtolower($arLinkParams[1][$pkey]);

				if ($param_name === 'href')
					$url_key = $pkey;
				elseif (isset($arIgnoreAttributes[$param_name]))
				{
					$bIgnoreLink = true;
					break;
				}
				else
					$strAdditional .= $value.' ';
			}

			if ($url_key >= 0 && !$bIgnoreLink)
			{
				$url = \Bitrix\Main\Text\Converter::getHtmlConverter()->decode($arLinkParams[3][$url_key]);
				$url = str_replace($arSearch, '', $url);

				if ($this->__isAjaxURL($url))
				{
					$real_url = $url;

					$pos = strpos($url, '#');
					if ($pos !== false)
						$real_url = substr($real_url, 0, $pos);

					$real_url .= strpos($url, '?') === false ? '?' : '&';
					$real_url .= $add_param;

					$url_str = CAjax::GetLinkEx($real_url, $url, $match[2], 'comp_'.$this->componentID, $strAdditional);

					$arData[$iData] = $url_str;
					$bDataChanged = true;
				}
			}
		}

		if($bDataChanged)
			$data = implode('', $arData);
	}

	public function __PrepareForms(&$data)
	{
		$this->_checkPcreLimit($data);
		$arData = preg_split('/(<form([^>]*)>)/i'.BX_UTF_PCRE_MODIFIER, $data, -1, PREG_SPLIT_DELIM_CAPTURE);

		$bDataChanged = false;
		for ($key = 0, $l = count($arData); $key < $l; $key++)
		{
			if ($key % 3 != 0)
			{
				$arIgnoreAttributes = array('target');
				$bIgnore = false;
				foreach ($arIgnoreAttributes as $attr)
				{
					if (strpos($arData[$key], $attr.'="') !== false)
					{
						$bIgnore = true;
						break;
					}
				}

				if (!$bIgnore)
				{
					preg_match_all('/action=(["\']{1})(.*?)\1/i', $arData[$key], $arAction);
					$url = $arAction[2][0];

					if ($url === '' || $this->__isAjaxURL($url))
					{
						$arData[$key] = CAjax::GetForm($arData[$key+1], 'comp_'.$this->componentID, $this->componentID, true, $this->bShadow);
					}
					else
					{
						$new_url = str_replace(CAjax::GetSessionParam($this->componentID), '', $url);
						$arData[$key] = str_replace($url, $new_url, $arData[$key]);
					}

					$bDataChanged = true;
				}

				unset($arData[$key+1]);
				$key++;
			}

		}

		if ($bDataChanged)
			$data = implode('', $arData);
	}

	public function __prepareScripts(&$data)
	{
		$regexp = '/(<script(?:[^>]*)?>)(.*?)<\/script>/is'.BX_UTF_PCRE_MODIFIER;

		$this->_checkPcreLimit($data);
		$scripts_num = preg_match_all($regexp, $data, $out);

		$arScripts = array();

		if (false !== $scripts_num)
		{
			for ($i = 0; $i < $scripts_num; $i++)
			{
				$data = str_replace($out[0][$i], '', $data);

				if (strlen($out[1][$i]) > 0 && strpos($out[1][$i], 'src=') !== false)
				{
					$regexp_src = '/src="([^"]*)?"/i';
					if (preg_match($regexp_src, $out[1][$i], $out1) != 0)
					{
						$arScripts[] = array(
							'TYPE' => 'SCRIPT_SRC',
							'DATA' => $out1[1],
						);

					}
				}
				else
				{
					$out[2][$i] = str_replace('<!--', '', $out[2][$i]);
					$arScripts[] = array(
						'TYPE' => 'SCRIPT',
						'DATA' => $out[2][$i],
					);
				}
			}
		}

		if (count($arScripts) > 0)
		{
			$data .= "
<script type=\"text/javascript\">
top.bxcompajaxframeonload = function() {
	top.BX.CaptureEventsGet();
	top.BX.CaptureEvents(top, 'load');
	top.BX.evalPack(".CUtil::PhpToJsObject($arScripts).");
	setTimeout('top.BX.ajax.__runOnload();', 300);
}</script>
";
		}
	}

	function _PrepareAdditionalData()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		// get CSS changes list
		if ($this->bStyle)
		{
			$arCSSList = $APPLICATION->sPath2css;
			$cnt_old = count($this->arCSSList);
			$cnt_new = count($arCSSList);
			$arCSSNew = array();

			if ($cnt_old != $cnt_new)
				for ($i = $cnt_old; $i<$cnt_new; $i++)
				{
					$css_path = $arCSSList[$i];
					if(strtolower(substr($css_path, 0, 7)) != 'http://' && strtolower(substr($css_path, 0, 8)) != 'https://')
					{
						if(($p = strpos($css_path, "?"))>0)
							$css_file = substr($css_path, 0, $p);
						else
							$css_file = $css_path;

						if(file_exists($_SERVER["DOCUMENT_ROOT"].$css_file))
							$arCSSNew[] = $arCSSList[$i];
					}
					else
						$arCSSNew[] = $arCSSList[$i];
				}
		}

		// get scripts changes list
		$arHeadScripts = $APPLICATION->arHeadScripts;

		$cnt_old = count($this->arHeadScripts);
		$cnt_new = count($arHeadScripts);
		$arHeadScriptsNew = array();


		if ($cnt_old != $cnt_new)
			for ($i = $cnt_old; $i<$cnt_new; $i++)
				$arHeadScriptsNew[] = $arHeadScripts[$i];

		if(!$APPLICATION->oAsset->optimizeJs())
		{
			$arHeadScriptsNew = array_merge(CJSCore::GetScriptsList(), $arHeadScriptsNew);
		}

		// prepare additional data
		$arAdditionalData = array();
		$arAdditionalData['TITLE'] = htmlspecialcharsback($APPLICATION->GetTitle());
		$arAdditionalData['WINDOW_TITLE'] = htmlspecialcharsback($APPLICATION->GetTitle('title'));

		$arAdditionalData['SCRIPTS'] = array();
		$arHeadScriptsNew = array_unique($arHeadScriptsNew);

		foreach($arHeadScriptsNew as $script)
		{
			$arAdditionalData['SCRIPTS'][] = CUtil::GetAdditionalFileURL($script);
		}

		if (null !== $this->__nav_params)
		{
			$arAdditionalData['NAV_CHAIN'] = $APPLICATION->GetNavChain($this->__nav_params[0], $this->__nav_params[1], $this->__nav_params[2], $this->__nav_params[3], $this->__nav_params[4]);
		}

		if ($this->bStyle)
		{
			$arAdditionalData["CSS"] = array();
			/** @noinspection PhpUndefinedVariableInspection */
			$arCSSNew = array_unique($arCSSNew);
			foreach($arCSSNew as $style)
			{
				$arAdditionalData['CSS'][] = CUtil::GetAdditionalFileURL($style);
			}
		}

		$additional_data = '<script type="text/javascript" bxrunfirst="true">'."\n";
		$additional_data .= 'var arAjaxPageData = '.CUtil::PhpToJSObject($arAdditionalData).";\r\n";
		$additional_data .= 'top.BX.ajax.UpdatePageData(arAjaxPageData)'.";\r\n";

		$additional_data .= '</script><script type="text/javascript">';

		if (!$this->bIFrameMode && $this->bHistory)
		{
			$additional_data .= 'top.BX.ajax.history.put(window.AJAX_PAGE_STATE.getState(), \''.CUtil::JSEscape(CAjax::encodeURI($APPLICATION->GetCurPageParam('', array(BX_AJAX_PARAM_ID), false))).'\')'.";\r\n";
		}

		if ($this->bJump)
		{
			if ($this->bIFrameMode)
				$additional_data .= 'top.setTimeout(\'BX.scrollToNode("comp_'.$this->componentID.'")\', 100)'.";\r\n";
			else
				$additional_data .= 'top.BX.scrollToNode(\'comp_'.$this->componentID.'\')'.";\r\n";
		}

		$additional_data .= '</script>';

		echo $additional_data;
	}

	function _PrepareData()
	{
		global $APPLICATION;

		if ($this->bWrongRedirect)
			return null;

		$arBuffer = array_slice($APPLICATION->buffer_content, $this->buffer_start_counter, $this->buffer_finish_counter - $this->buffer_start_counter);

		$delimiter = '###AJAX_'.$APPLICATION->GetServerUniqID().'###';

		$data = implode($delimiter, $arBuffer);

		$this->__PrepareLinks($data);
		$this->__PrepareForms($data);

		if (!$this->bAjaxSession)
		{
			$data = '<div id="comp_'.$this->componentID.'">'.$data.'</div>';

			if ($this->bHistory)
			{
				$data =
					'<script type="text/javascript">if (window.location.hash != \'\' && window.location.hash != \'#\') top.BX.ajax.history.checkRedirectStart(\''.CUtil::JSEscape(BX_AJAX_PARAM_ID).'\', \''.CUtil::JSEscape($this->componentID).'\')</script>'
					.$data
					.'<script type="text/javascript">if (top.BX.ajax.history.bHashCollision) top.BX.ajax.history.checkRedirectFinish(\''.CUtil::JSEscape(BX_AJAX_PARAM_ID).'\', \''.CUtil::JSEscape($this->componentID).'\');</script>'
					.'<script type="text/javascript">top.BX.ready(BX.defer(function() {window.AJAX_PAGE_STATE = new top.BX.ajax.component(\'comp_'.$this->componentID.'\'); top.BX.ajax.history.init(window.AJAX_PAGE_STATE);}))</script>';
			}
		}
		else
		{
			if ($this->bIFrameMode)
			{
				$this->__PrepareScripts($data);

				// fix IE bug;
				$data = '<html><head></head><body>'.$data.'</body></html>';
			}
		}

		$arBuffer = explode($delimiter, $data);
		for ($i = 0, $cnt = count($arBuffer); $i < $cnt; $i++)
		{
			$APPLICATION->buffer_content[$this->buffer_start_counter + $i] = $arBuffer[$i];
		}

		return '';
	}

	function Process()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if ($this->componentID == '')
			return;

		$this->buffer_finish_counter = count($APPLICATION->buffer_content)+1;

		$APPLICATION->AddBufferContent(array($this, '_PrepareData'));

		$this->__removeHandlers();

		if ($this->bAjaxSession)
		{
			AddEventHandler('main', 'onAfterAjaxResponse', array($this, '_PrepareAdditionalData'));

			$APPLICATION->AddBufferContent(array('CComponentAjax', 'ExecuteEvents'));

			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
			exit();
		}
	}

	// will be called as delay function and not in class entity context
	function ExecuteEvents()
	{
		foreach (GetModuleEvents('main', 'onAfterAjaxResponse', true) as $arEvent)
		{
			echo ExecuteModuleEventEx($arEvent);
		}
	}
}
