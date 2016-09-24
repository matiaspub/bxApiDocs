<?
IncludeModuleLangFile(__FILE__);

class CIBlockSectionPropertyLink
{
	public static function Set($SECTION_ID, $PROPERTY_ID, $arLink = array())
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$PROPERTY_ID = intval($PROPERTY_ID);
		$rs = $DB->Query("
			SELECT *
			FROM b_iblock_section_property
			WHERE SECTION_ID = ".$SECTION_ID." AND PROPERTY_ID = ".$PROPERTY_ID."
		");

		$sectionProperty = $rs->Fetch();
		if ($sectionProperty)
		{
			$ar = self::CheckProperty($SECTION_ID, $PROPERTY_ID);
			if (is_array($ar))
			{
				$arUpdate = array();

				if (
					array_key_exists("SMART_FILTER", $arLink)
					&& $arLink["SMART_FILTER"] !== $sectionProperty["SMART_FILTER"]
				)
				{
					$arUpdate["SMART_FILTER"] = $arLink["SMART_FILTER"] === "Y"? "Y": false;
				}

				if (
					array_key_exists("DISPLAY_TYPE", $arLink)
					&& $arLink["DISPLAY_TYPE"] !== $sectionProperty["DISPLAY_TYPE"]
				)
				{
					$arUpdate["DISPLAY_TYPE"] = $arLink["DISPLAY_TYPE"];
				}

				if (
					array_key_exists("DISPLAY_EXPANDED", $arLink)
					&& $arLink["DISPLAY_EXPANDED"] !== $sectionProperty["DISPLAY_EXPANDED"]
				)
				{
					$arUpdate["DISPLAY_EXPANDED"] = $arLink["DISPLAY_EXPANDED"] === "Y"? "Y": false;
				}

				if (
					array_key_exists("FILTER_HINT", $arLink)
					&& $arLink["FILTER_HINT"] !== $sectionProperty["FILTER_HINT"]
				)
				{
					$arUpdate["FILTER_HINT"] = $arLink["FILTER_HINT"];
				}

				if (
					array_key_exists("IBLOCK_ID", $arLink)
					&& $arLink["IBLOCK_ID"] !== $sectionProperty["IBLOCK_ID"]
				)
				{
					$arUpdate["IBLOCK_ID"] = $arLink["IBLOCK_ID"];
				}

				if (!empty($arUpdate))
					$strUpdate = $DB->PrepareUpdate("b_iblock_section_property", $arUpdate);
				else
					$strUpdate = "";

				if (strlen($strUpdate) > 0)
				{
					$DB->Query("
						UPDATE b_iblock_section_property
						SET ".$strUpdate."
						WHERE SECTION_ID = ".$ar["SECTION_ID"]."
						AND PROPERTY_ID = ".$ar["PROPERTY_ID"]."
					");

					if (
						array_key_exists("SMART_FILTER", $arUpdate)
						&& $arUpdate["SMART_FILTER"] === "Y"
					)
					{
						\Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($arUpdate["IBLOCK_ID"]? $arUpdate["IBLOCK_ID"]: $ar["IBLOCK_ID"]);
					}
				}
			}
		}
		else
		{
			self::Add($SECTION_ID, $PROPERTY_ID, $arLink);
		}
	}

	public static function Add($SECTION_ID, $PROPERTY_ID, $arLink = array())
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$PROPERTY_ID = intval($PROPERTY_ID);
		$rs = $DB->Query("
			SELECT *
			FROM b_iblock_section_property
			WHERE SECTION_ID = ".$SECTION_ID." AND PROPERTY_ID = ".$PROPERTY_ID."
		");

		if (!$rs->Fetch())
		{
			$ar = self::CheckProperty($SECTION_ID, $PROPERTY_ID);
			if (is_array($ar))
			{
				$ar["ID"] = 1;
				$DB->Add("b_iblock_section_property", $ar);

				$arUpdate = array();
				if (array_key_exists("SMART_FILTER", $arLink))
					$arUpdate["SMART_FILTER"] = $arLink["SMART_FILTER"];
				if (array_key_exists("DISPLAY_TYPE", $arLink))
					$arUpdate["DISPLAY_TYPE"] = $arLink["DISPLAY_TYPE"];
				if (array_key_exists("DISPLAY_EXPANDED", $arLink))
					$arUpdate["DISPLAY_EXPANDED"] = $arLink["DISPLAY_EXPANDED"];
				if (array_key_exists("FILTER_HINT", $arLink))
					$arUpdate["FILTER_HINT"] = $arLink["FILTER_HINT"];
				if (array_key_exists("IBLOCK_ID", $arLink))
					$arUpdate["IBLOCK_ID"] = $arLink["IBLOCK_ID"];

				if (!empty($arUpdate))
					$strUpdate = $DB->PrepareUpdate("b_iblock_section_property", $arUpdate);
				else
					$strUpdate = "";

				if (strlen($strUpdate) > 0)
				{
					$DB->Query("
						UPDATE b_iblock_section_property
						SET ".$strUpdate."
						WHERE IBLOCK_ID = ".$ar["IBLOCK_ID"]."
						AND SECTION_ID = ".$ar["SECTION_ID"]."
						AND PROPERTY_ID = ".$ar["PROPERTY_ID"]."
					");

					if (
						array_key_exists("SMART_FILTER", $arUpdate)
						&& $arUpdate["SMART_FILTER"] === "Y"
						&& $arLink["INVALIDATE"] !== "N"
					)
					{
						\Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($arUpdate["IBLOCK_ID"]? $arUpdate["IBLOCK_ID"]: $ar["IBLOCK_ID"]);
					}
				}
			}
		}
	}

	public static function CheckProperty($SECTION_ID, $PROPERTY_ID)
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$PROPERTY_ID = intval($PROPERTY_ID);

		if ($SECTION_ID == 0)
		{
			$rs = $DB->Query("
				SELECT bp.IBLOCK_ID, 0 SECTION_ID, bp.ID PROPERTY_ID
				FROM b_iblock_property bp
				WHERE bp.ID = ".$PROPERTY_ID."
			");
			return $rs->Fetch();
		}
		elseif ($SECTION_ID > 0)
		{
			$rs = $DB->Query("
				SELECT bs.IBLOCK_ID, bs.ID SECTION_ID, bp.ID PROPERTY_ID
				FROM b_iblock_property bp
				,b_iblock_section bs
				WHERE bp.ID = ".$PROPERTY_ID."
				AND bs.ID = ".$SECTION_ID."
			");
			return $rs->Fetch();
		}
		else
		{
			return false;
		}
	}

	public static function Delete($SECTION_ID, $PROPERTY_ID)
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$PROPERTY_ID = intval($PROPERTY_ID);
		$DB->Query("DELETE FROM b_iblock_section_property WHERE SECTION_ID = ".$SECTION_ID." AND PROPERTY_ID = ".$PROPERTY_ID);
	}

	public static function DeleteByIBlock($IBLOCK_ID)
	{
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$DB->Query("DELETE FROM b_iblock_section_property WHERE IBLOCK_ID = ".$IBLOCK_ID);
		$DB->Query("UPDATE b_iblock SET SECTION_PROPERTY = 'N' WHERE ID = ".$IBLOCK_ID);
		\Bitrix\Iblock\PropertyIndex\Manager::deleteIndex($IBLOCK_ID);
		CIBlock::CleanCache($IBLOCK_ID);
	}

	public static function DeleteBySection($SECTION_ID, $PROPERTY_ID = null)
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		if (is_array($PROPERTY_ID))
			$PROPERTY_ID = array_map("intval", $PROPERTY_ID);
		elseif ($PROPERTY_ID !== null)
			$PROPERTY_ID = array(intval($PROPERTY_ID));

		$DB->Query("
			DELETE FROM b_iblock_section_property
			WHERE SECTION_ID = ".$SECTION_ID."
			".($PROPERTY_ID? "AND PROPERTY_ID NOT IN (".implode(", ", $PROPERTY_ID).")": "")."
		");
	}

	public static function DeleteByProperty($PROPERTY_ID)
	{
		global $DB;
		$PROPERTY_ID = intval($PROPERTY_ID);
		$DB->Query("DELETE FROM b_iblock_section_property WHERE PROPERTY_ID = ".$PROPERTY_ID);
	}

	public static function HasIBlockLinks($IBLOCK_ID)
	{
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$rs = $DB->Query($DB->TopSQL("
			SELECT
				1
			FROM
				b_iblock B
				INNER JOIN b_iblock_property BP ON BP.IBLOCK_ID = B.ID
				LEFT JOIN b_iblock_section_property BSP ON BSP.IBLOCK_ID = B.ID AND BSP.PROPERTY_ID = BP.ID
			WHERE
				B.ID = ".$IBLOCK_ID."
				AND (
					BSP.SECTION_ID IS NULL
					OR BSP.SECTION_ID > 0
					OR BSP.SMART_FILTER = 'Y'
				)
		", 1));
		return is_array($rs->Fetch());
	}

	public static function GetArray($IBLOCK_ID, $SECTION_ID = 0, $bNewSection = false)
	{
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$SECTION_ID = intval($SECTION_ID);
		$result = array();
		$rs = $DB->Query("
			SELECT
				B.SECTION_PROPERTY,
				BP.ID PROPERTY_ID,
				BSP.SECTION_ID LINK_ID,
				BSP.SMART_FILTER,
				BSP.DISPLAY_TYPE,
				BSP.DISPLAY_EXPANDED,
				BSP.FILTER_HINT,
				BP.SORT,
				0 LEFT_MARGIN,
				B.NAME LINK_TITLE,
				BP.PROPERTY_TYPE,
				BP.USER_TYPE
			FROM
				b_iblock B
				INNER JOIN b_iblock_property BP ON BP.IBLOCK_ID = B.ID
				LEFT JOIN b_iblock_section_property BSP ON BSP.SECTION_ID = 0 AND BSP.PROPERTY_ID = BP.ID
			WHERE
				B.ID = ".$IBLOCK_ID."
			ORDER BY
				BP.SORT ASC, BP.ID ASC
		");
		while ($ar = $rs->Fetch())
		{
			$displayTypesAvailable = self::getDisplayTypes($ar["PROPERTY_TYPE"], $ar["USER_TYPE"]);
			if (isset($displayTypesAvailable[$ar["DISPLAY_TYPE"]]))
				$DISPLAY_TYPE = $ar["DISPLAY_TYPE"];
			else
				$DISPLAY_TYPE = key($displayTypesAvailable);

			if ($ar["SECTION_PROPERTY"] === "Y")
			{
				if (strlen($ar["LINK_ID"]))
				{
					$result[$ar["PROPERTY_ID"]] = array(
						"PROPERTY_ID" => $ar["PROPERTY_ID"],
						"SMART_FILTER" => $ar["SMART_FILTER"],
						"DISPLAY_TYPE" => $DISPLAY_TYPE,
						"DISPLAY_EXPANDED" => $ar["DISPLAY_EXPANDED"],
						"FILTER_HINT" => $ar["FILTER_HINT"],
						"INHERITED" => $SECTION_ID == 0 && !$bNewSection? "N" : "Y",
						"INHERITED_FROM" => 0,
						"SORT" => $ar["SORT"],
						"LEFT_MARGIN" => $ar["LEFT_MARGIN"],
						"LINK_TITLE" => $ar["LINK_TITLE"],
						"PROPERTY_TYPE" => $ar["PROPERTY_TYPE"],
						"USER_TYPE" => $ar["USER_TYPE"],
					);
				}
			}
			else
			{
				$result[$ar["PROPERTY_ID"]] = array(
					"PROPERTY_ID" => $ar["PROPERTY_ID"],
					"SMART_FILTER" => "N",
					"DISPLAY_TYPE" => $DISPLAY_TYPE,
					"DISPLAY_EXPANDED" => $ar["DISPLAY_EXPANDED"],
					"INHERITED" => $SECTION_ID == 0 && !$bNewSection? "N" : "Y",
					"INHERITED_FROM" => 0,
					"SORT" => $ar["SORT"],
					"LEFT_MARGIN" => $ar["LEFT_MARGIN"],
					"LINK_TITLE" => $ar["LINK_TITLE"],
					"PROPERTY_TYPE" => $ar["PROPERTY_TYPE"],
					"USER_TYPE" => $ar["USER_TYPE"],
				);
			}
		}

		if ($SECTION_ID > 0)
		{
			$rs = $DB->Query($s = "
				SELECT
					B.SECTION_PROPERTY,
					BP.ID PROPERTY_ID,
					BSP.SECTION_ID LINK_ID,
					BSP.SMART_FILTER,
					BSP.DISPLAY_TYPE,
					BSP.DISPLAY_EXPANDED,
					BSP.FILTER_HINT,
					BP.SORT,
					BS.LEFT_MARGIN,
					BS.NAME LINK_TITLE,
					BP.PROPERTY_TYPE,
					BP.USER_TYPE
				FROM
					b_iblock B
					INNER JOIN b_iblock_property BP ON BP.IBLOCK_ID = B.ID
					INNER JOIN b_iblock_section M ON M.ID = ".$SECTION_ID."
					INNER JOIN b_iblock_section BS ON BS.IBLOCK_ID = M.IBLOCK_ID
						AND M.LEFT_MARGIN >= BS.LEFT_MARGIN
						AND M.RIGHT_MARGIN <= BS.RIGHT_MARGIN
					INNER JOIN b_iblock_section_property BSP ON BSP.IBLOCK_ID = BS.IBLOCK_ID AND BSP.SECTION_ID = BS.ID AND BSP.PROPERTY_ID = BP.ID
				WHERE
					B.ID = ".$IBLOCK_ID."
				ORDER BY
					BP.SORT ASC, BP.ID ASC, BS.LEFT_MARGIN ASC
			");
			while ($ar = $rs->Fetch())
			{
				$displayTypesAvailable = self::getDisplayTypes($ar["PROPERTY_TYPE"], $ar["USER_TYPE"]);
				if (isset($displayTypesAvailable[$ar["DISPLAY_TYPE"]]))
					$DISPLAY_TYPE = $ar["DISPLAY_TYPE"];
				else
					$DISPLAY_TYPE = key($displayTypesAvailable);

				$result[$ar["PROPERTY_ID"]] = array(
					"PROPERTY_ID" => $ar["PROPERTY_ID"],
					"SMART_FILTER" => $ar["SMART_FILTER"],
					"DISPLAY_TYPE" => $DISPLAY_TYPE,
					"DISPLAY_EXPANDED" => $ar["DISPLAY_EXPANDED"],
					"FILTER_HINT" => $ar["FILTER_HINT"],
					"INHERITED" => $SECTION_ID == $ar["LINK_ID"] ? "N" : "Y",
					"INHERITED_FROM" => $ar["LINK_ID"],
					"SORT" => $ar["SORT"],
					"LEFT_MARGIN" => $ar["LEFT_MARGIN"],
					"LINK_TITLE" => $ar["LINK_TITLE"],
					"PROPERTY_TYPE" => $ar["PROPERTY_TYPE"],
				);
			}
		}

		if ($result)
		{
			\Bitrix\Main\Type\Collection::sortByColumn(
				$result,
				array(
					"SORT" => SORT_ASC,
					"PROPERTY_ID" => SORT_ASC,
				),
				'',
				null,
				true
			);
		}
		return $result;
	}

	public static function getDisplayTypes($property_type, $user_type)
	{
		//ABCDE - for numbers
		//FGHIJ - for checkboxes
		//KLMNO - for radio buttons
		//PQRST - for drop down
		//UWXYZ - reserved
		if (
			$property_type == "S"
			&& $user_type == "directory"
		)
		{
			$result = array(
				"F" => GetMessage("SP_DISPLAY_TYPE_F"),
				"G" => GetMessage("SP_DISPLAY_TYPE_G"),
				"H" => GetMessage("SP_DISPLAY_TYPE_H"),
				"K" => GetMessage("SP_DISPLAY_TYPE_K"),
				"P" => GetMessage("SP_DISPLAY_TYPE_P"),
				"R" => GetMessage("SP_DISPLAY_TYPE_R"),
			);
		}
		elseif (
			$property_type == "S"
			&& $user_type == "DateTime"
		)
		{
			$result = array(
				"U" => GetMessage("SP_DISPLAY_TYPE_U"),
				"F" => GetMessage("SP_DISPLAY_TYPE_F"),
				"K" => GetMessage("SP_DISPLAY_TYPE_K"),
				"P" => GetMessage("SP_DISPLAY_TYPE_P"),
			);
		}
		elseif (
			$property_type == "S"
			|| $property_type == "L"
			|| $property_type == "E"
			|| $property_type == "G"
		)
		{
			$result = array(
				"F" => GetMessage("SP_DISPLAY_TYPE_F"),
				"K" => GetMessage("SP_DISPLAY_TYPE_K"),
				"P" => GetMessage("SP_DISPLAY_TYPE_P"),
			);
		}
		elseif (
			$property_type == "N"
		)
		{
			$result = array(
				"A" => GetMessage("SP_DISPLAY_TYPE_A"),
				"B" => GetMessage("SP_DISPLAY_TYPE_B"),
			);
		}
		else
		{
			$result = array();
		}
		return $result;
	}

	public static function getDisplayTypesJsFunction()
	{
		//ABCDE - for numbers
		//FGHIJ - for checkboxes
		//KLMNO - for radio buttons
		//PQRST - for drop down
		//UWXYZ - reserved
		$js = '
		function getDisplayTypes(property_type, user_type)
		{
			if (
				property_type == "S"
				&& user_type == "directory"
			)
			{
				result = {
					"F": "'.GetMessageJS("SP_DISPLAY_TYPE_F").'",
					"G": "'.GetMessageJS("SP_DISPLAY_TYPE_G").'",
					"H": "'.GetMessageJS("SP_DISPLAY_TYPE_H").'",
					"K": "'.GetMessageJS("SP_DISPLAY_TYPE_K").'",
					"P": "'.GetMessageJS("SP_DISPLAY_TYPE_P").'",
					"R": "'.GetMessageJS("SP_DISPLAY_TYPE_R").'"
				};
			}
			else if (
				property_type == "S"
				|| property_type == "L"
				|| property_type == "E"
				|| property_type == "G"
			)
			{
				result = {
					"F": "'.GetMessageJS("SP_DISPLAY_TYPE_F").'",
					"K": "'.GetMessageJS("SP_DISPLAY_TYPE_K").'",
					"P": "'.GetMessageJS("SP_DISPLAY_TYPE_P").'"
				};
			}
			else if (
				property_type == "N"
			)
			{
				result = {
					"A": "'.GetMessageJS("SP_DISPLAY_TYPE_A").'",
					"B": "'.GetMessageJS("SP_DISPLAY_TYPE_B").'"
				};
			}
			else
			{
				result = false;
			}
			return result;
		}
		';
		return $js;
	}

	public static function _sort($a, $b)
	{
		if($a["SORT"] > $b["SORT"])
			return 1;
		elseif($a["SORT"] < $b["SORT"])
			return -1;
		else
			return 0;
	}
}
?>
