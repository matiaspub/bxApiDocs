<?
use Bitrix\Main\SystemException;

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecuritySiteConfigurationTest
 * @since 12.5.0
 */
class CSecuritySiteConfigurationTest
	extends CSecurityBaseTest
{
	protected $internalName = "SiteConfigurationTest";

	protected $tests = array(
		"securityLevel" => array(
			"method" => "checkSecurityLevel"
		),
		"errorReporting" => array(
			"method" => "checkErrorReporting",
			"base_message_key" => "SECURITY_SITE_CHECKER_ERROR_REPORTING",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"exceptionDebug" => array(
			"method" => "checkExceptionDebug",
			"base_message_key" => "SECURITY_SITE_CHECKER_EXCEPTION_DEBUG",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"dbDebug" => array(
			"method" => "checkDbDebug",
			"base_message_key" => "SECURITY_SITE_CHECKER_DB_DEBUG",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"dbPassword" => array(
			"method" => "checkDbPassword"
		),
		"scriptExtension" => array(
			"method" => "checkScriptExtension",
			"base_message_key" => "SECURITY_SITE_CHECKER_SCRIPT_EXTENSIONS",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"modulesVersion" => array(
			"method" => "checkModulesVersion",
			"base_message_key" => "SECURITY_SITE_CHECKER_MODULES_VERSION",
			"critical" => CSecurityCriticalLevel::HIGHT
		)
	);

	protected static $actualScriptExtensions = "php,php3,php4,php5,php6,phtml,pl,asp,aspx,cgi,dll,exe,ico,shtm,shtml,fcg,fcgi,fpl,asmx,pht,py,psp";

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Check if saved script file extension is up to date
	 */
	protected function checkScriptExtension()
	{
		$missingExtensions = array_diff(
			explode(",", self::$actualScriptExtensions),
			getScriptFileExt()
		);

<<<<<<< HEAD
		return empty($missingExtensions);
	}

=======
>>>>>>> FETCH_HEAD
	protected function checkSecurityLevel()
	{
		if(!CSecurityFilter::IsActive())
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_WAF_OFF", CSecurityCriticalLevel::HIGHT);
		}
		if(self::AdminPolicyLevel() != "high")
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_ADMIN_SECURITY_LEVEL", CSecurityCriticalLevel::HIGHT);
		}
	}

	/**
	 * Return true if debug = off
	 *
	 * @return bool
	 * @since 14.0.0
	 */
	protected function checkDbDebug()
	{
		/** @global CDataBase $DB */
		global $DB;

		return !$DB->debug;
	}

	/**
	 * Return true if error_reporting = 0
	 *
	 * @return bool
	 * @since 14.0.0
	 */
	protected function checkErrorReporting()
	{
		$validErrorReporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE;
		return !(
			COption::GetOptionInt("main", "error_reporting", $validErrorReporting) != $validErrorReporting
			&& COption::GetOptionInt("main","error_reporting","") != 0);
	}

	/**
	 * Return true if exception_handling debug = false
	 *
	 * @return bool
	 * @since 14.0.0
	 */
	protected function checkExceptionDebug()
	{
		$exceptionConfig = \Bitrix\Main\Config\Configuration::getValue('exception_handling');
		return !(
			is_array($exceptionConfig)
			&& isset($exceptionConfig['debug'])
			&& $exceptionConfig['debug']
		);
	}

	/**
	 * Return true if module updates available
	 *
	 * @return bool
	 * @since 14.0.2
	 */
	protected function checkModulesVersion()
	{
		try
		{
			return !$this->isModuleUpdateAvailable();
		}
		catch (SystemException $e)
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_MODULES_VERSION_ERROR", CSecurityCriticalLevel::HIGHT);
		}

		return true;
	}

	protected function checkDbPassword()
	{
		/** @global CDataBase $DB */
		global $DB;
		$password = $DB->DBPassword;
		$sign = ",.#!*%$:-^@{}[]()'\"-+=<>?`&;";
		$dit = "1234567890";
		if(trim($password) == "")
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_EMPTY_PASS", CSecurityCriticalLevel::HIGHT);
		}
		else
		{
			if($password == strtolower($password))
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_SAME_REGISTER_PASS", CSecurityCriticalLevel::HIGHT);
			}
			if(strpbrk($password, $sign) === false)
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_NO_SIGN_PASS", CSecurityCriticalLevel::HIGHT);
			}
			if(strpbrk($password, $dit) === false)
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_NO_DIT_PASS", CSecurityCriticalLevel::HIGHT);
			}
			if (strlen($password)<8)
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_MIN_LEN_PASS", CSecurityCriticalLevel::HIGHT);
			}
		}
	}

	/**
	 * @since 14.0.7
	 * @return bool
	 * @throws Bitrix\Main\SystemException
	 */
	protected function isModuleUpdateAvailable()
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client.php');

		$errors = null;
		$installedModules = CUpdateClient::GetCurrentModules($errors);
		if ($errors !== null)
			throw new SystemException($errors);

		$stableVersionsOnly = COption::GetOptionString('main', 'stable_versions_only', 'Y');
		$errors = null;
		$updateList = CUpdateClient::GetUpdatesList($errors, LANG, $stableVersionsOnly);
		if ($errors !== null)
			throw new SystemException($errors);

		if (
			!isset($updateList['MODULES'])
			|| !is_array($updateList['MODULES'])
			|| !isset($updateList['MODULES'][0]['#'])
		)
		{
			throw new SystemException('Empty update modules list');
		}

		if (!$updateList['MODULES'][0]['#'])
		{
			return false;
		}

		if (
			!isset($updateList['MODULES'][0]['#']['MODULE'])
			|| !is_array($updateList['MODULES'][0]['#']['MODULE'])
		)
		{
			throw new SystemException('Empty update module list');
		}

		foreach ($updateList['MODULES'][0]['#']['MODULE'] as $module)
		{
			if (array_key_exists($module['@']['ID'], $installedModules))
				return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected static function AdminPolicyLevel()
	{
		$arGroupPolicy = array(
			"parent" => Array(
				"SESSION_TIMEOUT" => "",
				"SESSION_IP_MASK" => "",
				"MAX_STORE_NUM" => "",
				"STORE_IP_MASK" => "",
				"STORE_TIMEOUT" => "",
				"CHECKWORD_TIMEOUT" => "",
				"PASSWORD_LENGTH" => "",
				"PASSWORD_UPPERCASE" => "N",
				"PASSWORD_LOWERCASE" => "N",
				"PASSWORD_DIGITS" => "N",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => "",
			),
			"low" => Array(
				"SESSION_TIMEOUT" => 30, //minutes
				"SESSION_IP_MASK" => "0.0.0.0",
				"MAX_STORE_NUM" => 20,
				"STORE_IP_MASK" => "255.0.0.0",
				"STORE_TIMEOUT" => 60*24*93, //minutes
				"CHECKWORD_TIMEOUT" => 60*24*185,  //minutes
				"PASSWORD_LENGTH" => 6,
				"PASSWORD_UPPERCASE" => "N",
				"PASSWORD_LOWERCASE" => "N",
				"PASSWORD_DIGITS" => "N",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => 0,
			),
			"middle" => Array(
				"SESSION_TIMEOUT" => 20, //minutes
				"SESSION_IP_MASK" => "255.255.0.0",
				"MAX_STORE_NUM" => 10,
				"STORE_IP_MASK" => "255.255.0.0",
				"STORE_TIMEOUT" => 60*24*30, //minutes
				"CHECKWORD_TIMEOUT" => 60*24*1,  //minutes
				"PASSWORD_LENGTH" => 8,
				"PASSWORD_UPPERCASE" => "Y",
				"PASSWORD_LOWERCASE" => "Y",
				"PASSWORD_DIGITS" => "Y",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => 0,
			),
			"high" => Array(
				"SESSION_TIMEOUT" => 15, //minutes
				"SESSION_IP_MASK" => "255.255.255.255",
				"MAX_STORE_NUM" => 1,
				"STORE_IP_MASK" => "255.255.255.255",
				"STORE_TIMEOUT" => 60*24*3, //minutes
				"CHECKWORD_TIMEOUT" => 60,  //minutes
				"PASSWORD_LENGTH" => 10,
				"PASSWORD_UPPERCASE" => "Y",
				"PASSWORD_LOWERCASE" => "Y",
				"PASSWORD_DIGITS" => "Y",
				"PASSWORD_PUNCTUATION" => "Y",
				"LOGIN_ATTEMPTS" => 3,
			),
		);
		$arAdminPolicy = CUser::GetGroupPolicy(1);
		$level = 'high';
		if (is_array($arGroupPolicy))
		{
			foreach($arGroupPolicy['parent'] as $key => $value)
			{
				$el2_value = $arAdminPolicy[$key];
				$el2_checked = $arAdminPolicy[$key] === "Y";

				switch($key)
				{
					case "SESSION_TIMEOUT":
					case "MAX_STORE_NUM":
					case "STORE_TIMEOUT":
					case "CHECKWORD_TIMEOUT":
						if(intval($el2_value) <= intval($arGroupPolicy['high'][$key]))
							$clevel = 'high';
						elseif(intval($el2_value) <= intval($arGroupPolicy['middle'][$key]))
							$clevel = 'middle';
						else
							$clevel = 'low';
						break;
					case "PASSWORD_LENGTH":
						if(intval($el2_value) >= intval($arGroupPolicy['high'][$key]))
							$clevel = 'high';
						elseif(intval($el2_value) >= intval($arGroupPolicy['middle'][$key]))
							$clevel = 'middle';
						else
							$clevel = 'low';
						break;
					case "LOGIN_ATTEMPTS":
						if(intval($el2_value) > 0)
						{
							if(intval($el2_value) <= intval($arGroupPolicy['high'][$key]))
								$clevel = 'high';
							elseif(intval($el2_value) <= intval($arGroupPolicy['middle'][$key]))
								$clevel = 'middle';
							else
								$clevel = 'low';
						}
						else
						{
							if(intval($arGroupPolicy['high'][$key]) <= 0)
								$clevel = 'high';
							elseif(intval($arGroupPolicy['middle'][$key]) <= 0)
								$clevel = 'middle';
							else
								$clevel = 'low';
						}
						break;
					case "PASSWORD_UPPERCASE":
					case "PASSWORD_LOWERCASE":
					case "PASSWORD_DIGITS":
					case "PASSWORD_PUNCTUATION":
						if($el2_checked)
						{
							if($arGroupPolicy['high'][$key] == 'Y')
								$clevel = 'high';
							elseif($arGroupPolicy['middle'][$key] == 'Y')
								$clevel = 'middle';
							else
								$clevel = 'low';
						}
						else
						{
							if($arGroupPolicy['high'][$key] == 'N')
								$clevel = 'high';
							elseif($arGroupPolicy['middle'][$key] == 'N')
								$clevel = 'middle';
							else
								$clevel = 'low';
						}
						break;
					case "SESSION_IP_MASK":
					case "STORE_IP_MASK":
						$gp_ip = ip2long($el2_value);
						$high_ip = ip2long($arGroupPolicy['high'][$key]);
						$middle_ip = ip2long($arGroupPolicy['middle'][$key]);
						if(($gp_ip & $high_ip) == (0xFFFFFFFF & $high_ip))
							$clevel = 'high';
						elseif(($gp_ip & $middle_ip) == (0xFFFFFFFF & $middle_ip))
							$clevel = 'middle';
						else
							$clevel = 'low';
						break;
					default:
						$clevel = 'low';
						break;
				}

				if($clevel == 'low')
					$level = $clevel;
				elseif($clevel == 'middle' && $level == 'high')
					$level = $clevel;
			}
		}

		return $level;
	}
}