<?

namespace Bitrix\Sale;

use	Bitrix\Sale\Internals\StatusTable,
	Bitrix\Sale\Internals\StatusLangTable,
	Bitrix\Main\UserGroupTable,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class StatusBase
{
	/**
	 * @param $userId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getUserGroups($userId)
	{
		global $USER;

		if ($userId == $USER->GetID())
		{
			$groups = $USER->GetUserGroupArray();
		}
		else
		{
			static $cacheGroups;

			if (isset($cacheGroups[$userId]))
			{
				$groups = $cacheGroups[$userId];
			}
			else
			{
				// TODO: DATE_ACTIVE_FROM >=< DATE_ACTIVE_TO
				$result = UserGroupTable::getList(array('select' => array('GROUP_ID'), 'filter' => array('USER_ID' => $userId)));

				$groups = array();
				while ($row = $result->fetch())
					$groups []= $row['GROUP_ID'];

				$cacheGroups[$userId] = $groups;
			}
		}

		return $groups;
	}

	/** Check if user can do certain operations
	 * @param string|integer $userId     - user id
	 * @param string         $fromStatus - current status
	 * @param array          $operations - eg: array('update', 'cancel')
	 * @return bool             - true if user allowed to do all the operations, false otherwise
	 * @throws SystemException  - if no operations provided
	 */
	
	/**
	* <p>Проверяет, может ли пользователь выполнять определенные операции. Метод статический.</p>
	*
	*
	* @param mixed $string  Идентификатор пользователя.
	*
	* @param integer $userId  Текущий статус.
	*
	* @param string $fromStatus  Список операций. Массив вида: <code>array('update', 'cancel').</code>
	*
	* @param array $operations  
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/statusbase/canuserdooperations.php
	* @author Bitrix
	*/
	public static function canUserDoOperations($userId, $fromStatus, array $operations)
	{
		return self::canGroupDoOperations(self::getUserGroups($userId), $fromStatus, $operations);
	}

	/**
	 * @param $groupId
	 * @param $fromStatus
	 * @param array $operations
	 *
	 * @return bool
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function canGroupDoOperations($groupId, $fromStatus, array $operations)
	{
		if (! $operations)
			throw new SystemException('provide at least one operation', 0, __FILE__, __LINE__);

		if (! is_array($groupId))
			$groupId = array($groupId);

		if (in_array('1', $groupId, true) || \CMain::GetUserRight('sale', $groupId) >= 'W') // Admin
			return true;

		$operations = self::convertNamesToOperations($operations);

		unset($operation);

		$result = StatusTable::getList(array(
			'select' => array(
				'NAME' => 'Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME',
			),
			'filter' => array(
				'=ID' => $fromStatus,
				'=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID' => $groupId,
				'=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME' => $operations,
			),
		));

		while ($row = $result->fetch())
			if (($key = array_search($row['NAME'], $operations)) !== false)
				unset($operations[$key]);

		return ! $operations;
	}

	/** Get statuses that user can switch to.
	 * @param integer|string $userId    - user id
	 * @param string         $fromStatus - current status
	 * @return array - ["status ID"] => "status localized NAME"
	 */
	
	/**
	* <p>Возвращает статусы, на которые пользователь может изменить свой текущий статус. Метод статический.</p>
	*
	*
	* @param mixed $integer  Идентификатор пользователя.
	*
	* @param string $userId  Текущий статус пользователя.
	*
	* @param string $fromStatus  
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/statusbase/getalloweduserstatuses.php
	* @author Bitrix
	*/
	public static function getAllowedUserStatuses($userId, $fromStatus)
	{
		return self::getAllowedGroupStatuses(self::getUserGroups($userId), $fromStatus);
	}

	/**
	 * @param $groupId
	 * @param $fromStatus
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getAllowedGroupStatuses($groupId, $fromStatus)
	{
		$statuses = array();

		if (! is_array($groupId))
			$groupId = array($groupId);

		$cacheKey = md5($groupId."_".(is_array($fromStatus) ? join('|', $fromStatus) : $fromStatus));

		if (in_array('1', $groupId, true) || \CMain::GetUserRight('sale', $groupId) >= 'W') // Admin
		{
			if (!array_key_exists($cacheKey, static::$cacheAllowStatuses))
			{
				$result = StatusTable::getList(array(
					'select' => array('ID', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
					'filter' => array('=TYPE' => static::TYPE, '=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID),
					'order'  => array('SORT'),
				));

				while ($row = $result->fetch())
					$statuses[$row['ID']] = $row['NAME'];

				static::$cacheAllowStatuses[$cacheKey] = $statuses;
			}
			else
			{
				$statuses = static::$cacheAllowStatuses[$cacheKey];
			}
		}
		else
		{
			if (!array_key_exists($cacheKey, static::$cacheAllowStatuses))
			{
				if (StatusTable::getList(array( // check if group can change from status
											 'select' => array('ID'),
											 'filter' => array(
												 '=ID' => $fromStatus,
												 '=TYPE' => static::TYPE,
												 '=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID' => $groupId,
												 '=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME' => 'sale_status_from',
											 ),
											 'limit' => 1,
										 ))->fetch())
				{
					$result = StatusTable::getList(array(
													   'select' => array('ID', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
													   'filter' => array(
														   '=TYPE' => static::TYPE,
														   '=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID,
														   '=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID' => $groupId,
														   '=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME' => 'sale_status_to',
													   ),
													   'order' => array('SORT'),
												   ));

					while ($row = $result->fetch())
						$statuses[$row['ID']] = $row['NAME'];

					static::$cacheAllowStatuses[$cacheKey] = $statuses;
				}
				else
				{
					static::$cacheAllowStatuses[$cacheKey] = array();
				}
			}
			else
			{
				$statuses = static::$cacheAllowStatuses[$cacheKey];
			}
		}

		return $statuses;
	}

	private static function convertNamesToOperations($names)
	{
		$operations = array();

		foreach ($names as $name)
		{
			$operations []= 'sale_status_'.strtolower($name);
		}

		return $operations;
	}

	/**
	 * Get all statuses for current class type.
	 *
	 * @param bool $withName
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 */
	
	/**
	* <p>Базовый метод для доставок и заказов. Возвращает список статусов, в зависимости от класса-наследника. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/statusbase/getallstatuses.php
	* @author Bitrix
	*/
	public static function getAllStatuses($withName = false)
	{
		if (empty(static::$allStatuses))
		{
			$result = StatusTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=TYPE' => static::TYPE),
				'order'  => array('SORT')
			));
			while ($row = $result->fetch())
				static::$allStatuses[] = $row['ID'];
		}

		return static::$allStatuses;
	}


	/**
	 * Get all statuses names for current class type.
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getAllStatusesNames()
	{
		if (empty(static::$allStatusesNames))
		{
			$result = StatusTable::getList(array(
											   'select' => array("ID", "NAME" => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
											   'filter' => array('=TYPE' => static::TYPE),
										   ));
			while ($row = $result->fetch())
				static::$allStatusesNames[$row['ID']] = $row['NAME'];

			if (empty(static::$allStatuses))
				static::$allStatuses = array_keys(static::$allStatusesNames);
		}

		return static::$allStatusesNames;
	}


	/** Get statuses user can do operations within
	 * @param integer $userId - user id
	 * @param array $operations - array of operation names
	 * @return array - statuses ids
	 */
	
	/**
	* <p>Метод возвращает список операций, которые может выполнять пользователь. Метод статический.</p>
	*
	*
	* @param integer $userId  Идентификатор пользователя.
	*
	* @param array $operations  Массив названий операций.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/statusbase/getstatusesusercandooperations.php
	* @author Bitrix
	*/
	public static function getStatusesUserCanDoOperations($userId, array $operations)
	{
		return self::getStatusesGroupCanDoOperations(self::getUserGroups($userId), $operations);
	}

	/**
	 * @param $groupId
	 * @param array $operations
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getStatusesGroupCanDoOperations($groupId, array $operations)
	{
		$statuses = array();

		static $cacheStatuses = array();

		if (! is_array($groupId))
			$groupId = array($groupId);

		$cacheHash = md5(static::TYPE."|".join($groupId, '_')."|".join($operations, '_'));

		if (!empty($cacheStatuses[$cacheHash]))
		{
			return $cacheStatuses[$cacheHash];
		}
		else
		{
			if (in_array('1', $groupId, true) || \CMain::GetUserRight('sale', $groupId) >= 'W') // Admin
			{
				$statuses = self::getAllStatuses();
			}
			else
			{
				$statusesList = static::getStatusesByGroupId($groupId, $operations);
				if (!empty($statusesList) && is_array($statusesList))
				{
					$statuses = array_keys($statusesList);
				}

			}

			$cacheStatuses[$cacheHash] = $statuses;
		}

		return $statuses;
	}

	/**
	 * @param $userId
	 * @param array $operations
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getStatusesByUserId($userId, array $operations = array())
	{
		return static::getStatusesByGroupId(static::getUserGroups($userId), $operations);
	}

	/**
	 * @param $groupId
	 * @param array $operations
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getStatusesByGroupId($groupId, array $operations = array())
	{
		if (! is_array($groupId))
			$groupId = array($groupId);

		$operations = self::convertNamesToOperations($operations);

		$filter = array(
			'select' => array(
				'ID',
				'OPERATION' => 'Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME',
			),
			'filter' => array(
				'=TYPE' => static::TYPE,
				'=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID' => $groupId,
			),
			'order'  => array('SORT'),
		);


		if (!empty($operations))
		{
			$filter['filter']['=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME'] = $operations;
		}

		return static::getStatusListByFilter($filter);
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getStatusListByFilter(array $filter)
	{
		$statuses = array();
		$result = StatusTable::getList($filter);
		while ($row = $result->fetch())
		{
			if ($status = &$statuses[$row['ID']])
				$status []= $row['OPERATION'];
			else
				$status = array($row['OPERATION']);
		}
		unset($status);

		foreach ($statuses as $statusId => $operationsList)
		{
			if (!empty($operations) && array_diff($operations, $operationsList))
			{
				unset($statuses[$statusId]);
			}
			else
			{
				foreach ($operationsList as $key => $operationData)
				{
					if (strval($operationData) === '')
					{
						unset($statuses[$statusId][$key]);
					}
				}

				if (empty($statuses[$statusId]))
				{
					unset($statuses[$statusId]);
				}
			}
		}

		return $statuses;
	}

	/**
	 * @return mixed
	 */
	public static function getInitialStatus()
	{
		return reset(static::$initial);
	}

	public static function isInitialStatus($status)
	{
		return in_array($status, static::$initial, true);
	}

	public static function getFinalStatus()
	{
		return reset(static::$final);
	}

	public static function isFinalStatus($status)
	{
		return in_array($status, static::$final, true);
	}

	public static function install(array $data)
	{
		if (! ($statusId = $data['ID']) || ! is_string($statusId))
		{
			throw new SystemException('invalid status ID', 0, __FILE__, __LINE__);
		}

		if ($languages = $data['LANG'])
		{
			unset($data['LANG']);

			if (! is_array($languages))
				throw new SystemException('invalid status LANG', 0, __FILE__, __LINE__);
		}

		$data['TYPE'] = static::TYPE;

		// install status if it is not installed

		if (! StatusTable::getById($statusId)->fetch())
		{
			StatusTable::add($data);
		}

		// install status languages if they are not installed

		if ($languages)
		{
			$installedLanguages = array();

			$result = StatusLangTable::getList(array(
				'select' => array('LID'),
				'filter' => array('=STATUS_ID' => $statusId),
			));

			while ($row = $result->fetch())
			{
				$installedLanguages[$row['LID']] = true;
			}

			foreach ($languages as $language)
			{
				if (! is_array($language))
					throw new SystemException('invalid status language', 0, __FILE__, __LINE__);

				if (! $installedLanguages[$language['LID']])
				{
					$language['STATUS_ID'] = $statusId;

					StatusLangTable::add($language);
				}
			}
		}
	}
}

class OrderStatus extends StatusBase
{
	const TYPE = 'O';
	protected static $initial = array('N');
	protected static $final = array('F');
	protected static $allStatuses = array();
	protected static $allStatusesNames = array();
	protected static $cacheAllowStatuses = array();
}

class DeliveryStatus extends StatusBase
{
	const TYPE = 'D';
	protected static $initial = array('DN');
	protected static $final = array('DF');
	protected static $allStatuses = array();
	protected static $allStatusesNames = array();
	protected static $cacheAllowStatuses = array();
}
