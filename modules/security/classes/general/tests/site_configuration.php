<?

class CSecuritySiteConfigurationTest extends CSecurityBaseTest
{
	protected $internalName = "SiteConfigurationTest";

	protected $tests = array(
		"securityLevel" => array(
			"method" => "checkSecurityLevel"
		),
		"dbPassword" => array(
			"method" => "checkDbPassword"
		),
	);

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	protected function checkSecurityLevel()
	{
		/** @global CDataBase $DB */
		global $DB;
		if(!CSecurityFilter::IsActive())
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_WAF_OFF", CSecurityCriticalLevel::HIGHT);
		}
		if (self::AdminPolicyLevel() != "high")
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_ADMIN_SECURITY_LEVEL", CSecurityCriticalLevel::HIGHT);
		}
		$validErrorReporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE;
		if (COption::GetOptionInt("main", "error_reporting", $validErrorReporting) != $validErrorReporting && COption::GetOptionString("main","error_reporting","") != "")
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_ERROR_REPORTING", CSecurityCriticalLevel::MIDDLE);
		}
		if($DB->debug)
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_DEBUG", CSecurityCriticalLevel::HIGHT);
		}

	}

	protected function checkDbPassword()
	{
		/** @global CDataBase $DB */
		global $DB;
		$password = $DB->DBPassword;
		$sign = ",.#!*%$:-";
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
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_NO_DIT_PASS", CSecurityCriticalLevel::HIGHT);
			}
			if(strpbrk($password, $dit) === false)
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_NO_SIGN_PASS", CSecurityCriticalLevel::HIGHT);
			}
			if (strlen($password)<8)
			{
				$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_DB_MIN_LEN_PASS", CSecurityCriticalLevel::HIGHT);
			}
		}
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