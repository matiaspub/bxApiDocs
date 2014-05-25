<?php

class CSecurityTemporaryStorage
{
	const SESSION_DATA_KEY = 'SECURITY_TEMPORARY_STORAGE';
	const DEFAULT_DATA_KEY = 'default';
	protected $sessionData = array();

	public function __construct($sessionKey = '', $forceFlush = false)
	{
		$this->initializeSessionData($sessionKey);
		if($forceFlush)
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
	 * @param string $key
	 */
	public function clearKey($key)
	{
		unset($this->sessionData[$key]);
	}

	/**
	 * @param string $sessionKey
	 * @return bool
	 */
	protected function initializeSessionData($sessionKey = '')
	{
		if(!is_string($sessionKey) || !$sessionKey)
			$sessionKey = self::DEFAULT_DATA_KEY;

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
	 * @param int|string $key
	 * @param mixed $value
	 */
	public function setData($key, $value)
	{
		$this->sessionData[$key] = $value;
	}

	/**
	 * @param int|string $key
	 * @return string
	 */
	public function getString($key)
	{
		if(isset($this->sessionData[$key]) && is_string($this->sessionData[$key]))
		{
			return $this->sessionData[$key];
		}
		else
		{
			return '';
		}
	}

	/**
	 * @param int|string $key
	 * @return int
	 */
	public function getInt($key)
	{
		if(isset($this->sessionData[$key]) && is_numeric($this->sessionData[$key]))
		{
			return $this->sessionData[$key];
		}
		else
		{
			return 0;
		}
	}

	/**
	 * @param int|string $key
	 * @return bool
	 */
	public function getBool($key)
	{
		if(isset($this->sessionData[$key]) && is_bool($this->sessionData[$key]))
		{
			return $this->sessionData[$key];
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param int|string $key
	 * @return bool
	 */
	public function isEmpty($key)
	{
		if(!isset($this->sessionData[$key]) || empty($this->sessionData[$key]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param int|string $key
	 * @return bool
	 */
	public function isExists($key)
	{
		return isset($this->sessionData[$key]);
	}

	/**
	 * @param int|string $key
	 */
	public function increment($key)
	{
		$this->setData($key, $this->getInt($key) + 1);
	}

	/**
	 * @param int|string $key
	 */
	public function decrement($key)
	{
		$this->setData($key, $this->getInt($key) - 1);
	}

	/**
	 * @param int|string $key
	 * @return bool|array
	 */
	public function getArray($key)
	{
		if(isset($this->sessionData[$key]) && is_array($this->sessionData[$key]))
		{
			return $this->sessionData[$key];
		}
		else
		{
			return array();
		}
	}

	/**
	 * @param int|string $key
	 * @return mixed
	 */
	public function getArrayPop($key)
	{
		if(isset($this->sessionData[$key]) && is_array($this->sessionData[$key]))
		{
			return array_pop($this->sessionData[$key]);
		}
		else
		{
			return null;
		}
	}

	/**
	 * @param int|string $key
	 * @param mixed $value
	 */
	public function pushToArray($key, $value)
	{
		if(isset($this->sessionData[$key]) && is_array($this->sessionData[$key]))
		{
			array_push($this->sessionData[$key], $value);
		}
		else
		{
			$this->sessionData[$key] = array($value);
		}
	}
}
