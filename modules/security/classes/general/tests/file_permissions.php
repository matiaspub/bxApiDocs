<?

class CSecurityFilePermissionsTest extends CSecurityBaseTest
{
	const MAX_OUTPUT_FILES = 5;
	protected $internalName = "FilePermissionsTest";
	protected static $interestingFileExtentions = array(".php", ".js", ".htaccess", ".html");
	protected static $skipDirs = array("upload");
	protected $filesCount = 0;
	protected $filesPath = array();

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Run test and return results
	 * @param array $pParams
	 * @return array
	 */
	public function check($pParams = array())
	{
		self::initializeParams($pParams);
		if(!self::isRunOnWin())
		{
			$folder = self::getParam("folder", $_SERVER["DOCUMENT_ROOT"]);
			$this->checkWorldWritableDirRecursive($folder);
		}

		if($this->filesCount <= self::MAX_OUTPUT_FILES)
		{
			$recommendationFilesCount = $this->filesCount;
		}
		else
		{
			$recommendationFilesCount = self::MAX_OUTPUT_FILES;
		}
		$recommendation = GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_RECOMMENDATION");
		$recommendation .= "<br>";
		$recommendation .= GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_ADDITIONAL",array("#COUNT#" => $recommendationFilesCount));
		$recommendation .= "<br>";
		$recommendation .= $this->getFilesPathInString();

		$result = array(
			"name" => $this->getName(),
			"problem_count" => 1,
			"errors" => array(
				array(
					"title" => GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_TITLE", array("#COUNT#" => $this->filesCount)),
					"critical" => CSecurityCriticalLevel::HIGHT,
					"detail" => GetMessage("SECURITY_SITE_CHECKER_FILE_PERM_DETAIL"),
					"recommendation" => $recommendation
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