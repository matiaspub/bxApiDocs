<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

class CSecuritySessionVirtual
{
	/**
	 * @return bool
	 */
	public static function isStorageEnabled()
	{
		return defined("BX_SECURITY_SESSION_VIRTUAL") && BX_SECURITY_SESSION_VIRTUAL == true;
	}

	/**
	 * @return bool
	 */
	public static function Init()
	{
		return true;
	}

	/**
	 * @param string $savePath - unused on this handler
	 * @param string $sessionName - unused on this handler
	 * @return bool
	 */
	public static function open($savePath, $sessionName)
	{
		return true;
	}

	public static function close()
	{
		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return string
	 */
	public static function read($id)
	{
		return "";
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @param array $sessionData
	 * @return bool
	 */
	public static function write($id, $sessionData)
	{
		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return bool
	 */
	public static function destroy($id)
	{
		return true;
	}

	/**
	 * @param int $maxLifeTime - unused on this handler
	 */
	public static function gc($maxLifeTime)
	{
	}
}
