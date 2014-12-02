<?php

/**
 * Note: usually in phpDoc blocks for methods listed not all exceptions, 
 * that can be throwed by them.
 * 
 * @access public
 */
interface ILearnLesson
{
	/**
	 * WARNING: second param ($isCourse) must be always set to FALSE, because
	 * it's is for internal use only. If you want to create course, use
	 * CCourse::Add instead.
	 * 
	 * Creates new lesson
	 *
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Insert() in main
	 * module (version info:
	 *    define("SM_VERSION","11.0.12");
	 *    define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param array of pairs field => value for new lesson. Allowed fields are ACTIVE,
	 * ACTIVE, true by default, available values are: true/false
	 * NAME, mustn't be omitted
	 * CODE, NULL by default
	 * PREVIEW_PICTURE, NULL by default, available value is array ('name' => ..., 
	 *     'size' => ..., 'tmp_name' => ..., 'type' => ..., 'del' => ...)
	 * PREVIEW_TEXT, NULL by default
	 * PREVIEW_TEXT_TYPE, 'text' by default, available values are: 'text', 'html'
	 * DETAIL_PICTURE, NULL by default, available value is array ('name' => ..., 
	 *     'size' => ..., 'tmp_name' => ..., 'type' => ..., 'del' => ...)
	 * DETAIL_TEXT, NULL by default
	 * DETAIL_TEXT_TYPE, 'text' by default, available values are: 'text', 'html', 'file' (filename in LAUNCH)
	 * LAUNCH, NULL by default
	 * 
	 * @param bool flag of course. If FALSE (default) lesson is not course,
	 *        if TRUE created lesson will be the course (in this case arguments
	 *        $parentLessonId and $arProperties are ignored, and $arFields
	 *        can contain additional fields (any can be omitted):
	 *        - SORT or COURSE_SORT: integer, 500 by default, sort order of courses. CORSE_SORT overrides SORT
	 *        - ACTIVE_FROM: datetime, NULL by default
	 *        - ACTIVE_TO: datetime, NULL by default
	 *        - RATING: string (1 char): 'Y' / 'N' / NULL, 'N' by default
	 *        - RATING_TYPE: string, allowed values: NULL, "like", "standart_text", 
	 *           "like_graphic", "standart", NULL by default
	 *        - SCORM: string (1 char), 'N' by default
	 *        ).
	 * @param integer/bool id of immediate parent lesson. Default value is TRUE,
	 *        what means "no immediate parent".
	 * @param array of properties in relation to
	 *        parent lesson: array ('SORT' => sort_order)
	 *
	 * @throws LearnException with errcodes bit set (one of):
	 *         - LearnException::EXC_ERR_GN_CREATE,
	 *         - LearnException::EXC_ERR_GN_CHECK_PARAMS,
	 *         - LearnException::EXC_ERR_GN_FILE_UPLOAD
	 * Also can throws other exceptions or exceptions' codes.
	 *
	 * @return integer id of created lesson (if course was created, 
	 *         for get id of course method GetLinkedCourse() must be used)
	 */
	public static function Add ($arFields, $isCourse = false,
		$parentLessonId = true, $arProperties = array('SORT' => 500));


	/**
	 * Changes lesson's data
	 *
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Update() in main
	 * module (version info:
	 *    define("SM_VERSION","11.0.12");
	 *    define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param integer id of node to be updated
	 * @param array of pairs field => value for lesson
	 *        If lesson is the course, additional fields maybe set:
	 *        - SORT or COURSE_SORT: integer, sort order of courses. CORSE_SORT overrides SORT
	 *        - ACTIVE_FROM: datetime
	 *        - ACTIVE_TO: datetime
	 *        - RATING: string (1 char): 'Y' / 'N' / NULL
	 *        - RATING_TYPE: string, allowed values: NULL, "like", "standart_text", 
	 *           "like_graphic", "standart"
	 *        - SCORM: string (1 char)
	 *        ).
	 *
	 * @throws LearnException with errcodes bit set (one of):
	 *         - LearnException::EXC_ERR_GN_UPDATE,
	 *         - LearnException::EXC_ERR_GN_CHECK_PARAMS,
	 *         - LearnException::EXC_ERR_GN_FILE_UPLOAD
	 * Also can throws other exceptions or exceptions' codes
	 */
	public static function Update ($id, $arFields);


	/**
	 * Removes lesson and all relations from/to it.
	 *
	 * @param integer/array. If not array => param interpreted as id of lesson to be removed.
	 * If array => param interpreted as array of params. Available params are:
	 * - 'lesson_id' - integer. Id of lesson to be removed.
	 * - 'simulate' - boolean, false by default. If true => nothing will be writed to DB.
	 * - 'check_permissions' - boolean, true by default
	 * - 'user_id' - integer. User_id for which permissions will be checked, -1 by 
	 * default, which means 'current logged user'
	 * (it's means 'current user')
	 *
	 * @throws LearnException with errcode bit set LearnException::EXC_ERR_GN_REMOVE,
	 *         also errmsg === 'EA_NOT_EXISTS' if there is wasn't node with this id.
	 */
	public static function Delete ($id);


	/**
	 * Detach the given lesson from all parents and recursively remove descendants, excepts
	 * descendants, that have ancestors outside of descendants of the given lesson. Such
	 * lessons will not be removed, but will be unlinked from lessons, which will be really 
	 * removed.
	 *
	 * @param integer/array. If not array => param interpreted as id of lesson to be removed.
	 * If array => param interpreted as array of params. Available params are:
	 * - 'lesson_id' - integer. Id of lesson to be removed.
	 * - 'simulate' - boolean, false by default. If true => nothing will be writed to DB.
	 * - 'check_permissions' - boolean, true by default
	 * - 'user_id' - integer. User_id for which permissions will be checked, -1 by default 
	 * (it's means 'current user')
	 */
	public static function DeleteRecursiveLikeHardlinks ($id);


	/**
	 * WARNING: don't use this function, it's for internal use only.
	 * 
	 * @param integer id of node to be getted
	 *
	 * @throws LearnException with errcode bit set LearnException::EXC_ERR_GN_GETBYID.
	 *         Messages can be: 'EA_PARAMS', 'EA_ACCESS_DENIED',
	 *         'EA_SQLERROR', 'EA_NOT_EXISTS'.
	 * 
	 * @access private
	 *
	 * @return array of properties for node with $id
	 */
	public static function GetByIDAsArr($id);


	/**
	 * @throws LearnException with error bit set EXC_ERR_ALL_PARAMS 
	 * @return CDBResult
	 */
	public static function GetByID($id);


	/**
	 * @param array order in format: array('FIELD_NAME' => '#sort_order#' [, ...]), 
	 * where #sort_order# is 'ASC' or 'DESC'
	 * 
	 * @param array filter in format: array('???FIELD_NAME' => 'value' [, ...]),
	 * where ??? can be one of (without double quotes):
	 * "!" - not equals
	 * "<" - less than value
	 * "<=" - less than or equal to value
	 * ">" - greater than value
	 * ">=" - greater than or equal to value
	 * 
	 * Additionally available fields (not presented in data, but 
	 * can be used for filter): DATE_ACTIVE_TO, DATE_ACTIVE_FROM, ACTIVE_DATE.
	 * 
	 * @example shows lessons with LESSON_ID >= 100, and DETAIL_TEXT_TYPE != 'html',
	 * sorted by NAME ascending, than by LESSON_ID descending.
	 * 
	 * <?php
	 * $arOrder = array ('NAME' => 'ASC', 'LESSON_ID' => 'DESC');
	 * $arFilter = array ('!DETAIL_TEXT_TYPE' => 'html', '>=LESSON_ID' => 100);
	 * 
	 * $rc = ClassName::GetList($arOrder, $arFilter);
	 * while (($data = $rc->Fetch()) !== false)
	 *    var _dump ($data);
	 * 
	 * @throws LearnException with error bit set EXC_ERR_ALL_PARAMS 
	 * @return CDBResult each element of which can contains 
	 * COURSE_SORT (only if lesson is course)
	 */
	public static function GetList($arOrder = array(), $arFilter = array());


	/**
	 * @param int id of child lesson
	 * @param array order (see format in comment for ThisClass::GetList())
	 * @param array filter (see format in comment for ThisClass::GetList())
	 * @param array list of fields to be selected. If empty => selects all fields.
	 * 
	 * @throws LearnException with error bit set EXC_ERR_ALL_PARAMS 
	 * @return CDBResult
	 */
	public static function GetListOfImmediateParents($lessonId, $arOrder = array(), $arFilter = array(), $arSelectFields = array());


	/**
	 * @param int id of parent lesson
	 * @param array order (see format in comment for ThisClass::GetList())
	 * @param array filter (see format in comment for ThisClass::GetList())
	 * @param array list of fields to be selected. If empty => selects all fields.
	 * 
	 * @throws LearnException with error bit set EXC_ERR_ALL_PARAMS 
	 * @return CDBResult each element contains EDGE_SORT - the sort index
	 * of LESSON_ID in relation to parent lesson given in first argument
	 * to this method.
	 */
	public static function GetListOfImmediateChilds($lessonId, $arOrder = array(), $arFilter = array(), $arSelectFields = array());


	/**
	 * Lists immediate parents.
	 *
	 * @param integer id of lesson
	 *
	 * @return array of immediate parents (empty array if there is no parents)
	 *
	 * @example
	 * <?php
	 * $arParents = ThisClass::ListImmediateNeighbours (1);
	 * var _dump ($arParents);
	 * ?>
	 *
	 * output:
	 * array(2) {
	 *   [0]=>
	 *   array(4) {
	 *     ["PARENT_LESSON"]=>
	 *     int(1)
	 *     ["CHILD_LESSON"]=>
	 *     int(2)
	 *     ["SORT"]=>
	 *     int(500)
	 *   }
	 *   [1]=>
	 *   array(4) {
	 *     ["PARENT_LESSON"]=>
	 *     int(4)
	 *     ["CHILD_LESSON"]=>
	 *     int(1)
	 *     ["SORT"]=>
	 *     int(500)
	 *   }
	 * }
	 *
	 */
	public static function ListImmediateParents($lessonId);


	/**
	 * Lists immediate childs.
	 *
	 * @param integer id of lesson
	 *
	 * @return array of immediate childs (empty array if there is no childs)
	 * 
	 * @see example for ListImmediateParents()
	 */
	public static function ListImmediateChilds($lessonId);


	/**
	 * Lists immediate neighbours.
	 *
	 * @param integer id of lesson
	 *
	 * @return array of immediate neighbours (empty array if there is no neighbours)
	 * 
	 * @see example for ListImmediateParents()
	 */
	public static function ListImmediateNeighbours($lessonId);


