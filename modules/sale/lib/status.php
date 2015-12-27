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
	protected static function getUserGroups($userId)
	{
		global $USER;

		if ($userId == $USER->GetID())
		{
			$groups = $USER->GetUserGroupArray();
		}
		else
		{
			static $cache;

			if (isset($cache[$userId]))
			{
				$groups = $cache[$userId];
			}
			else
			{
				// TODO: DATE_ACTIVE_FROM >=< DATE_ACTIVE_TO
				$result = UserGroupTable::getList(array('select' => array('GROUP_ID'), 'filter' => array('USER_ID' => $userId)));

				$groups = array();
				while ($row = $result->fetch())
					$groups []= $row['GROUP_ID'];

				$cache[$userId] = $groups;
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
	static function canUserDoOperations($userId, $fromStatus, array $operations)
	{
		return self::canGroupDoOperations(self::getUserGroups($userId), $fromStatus, $operations);
	}
	static function canGroupDoOperations($groupId, $fromStatus, array $operations)
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
	static function getAllowedUserStatuses($userId, $fromStatus)
	{
		return self::getAllowedGroupStatuses(self::getUserGroups($userId), $fromStatus);
	}
	static function getAllowedGroupStatuses($groupId, $fromStatus)
	{
		$statuses = array();

		if (! is_array($groupId))
			$groupId = array($groupId);

		if (in_array('1', $groupId, true) || \CMain::GetUserRight('sale', $groupId) >= 'W') // Admin
		{
			$result = StatusTable::getList(array(
				'select' => array('ID', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
				'filter' => array('=TYPE' => static::TYPE, '=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID),
				'order'  => array('SORT'),
			));

			while ($row = $result->fetch())
				$statuses[$row['ID']] = $row['NAME'];
		}
		elseif (StatusTable::getList(array( // check if group can change from status
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

	/** Get all statuses for current class type.
	 * @return array - statuses ids
	 */
	static function getAllStatuses()
	{
		if (empty(static::$allStatuses))
		{
			$result = StatusTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=TYPE' => static::TYPE),
			));
			while ($row = $result->fetch())
				static::$allStatuses[] = $row['ID'];
		}

		return static::$allStatuses;
	}

	/** Get statuses user can do operations within
	 * @param integer $userId - user id
	 * @param array $operations - array of operation names
	 * @return array - statuses ids
	 */
	static function getStatusesUserCanDoOperations($userId, array $operations)
	{
		return self::getStatusesGroupCanDoOperations(self::getUserGroups($userId), $operations);
	}
	static function getStatusesGroupCanDoOperations($groupId, array $operations)
	{
		$statuses = array();

		if (! is_array($groupId))
			$groupId = array($groupId);

		if (in_array('1', $groupId, true) || \CMain::GetUserRight('sale', $groupId) >= 'W') // Admin
		{
			$statuses = self::getAllStatuses();
		}
		else
		{
			$operations = self::convertNamesToOperations($operations);

			$result = StatusTable::getList(array(
				'select' => array(
					'ID',
					'OPERATION' => 'Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME',
				),
				'filter' => array(
					'=TYPE' => static::TYPE,
					'=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID' => $groupId,
					'=Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK.Bitrix\Main\TaskOperation:TASK.OPERATION.NAME' => $operations,
				),
				'order'  => array('SORT'),
			));

			while ($row = $result->fetch())
			{
				if ($status = &$statuses[$row['ID']])
					$status []= $row['OPERATION'];
				else
					$status = array($row['OPERATION']);
			}

			unset($status);

			foreach ($statuses as $id => $ops)
				if (array_diff($operations, $ops))
					unset($statuses[$id]);

			$statuses = array_keys($statuses);
		}

		return $statuses;
	}

	static function getInitialStatus()
	{
		return reset(static::$initial);
	}

	static function isInitialStatus($status)
	{
		return in_array($status, static::$initial, true);
	}

	static function getFinalStatus()
	{
		return reset(static::$final);
	}

	static function isFinalStatus($status)
	{
		return in_array($status, static::$final, true);
	}

	/*
	 *
	 */
	static function install(array $data)
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
}

class DeliveryStatus extends StatusBase
{
	const TYPE = 'D';
	protected static $initial = array('DN');
	protected static $final = array('DF');
	protected static $allStatuses = array();
}
