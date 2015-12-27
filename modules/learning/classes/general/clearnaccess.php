<?php

interface ILearnAccessInterface
{
	/**
	 * @param int operations code (taken from sum of constants with prefix OP_)
	 * @param bool flag isUseCache (can be omitted, false by default)
	 * @param string prefix for tables acronyms (can be omitted)
	 * 
	 * @return string #sql_code# for usage in "SELECT ... WHERE LESSON_ID IN (#sql_code#)"
	 * 
	 * @example
	 * $o = CLearnAccess::GetInstance ($someUserId);
	 * $sql = $o->SQLClauseForAccessibleLessons (CLearnAccess::OP_LESSON_READ + CLearnAccess::OP_LESSON_WRITE);
	 * // Selects only lessons, which are accessible by user with id = $someUserId
	 * $rc = $DB->Query ("SELECT NAME FROM b_learn_lesson WHERE ACTIVE = 'Y' AND ID IN (" . $sql . ")");
	 */
	static public function SQLClauseForAccessibleLessons ($in_bitmaskOperations, $isUseCache = false, $lessonId = 0, $in_prfx = 'DEFPRFX');


	public static function GetNameForTask ($taskId);

	/**
	 * @return array of possible rights. Example of array item:
	 *			$arPossibleRights['ID'] = array(
	 *				'name'              => 'NAME',
	 *				'name_human'        => $nameUpperCase,
	 *				'sys'               => 'SYS',
	 *				'description'       => 'DESCRIPTION',
	 *				'description_human' => $descrUpperCase,
	 *				'binding'           => 'BINDING'
	 *				);
	 */
	public static function ListAllPossibleRights();


	/**
	 * This function include CR to access symbols when checks base rights.
	 * @param int bitmask of operations (constants self::OP_...)
	 * @param bool use cache
	 * 
	 * @return bool true - if there is access to given operations
	 */
	static public function IsBaseAccessForCR ($in_bitmaskRequested, $isUseCache = false);


	/**
	 * @param int bitmask of operations (constants self::OP_...)
	 * @param bool use cache (false be default)
	 * @param bool does include CR to check? (false by default)
	 * 
	 * @return bool true - if there is access to given operations
	 */
	static public function IsBaseAccess ($in_bitmaskRequested, $isUseCache = false, $checkForAuthor = false);


	/**
	 * @param array $arPermPairs, for example: array ('CR' => 4, 'U2' => '1', ...).
	 * All unlisted access symbols ("subjects") will be removed.
	 */
	static public function SetBasePermissions ($in_arPermPairs);


	/**
	 * @return array of base for all lessons permissions
	 * @example
	 * <?php
	 * $oAccess = CLearnAccess::getInstance ($USER->GetID());
	 * $arPermPairs = $oAccess->GetLessonPermissions ($some_lesson_id);
	 * ?>
	 * $arPermPairs now contains
	 * array ('AU' => 1, 'U12' => '3', 'CR' => 2, ...)
	 */
	static public function GetBasePermissions ();


	/**
	 * @return array of lesson's permissions
	 * @example
	 * <?php
	 * $oAccess = CLearnAccess::getInstance ($USER->GetID());
	 * $arPermPairs = $oAccess->GetLessonPermissions ($some_lesson_id);
	 * ?>
	 * $arPermPairs now contains
	 * array ('AU' => 1, 'U12' => '3', 'CR' => 2, ...)
	 */
	static public function GetLessonPermissions ($in_lessonId);


	/**
	 * @param array of permissions.
	 * @example
	 * $arPermissions = array(
	 *    1437 => array('CR' => 2, 'AU' => 1),	// lesson_id = 1437, task_id = 2 and 1
	 *    1258 => array('AU' => 1),  // lesson_id = 1258, task_id = 1
	 *    178  => array()	// for this lesson will be cleaned all rights
	 * );
	 * $userId = $USER->GetID();
	 * $oAccess = CLearnAccess::getInstance ($userId);
	 * $oAccess->SetLessonsPermissions ($arPermissions);
	 * 
	 */
	static public function SetLessonsPermissions ($in_arPermissions);


	/**
	 * This function checks access rights for user to given lesson. 
	 * It's includes checks for base rights (shared for all lessons).
	 * 
	 * @return bool true - if lesson is accessible by given user for given operations.
	 */
	static public function IsLessonAccessible ($in_lessonId, $in_bitmaskOperations, $isUseCache = false);


