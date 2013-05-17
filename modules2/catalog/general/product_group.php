<?
IncludeModuleLangFile(__FILE__);

/***********************************************************************/
/***********  CCatalogProductGroups  ***********************************/
/***********************************************************************/

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/index.php
 * @author Bitrix
 */
class CAllCatalogProductGroups
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "PRODUCT_ID") || $ACTION=="ADD") && intval($arFields["PRODUCT_ID"]) <= 0)
			return false;

		if ((is_set($arFields, "GROUP_ID") || $ACTION=="ADD") && intval($arFields["GROUP_ID"]) <= 0)
			return false;

		if ((is_set($arFields, "ACCESS_LENGTH") || $ACTION=="ADD"))
		{
			$arFields["ACCESS_LENGTH"] = intval($arFields["ACCESS_LENGTH"]);
			if ($arFields["ACCESS_LENGTH"] < 0)
				$arFields["ACCESS_LENGTH"] = 0;
		}

		if ((is_set($arFields, "ACCESS_LENGTH_TYPE") || $ACTION=="ADD") && !array_key_exists($arFields["ACCESS_LENGTH_TYPE"], $GLOBALS["CATALOG_TIME_PERIOD_TYPES"]))
		{
			$arTypeKeys = array_keys($GLOBALS["CATALOG_TIME_PERIOD_TYPES"]);
			$arFields["ACCESS_LENGTH_TYPE"] = $arRecurSchemeKeys[1];
		}

		return True;
	}

	
	/**
	 * <p>Метод выбирает параметры информации о связи между товаром и группами пользователей по коду информации ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код записи.
	 *
	 *
	 *
	 * @return array <p>Метод возвращает ассоциативный массив параметров информации о
	 * временном доступе с ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li>
	 * <b>PRODUCT_ID</b> - код товара;</li> <li> <b>GROUP_ID</b> - код группы
	 * пользователей;</li> <li> <b>ACCESS_LENGTH</b> - длина периода, на который
	 * пользователь привязывается к группе пользователей при покупке
	 * товара (0 - навсегда);</li> <li> <b>ACCESS_LENGTH_TYPE</b> - тип периода, на который
	 * пользователь привязывается к группе пользователей при покупке
	 * товара ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал, "S" -
	 * полугодие, "Y" - год).</li> </ul>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql =
			"SELECT CPG.ID, CPG.PRODUCT_ID, CPG.GROUP_ID, CPG.ACCESS_LENGTH, CPG.ACCESS_LENGTH_TYPE ".
			"FROM b_catalog_product2group CPG ".
			"WHERE CPG.ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	 * <p>Метод изменяет параметры записи с кодом ID информации о связи товаров и групп пользователей, к которым пользователь привязывается при покупке товара, в соответствии с данными из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  код записи 
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров новой информации о связи
	 * товаров и групп пользователей, ключами в котором являются
	 * названия параметров, а значениями - соответствующие значения.
	 * Допустимые ключи: <ul> <li> <b> PRODUCT_ID</b> - код товара;</li> <li> <b> GROUP_ID</b> - код
	 * группы пользователей;</li> <li> <b> ACCESS_LENGTH</b> - длина периода, на
	 * который пользователь привязывается к группе пользователей при
	 * покупке товара (0 - навсегда);</li> <li> <b> ACCESS_LENGTH_TYPE</b> - тип периода, на
	 * который пользователь привязывается к группе пользователей при
	 * покупке товара ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал,
	 * "S" - полугодие, "Y" - год).</li> </ul>
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает код измененной записи или <i>false</i> в случае
	 * ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		if (!CCatalogProductGroups::CheckFields("UPDATE", $arFields, $ID))
			return False;

		$strUpdate = $DB->PrepareUpdate("b_catalog_product2group", $arFields);
		$strUpdate = Trim($strUpdate);
		if (StrLen($strUpdate) > 0)
		{
			$strSql = "UPDATE b_catalog_product2group SET ".$strUpdate." WHERE ID = ".$ID." ";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}

	
	/**
	 * <p>Метод удаляет информацию о связи товаров и группы пользователей, к которой пользователь привязывается при покупке товара.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код удаляемой записи.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления и <i>false</i> в
	 * случае ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		return $DB->Query("DELETE FROM b_catalog_product2group WHERE ID = ".$ID." ", True);
	}

	public static function DeleteByGroup($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		return $DB->Query("DELETE FROM b_catalog_product2group WHERE GROUP_ID = ".$ID." ", True);
	}

	public static function OnGroupDelete($ID)
	{
		CCatalogProductGroups::DeleteByGroup($ID);
	}
}
?>