<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/usertype.php");

class CUserTypeEntity extends CAllUserTypeEntity
{
	public static function CreatePropertyTables($entity_id)
	{
		global $DB, $APPLICATION;
		if(!$DB->TableExists("b_utm_".strtolower($entity_id)))
		{
			if(defined("MYSQL_TABLE_TYPE"))
				$DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", true);
			$rs = $DB->Query("
				create table b_utm_".strtolower($entity_id)." (
					ID int(11) not null auto_increment,
					VALUE_ID int(11) not null,
					FIELD_ID int(11) not null,
					VALUE text,
					VALUE_INT int,
					VALUE_DOUBLE float,
					VALUE_DATE datetime,
					INDEX ix_utm_".$entity_id."_1(FIELD_ID),
					INDEX ix_utm_".$entity_id."_2(VALUE_ID),
					PRIMARY KEY (ID)
				)
			", false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			if(!$rs)
			{
				$APPLICATION->ThrowException(GetMessage("USER_TYPE_TABLE_CREATION_ERROR",array(
					"#ENTITY_ID#"=>htmlspecialcharsbx($entity_id),
				)));
				return false;
			}
		}
		if(!$DB->TableExists("b_uts_".strtolower($entity_id)))
		{
			if(defined("MYSQL_TABLE_TYPE"))
				$DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", true);

			$rs = $DB->Query("
				create table b_uts_".strtolower($entity_id)." (
					VALUE_ID int(11) not null,
					PRIMARY KEY (VALUE_ID)
				)
			", false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			if(!$rs)
			{
				$APPLICATION->ThrowException(GetMessage("USER_TYPE_TABLE_CREATION_ERROR",array(
					"#ENTITY_ID#"=>htmlspecialcharsbx($entity_id),
				)));
				return false;
			}
		}
		return true;
	}

	public static function DropColumnSQL($strTable, $arColumns)
	{
		return array("ALTER TABLE ".$strTable." DROP ".implode(", DROP ", $arColumns));
	}
}

class CUserTypeManager extends CAllUserTypeManager
{

	public static function DateTimeToChar($FIELD_NAME)
	{
		global $DB;
		return "IF(EXTRACT(HOUR_SECOND FROM ".$FIELD_NAME.")>0, ".$DB->DateToCharFunction($FIELD_NAME, "FULL").", ".$DB->DateToCharFunction($FIELD_NAME, "SHORT").")";
	}
}

class CSQLWhere extends CAllSQLWhere
{
	function _Upper($field)
	{
		return "UPPER(".$field.")";
	}

	function _Empty($field)
	{
		return "(".$field." IS NULL OR ".$field." = '')";
	}

	function _NotEmpty($field)
	{
		return "(".$field." IS NOT NULL AND LENGTH(".$field.") > 0)";
	}

	function _StringEQ($field, $sql_value)
	{
		return $field." = '".$sql_value."'";
	}

	function _StringNotEQ($field, $sql_value)
	{
		return "(".$field." IS NULL OR ".$field." <> '".$sql_value."')";
	}

	function _StringIN($field, $sql_values)
	{
		return $field." in ('".implode("', '", $sql_values)."')";
	}

	function _StringNotIN($field, $sql_values)
	{
		return "(".$field." IS NULL OR ".$field." not in ('".implode("', '", $sql_values)."'))";
	}

	function _ExprEQ($field, CSQLWhereExpression $val)
	{
		return $field." = ".$val->compile();
	}

	function _ExprNotEQ($field, CSQLWhereExpression $val)
	{
		return "(".$field." IS NULL OR ".$field." <> ".$val->compile().")";
	}
}

/**
 * Эта переменная содержит экземпляр класса через API которого
 * и происходит работа с пользовательскими свойствами.
 * @global CUserTypeManager $GLOBALS['USER_FIELD_MANAGER']
 * @name $USER_FIELD_MANAGER
 */
$GLOBALS['USER_FIELD_MANAGER'] = new CUserTypeManager;
?>