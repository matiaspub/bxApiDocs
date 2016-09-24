<?

use Bitrix\Security\SessionTable;

class CSecuritySessionDB
{
	protected static $isReadOnly = false;
	protected static $sessionId = null;

	/**
	 * @return bool
	 */
	public static function Init()
	{
		self::$isReadOnly = defined('BX_SECURITY_SESSION_READONLY');
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

	/**
	 * @return bool
	 */
	public static function close()
	{
		if (!self::$isReadOnly && static::isValidId(static::$sessionId))
		{
			SessionTable::unlock(static::$sessionId);
		}


		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return string
	 */
	public static function read($id)
	{
		if (!self::isValidId($id))
			return "";

		if (!self::$isReadOnly && !SessionTable::lock($id, 60/*TODO: timelimit from php.ini?*/))
			CSecuritySession::triggerFatalError('Unable to get session lock within 60 seconds.');

		self::$sessionId = $id;
		$sessionRow = SessionTable::getRow(array(
			'select' => array('SESSION_DATA'),
			'filter' => array('=SESSION_ID' => $id)
		));

		if ($sessionRow && isset($sessionRow['SESSION_DATA']))
		{
			return base64_decode($sessionRow['SESSION_DATA']);
		}

		return '';
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @param string $sessionData
	 * @return bool
	 */
	public static function write($id, $sessionData)
	{
		if(!self::isValidId($id))
			return false;

		if (self::$isReadOnly)
		{
			if (!CSecuritySession::isOldSessionIdExist())
			{
				return true;
			}
		}

		if(CSecuritySession::isOldSessionIdExist())
			$oldSessionId = CSecuritySession::getOldSessionId(true);
		else
			$oldSessionId = $id;

		SessionTable::delete($oldSessionId);
		$result = SessionTable::add(array(
			'SESSION_ID' => $id,
			'TIMESTAMP_X' => new Bitrix\Main\Type\DateTime,
			'SESSION_DATA' => base64_encode($sessionData),
		));

		return $result->isSuccess();
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return bool
	 */
	public static function destroy($id)
	{
		if(!self::isValidId($id))
			return false;

		if (self::$isReadOnly)
			return false;

		SessionTable::delete($id);

		if(CSecuritySession::isOldSessionIdExist())
			SessionTable::delete(CSecuritySession::getOldSessionId(true));

		return true;
	}

	/**
	 * @param int $maxLifeTime
	 * @return bool
	 */
	public static function gc($maxLifeTime)
	{
		SessionTable::deleteOlderThan($maxLifeTime);
		return true;
	}

	/**
	 * @param string $pId
	 * @return bool
	 */
	protected static function isValidId($pId)
	{
		return (
			$pId
			&& is_string($pId)
			&& preg_match('/^[\da-z\-,]{6,}$/iD', $pId)
		);
	}

}
