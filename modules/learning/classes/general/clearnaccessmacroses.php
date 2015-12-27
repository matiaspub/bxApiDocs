<?php

class CLearnAccessMacroses
{
	public static function CanUserViewLessonAsPublic ($arParams, $allowAccessViaLearningGroups = true)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'COURSE_ID' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					),
				'LESSON_ID' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		// Is it course?
		$linkedLessonId = CCourse::CourseGetLinkedLesson($options['COURSE_ID']);
		if ($linkedLessonId === false)
			return (false);		// Access denied

		$lessonId = $options['LESSON_ID'];
		$breakOnLessonId = $linkedLessonId;	// save resources

		// Is lesson included into given course?
		$isLessonChildOfCourse = false;
		$arOPathes = CLearnLesson::GetListOfParentPathes ($lessonId, $breakOnLessonId);
		foreach ($arOPathes as $oPath)
		{
			$topLessonId = $oPath->GetTop();

			if (($topLessonId !== false) && ($topLessonId == $linkedLessonId))
			{
				$isLessonChildOfCourse = true;
				break;
			}
		}

		if ( ! $isLessonChildOfCourse )
			return (false);		// Access denied

		// Check permissions for course
		$isCourseAccessible = self::CanUserViewLessonContent (array('lesson_id' => $linkedLessonId), $allowAccessViaLearningGroups);

		// Permissions for all lessons/chapters in public are equivalent to course permissions
		return ($isCourseAccessible);
	}


	/**
	 * If $arParams['user_id'] not set, or set to -1 => $USER->GetID() will be used
	 */
	public static function CanUserAddLessonWithoutParentLesson ($arParams = array())
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser($arParams, array());

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE);

		return ($isAccessGranted);
	}


	/**
	 * If $arParams['user_id'] not set, or set to -1 => $USER->GetID() will be used
	 * $arParams['parent_lesson_id'] must be set.
	 */
	public static function CanUserAddLessonToParentLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'parent_lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$parent_lesson_id = $options['parent_lesson_id'];
		$user_id          = $options['user_id'];

		$oAccess = CLearnAccess::GetInstance($user_id);

		$isAccessGranted = 
			$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE)
			&& $oAccess->IsBaseAccessForCR(CLearnAccess::OP_LESSON_LINK_TO_PARENTS)
			&& $oAccess->IsLessonAccessible($parent_lesson_id, CLearnAccess::OP_LESSON_LINK_DESCENDANTS);

		return ($isAccessGranted);
	}


	public static function CanUserEditLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_WRITE)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_WRITE);

		return ($isAccessGranted);
	}


	public static function CanUserRemoveLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_REMOVE)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_REMOVE);

		return ($isAccessGranted);
	}


	public static function CanUserViewLessonContent ($arParams, $allowAccessViaLearningGroups = true)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_READ)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_READ);


		if ($allowAccessViaLearningGroups)
		{
			if ( ! $isAccessGranted )
			{
				$arPeriod = self::getActiveLearningGroupsPeriod($options['lesson_id'], $options['user_id']);

				if ($arPeriod['IS_EXISTS'])
					$isAccessGranted = true;
			}
		}

		return ($isAccessGranted);
	}


	public static function CanUserViewLessonRelations ($arParams)
	{
		$isAccessGranted = false;

		if (self::CanUserViewLessonContent ($arParams)
			|| self::CanUserPerformAtLeastOneRelationAction ($arParams)
		)
		{
			$isAccessGranted = true;	// Access granted
		}

		return ($isAccessGranted);
	}


	public static function CanUserPerformAtLeastOneRelationAction ($arParams)
	{
		static $arPermissiveOperations = array(
			CLearnAccess::OP_LESSON_LINK_TO_PARENTS,
			CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS,
			CLearnAccess::OP_LESSON_LINK_DESCENDANTS,
			CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS
			);

		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		foreach ($arPermissiveOperations as $operation)
		{
			if ($oAccess->IsLessonAccessible(
				$options['lesson_id'],
				$operation)
			)
			{
				return (true);	// Yeah, there is some rights for some actions with relations
			}
		}

		return (false);
	}


	public static function CanUserEditLessonRights ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsLessonAccessible(
			$options['lesson_id'],
			CLearnAccess::OP_LESSON_MANAGE_RIGHTS
			);
			
		return ($isAccessGranted);
	}


	public static function CanUserViewLessonRights ($arParams)
	{
		$isAccessGranted = self::CanUserViewLessonContent ($arParams)
			|| self::CanUserEditLessonRights ($arParams);

		return ($isAccessGranted);
	}


	/**
	 * Parse params throughs CLearnSharedArgManager::StaticParser(),
	 * but includes shared field 'user_id' and automatically replace
	 * user_id === -1 to user_id = $USER->GetID();
	 */
	protected static function ParseParamsWithUser ($arParams, $arParserOptions)
	{
		if ( ! (is_array($arParams) && is_array($arParserOptions)) )
		{
			throw new LearnException(
				'EA_LOGIC: $arParams and $arParserOptions must be arrays', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_LOGIC
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		if (array_key_exists('user_id', $arParserOptions))
		{
			throw new LearnException(
				'EA_LOGIC: unexpected user_id in $arParams', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_LOGIC
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$arParserOptions['user_id'] = array(
			'type'          => 'strictly_castable_to_integer',
			'mandatory'     => false,
			'default_value' => -1	// it means, we must should use current user id
			);

		// Parse options
		try
		{
			$options = CLearnSharedArgManager::StaticParser(
				$arParams,
				$arParserOptions
				);
		}
		catch (Exception $e)
		{
			throw new LearnException(
				'EA_OTHER: CLearnSharedArgManager::StaticParser() throws an exception with code: ' 
					. $e->GetCode()	. ' and message: ' . $e->GetMessage(), 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		if ($options['user_id'] === -1)
			$options['user_id'] = self::GetCurrentUserId();

		if ($options['user_id'] < 1)
			$options['user_id'] = 0;	// Not authorized user

		return ($options);
	}


	protected static function GetCurrentUserId()
	{
		global $USER;

		if ( ! (is_object($USER) && method_exists($USER, 'GetID')) )
			return (0);

		if ( ! $USER->IsAuthorized() )
			return (0);

		return ( (int) $USER->GetID() );
	}


	public static function getActiveLearningGroupsPeriod($courseLessonId, $userId)
	{
		static $arCache = array();

		$userId = intval($userId);
		$courseLessonId = intval($courseLessonId);
		$cacheKey = $courseLessonId."|".$userId;

		if ( ! array_key_exists($cacheKey, $arCache) )
		{
			$rs = CLearningGroup::getList(
				array(),
				array(
					'ACTIVE'           => 'Y',
					'MEMBER_ID'        => $userId,
					'COURSE_LESSON_ID' => $courseLessonId,
					'ACTIVE_DATE'      => 'Y'
				),
				array('ID', 'MEMBER_ID', 'ACTIVE_FROM', 'ACTIVE_TO')	// $arSelect
			);

			$minActiveFrom = null;
			$minActiveFromFound = false;
			$minActiveFromTs = PHP_INT_MAX;
			$maxActiveTo = null;
			$maxActiveToFound = false;
			$maxActiveToTs = 0;

			$exists = false;
			$arGroupsActiveFrom = array();
			while ($ar = $rs->fetch())
			{
				$exists = true;
				$arGroupsActiveFrom[$ar['ID']] = $ar['ACTIVE_FROM'];

				if ($ar['ACTIVE_FROM'] === null)
				{
					$minActiveFrom = null;
					$minActiveFromFound = true;
				}
				elseif (!$minActiveFromFound)
				{
					$activeFromTs = MakeTimeStamp($ar['ACTIVE_FROM']);
					if ($activeFromTs < $minActiveFromTs)
					{
						$minActiveFrom   = $ar['ACTIVE_FROM'];
						$minActiveFromTs = $activeFromTs;
					}
				}

				if ($ar['ACTIVE_TO'] === null)
				{
					$maxActiveTo = null;
					$maxActiveToFound = true;
				}
				elseif (!$maxActiveToFound)
				{
					$activeToTs = MakeTimeStamp($ar['ACTIVE_TO']);
					if ($activeToTs > $maxActiveToTs)
					{
						$maxActiveTo   = $ar['ACTIVE_TO'];
						$maxActiveToTs = $activeToTs;
					}
				}
			}

			$arPeriod = array(
				'IS_EXISTS'   => $exists,
				'ACTIVE_FROM' => $minActiveFrom,
				'ACTIVE_TO'   => $maxActiveTo,
				'GROUPS_ACTIVE_FROM' => $arGroupsActiveFrom
			);

			$arCache[$cacheKey] = $arPeriod;
		}
		else
			$arPeriod = $arCache[$cacheKey];

		return ($arPeriod);
	}


	public static function getActiveLearningChaptersPeriod($courseLessonId, $userId)
	{
		$arGroupsPeriods = self::getActiveLearningGroupsPeriod($courseLessonId, $userId);
		if (!$arGroupsPeriods['IS_EXISTS'])
		{
			return false;
		}

		$arChaptersActiveFrom = array();
		$arGroupsActiveFrom = $arGroupsPeriods['GROUPS_ACTIVE_FROM'];

		$arLessons = array();
		$rs = CLearnLesson::GetListOfImmediateChilds(
			$courseLessonId,
			array(),
			array('CHECK_PERMISSIONS' => 'N'),
			array('LESSON_ID')
		);

		$arMinChaptersActiveFromTimestamp = array();
		while ($ar = $rs->fetch())
		{
			$arLessons[$ar['LESSON_ID']] = $ar['NAME'];
			$arChaptersActiveFrom[$ar['LESSON_ID']] = null;
			$arMinChaptersActiveFromTimestamp[$ar['LESSON_ID']] = PHP_INT_MAX;
		}

		// Get the nearest dates, when lesson can be opened
		foreach ($arGroupsActiveFrom as $groupId => $groupActiveFrom)
		{
			if ($groupActiveFrom === null)
			{
				continue;
			}

			$arDelays = CLearningGroupLesson::getDelays($groupId, array_keys($arLessons));
			$groupActiveFromTs = MakeTimeStamp($groupActiveFrom);

			foreach ($arDelays as $lessonId => $delay)
			{
				$fromTs = $groupActiveFromTs + 86400 * $delay;	// 24h is 86400 seconds

				// search for nearest dates
				if ($fromTs < $arMinChaptersActiveFromTimestamp[$lessonId])
				{
					$arChaptersActiveFrom[$lessonId] = ConvertTimeStamp($fromTs, 'FULL');
					$arMinChaptersActiveFromTimestamp[$lessonId] = $fromTs;
				}
			}
		}

		return ($arChaptersActiveFrom);
	}

	public static function CanViewAdminMenu()
    {
		global $USER;

		if ($USER->IsAdmin())
		{
			return true;
		}

		$oAccess = CLearnAccess::GetInstance($USER->GetID());
		if (
			$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_READ)
			&& (
				$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_WRITE)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_REMOVE)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_TO_PARENTS)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_DESCENDANTS)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS)
				|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_MANAGE_RIGHTS)
			)
		)
		{
			return true;
		}

		if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE))
		{
			return true;
		}

		$db = CCourse::GetList(
			array(),
			array(
				"CHECK_PERMISSIONS" => "Y",
				"ACCESS_OPERATIONS" =>
					CLearnAccess::OP_LESSON_CREATE |
					CLearnAccess::OP_LESSON_WRITE |
					CLearnAccess::OP_LESSON_REMOVE |
					CLearnAccess::OP_LESSON_LINK_TO_PARENTS |
					CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS |
					CLearnAccess::OP_LESSON_LINK_DESCENDANTS |
					CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS |
					CLearnAccess::OP_LESSON_MANAGE_RIGHTS
			),
			array("nTopCount" => 1)
		);

		return $db->Fetch() !== false;
	}
}
