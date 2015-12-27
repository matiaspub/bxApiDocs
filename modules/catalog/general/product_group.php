<?
IncludeModuleLangFile(__FILE__);


/**
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
	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.add.php">CCatalogProductGroups::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.update.php">CCatalogProductGroups::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.add.php">CCatalogProductGroups::Add</a>;</li>
	* <li> <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.update.php">CCatalogProductGroups::Update</a>.</li>
	* </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров информации о связи товаров и
	* групп пользователей. Допустимые ключи: <ul> <li> <b>PRODUCT_ID</b> - код
	* товара;</li> <li> <b>GROUP_ID</b> - код группы пользователей;</li> <li> <b>ACCESS_LENGTH</b>
	* - длина периода, на который пользователь привязывается к группе
	* пользователей при покупке товара (0 - навсегда);</li> <li>
	* <b>ACCESS_LENGTH_TYPE</b> - тип периода, на который пользователь
	* привязывается к группе пользователей при покупке товара ("H" - час,
	* "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал, "S" - полугодие, "Y" -
	* год).</li> </ul>
	*
	* @param int $ID = 0 Код записи с информацией о связи товаров и групп пользователей, к
	* которым пользователь привязывается при покупке товаров.
	* Параметр является необязательным и имеет смысл только для $ACTION =
	* 'UPDATE'.
	*
	* @return bool <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если метод вернул false, с помощью $APPLICATION-&gt;GetException() можно
	* получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.add.php">CCatalogProductGroups::Add</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.update.php">CCatalogProductGroups::Update</a></li>
	* </ul> </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/checkfields.php
	* @author Bitrix
	*/
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

		if ((is_set($arFields, "ACCESS_LENGTH_TYPE") || $ACTION=="ADD") && !array_key_exists($arFields["ACCESS_LENGTH_TYPE"], CCatalogProduct::GetTimePeriodTypes(true)))
		{
			$arFields["ACCESS_LENGTH_TYPE"] = CCatalogProduct::TIME_PERIOD_DAY;
		}

		return true;
	}

	
	/**
	* <p>Метод выбирает параметры информации о связи между товаром и группами пользователей по коду информации ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код записи.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров информации о
	* временном доступе с ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li>
	* <b>PRODUCT_ID</b> - код товара;</li> <li> <b>GROUP_ID</b> - код группы
	* пользователей;</li> <li> <b>ACCESS_LENGTH</b> - длина периода, на который
	* пользователь привязывается к группе пользователей при покупке
	* товара (0 - навсегда);</li> <li> <b>ACCESS_LENGTH_TYPE</b> - тип периода, на который
	* пользователь привязывается к группе пользователей при покупке
	* товара ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал, "S" -
	* полугодие, "Y" - год).</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		$strSql = "SELECT ID, PRODUCT_ID, GROUP_ID, ACCESS_LENGTH, ACCESS_LENGTH_TYPE FROM b_catalog_product2group WHERE ID = ".$ID;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Метод изменяет параметры записи с кодом ID информации о связи товаров и групп пользователей, к которым пользователь привязывается при покупке товара, в соответствии с данными из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  код записи </bod
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
	* @return bool <p>Метод возвращает код измененной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
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
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_product2group SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет информацию о связи товаров и группы пользователей, к которой пользователь привязывается при покупке товара. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код удаляемой записи.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления и <i>false</i> в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		return $DB->Query("DELETE FROM b_catalog_product2group WHERE ID = ".$ID, true);
	}

	
	/**
	* <p>Метод удаляет привязку к группе пользователей с кодом <i>ID</i> из всех карточек товаров-подписок (продажа прав). Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления и <i>false</i> в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/deletebygroup.php
	* @author Bitrix
	*/
	public static function DeleteByGroup($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		return $DB->Query("DELETE FROM b_catalog_product2group WHERE GROUP_ID = ".$ID, true);
	}

	public static function OnGroupDelete($ID)
	{
		CCatalogProductGroups::DeleteByGroup($ID);
	}
}
?>