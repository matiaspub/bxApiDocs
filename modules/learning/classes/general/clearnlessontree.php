<?php
class CLearnLessonTree
{
	protected $arTree = NULL;
	protected $arLessonsInTree = array();		// Array of ids of lessons already pushed to tree
	protected $arLessonsAsList = array();		// Lessons' tree in list mode (with depth)
	protected $arLessonsAsListOldMode = array();		// Lessons' tree in list mode (with depth) - in old compatibility mode
	protected $arPublishProhibitedLessons = array();	// Lessons that are prohibited for publish (setted only if publishProhibitionMode enabled)

	/**
	 * Build tree of lessons with the given root.
	 * 
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 * 
	 * @param integer id of root lesson
	 * @param array order
	 * @param array filter
	 * @param bool skip publish prohibited lessons in context of $rootLessonId
	 */
	public function __construct ($rootLessonId, $arOrder = null, $arFilter = array(), $publishProhibitionMode = true, $arSelectFields = array())
	{
		$this->EnsureStrictlyCastableToInt ($rootLessonId);		// throws an exception on error
		if ($arOrder === null)
			$arOrder = array ('EDGE_SORT' => 'asc');

		if (is_array($arSelectFields) && (count($arSelectFields) > 0))
		{
			$arFieldsMustBeSelected = array ('LESSON_ID', 'EDGE_SORT', 'IS_CHILDS');
			foreach ($arFieldsMustBeSelected as $fieldName)
			{
				if ( ! in_array($fieldName, $arSelectFields) )
					$arSelectFields[] = $fieldName;
			}
		}

		$publishProhibitionContext = false;

		if ($publishProhibitionMode)
		{
			$publishProhibitionContext = (int) $rootLessonId;

			global $DB;
			$rc = $DB->Query (
				"SELECT PROHIBITED_LESSON_ID
				FROM b_learn_publish_prohibition
				WHERE COURSE_LESSON_ID = $publishProhibitionContext",
				true	// ignore errors
				);

			if ($rc === false)
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

			while ($arData = $rc->Fetch())
				$this->arPublishProhibitedLessons[] = (int) $arData['PROHIBITED_LESSON_ID'];
		}

		$arCurrentPath = array($rootLessonId);
		$this->arTree = $this->BuildTreeRecursive(
			$rootLessonId, 
			$arOrder, 
			$arFilter, 
			0, 
			NULL, 
			$arSelectFields, 
			$arCurrentPath
		);
	}

	/**
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 * 
	 * @return array
	 * @example of returned array:
	 * array (
	 *    0 => array (
	 *         'LESSON_ID' => 7,
	 *         'EDGE_SORT' => 463,
	 *         ... other fields accorded to CLearnLesson::GetList();
	 *         '#childs'   => array(
	 *                           0 => array (
	 *                                'LESSON_ID' => 8,
	 *                                'EDGE_SORT' => 463,
	 *                                ... other fields accorded to CLearnLesson::GetList();
	 *                                '#childs'   => array(),
	 *                           1 => array(...),
	 *                           ...
	 *                        )
	 *         ),
	 *    1 => array (...),
	 *    ...
	 * )
	 */
	public function GetTree ()
	{
		return ($this->arTree);
	}

	/**
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 * 
	 * @return array
	 * @example of returned array:
	 * array (
	 *    0 => array (
	 *         'LESSON_ID' => 7,
	 *         'EDGE_SORT' => 463,
	 *         ... other fields accorded to CLearnLesson::GetList();
	 *         '#DEPTH_IN_TREE' => 0
	 * 
	 *    1 => array (
	 *         'LESSON_ID' => 8,
	 *         'EDGE_SORT' => 463,
	 *         ... other fields accorded to CLearnLesson::GetList();
	 *         '#DEPTH_IN_TREE' => 1,	// this is child of LESSON_ID=7, so it's deeper
	 *         ),
	 *    2 => array (...),
	 *    ...
	 * )
	 */
	public function GetTreeAsList()
	{
		return ($this->arLessonsAsList);
	}


	/**
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 */
	public function GetTreeAsListOldMode()
	{
		return ($this->arLessonsAsListOldMode);
	}


