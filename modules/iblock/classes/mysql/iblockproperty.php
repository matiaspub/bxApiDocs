<?

/**
 * <b>CIBlockProperty</b> - класс для работы со свойствами информационных разделов.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/index.php
 * @author Bitrix
 */
class CIBlockProperty extends CAllIBlockProperty
{
	public function _Update($ID, $arFields, $bCheckDescription = false)
	{
		global $DB;
		$ID=intval($ID);
		$rsProperty = $this->GetByID($ID);
		$arProperty = $rsProperty->Fetch();
		if(!$arProperty)
		{
			$this->LAST_ERROR = $this->FormatNotFoundError($ID);
			return false;
		}
		if($arProperty["VERSION"]!=2)
		{
			return true;
		}
		$tableFields = $DB->GetTableFields("b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]);
		if(is_set($arFields, "MULTIPLE") && $arFields["MULTIPLE"]!=$arProperty["MULTIPLE"])
		{//MULTIPLE changed
			if($arFields["MULTIPLE"]=="Y")
			{//MULTIPLE=Y
				$strSql = "
					INSERT INTO b_iblock_element_prop_m".$arProperty["IBLOCK_ID"]."
					(IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE, VALUE_ENUM, VALUE_NUM
					".(isset($tableFields["DESCRIPTION_".$arProperty["ID"]])? ", DESCRIPTION": "")."
					)
					SELECT
						IBLOCK_ELEMENT_ID
						,".$arProperty["ID"]."
						,PROPERTY_".$arProperty["ID"]."
						,".($arProperty["PROPERTY_TYPE"]=="S" || $arProperty["PROPERTY_TYPE"]=="s"?
							"0":
							"PROPERTY_".$arProperty["ID"])."
						,".($arProperty["PROPERTY_TYPE"]=="S" || $arProperty["PROPERTY_TYPE"]=="s"?
							"0":
							"PROPERTY_".$arProperty["ID"])."
						".(isset($tableFields["DESCRIPTION_".$arProperty["ID"]])? ",DESCRIPTION_".$arProperty["ID"]: "")."
					FROM
						b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
					WHERE
						PROPERTY_".$arProperty["ID"]." is not null
				";
				if(!$DB->Query($strSql))
				{
					$this->LAST_ERROR = $this->FormatUpdateError($ID, "MY01");
					return false;
				}
				$strSql = "
					UPDATE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
					SET PROPERTY_".$arProperty["ID"]."=null
					".(isset($tableFields["DESCRIPTION_".$arProperty["ID"]])? ", DESCRIPTION_".$arProperty["ID"]."=null": "")."
				";
				if(!$DB->Query($strSql))
				{
					$this->LAST_ERROR =  $this->FormatUpdateError($ID, "MY02");
					return false;
				}
				$strSql = "
					ALTER TABLE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
					CHANGE PROPERTY_".$arProperty["ID"]." PROPERTY_".$arProperty["ID"]." longtext
				";
				if(!$DB->DDL($strSql))
				{
					$this->LAST_ERROR =  $this->FormatUpdateError($ID, "MY03");
					return false;
				}
			}
			else
			{//MULTIPLE=N
				switch($arFields["PROPERTY_TYPE"])
				{
					case "S":
						$strType = "text";
						break;
					case "N":
						$strType = "numeric(18,4)";
						break;
					case "L":
					case "F":
					case "G":
					case "E":
						$strType = "int(11)";
						break;
					default://s - small string
						$strType = "varchar(255)";
				}
				$strSql = "
					ALTER TABLE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
					CHANGE PROPERTY_".$arProperty["ID"]." PROPERTY_".$arProperty["ID"]." ".$strType."
				";
				if(!$DB->DDL($strSql))
				{
					$this->LAST_ERROR =  $this->FormatUpdateError($ID, "MY04");
					return false;
				}
				switch($arFields["PROPERTY_TYPE"])
				{
					case "N":
					case "F":
					case "G":
					case "E":
						$strTrans = "VALUE_NUM";
						break;
					case "L":
						$strTrans = "VALUE_ENUM";
						break;
					case "s":
						$strTrans = "SUBSTRING(VALUE, 0 ,255)";
						break;
					default:
						$strTrans = "VALUE";
				}
				$strSql = "
					UPDATE
						b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]." EL
						,b_iblock_element_prop_m".$arProperty["IBLOCK_ID"]." EN
					SET
						PROPERTY_".$ID." = ".$strTrans."
						".(isset($tableFields["DESCRIPTION_".$ID])? ",DESCRIPTION_".$ID." = DESCRIPTION": "")."
					WHERE
						EN.IBLOCK_ELEMENT_ID = EL.IBLOCK_ELEMENT_ID
						AND EN.IBLOCK_PROPERTY_ID = ".$ID."
				";
				if(!$DB->Query($strSql))
				{
					$this->LAST_ERROR = $this->FormatUpdateError($ID, "MY05");
					return false;
				}
				$strSql = "
					DELETE FROM
						b_iblock_element_prop_m".$arProperty["IBLOCK_ID"]."
					WHERE
						IBLOCK_PROPERTY_ID = ".$ID."
				";
				if(!$DB->Query($strSql))
				{
					$this->LAST_ERROR = $this->FormatUpdateError($ID, "MY06");
					return false;
				}
			}
		}
		else
		{//MULTIPLE not changed
			if(is_set($arFields ,"PROPERTY_TYPE")
			&& $arFields["PROPERTY_TYPE"]!=$arProperty["PROPERTY_TYPE"]
			&& $arProperty["MULTIPLE"]=="N")
			{
				switch($arFields["PROPERTY_TYPE"])
				{
					case "S":
						$strType = "text";
						break;
					case "N":
						$strType = "numeric(18,4)";
						break;
					case "L":
					case "F":
					case "G":
					case "E":
						$strType = "int(11)";
						break;
					default://s - small string
						$strType = "varchar(255)";
				}
				$strSql = "
					ALTER TABLE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
					CHANGE PROPERTY_".$arProperty["ID"]." PROPERTY_".$arProperty["ID"]." ".$strType."
				";
				if(!$DB->DDL($strSql))
				{
					$this->LAST_ERROR =  $this->FormatUpdateError($ID, "MY07");
					return false;
				}
			}
		}

		if(
			$bCheckDescription
			|| (
				array_key_exists("WITH_DESCRIPTION", $arFields)
				&& $arFields["WITH_DESCRIPTION"] != $arProperty["WITH_DESCRIPTION"]
			)
		)
		{//WITH_DESCRIPTION changed
			if ($arFields["WITH_DESCRIPTION"] == "Y")
			{//WITH_DESCRIPTION=Y
				if (!isset($tableFields["DESCRIPTION_".$ID]))
				{
					$strSql = "
						ALTER TABLE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
						ADD DESCRIPTION_".$ID." varchar(255)
					";
					if (!$DB->DDL($strSql))
					{
						$this->LAST_ERROR = $this->FormatUpdateError($ID, "MY08");
						return false;
					}
				}
			}
			else
			{//WITH_DESCRIPTION=N
				if (isset($tableFields["DESCRIPTION_".$ID]))
				{
					$rs = $DB->Query("
						SELECT COUNT(1) CNT
						FROM b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
						WHERE DESCRIPTION_".$ID." IS NOT NULL AND DESCRIPTION_".$ID." <> ''
					");
					$ar = $rs->Fetch();
					if ($ar["CNT"] <= 0)
					{
						$strSql = "
							ALTER TABLE b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
							DROP DESCRIPTION_".$ID."
						";
						if (!$DB->DDL($strSql))
						{
							$this->LAST_ERROR = $this->FormatUpdateError($ID, "MY09");
							return false;
						}
					}
				}
			}
		}

		return true;
	}

	public static function DropColumnSQL($strTable, $arColumns)
	{
		global $DB;
		$tableFields = $DB->GetTableFields($strTable);
		foreach ($arColumns as $i => $columnName)
		{
			if (!isset($tableFields[$columnName]))
				unset($arColumns[$i]);
		}
		if ($arColumns)
			return array("ALTER TABLE ".$strTable." DROP ".implode(", DROP ", $arColumns));
		else
			return array();
	}

	public static function _Add($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if($arFields["MULTIPLE"]=="Y")
			$strType = "longtext";
		else
		{
			switch($arFields["PROPERTY_TYPE"])
			{
				case "S":
					$strType = "text";
					break;
				case "N":
					$strType = "numeric(18,4)";
					break;
				case "L":
				case "F":
				case "G":
				case "E":
					$strType = "int(11)";
					break;
				default://s - small string
					$strType = "varchar(255)";
			}
		}
		$strSql = "
			ALTER TABLE b_iblock_element_prop_s".$arFields["IBLOCK_ID"]."
			ADD PROPERTY_".$ID." ".$strType."
			".($arFields["WITH_DESCRIPTION"] == "Y"? ", ADD DESCRIPTION_".$ID." varchar(255)": "")."
		";
		$rs = $DB->DDL($strSql, true);
		return $rs;
	}
}
