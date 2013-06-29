<?
class CIBlockSectionPropertyLink
{
	public static function Add($SECTION_ID, $PROPERTY_ID, $arLink = array())
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$PROPERTY_ID = intval($PROPERTY_ID);
		$rs = $DB->Query("SELECT * FROM b_iblock_section_property WHERE SECTION_ID = ".$SECTION_ID." AND PROPERTY_ID = ".$PROPERTY_ID);
		if (!$rs->Fetch())
		{
			if ($SECTION_ID == 0)
				$rs = $DB->Query("
					SELECT 1 ID, bp.IBLOCK_ID, 0 SECTION_ID, bp.ID PROPERTY_ID
					FROM b_iblock_property bp
					WHERE bp.ID = ".$PROPERTY_ID."
				");
			elseif ($SECTION_ID > 0)
				$rs = $DB->Query("
					SELECT 1 ID, bs.IBLOCK_ID, bs.ID SECTION_ID, bp.ID PROPERTY_ID
					FROM b_iblock_property bp
					,b_iblock_section bs
					WHERE bp.ID = ".$PROPERTY_ID."
					AND bs.ID = ".$SECTION_ID."
				");
			else
				$rs = false;

			if (is_object($rs))
				$ar = $rs->Fetch();
			else
				$ar = false;

			if (is_array($ar))
			{
				$DB->Add("b_iblock_section_property", $ar);

				$arUpdate = array();
				if (array_key_exists("SMART_FILTER", $arLink))
					$arUpdate["SMART_FILTER"] = $arLink["SMART_FILTER"];
				if (array_key_exists("IBLOCK_ID", $arLink))
					$arUpdate["IBLOCK_ID"] = $arLink["IBLOCK_ID"];

				if (!empty($arUpdate))
					$strUpdate = $DB->PrepareUpdate("b_iblock_section_property", $arUpdate);
				else
					$strUpdate = "";

				if (strlen($strUpdate))
				{
					$DB->Query("
						UPDATE b_iblock_section_property
						SET ".$strUpdate."
						WHERE IBLOCK_ID = ".$ar["IBLOCK_ID"]."
						AND SECTION_ID = ".$ar["SECTION_ID"]."
						AND PROPERTY_ID = ".$ar["PROPERTY_ID"]."
					");
				}
			}
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
		CIBlock::CleanCache($IBLOCK_ID);
	}

	public static function DeleteBySection($SECTION_ID)
	{
		global $DB;
		$SECTION_ID = intval($SECTION_ID);
		$DB->Query("DELETE FROM b_iblock_section_property WHERE SECTION_ID = ".$SECTION_ID);
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
		if ($SECTION_ID > 0)
		{
			$rs = $DB->Query($s = "
				SELECT
					B.SECTION_PROPERTY,
					BP.ID PROPERTY_ID,
					BSP.SECTION_ID LINK_ID,
					BSP.SMART_FILTER,
					BP.SORT
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
					BP.SORT ASC, BP.ID ASC, BS.LEFT_MARGIN DESC
			");
			while ($ar = $rs->Fetch())
			{
				$result[$ar["PROPERTY_ID"]] = array(
					"PROPERTY_ID" => $ar["PROPERTY_ID"],
					"SMART_FILTER" => $ar["SMART_FILTER"],
					"INHERITED" => $SECTION_ID == $ar["LINK_ID"] ? "N" : "Y",
					"INHERITED_FROM" => $ar["LINK_ID"],
					"SORT" => $ar["SORT"],
				);
			}
		}
		if ($SECTION_ID >= 0)
		{
			$rs = $DB->Query("
				SELECT
					B.SECTION_PROPERTY,
					BP.ID PROPERTY_ID,
					BSP.SECTION_ID LINK_ID,
					BSP.SMART_FILTER,
					BP.SORT
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
				if ($ar["SECTION_PROPERTY"] === "Y")
				{
					if (strlen($ar["LINK_ID"]))
						$result[$ar["PROPERTY_ID"]] = array(
							"PROPERTY_ID" => $ar["PROPERTY_ID"],
							"SMART_FILTER" => $ar["SMART_FILTER"],
							"INHERITED" => $SECTION_ID == 0 && !$bNewSection? "N" : "Y",
							"INHERITED_FROM" => 0,
							"SORT" => $ar["SORT"],
						);
				}
				else
				{
					$result[$ar["PROPERTY_ID"]] = array(
						"PROPERTY_ID" => $ar["PROPERTY_ID"],
						"SMART_FILTER" => "N",
						"INHERITED" => $SECTION_ID == 0 && !$bNewSection? "N" : "Y",
						"INHERITED_FROM" => 0,
						"SORT" => $ar["SORT"],
					);
				}
			}
			uasort($result, array("CIBlockSectionPropertyLink", "_sort"));
		}
		return $result;
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