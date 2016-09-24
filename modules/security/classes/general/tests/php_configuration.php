<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityPhpConfigurationTest
 * @since 12.5.0
 */
class CSecurityPhpConfigurationTest
	extends CSecurityBaseTest
{
	protected $internalName = "PhpConfigurationTest";

	protected $tests = array(
		"phpEntropy" => array(
			"method" => "checkPhpEntropy"
		),
		"phpInclude" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("allow_url_include"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_INCLUDE",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"phpFopen" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("allow_url_fopen"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_FOPEN",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"aspTags" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("asp_tags"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_ASP",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"httpOnly" => array(
			"method" => "isPhpConfVarOn",
			"params" => array("session.cookie_httponly"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_HTTPONLY",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"cookieOnly" => array(
			"method" => "isPhpConfVarOn",
			"params" => array("session.use_only_cookies"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_COOKIEONLY",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"mbstringSubstitute" => array(
			"method" => "checkMbstringSubstitute",
			"params" => array(),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_MBSTRING_SUBSTITUTE",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		// ToDo: need compatibility with PHP < 5.4.0?
		"zendMultibyte" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("zend.multibyte"),
			"base_message_key" => "SECURITY_SITE_CHECKER_ZEND_MULTIBYTE_ENABLED",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"displayErrors" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("display_errors"),
			"base_message_key" => "SECURITY_SITE_CHECKER_DISPLAY_ERRORS",
			"critical" => CSecurityCriticalLevel::LOW
		),
		"requestOrder" => array(
			"method" => "checkRequestOrder"
		),
		"mailAddHeader" => array(
			"method" => "isPhpConfVarOff",
			"params" => array("mail.add_x_header"),
			"base_message_key" => "SECURITY_SITE_CHECKER_MAIL_ADD_HEADER",
			"critical" => CSecurityCriticalLevel::LOW
		),
	);

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Check php session entropy
	 * @return bool
	 */
	protected function checkPhpEntropy()
	{
		if(self::isRunOnWin() && version_compare(phpversion(),"5.3.3","<"))
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_LOW_PHP_VERSION_ENTROPY", CSecurityCriticalLevel::MIDDLE);
			return self::STATUS_FAILED;
		}
		elseif(!self::checkPhpEntropyConfigs())
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_PHP_ENTROPY", CSecurityCriticalLevel::MIDDLE);
			return self::STATUS_FAILED;
		}
		return self::STATUS_PASSED;
	}

	/**
	 * @return bool
	 */
	protected function checkPhpEntropyConfigs()
	{
		$entropyFile = ini_get("session.entropy_file");
		$entropyLength = ini_get("session.entropy_length");

		if(!in_array($entropyFile, array("/dev/random", "/dev/urandom"), true))
		{
			return self::STATUS_FAILED;
		}

		if(self::isRunOnWin() && !$entropyLength)
		{
			return self::STATUS_FAILED;
		}
		elseif ($entropyLength < 128)
		{
			return self::STATUS_FAILED;
		}

		return self::STATUS_PASSED;
	}

	protected function checkRequestOrder()
	{
		$order = ini_get('request_order');
		if (!$order || !in_array($order, array('GP', 'PG'), true))
		{
			$this->addUnformattedDetailError(
					'SECURITY_SITE_CHECKER_PHP_REQUEST_ORDER',
					CSecurityCriticalLevel::MIDDLE,
					getMessage('SECURITY_SITE_CHECKER_PHP_REQUEST_ORDER_ADDITIONAL', array(
						'#CURRENT#' => $order,
						'#RECOMMENDED#' => 'GP'
					))
			);
			return self::STATUS_FAILED;
		}

		return self::STATUS_PASSED;
	}

	/**
	 * @return bool
	 */
	protected function checkMbstringSubstitute()
	{
		if (extension_loaded('mbstring') && $this->isPhpConfVarEquals('mbstring.substitute_character', 'none'))
			return self::STATUS_FAILED;

		return self::STATUS_PASSED;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	protected function isPhpConfVarOff($name)
	{
		return (intval(ini_get($name)) == 0 || strtolower(trim(ini_get($name))) == "off");
	}

	/**
	 * @param string $name
	 * @return bool
	 * @since 14.0.0
	 */
	protected function isPhpConfVarOn($name)
	{
		return (intval(ini_get($name)) == 1 || strtolower(trim(ini_get($name))) == "on");
	}

	/**
	 * @param string $name
	 * @param int|string $value
	 * @return bool
	 */
	protected function isPhpConfVarEquals($name, $value)
	{
		return ini_get($name) == $value;
	}

	/**
	 * @param string $name
	 * @param int|string $value
	 * @return bool
	 */
	protected function isPhpConfVarNotEquals($name, $value)
	{
		return ini_get($name) != $value;
	}

}