	static public function GetAccessibleLessonsList($in_bitmaskOperations, $isUseCache = false);


	/**
	 * @param int id of lesson
	 * @param int bitmask of operations (constants self::OP_...)
	 * @param bool use cache (false be default)
	 * 
	 * @return array of symbols, which has access to lesson with given operation bitmask.
	 * 
	 * @example of returned array: array ('G11', 'U11', 'AU', 'CR')
	 */
	public static function GetSymbolsAccessibleToLesson ($in_lessonId, $in_bitmaskOperations, $isUseCache = false);
}


class CLearnAccess implements ILearnAccessInterface
{
	protected static $instanceOfSelf = array();
	protected static $CAccessLastUpdated = false;

	protected $userId = false;

	const OP_LESSON_READ                = 0x0001;
	const OP_LESSON_CREATE              = 0x0002;
	const OP_LESSON_WRITE               = 0x0004;
	const OP_LESSON_REMOVE              = 0x0008;
	const OP_LESSON_LINK_TO_PARENTS     = 0x0010;
	const OP_LESSON_UNLINK_FROM_PARENTS = 0x0020;
	const OP_LESSON_LINK_DESCENDANTS    = 0x0040;
	const OP_LESSON_UNLINK_DESCENDANTS  = 0x0080;
	const OP_LESSON_MANAGE_RIGHTS       = 0x0100;

	protected static $arOperations = array(
		'lesson_read'                => self::OP_LESSON_READ,
		'lesson_create'              => self::OP_LESSON_CREATE,
		'lesson_write'               => self::OP_LESSON_WRITE,
		'lesson_remove'              => self::OP_LESSON_REMOVE,
		'lesson_link_to_parents'     => self::OP_LESSON_LINK_TO_PARENTS,
		'lesson_unlink_from_parents' => self::OP_LESSON_UNLINK_FROM_PARENTS,
		'lesson_link_descendants'    => self::OP_LESSON_LINK_DESCENDANTS,
		'lesson_unlink_descendants'  => self::OP_LESSON_UNLINK_DESCENDANTS,
		'lesson_manage_rights'       => self::OP_LESSON_MANAGE_RIGHTS
		);


	// prevent creating throughs "new"
	private function __construct($in_userId)
	{
		$this->userId = self::StrictlyCastToInteger ($in_userId);
	}


	// prevent clone of object
	private function __clone()
	{
	}


	// prevent wakeup
	private function __wakeup()
	{
	}

	/**
	 * @param $in_userId
	 * @return CLearnAccess
	 */
	public static function GetInstance($in_userId)
	{
		$userId = self::StrictlyCastToInteger ($in_userId);

		if ( ! array_key_exists($userId, self::$instanceOfSelf) )
			self::$instanceOfSelf[$userId] = new CLearnAccess($userId);
		
		return (self::$instanceOfSelf[$userId]);
	}


	/**
	 * If user logged in - get hash for of access symbols for user.
	 * If user isn't logged in - get hash of access symbols for not authorized users.
	 */
	public static function GetAccessSymbolsHashForSiteUser()
	{
		global $USER;

		$userId = $USER->GetID();
		$arCodes = array();

		if ($userId > 0)
		{
			$oAccess = CLearnAccess::GetInstance ($userId);
			$arCodes = $oAccess->GetAccessCodes();
		}
		else
			$arCodes = array('G2');	// G2 - is group included all users (not authorized too)

		$hash = base64_encode (serialize($arCodes));

		return ($hash);
	}


	public static function GetNameForTask($taskId)
	{
		global $DB, $MESS;

		$rc = $DB->Query("SELECT NAME FROM b_task WHERE ID = " . (int) $taskId . " AND MODULE_ID = 'learning'");
		if ($rc === false)
		{
			throw new LearnException ('EA_SQLERROR',
				LearnException::EXC_ERR_ALL_GIVEUP);
		}

		$row = $rc->Fetch();

		if ( ! isset($row['NAME']) )
		{
			throw new LearnException ('EA_NOT_EXISTS',
				LearnException::EXC_ERR_ALL_LOGIC);
		}

		$nameUpperCase = strtoupper($row['NAME']);

		return CTask::GetLangTitle($nameUpperCase, "learning");
	}


