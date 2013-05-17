<?php
IncludeModuleLangFile(__FILE__);

class CSecurityFilter
{
	private $action = "filter";
	private $doBlock = false;
	private $doLog = false;
	private $foundVars = array();
	private $isUserBlocked = false;
	/** @var CSecurityFilterBaseAuditor[] */
	private $auditors = array();

	private static $validActions = array("none", "clear", "filter");

	function __construct($pCustomOptions = array(), $pChar = "")
	{
		if(isset($pCustomOptions["action"]))
		{
			$this->setAction($pCustomOptions["action"]);
		}
		else
		{
			$this->setAction(COption::GetOptionString("security", "filter_action"));
		}

		if(isset($pCustomOptions["stop"]))
		{
			$this->setStop($pCustomOptions["stop"]);
		}
		else
		{
			$this->setStop(COption::GetOptionString("security", "filter_stop"));
		}

		if(isset($pCustomOptions["log"]))
		{
			$this->setLog($pCustomOptions["log"]);
		}
		else
		{
			$this->setLog(COption::GetOptionString("security", "filter_log"));
		}

		$this->auditors = array();
		$this->auditors["XSS"] = new CSecurityFilterXssAuditor($pChar);
		$this->auditors["SQL"] = new CSecurityFilterSqlAuditor($pChar);
		$this->auditors["PHP"] = new CSecurityFilterPathAuditor($pChar);
	}

	/**
	 *
	 */
	public static function OnBeforeProlog()
	{
		if(CSecurityFilterMask::Check(SITE_ID, $_SERVER["REQUEST_URI"]))
			return;

		$filter = new CSecurityFilter;
		$filter->process();
	}

	/**
	 * Main filtering loop also sets up global vars GET POST COOKIE and some $_SERVER keys
	 */
	static public function process()
	{
		global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_REQUEST_VARS;

		if($this->currentUserHaveRightsForSkip())
		{
			if(
				$_SERVER["REQUEST_METHOD"] === "POST"
				&& check_bitrix_sessid()
				&& empty($_POST['____SECFILTER_CONVERT_JS'])
			)
			{
				return;
			}
		}

		//Do not touch those variables who did not come from REQUEST
		$this->cleanGlobals();
		
		$originalPostVars = $_POST;

		$_GET = $this->safeizeArray($_GET, '$_GET');
		$_POST = $this->safeizeArray($_POST, '$_POST', '/^File\d+_\d+$/');
		$_COOKIE = $this->safeizeArray($_COOKIE, '$_COOKIE');
		$_SERVER = $this->safeizeServerArray($_SERVER);

		$_REQUEST = $_GET;
		foreach($_POST as $k => $v)
			$_REQUEST[$k] = $v;
		foreach($_COOKIE as $k => $v)
			$_REQUEST[$k] = $v;

		$HTTP_GET_VARS = $_GET;
		$HTTP_POST_VARS = $_POST;
		$HTTP_COOKIE_VARS = $_COOKIE;
		$HTTP_REQUEST_VARS = $_REQUEST;

		
		$this->restoreGlobals();

		$this->doPostProccessActions($originalPostVars);
	}


