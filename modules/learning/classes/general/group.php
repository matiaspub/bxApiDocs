<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage learning
 * @copyright 2001-2013 Bitrix
 */


/*
	$arFields:

	ID int(11) unsigned not null auto_increment,
	ACTIVE char(1) not null default 'Y',
	TITLE varchar(255) not null default ' ',
	CODE varchar(50) NULL DEFAULT NULL,
	SORT int(11) not null default '500',
	ACTIVE_FROM datetime,
	ACTIVE_TO datetime,
	COURSE_LESSON_ID INT NOT NULL,
	PRIMARY KEY(ID)
*/
class CLearningGroup
{
	/**
	 * Creates new learning group
	 * 
	 * @param array $arFields
	 * 
	 * @return mixed (int) id of just created group OR (bool) false on error
	 */
	public static function add($arFields)
	{
		global $DB, $USER_FIELD_MANAGER, $APPLICATION;

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if ( ! self::CheckFields($arFields) )
			return false;

		if ( ! $USER_FIELD_MANAGER->CheckFields("LEARNING_LGROUPS", 0, $arFields) )
			return (false);

		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);
		
		foreach(GetModuleEvents('learning', 'OnBeforeLearningGroupAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				IncludeModuleLangFile(__FILE__);

				$errmsg = GetMessage("LEARNING_GROUP_ADD_UNKNOWN_ERROR");
				$errno  = 'LEARNING_GROUP_ADD_UNKNOWN_ERROR';

				if ($ex = $APPLICATION->getException())
				{
					$errmsg = $ex->getString();
					$errno  = $ex->getId();
				}

				$e = new CAdminException(array('text' => $errmsg, 'id' => $errno));
				$APPLICATION->ThrowException($e);

				return false;
			}
		}

		$id = $DB->Add("b_learn_groups", $arFields, array(), "learning");
		if ($id)
			$USER_FIELD_MANAGER->Update("LEARNING_LGROUPS", $id, $arFields);

		if ($id > 0 && defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP_' . (int) ($id / 100));
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP');
		}