	/**
	 * @return array of possible rights. Example of array item:
	 *			$arPossibleRights['ID'] = array(
	 *				'name'              => 'NAME',
	 *				'name_human'        => $nameUpperCase,
	 *				'sys'               => 'SYS',
	 *				'description'       => 'DESCRIPTION',
	 *				'description_human' => $descrUpperCase,
	 *				'binding'           => 'BINDING'
	 *				);
	 */
	public static function ListAllPossibleRights()
	{
		global $DB, $MESS;

		$rc = $DB->Query("SELECT ID, NAME, SYS, DESCRIPTION, BINDING FROM b_task WHERE MODULE_ID = 'learning'");
		if ($rc === false)
		{
			throw new LearnException ('EA_SQLERROR',
				LearnException::EXC_ERR_ALL_ACCESS_DENIED
				| LearnException::EXC_ERR_ALL_GIVEUP);
		}

		$arPossibleRights = array();
		while ($row = $rc->Fetch())
		{
			$nameUpperCase = strtoupper($row['NAME']);

			$arPossibleRights[$row['ID']] = array(
				'name'              => $row['NAME'],
				'name_human'        => CTask::GetLangTitle($nameUpperCase, "learning"),
				'sys'               => $row['SYS'],
				'description'       => $row['DESCRIPTION'],
				'description_human' => CTask::GetLangDescription($nameUpperCase, "", "learning"),
				'binding'           => $row['BINDING']
			);
		}

		return ($arPossibleRights);
	}


