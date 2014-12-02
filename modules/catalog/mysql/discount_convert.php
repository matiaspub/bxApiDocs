<?
class CCatalogDiscountConvertTmp
{
	public static $strTableName = 'b_catalog_dsc_tmp';
	public static $strMainTableName = 'b_catalog_discount';
	public static $boolError = false;
	public static $arErrors = false;

	public static function CreateTable()
	{
		global $DB;

		if (self::$boolError)
			return false;

		$strSql = "SHOW TABLES LIKE '".$DB->ForSql(self::$strTableName)."'";
		$dbResult = $DB->Query($strSql);
		if (!($arResult = $dbResult->Fetch()))
		{
			$strSql = "CREATE TABLE ".self::$strTableName." (
						ID INT NOT NULL,
						PRIMARY KEY (ID)
						)";
			$result = $DB->Query($strSql, true);

			if (!$result)
			{
				self::$boolError = true;
				if (!is_array(self::$arErrors))
					self::$arErrors = array();
				self::$arErrors[] = $DB->db_Error;
				return false;
			}
		}
		return true;
	}

	public static function DropTable()
	{
		global $DB;

		if (self::$boolError)
			return false;

		$strSql = "DROP TABLE IF EXISTS ".self::$strTableName;
		$result = $DB->Query($strSql, true);

		if (!$result)
		{
			self::$boolError = true;
			if (!is_array(self::$arErrors))
				self::$arErrors = array();
			self::$arErrors[] = $DB->db_Error;
			return false;
		}
		return true;
	}

	public static function IsExistID($intID)
	{
		if (self::$boolError)
			return false;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;

		global $DB;

		$strSql = "SELECT COUNT(ID) as CNT FROM ".self::$strTableName." WHERE ID = ".$intID;
		$rsCounts = $DB->Query($strSql, true);
		if ($arCount = $rsCounts->Fetch())
		{
			return (0 < intval($arCount['CNT']) ? 1 : 0);
		}
		else
		{
			return false;
		}
	}

	public static function GetLastID()
	{
		if (self::$boolError)
			return false;

		global $DB;

		$strSql = "SELECT ID FROM ".self::$strTableName." WHERE 1 = 1 ORDER BY ID DESC LIMIT 1";
		$rsLasts = $DB->Query($strSql, true);
		if ($arLast = $rsLasts->Fetch())
		{
			return intval($arLast['ID']);
		}
		else
		{
			return 0;
		}
	}

	public static function SetID($intID)
	{
		if (self::$boolError)
			return false;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;

		global $DB;

		$strSql = "INSERT INTO ".self::$strTableName."(ID) VALUES(".$intID.")";
		$rsRes = $DB->Query($strSql, true);
		return (!(false === $rsRes));
	}

	public static function GetNeedConvert($intMinProduct)
	{
		if (self::$boolError)
			return false;

		global $DB;

		$intMinProduct = intval($intMinProduct);
		if (0 >= $intMinProduct)
			$intMinProduct = 0;

		$strSql = "SELECT COUNT(CD.ID) as CNT FROM ".self::$strMainTableName." CD WHERE
			CD.TYPE = ".CCatalogDiscount::ENTITY_ID." AND CD.VERSION = ".CCatalogDiscount::CURRENT_FORMAT."
			AND CD.ID NOT IN (SELECT CDT.ID FROM ".self::$strTableName." CDT WHERE 1=1)";
		if (0 < $intMinProduct)
		{
			$strSql .= " AND CD.ID > ".$intMinProduct;
		}
		$rsCounts = $DB->Query($strSql, true);
		if ($arCount = $rsCounts->Fetch())
		{
			return intval($arCount['CNT']);
		}
		else
		{
			return false;
		}
	}
}
?>