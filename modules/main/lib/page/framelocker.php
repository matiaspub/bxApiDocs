<?
namespace Bitrix\Main\Page;

use Bitrix\Main\Application;
use Bitrix\Main\DB\MysqlCommonConnection;

class FrameLocker
{
	const CONNECTION_NAME = "composite";

	/**
	 * Tries to obtain a lock with a name given by the string $id
	 * Supports only Mysql.
	 *
	 * @param string $id Lock Name.
	 * @return bool Returns true if lock was obtained.
	 */
	
	/**
	* <p>Статический метод блокирует область по имени указанному в строке <b>$id</b>. <br> Поддерживает только Mysql.</p>
	*
	*
	* @param string $id  Имя блокировки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framelocker/lock.php
	* @author Bitrix
	*/
	public static function lock($id)
	{
		$result = true;
		$connection = static::getConnection();
		if ($connection instanceof MysqlCommonConnection)
		{
			$lock = $connection->queryScalar(
				sprintf("SELECT GET_LOCK('%s', %d)", md5($id), 0)
			);
			$result = $lock != "0";
		}

		return $result;
	}

	/**
	 * Releases the lock named by the string $id
	 * Supports only Mysql
	 *
	 * @param string $id Lock Name.
	 * @return bool Returns true if lock was released.
	 */
	
	/**
	* <p>Статический метод снимает блокировку области с именем, указанным в <b>$id</b>. <br> Поддерживается только для Mysql.</p>
	*
	*
	* @param string $id  Имя блокировки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framelocker/unlock.php
	* @author Bitrix
	*/
	public static function unlock($id)
	{
		$connection = static::getConnection();
		if ($connection instanceof MysqlCommonConnection)
		{
			$connection->queryExecute(
				sprintf("DO RELEASE_LOCK('%s')", md5($id))
			);
		}

		return true;
	}

	/**
	 * Gets a new connection for the lock mechanism
	 * @return \Bitrix\Main\DB\Connection
	 */
	private static function getConnection()
	{
		$pool = Application::getInstance()->getConnectionPool();
		$connection = $pool->getConnection(static::CONNECTION_NAME);
		if (!$connection)
		{
			$connection = $pool->cloneConnection(
				$pool::DEFAULT_CONNECTION_NAME,
				static::CONNECTION_NAME
			);
		}

		return $connection;
	}

}