	public static function GetSymbolsAccessibleToLesson ($in_lessonId, $in_bitmaskOperations, $isUseCache = false)
	{
		global $DB;
		static $cacheSymbols = array();

		if ( ! (is_int($in_bitmaskOperations) && ($in_bitmaskOperations > 0)) )
		{
			throw new LearnException ('bitmask must be an integer > 0', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		$lessonId = (int) $in_lessonId;

		$cacheKey = 'k' . $in_lessonId . '|' . $in_bitmaskOperations;

		if ( ! ($isUseCache && isset($cacheSymbols[$cacheKey])) )
		{
			$arSymbols = array();
			$sqlOperationsNames = self::ParseOperationsForSQL ($in_bitmaskOperations);

			$rc = $DB->Query(
				"SELECT TLR.SUBJECT_ID AS SYMBOLS
				FROM b_learn_rights TLR
				INNER JOIN b_task_operation TTO
					ON TTO.TASK_ID = TLR.TASK_ID
				INNER JOIN b_operation XTO
					ON XTO.ID = TTO.OPERATION_ID
				WHERE TLR.LESSON_ID = " . $lessonId . "
					AND XTO.MODULE_ID = 'learning'
					AND XTO.NAME IN (" . $sqlOperationsNames . ")

				UNION

				SELECT TLRA.SUBJECT_ID AS SYMBOLS
				FROM b_learn_rights_all TLRA
				INNER JOIN b_task_operation TTO
					ON TTO.TASK_ID = TLRA.TASK_ID
				INNER JOIN b_operation XTO
					ON XTO.ID = TTO.OPERATION_ID
				WHERE XTO.MODULE_ID = 'learning'
					AND XTO.NAME IN (" . $sqlOperationsNames . ")
				", true);

			if ($rc === false)
			{
				throw new LearnException ('EA_SQLERROR', 
					LearnException::EXC_ERR_ALL_ACCESS_DENIED 
					| LearnException::EXC_ERR_ALL_GIVEUP);
			}

			while ($row = $rc->Fetch())
				$arSymbols[] = $row['SYMBOLS'];

			$cacheSymbols[$cacheKey] = $arSymbols;
		}

		return ($cacheSymbols[$cacheKey]);
	}


	/**
	 * This function include CR to access symbols when checks base rights.
	 * @param int bitmask of operations (constants self::OP_...)
	 * @param bool use cache
	 * 
	 * @return bool true - if there is access to given operations
	 */
	public function IsBaseAccessForCR ($in_bitmaskRequested, $isUseCache = false)
	{
		return ($this->IsBaseAccess ($in_bitmaskRequested, $isUseCache, true));
	}


	/**
	 * @param int bitmask of operations (constants self::OP_...)
	 * @param bool use cache
	 * @param bool does include CR to check? (false by default)
	 * 
	 * @return bool true - if there is access to given operations
	 */
	public function IsBaseAccess ($in_bitmaskRequested, $isUseCache = false, $checkForAuthor = false)
	{
		global $USER;

		if (is_object($USER) 
			&& ( $this->userId === ((int) $USER->GetID()) ) 
			&& $USER->IsAdmin()
		)
		{
			// Admin can access anything
			return (true);
		}
		elseif (defined('CRON_MODE'))
		{
			// Under cron script anybody can access anything
			return (true);
		}

		if ( ! (is_int($in_bitmaskRequested) && ($in_bitmaskRequested > 0)) )
		{
			throw new LearnException ('bitmask must be an integer > 0', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		$bitmaskRequested = $in_bitmaskRequested;

		// access codes for user $this->userId
		$arUserAccessSymbols = $this->GetAccessCodes ($isUseCache);

		if ($checkForAuthor)
			$arUserAccessSymbols[] = 'CR';

		// bitmask of accessible operations for user
		$bitmaskBaseAccess = $this->GetBitmaskOperationsForAllLessons($arUserAccessSymbols);

		// check that all bits in $bitmaskRequested are setted in $bitmaskBaseAccess
		if ( ($bitmaskRequested & $bitmaskBaseAccess) === $bitmaskRequested )
			return (true);
		else
			return (false);
	}


	/**
	 * @param array $arPermPairs, for example: array ('CR' => 4, 'U2' => '1', ...).
	 * All unlisted access symbols ("subjects") will be removed.
	 */
	public function SetBasePermissions ($in_arPermPairs)
	{
		global $DB, $USER;

		// Check args
		if ( ! is_array($in_arPermPairs) )
		{
			throw new LearnException ('', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		// Check & escape for SQL
		$arPermPairs = array();
		foreach ($in_arPermPairs as $in_subject_id => $in_task_id)
		{
			$subject_id = $DB->ForSQL($in_subject_id);
			$task_id    = self::StrictlyCastToInteger($in_task_id);
			$arPermPairs[$subject_id] = $task_id;
		}

		// Check rights (we can access only if is admin and logged in)
		if ( ! (self::IsLoggedUserCanAccessModuleSettings() 
			&& ( ((int) $USER->GetID()) === $this->userId) ) 
		)
		{
			throw new LearnException ('', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		// Yes, I know - most of products on MyISAM. So, In God We Trust.
		$DB->StartTransaction();

		$rc = $DB->Query(
			"DELETE FROM b_learn_rights_all 
			WHERE 1=1", true);

		if ($rc === false)
		{
			$DB->Rollback();
			throw new LearnException ('EA_SQLERROR', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_GIVEUP);
		}

		foreach ($arPermPairs as $subject_id => $task_id)
		{
			// All data already escaped above!
			$rc = $DB->Query(
				"INSERT INTO b_learn_rights_all (SUBJECT_ID, TASK_ID) 
				VALUES ('" . $subject_id . "', " . $task_id . ")", true);
			if ($rc === false)
			{
				$DB->Rollback();
				throw new LearnException ('EA_SQLERROR', 
					LearnException::EXC_ERR_ALL_ACCESS_DENIED 
					| LearnException::EXC_ERR_ALL_GIVEUP);
			}
		}

		// Amen
		$DB->Commit();

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	/**
	 * @return array of base for all lessons permissions
	 * @example
	 * <?php
	 * $oAccess = CLearnAccess::getInstance ($USER->GetID());
	 * $arPermPairs = $oAccess->GetLessonPermissions ($some_lesson_id);
	 * ?>
	 * $arPermPairs now contains
	 * array ('AU' => 1, 'U12' => '3', 'CR' => 2, ...)
	 */
	static public function GetBasePermissions ()
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT SUBJECT_ID, TASK_ID 
			FROM b_learn_rights_all
			WHERE 1=1");

		if ($rc === false)
		{
			throw new LearnException('EA_SQLERROR', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$arPermPairs = array();
		while ($row = $rc->Fetch())
			$arPermPairs[$row['SUBJECT_ID']] = (int) $row['TASK_ID'];

		return ($arPermPairs);
	}


	/**
	 * @return array of lesson's permissions
	 * @example
	 * <?php
	 * $oAccess = CLearnAccess::getInstance ($USER->GetID());
	 * $arPermPairs = $oAccess->GetLessonPermissions ($some_lesson_id);
	 * ?>
	 * $arPermPairs now contains
	 * array ('AU' => 1, 'U12' => '3', 'CR' => 2, ...)
	 */
	static public function GetLessonPermissions ($in_lessonId)
	{
		global $DB;

		$lessonId = self::StrictlyCastToInteger($in_lessonId);

		$rc = $DB->Query(
			"SELECT LESSON_ID, SUBJECT_ID, TASK_ID 
			FROM b_learn_rights
			WHERE LESSON_ID = " . $lessonId . "
			");

		if ($rc === false)
		{
			throw new LearnException('EA_SQLERROR', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$arPermPairs = array();
		while ($row = $rc->Fetch())
			$arPermPairs[$row['SUBJECT_ID']] = (int) $row['TASK_ID'];

		return ($arPermPairs);
	}


	/**
	 * @param array of permissions.
	 * @example
	 * $arPermissions = array(
	 *    1437 => array('CR' => 2, 'AU' => 1),	// lesson_id = 1437, task_id = 2 and 1
	 *    1258 => array('AU' => 1),  // lesson_id = 1258, task_id = 1
	 *    178  => array()	// for this lesson will be cleaned all rights
	 * );
	 * $userId = $USER->GetID();
	 * $oAccess = CLearnAccess::getInstance ($userId);
	 * $oAccess->SetLessonsPermissions ($arPermissions);
	 * 
	 */
	public function SetLessonsPermissions ($in_arPermissions)
	{
		global $DB;

		// Check args
		if ( ! is_array($in_arPermissions) )
		{
			throw new LearnException ('', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		// First request for rights will not use cache (this will refresh cache)
		$isUseCacheForRights = false;

		$arPermissions = array();
		foreach ($in_arPermissions as $in_lessonId => $arPermPairs)
		{
			if ( ! is_array($arPermPairs) )
			{
				throw new LearnException ('', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
			}

			$lesson_id  = self::StrictlyCastToInteger($in_lessonId);

			// Ensure, that for all requested lessons there is rights for changing rights.
			if ( ! $this->IsLessonAccessible($lesson_id, self::OP_LESSON_MANAGE_RIGHTS, $isUseCacheForRights) )
				throw new LearnException ('', LearnException::EXC_ERR_ALL_ACCESS_DENIED);

			$isUseCacheForRights = true;	// use cache for every next request for rights
	
			// Check params & escape for SQL
			$arPermissions[$lesson_id] = array();
			foreach ($arPermPairs as $in_subject_id => $in_task_id)
			{
				$subject_id = $DB->ForSQL($in_subject_id);
				$task_id    = self::StrictlyCastToInteger($in_task_id);
				$arPermissions[$lesson_id][$subject_id] = $task_id;
			}
		}

		// Yes, I know - most of products on MyISAM. So, In God We Trust.
		$DB->StartTransaction();

		// Process setting permissions
		foreach ($arPermissions as $lesson_id => $arPermPairs)
		{
			$subject_id = $arPerm[0];
			$task_id    = $arPerm[1];

			$rc = $DB->Query(
				"DELETE FROM b_learn_rights 
				WHERE LESSON_ID = $lesson_id", true);

			if ($rc === false)
			{
				$DB->Rollback();
				throw new LearnException ('EA_SQLERROR', 
					LearnException::EXC_ERR_ALL_ACCESS_DENIED 
					| LearnException::EXC_ERR_ALL_GIVEUP);
			}

			foreach ($arPermPairs as $subject_id => $task_id)
			{
				// All data already escaped above!
				$rc = $DB->Query(
					"INSERT INTO b_learn_rights (LESSON_ID, SUBJECT_ID, TASK_ID) 
					VALUES (" . $lesson_id . ", '" . $subject_id . "', " . $task_id . ")", true);
				if ($rc === false)
				{
					$DB->Rollback();
					throw new LearnException ('EA_SQLERROR', 
						LearnException::EXC_ERR_ALL_ACCESS_DENIED 
						| LearnException::EXC_ERR_ALL_GIVEUP);
				}
			}
		}

		// Amen
		$DB->Commit();

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	public function IsLessonAccessible ($in_lessonId, $in_bitmaskOperations, $isUseCache = false)
	{
		static $cacheArIds = array();

		$lessonId = intval($in_lessonId);
		$cacheKey = $in_bitmaskOperations."_".$lessonId;

		if ($isUseCache && array_key_exists($cacheKey, $cacheArIds))
		{
			return true;
		}

		$cacheArIds = array_merge($cacheArIds, $this->GetAccessibleLessonsList($in_bitmaskOperations, $isUseCache, $lessonId));
		return array_key_exists($cacheKey, $cacheArIds);
	}


	public function GetAccessibleLessonsList($in_bitmaskOperations, $isUseCache = false, $lessonId = 0)
	{
		global $DB;

		$sql = $this->SQLClauseForAccessibleLessons($in_bitmaskOperations, $isUseCache, $lessonId);

		$rc = $DB->Query($sql, true);

		if ($rc === false)
		{
			throw new LearnException ('EA_SQLERROR', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_GIVEUP);
		}

		$arIds = array();
		while ($row = $rc->Fetch())
			$arIds[$in_bitmaskOperations."_".$row['LESSON_ID']] = (int) $row['LESSON_ID'];

		return ($arIds);
	}


	/**
	 * @param int operations code (taken from sum of constants with prefix OP_)
	 * @param bool flag isUseCache (can be omitted, false by default)
	 * @param string prefix for tables acronyms (can be omitted)
	 * 
	 * @return string #sql_code# for usage in "SELECT ... WHERE LESSON_ID IN (#sql_code#)"
	 * 
	 * @example
	 * $o = CLearnAccess::GetInstance ($someUserId);
	 * $sql = $o->SQLClauseForAccessibleLessons (CLearnAccess::OP_LESSON_READ + CLearnAccess::OP_LESSON_WRITE);
	 * // Selects only lessons, which are accessible by user with id = $someUserId
	 * $rc = $DB->Query ("SELECT NAME FROM b_learn_lesson WHERE ACTIVE = 'Y' AND ID IN (" . $sql . ")");
	 */
	public function SQLClauseForAccessibleLessons ($in_bitmaskOperations, $isUseCache = false, $lessonId = 0, $in_prfx = 'DEFPRFX')
	{
		if ( ! (is_int($in_bitmaskOperations) && ($in_bitmaskOperations > 0)) )
		{
			throw new LearnException ('bitmask must be an integer > 0', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		$prfx   = CDatabase::ForSQL ($in_prfx);
		$userId = (int) $this->userId;

		// access codes for user $this->userId
		$arUserAccessSymbols = $this->GetAccessCodes ($isUseCache);

		$userAccessSymbols = 'NULL';
		// convert array to comma-separeted list for sql query (items will be escaped)
		if (count($arUserAccessSymbols) > 0)
			$userAccessSymbols = $this->Array2CommaSeparatedListForSQL ($arUserAccessSymbols);

		/**
		 * There are some operations, granted on all lessons in context of some user.
		 * So, we must adjust $in_bitmaskOperations on operations, which are already 
		 * accessible by user (in both roles: as author(CR) and as just any user(Any)).
		 * User role is unknown now, it will be known on SQL query only.
		 */
		// Get bitmask of operations granted on all lessons (any user mode)
		$bitmaskAvailOperationsForAny = $this->GetBitmaskOperationsForAllLessons($arUserAccessSymbols);
		// Get bitmask of operations granted on all lessons (user-author mode)
		$bitmaskAvailOperationsForCR  = $this->GetBitmaskOperationsForAllLessons(array_merge($arUserAccessSymbols, array('CR')));

		/**
		 * Now, switch off bits for operations, 
		 * that are available for current user 
		 * on all lessons (or all own lessons for author).
		 * Because, we must check only rights, that are not
		 * available on all lessons yet.
		 */
		$bitmaskOperationsForAny = $in_bitmaskOperations & ( ~ $bitmaskAvailOperationsForAny );
		$bitmaskOperationsForCR  = $in_bitmaskOperations & ( ~ $bitmaskAvailOperationsForCR );

		// Convert bitmasks to sql comma-separated list of operations' names
		$sqlOperationsForAny = false;
		$sqlOperationsForCR  = false;
		if ($bitmaskOperationsForAny !== 0)
			$sqlOperationsForAny = $this->ParseOperationsForSQL ($bitmaskOperationsForAny);
		if ($bitmaskOperationsForCR !== 0)
			$sqlOperationsForCR  = $this->ParseOperationsForSQL ($bitmaskOperationsForCR);

		$arSqlWhere = array();

		// Is some operations must be checked for author?
		if ($sqlOperationsForCR !== false)
			$arSqlWhere[] = "(${prfx}TLR.SUBJECT_ID = 'CR' AND ${prfx}TLL.CREATED_BY = $userId AND ${prfx}XTO.NAME IN ($sqlOperationsForCR))";
		else
			$arSqlWhere[] = "(${prfx}TLL.CREATED_BY = $userId)";	// All requested operations are permitted for author

		if ($sqlOperationsForAny !== false)
			$arSqlWhere[] = "(${prfx}TLR.SUBJECT_ID IN ($userAccessSymbols) AND ${prfx}XTO.NAME IN ($sqlOperationsForAny))";
		else
			$arSqlWhere[] = "(1=1)";	// All requested operations permitted for user $this->userId

		$sqlWhere = implode("\n OR \n", $arSqlWhere);

		$lessonId = intval($lessonId);
		if ($lessonId > 0)
		{
			$sqlWhere = "${prfx}TLL.ID={$lessonId} AND (".$sqlWhere.")";
		}

		$sql = "SELECT ${prfx}TLL.ID AS LESSON_ID
		FROM b_learn_lesson ${prfx}TLL
		LEFT OUTER JOIN b_learn_rights ${prfx}TLR
			ON ${prfx}TLL.ID = ${prfx}TLR.LESSON_ID
		LEFT OUTER JOIN b_task_operation ${prfx}TTO
			ON ${prfx}TLR.TASK_ID = ${prfx}TTO.TASK_ID
		LEFT OUTER JOIN b_operation ${prfx}XTO
			ON ${prfx}TTO.OPERATION_ID = ${prfx}XTO.ID
		WHERE
			$sqlWhere";

		return ($sql);
		
		/*
		prev version of code:

		$userAccessSymbols = $this->GetAccessCodesForSQL ($isUseCache);
		$sqlOperations     = $this->ParseOperationsForSQL ($in_bitmaskOperations);
		$prfx   = CDatabase::ForSQL ($in_prfx);
		$userId = $this->userId;

		$sql = "
		SELECT ${prfx}TLR.LESSON_ID
		FROM b_learn_rights ${prfx}TLR
		INNER JOIN b_task_operation ${prfx}TTO
			ON ${prfx}TLR.TASK_ID = ${prfx}TTO.TASK_ID
		INNER JOIN b_operation ${prfx}TO
			ON ${prfx}TTO.OPERATION_ID = ${prfx}TO.ID
		INNER JOIN b_learn_lesson ${prfx}TLL
			ON ${prfx}TLL.ID = ${prfx}TLR.LESSON_ID
		WHERE 
			TO.NAME IN ($sqlOperations)
			AND
			(
			(${prfx}TLR.SUBJECT_ID = 'CR' AND ${prfx}TLL.CREATED_BY = $userId)
			OR (TLR.SUBJECT_ID IN ($userAccessSymbols))
			)";

		return ($sql);
		*/
	}


	protected function GetBitmaskOperationsForAllLessons($arUserAccessSymbols)
	{
		global $DB;
		static $cache = array();

		if (!is_array($arUserAccessSymbols) || count($arUserAccessSymbols) < 1)
		{
			return 0;
		}

		$userAccessSymbols = $this->Array2CommaSeparatedListForSQL ($arUserAccessSymbols);
		if (isset($cache[$userAccessSymbols]))
		{
			return $cache[$userAccessSymbols];
		}

		$rc = $DB->Query (
			"SELECT XTO.NAME AS OPERATION_NAME
			FROM b_learn_rights_all TLRA
			INNER JOIN b_task_operation TTO
				ON TTO.TASK_ID = TLRA.TASK_ID
			INNER JOIN b_operation XTO
				ON XTO.ID = TTO.OPERATION_ID
			WHERE TLRA.SUBJECT_ID IN ($userAccessSymbols)", 
			true);
		if ($rc === false)
		{
			throw new LearnException ('EA_SQLERROR: ', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$bitmaskOperations = 0;
		while ($arData = $rc->Fetch())
		{
			if ( ! isset(self::$arOperations[$arData['OPERATION_NAME']]) )
			{
				throw new LearnException ('Unknown operation: ' . $arData['OPERATION_NAME'], 
					LearnException::EXC_ERR_ALL_LOGIC 
					| LearnException::EXC_ERR_ALL_GIVEUP
					| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
			}

			$bitmaskOperations = $bitmaskOperations | self::$arOperations[$arData['OPERATION_NAME']];
		}

		$cache[$userAccessSymbols] = $bitmaskOperations;
		return ($bitmaskOperations);
	}


	/**
	 * @return string of comma-separated operations names
	 */
	protected static function ParseOperationsForSQL ($in_operations)
	{
		static $determinedCache = array();

		if ( ! (is_int($in_operations) && ($in_operations > 0)) )
			throw new LearnException ('', LearnException::EXC_ERR_ALL_PARAMS | LearnException::EXC_ERR_ALL_ACCESS_DENIED);

		$cacheKey = 'str' . $in_operations;

		if ( ! isset ($determinedCache[$cacheKey]) )
		{
			$arOperations = array();
			foreach (self::$arOperations as $operationName => $operationBitFlag)
			{
				if ($in_operations & $operationBitFlag)
				{
					$arOperations[] = $operationName;
					$in_operations -= $operationBitFlag;
				}
			}

			// Must be zero. If not => not all operations listed in self::$arOperations
			// or wrong requested value in $in_operations
			if ($in_operations !== 0)
				throw new LearnException ('', LearnException::EXC_ERR_ALL_PARAMS | LearnException::EXC_ERR_ALL_ACCESS_DENIED);

			$sql = self::Array2CommaSeparatedListForSQL ($arOperations);
			$determinedCache[$cacheKey] = $sql;
		}

		return ($determinedCache[$cacheKey]);
	}


	/**
	 * @return string of comma-separated access codes, includes AU symbol (if user is authorized)
	 */
	protected function GetAccessCodesForSQL ($isUseCache = false)
	{
		static $cache = array();

		if ($isUseCache && isset($cache['str' . $this->userId]))
			return ($cache['str' . $this->userId]);

		$arCodes = $this->GetAccessCodes ($isUseCache);
		$sql = $this->Array2CommaSeparatedListForSQL ($arCodes);

		// Cache in case when $isUseCache === false too. 
		// Because, this will refresh cache, if it exists before.
		$cache['str' . $this->userId] = $sql;

		return ($sql);
	}


	/**
	 * @return array of access codes, includes AU symbol (if user is authorized)
	 */
	protected function GetAccessCodes ($isUseCache = false)
	{
		global $USER;
		static $cache = array();
		$isNeedCAccessUpdate = true;

		if ($isUseCache)
		{
			// Cache hits?
			if (isset($cache['str' . $this->userId]))
				return ($cache['str' . $this->userId]);

			// Prevent call CAccess->UpdateCodes() multiple times per hit,
			// except long time period (three seconds) expired.
			if ( ($this->CAccessLastUpdated === false) 
				|| ( (microtime(true) - $this->CAccessLastUpdated) > 3 )
			)
			{
				$isNeedCAccessUpdate = true;
			}
			else
				$isNeedCAccessUpdate = false;
		}
		else
			$isNeedCAccessUpdate = true;

		if ($isNeedCAccessUpdate)
		{
			$oAcc = new CAccess();
			$oAcc->UpdateCodes();

			if ($isUseCache)
				$this->CAccessLastUpdated = microtime(true);

			unset ($oAcc);
		}

		$rc = CAccess::GetUserCodes($this->userId);
		if ($rc === false)
		{
			throw new LearnException('', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$arData = array();
		while ($arItem = $rc->Fetch())
		{
			if ( ( (int) $arItem['USER_ID'] ) !== $this->userId )
			{
				throw new LearnException('', 
					LearnException::EXC_ERR_ALL_GIVEUP 
					| LearnException::EXC_ERR_ALL_LOGIC
					| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
			}

			$arData[] = $arItem['ACCESS_CODE'];
		}

		if ( is_object($USER) && ( $this->userId === ((int) $USER->GetID()) ) )
			$arData[] = 'AU';

		// Cache in case when $isUseCache === false too. 
		// Because, this will refresh cache, if it exists before.
		$cache['str' . $this->userId] = $arData;

		return ($arData);
	}


	protected static function Array2CommaSeparatedListForSQL ($in_arData)
	{
		$arData = array_map (array('CLearnAccess', 'EscapeAndAddLateralQuotes'), $in_arData);

		$sql = implode(',', $arData);

		return ($sql);
	}


	protected static function EscapeAndAddLateralQuotes ($txt)
	{
		return ("'" . CDatabase::ForSQL($txt) . "'");
	}


	public static function IsLoggedUserCanAccessModuleSettings()
	{
		global $USER, $APPLICATION;

		if ($USER->IsAdmin() || ($APPLICATION->GetGroupRight('learning') === 'W'))
			return (true);
		else
			return (false);
	}


	protected static function StrictlyCastToInteger ($var)
	{
		if ( ! preg_match("/^[0-9]+$/", (string) $var) )
		{
			throw new LearnException(
				'EA_PARAMS: can\'t b strictly casted to integer, but expected: ' . $var, 
				LearnException::EXC_ERR_ALL_PARAMS 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		return ( (int) $var );
	}
}