		foreach(GetModuleEvents('learning', 'OnAfterLearningGroupAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, &$arFields));

		return ($id);
	}


	/**
	 * Updates existing learning group
	 * 
	 * @param int $id
	 * @param array $arFields
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function update($id, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER, $APPLICATION;

		$id = (int) $id;

		if ($id < 1)
			return (false);

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if ( ! self::CheckFields($arFields, $id) )
			return false;

		if ( ! $USER_FIELD_MANAGER->CheckFields("LEARNING_LGROUPS", $id, $arFields) )
			return (false);

		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);
		
		foreach(GetModuleEvents('learning', 'OnBeforeLearningGroupUpdate', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($id, &$arFields)) === false)
			{
				IncludeModuleLangFile(__FILE__);

				$errmsg = GetMessage("LEARNING_GROUP_UPDATE_UNKNOWN_ERROR");
				$errno  = 'LEARNING_GROUP_UPDATE_UNKNOWN_ERROR';

				if ($ex = $APPLICATION->getException())
				{
					$errmsg = $ex->getString();
					$errno  = $ex->getId();
				}

				$e = new CAdminException(array('text' => $errmsg, 'id' => $errno));
				$APPLICATION->ThrowException($e);

				return false;
			}
		}

		$USER_FIELD_MANAGER->Update('LEARNING_LGROUPS', $id, $arFields);

		$strUpdate = $DB->PrepareUpdate("b_learn_groups", $arFields, "learning");
		$strSql = "UPDATE b_learn_groups SET " . $strUpdate . " WHERE ID=" . $id;
		$rc = $DB->queryBind($strSql, $arBinds = array(), $bIgnoreErrors = true);

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP_' . (int) ($id / 100));
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP');
		}

		foreach(GetModuleEvents('learning', 'OnAfterLearningGroupUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, &$arFields));

		return ($rc !== false);
	}


	/**
	 * Get list of existing learning groups
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
		global $DB, $USER, $USER_FIELD_MANAGER;

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("LEARNING_LGROUPS", "LG.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$arFields = array(
			'ID'               => 'LG.ID',
			'TITLE'            => 'LG.TITLE',
			'ACTIVE'           => 'LG.ACTIVE',
			'CODE'             => 'LG.CODE',
			'SORT'             => 'LG.SORT', 
			'ACTIVE_FROM'      => $DB->DateToCharFunction('LG.ACTIVE_FROM', 'FULL'),
			'ACTIVE_TO'        => $DB->DateToCharFunction('LG.ACTIVE_TO', 'FULL'),
			'COURSE_LESSON_ID' => 'LG.COURSE_LESSON_ID',
			'COURSE_TITLE'     => 'LL.NAME',
			'MEMBER_ID'        => 'LGM.USER_ID'
		);

		$arFieldsSort = $arFields;
		$arFieldsSort["ACTIVE_FROM"] = "LG.ACTIVE_FROM";
		$arFieldsSort["ACTIVE_TO"] = "LG.ACTIVE_TO";

		if (count($arSelect) <= 0 || in_array("*", $arSelect))
			$arSelect = array_diff(array_keys($arFields), array('MEMBER_ID'));
		elseif (!in_array("ID", $arSelect))
			$arSelect[] = "ID";

		if (!is_array($arOrder))
			$arOrder = array();

		foreach ($arOrder as $by => $order)
		{
			$by = (string) $by;
			$byUppercase = strtoupper($by);
			$needle = null;
			$order = strtolower($order);

			if ($order != "asc")
				$order = "desc";

			if (array_key_exists($byUppercase, $arFieldsSort))
			{
				$arSqlOrder[] = ' ' . $arFieldsSort[$byUppercase] . ' ' . $order . ' ';
				$needle = $byUppercase;
			}
			elseif ($s = $obUserFieldsSql->getOrder(strtolower($by)))
				$arSqlOrder[] = ' ' . $s . ' ' . $order . ' ';

			if (
				($needle !== null)
				&& ( ! in_array($needle, $arSelect, true) )
			)
			{
				$arSelect[] = $needle;
			}
		}

		if (
			isset($arFilter['MEMBER_ID'])
			&& ( ! in_array('MEMBER_ID', $arSelect, true) )
		)
		{
			$arSelect[] = 'MEMBER_ID';
		}

		$arSqlSelect = array();
		foreach ($arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field] . ' AS ' . $field;
		}

		if (!sizeof($arSqlSelect))
			$arSqlSelect = 'LG.ID AS ID';

		$arSqlSearch = self::getFilter($arFilter);

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
			$arSqlSearch[] = "(".$r.")";

		$strSql = "
			SELECT
				".implode(",\n", $arSqlSelect)."
				".$obUserFieldsSql->GetSelect();

		$strFrom = "
			FROM
				b_learn_groups LG
				";

		if (in_array('COURSE_TITLE', $arSelect, true))
			$strFrom .= "LEFT OUTER JOIN b_learn_lesson LL ON LL.ID = LG.COURSE_LESSON_ID \n" ;

		if (in_array('MEMBER_ID', $arSelect, true))
			$strFrom .= "LEFT JOIN b_learn_groups_member LGM ON LGM.LEARNING_GROUP_ID = LG.ID \n" ;

		$strFrom .= $obUserFieldsSql->GetJoin("LG.ID") . " "
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
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("LEARNING_LGROUPS"));
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(LG.ID) as C " . $strFrom);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("LEARNING_LGROUPS"));
				$rc = $res->NavQuery($strSql, $res_cnt["C"], $arNavParams, $bIgnoreErrors = false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
		}
		else
		{
			$res = $DB->Query($strSql, $bIgnoreErrors = false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("LEARNING_LGROUPS"));
		}

		return $res;
	}


	/**
	 * Removes existing learning group
	 * 
	 * @param int $groupId
	 * 
	 * @return bool false on error, or true - if no errors detected
	 */
	public static function delete($groupId)
	{
		global $DB, $APPLICATION, $USER_FIELD_MANAGER;

		foreach(GetModuleEvents('learning', 'OnBeforeLearningGroupDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($groupId)) === false)
			{
				IncludeModuleLangFile(__FILE__);

				$errmsg = GetMessage("LEARNING_GROUP_DELETE_UNKNOWN_ERROR");
				$errno  = 'LEARNING_GROUP_DELETE_UNKNOWN_ERROR';

				if ($ex = $APPLICATION->getException())
				{
					$errmsg = $ex->getString();
					$errno  = $ex->getId();
				}

				$e = new CAdminException(array('text' => $errmsg, 'id' => $errno));
				$APPLICATION->ThrowException($e);

				return false;
			}
		}

		$rc = $DB->Query(
			"DELETE FROM b_learn_groups WHERE ID = " . (int) $groupId,
			$bIgnoreErrors = true
		);

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP_' . (int) ($groupId / 100));
			$CACHE_MANAGER->ClearByTag('LEARNING_GROUP');
		}

		$USER_FIELD_MANAGER->delete('LEARNING_LGROUPS', $groupId);

		CEventLog::add(array(
			'AUDIT_TYPE_ID' => 'LEARNING_REMOVE_ITEM',
			'MODULE_ID'     => 'learning',
			'ITEM_ID'       => 'LG #' . $groupId,
			'DESCRIPTION'   => 'learning group removed'
		));

		foreach(GetModuleEvents('learning', 'OnAfterLearningGroupDelete', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($groupId));

		return ($rc !== false);
	}


	private static function CheckFields($arFields, $id = false)
	{
		global $DB;

		$arMsg = array();

		if ( (is_set($arFields, "TITLE") || $id === false) && strlen(trim($arFields["TITLE"])) <= 0)
			$arMsg[] = array("id"=>"TITLE", "text"=> GetMessage("LEARNING_BAD_NAME"));

		if (is_set($arFields, "ACTIVE_FROM") && strlen($arFields["ACTIVE_FROM"])>0 && (!$DB->IsDate($arFields["ACTIVE_FROM"], false, LANG, "FULL")))
			$arMsg[] = array("id"=>"ACTIVE_FROM", "text"=> GetMessage("LEARNING_BAD_ACTIVE_FROM"));

		if (is_set($arFields, "ACTIVE_TO") && strlen($arFields["ACTIVE_TO"])>0 && (!$DB->IsDate($arFields["ACTIVE_TO"], false, LANG, "FULL")))
			$arMsg[] = array("id"=>"ACTIVE_TO", "text"=> GetMessage("LEARNING_BAD_ACTIVE_TO"));

		if ($id === false)
		{
			if ( ! array_key_exists('COURSE_LESSON_ID', $arFields) )
				$arMsg[] = array("id"=>"COURSE_LESSON_ID", "text"=> GetMessage("LEARNING_BAD_COURSE_ID"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}


	private static function getFilter($arFilter)
	{
		global $DB;

		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CLearnHelper::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case 'ID':
				case 'SORT':
				case 'COURSE_LESSON_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('LG.' . $key, $val, 'number', $bFullJoin, $cOperationType);
					break;

				case 'ACTIVE':
					$arSqlSearch[] = CLearnHelper::FilterCreate('LG.' . $key, $val, 'string_equal', $bFullJoin, $cOperationType);
					break;

				case 'ACTIVE_FROM':
				case 'ACTIVE_TO':
					if ($val !== null)
						$arSqlSearch[] = CLearnHelper::FilterCreate('LG.' . $key, $val, 'date', $bFullJoin, $cOperationType);
					break;

				case 'TITLE':
				case 'CODE':
					$arSqlSearch[] = CLearnHelper::FilterCreate("LG." . $key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case 'COURSE_TITLE':
					$arSqlSearch[] = CLearnHelper::FilterCreate("LL.NAME", $val, "string", $bFullJoin, $cOperationType);
					break;

				case 'MEMBER_ID':
					$arSqlSearch[] = CLearnHelper::FilterCreate('LGM.USER_ID', $val, 'number', $bFullJoin, $cOperationType);
					break;

				default:
					if (substr($key, 0, 3) !== 'UF_')
					{
						throw new LearnException(
							'Unknown field: ' . $key, 
							LearnException::EXC_ERR_ALL_PARAMS
						);
					}
				break;
			}
		}

		return array_filter($arSqlSearch);
	}
}