	/**
	 * Gets id of course corresponded to given lesson
	 * @param integer id of lesson
	 * @throws LearnException with error bit set (one of):
	 *         - LearnException::EXC_ERR_ALL_GIVEUP
	 *         - LearnException::EXC_ERR_ALL_LOGIC
	 * @return integer/bool id of linked (corresponded) course or 
	 *         FALSE if there is no course corresponded to the lesson.
	 */
	public static function GetLinkedCourse ($lessonId);


	/**
	 * Build tree of lessons with the given root.
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 * 
	 * @param integer id of root lesson
	 * @param array order (by default: array('EDGE_SORT' => 'asc'))
	 * @param array Filter for lessons
	 * @param bool public prohibition mode flag. If set to TRUE, than all 
	 * 		lessons (and they descendants) that are public prohibited in context 
	 * 		of a course with lesson_id == $lessonId will be skipped during 
	 * 		tree building.
	 * @return object of type CLearnLessonTree
	 */
	public static function GetTree (
		$lessonId,
		$arOrder = array ('EDGE_SORT' => 'asc'),
		$arFilter = array(),
		$publishProhibitionMode = true
	);


	/**
	 * Link two lessons from $parentLessonId to $childLessonId
	 *
	 * @param int $parentLessonId
	 * @param int $childLessonId
	 * @param array of properties of the link. Currently available properties:
	 *    - 'SORT', integer
	 * All properties must be set.
	 *
	 * @throws Exception LearnException with code error bit set LearnException::EXC_ERR_GR_LINK
	 */
	public static function RelationAdd ($parentLessonId, $childLessonId, $arProperties);


	/**
	 * Update parametres of relation between two lessons
	 *
	 * @param int $parentLessonId
	 * @param int $childLessonId
	 * @param array of properties of the link. Currently available properties:
	 *    - 'SORT', integer
	 *
	 * @throws Exception LearnException with code error bit set LearnException::EXC_ERR_GR_SET_PROPERTY
	 */
	public static function RelationUpdate ($parentLessonId, $childLessonId, $arProperties);


	/**
	 * Get parametres of relation between two lessons
	 *
	 * @param int $parentLessonId
	 * @param int $childLessonId
	 * @return array of properties of the link. Currently available properties:
	 *    - 'SORT', integer
	 *
	 * @throws Exception LearnException with code error bit set LearnException::EXC_ERR_GR_GET_PROPERTY
	 */
	public static function RelationGet ($parentLessonId, $childLessonId);


	/**
	 * Remove relation from $parentLessonId to $childLessonId
	 *
	 * @param int $parentLessonId
	 * @param int $childLessonId
	 *
	 * @throws Exception LearnException with code error bit set LearnException::EXC_ERR_GR_UNLINK
	 *         if relation isn't exists => message of exception === 'EA_NOT_EXISTS'
	 */
	public static function RelationRemove ($parentLessonId, $childLessonId);


	/**
	 * Counts how much immediate childs given lesson has.
	 * 
	 * @param int id of lesson
	 * 
	 * @return int count of immediate childs for given lesson id.
	 */
	public static function CountImmediateChilds ($lessonId);


	/**
	 * Lists all pathes to given lesson. Given lesson not included in pathes.
	 * 
	 * @param int lesson id to be started from
	 * @param int/bool id of breakpoint-lesson (root lesson).
	 * It means, this lesson will be interpreted as parentless lesson.
	 * If param is false (it's by default) - than this argument will be ignored.
	 * @param int/bool id of pre-breakpoint-lesson.
	 * It means, this lesson will not be included in pathes (all childs of this
	 * lesson will be interpreted as parentless lessons).
	 * If param is false (it's by default) - than this argument will be ignored.
	 * @param array of edges to be ignored (interpreted as non-existing).
	 * array must be array of such arrays: ('PARENT_LESSON' => #id#, 'CHILD_LESSON' => #id#)
	 * 
	 * @return array of objects CLearnPath
	 */
	public static function GetListOfParentPathes ($lessonId, $breakOnLessonId = false, 
		$breakBeforeLessonId = false, $arIgnoreEdges = array());


	/**
	 * Checks for probition of publishing for given lesson in context of given course.
	 * 
	 * @param int lesson id to be checked for publish prohibition
	 * @param int lesson id in context of which check. Must corresponds to course.
	 * 
	 * @return bool true - if lesson is prohibited to be published in this course, otherwise - false.
	 */
	public static function IsPublishProhibited ($lessonId, $contextCourseLessonId);


	/**
	 * 
	 * 
	 * @param int lesson id for publish (un)prohibition
	 * @param int lesson id in context of which publish (un)prohibition will be done
	 * @param bool if true - lesson will be prohibited for publish. If false - prohibition will be removed.
	 * 
	 * @return bool if true - prohibition status changed, false - otherwise. If status not changed - it isn't error,
	 * it means that status to be setted === status, that already set for lesson.
	 */
	public static function PublishProhibitionSetTo ($lessonId, $contextCourseLessonId, $isProhibited);
}

class CLearnLesson implements ILearnLesson
{
	const GET_LIST_ALL                  = 0x0;	// List any lessons
	const GET_LIST_IMMEDIATE_CHILDS_OF  = 0x1;	// List only immediate childs of requested parent_lesson_id
	const GET_LIST_IMMEDIATE_PARENTS_OF = 0x2;	// List only immediate parents of requested parent_lesson_id

	
	// PUBLISH_PROHIBITION_PURGE_* constants can be ORed
	
	// Purge all prohibitions where given lessonId is contextCourse
	const PUBLISH_PROHIBITION_PURGE_ALL_LESSONS_IN_COURSE_CONTEXT = 0x1;
	
	// Purge all prohibitions for lessonId in all contextCourses
	const PUBLISH_PROHIBITION_PURGE_LESSON_IN_ALL_COURSE_CONTEXT  = 0x2;

	// Purge all prohibitions for given lessonId (as course, and as prohibited lesson)
	const PUBLISH_PROHIBITION_PURGE_BOTH                          = 0x3;


	final public static function Add ($arFields,
		$isCourse = false,
		$parentLessonId = true,
		$arProperties = array('SORT' => 500),
		$isCheckPermissions = true,
		$checkPermissionsForUserId = -1		// -1 means - for current logged user
	)
	{
		global $USER_FIELD_MANAGER;

		$isAccessGranted = false;

		if ($isCheckPermissions)
		{
			if (CLearnAccessMacroses::CanUserAddLessonWithoutParentLesson(
				array('user_id' => $checkPermissionsForUserId)
				)
			)
			{
				if ($parentLessonId === true)
				{
					// we don't need to link lesson to parent,
					// so permissions check is complete
					$isAccessGranted = true;
				}
				else
				{
					// We must check, is user have access to link lesson to some parent
					if (CLearnAccessMacroses::CanUserAddLessonToParentLesson (
						array(
							'parent_lesson_id' => $parentLessonId, 
							'user_id'          => $checkPermissionsForUserId
							)
						)
					)
					{
						$isAccessGranted = true;
					}
				}
			}
		}
		else
			$isAccessGranted = true;	// don't check permissions

		if ( ! $isAccessGranted )
		{
			throw new LearnException(
				'EA_ACCESS_DENIED', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}


		// If lesson is course, there is can be additional params, which must be extracted
		if ($isCourse)
		{
			// Additional fields will be removed from $arFields by this method
			$arCourseFields = self::_ExtractAdditionalCourseFields ($arFields);
		}

		if ( ! $USER_FIELD_MANAGER->CheckFields('LEARNING_LESSONS', 0, $arFields) )
			return (false);

		foreach(GetModuleEvents('learning', 'OnBeforeLessonAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		if (
			( ! isset($arFields['NAME']) )
			|| ($arFields['NAME'] == '')
		)
		{
			$lessonId = false;

			$arMsg = array(array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME")));

			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
		}
		else
			$lessonId = CLearnGraphNode::Create ($arFields);

		if ($lessonId)
		{
			$USER_FIELD_MANAGER->Update('LEARNING_LESSONS', $lessonId, $arFields);

			if ($isCourse)
			{
				// Convert lesson to course
				self::BecomeCourse ($lessonId, $arCourseFields);
			}
			else
			{
				// Link to parent lesson, if need
				if ($parentLessonId !== true)
					self::RelationAdd ($parentLessonId, $lessonId, $arProperties);
			}

			CLearnCacheOfLessonTreeComponent::MarkAsDirty();
		}

		$arFields['LESSON_ID'] = $lessonId;
		foreach(GetModuleEvents('learning', 'OnAfterLessonAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return ($lessonId);
	}


	protected static function _ExtractAdditionalCourseFields (&$arFields)
	{
		$arCourseFields = array();

		if (array_key_exists('SORT', $arFields) 
			&& ( ! array_key_exists('COURSE_SORT', $arFields))
		)
		{
			// If SORT given, but COURSE_SORT not given => COURSE_SORT = SORT
			$arFields['COURSE_SORT'] = $arFields['SORT'];

			// So, if both SORT and COURSE_SORT are exists => SORT ignored.
		}

		// We must unset course-related fields
		if (array_key_exists('SORT', $arFields))
			unset ($arFields['SORT']);

		$additionalParams = array ('COURSE_SORT', 'ACTIVE_FROM', 
			'ACTIVE_TO', 'RATING', 'RATING_TYPE', 'SCORM');
		
		foreach ($additionalParams as $paramName)
		{
			if (array_key_exists($paramName, $arFields))
			{
				$arCourseFields[$paramName] = $arFields[$paramName];
				unset ($arFields[$paramName]);	// We must unset course-related fields
			}
		}

		return ($arCourseFields);
	}


	/**
	 * Canonize and checks additional params when adding course
	 * @throws LearnException with error bit set LearnException::EXC_ERR_ALL_PARAMS
	 * @return array of canonized params
	 */
	protected static function _CanonizeAndCheckAdditionalParamsForAddCourse ($arFields, $forUpdate = false)
	{
		if ( ! is_array($arFields) )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);

		$arAllowedFields = array ('COURSE_SORT', 'ACTIVE_FROM', 'ACTIVE_TO', 'RATING', 'RATING_TYPE', 'SCORM');

		if ( ! $forUpdate )
		{
			$defaultsValues = array (
				'COURSE_SORT' => 500,
				'ACTIVE_FROM' => NULL,
				'ACTIVE_TO'   => NULL,
				'RATING'      => 'N',
				'RATING_TYPE' => NULL,
				'SCORM'       => 'N'
				);

			// set defaults values, if need
			foreach ($defaultsValues as $fieldName => $defaultValue)
			{
				if ( ! array_key_exists($fieldName, $arFields) )
					$arFields[$fieldName] = $defaultValue;
			}
		}

		// check for admitted regions (do all checks only if not forUpdate mode OR in forUpdate mode and field given):

		// COURSE_SORT
		if ( ( ! $forUpdate) || array_key_exists('COURSE_SORT', $arFields) )
			self::_EnsureArgsStrictlyCastableToIntegers ($arFields['COURSE_SORT']);

		// ACTIVE_FROM
		if ( ( ! $forUpdate) || isset($arFields['ACTIVE_FROM']) )
		{
			if ( ($arFields['ACTIVE_FROM'] !== NULL)
				&& ( ! is_string($arFields['ACTIVE_FROM']) )
			)
			{
				throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);
			}
		}

		// ACTIVE_TO
		if ( ( ! $forUpdate) || isset($arFields['ACTIVE_TO']) )
		{
			if ( ($arFields['ACTIVE_TO'] !== NULL)
				&& ( ! is_string($arFields['ACTIVE_TO']) )
			)
			{
				throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);
			}
		}

		// RATING
		if ( ( ! $forUpdate) || array_key_exists('RATING', $arFields) )
		{
			if ($arFields['RATING'] === '')
				$arFields['RATING'] = NULL;

			if ( ! in_array ($arFields['RATING'], array ('Y', 'N', NULL), true) )
				throw new LearnException ('EA_PARAMS: RATING is ' . $arFields['RATING'], LearnException::EXC_ERR_ALL_PARAMS);
		}

		// RATING_TYPE
		if ( ( ! $forUpdate) || array_key_exists('RATING_TYPE', $arFields) )
		{
			if ( ($arFields['RATING_TYPE'] !== NULL)
				&&
				( ! in_array (
						$arFields['RATING_TYPE'], 
						array ('like', 'standart_text', 'like_graphic', 'standart'), 
						true)
				)
			)
			{
				throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);
			}
		}

		// SCORM
		if ( ( ! $forUpdate) || array_key_exists('SCORM', $arFields) )
		{
			if ( ! in_array ($arFields['SCORM'], array ('Y', 'N'), true) )
				throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);
		}


		// Return only exists fields (some fields may be omitted in case $forUpdate = true)
		$rc = array();
		foreach ($arAllowedFields as $fieldName)
		{
			if (array_key_exists($fieldName, $arFields))
				$rc[$fieldName] = $arFields[$fieldName];
		}

		return ($rc);
	}


