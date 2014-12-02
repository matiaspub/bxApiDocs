<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityFilePermissionsTest
 * @since 12.5.0
 */
class CSecurityFilePermissionsTest
	extends CSecurityBaseTest
{
	const MAX_OUTPUT_FILES = 5;
	protected $internalName = "FilePermissionsTest";
	protected static $interestingFileExtentions = array(".php", ".js", ".htaccess", ".html");
	protected static $skipDirs = array("upload");
	protected $filesCount = 0;
	protected $filesPath = array();

	protected $maximumExecutionTime = 0.0;
	protected $savedMaxExecutionTime = 0.0;

	public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
		$this->savedMaxExecutionTime = ini_get("max_execution_time");
		if($this->savedMaxExecutionTime <= 0)
			$phpMaxExecutionTime = 30;
		else
			$phpMaxExecutionTime = $this->savedMaxExecutionTime - 2;
		$this->maximumExecutionTime = time() + $phpMaxExecutionTime;
		set_time_limit(0);
	}

	public function __destruct()
	{
		set_time_limit($this->savedMaxExecutionTime);
	}

	/**
	 * Check test requirements (e.g. max_execution_time)
	 *
	 * @param array $params
	 * @throws CSecurityRequirementsException
	 * @return bool
	 */
	public function checkRequirements($params = array())
	{
		if($this->maximumExecutionTime - time() <= 5)
			throw new CSecurityRequirementsException(GetMessage('SECURITY_SITE_CHECKER_FILE_PERM_SMALL_MAX_EXEC'));
		return true;
	}

	/**
	 * Run test and return results
	 * @param array $params
	 * @return array
	 */
	public function check($params = array())
	{
		$this->initializeParams($params);
		if(!self::isRunOnWin())
		{
			$folder = self::getParam("folder", $_SERVER["DOCUMENT_ROOT"]);
			try
			{
				$this->checkWorldWritableDirRecursive($folder);
			}
			catch(Exception $e)
			{
				return array(
					"name" => $this->getName(),
					"status" => true,
					"fatal_error_text" => GetMessage($e->getMessage())
				);
			}
		}

		if($this->filesCount <= self::MAX_OUTPUT_FILES)
			$recommendationFilesCount = $this->filesCount;
		else
			$recommendationFilesCount = self::MAX_OUTPUT_FILES;

		$additionalInfo = GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_ADDITIONAL",array("#COUNT#" => $recommendationFilesCount));
		$additionalInfo .= "<br>";
		$additionalInfo .= $this->getFilesPathInString();

		$result = array(
			"name" => $this->getName(),
			"problem_count" => 1,
			"errors" => array(
				array(
					"title" => GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_TITLE", array("#COUNT#" => $this->filesCount)),
					"critical" => CSecurityCriticalLevel::HIGHT,
					"detail" => GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_DETAIL"),
					"recommendation" => GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_RECOMMENDATION"),
					"additional_info" => $additionalInfo
				)
			),
			"status" => ($this->filesCount <= 0)
		);
		return $result;
	}

	/**
	 * @param string $pFileName
	 * @return bool
	 */
	protected static function isInterestingDir($pFileName)
	{
		return is_dir($pFileName);
	}

	/**
	 * @param string $pFileName
	 * @return bool
	 */
	protected static function isInterestingFile($pFileName)
	{
		return is_file($pFileName) && in_array(substr($pFileName, -4), self::$interestingFileExtentions, true);
	}

	/**
	 * @param string $pDir
	 * @throws Exception
	 * @return bool
	 */
	protected function checkWorldWritableDirRecursive($pDir)
	{
		$result = false;
		if ($handle = opendir($pDir))
		{
			while (false !== ($item = readdir($handle)))
			{
				if($item == "." || $item == ".." || in_array($item, self::$skipDirs))
					continue;

				if($this->filesCount > self::MAX_OUTPUT_FILES)
					return $result;

				if(time() >= $this->maximumExecutionTime)
					throw new Exception('SECURITY_SITE_CHECKER_FILE_PERM_TIMEOUT');

				$curFile = $pDir."/".$item;
				$isInteresting = self::isInterestingFile($curFile) || self::isInterestingDir($curFile);
				if ($isInteresting && self::isWorldWritable($curFile))
				{
					$result = true;
					$this->filesCount++;
					$this->addFilePath($curFile);

				}
				if (is_dir($curFile))
				{
					if($this->checkWorldWritableDirRecursive($curFile))
					{
						$result = true;
					}
				}
			}
			closedir($handle);
		}
		return $result;
	}

	/**
	 * @param string $pFilePath
	 */
	protected function addFilePath($pFilePath)
	{
		if($this->filesCount <= self::MAX_OUTPUT_FILES)
		{
			array_push($this->filesPath, self::removeDocumentRoot($pFilePath));
		}
	}

	/**
	 * @return array
	 */
	protected function getFilesPath()
	{
		return $this->filesPath;
	}

	/**
	 * @param string $pGlue
	 * @return string
	 */
	protected function getFilesPathInString($pGlue = "<br>")
	{
		return implode($pGlue, $this->filesPath);
	}
}