<?php

class CSecurityTemporaryStorage
{
	const SESSION_DATA_KEY = "SECURITY_TEMPORARY_STORAGE";
	const DEFAULT_DATA_KEY = "default";
	protected $sessionData = array();

	public function __construct($pSessionKey = "", $pForceFlush = false)
	{
		$this->initializeSessionData($pSessionKey);
		if($pForceFlush)
		{
			$this->flushData();
		}
	}

	/*
	 * Destroy data in all temporary storage
	 */
	public static function clearAll()
	{
		unset($_SESSION[self::SESSION_DATA_KEY]);
	}

	/**
	 * @param string $pKey
	 */
	public function clearKey($pKey)
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
		$this->sessionData = &$_SESSION[self::SESSION_DATA_KEY][$sessionKey];
		if(!is_array($this->sessionData))
		{
			$this->sessionData = array();
		}
		return true;
	}

	public function flushData()
	{
		$this->sessionData = array();
	}

	/**
	 * @param int|string $pKey
	 * @param mixed $pValue
	 */
	public function setData($pKey, $pValue)
	{
		$this->sessionData[$pKey] = $pValue;
	}

	/**
	 * @param int|string $pKey
	 * @return string
	 */
	public function getString($pKey)
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
	 * @param int|string $pKey
	 * @return int
	 */
	public function getInt($pKey)
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
	 * @param int|string $pKey
	 * @return bool
	 */
	public function getBool($pKey)
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
	 * @param int|string $pKey
	 * @return bool
	 */
	public function isEmpty($pKey)
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
	 * @param int|string $pKey
	 * @return bool
	 */
	public function isExists($pKey)
	{
		return isset($this->sessionData[$pKey]);
	}

	/**
	 * @param int|string $pKey
	 */
	public function increment($pKey)
	{
		$this->setData($pKey, $this->getInt($pKey) + 1);
	}

	/**
	 * @param int|string $pKey
	 */
	public function decrement($pKey)
	{
		$this->setData($pKey, $this->getInt($pKey) - 1);
	}

	/**
	 * @param int|string $pKey
	 * @return bool|array
	 */
	public function getArray($pKey)
	{
		if(isset($this->sessionData[$pKey]) && is_array($this->sessionData[$pKey]))
		{
			return $this->sessionData[$pKey];
		}
		else
		{
			return array();
		}
	}

	/**
	 * @param int|string $pKey
	 * @return bool|mixed
	 */
	public function getArrayPop($pKey)
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
