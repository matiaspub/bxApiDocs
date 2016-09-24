<?php

IncludeModuleLangFile(__FILE__);
use Bitrix\Security\Filter;

class CSecurityFilter
{
	const DEFAULT_REQUEST_ORDER = "GP";
	private $doBlock = false;
	/** @var \Bitrix\Security\Filter\Request $requestFilter */
	private $requestFilter = null;
	/** @var \Bitrix\Security\Filter\Server $serverFilter */
	private $serverFilter = null;
	/** @var \Bitrix\Main\Context $context */
	private $context = null;
	private $splittingChar = '';
	protected $defaultAuditors = array(
		array('type' => 'XSS', 'class' => 'Bitrix\Security\Filter\Auditor\Xss'),
		array('type' => 'SQL', 'class' => 'Bitrix\Security\Filter\Auditor\Sql'),
		array('type' => 'PHP', 'class' => 'Bitrix\Security\Filter\Auditor\Path')
	);

	public function __construct($customOptions = array(), $char = "")
	{
		if(isset($customOptions["stop"]))
		{
			$this->doBlock = $customOptions["stop"];
		}
		else
		{
			$this->doBlock = (\COption::getOptionString("security", "filter_stop") === 'Y');
		}

		$this->requestFilter = new Filter\Request($customOptions);
		$this->serverFilter = new Filter\Server($customOptions);
		$this->context = \Bitrix\Main\Application::getInstance()->getContext();
		$this->splittingChar = $char;
	}

