<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage learning
 * @copyright 2001-2013 Bitrix
 */


/*
	table definition:

	CREATE TABLE b_learn_groups_member (
		LEARNING_GROUP_ID int(11) NOT NULL DEFAULT '0',
		USER_ID int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (LEARNING_GROUP_ID, USER_ID),
		KEY USER_ID (USER_ID)
	);
*/
class CLearningGroupMember
{
	/**
	 * Creates new learning group <-> member pair
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

		$userId  = (int) $arFields['USER_ID'];
		$groupId = (int) $arFields['LEARNING_GROUP_ID'];

		$strSql = "INSERT INTO b_learn_groups_member (LEARNING_GROUP_ID, USER_ID)
			VALUES ($groupId, $userId)";

		$rc = $DB->query($strSql, $bIgnoreErrors = true);

		foreach(GetModuleEvents('learning', 'OnAfterLearningGroupMemberAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return ($rc !== false);
	}


	/**
	 * Get list of existing learning group <-> member pairs
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
			'LEARNING_GROUP_ID'  => 'LGM.LEARNING_GROUP_ID',
			'USER_ID'            => 'LGM.USER_ID'
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
			$arSqlSelect[] = 'LGM.USER_ID AS USER_ID';

		$arSqlSearch = self::getFilter($arFilter);

		$strSql = "
			SELECT 
				U.NAME AS MEMBER_NAME,
				U.LAST_NAME AS MEMBER_LAST_NAME,
				U.SECOND_NAME AS MEMBER_SECOND_NAME,
				U.LOGIN AS MEMBER_LOGIN,
				U.EMAIL AS MEMBER_EMAIL,
				" . implode(",\n", $arSqlSelect);

		$strFrom = "
			FROM
				b_learn_groups_member LGM
				LEFT JOIN b_user U ON U.ID = LGM.USER_ID
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
				$res_cnt = $DB->Query("SELECT COUNT(LGM.ID) as C " . $strFrom);
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
	 * Removes existing learning group <-> member pairs
	 * 
	 * @param int $groupId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function deleteByGroup($groupId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_member WHERE LEARNING_GROUP_ID = " . (int) $groupId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
	}


	/**
	 * Removes existing learning group <-> member pairs
	 * 
	 * @param int $userId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function deleteByUser($userId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_member WHERE USER_ID = " . (int) $userId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
	}


	/**
	 * Removes existing learning group <-> member pairs
	 * 
	 * @param int $userId
	 * @param int $groupId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function delete($userId, $groupId)
	{
		global $DB;

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups_member 
			WHERE USER_ID = " . (int) $userId . "
				AND LEARNING_GROUP_ID = " . (int) $groupId,
			$bIgnoreErrors = true
		);

		return ($rc !== false);
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
				$arMsg[] = array("text" => GetMessage("LEARNING_BAD_LEARNING_GROUP_ID_EX"), "id" => "BAD_USER_ID");
		}

		if ( ! array_key_exists('USER_ID', $arFields) )
			$arMsg[] = array("id" => "USER_ID", "text" => GetMessage("LEARNING_BAD_USER_ID"));
		else
		{
			$r = CUser::GetByID((int)$arFields["USER_ID"]);
			if ( ! ($r && $r->fetch()) )
				$arMsg[] = array("text" => GetMessage("LEARNING_BAD_USER_ID_EX"), "id" => "BAD_USER_ID");
		}

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
				case 'USER_ID':
				case 'LEARNING_GROUP_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('LGM.' . $key, $val, 'number', $bFullJoin, $cOperationType);
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