	final public static function Update ($id, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		if ( isset($arFields['ACTIVE']) 
			&& ( ! is_bool($arFields['ACTIVE']) )
		)
		{
			if ($arFields['ACTIVE'] === 'Y')
				$arFields['ACTIVE'] = true;
			else
				$arFields['ACTIVE'] = false;
		}

		if ( ! $USER_FIELD_MANAGER->CheckFields('LEARNING_LESSONS', $id, $arFields) )
			return (false);

		$courseId = self::GetLinkedCourse ($id);

		// if lesson is course, extract additional fields of course
		if ($courseId !== false)
		{
			// Additional fields will be removed from $arFields by this method
			$arCourseFields = self::_ExtractAdditionalCourseFields ($arFields);
		}

		foreach(GetModuleEvents('learning', 'OnBeforeLessonUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		if (
			array_key_exists('NAME', $arFields)
			&& ($arFields['NAME'] == '')
		)
		{
			$lessonId = false;

			$arMsg = array(array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME")));

			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);

			return(false);
		}

		$USER_FIELD_MANAGER->Update('LEARNING_LESSONS', $id, $arFields);

		// Update main lesson data
		CLearnGraphNode::Update ($id, $arFields);

		// If lesson is course, update course-specific data
		if ($courseId !== false)
		{
			// LearnException will be throwed on invalid params
			$arCourseFields = self::_CanonizeAndCheckAdditionalParamsForAddCourse ($arCourseFields, true);	// forUpdate = true

			$arFieldsToDb = array();

			if (array_key_exists('COURSE_SORT', $arCourseFields))
				$arFieldsToDb['SORT'] = "'" . (int) ($arCourseFields['COURSE_SORT'] + 0) . "'";

			if (array_key_exists('ACTIVE_FROM', $arCourseFields))
			{
				if (($arCourseFields['ACTIVE_FROM'] === NULL) || ($arCourseFields['ACTIVE_FROM'] === ''))
					$arFieldsToDb['ACTIVE_FROM'] = 'NULL';
				else
					$arFieldsToDb['ACTIVE_FROM'] = $DB->CharToDateFunction($arCourseFields['ACTIVE_FROM']);
			}

			if (array_key_exists('ACTIVE_TO', $arCourseFields))
			{
				if (($arCourseFields['ACTIVE_TO'] === NULL) || ($arCourseFields['ACTIVE_TO'] === ''))
					$arFieldsToDb['ACTIVE_TO'] = 'NULL';
				else
					$arFieldsToDb['ACTIVE_TO'] = $DB->CharToDateFunction($arCourseFields['ACTIVE_TO']);
			}

			if (array_key_exists('RATING', $arCourseFields))
				$arFieldsToDb['RATING'] = "'" . $DB->ForSql($arCourseFields['RATING']) . "'";

			if (array_key_exists('RATING_TYPE', $arCourseFields))
			{
				if ($arCourseFields['RATING_TYPE'] === NULL)
					$arFieldsToDb['RATING_TYPE'] = 'NULL';
				else
					$arFieldsToDb['RATING_TYPE'] = "'" . $DB->ForSql($arCourseFields['RATING_TYPE']) . "'";
			}

			if (array_key_exists('SCORM', $arCourseFields))
				$arFieldsToDb['SCORM'] = "'" . $DB->ForSql($arCourseFields['SCORM']) . "'";

			// Does need update for some fields?
			if (count($arFieldsToDb) > 0)
			{
				$rc = $DB->Update ('b_learn_course', $arFieldsToDb,
					"WHERE ID='" . (int) ($courseId + 0) . "'", __LINE__, false,
					false);	// we must halt on errors due to bug in CDatabase::Update();

				// reload cache of LINKED_LESSON_ID -> COURSE_ID
				self::GetCourseToLessonMap_ReloadCache();

				/**
				 * This code will be useful after bug in CDatabase::Update()
				 * and CDatabase::Insert() will be solved and $ignore_errors setted
				 * to true in Insert()/Update() call above.
				 */
				if ($rc === false)
					throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);
			}
		}

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();

		foreach(GetModuleEvents('learning', 'OnAfterLessonUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields, $id));
	}


	protected static function _funcDelete_ParseOptions($lesson_id)
	{
		$simulate = false;	// don't simulate by default
		$check_permissions = true;	// check rights by default
		$user_id = -1;	// -1 means 'current logged user'

		if (is_array($lesson_id))
		{
			// Parse options
			$options = CLearnSharedArgManager::StaticParser(
				$lesson_id,
				array(
					'lesson_id' => array(
						'type'          => 'strictly_castable_to_integer',
						'mandatory'     => true
						),
					'simulate' => array(
						'type'          => 'boolean',
						'mandatory'     => false,
						'default_value' => $simulate
						),
					'check_permissions' => array(
						'type'          => 'boolean',
						'mandatory'     => false,
						'default_value' => $check_permissions
						),
					'user_id' => array(
						'type'          => 'strictly_castable_to_integer',
						'mandatory'     => false,
						'default_value' => $user_id
						)
					)
				);

			$lesson_id         = $options['lesson_id'];
			$simulate          = $options['simulate'];
			$check_permissions = $options['check_permissions'];
			$user_id           = $options['user_id'];
		}
		else
			$lesson_id = (int) $lesson_id;

		if ($check_permissions)
		{
			if ($user_id === -1)
			{
				global $USER;

				if ( ! (is_object($USER) && method_exists($USER, 'GetID')) )
				{
					throw new LearnException(
						'EA_OTHER: $USER isn\'t available.', 
						LearnException::EXC_ERR_ALL_GIVEUP 
						| LearnException::EXC_ERR_ALL_LOGIC);
				}

				$user_id = (int) $USER->GetID();
			}
		}

		return (array($lesson_id, $simulate, $check_permissions, $user_id));
	}


	final public static function DeleteRecursiveLikeHardlinks ($in_data)
	{
		list ($root_lesson_id, $simulate, $check_permissions, $user_id) = 
			self::_funcDelete_ParseOptions($in_data);

		// list of lessons, which are candidates to be removed
		$arCandidatesToRemove = array();

		// Build list of all descendants (excluded duplicated)
		$oTree = self::GetTree($root_lesson_id);
		$arDescendantsList = $oTree->GetLessonsIdListInTree();

		// Transform list: add list of immediate parents to every candidate
		foreach ($arDescendantsList as $lesson_id)
		{
			$arParents = array();
			$arEdges = self::ListImmediateParents($lesson_id);

			foreach ($arEdges as $arEdgeData)
				$arParents[] = (int) $arEdgeData['PARENT_LESSON'];

			$arCandidatesToRemove[(int) $lesson_id] = $arParents;
		}

		// Now, move out parents of root lesson, because they mustn't be checked below.
		$arCandidatesToRemove[$root_lesson_id] = array();

		// Withdraw lessons, which has ancestors not among candidates to be removed
		do
		{
			$lessonsWithdrawn = 0;	// count of withdrawn lessons

			foreach ($arCandidatesToRemove as $lesson_id => $arParents)
			{
				// Check that all parents are from candidates to be removed;
				// otherwise $lesson_id must be withdrew from candidates
				foreach ($arParents as $parent_lesson_id)
				{
					if ( ! array_key_exists((int) $parent_lesson_id, $arCandidatesToRemove) )
					{
						unset($arCandidatesToRemove[(int) $lesson_id]);
						$lessonsWithdrawn++;
						break;	// we don't need to check other parents for this lesson anymore
					}
				}
			}
		}
		while ($lessonsWithdrawn > 0);

		// Now, broke edges and remove lessons.
		// Broke edges to lessons in $arCandidatesToRemove list only.
		foreach ($arCandidatesToRemove as $lesson_id => $arParents)
		{
			try
			{
				self::Delete(
					array(
						'lesson_id'         => $lesson_id,
						'simulate'          => $simulate,
						'check_permissions' => $check_permissions,
						'user_id'           => $user_id
						)
					);
			}
			catch (LearnException $e)
			{
				if ($e->GetCode() === LearnException::EXC_ERR_LL_UNREMOVABLE_CL)
					;	// course cannot be removed - ignore this error
				elseif ($e->GetCode() === LearnException::EXC_ERR_ALL_ACCESS_DENIED)
				{
					// if lesson not exists - ignore error (lesson to be deleted is already removed)
					$rsLesson = self::GetListUni(
						array(),
						array('LESSON_ID' => $lesson_id, 'CHECK_PERMISSIONS' => 'N'),
						array('LESSON_ID'),
						self::GET_LIST_ALL
					);

					if ( ! $rsLesson->fetch() )
					{
						;	// ignore this situation, it's OK
					}
					else
					{
						// bubble exception
						throw new LearnException ($e->GetMessage(), $e->GetCode());
					}
				}
				else
				{
					// bubble exception
					throw new LearnException ($e->GetMessage(), $e->GetCode());
				}
			}
		}
	}

	
	final public static function Delete ($lesson_id)
	{
		global $USER_FIELD_MANAGER;

		list ($lesson_id, $simulate, $check_permissions, $user_id) = 
			self::_funcDelete_ParseOptions($lesson_id);

		if ($check_permissions)
		{
			$oAccess = CLearnAccess::GetInstance($user_id);
			if ( ! $oAccess->IsLessonAccessible($lesson_id, CLearnAccess::OP_LESSON_REMOVE) )
			{
				throw new LearnException(
					'EA_ACCESS_DENIED', 
					LearnException::EXC_ERR_ALL_ACCESS_DENIED);
			}
		}

		// Parents and childs of the lesson
		$arNeighboursEdges = self::ListImmediateNeighbours ($lesson_id);

		// precache rights for lesson
		if ($check_permissions)
		{
			$IsLessonAccessibleFor_OP_LESSON_UNLINK_DESCENDANTS =
				$oAccess->IsLessonAccessible($lesson_id, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS);
			$IsLessonAccessibleFor_OP_LESSON_UNLINK_FROM_PARENTS =
				$oAccess->IsLessonAccessible($lesson_id, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS);
		}

		foreach(GetModuleEvents('learning', 'OnBeforeLessonDelete', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($lesson_id));

		foreach ($arNeighboursEdges as $arEdge)
		{
			$child_lesson_id  = (int) $arEdge['CHILD_LESSON'];
			$parent_lesson_id = (int) $arEdge['PARENT_LESSON'];
			if ($check_permissions)
			{
				$IsLessonAccessible = false;

				if ($child_lesson_id === $lesson_id)
				{
					// if we will be remove edge to parent - use precached rights for OP_LESSON_UNLINK_FROM_PARENTS
					$IsLessonAccessible = $IsLessonAccessibleFor_OP_LESSON_UNLINK_FROM_PARENTS
						&& $oAccess->IsLessonAccessible($parent_lesson_id, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS);
				}
				elseif ($parent_lesson_id === $lesson_id)
				{
					// if we will be remove edge to child - use precached rights for OP_LESSON_UNLINK_DESCENDANTS
					$IsLessonAccessible = $IsLessonAccessibleFor_OP_LESSON_UNLINK_DESCENDANTS
						&& $oAccess->IsLessonAccessible($child_lesson_id, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS);
				}
				else
				{
					throw new LearnException(
						'EA_FATAL: $lesson_id (' . $lesson_id 
							. ') not equal to one of: $child_lesson_id (' 
							. $child_lesson_id . '), $parent_lesson_id (' 
							. $parent_lesson_id . ')', 
						LearnException::EXC_ERR_ALL_LOGIC 
						| LearnException::EXC_ERR_ALL_GIVEUP);
				}

				if ( ! $IsLessonAccessible )
				{
					throw new LearnException(
						'EA_ACCESS_DENIED', 
						LearnException::EXC_ERR_ALL_ACCESS_DENIED);
				}

				if ($simulate === false)
					self::RelationRemove ($parent_lesson_id, $child_lesson_id);
			}
		}

		$linkedCourseId = self::GetLinkedCourse ($lesson_id);

		// If lesson is course, remove course
		if ($linkedCourseId !== false)
		{
			global $DB;

			if ($simulate === false)
			{
				if ( ! $DB->Query("DELETE FROM b_learn_course_site WHERE COURSE_ID = " . (int) $linkedCourseId, true) )
					throw new LearnException ( 'EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

				$rc = self::CourseBecomeLesson ($linkedCourseId);

				// if course cannot be converted to lesson - don't remove lesson
				if ($rc === false)
				{
					throw new LearnException (
						'EA_OTHER: lesson is unremovable because linked course is in use.', 
						LearnException::EXC_ERR_LL_UNREMOVABLE_CL);
				}

				// reload cache of LINKED_LESSON_ID -> COURSE_ID
				self::GetCourseToLessonMap_ReloadCache();

				if (CModule::IncludeModule("search"))
				{
					CSearch::DeleteIndex("learning", false, "C" . $linkedCourseId);
					CSearch::DeleteIndex("learning", "C" . $linkedCourseId);
				}
			}
		}

		// And remove lesson
		if ($simulate === false)
		{
			global $DB;

			$r = $DB->Query(
				"SELECT PREVIEW_PICTURE, DETAIL_PICTURE 
				FROM b_learn_lesson 
				WHERE ID = " . (int) $lesson_id, 
				true);

			if ($r === false)
			{
				throw new LearnException(
					'EA_SQLERROR', 
					LearnException::EXC_ERR_ALL_GIVEUP);
			}

			$arRes = $r->Fetch();
			if ( ! $arRes )
			{
				throw new LearnException(
					'EA_SQLERROR', 
					LearnException::EXC_ERR_ALL_GIVEUP);
			}

			CFile::Delete($arRes['PREVIEW_PICTURE']);
			CFile::Delete($arRes['DETAIL_PICTURE']);

			// Remove questions
			$q = CLQuestion::GetList(
				array(), 
				array('LESSON_ID' => $lesson_id)
				);
			while($arQ = $q->Fetch())
			{
				if ( ! CLQuestion::Delete($arQ['ID']) )
				{
					throw new LearnException(
						'EA_QUESTION_NOT_REMOVED', 
						LearnException::EXC_ERR_ALL_GIVEUP);
				}
			}

			CLearnGraphNode::Remove($lesson_id);

			$USER_FIELD_MANAGER->delete('LEARNING_LESSONS', $lesson_id);

			CLearnCacheOfLessonTreeComponent::MarkAsDirty();

			CEventLog::add(array(
				'AUDIT_TYPE_ID' => 'LEARNING_REMOVE_ITEM',
				'MODULE_ID'     => 'learning',
				'ITEM_ID'       => 'L #' . $lesson_id,
				'DESCRIPTION'   => 'lesson removed'
			));

			if (CModule::IncludeModule('search'))
			{
				CSearch::DeleteIndex('learning', false, 'L' . $lesson_id);
				CSearch::DeleteIndex('learning', 'L' . $lesson_id);
			}
		}

		if ($simulate === false)
		{
			foreach(GetModuleEvents('learning', 'OnAfterLessonDelete', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($lesson_id));
		}
	}


	final public static function GetByID($id)
	{
		return (self::GetList(array(), array('LESSON_ID' => $id)));
	}


	final public static function GetByIDAsArr($id)
	{
		global $DB;

		$arData = CLearnGraphNode::GetByID($id);

		// If lesson is course - get additional data
		$courseId = self::GetLinkedCourse ($id);
		if ($courseId !== false)
		{
			$rc = $DB->Query (
				"SELECT SORT, ACTIVE_FROM, ACTIVE_TO, RATING, RATING_TYPE, SCORM
				FROM b_learn_course
				WHERE ID = '" . (int) ($courseId + 0) . "'",
				true	// ignore errors
				);

			if ($rc === false)
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

			$arCourseData = $rc->Fetch();
			if ( ($arCourseData === false) || ( ! isset($arCourseData['SORT']) ) )
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

			$arData = array_merge($arData, $arCourseData);
		}

		// convert return data to expected form
		if ( isset($arData['ACTIVE']) 
			&& is_bool($arData['ACTIVE'])
		)
		{
			if ($arData['ACTIVE'])
				$arData['ACTIVE'] = 'Y';
			else
				$arData['ACTIVE'] = 'N';
		}

		$arData['LESSON_ID'] = $arData['ID'];

		return ($arData);
	}


	final public static function GetLinkedCourse ($lessonId)
	{
		$arMap = self::GetCourseToLessonMap();

		if ( ! isset($arMap['L' . $lessonId]) )
			return (false);	// no corresponded course

		// return id of corresponded course
		return ($arMap['L' . $lessonId]);
	}


	/**
	 * This function is for internal use only. It's not a part of public API.
	 * 
	 * @access private
	 */
	final public static function GetCourseToLessonMap($bRefreshCache = false)
	{
		static $arMap = array();
		$bCacheHit = false;
		static $ttl = 1800;	// seconds
		static $cacheId = 'fixed_cache_id';
		static $cachePath = '/learning/coursetolessonmap/';

		$oCache = new CPHPCache();

		// Try to load from cache only if cache isn't dirty
		if ( ! $bRefreshCache )
		{
			if ($oCache->InitCache($ttl, $cacheId, $cachePath))
			{
				$arCached = $oCache->GetVars();
				if (isset($arCached['arMap']) && is_array($arCached['arMap']))
				{
					$arMap = $arCached['arMap'];
					$bCacheHit = true;
				}
			}
		}

		// Reload map from DB on cache miss or when cache is dirty
		if (( ! $bCacheHit ) || $bRefreshCache)
		{
			$oCache->CleanDir($cachePath);

			$arMap = self::GetCourseToLessonMap_LoadFromDB();
			$oCache->InitCache($ttl, $cacheId, $cachePath);
			$oCache->StartDataCache($ttl, $cacheId, $cachePath);
			$oCache->EndDataCache(array('arMap' => $arMap));
		}

		return ($arMap);
	}


	protected static function GetCourseToLessonMap_ReloadCache()
	{
		$bRefreshCache = true;
		self::GetCourseToLessonMap($bRefreshCache);
	}


	protected static function GetCourseToLessonMap_LoadFromDB()
	{
		global $DB;
		$arMap = array();

		$rc = $DB->Query (
			"SELECT ID, LINKED_LESSON_ID
			FROM b_learn_course
			WHERE 1 = 1",
			true	// ignore errors
			);

		if ($rc === false)
		{
			throw new LearnException (
				'EA_SQLERROR', 
				LearnException::EXC_ERR_ALL_GIVEUP);
		}

		while ($arData = $rc->Fetch())
		{
			// skip invalid elements
			if ( ($arData['ID'] <= 0) || ($arData['LINKED_LESSON_ID'] <= 0) )
				continue;

			$arMap['C' . $arData['ID']]               = (int) $arData['LINKED_LESSON_ID'];
			$arMap['L' . $arData['LINKED_LESSON_ID']] = (int) $arData['ID'];
		}

		return ($arMap);
	}


	/**
	 * WARNING: don't use this method, it's for internal use only
	 * 
	 * Convert lesson to course (lesson will stay exists, but new course 
	 * binded to lesson will be created)
	 * 
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Update() in main
	 * module (version info:
	 *    define("SM_VERSION","11.0.12");
	 *    define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param int $lessonId
	 * @param array of pairs field => value for course additional params. Allowed are:
	 *        - SORT or COURSE_SORT: integer, 500 by default, sort order of courses. CORSE_SORT overrides SORT
	 *        - ACTIVE_FROM: datetime, NULL by default
	 *        - ACTIVE_TO: datetime, NULL by default
	 *        - RATING: string (1 char), 'N' by default
	 *        - RATING_TYPE: string, allowed values: NULL, "like", "standart_text", 
	 *           "like_graphic", "standart", NULL by default
	 *        - SCORM: string (1 char), 'N' by default
	 * 
	 * @return int course id
	 * 
	 * @access private
	 */
	protected static function BecomeCourse ($lessonId, $arFields)
	{
		global $DB;

		self::_EnsureArgsStrictlyCastableToIntegers ($lessonId);

		// LearnException will be throwed on invalid params
		$arCourseFields = self::_CanonizeAndCheckAdditionalParamsForAddCourse ($arFields);

		$ACTIVE_FROM = $ACTIVE_TO = 'NULL';

		if (($arCourseFields['ACTIVE_FROM'] !== NULL) && ($arCourseFields['ACTIVE_FROM'] !== ''))
			$ACTIVE_FROM = $DB->CharToDateFunction($arCourseFields['ACTIVE_FROM']);

		if (($arCourseFields['ACTIVE_TO'] !== NULL) && ($arCourseFields['ACTIVE_TO'] !== ''))
			$ACTIVE_TO = $DB->CharToDateFunction($arCourseFields['ACTIVE_TO']);

		$arFieldsToDb = array(
			'LINKED_LESSON_ID' => "'" . (int) ($lessonId + 0) . "'",
			'SORT'             => "'" . (int) ($arCourseFields['COURSE_SORT'] + 0) . "'",
			'ACTIVE_FROM'      => $ACTIVE_FROM,
			'ACTIVE_TO'        => $ACTIVE_TO,
			'RATING'           => "'" . $DB->ForSql($arCourseFields['RATING']) . "'",
			'RATING_TYPE'      => ( ($arCourseFields['RATING_TYPE'] === NULL) ? 'NULL' : ("'" . $DB->ForSql($arCourseFields['RATING_TYPE']) . "'") ),
			'SCORM'            => "'" . $DB->ForSql($arCourseFields['SCORM']) . "'"
			);

		$rc = $DB->Insert ('b_learn_course',
								$arFieldsToDb,
								__LINE__,		// $error_position
								false,			// $debug
								"",				// $exist_id
								false			// $ignore_errors, we must halt on errors due to bug in CDatabase::Insert();
								);

		// reload cache of LINKED_LESSON_ID -> COURSE_ID
		self::GetCourseToLessonMap_ReloadCache();

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();

		/**
		 * This code will be useful after bug in CDatabase::Update()
		 * and CDatabase::Insert() will be solved and $ignore_errors setted
		 * to true in Insert()/Update() call above.
		 */
		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		return ( (int) $rc);	// returns course_id
	}


	/**
	 * WARNING: don't use this method, it's for internal use only
	 * 
	 * Convert course to non-course lesson (course will be removed,
	 * but lesson will stay exists)
	 * 
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Update() in main
	 * module (version info:
	 *    // define("SM_VERSION","11.0.12");
	 *    // define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param int $courseId (returned by GetLinkedCourse($lessonId) )
	 * 
	 * @access private
	 */
	protected static function CourseBecomeLesson ($courseId)
	{
		global $DB;

		self::_EnsureArgsStrictlyCastableToIntegers ($courseId);

		$linkedLessonId = CCourse::CourseGetLinkedLesson ($courseId);
		if ($linkedLessonId === false)
		{
			return false;
		}

		// Check certificates (if exists => forbid removing course)
		$certificate = CCertification::GetList(Array(), Array("COURSE_ID" => $courseId, 'CHECK_PERMISSIONS' => 'N'));
		if ( ($certificate === false) || ($certificate->GetNext()) )
			return false;

		// Remove tests
		$tests = CTest::GetList(Array(), Array("COURSE_ID" => $courseId));
		if ($tests  === false)
			return (false);

		while ($arTest = $tests->Fetch())
		{
			if ( ! CTest::Delete($arTest["ID"]) )
				return false;
		}

		// Remove all prohibitions for lessons in context of course to be removed
		// and remove prohibitions for course to be removed in context of all other courses
		self::PublishProhibitionPurge(
			$linkedLessonId, 
			self::PUBLISH_PROHIBITION_PURGE_ALL_LESSONS_IN_COURSE_CONTEXT
			| self::PUBLISH_PROHIBITION_PURGE_LESSON_IN_ALL_COURSE_CONTEXT
			);

		$rc = $DB->Query (
			"DELETE FROM b_learn_course
			WHERE ID=" . (string) ((int) $courseId),
			true	// $ignore_errors
			);

		// reload cache of LINKED_LESSON_ID -> COURSE_ID
		self::GetCourseToLessonMap_ReloadCache();

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();

		/**
		 * This code will be useful after bug in CDatabase::Update()
		 * and CDatabase::Insert() will be solved and $ignore_errors setted
		 * to true in Insert()/Update() call above.
		 */
		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		// If data not updated
		if ($rc === 0)
			throw new LearnException ('EA_OTHER: data not updated', LearnException::EXC_ERR_ALL_GIVEUP);
	}


	/**
	 * @throws LearnException with error bit set LearnException::EXC_ERR_ALL_LOGIC
	 *         and errmessage "EA_PARAMS", if any of args isn't of integer type
	 *         or can't be strictly casted to integer.
	 */
	protected static function _EnsureArgsStrictlyCastableToIntegers ()
	{
		$args = func_get_args();
		foreach ($args as $arg)
		{
			if (
				( ! is_numeric($arg) )
				|| ( ! is_int($arg + 0) )
			)
			{
				throw new LearnException ('EA_PARAMS', 
					LearnException::EXC_ERR_ALL_LOGIC | LearnException::EXC_ERR_ALL_PARAMS);
			}
		}

		return (true);
	}


	final public static function RelationAdd ($parentLessonId, $childLessonId, $arProperties)
	{
		CLearnGraphRelation::Link ($parentLessonId, $childLessonId, $arProperties);

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	final public static function RelationUpdate ($parentLessonId, $childLessonId, $arProperties)
	{
		foreach ($arProperties as $propertyName => $value)
			CLearnGraphRelation::SetProperty ($parentLessonId, $childLessonId, $propertyName, $value);

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	final public static function RelationGet ($parentLessonId, $childLessonId)
	{
		$rc = array();
		$rc['SORT'] = CLearnGraphRelation::GetProperty ($parentLessonId, $childLessonId, 'SORT');

		return ($rc);
	}


	final public static function RelationRemove ($parentLessonId, $childLessonId)
	{
		self::PublishProhibitionPurge_OnBeforeRelationRemove ($parentLessonId, $parentLessonId);
		CLearnGraphRelation::Unlink ($parentLessonId, $childLessonId);
		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	final public static function ListImmediateParents($lessonId)
	{
		return (CLearnGraphRelation::ListImmediateParents ($lessonId));
	}


	final public static function ListImmediateChilds($lessonId)
	{
		return (CLearnGraphRelation::ListImmediateChilds ($lessonId));
	}


	final public static function ListImmediateNeighbours($lessonId)
	{
		return (CLearnGraphRelation::ListImmediateNeighbours ($lessonId));
	}


	protected static function GetListUni ($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $mode = self::GET_LIST_ALL, $lessonId = -1, $arNavParams = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity('LEARNING_LESSONS', 'TL.ID');
		$obUserFieldsSql->SetSelect($arSelectFields);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$bReplaceCourseId = false;
		if (isset($arFilter['#REPLACE_COURSE_ID_TO_ID']))
		{
			$bReplaceCourseId = true;
			unset($arFilter['#REPLACE_COURSE_ID_TO_ID']);
		}

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);

		// For ordering
		$arMap = array(
			'lesson_id'        => 'TL.ID',
			'site_id'          => 'TL.ID',			// hack for compatibility with courses in shared lists
			'name'             => 'TL.NAME',
			'code'             => 'TL.CODE',
			'active'           => 'TL.ACTIVE',
			'created'          => 'TL.DATE_CREATE',	// 'created' was in previous code, perhaps for back compatibility
			'date_create'      => 'TL.DATE_CREATE',
			'created_by'       => 'TL.CREATED_BY',
			'timestamp_x'      => 'TL.TIMESTAMP_X',
			'course_id'        => 'TC.ID',
			'course_sort'      => 'TC.SORT',
			'active_from'      => 'TC.ACTIVE_FROM',

			// ! This will be overrided below to TLE.SORT in case of self::GET_LIST_IMMEDIATE_CHILDS_OF
			'sort'             => 'TC.SORT',
			'linked_lesson_id' => 'TC.LINKED_LESSON_ID'

			// This element is dynamically added below for case of self::GET_LIST_IMMEDIATE_CHILDS_OF
			// 'edge_sort'        => 'TLE.SORT'
			);

		$allowedModes = array(
			self::GET_LIST_ALL, 
			self::GET_LIST_IMMEDIATE_CHILDS_OF, 
			self::GET_LIST_IMMEDIATE_PARENTS_OF,
			self::GET_LIST_IMMEDIATE_CHILDS_OF | self::GET_LIST_IMMEDIATE_PARENTS_OF
		);

		$argsCheck = is_array($arOrder)
			&& is_array($arSelectFields)
			&& in_array($mode, $allowedModes, true)
			&& self::_EnsureArgsStrictlyCastableToIntegers ($lessonId);

		if ( ! $argsCheck )
			throw new LearnException('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);

		$arFieldsMap = array(
			'LESSON_ID'         => 'TL.ID',
			'SITE_ID'           => 'CASE WHEN (1 > 0) THEN \'no site\' ELSE \'0\' END',	// hack for compatibility with courses in shared lists
			'WAS_CHAPTER_ID'    => 'TL.WAS_CHAPTER_ID',
			'KEYWORDS'          => 'TL.KEYWORDS',
			'CHILDS_CNT'        => '(SELECT COUNT(*) FROM b_learn_lesson_edges TLES WHERE TLES.SOURCE_NODE = TL.ID)',
			'IS_CHILDS'         => 'CASE WHEN (SELECT COUNT(*) FROM b_learn_lesson_edges TLES WHERE TLES.SOURCE_NODE = TL.ID) > 0 THEN \'1\' ELSE \'0\' END',
			'SORT'              => 'TC.SORT',
			'TIMESTAMP_X'       => $DB->DateToCharFunction('TL.TIMESTAMP_X'),
			'DATE_CREATE'       => $DB->DateToCharFunction('TL.DATE_CREATE'),
			'CREATED_USER_NAME' => $DB->Concat("'('", 'TU.LOGIN', "') '", 'TU.NAME', "' '", 'TU.LAST_NAME'),
			'CREATED_BY'        => 'TL.CREATED_BY',
			'ACTIVE'            => 'TL.ACTIVE',
			'NAME'              => 'TL.NAME',
			'PREVIEW_PICTURE'   => 'TL.PREVIEW_PICTURE',
			'PREVIEW_TEXT'      => 'TL.PREVIEW_TEXT',
			'PREVIEW_TEXT_TYPE' => 'TL.PREVIEW_TEXT_TYPE',
			'DETAIL_TEXT'       => 'TL.DETAIL_TEXT',
			'DETAIL_PICTURE'    => 'TL.DETAIL_PICTURE',
			'DETAIL_TEXT_TYPE'  => 'TL.DETAIL_TEXT_TYPE',
			'LAUNCH'            => 'TL.LAUNCH',
			'CODE'              => 'TL.CODE',
			'ACTIVE_FROM'       => $DB->DateToCharFunction('TC.ACTIVE_FROM'),
			'ACTIVE_TO'         => $DB->DateToCharFunction('TC.ACTIVE_TO'),
			'RATING'            => 'TC.RATING',
			'RATING_TYPE'       => 'TC.RATING_TYPE',
			'SCORM'             => 'TC.SCORM',
			'LINKED_LESSON_ID'  => 'TC.LINKED_LESSON_ID',
			'COURSE_ID'         => 'TC.ID',
			'COURSE_SORT'       => 'TC.SORT'
		);

		// filter by TIMESTAMP_X by default 
		if (count($arOrder) == 0)
			$arOrder['TIMESTAMP_X'] = 'DESC';

		$arSqlSearch = self::GetFilter($arFilter, $mode);

		if (isset($arFilter['SITE_ID']))
		{
			$arLID = array();
			
			if (is_array($arFilter['SITE_ID']))
				$arLID = $arFilter['SITE_ID'];
			else
			{
				if (strlen($arFilter['SITE_ID']) > 0)
					$arLID[] = $arFilter['SITE_ID'];
			}

			$SqlSearchLang = "''";
			foreach ($arLID as $v)
				$SqlSearchLang .= ", '" . $DB->ForSql($v) . "'";
		}

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
			$arSqlSearch[] = "(".$r.")";

		$sqlSearch = '';
		foreach ($arSqlSearch as $value)
		{
			if (strlen($value) > 0)
				$sqlSearch .= ' AND ' . $value;
		}

		$modeSQL_join = $modeSQL_where = '';
		$modeSQL_defaultSortField = "TC.SORT";	// as SORT

		// Prepare SQL's joins, if $mode need it
		if ($mode & self::GET_LIST_IMMEDIATE_PARENTS_OF)
		{
			$modeSQL_join .= 
				"\nINNER JOIN b_learn_lesson_edges TLE 
				ON TLE.SOURCE_NODE = TL.ID\n";

			$modeSQL_where .= "\nAND TLE.TARGET_NODE = " . ($lessonId + 0) . "\n";

			$arFieldsMap['EDGE_SORT'] = 'TLE.SORT';
			$arFieldsMap['SORT']      = 'TLE.SORT';
		}

		if ($mode & self::GET_LIST_IMMEDIATE_CHILDS_OF)
		{
			/**
			 * GROUP BY works for MySQL, MSSQL, Oracle
			 * select a.id, a.NAME, count(b.USER_ID) as C 
			 * from b_group a, b_user_group b
			 * where a.id = b.GROUP_ID
			 * group by a.id, a.NAME
			 * order by C
			 */
			$modeSQL_join .= 
				"\nINNER JOIN b_learn_lesson_edges TLE 
				ON TLE.TARGET_NODE = TL.ID\n";

			$modeSQL_where .= "\nAND TLE.SOURCE_NODE = " . ($lessonId + 0) . "\n";

			$arMap['childs_cnt'] = 'CHILDS_CNT';
			$arMap['is_childs']  = 'IS_CHILDS';
			$arMap['edge_sort']  = 'TLE.SORT';

			// Override default sort
			$arMap['sort'] = $arMap['edge_sort'];
			$modeSQL_defaultSortField = "TLE.SORT";		// as SORT

			$arFieldsMap['EDGE_SORT'] = 'TLE.SORT';
			$arFieldsMap['SORT']      = 'TLE.SORT';
		}

		if ($bReplaceCourseId)
			$arFieldsMap['ID'] = $arFieldsMap['COURSE_ID'];

		// Select all fields by default
		if (count($arSelectFields) == 0)
			$arSelectFields = array_keys($arFieldsMap);

		// Ensure that all order fields will be selected
		foreach ($arOrder as $by => $order)
		{
			$fieldName = strtoupper($by);
			if ( ! in_array($fieldName, $arSelectFields) )
				$arSelectFields[] = $fieldName;
		}

		// Build list of fields to be selected
		$strSqlSelect = '';
		$bFirstPass = true;
		$bDefaultSortFieldSelected = false;
		foreach ($arSelectFields as $selectFieldName)
		{
			if (substr($selectFieldName, 0, 3) === 'UF_')
				continue;

			if (!$bFirstPass)
				$strSqlSelect .= ', ';
			else
				$bFirstPass = false;

			if (!isset($arFieldsMap[$selectFieldName]))
			{
				throw new LearnException(
					'EA_OTHER: UNKNOWN FIELD: ' . $selectFieldName,
					LearnException::EXC_ERR_ALL_GIVEUP);
			}

			$strSqlSelect .= $arFieldsMap[$selectFieldName] . ' AS ' . $selectFieldName;

			if (
				($selectFieldName === 'SORT')
				&& ($arFieldsMap[$selectFieldName] === $modeSQL_defaultSortField)
			)
			{
				$bDefaultSortFieldSelected = true;
			}
		}

		if ( ! $bDefaultSortFieldSelected )
		{
			if ($strSqlSelect !== '')
				$strSqlSelect .= ', ';

			$strSqlSelect .= $modeSQL_defaultSortField . ' AS SORT';
		}

		$strSqlSelect .= $obUserFieldsSql->GetSelect();

		$sqlLangConstraint = '';

		if (strlen($SqlSearchLang) > 2)
		{
			$sqlLangConstraint = "
			AND
			EXISTS
			(
				SELECT 'x' FROM b_learn_course_site TCS
				WHERE TC.ID = TCS.COURSE_ID AND TCS.SITE_ID IN (" . $SqlSearchLang . ")
			)
			";
		}

		$strSqlFrom = "FROM b_learn_lesson TL
			LEFT JOIN b_learn_course TC 
				ON TC.LINKED_LESSON_ID = TL.ID
			LEFT JOIN b_user TU 
				ON TU.ID = TL.CREATED_BY "
			. $modeSQL_join				// for getting only parents/childs, if need
			. $obUserFieldsSql->GetJoin("TL.ID")
			. " WHERE 1 = 1 "
			. $sqlLangConstraint		// filter by site IDs
			. $modeSQL_where;			// for getting only parents/childs, if need

		if ($oPermParser->IsNeedCheckPerm())
			$strSqlFrom .= " AND TL.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSqlFrom .= $sqlSearch;

		$sql = "SELECT " . $strSqlSelect . " " . $strSqlFrom;

		$arSqlOrder = array();
		foreach($arOrder as $by => $order)
		{
			$by    = strtolower($by);
			$order = strtolower($order);

			if ($order !== 'asc')
				$order = 'desc';

			if ($s = $obUserFieldsSql->getOrder(strtolower($by)))
				$arSqlOrder[] = ' ' . $s . ' ' . $order . ' ';

			if (substr($by, 0, 3) !== 'UF_')
			{
				if ( ! isset($arMap[$by]) )
				{
					throw new LearnException(
						'EA_PARAMS: unknown order by field: "' . $by . '"', 
						LearnException::EXC_ERR_ALL_PARAMS
					);
				}
			}

			$arSqlOrder[] = ' ' . $arMap[$by] . ' ' . $order . ' ';
		}

		// on duplicate first occured FIELD will be used according to function description
		DelDuplicateSort($arSqlOrder);

		$sql .= ' ORDER BY ' . implode(', ', $arSqlOrder);

		if (is_array($arNavParams) && ( ! empty($arNavParams) ) )
		{
			if (isset($arNavParams['nTopCount']) && ((int) $arNavParams['nTopCount'] > 0))
			{
				$sql = $DB->TopSql($sql, (int) $arNavParams['nTopCount']);
				$res = $DB->Query($sql, true);
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(TL.ID) as C " . $strSqlFrom);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$rc = $res->NavQuery($sql, $res_cnt['C'], $arNavParams, true);
				if ($rc === false)
					throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);
			}
		}
		else
			$res = $DB->Query($sql, true);

		if ($res === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields('LEARNING_LESSONS'));

		return ($res);
	}


	final public static function GetList ($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavParams = array())
	{
		return (self::GetListUni($arOrder, $arFilter, $arSelectFields, self::GET_LIST_ALL, -1, $arNavParams));
	}


	final public static function GetListOfImmediateChilds ($lessonId, $arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavParams = array())
	{
		return (self::GetListUni($arOrder, $arFilter, $arSelectFields, self::GET_LIST_IMMEDIATE_CHILDS_OF, $lessonId, $arNavParams));
	}


	final public static function GetListOfImmediateParents ($lessonId, $arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavParams = array())
	{
		return (self::GetListUni($arOrder, $arFilter, $arSelectFields, self::GET_LIST_IMMEDIATE_PARENTS_OF, $lessonId, $arNavParams));
	}


	final public static function GetTree (
		$lessonId,
		$arOrder = array ('EDGE_SORT' => 'asc'),
		$arFilter = array(),
		$publishProhibitionMode = true,
		$arSelectFields = array()
	)
	{
		return (new CLearnLessonTree ($lessonId, $arOrder, $arFilter, $publishProhibitionMode, $arSelectFields));
	}


	/**
	 * @access protected
	 * @throws LearnException with error bit set EXC_ERR_ALL_PARAMS 
	 */
	protected static function GetFilter($arFilter = array(), $mode)
	{
		global $DB;

		if ( ! is_array($arFilter) )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);

		$arSqlSearch = array();

		foreach ($arFilter as $key => $val)
		{
			$res = CLearnHelper::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				// for courses only
				case 'COURSE_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TC.ID', $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'COURSE_SORT':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TC.SORT', $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'EDGE_SORT':
					// edges table (TLE) available only if requested immediate childs of some parent lesson
					if ($mode & self::GET_LIST_IMMEDIATE_CHILDS_OF)
						$arSqlSearch[] = CLearnHelper::FilterCreate('TLE.SORT', $val, 'number', $bFullJoin, $cOperationType);
					else
						throw new LearnException ('EA_PARAMS: unknown field ' . $key, LearnException::EXC_ERR_ALL_PARAMS);
					break;

				case 'SORT':
					if ($mode & self::GET_LIST_IMMEDIATE_CHILDS_OF)
					{
						// edges table (TLE) available only if requested immediate childs of some parent lesson
						$arSqlSearch[] = CLearnHelper::FilterCreate('TLE.SORT', $val, 'number', $bFullJoin, $cOperationType);
					}
					else
					{
						// so, by default sort by b_learn_course.SORT (for partially backward compatibility)
						$arSqlSearch[] = CLearnHelper::FilterCreate('TC.SORT', $val, 'number', $bFullJoin, $cOperationType);
					}
					break;

				case 'LINKED_LESSON_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TC.' . $key, $val, 'number', $bFullJoin, $cOperationType, false);
					break;

				case 'CHILDS_CNT':
					$arSqlSearch[] = CLearnHelper::FilterCreate('(SELECT COUNT(*) FROM b_learn_lesson_edges TLES WHERE TLES.SOURCE_NODE = TL.ID)', $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'ACTIVE_FROM':
				case 'ACTIVE_TO':
					if (strlen($val) > 0)
					{
						$arSqlSearch[] = "(TC." . $key . " " . ($cOperationType == "N" ? "<" : ">=")
							. $DB->CharToDateFunction($DB->ForSql($val), "FULL")
							. ($cOperationType == "N" ? "" : " OR TC.ACTIVE_FROM IS NULL") 
							. ")";
					}
					break;

				case "ACTIVE_DATE":
					if(strlen($val) > 0)
					{
						$arSqlSearch[] = ($cOperationType == "N" ? " NOT" : "") 
							. "((TC.ACTIVE_TO >= " . $DB->GetNowFunction() 
							." OR TC.ACTIVE_TO IS NULL) AND (TC.ACTIVE_FROM <= " . $DB->GetNowFunction() 
							. " OR TC.ACTIVE_FROM IS NULL))";
					}
					break;

				case "DATE_ACTIVE_TO":
				case "DATE_ACTIVE_FROM":
					$arSqlSearch[] = CLearnHelper::FilterCreate("TC." . $key, $val, 'date', $bFullJoin, $cOperationType);
					break;

				case 'RATING_TYPE':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TC.' . $key, $val, 'string', $bFullJoin, $cOperationType);
					break;

				case 'RATING':
				case 'SCORM':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TC.' . $key, $val, 'string_equal', $bFullJoin, $cOperationType);
					break;

				// for all lessons
				case 'WAS_CHAPTER_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.WAS_CHAPTER_ID', $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'LESSON_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.ID', $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'CREATED_BY':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.' . $key, $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'NAME':
				case 'CODE':
				case 'LAUNCH':
				case 'DETAIL_TEXT':
				case 'DETAIL_TEXT_TYPE':
				case 'PREVIEW_TEXT':
				case 'PREVIEW_TEXT_TYPE':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.' . $key, $val, 'string', $bFullJoin, $cOperationType);
					break;

				case 'CREATED_USER_NAME':
					$arSqlSearch[] = CLearnHelper::FilterCreate($DB->Concat("'('", 'TU.LOGIN', "') '", 'TU.NAME', "' '", 'TU.LAST_NAME'), 
						$val, 'string', $bFullJoin, $cOperationType);
					break;

				case 'KEYWORDS':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.' . $key, $val, 'string', $bFullJoin, $cOperationType);
					break;

				case 'ACTIVE':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.' . $key, $val, 'string_equal', $bFullJoin, $cOperationType);
					break;

				case 'TIMESTAMP_X':
				case 'DATE_CREATE':
					$arSqlSearch[] = CLearnHelper::FilterCreate('TL.' . $key, $val, 'date', $bFullJoin, $cOperationType);
					break;

				case 'SITE_ID':
					break;

				case 'CHECK_PERMISSIONS':
				case 'CHECK_PERMISSIONS_FOR_USER_ID':
				case 'ACCESS_OPERATIONS':
					// this is meta-fields, nothing to do with them here
				break;

				default:
					if (substr($key, 0, 3) !== 'UF_')
						throw new LearnException ('EA_PARAMS: unknown field ' . $key, LearnException::EXC_ERR_ALL_PARAMS);
				break;
			}
		}

		return $arSqlSearch;
	}


	final public static function CountImmediateChilds ($lessonId)
	{
		if ( ! self::_EnsureArgsStrictlyCastableToIntegers ($lessonId) )
			throw new LearnException('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);

		global $DB;

		$rc = $DB->Query (
			"SELECT COUNT(TARGET_NODE) AS CHILDS_COUNT
			FROM b_learn_lesson_edges
			WHERE SOURCE_NODE = '" . (int) ($lessonId + 0) . "'",
			true	// ignore errors
			);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$arData = $rc->Fetch();
		if ( ($arData === false) || ( ! isset($arData['CHILDS_COUNT']) ) )
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		return ( (int) ($arData['CHILDS_COUNT'] + 0) );
	}


	/**
	 * This function is DEPRECATED
	 * 
	 * Get lesson id of lesson, previously was chapter (before convertion to new data model.
	 * 
	 * WARNING: This function is for backward compatibility of old-style 
	 * links to courses, chapters, lessons resolving in components.
	 * 
	 * Don't use it in new projects, when there is no old-style links.
	 * 
	 * @access public
	 */
	final public static function LessonIdByChapterId ($chapterId)
	{
		$rc = self::GetListUni(
			array(), 
			array(
				'WAS_CHAPTER_ID'    => $chapterId,
				'CHECK_PERMISSIONS' => 'N'
			), 
			array('LESSON_ID'), 
			self::GET_LIST_ALL
		);

		if ($rc === false)
			throw new LearnException ('EA_UNKNOWN_ERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$row = $rc->Fetch();
		if ( ! isset($row['LESSON_ID']) )
			return (false);
		else
			return ( (int) $row['LESSON_ID'] );
	}


	/**
	 * @access private
	 */
	final public static function GetListOfAncestors ($lessonId, $stopAtLessonId = false, $stopBeforeLessonId = false, $arIgnoreEdges = array())
	{
		$arAncestors = array();
		
		$arOPathes = self::GetListOfParentPathes ($lessonId, $stopAtLessonId, $stopBeforeLessonId, $arIgnoreEdges);
		foreach ($arOPathes as $oPath)
			$arAncestors = array_merge($arAncestors, array_map('intval', $oPath->GetPathAsArray()));

		array_unique($arAncestors);

		return ($arAncestors);
	}


	/**
	 * @access public
	 */
	final public static function GetListOfParentPathes ($lessonId, $breakOnLessonId = false, $breakBeforeLesson = false, $arIgnoreEdges = array())
	{
		$arPathes = array(
			array($lessonId)
			);
		$arAlreadyProcessedLessons = array($lessonId);
		if ($breakOnLessonId !== false)
		{
			// This lesson must be interpreted as parentless.
			// This behaviour can be emulated by adding to 
			// $arAlreadyProcessedLessons all immediate parents
			// of this lesson.
			$arEdges = self::ListImmediateParents($breakOnLessonId);
			foreach ($arEdges as $arEdge)
				$arAlreadyProcessedLessons[] = (int) $arEdge['PARENT_LESSON'];
		}

		if ($breakBeforeLesson !== false)
			$arAlreadyProcessedLessons[] = (int) $breakBeforeLesson;

		$arAllPathes = self::GetListOfParentPathesRecursive ($arPathes, $arAlreadyProcessedLessons, $arIgnoreEdges);

		$arObjPathes = array();
		foreach ($arAllPathes as $arPathBackward)
		{
			$arPath = array_reverse($arPathBackward);
			$o = new CLearnPath ($arPath);
			$o->PopBottom();		// remove $lessonId

			// skip empty pathes
			if ($o->Count() > 0)
				$arObjPathes[] = $o;
		}

		return ($arObjPathes);
	}


	protected static function GetListOfParentPathesRecursive ($arPathes, &$arAlreadyProcessedLessons, $arIgnoreEdges = array())
	{
		$arPathesNew = $arPathes;
		$must_be_stopped = 0x1;	// stop if no more parents available or finally cycled

		foreach ($arPathes as $key => $arPath)
		{
			$lessonId = $arPath[count($arPath) - 1];
			$arEdges = self::ListImmediateParents($lessonId);
			$arParents = array();
			foreach ($arEdges as $arEdge)
			{
				$parentLessonId = (int) $arEdge['PARENT_LESSON'];
				if ( ! in_array($parentLessonId, $arAlreadyProcessedLessons, true) )
				{
					$isEdgeIgnored = false;
					foreach ($arIgnoreEdges as $arIgnoreEdge)
					{
						if (
							($arIgnoreEdge['PARENT_LESSON'] == $arEdge['PARENT_LESSON'])
							&& ($arIgnoreEdge['CHILD_LESSON'] == $arEdge['CHILD_LESSON'])
						)
						{
							$isEdgeIgnored = true;
							break;
						}
					}

					if ( ! $isEdgeIgnored )
					{
						$arParents[] = $parentLessonId;

						// Precache already processed lesson (for prevent cycling)
						$arAlreadyProcessedLessons[] = $parentLessonId;
					}
				}
			}

			$must_be_stopped &= (int) (count($arParents) === 0);	// true evaluted to 1

			$i = 0;
			foreach ($arParents as $parentLessonId)
			{
				$parentLessonId = (int) $parentLessonId;
				if ( $i || $i++ )	// executed only for all except first lesson in $arParents
				{
					$arPathTmp     = $arPath;
					$arPathTmp[]   = $parentLessonId;
					$arPathesNew[] = $arPathTmp;
				}
				else
				{
					$arPathesNew[$key][] = $parentLessonId;
				}
			}
		}

		if ($must_be_stopped)
			return ($arPathesNew);

		return (self::GetListOfParentPathesRecursive($arPathesNew, $arAlreadyProcessedLessons, $arIgnoreEdges));
	}


	final public static function IsPublishProhibited ($in_lessonId, $in_contextCourseLessonId)
	{
		global $DB;

		self::_EnsureArgsStrictlyCastableToIntegers ($in_lessonId, $in_contextCourseLessonId);
		$lessonId              = (int) $in_lessonId;
		$contextCourseLessonId = (int) $in_contextCourseLessonId;

		$rc = $DB->Query (
			"SELECT COURSE_LESSON_ID
			FROM b_learn_publish_prohibition
			WHERE COURSE_LESSON_ID = $contextCourseLessonId
				AND PROHIBITED_LESSON_ID = $lessonId
			",
			true	// ignore errors
			);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$arData = $rc->Fetch();
		if ($arData === false)
			return (false);		// lesson isn't prohibited for publish under given course

		return (true);		// lesson is prohibited for publish
	}


	final public static function PublishProhibitionSetTo ($in_lessonId, $in_contextCourseLessonId, $in_isProhibited)
	{
		global $DB;

		self::_EnsureArgsStrictlyCastableToIntegers ($in_lessonId, $in_contextCourseLessonId);
		if ( ! is_bool($in_isProhibited) )
		{
			throw new LearnException ('EA_PARAMS: isProhibited', 
				LearnException::EXC_ERR_ALL_LOGIC | LearnException::EXC_ERR_ALL_PARAMS);
		}

		$lessonId              = (int) $in_lessonId;
		$contextCourseLessonId = (int) $in_contextCourseLessonId;

		$isProhibitedNow = self::IsPublishProhibited ($lessonId, $contextCourseLessonId);

		// Update status only if it was changed
		if ($isProhibitedNow !== $in_isProhibited)
		{
			if ($in_isProhibited)
			{
				$sql = "INSERT INTO b_learn_publish_prohibition 
				(COURSE_LESSON_ID, PROHIBITED_LESSON_ID) 
				VALUES ($contextCourseLessonId, $lessonId)";
			}
			else
			{
				$sql = "DELETE FROM b_learn_publish_prohibition
				WHERE COURSE_LESSON_ID = $contextCourseLessonId
					AND PROHIBITED_LESSON_ID = $lessonId";
			}

			$rc = $DB->Query($sql, true);

			if ($rc === false)
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

			CLearnCacheOfLessonTreeComponent::MarkAsDirty();

			return (true);	// prohibition status changed
		}

		return (false);		// prohibition status not changed
	}


	/**
	 * 
	 * @param int lesson id
	 * @param int purge mode (PUBLISH_PROHIBITION_PURGE_ALL_LESSONS_IN_COURSE_CONTEXT, 
	 * PUBLISH_PROHIBITION_PURGE_LESSON_IN_ALL_COURSE_CONTEXT, 
	 * PUBLISH_PROHIBITION_PURGE_BOTH)
	 */
	protected static function PublishProhibitionPurge ($in_lessonId, $in_purgeMode)
	{
		global $DB;

		self::_EnsureArgsStrictlyCastableToIntegers ($in_lessonId, $in_purgeMode);

		$lessonId  = (int) $in_lessonId;
		$purgeMode = (int) $in_purgeMode;

		if ( ! in_array(
				$purgeMode, 
				array(
					self::PUBLISH_PROHIBITION_PURGE_ALL_LESSONS_IN_COURSE_CONTEXT, 
					self::PUBLISH_PROHIBITION_PURGE_LESSON_IN_ALL_COURSE_CONTEXT, 
					self::PUBLISH_PROHIBITION_PURGE_BOTH	// ORed previous two elements
					), 
				true
				)
		)
		{
			throw new LearnException ('EA_PARAMS: purgeMode', 
				LearnException::EXC_ERR_ALL_LOGIC | LearnException::EXC_ERR_ALL_PARAMS);
		}

		$arSqlCondition = array();

		if ($purgeMode & PUBLISH_PROHIBITION_PURGE_ALL_LESSONS_IN_COURSE_CONTEXT)
			$arSqlCondition[] = 'COURSE_LESSON_ID = ' . $lessonId;

		if ($purgeMode & PUBLISH_PROHIBITION_PURGE_LESSON_IN_ALL_COURSE_CONTEXT)
			$arSqlCondition[] = 'PROHIBITED_LESSON_ID = ' . $lessonId;

		if (count($arSqlCondition) > 0)
		{
			$sqlCondition = implode(' OR ', $arSqlCondition);

			$rc = $DB->Query(
				"DELETE FROM b_learn_publish_prohibition
				WHERE " . $sqlCondition,
				true);

			if ($rc === false)
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);
		}

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}


	/**
	 * Cleanup publish prohibitions to be orphaned on relation remove.
	 * 
	 * @param int $parentLessonId of relation to be removed
	 * @param int $childLessonId of relation to be removed
	 */
	protected static function PublishProhibitionPurge_OnBeforeRelationRemove ($in_parentLessonId, $in_childLessonId)
	{
		global $DB;

		/*
		We must remove publish prohibition for all lessons-descendants of $in_childLessonId,
		in context of courses-ancestors of $in_parentLessonId, that are will lost link (path).


				General version of algorithm:
			1) Get list of all descendants of $in_childLessonId (include $in_childLessonId itself).
			2) Get list of publish prohibitions for lessons from step 1.
			3) Checks every prohibition, that prohibited lesson still have path 
		to courseLessonId in context of which lesson is prohibited.
		Remove prohibition, when check failed.

				Optimized version of algorithm:
			1) Get list of all ancectors (that are courses) of $in_parentLessonId (include 
		$in_parentLessonId itself).
		EXPLAINATION: when DeleteRecursiveLikeHardlinks() function will work,
		relations will be removed from top to bottom mainly. It means, that if
		we will get list descendants on each step - it will be too many lessons.
		So, we get ancestors instead.
			2) Get list of publish prohibitions in context of courses from step 1.
			3) Checks every prohibition, that prohibited lesson still have path
		to courseLessonId in context of which lesson is prohibited.
		Remove prohibition, when check failed.

				One more optimization:
			In optimized algorithm, we shouldn't exclude non-courses from ancestors list
		on step 1, because, there is no non-courses can be in table 
		b_learn_publish_prohibition. So, if we can do 
			"SELECT *
			FROM b_learn_publish_prohibition 
			WHERE COURSE_LESSON_ID IN (...list of all ancestors...)"
		and result will be as expected when ancesotrs list includes only courses.
		I'm sure, DB engine will do this job more fast, than my PHP-script excludes non-courses.

				And one more optimization:
			In step 1 of optimized algorithm we can limit tree of ancestors at $in_childLessonId
		(in case, when tree of ancestors are cycled).
		EXPLAINATION: $in_childLessonId will lost relation to parent lesson ($in_parentLessonId) only.
		It means, that all descendants of $in_childLessonId (include $in_childLessonId itself) 
		will not lost link (path) to other immediate parents of $in_childLessonId and to 
		$in_childLessonId itself. So we don't need to check descendsnts in context of $in_childLessonId
		or it's ancestors (except $in_parentLessonId and it's ancestros).

				About checking that lesson after relation 
				remove still have path (link) to some course:
			1) Get all ancestors of lesson with method self::GetListOfAncestors($lessonId, false, 
		false, $arIgnoreEdges). It will return ancestors in case, when all edges from $arIgnoreEdges
		is interpreted as non-existing.
			2) If course-lesson among this ancestors, that link will be still exists after relation removing.
		This steps will be perfomed for every pair of finded prohibitions.
		There is probability that prohibited lesson will be in few courses.
		We can optimize steps by caching ancestors for prohibited lessons.
		In spite of that probability is not good in general case, we should use cache,
		because cache hit can save very-very much time. And caching itself don't gives
		overhead for processor, it's only overheads RAM, but a little.

				So, final algorithm:
			1) Get list of all ancectors of $in_parentLessonId (include $in_parentLessonId itself). 
		Stop cycling BEFORE $in_childLessonId.
			2) Get list of publish prohibitions in context of courses from step 1.
			3) Checks every prohibition, that prohibited lesson still have path
		to courseLessonId in context of which lesson is prohibited.
		Remove prohibition, when check failed.
		*/

		// 1) Get list of all ancectors of $in_parentLessonId (include $in_parentLessonId itself). 
		// Stop cycling BEFORE $in_childLessonId.
		$arAncestors = self::GetListOfAncestors ($in_parentLessonId, false, $in_childLessonId);
		$arAncestors[] = (int) $in_parentLessonId;		// include $in_parentLessonId itself

		// convert ids to int
		$arAncestorsInt = array();
		foreach ($arAncestors as $ancestroId)
			$arAncestorsInt[] = (int) $ancestroId;

		// 2) Get list of publish prohibitions in context of courses from step 1.
		$rc = $DB->Query(
			"SELECT COURSE_LESSON_ID, PROHIBITED_LESSON_ID
			FROM b_learn_publish_prohibition
			WHERE COURSE_LESSON_ID IN (" . implode (',', $arAncestorsInt) . ")"
			, 
			true);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		// This relation will be removed, so must be ignoredm when determine 
		// future ancestors (after relation removing)
		$arIgnoreEdges = array(
			array(
				'PARENT_LESSON' => (int) $in_parentLessonId,
				'CHILD_LESSON'  => (int) $in_childLessonId
				)
			);

		$arCache_ancestorsOfLesson = array();
		while ($arData = $rc->Fetch())
		{
			$prohibitedLessonId = (int) $arData['PROHIBITED_LESSON_ID'];
			$contextLessonId    = (int) $arData['COURSE_LESSON_ID'];

			// Precache future ancestors (after relation removing) 
			// for lesson, if they are not precached yet.
			if ( ! isset($arCache_ancestorsOfLesson[$prohibitedLessonId]) )
			{
				$arCache_ancestorsOfLesson[$prohibitedLessonId] = 
					self::GetListOfAncestors(
						$prohibitedLessonId, 
						false,		// $stopAtLessonId
						false,		// $stopBeforeLessonId
						$arIgnoreEdges
						);
			}

			// Will prohibited lesson lost link to course $contextLessonId?
			if ( ! in_array($contextLessonId, $arCache_ancestorsOfLesson[$prohibitedLessonId], true) )
			{
				// Yes, this lesson will not in subpathes of $contextLessonId,
				// so accorded publish prohibition must be removed.
				self::PublishProhibitionSetTo ($prohibitedLessonId, $contextLessonId, false);
			}
		}

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();
	}
}