	public static function OnBeforeProlog()
	{
		if (CSecuritySystemInformation::isCliMode())
			return;

		if(CSecurityFilterMask::Check(SITE_ID, $_SERVER["REQUEST_URI"]))
			return;

		if(
			check_bitrix_sessid()
			&& self::currentUserHaveRightsForSkip()
			&& (
				!isset($_POST['____SECFILTER_CONVERT_JS'])
				|| !$_POST['____SECFILTER_CONVERT_JS']
			)
		)
		{
			return;
		}

		$filter = new CSecurityFilter;
		$filter->process();
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
				registerModuleDependences("main", "OnBeforeProlog", "security", "CSecurityFilter", "OnBeforeProlog", "5");
				registerModuleDependences("main", "OnEndBufferContent", "security", "CSecurityXSSDetect", "OnEndBufferContent", 9999);
			}
		}
		else
		{
			if(CSecurityFilter::IsActive())
			{
				unregisterModuleDependences("main", "OnBeforeProlog", "security", "CSecurityFilter", "OnBeforeProlog");
				unregisterModuleDependences("main", "OnEndBufferContent", "security", "CSecurityXSSDetect", "OnEndBufferContent");
			}
		}
	}


	/**
	 * @return array
	 */
	public static function GetAuditTypes()
	{
		return array(
			"SECURITY_FILTER_SQL" => "[SECURITY_FILTER_SQL] ".getMessage("SECURITY_FILTER_SQL"),
			"SECURITY_FILTER_XSS" => "[SECURITY_FILTER_XSS] ".getMessage("SECURITY_FILTER_XSS"),
			"SECURITY_FILTER_XSS2" => "[SECURITY_FILTER_XSS2] ".getMessage("SECURITY_FILTER_XSS"),
			"SECURITY_FILTER_PHP" => "[SECURITY_FILTER_PHP] ".getMessage("SECURITY_FILTER_PHP"),
			"SECURITY_REDIRECT" => "[SECURITY_REDIRECT] ".getMessage("SECURITY_REDIRECT"),
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
			"TITLE" => getMessage("SECURITY_FILTER_INFORM_TITLE"),
			"COLOR" => "blue",
			"FOOTER" => '<a href="'.$setupLink.'">'.getMessage("SECURITY_FILTER_INFORM_LINK_TO_SETUP_ON").'</a>'
		);

		try
		{
			if (self::IsActive())
			{

				$days = COption::getOptionInt("main", "event_log_cleanup_days", 7);
				if($days > 7)
					$days = 7;
				$timestampX = ConvertTimeStamp(time()-$days*24*3600+CTimeZone::getOffset());
				$eventLink = '/bitrix/admin/event_log.php?set_filter=Y&find_type=audit_type_id&find_audit_type[]=SECURITY_FILTER_SQL&find_audit_type[]=SECURITY_FILTER_XSS&find_audit_type[]=SECURITY_FILTER_XSS2&find_audit_type[]=SECURITY_FILTER_PHP&mod=security&find_timestamp_x_1='.$timestampX.'&lang='.LANGUAGE_ID;

				$eventCount = self::getEventsCount($timestampX);
				if($eventCount > 999)
					$eventCount = round($eventCount/1000,1).'K';

				if($eventCount > 0)
					$descriptionText = getMessage("SECURITY_FILTER_INFORM_EVENT_COUNT").'<a href="'.$eventLink.'">'.$eventCount.'</a>';
				else
					$descriptionText = getMessage("SECURITY_FILTER_INFORM_EVENT_COUNT_EMPTY");

				$WAFAIParams["FOOTER"] = '<a href="'.$setupLink.'">'.getMessage("SECURITY_FILTER_INFORM_LINK_TO_SETUP").'</a>';
				$WAFAIParams["ALERT"] = false;

				$WAFAIParams["HTML"] = '
<div class="adm-informer-item-section">
	<span class="adm-informer-item-l">
		<span class="adm-informer-strong-text">'.getMessage("SECURITY_FILTER_INFORM_FILTER_ON").'</span>
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
			<span class="adm-informer-strong-text">'.getMessage("SECURITY_FILTER_INFORM_FILTER_OFF").'</span>
			<span>'.getMessage("SECURITY_FILTER_INFORM_FILTER_ON_RECOMMENDATION", array("#LINK#" => $setupLink)).'</span>
		</span>
</div>
';
			}
		}
		catch (Exception $e)
		{
			$WAFAIParams["TITLE"] .= " - ".getMessage("top_panel_ai_title_err");
			$WAFAIParams["ALERT"] = true;
			$WAFAIParams["HTML"] = $e->getMessage();
		}

		CAdminInformer::AddItem($WAFAIParams);
		return true;
	}


	/**
	 * @deprecated deprecated agent since version 12.0.8
	 * @return string
	 */
	public static function ClearTmpFiles()
	{
		return "";
	}

	/**
	 * ATTENTION! Do "NOTHING" since 14.0.0
	 * @deprecated deprecated since version 12.0.8
	 * @param string $pValue
	 * @param string $pAction
	 * @return string
	 */
	static public function testXSS($pValue, $pAction = "clear")
	{
		return $pValue;
	}

	protected function process()
	{
		$auditors = $this->getAuditorInstances();
		$this->requestFilter->setAuditors($auditors);
		$this->serverFilter->setAuditors($auditors);
		$this->getHttpRequest()->addFilter($this->requestFilter);
		$this->context->getServer()->addFilter($this->serverFilter);

		if ($this->isAuditorsTriggered())
		{
			if ($this->isSomethingChanged())
			{
				$this->overrideSuperGlobals();

				if ($this->currentUserHaveRightsForSkip())
				{
					$this->showForm();
				}
			}

			$this->doPostProcessActions();
		}
	}

	protected function getAuditors()
	{
		$wafConfig = \Bitrix\Main\Config\Configuration::getValue("waf");
		if (is_array($wafConfig) && isset($wafConfig['auditors']))
			return $wafConfig['auditors'];

		return $this->defaultAuditors;
	}

	protected function getAuditorInstances()
	{
		$auditors = $this->getAuditors();
		$result = array();
		foreach($auditors as $auditor)
		{
			if (isset($auditor['file']))
			{
				include_once $auditor['file'];
			}

			$class = $auditor['class'];
			$result[$auditor['type']] = new $class($this->splittingChar);
		}
		return $result;
	}

	protected function overrideSuperGlobals()
	{
		global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_REQUEST_VARS, $HTTP_SERVER_VARS;

		self::cleanGlobals();

		$httpRequest = $this->getHttpRequest();
		$_GET = $httpRequest->getQueryList()->toArray();
		$_POST = $httpRequest->getPostList()->toArray();
		$_COOKIE = $httpRequest->getCookieRawList()->toArray();
		$_SERVER = $this->context->getServer()->toArray();

		self::reconstructRequest();
		self::restoreGlobals();

		$HTTP_GET_VARS = $_GET;
		$HTTP_POST_VARS = $_POST;
		$HTTP_COOKIE_VARS = $_COOKIE;
		$HTTP_REQUEST_VARS = $_REQUEST;
		$HTTP_SERVER_VARS = $_SERVER;
	}

	/**
	 * @since 14.0.3
	 * @return bool
	 */
	protected function isAuditorsTriggered()
	{
		return (
			$this->requestFilter->isAuditorsTriggered()
			|| $this->serverFilter->isAuditorsTriggered()
		);
	}

	/**
	 * @return bool
	 */
	protected function isSomethingChanged()
	{
		return (
			count($this->requestFilter->getChangedVars()) > 0
			|| count($this->serverFilter->getChangedVars()) > 0
		);
	}

	/**
	 * @return array
	 */
	protected function getChangedVars()
	{
		return $this->requestFilter->getChangedVars() + $this->serverFilter->getChangedVars();
	}

	/**
	 * @return \Bitrix\Main\HttpRequest
	 */
	protected function getHttpRequest()
	{
		return $this->context->getRequest();
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
	protected static function currentUserHaveRightsForSkip()
	{
		/** @global CUser $USER */
		global $USER;
		if(is_object($USER))
			return $USER->CanDoOperation('security_filter_bypass');
		else
			return false;
	}


	/**
	 * @param string $ip
	 * @return bool
	 */
	protected function blockCurrentUser($ip = "")
	{
		if(self::currentUserHaveRightsForSkip())
			return false;

		if(!is_string($ip) || $ip === "")
			$ip = $_SERVER["REMOTE_ADDR"];

		$rule = new CSecurityIPRule;

		CTimeZone::Disable();
		$added = $rule->Add(array(
			"RULE_TYPE" => "A",
			"ACTIVE" => "Y",
			"ADMIN_SECTION" => "Y",
			"NAME" => getMessage("SECURITY_FILTER_IP_RULE", array("#IP#" => $ip)),
			"ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
			"ACTIVE_TO" => ConvertTimeStamp(time()+COption::getOptionInt("security", "filter_duration")*60, "FULL"),
			"INCL_IPS" => array($ip),
			"INCL_MASKS" => array("/*"),
		));
		CTimeZone::Enable();

		return ($added > 0);
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
			"_UNSECURE" => 1
		);

		return $safetyVars;
	}

	protected static function cleanGlobals()
	{
		$safetyGlobals = self::getSafetyGlobals();
		foreach($_REQUEST as $key => $value)
		{
			if(!isset($safetyGlobals[$key]) && $value === $GLOBALS[$key])
			{
				unset($GLOBALS[$key]);
			}
		}
	}

	/**
	 * @param string $type
	 * @return array
	 */
	protected static function getSuperGlobalArray($type)
	{
		switch($type)
		{
			case "g":
			case "G":
				return $_GET;
			break;
			case "p":
			case "P":
				return $_POST;
			break;
			case "c":
			case "C":
				return $_COOKIE;
			break;
			case "s":
			case "S":
				return $_SERVER;
			break;
			case "e":
			case "E":
				return $_ENV;
			break;
			default:
				return array();
			break;
		}
	}

	protected static function getRequestOrder()
	{
		$result = ini_get("request_order");

		if (!$result)
			$result = ini_get("variables_order");

		if (!$result)
			$result = self::DEFAULT_REQUEST_ORDER;

		return $result;
	}

	protected static function reconstructRequest()
	{
		$systemOrder = static::getRequestOrder();

		$_REQUEST = self::getSuperGlobalArray($systemOrder[0]);
		for($i = 1, $count = strlen($systemOrder); $i < $count; $i ++)
		{
			$targetArray = self::getSuperGlobalArray($systemOrder[$i]);
			foreach($targetArray as $k => $v)
			{
				$_REQUEST[$k] = $v;
			}
		}
	}

	protected static function restoreGlobals()
	{
		$safetyGlobals = self::getSafetyGlobals();
		foreach($_REQUEST as $key => $value)
		{
			if(!isset($safetyGlobals[$key])
				&& !(
					isset($GLOBALS[$key])
					|| array_key_exists($key, $GLOBALS)
				)
			)
			{
				$GLOBALS[$key] = $value;
			}
		}
	}

	protected function doPostProcessActions()
	{
		if (
			$this->isBlockNeeded()
			&& $this->blockCurrentUser()
			&& CSecurityIPRule::IsActive()
		)
		{
			CSecurityIPRule::OnPageStart(true);
		}
	}

	protected function showForm()
	{
		if(!isset($_POST['____SECFILTER_CONVERT_JS']) || empty($_POST['____SECFILTER_CONVERT_JS']))
		{
			if(
				//intranet tasks folder created
				(
					isset($_GET["bx_task_action_request"])
					&& $_GET["bx_task_action_request"] === "Y"
					&& isset($_GET["action"])
					&& $_GET["action"] === "folder_edit"
				)
				//or create ticket with wizard
				|| (
					isset($_GET['show_wizard'])
					&& $_GET['show_wizard'] === "Y"
					&& isset($_POST['AJAX_CALL'])
					&& $_POST['AJAX_CALL'] === "Y"
				)
				//or by bitrix:search.title
				|| (
					isset($_POST['q'])
					&& !empty($_POST['q'])
					&& isset($_POST['ajax_call'])
					&& $_POST['ajax_call'] === "y"
				)
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
				$originalPostVars = $this->getHttpRequest()->getPostList()->toArrayRaw();
				if (!$originalPostVars)
					$originalPostVars = array();

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
		echo "[WAF] ".getMessage("SECURITY_FILTER_FORM_SUB_TITLE")." ".getMessage("SECURITY_FILTER_FORM_TITLE").".";
	}

	/**
	 *
	 */
	protected function showAjaxForm()
	{
		echo '<script>top.BX.closeWait(); top.BX.WindowManager.Get().ShowError(\''.getMessageJS("SECURITY_FILTER_FORM_SUB_TITLE")." ".getMessageJS("SECURITY_FILTER_FORM_TITLE").".".'\')</script>';
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
		<meta name="robots" content="none" />
		<title><?echo getMessage("SECURITY_FILTER_FORM_TITLE")?></title>
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
								<td><?echo getMessage("SECURITY_FILTER_FORM_SUB_TITLE")?></td>
							</tr>
						</table>
					</div>
					<div class="description">
						<?echo getMessage("SECURITY_FILTER_FORM_MESSAGE")?><br /><br />
						<table cellpadding="0" cellspacing="0" witdh="100%">
							<tr>
								<td class="head" align="center"><?echo getMessage("SECURITY_FILTER_FORM_VARNAME")?></td>
								<td class="head" align="center"><?echo getMessage("SECURITY_FILTER_FORM_VARDATA")?></td>
							</tr>
							<?foreach($this->getChangedVars() as $var_name => $str):?>
							<tr valign="top">
								<td><?echo htmlspecialcharsbx($var_name)?></td>
								<td><?echo htmlspecialcharsbx($str)?></td>
							</tr>
							<?endforeach?>
						</table><br />
						<form method="POST" <?if(defined('POST_FORM_ACTION_URI')):?> action="<?echo POST_FORM_ACTION_URI?>" <?endif?>>
							<?echo self::formatHiddenFields($originalPostVars);?>
							<?echo bitrix_sessid_post();?>
							<input type="submit" name='____SECFILTER_ACCEPT_JS' value="<?echo getMessage('SECURITY_FILTER_FORM_ACCEPT')?>" />
							<input type="submit" name='____SECFILTER_CONVERT_JS' value="<?echo getMessage('SECURITY_FILTER_FORM_CONVERT')?>" />
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
	 * @param array $array
	 * @param string $prefix
	 * @return string
	 */
	protected static function formatHiddenFields(array $array, $prefix = null)
	{
		$result = "";
		foreach($array as $key => $value)
		{
			if($prefix !== null)
				$key = $prefix."[".$key."]";

			if(is_array($value))
			{
				$result .= self::formatHiddenFields($value, $key);
			}
			else
			{
				$result .= "<input type=hidden name=\"".htmlspecialcharsbx($key)."\" value=\"".htmlspecialcharsbx($value)."\">\r\n";
			}
		}

		return $result;
	}

}

