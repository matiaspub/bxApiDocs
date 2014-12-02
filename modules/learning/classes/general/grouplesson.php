<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage learning
 * @copyright 2001-2013 Bitrix
 */


/*
	table definition:

	CREATE TABLE b_learn_groups_lesson (
		LEARNING_GROUP_ID int(11) NOT NULL DEFAULT '0',
		LESSON_ID int(11) NOT NULL DEFAULT '0',
		DELAY int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (LEARNING_GROUP_ID, LESSON_ID),
		KEY LESSON_ID (LESSON_ID)
	);
*/
class CLearningGroupLesson
{
	/**
	 * Creates new learning group <-> lesson pair
	 * 
	 * @param array $arFields
	 * 
	 * @return bool true/false (false - on error)
	 */
	public static function add($arFields)
	{
		global $DB;

		if ( ! self::checkFields($arFields) )
			return false;

		$delay    = (int) $arFields['DELAY'];
		$lessonId = (int) $arFields['LESSON_ID'];
		$groupId  = (int) $arFields['LEARNING_GROUP_ID'];

		$strSql = "INSERT INTO b_learn_groups_lesson (LEARNING_GROUP_ID, LESSON_ID, DELAY)
			VALUES ($groupId, $lessonId, $delay)";

		$rc = $DB->query($strSql, $bIgnoreErrors = true);

		return ($rc !== false);
	}


	public static function update($arFields)
	{
		global $DB;

		if ( ! self::checkFields($arFields) )
			return false;

		$delay    = (int) $arFields['DELAY'];
		$lessonId = (int) $arFields['LESSON_ID'];
		$groupId  = (int) $arFields['LEARNING_GROUP_ID'];

		$strSql = "UPDATE b_learn_groups_lesson 
			SET DELAY = $delay
			WHERE LEARNING_GROUP_ID = $groupId AND LESSON_ID = $lessonId
		";

		$rc = $DB->query($strSql, $bIgnoreErrors = true);

		return ($rc !== false);
	}


	/**
	 * Get list of existing learning group <-> lesson pairs
	 * 
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arNavParams
	 * 
	 * @return CDBResult
	 */
	public static function getList($arOrder, $arFilter, $arSelect = array(), $arNavParams = array())
	{
		global $DB, $USER;

		$arFields = array(
			'LEARNING_GROUP_ID' => 'LGL.LEARNING_GROUP_ID',
			'LESSON_ID'         => 'LGL.LESSON_ID',
			'DELAY'             => 'LGL.DELAY'
		);

		if (count($arSelect) <= 0 || in_array("*", $arSelect))
			$arSelect = array_keys($arFields);

		if (!is_array($arOrder))
			$arOrder = array();

		foreach ($arOrder as $by => $order)
		{
			$by = (string) $by;
			$needle = null;
			$order = strtolower($order);

			if ($order != "asc")
				$order = "desc";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = ' ' . $by . ' ' . $order . ' ';
				$needle = $by;
			}

			if (
				($needle !== null)
				&& ( ! in_array($needle, $arSelect, true) )
			)
			{
				$arSelect[] = $needle;
			}
		}

