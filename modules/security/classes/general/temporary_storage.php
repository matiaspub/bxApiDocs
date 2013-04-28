<?php

class CSecurityTemporaryStorage
{
	const SESSION_DATA_KEY = "SECURITY_SITE_CHECKER";
	const DEFAULT_DATA_KEY = "default";
	protected $sessionData = array();

	static public function __construct($pSessionKey = "", $pForceFlush = false)
	{
		$this->initializeSessionData($pSessionKey);
		if($pForceFlush)
		{
			$this->flushData();
		}
	}

	/**
	 *
	 */
	public static function clearAll()
	{
		unset($_SESSION[CSecuritySiteChecker::SESSION_DATA_KEY]);
	}

	static public function clearKey($pKey)
	{
		unset($this->sessionData[$pKey]);
	}
	/**
	 * @param string $pSessionKey
	 * @return bool
	 */
	protected function initializeSessionData($pSessionKey = "")
	{
		if(is_string($pSessionKey) && $pSessionKey != "")
		{
			$sessionKey = $pSessionKey;
		}
		else
		{
			$sessionKey = self::DEFAULT_DATA_KEY;
		}
		$this->sessionData = &$_SESSION[CSecuritySiteChecker::SESSION_DATA_KEY][$sessionKey];
		if(!is_array($this->sessionData))
		{
			$this->sessionData = array();
		}
		return true;
	}

	/**
	 *
	 */
	static public function flushData()
	{
		$this->sessionData = array();
	}

	/**
	 * @param $pKey
	 * @param $pValue
	 */
	static public function setData($pKey, $pValue)
	{
		$this->sessionData[$pKey] = $pValue;
	}

	/**
	 * @param $pKey
	 * @return string
	 */
	static public function getString($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_string($this->sessionData[$pKey]))
		{
			return $this->sessionData[$pKey];
		}
		else
		{
			return "";
		}
	}

	/**
	 * @param $pKey
	 * @return int
	 */
	static public function getInt($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_numeric($this->sessionData[$pKey]))
		{
			return $this->sessionData[$pKey];
		}
		else
		{
			return 0;
		}
	}

	/**
	 * @param $pKey
	 * @return bool
	 */
	static public function getBool($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_bool($this->sessionData[$pKey]))
		{
			return $this->sessionData[$pKey];
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $pKey
	 * @return bool
	 */
	static public function isEmpty($pKey)
	{
		if(!isset($this->sessionData[$pKey]) || empty($this->sessionData[$pKey]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $pKey
	 * @return bool
	 */
	static public function isExists($pKey)
	{
		return isset($this->sessionData[$pKey]);
	}

	/**
	 * @param $pKey
	 */
	static public function increment($pKey)
	{
		$this->setData($pKey, $this->getInt($pKey) + 1);
	}

	/**
	 * @param $pKey
	 */
	static public function decrement($pKey)
	{
		$this->setData($pKey, $this->getInt($pKey) - 1);
	}

	/**
	 * @param $pKey
	 * @return bool|array
	 */
	static public function getArray($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_array($this->sessionData[$pKey]))
		{
			return $this->sessionData[$pKey];
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $pKey
	 * @return bool|mixed
	 */
	static public function getArrayPop($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_array($this->sessionData[$pKey]))
		{
			return array_pop($this->sessionData[$pKey]);
		}
		else
		{
			return false;
		}
	}
}