	/**
	 * @return bool
	 */
	public static function IsActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnBeforeProlog", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityFilter"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}


	/**
	 * @param bool $bActive
	 */
	public static function SetActive($bActive = false)
	{
		if($bActive)
		{
			if(!CSecurityFilter::IsActive())
			{
				RegisterModuleDependences("main", "OnBeforeProlog", "security", "CSecurityFilter", "OnBeforeProlog", "5");
				RegisterModuleDependences("main", "OnEndBufferContent", "security", "CSecurityXSSDetect", "OnEndBufferContent", 9999);
				// CAgent::AddAgent("CSecurityFilter::ClearTmpFiles();", "security", "N");
			}
		}
		else
		{
			if(CSecurityFilter::IsActive())
			{
				UnRegisterModuleDependences("main", "OnBeforeProlog", "security", "CSecurityFilter", "OnBeforeProlog");
				UnRegisterModuleDependences("main", "OnEndBufferContent", "security", "CSecurityXSSDetect", "OnEndBufferContent");
				// CAgent::RemoveAgent("CSecurityFilter::ClearTmpFiles();", "security");
			}
		}
	}


	/**
	 * @return array
	 */
	public static function GetAuditTypes()
	{
		return array(
			"SECURITY_FILTER_SQL" => "[SECURITY_FILTER_SQL] ".GetMessage("SECURITY_FILTER_SQL"),
			"SECURITY_FILTER_XSS" => "[SECURITY_FILTER_XSS] ".GetMessage("SECURITY_FILTER_XSS"),
			"SECURITY_FILTER_XSS2" => "[SECURITY_FILTER_XSS] ".GetMessage("SECURITY_FILTER_XSS"),
			"SECURITY_FILTER_PHP" => "[SECURITY_FILTER_PHP] ".GetMessage("SECURITY_FILTER_PHP"),
			"SECURITY_REDIRECT" => "[SECURITY_REDIRECT] ".GetMessage("SECURITY_REDIRECT"),
		);
	}

	/**
	 * Return WAF events count for Admin's informer popup and Admin's gadget
	 * @param string $timestampX  - from date
	 * @return integer
	 */
	public static function GetEventsCount($timestampX = '')
	{
		return CSecurityEvent::getInstance()->getEventsCount($timestampX);
	}

	/**
	 * Shows information about WAF stats in Admin's informer popup
	 * @return bool|void
	 */
	public static function OnAdminInformerInsertItems()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		if ($APPLICATION->GetGroupRight("security") < "W")
			return false;

		$setupLink = '/bitrix/admin/security_filter.php?lang='.LANGUAGE_ID;
		$WAFAIParams = array(
			"TITLE" => GetMessage("SECURITY_FILTER_INFORM_TITLE"),
			"COLOR" => "blue",
			"FOOTER" => '<a href="'.$setupLink.'">'.GetMessage("SECURITY_FILTER_INFORM_LINK_TO_SETUP_ON").'</a>'
		);

		try
		{
			if (self::IsActive())
			{

				$days = COption::GetOptionInt("main", "event_log_cleanup_days", 7);
				if($days > 7)
					$days = 7;
				$timestampX = ConvertTimeStamp(time()-$days*24*3600+CTimeZone::GetOffset());
				$eventLink = '/bitrix/admin/event_log.php?set_filter=Y&find_type=audit_type_id&find_audit_type[]=SECURITY_FILTER_SQL&find_audit_type[]=SECURITY_FILTER_XSS&find_audit_type[]=SECURITY_FILTER_XSS2&find_audit_type[]=SECURITY_FILTER_PHP&mod=security&find_timestamp_x_1='.$timestampX.'&lang='.LANGUAGE_ID;

				$eventCount = self::GetEventsCount($timestampX);
				if($eventCount > 999)
					$eventCount = round($eventCount/1000,1).'K';

				if($eventCount > 0)
					$descriptionText = GetMessage("SECURITY_FILTER_INFORM_EVENT_COUNT").'<a href="'.$eventLink.'">'.$eventCount.'</a>';
				else
					$descriptionText = GetMessage("SECURITY_FILTER_INFORM_EVENT_COUNT_EMPTY");

				$WAFAIParams["FOOTER"] = '<a href="'.$setupLink.'">'.GetMessage("SECURITY_FILTER_INFORM_LINK_TO_SETUP").'</a>';
				$WAFAIParams["ALERT"] = false;

				$WAFAIParams["HTML"] = '
<div class="adm-informer-item-section">
	<span class="adm-informer-item-l">
		<span class="adm-informer-strong-text">'.GetMessage("SECURITY_FILTER_INFORM_FILTER_ON").'</span>
		<span>'.$descriptionText.'</span>
	</span>
</div>
';
			}
			else
			{
				$WAFAIParams["ALERT"] = true;

				$WAFAIParams["HTML"] = '
<div class="adm-informer-item-section">
		<span class="adm-informer-item-l">
			<span class="adm-informer-strong-text">'.GetMessage("SECURITY_FILTER_INFORM_FILTER_OFF").'</span>
			<span>'.GetMessage("SECURITY_FILTER_INFORM_FILTER_ON_RECOMMENDATION", array("#LINK#" => $setupLink)).'</span>
		</span>
</div>
';
			}
		}
		catch (Exception $e)
		{
			$WAFAIParams["TITLE"] .= " - ".GetMessage("top_panel_ai_title_err");
			$WAFAIParams["ALERT"] = true;
			$WAFAIParams["HTML"] = $e->getMessage();
		}

		CAdminInformer::AddItem($WAFAIParams);
		return true;
	}


	/**
	 * @return string
	 */
	public static function ClearTmpFiles()
	{
		return "";
	}

	/**
	 * @param $pAction
	 * @return bool
	 */
	public static function isActionValid($pAction)
	{
		return in_array($pAction, self::getValidActions());
	}

	/**
	 * Returns the filtered value after checking CSecurityFilterXssAuditor
	 * @deprecated deprecated since version 12.0.8
	 * @param string $pValue
	 * @param string $pAction
	 * @return string
	 */
	static public function testXSS($pValue, $pAction = "clear")
	{
		$this->setStop("N");
		$this->setLog("N");
		if($pAction == "replace")
			$this->setAction("filter");
		else
			$this->setAction("clear");
		unset($this->auditors["SQL"]);
		unset($this->auditors["PHP"]);

		return $this->safeizeVar($pValue, 'fakeVar');
	}

	/**
	 * @return array
	 */
	protected static function getValidActions()
	{
		return self::$validActions;
	}


	/**
	 * @param $pAction
	 */
	protected function setAction($pAction)
	{
		if(self::isActionValid($pAction))
		{
			$this->action = $pAction;
		}
	}


	/**
	 * @param $pStop
	 */
	protected function setStop($pStop)
	{
		if(is_string($pStop) && $pStop == "Y")
		{
			$this->doBlock = true;
		}
		else
		{
			$this->doBlock = false;
		}
	}


	/**
	 * @param $pLog
	 */
	protected function setLog($pLog)
	{
		if(is_string($pLog) && $pLog == "Y")
		{
			$this->doLog = true;
		}
		else
		{
			$this->doLog = false;
		}
	}


	/**
	 * @return bool
	 */
	protected function isFilterAction()
	{
		return ($this->action === "filter");
	}


	/**
	 * @return bool
	 */
	protected function isClearAction()
	{
		return ($this->action === "clear");
	}


	/**
	 * @return bool
	 */
	protected function isNeedShowForm()
	{
		return (count($this->getFoundVars()) > 0 && $this->action !== "none");
	}


	/**
	 * @return bool
	 */
	protected function isBlockNeeded()
	{
		return $this->doBlock;
	}


	/**
	 * @return bool
	 */
	protected function isLogNeeded()
	{
		return $this->doLog;
	}


	/**
	 * @param $pString
	 * @return bool
	 */
	protected static function AdjustPcreBacktrackLimit($pString)
	{
		if(!is_string($pString))
			return false;

		$strlen = CUtil::BinStrlen($pString) * 2;
		CUtil::AdjustPcreBacktrackLimit($strlen);
		return true;
	}


	/**
	 * @param $pValue
	 * @param $pName
	 */
	protected function pushFoundVar($pValue, $pName)
	{
		if(!is_array($this->foundVars))
			$this->foundVars = array();

		$this->foundVars[$pName] = $pValue;
	}


	/**
	 * @return array
	 */
	protected function getFoundVars()
	{
		return $this->foundVars;
	}


	/**
	 * @param $pValue
	 * @param $pName
	 * @return string
	 */
	protected function safeizeVar($pValue, $pName)
	{
		if(preg_match("/^[A-Za-z0-9_.,-]*$/", $pValue))
			return $pValue;

		self::AdjustPcreBacktrackLimit($pValue);
		$checkedValue = CSecurityHtmlEntity::decodeString($pValue);

		$bFound = false;
		foreach($this->auditors as $auditName => $auditor)
		{
			if($auditor->process($checkedValue))
			{
				$bFound = true;
				$this->pushFoundVar($pValue, $pName);

				if($this->isBlockNeeded())
				{
					$this->blockCurrentUser();
				}
			
				if($this->isLogNeeded())
				{
					$this->logVariable($pValue, $pName, $auditName);
				}

				if($this->isFilterAction())
				{
					$checkedValue = $auditor->getValidString();
				}
				elseif($this->isClearAction())
				{
					$checkedValue = "";
					break;
				}

			}
		}
		if($bFound)
			return $checkedValue;
		else
			return $pValue;
	}


	/**
	 * @param $pArray
	 * @return array
	 */
	protected function safeizeServerArray($pArray)
	{
		if(!is_array($pArray))
			return $pArray;

		$array = $pArray;

		foreach($array as $key => $value)
		{
			if(strpos($key, "HTTP_")===0)
			{
				$array[$key] = $this->safeizeVar($array[$key], '$_SERVER["'.$key.'"]');
			}

		}
		$array["QUERY_STRING"] = $this->safeizeVar($array["QUERY_STRING"], '$_SERVER["QUERY_STRING"]');
		$array["REQUEST_URI"] = $this->safeizeVar($array["REQUEST_URI"], '$_SERVER["REQUEST_URI"]');
		$array["SCRIPT_URL"] = $this->safeizeVar($array["SCRIPT_URL"], '$_SERVER["SCRIPT_URL"]');
		$array["SCRIPT_URI"] = $this->safeizeVar($array["SCRIPT_URI"], '$_SERVER["SCRIPT_URI"]');
		return $array;
	}


	/**
	 * @param array $pArray
	 * @param string $pName
	 * @param string $pSkipKeyPreg
	 * @return array
	 */
	protected function safeizeArray($pArray, $pName, $pSkipKeyPreg = '')
	{
		if(!is_array($pArray))
			return $pArray;

		$array = $pArray;
		
		foreach($array as $key => $value)
		{
			if($pSkipKeyPreg && preg_match($pSkipKeyPreg, $key))
				continue;

			$filteredKey =  $this->safeizeVar($key, $pName."['".$key."']");
			if($filteredKey != $key)
			{
				unset($array[$key]);
				$key = $filteredKey;
			}

			if(is_array($value))
			{
				$array[$key] = $this->safeizeArray($value, $pName."['".$key."']", $pSkipKeyPreg);
			}
			else
			{
				$array[$key] = $this->safeizeVar($value, $pName."['".$key."']");
			}
		}
		return $array;
	}


	/**
	 * @return bool
	 */
	protected function currentUserHaveRightsForSkip()
	{
		/** @global CUser $USER */
		global $USER;
		if(is_object($USER))
			return $USER->CanDoOperation('security_filter_bypass');
		else
			return false;
	}


	/**
	 * @param string $pIP
	 */
	protected function blockCurrentUser($pIP = "")
	{
		static $blocked = array();

		if($this->currentUserHaveRightsForSkip())
			return;

		if(is_string($pIP) && $pIP != "")
		{
			$ip = $pIP;
		}
		else
		{
			$ip = $_SERVER["REMOTE_ADDR"];
		}

		if(!array_key_exists($ip, $blocked))
		{
			$rule = new CSecurityIPRule;

			CTimeZone::Disable();
			$rule->Add(array(
				"RULE_TYPE" => "A",
				"ACTIVE" => "Y",
				"ADMIN_SECTION" => "Y",
				"NAME" => GetMessage("SECURITY_FILTER_IP_RULE", array("#IP#" => $ip)),
				"ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
				"ACTIVE_TO" => ConvertTimeStamp(time()+COption::GetOptionInt("security", "filter_duration")*60, "FULL"),
				"INCL_IPS" => array($ip),
				"INCL_MASKS" => array("*"),
			));
			CTimeZone::Enable();

			$blocked[$ip] = true;
			$this->isUserBlocked = true;
		}
	}


	/**
	 * @param string $pValue
	 * @param string $pName
	 * @param string $pAuditorName
	 * @return bool
	 */
	protected static function logVariable($pValue, $pName, $pAuditorName)
	{
		return CSecurityEvent::getInstance()->doLog("SECURITY", "SECURITY_FILTER_".$pAuditorName, $pName, "==".base64_encode($pValue));
	}


	/**
	 * @return array
	 */
	protected static function getSafetyGlobals()
	{
		static $safetyVars = array(
			"_GET" => 1,
			"_POST" => 1,
			"_SERVER" => 1,
			"_ENV" => 1,
			"_COOKIE" => 1,
			"_FILES" => 1,
			"_REQUEST" => 1,
			"_SESSION" => 1,
			"GLOBALS" => 1,
			"HTTP_GET_VARS" => 1,
			"HTTP_POST_VARS" => 1,
			"HTTP_SERVER_VARS" => 1,
			"HTTP_ENV_VARS" => 1,
			"HTTP_COOKIE_VARS" => 1,
			"HTTP_FILES_VARS" => 1,
			"HTTP_REQUEST_VARS" => 1,
			"HTTP_SESSION_VARS" => 1,
			"php_errormsg" => 1,
			"HTTP_RAW_POST_DATA" => 1,
			"http_response_header" => 1,
			"argc" => 1,
			"argv" => 1,
			"DOCUMENT_ROOT" => 1,
			"__SECFILTER_FILES" => 1,
		);

		return $safetyVars;
	}

	/**
	 *
	 */
	protected function cleanGlobals()
	{
		foreach($_REQUEST as $key => $value)
		{
			if($value === $GLOBALS[$key] && !array_key_exists($key, self::getSafetyGlobals()))
			{
				unset($GLOBALS[$key]);
			}
		}
	}

	/**
	 *
	 */
	protected function restoreGlobals()
	{
		foreach($_REQUEST as $key => $value)
		{
			if(!array_key_exists($key, self::getSafetyGlobals()) && empty($GLOBALS[$key]))
			{
				$GLOBALS[$key] = $value;
			}
		}
	}


	/**
	 * @param array $originalPostVars
	 */
	protected function doPostProccessActions($originalPostVars = array())
	{
		if($this->currentUserHaveRightsForSkip() && $this->isNeedShowForm())
		{
			$this->showForm($originalPostVars);
		}
		elseif($this->isUserBlocked && CSecurityIPRule::IsActive())
		{
			CSecurityIPRule::OnPageStart(true);
		}
	}


	/**
	 * @param array $originalPostVars
	 */
	protected function showForm($originalPostVars = array())
	{
		if(empty($_POST['____SECFILTER_CONVERT_JS']))
		{
			if(
				//intranet tasks folder created
				($_GET["bx_task_action_request"] == "Y" && $_GET["action"] == "folder_edit")
				//or create ticket with wizard
				|| ($_POST['AJAX_CALL'] == "Y" && $_GET['show_wizard'] == "Y")
				//or by bitrix:search.title
				|| ($_POST['ajax_call'] == "y" && !empty($_POST['q']))
				//or by constant defined on the top of the page
				|| defined('BX_SECURITY_SHOW_MESSAGE')
			)
			{
				$this->showTextForm();
			}
			elseif(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
			{
				$this->showAjaxForm();
			}
			else
			{
				$this->showHtmlForm($originalPostVars);
			}

			die();
		}
	}

	/**
	 *
	 */
	protected function showTextForm()
	{
		echo "[WAF] ".GetMessage("SECURITY_FILTER_FORM_SUB_TITLE")." ".GetMessage("SECURITY_FILTER_FORM_TITLE").".";
	}

	/**
	 *
	 */
	protected function showAjaxForm()
	{
		echo '<script>top.BX.closeWait(); top.BX.WindowManager.Get().ShowError(\''.GetMessageJS("SECURITY_FILTER_FORM_SUB_TITLE")." ".GetMessageJS("SECURITY_FILTER_FORM_TITLE").".".'\')</script>';
	}

	/**
	 * @param array $originalPostVars
	 */
	protected function showHtmlForm($originalPostVars = array())
	{

		?>
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?echo LANG_CHARSET?>" />
		<title><?echo GetMessage("SECURITY_FILTER_FORM_TITLE")?></title>
		<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/adminstyles.css" />
		<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/404.css" />
	</head>
	<body>
	<script>if(document.location!=top.location)top.location=document.location;</script>
	<style>
		div.description td { font-family:Verdana,Arial,sans-serif; font-size:70%;  border: 1px solid #BDC6E0; padding:3px; background-color: white; }
		div.description table { border-collapse:collapse; }
		div.description td.head { background-color:#E6E9F4; }
	</style>

	<div class="error-404">
		<table class="error-404" border="0" cellpadding="0" cellspacing="0" align="center">
			<tbody><tr class="top">
				<td class="left"><div class="empty"></div></td>
				<td><div class="empty"></div></td>
				<td class="right"><div class="empty"></div></td>
			</tr>
			<tr>
				<td class="left"><div class="empty"></div></td>
				<td class="content">
					<div class="title">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td><div class="icon"></div></td>
								<td><?echo GetMessage("SECURITY_FILTER_FORM_SUB_TITLE")?></td>
							</tr>
						</table>
					</div>
					<div class="description">
						<?echo GetMessage("SECURITY_FILTER_FORM_MESSAGE")?><br /><br />
						<table cellpadding="0" cellspacing="0" witdh="100%">
							<tr>
								<td class="head" align="center"><?echo GetMessage("SECURITY_FILTER_FORM_VARNAME")?></td>
								<td class="head" align="center"><?echo GetMessage("SECURITY_FILTER_FORM_VARDATA")?></td>
							</tr>
							<?foreach($this->getFoundVars() as $var_name => $str):?>
							<tr valign="top">
								<td><?echo htmlspecialcharsbx($var_name)?></td>
								<td><?echo htmlspecialcharsbx($str)?></td>
							</tr>
							<?endforeach?>
						</table><br />
						<form method="POST" <?if(defined('POST_FORM_ACTION_URI')):?> action="<?echo POST_FORM_ACTION_URI?>" <?endif?>>
							<?echo self::formatHiddenFields($originalPostVars);?>
							<?echo bitrix_sessid_post();?>
							<input type="submit" name='____SECFILTER_ACCEPT_JS' value="<?echo GetMessage('SECURITY_FILTER_FORM_ACCEPT')?>" />
							<input type="submit" name='____SECFILTER_CONVERT_JS' value="<?echo GetMessage('SECURITY_FILTER_FORM_CONVERT')?>" />
						</form>
					</div>
				</td>
				<td class="right"><div class="empty"></div></td>
			</tr>
			<tr class="bottom">
				<td class="left"><div class="empty"></div></td>
				<td><div class="empty"></div></td>
				<td class="right"><div class="empty"></div></td>
			</tr>
			</tbody></table>
	</div>
	</body>
	</html>
	<?
	}

	/**
	 * @param array $pArray
	 * @param string $pPrefix
	 * @return string
	 */
	protected static function formatHiddenFields($pArray, $pPrefix = "")
	{
		$result = "";
		foreach($pArray as $key => $value)
		{
			if(is_array($value))
			{
				if($pPrefix != "")
				{
					$result .= self::formatHiddenFields($value, htmlspecialcharsbx($key));
				}
				else
				{
					$result .= self::formatHiddenFields($value, $pPrefix."[".htmlspecialcharsbx($key)."]");
				}
			}
			else
			{
				if($pPrefix != "")
				{
					$result .= "<input type=hidden name=\"".htmlspecialcharsbx($key)."\" value=\"".htmlspecialcharsbx($value)."\">\r\n";
				}
				else
				{
					$result .= "<input type=hidden name=\"{$pPrefix}[".htmlspecialcharsbx($key)."]\" value=\"".htmlspecialcharsbx($value)."\">\r\n";
				}
			}
		}

		return $result;
	}

}

class CSecurityFilterMask
{
	public static function Update($arMasks)
	{
		global $DB, $CACHE_MANAGER;

		if(is_array($arMasks))
		{
			$res = $DB->Query("DELETE FROM b_sec_filter_mask", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{
				$arLikeSearch = array("?", "*", ".");
				$arLikeReplace = array("_",  "%", "\\.");
				$arPregSearch = array("\\", ".",  "?", "*",   "'");
				$arPregReplace = array("/",  "\.", ".", ".*?", "\'");

				$added = array();
				$i = 10;
				foreach($arMasks as $arMask)
				{
					$site_id = trim($arMask["SITE_ID"]);
					if($site_id == "NOT_REF")
						$site_id = "";

					$mask = trim($arMask["MASK"]);
					if($mask && !array_key_exists($mask, $added))
					{
						$arMask = array(
							"SORT" => $i,
							"FILTER_MASK" => $mask,
							"LIKE_MASK" => str_replace($arLikeSearch, $arLikeReplace, $mask),
							"PREG_MASK" => str_replace($arPregSearch, $arPregReplace, $mask),
						);
						if($site_id)
							$arMask["SITE_ID"] = $site_id;

						$DB->Add("b_sec_filter_mask", $arMask);
						$i += 10;
						$added[$mask] = true;
					}
				}

				if(CACHED_b_sec_filter_mask !== false)
					$CACHE_MANAGER->CleanDir("b_sec_filter_mask");

			}
		}

		return true;
	}

	public static function GetList()
	{
		global $DB;
		$res = $DB->Query("SELECT SITE_ID,FILTER_MASK from b_sec_filter_mask ORDER BY SORT");
		return $res;
	}

	public static function Check($site_id, $uri)
	{
		global $DB, $CACHE_MANAGER;
		$bFound = false;

		if(CACHED_b_sec_filter_mask !== false)
		{
			$cache_id = "b_sec_filter_mask";
			if($CACHE_MANAGER->Read(CACHED_b_sec_filter_mask, $cache_id, "b_sec_filter_mask"))
			{
				$arMasks = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arMasks = array();

				$rs = $DB->Query("SELECT * FROM b_sec_filter_mask ORDER BY SORT");
				while($ar = $rs->Fetch())
				{
					$site_id = $ar["SITE_ID"]? $ar["SITE_ID"]: "-";
					$arMasks[$site_id][$ar["SORT"]] = $ar["PREG_MASK"];
				}

				$CACHE_MANAGER->Set($cache_id, $arMasks);
			}

			if(isset($arMasks["-"]) && is_array($arMasks["-"]))
			{
				foreach($arMasks["-"] as $mask)
				{
					if(preg_match("#^".$mask."$#", $uri))
					{
						$bFound = true;
						break;
					}
				}
			}

			if(!$bFound && array_key_exists($site_id, $arMasks))
			{
				foreach($arMasks[$site_id] as $mask)
				{
					if(preg_match("#^".$mask."$#", $uri))
					{
						$bFound = true;
						break;
					}
				}
			}

		}
		else
		{
			$rs = $DB->Query("
				SELECT m.*
				FROM
					b_sec_filter_mask m
				WHERE
					(m.SITE_ID IS NULL AND '".$DB->ForSQL($uri)."' like m.LIKE_MASK)
					OR (m.SITE_ID = '".$DB->ForSQL($site_id)."' AND '".$DB->ForSQL($uri)."' like m.LIKE_MASK)
			");
			if($rs->Fetch())
				$bFound = true;
		}

		return $bFound;
	}
}
