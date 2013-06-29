<?php

abstract class CSecurityBaseTest
{
	protected $internalName = "BaseTest";
	protected $isDebug = false;
	protected $params = array();
	protected $tests = array();
	protected $detailErrors = array();

	/**
	 * Return test name
	 * @return string
	 */
	public function getName()
	{
		return GetMessage("SECURITY_SITE_CHECKER_".$this->getInternalName()."_NAME");
	}

	/**
	 * Run test and return results
	 * @param array $pParams
	 * @return array
	 */
	public function check($pParams = array())
	{
		$this->initializeParams($pParams);
		$isSomethingFound = false;
		$neededTests = self::getParam("folder", array());
		if(is_string($neededTests) && $neededTests != "")
		{
			$neededTests = array($neededTests);
		}

		if(is_callable("ini_get") && is_callable("call_user_func_array"))
		{
			foreach($this->tests as $name => $test)
			{
				if(!empty($pNeededTests) && !in_array($name, $neededTests, true))
					continue;

				if(isset($test["params"]) && is_array($test["params"]))
				{
					$params = $test["params"];
				}
				else
				{
					$params = array();
				}

				if(!call_user_func_array(array($this, $test["method"]), $params))
				{
					if(isset($test["base_message_key"]) && $test["base_message_key"] != "")
					{
						if(isset($test["critical"]) && $test["critical"] != "")
						{
							$critical = $test["critical"];
						}
						else
						{
							$critical = CSecurityCriticalLevel::LOW;
						}

						$this->addUnformattedDetailError($test["base_message_key"], $critical);
					}
					$isSomethingFound = true;
				}
			}
		}


		$result = array(
			'name' => $this->getName(),
			'problem_count' => count($this->getDetailErrors()),
			'errors' => $this->getDetailErrors(),
			'status' => !$isSomethingFound
		);

		return $result;
	}

	/**
	 * Return internal name (for technical usage)
	 * @return string
	 */
	public function getInternalName()
	{
		return $this->internalName;
	}

	/**
	 * Initialize starting params, e.g. debug mode
	 * @param array $pParams
	 */
	protected function initializeParams(array $pParams = array())
	{
		if(is_array($pParams) && !empty($pParams))
		{
			$this->params = $pParams;
		}
		$this->isDebug = (self::getParam("debug", false) === true);
	}

	/**
	 * @return bool
	 */
	protected function isRunOnWin()
	{
		return (strtoupper(substr(PHP_OS, 0, 3)) === "WIN");
	}

	/**
	 * Return file or dir permissions
	 * @param string $pFile - file path
	 * @return int
	 */
	protected static function getFilePerm($pFile)
	{
		if(!(is_dir($pFile) || is_file($pFile)))
			return false;

		return fileperms($pFile);
	}

	/**
	 * Check file or dir for write permissions
	 * @param string $pFile - file path
	 * @return bool
	 */
	protected static function isWorldWritable($pFile)
	{
		return (self::getFilePerm($pFile) & 0x0002) > 0;
	}

	/**
	 * Check file or dir for read permissions
	 * @param string $pFile - file path
	 * @return bool
	 */
	protected static function isWorldReadable($pFile)
	{
		return (self::getFilePerm($pFile) & 0x0004) > 0;
	}

	/**
	 * Check file or dir for read or write permissions
	 * @param string $pFile - file path
	 * @return bool
	 */
	protected static function isWorldAccessible($pFile)
	{
		$perms = self::getFilePerm($pFile);
		return ($perms & 0x0004 > 0) || ($perms & 0x0002 > 0);
	}

	/**
	 * Return param value, or default value if not present
	 * @param string $pParamName
	 * @param string $pDefaultValue
	 * @return string
	 */
	protected function getParam($pParamName, $pDefaultValue = "")
	{
		if(isset($this->params[$pParamName]))
		{
			return $this->params[$pParamName];
		}
		else
		{
			return $pDefaultValue;
		}
	}

	/**
	 * @return bool
	 */
	protected function isDebug()
	{
		return $this->isDebug;
	}

	/**
	 * Return errors array for checking results
	 * @return array
	 */
	protected function getDetailErrors()
	{
		return $this->detailErrors;
	}

	/**
	 * @param string $pBaseMessageKey
	 * @return string
	 */
	protected static function getDetailText($pBaseMessageKey)
	{
		if(HasMessage($pBaseMessageKey."_DETAIL"))
		{
			$result = GetMessage($pBaseMessageKey."_DETAIL");
		}
		else
		{
			$result = "";
		}
		return $result;
	}

	/**
	 * @param string $pBaseMessageKey
	 * @return string
	 */
	protected static function getRecommendationText($pBaseMessageKey)
	{
		if(HasMessage($pBaseMessageKey."_RECOMMENDATION"))
		{
			$result = GetMessage($pBaseMessageKey."_RECOMMENDATION");
		}
		else
		{
			$result = "";
		}
		return $result;
	}

	/**
	 * @param string $pBaseMessageKey
	 * @return string
	 */
	protected static function getTitleText($pBaseMessageKey)
	{
		if(HasMessage($pBaseMessageKey))
		{
			$result = GetMessage($pBaseMessageKey);
		}
		else
		{
			$result = "";
		}
		return $result;
	}

	/**
	 * Add new error
	 * @param string $pTitle
	 * @param string $pCritical
	 * @param string $pDetail
	 * @param string $pRecommendation
	 */
	protected function addDetailError($pTitle, $pCritical, $pDetail, $pRecommendation = "")
	{
		$detailError = array(
			"title" => $pTitle,
			"critical" => $pCritical,
			"detail" => $pDetail,
			"recommendation" => $pRecommendation
		);
		$this->pushDetailError($detailError);
	}

	/**
	 * @param array $pDetailError
	 */
	private function pushDetailError($pDetailError = array())
	{
		if(is_array($pDetailError) && !empty($pDetailError))
		{
			array_push($this->detailErrors, $pDetailError);
		}
	}

	/**
	 * Add new unformatted error (call formatDetailError inside)
	 * @param string $pBaseMessageKey
	 * @param string $pCritical
	 */
	protected function addUnformattedDetailError($pBaseMessageKey, $pCritical)
	{
		$detailError = self::formatDetailError($pBaseMessageKey, $pCritical);
		$this->pushDetailError($detailError);
	}

	/**
	 * Return formatted detail error from messages
	 * @param string $pBaseMessageKey
	 * @param string $pCritical
	 * @return array
	 */
	protected static function formatDetailError($pBaseMessageKey, $pCritical)
	{
		return array(
				"title" => self::getTitleText($pBaseMessageKey),
				"critical" => $pCritical,
				"detail" => self::getDetailText($pBaseMessageKey),
				"recommendation" => self::getRecommendationText($pBaseMessageKey)
			);
	}

	/**
	 * Return path without $_SERVER['DOCUMENT_ROOT']
	 * @param string $pPath
	 * @return string
	 */
	protected static function removeDocumentRoot($pPath)
	{
		$path = removeDocRoot($pPath);
		return $path;
	}
}
