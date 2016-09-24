<?php
namespace Bitrix\Security;


use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/*
CREATE TABLE b_sec_session
(
	SESSION_ID VARCHAR(250) NOT NULL,
	TIMESTAMP_X TIMESTAMP NOT NULL,
	SESSION_DATA LONGTEXT,
	PRIMARY KEY(SESSION_ID)
);
 */

/**
 * Class SessionTable
 * @since 16.0.0
 */
class SessionTable
	extends Entity\DataManager
{
	/** @var string Connection name used for SQL queries */
	const CONNECTION_NAME = 'user_session';

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает имя таблицы в базе данных, соответствующей сущности.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_sec_session';
	}

	/**
	 * Returns connection name for entity
	 * Have side affect, keep it in mind!
	 * Clone default database connection if connection {@link SessionTable::CONNECTION_NAME} doesn't exists
	 *
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает имя соединения, соответствующего данной сущности.</p> <p></p> <div class="note"> <b>Примечание</b>: копирует текущее соединение с базой данных, если соединение <b>{@link SessionTable::CONNECTION_NAME}</b> не существует.</div> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/getconnectionname.php
	* @author Bitrix
	*/
	public static function getConnectionName()
	{
		$pool = \Bitrix\Main\Application::getInstance()->getConnectionPool();
		$isConnectionExists = $pool->getConnection(static::CONNECTION_NAME) !== null;
		if (!$isConnectionExists)
		{
			$pool->cloneConnection(
				$pool::DEFAULT_CONNECTION_NAME,
				static::CONNECTION_NAME
			);
		}
		return static::CONNECTION_NAME;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает список полей с типами.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			new Entity\StringField('SESSION_ID', array(
				'primary' => true,
				'format' => '#^[0-9a-z\-,]{6,250}$#iD'
			)),
			new Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Type\DateTime
			)),
			new Entity\TextField('SESSION_DATA', array(
				'default_value' => '',
				'save_data_modification' => function()
				{
					return array(function($data)
					{
						return base64_encode($data);
					});
				},
				'fetch_data_modification' => function()
				{
					return array(function($data)
					{
						return base64_decode($data);
					});
				},
			))
		);
	}

	/**
	 * Locks specified session id
	 * Supports Mysql, MSSQL and Oracle so far.
	 *
	 * @param string $id Session id.
	 * @param int $timeout Lock timeout.
	 * @return bool Returns true if lock occurred.
	 */
	
	/**
	* <p>Статический метод блокирует определенный идентификатор сессии. Пока поддерживаются <i>Mysql</i>, <i>MSSQL</i> and <i>Oracle</i>.</p>
	*
	*
	* @param string $id  Идентификатор сессии.
	*
	* @param integer $timeout = 60 Время блокировки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/lock.php
	* @author Bitrix
	*/
	public static function lock($id, $timeout = 60)
	{
		$result = true;
		$connection = static::getEntity()->getConnection();
		if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$lock = $connection->queryScalar(
				sprintf('SELECT GET_LOCK("%s", %d)', md5($id), (int) $timeout)
			);
			$result = $lock != '0';
		}
		elseif ($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$id = $connection->getSqlHelper()->forSql($id);
			$timeout = (int) $timeout;
			$connection->startTransaction();
			try
			{
				$connection->queryExecute(sprintf('SET LOCK_TIMEOUT %d', $timeout * 1000));
				$queryResult = static::update($id, array(
					'TIMESTAMP_X' => new Type\DateTime
				));
				if ($queryResult->isSuccess())
				{
					if ($queryResult->getAffectedRowsCount())
					{
						$result = true;
					}
					else
					{
						$queryResult = static::add(array(
							'SESSION_ID' => $id
						));
						$result = $queryResult->isSuccess();
					}
				}
				else
				{
					$result = false;
				}
			}
			catch (\Bitrix\Main\DB\SqlQueryException $e)
			{
				$result = false;
			}
		}
		elseif ($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			$id = $connection->getSqlHelper()->forSql($id);
			$timeout = (int) $timeout;
			$connection->startTransaction();
			$result = null;
			while ($result === null)
			{
				try
				{
					$lock = $connection->query("
						select *
						from b_sec_session
							where SESSION_ID = '${id}'
						for update wait ${timeout}
					");

					if ($lock->fetch())
					{
						$result = true;
					}
					else
					{
						static::add(array(
							'SESSION_ID' => $id
						));
					}
				}
				catch (\Bitrix\Main\DB\SqlQueryException $e)
				{
					$result = false;
				}
			}
		}
		else
		{
			trigger_error(sprintf('SessionTable::lock not supported for connection of type "%s"', get_class($connection)), E_USER_WARNING);
		}

		return $result;
	}

	/**
	 * Unlock specified session id
	 * Supports Mysql, MSSQL and Oracle so far.
	 *
	 * @param string $id Session id.
	 * @return bool Returns true if lock released.
	 */
	
	/**
	* <p>Статический метод разблокирует определенный идентификатор сессии. Пока что поддерживается <i>Mysql</i>, <i>MSSQL</i> and <i>Oracle</i>.</p>
	*
	*
	* @param string $id  Идентификатор сессии.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/unlock.php
	* @author Bitrix
	*/
	public static function unlock($id)
	{
		$connection = static::getEntity()->getConnection();
		if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				sprintf('DO RELEASE_LOCK("%s")', md5($id))
			);
		}
		elseif ($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$connection->queryExecute("SET LOCK_TIMEOUT -1");
			$connection->commitTransaction();
		}
		elseif ($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			$connection->commitTransaction();
		}
		else
		{
			trigger_error(sprintf('SessionTable::unlock not supported for connection of type "%s"', get_class($connection)), E_USER_WARNING);
		}

		return true;
	}

	/**
	 * Deletes old sessions
	 *
	 * @param int $sec Seconds.
	 * @return void
	 */
	
	/**
	* <p>Статический метод удаляет старые сессии.</p>
	*
	*
	* @param integer $sec  Секунды.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/sessiontable/deleteolderthan.php
	* @author Bitrix
	*/
	public static function deleteOlderThan($sec)
	{
		$connection = static::getEntity()->getConnection();
		$connection->queryExecute(
			sprintf('delete from b_sec_session where TIMESTAMP_X < %s',
				$connection->getSqlHelper()->addSecondsToDateTime('-'.$sec))
		);
	}
}