	/**
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 */
	public function GetLessonsIdListInTree()
	{
		return ($this->arLessonsInTree);
	}


	/**
	 * WARNING: tree build algorithm skips duplicated lessons, so
	 * if there is some duplicates lessons, only one of them
	 * will be in resulted tree.
	 */
	protected function BuildTreeRecursive ($rootLessonId, $arOrder, $arFilter, $depth = 0, $parentChapterId = NULL, $arSelectFields, $arRootPath)
	{
		$oPath = new CLearnPath();
		$arLessons = array();

		$CDBResult = CLearnLesson::GetListOfImmediateChilds($rootLessonId, $arOrder, $arFilter, $arSelectFields);
		while (($arData = $CDBResult->Fetch()) !== false)
		{
			// Skip lessons that are already in tree (prevent cycling)
			if ( in_array($arData['LESSON_ID'], $this->arLessonsInTree) )
				continue;

			// Skip lessons prohibited for publishing
			if (in_array( (int) $arData['LESSON_ID'], $this->arPublishProhibitedLessons, true))
				continue;

			// Path as array for current LESSON_ID
			$arCurrentLessonPath = $arRootPath;
			$arCurrentLessonPath[] = (int) $arData['LESSON_ID'];
			$oPath->SetPathFromArray($arCurrentLessonPath);
			$strUrlencodedCurrentLessonPath = $oPath->ExportUrlencoded();


			// Register lesson
			$this->arLessonsInTree[] = $arData['LESSON_ID'];
			$this->arLessonsAsList[] = array_merge(
				$arData, 
				array(
					'#DEPTH_IN_TREE' => $depth,
					'#LESSON_PATH'   => $strUrlencodedCurrentLessonPath
				)
			);

			// hack: we don't know yet, what index name must be for element in array.
			// And we must preserve order in array elements (for compatibility).
			// But we will know index name after BuildTreeRecursive will be called, which
			// adds to array new elements. So create bother elements, and after remove unneeded.
			$this->arLessonsAsListOldMode['LE' . $arData['LESSON_ID']] = array();
			$this->arLessonsAsListOldMode['CH' . $arData['LESSON_ID']] = array();

			$item = $arData;
			$item['#childs'] = array();
			$lessonType_oldDataModel = 'LE';

			if ($arData['IS_CHILDS'])
			{
				$lessonType_oldDataModel = 'CH';
				$item['#childs'] = $this->BuildTreeRecursive (
					$arData['LESSON_ID'], 
					$arOrder, 
					$arFilter, 
					$depth + 1, 
					$arData['LESSON_ID'], 
					$arSelectFields,
					$arCurrentLessonPath
				);
				
				// It still can be zero childs due to $arFilter, publish prohibition or prevent cycling instead of non-zero $arData['IS_CHILDS']
				if (count($item['#childs']) == 0)
					$lessonType_oldDataModel = 'LE';
			}

			// remove unneeded element caused by hack above			
			if ($lessonType_oldDataModel === 'LE')
				unset($this->arLessonsAsListOldMode['CH' . $arData['LESSON_ID']]);
			else
				unset($this->arLessonsAsListOldMode['LE' . $arData['LESSON_ID']]);
			
			$this->arLessonsAsListOldMode[$lessonType_oldDataModel . $arData['LESSON_ID']] = array_merge(
				$arData, 
				array(
					'ID'           => $arData['LESSON_ID'],
					'CHAPTER_ID'   => $parentChapterId,
					'SORT'         => $arData['EDGE_SORT'],
					'TYPE'         => $lessonType_oldDataModel,
					'DEPTH_LEVEL'  => $depth + 1,
					'#LESSON_PATH' => $strUrlencodedCurrentLessonPath
					)
				);

			$arLessons[] = $item;
		}

		return ($arLessons);
	}

	protected function EnsureStrictlyCastableToInt ($i)
	{
		if ( ( ! is_numeric($i) )
			|| ( ! is_int($i + 0) )
		)
		{
			throw new LearnException ('Non-strictly casts to integer: ' . htmlspecialcharsbx($i), 
				LearnException::EXC_ERR_ALL_PARAMS 
				| LearnException::EXC_ERR_ALL_LOGIC);
		}
	}
}