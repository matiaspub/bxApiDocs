<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/discount_save.php");

class CCatalogDiscountSave extends CAllCatalogDiscountSave
{
	public static function Add($arFields, $boolCalc = false)
	{
		global $DB;

		if (!CCatalogDiscountSave::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_discount", $arFields);

		$strSql = "INSERT INTO b_catalog_discount(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = (int)$DB->LastID();
		if ($ID > 0)
		{
			foreach ($arFields['RANGES'] as &$arRange)
			{
				$arRange['DISCOUNT_ID'] = $ID;
				$arInsert = $DB->PrepareInsert("b_catalog_disc_save_range", $arRange);
				$strSql = "INSERT INTO b_catalog_disc_save_range(".$arInsert[0].") VALUES(".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			foreach ($arFields['GROUP_IDS'] as &$intGroupID)
			{
				$strSql = "INSERT INTO b_catalog_disc_save_group(DISCOUNT_ID,GROUP_ID) VALUES(".$ID.",".$intGroupID.")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			$boolCalc = ($boolCalc === true);
			if ($boolCalc)
				CCatalogDiscountSave::UserDiscountCalc($ID,$arFields,false);

		}
		return $ID;
	}

	public static function Update($intID, $arFields, $boolCalc = false)
	{
		global $DB;

		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		if (!CCatalogDiscountSave::CheckFields('UPDATE',$arFields,$intID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_discount", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_discount SET ".$strUpdate." where ID = ".$intID." and TYPE = ".self::ENTITY_ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (!empty($arFields['RANGES']))
		{
			$DB->Query("delete from b_catalog_disc_save_range where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			foreach ($arFields['RANGES'] as &$arRange)
			{
				$arRange['DISCOUNT_ID'] = $intID;
				$arInsert = $DB->PrepareInsert("b_catalog_disc_save_range", $arRange);
				$strSql = "insert into b_catalog_disc_save_range(".$arInsert[0].") values(".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			unset($arRange);
		}

		if (!empty($arFields['GROUP_IDS']))
		{
			$DB->Query("delete from b_catalog_disc_save_group where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			foreach ($arFields['GROUP_IDS'] as &$intGroupID)
			{
				$strSql = "insert into b_catalog_disc_save_group(DISCOUNT_ID,GROUP_ID) values(".$intID.",".$intGroupID.")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			unset($intGroupID);
		}

		$boolCalc = ($boolCalc === true);
		if ($boolCalc)
			CCatalogDiscountSave::UserDiscountCalc($intID, $arFields, false);

		return $intID;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool|array $arGroupBy
	 * @param bool|array $arNavStartParams
	 * @param array $arSelectFields
	 * @return bool|CDBResult
	 */
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DS.ID", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "DS.XML_ID", "TYPE" => "string"),
			'SITE_ID' => array("FIELD" => "DS.SITE_ID", "TYPE" => "string"),
			"TYPE" => array("FIELD" => "DS.TYPE", "TYPE" => "int"),
			'NAME' => array("FIELD" => "DS.NAME", "TYPE" => "string"),
			'ACTIVE' => array("FIELD" => "DS.ACTIVE", "TYPE" => "char"),
			"SORT" => array("FIELD" => "DS.SORT", "TYPE" => "int"),
			"CURRENCY" => array("FIELD" => "DS.CURRENCY", "TYPE" => "string"),
			"ACTIVE_FROM" => array("FIELD" => "DS.ACTIVE_FROM", "TYPE" => "datetime"),
			"ACTIVE_TO" => array("FIELD" => "DS.ACTIVE_TO", "TYPE" => "datetime"),
			"COUNT_PERIOD" => array("FIELD" => "DS.COUNT_PERIOD", "TYPE" => "char"),
			"COUNT_SIZE" => array("FIELD" => "DS.COUNT_SIZE", "TYPE" => "int"),
			"COUNT_TYPE" => array("FIELD" => "DS.COUNT_TYPE", "TYPE" => "char"),
			"COUNT_FROM" => array("FIELD" => "DS.COUNT_FROM", "TYPE" => "datetime"),
			"COUNT_TO" => array("FIELD" => "DS.COUNT_TO", "TYPE" => "datetime"),
			"ACTION_SIZE" => array("FIELD" => "DS.ACTION_SIZE", "TYPE" => "int"),
			"ACTION_TYPE" => array("FIELD" => "DS.ACTION_TYPE", "TYPE" => "char"),
			"TIMESTAMP_X" => array("FIELD" => "DS.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "DS.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "DS.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "DS.CREATED_BY", "TYPE" => "int"),

			"RANGE_FROM" => array("FIELD" => "DSR.RANGE_FROM", "TYPE" => "double", "FROM" => "LEFT JOIN b_catalog_disc_save_range DSR ON (DS.ID = DSR.DISCOUNT_ID)"),
			"VALUE" => array("FIELD" => "DSR.VALUE", "TYPE" => "double", "FROM" => "LEFT JOIN b_catalog_disc_save_range DSR ON (DS.ID = DSR.DISCOUNT_ID)"),
			"VALUE_TYPE" => array("FIELD" => "DSR.TYPE", "TYPE" => "char", "FROM" => "LEFT JOIN b_catalog_disc_save_range DSR ON (DS.ID = DSR.DISCOUNT_ID)"),

			"GROUP_ID" => array("FIELD" => "DSG.GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_disc_save_group DSG ON (DS.ID = DSG.DISCOUNT_ID)"),
		);

		if (empty($arSelectFields))
			$arSelectFields = array('ID','XML_ID','SITE_ID','TYPE','NAME','ACTIVE','SORT','CURRENCY','ACTIVE_FROM','ACTIVE_TO','COUNT_PERIOD','COUNT_SIZE','COUNT_TYPE','COUNT_FROM','COUNT_TO','ACTION_SIZE','ACTION_TYPE','TIMESTAMP_X','MODIFIED_BY','DATE_CREATE','CREATED_BY');
		elseif (is_array($arSelectFields) && in_array('*',$arSelectFields))
			$arSelectFields = array('ID','XML_ID','SITE_ID','TYPE','NAME','ACTIVE','SORT','CURRENCY','ACTIVE_FROM','ACTIVE_TO','COUNT_PERIOD','COUNT_SIZE','COUNT_TYPE','COUNT_FROM','COUNT_TO','ACTION_SIZE','ACTION_TYPE','TIMESTAMP_X','MODIFIED_BY','DATE_CREATE','CREATED_BY');

		if (!is_array($arFilter))
			$arFilter = array();
		$arFilter['TYPE'] = self::ENTITY_ID;

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount DS ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount DS ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount DS ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function GetRangeByDiscount($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DSR.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DSR.DISCOUNT_ID", "TYPE" => "int"),
			"RANGE_FROM" => array("FIELD" => "DSR.RANGE_FROM", "TYPE" => "double"),
			"VALUE" => array("FIELD" => "DSR.VALUE", "TYPE" => "double"),
			"TYPE" => array("FIELD" => "DSR.TYPE", "TYPE" => "char"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_range DSR ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_range DSR ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_disc_save_range DSR ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function GetGroupByDiscount($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DSG.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DSG.DISCOUNT_ID", "TYPE" => "int"),
			"GROUP_ID" => array("FIELD" => "DSG.GROUP_ID", "TYPE" => "int"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_group DSG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_group DSG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_disc_save_group DSG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function GetUserInfoByDiscount($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DSU.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DSU.DISCOUNT_ID", "TYPE" => "int"),
			"USER_ID" => array("FIELD" => "DSU.USER_ID", "TYPE" => "int"),
			"ACTIVE_FROM" => array("FIELD" => "DSU.ACTIVE_FROM", "TYPE" => "datetime"),
			"ACTIVE_TO" => array("FIELD" => "DSU.ACTIVE_TO", "TYPE" => "datetime"),
			"RANGE_FROM" => array("FIELD" => "DSU.RANGE_FROM", "TYPE" => "double"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_user DSU ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_disc_save_user DSU ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_disc_save_user DSU ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	protected static function __GetDiscountIDByGroup($arUserGroup = array())
	{
		global $DB;

		$arResult = array();

		if (!empty($arUserGroup) && is_array($arUserGroup))
		{
			$arValid = array();
			foreach ($arUserGroup as &$intGroupID)
			{
				$intGroupID = intval($intGroupID);
				if (0 < $intGroupID && 2 != $intGroupID)
					$arValid[] = $intGroupID;
			}
			if (isset($intGroupID))
				unset($intGroupID);
			if (!empty($arValid))
			{
				$arUserGroup = array_unique($arValid);

				$strUserGroup = implode(' or GROUP_ID = ',$arUserGroup);

				$strQuery = 'select DISCOUNT_ID from b_catalog_disc_save_group WHERE GROUP_ID = '.$strUserGroup;

				$rsDiscounts = $DB->Query($strQuery,  false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arDiscount = $rsDiscounts->Fetch())
				{
					$arResult[] = intval($arDiscount['DISCOUNT_ID']);
				}
				if (!empty($arResult))
					$arResult = array_unique($arResult);
			}
		}

		return $arResult;
	}

	protected static function __GetUserInfoByDiscount($arParams, $arSettings = array())
	{
		global $DB;

		$arResult = false;
		if (!empty($arParams) && is_array($arParams))
		{
			if (!is_array($arSettings))
				$arSettings = array();
			$boolActiveFromFilter = true;
			$boolDelete = true;
			if (!empty($arSettings) && isset($arSettings['ACTIVE_FROM']))
				$boolActiveFromFilter = (true === $arSettings['ACTIVE_FROM'] ? true : false);
			if (!empty($arSettings) && isset($arSettings['DELETE']))
				$boolDelete = (true === $arSettings['DELETE'] ? true : false);

			$intUserID = intval($arParams['USER_ID']);
			$intDiscountID = intval($arParams['DISCOUNT_ID']);
			$strActiveDate = strval($arParams['ACTIVE_FROM']);
			if (0 < $intUserID && 0 < $intDiscountID && !($boolActiveFromFilter && empty($strActiveDate)))
			{
				$strQuery = 'select U.*, '.
				$DB->DateToCharFunction('U.ACTIVE_FROM', 'FULL').' as ACTIVE_FROM_FORMAT, '.
				$DB->DateToCharFunction('U.ACTIVE_TO', 'FULL').' as ACTIVE_TO_FORMAT '.
				'from b_catalog_disc_save_user U where DISCOUNT_ID = '.$intDiscountID.' AND USER_ID = '.$intUserID;
				if ($boolActiveFromFilter)
					$strQuery .= ' AND ACTIVE_FROM >= '.$DB->CharToDateFunction($strActiveDate);
				$rsResults = $DB->Query($strQuery,  false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arResult = $rsResults->Fetch())
				{

				}
				else
				{
					if ($boolDelete)
					{
						$strQuery = 'delete from b_catalog_disc_save_user where DISCOUNT_ID = '.$intDiscountID.' AND USER_ID = '.$intUserID;
						$DB->Query($strQuery,  false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
				}
			}
		}
		return $arResult;
	}

	protected static function __UpdateUserInfoByDiscount($arParams, $arSettings = array())
	{
		global $DB;
		if (!empty($arParams) && is_array($arParams))
		{
			if (!is_array($arSettings))
				$arSettings = array();
			$boolSearch = false;
			$boolDelete = true;
			if (!empty($arSettings) && isset($arSettings['SEARCH']))
				$boolSearch = (true === $arSettings['SEARCH'] ? true : false);
			if (!empty($arSettings) && isset($arSettings['DELETE']))
				$boolDelete = (true === $arSettings['DELETE'] ? true : false);

			$intUserID = intval($arParams['USER_ID']);
			$intDiscountID = intval($arParams['DISCOUNT_ID']);
			$strActiveFrom = strval($arParams['ACTIVE_FROM']);
			$strActiveTo = strval($arParams['ACTIVE_TO']);
			if (0 < $intUserID && 0 < $intDiscountID && !empty($strActiveFrom) && !empty($strActiveTo))
			{
				if ($boolSearch)
				{
					$strQuery = 'select ID from b_catalog_disc_save_user where DISCOUNT_ID = '.$intDiscountID.' AND USER_ID = '.$intUserID.' limit 1';
					$rsItems = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					if ($arItem = $rsItems->Fetch())
					{
						return;
					}
				}
				if ($boolDelete)
				{
					$strQuery = 'delete from b_catalog_disc_save_user where DISCOUNT_ID = '.$intDiscountID.' AND USER_ID = '.$intUserID;
					$DB->Query($strQuery,  false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				$arInsert = $DB->PrepareInsert("b_catalog_disc_save_user", $arParams);
				$strQuery =
					"INSERT INTO b_catalog_disc_save_user(".$arInsert[0].") ".
					"VALUES(".$arInsert[1].")";
				$DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}
}