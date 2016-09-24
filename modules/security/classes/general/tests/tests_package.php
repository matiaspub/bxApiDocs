<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityTestsPackage
 * @since 12.5.0
 */
class CSecurityTestsPackage
{
	const SLOW_LOCAL_TESTS = "slow_local";
	const FAST_LOCAL_TESTS = "fast_local";
	const LOCAL_TESTS = "local";
	const REMOTE_TESTS = "remote";

	protected static $fastLocalTests = array(
		"CSecurityEnvironmentTest",
		"CSecurityPhpConfigurationTest"
	);

	protected static $slowLocalTests = array(
		"CSecurityFilePermissionsTest",
		"CSecurityTaintCheckingTest",
		"CSecurityUserTest",
		"CSecuritySiteConfigurationTest"
	);

	protected static $remoteTests = array(
		"CSecurityCloudMonitorTest",
	);

	/**
	 * Return tests classes
	 * @param string $pType
	 * @return array
	 */
	public static function getTestsPackage($pType = "")
	{
		if(is_array($pType))
		{
			$tests = array();
			foreach($pType as $type)
			{
				$tests = array_merge($tests, self::getPackage($type));
			}
		}
		else
		{
			$tests = self::getPackage($pType);
		}
		return $tests;
	}

	/**
	 * @return array
	 */
	public static function getAllTests()
	{
		return array_merge(self::$fastLocalTests, self::$slowLocalTests, self::$remoteTests);
	}

	/**
	 * @param string $pType
	 * @return array
	 */
	protected static function getPackage($pType = "")
	{
		if(!is_string($pType) || $pType == "")
			return array();

		if($pType === self::FAST_LOCAL_TESTS)
		{
			return self::$fastLocalTests;
		}
		elseif($pType === self::SLOW_LOCAL_TESTS)
		{
			return self::$slowLocalTests;
		}
		elseif($pType === self::LOCAL_TESTS)
		{
			return array_merge(self::$fastLocalTests, self::$slowLocalTests);
		}
		elseif($pType === self::REMOTE_TESTS)
		{
			return self::$remoteTests;
		}
		else
		{
			return array();
		}
	}
}
