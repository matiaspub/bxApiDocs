<?php

// Process permissions
class CLearnParsePermissionsFromFilter
{
	protected $requestedUserId     = false;
	protected $bCheckPerm          = false;
	protected $cachedSQL           = false;
	protected $requestedOperations = false;
	protected $oAccess             = false;
	private static $availableLessons = array();


	public function __construct ($arFilter)
	{
		$loggedUserId = false;

		// Skip checking permissions?
		if (isset($arFilter['CHECK_PERMISSIONS']) && ($arFilter['CHECK_PERMISSIONS'] === 'N'))
			return;

		// Determine requested operations
		$this->requestedOperations = self::ParseRequestedOperations ($arFilter);

		// Determine logged in user
		global $USER;
		if (is_object($USER) && method_exists($USER, 'GetID'))
			$loggedUserId = (int) $USER->GetID();
		
		$this->requestedUserId = self::DetermineRequestedUserId ($arFilter, $loggedUserId);

		// If user_id === current logged user_id, and he is admin => skip checking permissions
		if (($this->requestedUserId === $loggedUserId) && $USER->IsAdmin())
			return;		// skip checking permissions

		$this->oAccess = CLearnAccess::GetInstance($this->requestedUserId);

		// If base (shared) user rights covers requested operations => nothing to check.
		if ($this->oAccess->IsBaseAccess ($this->requestedOperations))
			return;		// skip checking permissions

		// Checking of permissions must be.
		$this->bCheckPerm = true;
	}


	protected static function DetermineRequestedUserId ($arFilter, $loggedUserId)
	{
		// If user_id given - use it, instead of logged-in user_id
		if ( isset($arFilter['CHECK_PERMISSIONS_FOR_USER_ID']) )
			$requestedUserId = (int) $arFilter['CHECK_PERMISSIONS_FOR_USER_ID'];
		elseif ($loggedUserId !== false)
			$requestedUserId = $loggedUserId;
		else
		{
			// No user logged in and no user given: this is logic error.
			throw new LearnException ('EA_LOGIC',
				LearnException::EXC_ERR_ALL_LOGIC 
				| LearnException::EXC_ERR_ALL_GIVEUP);
		}

		return ($requestedUserId);
	}


	protected static function ParseRequestedOperations ($arFilter)
	{
		// firstly, ensure that no MIN_PERMISSION given (it's orphaned request)
		if (array_key_exists('MIN_PERMISSION', $arFilter))
		{
			throw new LearnException (
				'EA_PARAMS: outdated "MIN_PERMISSION" key used.',
				LearnException::EXC_ERR_ALL_LOGIC 
				| LearnException::EXC_ERR_ALL_PARAMS);
		}

		// Determine requested operations
		if ( ! isset($arFilter['ACCESS_OPERATIONS']) )
		{
			// no requested operations given, it means that only OP_LESSON_READ opeartions requested.
			$requestedOperations = CLearnAccess::OP_LESSON_READ;
		}
		else
		{
			// requested operations MUST be an integer, because of bitmask nature.
			// and must be > 0
			if ( 
				( ! is_int($arFilter['ACCESS_OPERATIONS']) )
				|| ( ! ($arFilter['ACCESS_OPERATIONS'] > 0) )
			)
			{
				throw new LearnException (
					'EA_PARAMS: bitmask ACCESS_OPERATIONS must be an integer and > 0.',
					LearnException::EXC_ERR_ALL_LOGIC 
					| LearnException::EXC_ERR_ALL_PARAMS);
			}

			// requested operations
			$requestedOperations = $arFilter['ACCESS_OPERATIONS'];
		}

		return ($requestedOperations);
	}


	public function SQLForAccessibleLessons()
	{
		// SQL exists only if check permissions must be done
		if ($this->bCheckPerm === false)
		{
			throw new LearnException ('', 
				LearnException::EXC_ERR_ALL_LOGIC 
				| LearnException::EXC_ERR_ALL_GIVEUP);
		}

		// Is not cached yet?
		if ($this->cachedSQL === false)
		{
			$this->cachedSQL = $this->oAccess->SQLClauseForAccessibleLessons ($this->requestedOperations);

			global $USER;
			if (is_object($USER) && method_exists($USER, "GetID") && intval($USER->GetID()) > 0)
			{
				$rs = CLearningGroup::getList(
					array(),
					array(
						"MEMBER_ID" => intval($USER->GetID()),
						"ACTIVE" => "Y",
						"ACTIVE_DATE" => "Y"
					)
				);

				$availableCourses = array();
				while ($group = $rs->fetch())
				{
					$availableCourses[] = $group["COURSE_LESSON_ID"];
				}

				if (count($availableCourses) > 0)
				{
					$this->cachedSQL .= " UNION SELECT ID as LESSON_ID
						FROM b_learn_lesson
						WHERE ID IN (".join(",", $availableCourses).")";
				}
			}
		}

		return ($this->cachedSQL);
	}

	public function IsNeedCheckPerm()
	{
		return ($this->bCheckPerm);
	}
}
