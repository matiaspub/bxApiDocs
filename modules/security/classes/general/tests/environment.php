<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityEnvironmentTest
 * @since 12.5.0
 */
class CSecurityEnvironmentTest
	extends CSecurityBaseTest
{
	const MIN_UID = 10;
	const MIN_GID = 10;

	protected $internalName = "EnvironmentTest";
	protected $tests = array(
		"sessionDir" => array(
			"method" => "checkPhpSessionDir"
		),
		"collectivePhpSession" => array(
			"method" => "checkCollectivePhpSession"
		),
		"uploadScriptExecution" => array(
			"method" => "checkUploadScriptExecution"
		),
		"uploadNegotiationEnabled" => array(
			"method" => "checkUploadNegotiationEnabled"
		),
		"privilegedPhpUserOrGroup" => array(
			"method" => "checkPhpUserAndGroup"
		),
		"bitrixTempPath" => array(
			"method" => "checkBitrixTempPath"
		)
	);
	//TODO: check custom php/py/perl/etc handlers in .htaccess files

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}


	/**
	 * Check if any server-side script executed in /upload dir and push those information to detail error
	 * @return bool
	 */
	protected function checkUploadScriptExecution()
	{
		$baseMessageKey = "SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE";

		$isHtaccessOverrided = false;
// ToDo: fix and enable later
//		if(self::isHtaccessOverrided())
//		{
//			$isHtaccessOverrided = true;
//			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_UPLOAD_HTACCESS", CSecurityCriticalLevel::LOW);
//		}

		$isPhpExecutable = false;
		$uniqueString = randString(20);
		if(self::isScriptExecutable("test.php", "<?php echo '{$uniqueString}'; ?>", $uniqueString))
		{
			$isPhpExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PHP", CSecurityCriticalLevel::LOW);
		}

		$isPhpDoubleExtensionExecutable = false;
		if(!$isPhpExecutable && self::isScriptExecutable("test.php.any", "<?php echo '{$uniqueString}'; ?>", $uniqueString))
		{
			$isPhpDoubleExtensionExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PHP_DOUBLE", CSecurityCriticalLevel::LOW);
		}

		$isPythonCgiExecutable = false;
		if(self::isScriptExecutable("test.py", "print 'Content-type:text/html\\r\\n\\r\\n{$uniqueString}'", $uniqueString))
		{
			$isPythonCgiExecutable = true;
			$this->addUnformattedDetailError($baseMessageKey."_PY", CSecurityCriticalLevel::LOW);
		}

		if ($isPhpExecutable || $isPhpDoubleExtensionExecutable || $isHtaccessOverrided || $isPythonCgiExecutable)
			return self::STATUS_FAILED;
		else
			return self::STATUS_PASSED;
	}

	/**
	 * Check if Apache Content Negotiation enabled in /upload dir and push those information to detail error
	 * @return bool
	 */
	protected function checkUploadNegotiationEnabled()
	{
		$testingText = "test";
		$testFileContent = "
Content-language: ru
Content-type: text/html;
Body:----------ru--
".$testingText."
----------ru--

";

		if(self::isScriptExecutable("test.var.jpg", $testFileContent, $testingText))
		{
			$this->addUnformattedDetailError("SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION", CSecurityCriticalLevel::MIDDLE);
			return self::STATUS_FAILED;
		}

		return self::STATUS_PASSED;
	}

	/**
	 * Check apache AllowOverride
	 * @return bool
	 */
	protected function isHtaccessOverrided()
	{
		$uploadDir = self::getUploadDir();
		$uploadPathTestFile = $uploadDir.'test/test.php';
		$uploadPathHtaccessFile = $uploadDir.'test/.htaccess';
		$uploadPathTestUri = $uploadDir.'test/test_notexist.php';

		if(!CheckDirPath($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile))
			return false;

		$testingText = "testing text here...";
		$htaccessText = <<<HTACCESS
ErrorDocument 404 ${uploadPathTestFile}

<IfModule mod_rewrite.c>
	RewriteEngine Off
</IfModule>
HTACCESS;

		$result = false;
		if(file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile, $testingText))
		{
			if(file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPathHtaccessFile, $htaccessText))
			{
				$response = self::doRequestToLocalhost($uploadPathTestUri);
				if($response && $response == $testingText)
				{
					$result = true;
				}
				unlink($_SERVER['DOCUMENT_ROOT'].$uploadPathHtaccessFile);
			}
			unlink($_SERVER['DOCUMENT_ROOT'].$uploadPathTestFile);
		}
		return $result;
	}

	/**
	 * Check minimal UID and GID
	 *
	 * @param int $minUid
	 * @param int $minGid
	 * @return bool
	 */
	protected function checkPhpUserAndGroup($minUid = self::MIN_UID, $minGid = self::MIN_GID)
	{
		if(self::isRunOnWin())
			return self::STATUS_PASSED;

		$uid = self::getCurrentUID();
		$uidCheckFailed = false;
		if($uid !== null && $uid < $minUid)
			$uidCheckFailed = true;

		$gid = self::getCurrentGID();
		$gidCheckFailed = false;
		if($gid !== null && $gid < $minGid)
			$gidCheckFailed = true;

		if ($uidCheckFailed || $gidCheckFailed)
		{
			$this->addUnformattedDetailError(
					'SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER',
					($uid == 0 || $gid == 0 ? CSecurityCriticalLevel::HIGHT: CSecurityCriticalLevel::MIDDLE),
					getMessage('SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER_ADDITIONAL', array(
						'#UID#' => static::formatUID($uid),
						'#GID#' => static::formatGID($gid)
					))
			);
			return self::STATUS_FAILED;
		}

		return self::STATUS_PASSED;
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
		$errors = array();
		return CBXPunycode::ToASCII($host, $errors);
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
		if(file_put_contents($_SERVER['DOCUMENT_ROOT'].$uploadPath, $pText))
		{
			$response = self::doRequestToLocalhost($uploadPath);
			if($response)
			{
				if($response != $pText && strpos($response, $pSearch) !== false)
				{
					$result = true;
				}
			}
			unlink($_SERVER['DOCUMENT_ROOT'].$uploadPath);
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
	 * @return string
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

		return preg_replace('#[\\\/]+#', '/', $result);
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
	 * Check session files collective usage, e.g. several owners in the same session directory
	 * @return bool
	 */
	protected function checkCollectivePhpSession()
	{
		if(self::isRunOnWin())
			return self::STATUS_PASSED;

		if(COption::GetOptionString("security", "session") == "Y")
			return self::STATUS_PASSED;

		if(ini_get("session.save_handler") != "files")
			return self::STATUS_PASSED;

		$tmpDir = self::getTmpDir("session.save_path");
		if(!$tmpDir)
			return self::STATUS_PASSED;

		$additionalInfo = "";
		$isFailed = false;
		$currentUID = self::getCurrentUID();
		$sessionSign = self::getSessionUniqID();
		foreach (glob($tmpDir."/sess_*", GLOB_NOSORT) as $fileName)
		{

			if($currentUID !== null)
			{
				$fileOwner = fileowner($fileName);
				if($currentUID != $fileOwner)
				{
					$additionalInfo = getMessage("SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_OWNER", array(
						"#FILE#" => $fileName,
						"#FILE_ONWER#" => $fileOwner,
						"#CURRENT_OWNER#" => $currentUID,
					));
					$isFailed = true;
					break;
				}
			}

			if(is_readable($fileName))
			{
				$fileContent = file_get_contents($fileName);
				if (strpos($fileContent, $sessionSign) === false)
				{
					$additionalInfo = getMessage("SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_SIGN", array(
						"#FILE#" => $fileName,
						"#FILE_CONTENT#" => htmlspecialcharsbx(substr($fileContent, 0, 1024)),
						"#SIGN#" => $sessionSign
					));
					$isFailed = true;
					break;
				}
			}
		}

		if($isFailed)
		{
			$this->addUnformattedDetailError(
				"SECURITY_SITE_CHECKER_COLLECTIVE_SESSION",
				CSecurityCriticalLevel::HIGHT,
				$additionalInfo
			);
			return self::STATUS_FAILED;
		}

		return self::STATUS_PASSED;
	}

	/**
	 * Check php session save dir for world accessible
	 * @return bool
	 */
	protected function checkPhpSessionDir()
	{
		if (self::isRunOnWin())
			return self::STATUS_PASSED;

		if (COption::GetOptionString("security", "session") == "Y")
			return self::STATUS_PASSED;

		if (ini_get("session.save_handler") != "files")
			return self::STATUS_PASSED;

		$tmpDir = self::getTmpDir("session.save_path");
		if (!$tmpDir)
			return self::STATUS_PASSED;

		$dir = $tmpDir;
		while ($dir && $dir != '/')
		{
			$perms = static::getFilePerm($dir);
			if (($perms & 0x0001) === 0)
				return self::STATUS_PASSED;

			$dir = dirname($dir);
		}

		$this->addUnformattedDetailError(
			"SECURITY_SITE_CHECKER_SESSION_DIR",
			CSecurityCriticalLevel::HIGHT,
			getMessage("SECURITY_SITE_CHECKER_SESSION_DIR_ADDITIONAL", array(
				"#DIR#" => $tmpDir,
				"#PERMS#" => self::formatFilePermissions(static::getFilePerm($tmpDir)),
			))
		);

		return self::STATUS_FAILED;
	}

	/**
	 * Return current system user ID
	 *
	 * @return int|null
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
			return null;
		}
	}

	/**
	 * Return current system user group ID
	 *
	 * @return int|null
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
			return null;
		}
	}

	/**
	 * Format system user ID, e.g. $uid 0 = root(0)
	 *
	 * @param int $uid
	 * @return string
	 */
	protected static function formatUID($uid)
	{
		if(is_callable("posix_getpwuid"))
		{
			$uid = posix_getpwuid($uid);
			return sprintf('%s(%s)', $uid['name'], $uid['uid']);
		}

		return $uid;
	}

	/**
	 * Format system user group ID, e.g. $gid 0 = root(0)
	 *
	 * @param int $gid
	 * @return string
	 */
	protected static function formatGID($gid)
	{
		if(is_callable("posix_getgrgid"))
		{
			$gid = posix_getgrgid($gid);
			return sprintf('%s(%s)', $gid['name'], $gid['gid']);
		}

		return $gid;
	}

	protected static function formatFilePermissions($perms)
	{
		// http://www.php.net/manual/en/function.fileperms.php

		if (($perms & 0xC000) == 0xC000)
		{
			// Socket
			$info = 's';
		}
		elseif (($perms & 0xA000) == 0xA000)
		{
			// Symbolic Link
			$info = 'l';
		}
		elseif (($perms & 0x8000) == 0x8000)
		{
			// Regular
			$info = '-';
		}
		elseif (($perms & 0x6000) == 0x6000)
		{
			// Block special
			$info = 'b';
		}
		elseif (($perms & 0x4000) == 0x4000)
		{
			// Directory
			$info = 'd';
		}
		elseif (($perms & 0x2000) == 0x2000)
		{
			// Character special
			$info = 'c';
		}
		elseif (($perms & 0x1000) == 0x1000)
		{
			// FIFO pipe
			$info = 'p';
		}
		else
		{
			// Unknown
			$info = 'u';
		}

		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ?
			(($perms & 0x0800) ? 's' : 'x' ) :
			(($perms & 0x0800) ? 'S' : '-'));

		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ?
			(($perms & 0x0400) ? 's' : 'x' ) :
			(($perms & 0x0400) ? 'S' : '-'));

		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ?
			(($perms & 0x0200) ? 't' : 'x' ) :
			(($perms & 0x0200) ? 'T' : '-'));

		return $info;
	}

	/**
	 * Check Bitrix temporary directory path.
	 *
	 * @since 15.5.4
	 * @return string
	 */
	protected function checkBitrixTempPath()
	{
		$io = CBXVirtualIo::GetInstance();

		$path = CTempFile::GetAbsoluteRoot();
		$path = $io->CombinePath($path);

		$documentRoot = self::getParam("DOCUMENT_ROOT", $_SERVER["DOCUMENT_ROOT"]);
		$documentRoot = $io->CombinePath($documentRoot);

		if (strpos($path, $documentRoot) === 0)
		{
			$this->addUnformattedDetailError(
					"SECURITY_SITE_CHECKER_BITRIX_TMP_DIR",
					CSecurityCriticalLevel::MIDDLE,
					getMessage("SECURITY_SITE_CHECKER_BITRIX_TMP_DIR_ADDITIONAL", array(
							"#DIR#" => $path
					))
			);

			return static::STATUS_FAILED;
		}

		return static::STATUS_PASSED;
	}
}