		$arSqlSelect = array();
		foreach ($arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field] . ' AS ' . $field;
		}

		if (!sizeof($arSqlSelect))
			$arSqlSelect = 'LGL.LESSON_ID AS LESSON_ID';

		$arSqlSearch = self::getFilter($arFilter);

		$strSql = "
			SELECT 
				" . implode(",\n", $arSqlSelect);

		$strFrom = "
			FROM
				b_learn_groups_lesson LGL
				" 
			. (sizeof($arSqlSearch) ? " WHERE " . implode(" AND ", $arSqlSearch) : "") . " ";

		$strSql .= $strFrom;

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $arSqlOrderCnt = count($arSqlOrder); $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		if (count($arNavParams))
		{
			if (isset($arNavParams['nTopCount']))
			{
				$strSql = $DB->TopSql($strSql, (int) $arNavParams['nTopCount']);
				$res = $DB->Query($strSql, $bIgnoreErrors = false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(LGL.ID) as C " . $strFrom);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$rc = $res->NavQuery($strSql, $res_cnt["C"], $arNavParams, $bIgnoreErrors = false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
		}
		else
		{
			$res = $DB->Query($strSql, $bIgnoreErrors = false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
		}

		return $res;
	}


	/**
	 * Removes existing learning group <-> lesson pairs
	 * 
	 * @param int $groupId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function deleteByGroup($groupId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_lesson WHERE LEARNING_GROUP_ID = " . (int) $groupId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
	}


	/**
	 * Removes existing learning group <-> lesson pairs
	 * 
	 * @param int $lessonId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function deleteByLesson($lessonId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_lesson WHERE LESSON_ID = " . (int) $lessonId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
	}


	/**
	 * Removes existing learning group <-> lesson pairs
	 * 
	 * @param int $lessonId
	 * @param int $groupId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function delete($lessonId, $groupId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_lesson 
			WHERE LESSON_ID = " . (int) $lessonId . "
				AND LEARNING_GROUP_ID = " . (int) $groupId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
	}


	public static function getDelays($learningGroupId, $arLessonsIds)
	{
		if ( ! is_array($arLessonsIds) )
			return false;

		$arLessonsIds = array_filter($arLessonsIds);

		if (empty($arLessonsIds))
			return (array());

		// fill default values
		$arDelays = array();
		foreach ($arLessonsIds as $lessonId)
			$arDelays[$lessonId] = 0;

		$rs = self::getList(
			array(),
			array(
				'LEARNING_GROUP_ID' => $learningGroupId,
				'LESSON_ID'         => $arLessonsIds
			),
			array('LESSON_ID', 'DELAY')
		);

		while ($ar = $rs->fetch())
		{
			$lessonId = (int) $ar['LESSON_ID'];

			if (isset($arDelays[$lessonId]))
				$arDelays[$lessonId] = (int) $ar['DELAY'];
		}

		return ($arDelays);
	}


	public static function setDelays($learningGroupId, $arDelays)
	{
		if ( ! is_array($arDelays) )
			return false;

		$learningGroupId = (int) $learningGroupId;

		$arLessonsIds = array();

		// first, collect lessons ids
		foreach ($arDelays as $lessonId => $delay)
			$arLessonsIds[] = (int) $lessonId;

		$arLessonsIds = array_unique(array_filter($arLessonsIds));

		// determine already registered delays in DB
		$arRegistered = array();
		if ( ! empty($arLessonsIds) )
		{
			$rs = self::getList(
				array(),
				array(
					'LEARNING_GROUP_ID' => $learningGroupId,
					'LESSON_ID'         => $arLessonsIds
				),
				array('LESSON_ID')
			);

			while ($ar = $rs->fetch())
				$arRegistered[] = (int) $ar['LESSON_ID'];
		}

		$arRegistered = array_unique(array_filter($arRegistered));

		// Do update/add
		foreach ($arDelays as $lessonId => $delay)
		{
			$arFields = array(
				'DELAY'             => abs((int)$delay),
				'LESSON_ID'         => (int) $lessonId,
				'LEARNING_GROUP_ID' => $learningGroupId
			);

			if (in_array((int)$lessonId, $arRegistered, true))
				self::update($arFields);
			else
				self::add($arFields);
		}
	}


	private static function checkFields($arFields)
	{
		global $DB;

		IncludeModuleLangFile(__FILE__);

		$arMsg = array();

		if ( ! array_key_exists('LEARNING_GROUP_ID', $arFields) )
			$arMsg[] = array("id" => "LEARNING_GROUP_ID", "text" => GetMessage("LEARNING_BAD_LEARNING_GROUP_ID"));
		else
		{
			$rs = CLearningGroup::getList(array(), array('ID' => (int) $arFields['LEARNING_GROUP_ID']), array('ID'));
			if ( ! ($rs && $rs->fetch()) )
				$arMsg[] = array("text" => GetMessage("LEARNING_BAD_LEARNING_GROUP_ID_EX"), "id" => "BAD_GROUP_ID");
		}

		if ( ! array_key_exists('LESSON_ID', $arFields) )
			$arMsg[] = array("id" => "LESSON_ID", "text" => GetMessage("LEARNING_BAD_LESSON_ID"));

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}


	private static function getFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = array();

		$arSqlSearch = array();

		foreach ($arFilter as $key => $val)
		{
			$res = CLearnHelper::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case 'LESSON_ID':
				case 'LEARNING_GROUP_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('LGL.' . $key, $val, 'number', $bFullJoin, $cOperationType);
					break;
			}
		}

		return array_filter($arSqlSearch);
	}


	public static function onAfterLearningGroupDelete($groupId)
	{
		self::deleteByGroup($groupId);
	}
}
