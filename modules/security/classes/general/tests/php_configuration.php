<?

class CSecurityPhpConfigurationTest extends CSecurityBaseTest
{
	protected $internalName = "PhpConfigurationTest";

	protected $tests = array(
		"phpEntropy" => array(
			"method" => "checkPhpEntropy"
		),
		"phpInclude" => array(
			"method" => "isPhpIniVarOff",
			"params" => array("allow_url_include"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_INCLUDE",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
		"phpFopen" => array(
			"method" => "isPhpIniVarOff",
			"params" => array("allow_url_fopen"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_FOPEN",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"aspTags" => array(
			"method" => "isPhpIniVarOff",
			"params" => array("asp_tags"),
			"base_message_key" => "SECURITY_SITE_CHECKER_PHP_ASP",
			"critical" => CSecurityCriticalLevel::HIGHT
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
			return false;
		}
		elseif(!self::checkPhpEntropyConfigs())
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_PHP_ENTROPY", CSecurityCriticalLevel::MIDDLE);
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	protected function checkPhpEntropyConfigs()
	{
		$entropyFile = ini_get("session.entropy_file");
		$entropyLength = ini_get("session.entropy_length");
		if(in_array($entropyFile, array("/dev/random", "/dev/urandom"), true))
		{
			if($entropyLength >= 128 || self::isRunOnWin())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $pName
	 * @return bool
	 */
	protected function isPhpIniVarOff($pName)
	{
		return (intval(ini_get($pName)) == 0 || strtolower(trim(ini_get($pName))) == "off");
	}

}