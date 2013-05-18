<?

class CSecurityEnvironmentTest extends CSecurityBaseTest
{
	const MIN_UID = 100;
	const MIN_GID = 100;
	const SYSTEM_TMP_DIR = "/tmp";

	protected $internalName = "EnvironmentTest";
	protected $tests = array(
		"uploadTmpDir" => array(
			"method" => "checkPhpUploadDir",
			"base_message_key" => "SECURITY_SITE_CHECKER_UPLOAD_TMP",
			"critical" => CSecurityCriticalLevel::MIDDLE
		),
		"sessionDir" => array(
			"method" => "checkPhpSessionDir",
			"base_message_key" => "SECURITY_SITE_CHECKER_SESSION",
			"critical" => CSecurityCriticalLevel::HIGHT
		),
//		"collectivePhpSession" => array(
//			"method" => "checkCollectivePhpSession",
//			"base_message_key" => "SECURITY_SITE_CHECKER_COLLECTIVE_SESSION",
//			"critical" => CSecurityCriticalLevel::HIGHT
//		),
		"uploadScriptExecution" => array(
			"method" => "checkUploadScriptExecution"
		),
	);

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}


	/**
	 * Check if any script executed in /upload dir and push those information to detail error
	 * @return bool
	 */
	protected function checkUploadScriptExecution()
	{
		$baseMessageKey = "SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE";
		if(self::isHtaccessOverrided())
		{
			$isHtaccessOverrided = true;
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_UPLOAD_HTACCESS", CSecurityCriticalLevel::LOW);
		}
		else
		{
			$isHtaccessOverrided = false;
		}


		$uniqueString = randString(20);
		if(self::isScriptExecutable("test.php", "<?php echo '{$uniqueString}'; ?>", $uniqueString))
		{
			$isPhpExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PHP", CSecurityCriticalLevel::LOW);
		}
		else
		{
			$isPhpExecutable = false;
		}


		if(!$isPhpExecutable && self::isScriptExecutable("test.php.any", "<?php echo '{$uniqueString}'; ?>", $uniqueString))
		{
			$isPhpDoubleExtensionExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PHP_DOUBLE", CSecurityCriticalLevel::LOW);
		}
		else
		{
			$isPhpDoubleExtensionExecutable = false;
		}


		if(self::isScriptExecutable("test.py", "print 'Content-type:text/html\r\n\r\n{$uniqueString}'", $uniqueString))
		{
			$isPythonCgiExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PY", CSecurityCriticalLevel::LOW);
		}
		else
		{
			$isPythonCgiExecutable = false;
		}

		return !($isPhpExecutable || $isPhpDoubleExtensionExecutable || $isHtaccessOverrided || $isPythonCgiExecutable);
	}

	/**
	 * Check apache AllowOverride
	 * @return bool
	 */
	protected function isHtaccessOverrided()
	{
		$uploadPathTestFile = self::getUploadDir().'test/test.php';
		$uploadPathHtaccessFile = self::getUploadDir().'test/.htaccess';
		if(!CheckDirPath($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile))
			return false;

		$testingText = "testing text here...";
		$result = false;
		if(@file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile, $testingText))
		{
			$response = self::doRequestToLocalhost($uploadPathTestFile);
			if($response && $response == $testingText)
			{
				if(@file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPathHtaccessFile, "Deny from All"))
				{
					$response = self::doRequestToLocalhost($uploadPathTestFile);
					if($response && $response != $testingText)
					{
						$result = true;
					}
					@unlink($_SERVER['DOCUMENT_ROOT'].$uploadPathHtaccessFile);
				}
			}
			@unlink($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile);
		}
		return $result;
	}

	/**
	 * Return upload dir path for test usage
	 * @return string
	 */
	protected static function getUploadDir()
	{
		return "/".COption::GetOptionString("main", "upload_dir", "upload")."/tmp/";
	}

	/**
	 * Return current domain name (in puny code for cyrillic domain)
	 * @return string
	 */
	protected static function getCurrentHost()
	{
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : 'localhost';
		return CBXPunycode::ToASCII($host, $arErrors);
	}

	/**
	 * Return current site url, e.g. http://localhost:8990
	 * @return string
	 */
	protected static function getCurrentSiteUrl()
	{
		$url = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
		$url .= self::getCurrentHost();
		$url .= $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '';
		return $url;
	}

	/**
	 * Make request to current site and return result
	 * @param string $pPath - url path, e.g. /upload/tmp/test.php
	 * @return bool|string
	 */
	protected static function doRequestToLocalhost($pPath)
	{
		$url = self::getCurrentSiteUrl();
		$url .= $pPath;
		$url .= "?".mt_rand(); //Prevent web-server cache
		return @CHTTP::sGet($url);
	}

	/**
	 * @param string $pFileName - testing file name. e.g. test.php
	 * @param string $pText - script entry
	 * @param string $pSearch - text for searching after script execute
	 * @return bool
	 */
	protected function isScriptExecutable($pFileName, $pText, $pSearch)
	{
		$uploadPath = self::getUploadDir().$pFileName;
		if(!CheckDirPath($_SERVER['DOCUMENT_ROOT'].$uploadPath))
			return false;

		$result = false;
		if(@file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPath, $pText))
		{
			$response = self::doRequestToLocalhost($uploadPath);
			if($response)
			{
				if($response != $pText && strpos($response, $pSearch) !== false)
				{
					$result = true;
				}
			}
			@unlink($_SERVER['DOCUMENT_ROOT'].$uploadPath);
		}
		return $result;
	}

	/**
	 * Return php tmp dir from environment
	 * @return string
	 */
	protected static function getTmpDirFromEnv()
	{
		if ($_ENV["TMP"])
		{
			return realpath($_ENV["TMP"]);
		}
		elseif ($_ENV["TMPDIR"])
		{
			return realpath($_ENV["TMPDIR"]);
		}
		elseif ($_ENV["TEMP"])
		{
			return realpath($_ENV["TEMP"]);
		}
		else
		{
			return "";
		}
	}

	/**
	 * Return php session or upload tmp dir
	 * @param string $pPhpSettingKey
	 * @return null|string
	 */
	protected static function getTmpDir($pPhpSettingKey = "upload_tmp_dir")
	{
		$result = ini_get($pPhpSettingKey);
		if(!$result)
		{
			if (function_exists("sys_get_temp_dir"))
			{
				$result = sys_get_temp_dir();
			}
			else
			{
				$result = self::getTmpDirFromEnv();
			}
		}
		return $result;
	}

	/**
	 * Check php upload tmp dir for world accessible
	 * @return bool
	 */
	protected function checkPhpUploadDir()
	{
		if(self::isRunOnWin())
			return true;

		$tmpDir = self::getTmpDir("upload_tmp_dir");
		if(!$tmpDir)
			return true;

		if($tmpDir == self::SYSTEM_TMP_DIR || self::isWorldAccessible($tmpDir))
			return false;
		else
			return true;
	}

	/**
	 * Return session unique ID
	 * @return string
	 */
	protected static function getSessionUniqID()
	{
		return bitrix_sess_sign();
	}

	/**
	 * Check session file
	 * @param string $pFileName
	 * @return bool
	 */
	protected function isStrangeSessionFile($pFileName)
	{
		$currentUID = self::getCurrentUID();
		if($currentUID != fileowner($pFileName))
			return true;

		if(is_readable($pFileName))
		{
			if(strpos(file_get_contents($pFileName), self::getSessionUniqID()) === false)
				return true;
		}
		return false;
	}

	/**
	 * Check session files collective usage, e.g. several owners in the same session directory
	 * @return bool
	 */
	protected function checkCollectivePhpSession()
	{
		if(self::isRunOnWin())
			return true;

		if(COption::GetOptionString("security", "session") == "Y")
			return true;

		if(ini_get("session.save_handler") != "files")
			return true;

		$tmpDir = self::getTmpDir("session.save_path");
		if(!$tmpDir)
			return true;


		foreach (glob($tmpDir."/sess_*", GLOB_NOSORT) as $fileName)
		{
			if(self::isStrangeSessionFile($fileName))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Check php session save dir for world accessible
	 * @return bool
	 */
	protected function checkPhpSessionDir()
	{
		if(self::isRunOnWin())
			return true;

		if(COption::GetOptionString("security", "session") == "Y")
			return true;

		if(ini_get("session.save_handler") != "files")
			return true;

		$tmpDir = self::getTmpDir("session.save_path");
		if(!$tmpDir)
			return true;

		if($tmpDir == self::SYSTEM_TMP_DIR || self::isWorldAccessible($tmpDir))
			return false;
		else
			return true;
	}

	/**
	 * Return current system user ID
	 * @return bool|int
	 */
	protected static function getCurrentUID()
	{
		if(is_callable("getmyuid"))
		{
			return getmyuid();
		}
		elseif(is_callable("posix_geteuid"))
		{
			return posix_geteuid();
		}
		else
		{
			return false;
		}
	}

	/**
	 * Return current system user group ID
	 * @return bool|int
	 */
	protected static function getCurrentGID()
	{
		if(is_callable("getmygid"))
		{
			return getmygid();
		}
		elseif(is_callable("posix_getegid"))
		{
			return posix_getegid();
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check minimal UID and GID
	 * @return bool
	 */
	protected function checkUserAndGroup()
	{
		if(self::isRunOnWin())
			return true;

		$result = true;
		$uid = self::getCurrentUID();
		if($uid !== false && $uid < self::MIN_UID)
			$result = false;
		$gid = self::getCurrentGID();
		if($gid !== false && $gid < self::MIN_GID)
			$result = false;
		return $result;
	}

}