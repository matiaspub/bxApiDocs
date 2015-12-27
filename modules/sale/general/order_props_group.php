<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsgroup/index.php
 * @author Bitrix
 */
class CAllSaleOrderPropsGroup
{
	
	/**
	* <p>Метод возвращает параметры группы свойств заказа с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы заказов. </ht
	*
	* @return array <p>Возвращается ассоциативный массив параметров группы свойств с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код группы заказов.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название группы.</td> </tr> <tr> <td>SORT</td>
	* <td>Индекс сортировки.</td> </tr> </table> <p>  </p<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if ($arPropsGroup = CSaleOrderPropsGroup::GetByID(3))
	*    echo $arPropsGroup["NAME"];
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsgroup/csaleorderpropsgroup__getbyid.e6e82420.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_order_props_group ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $USER;

		if (is_set($arFields, "PERSON_TYPE_ID") && $ACTION!="ADD")
			UnSet($arFields["PERSON_TYPE_ID"]);

		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPG_EMPTY_PERS_TYPE"), "ERROR_NO_PERSON_TYPE");
			return false;
		}
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPG_EMPTY_GROUP"), "ERROR_NO_NAME");
			return false;
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], GetMessage("SKGOPG_NO_PERS_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		return True;
	}

	
	/**
	* <p>Метод обновляет параметры группы заказов с кодом ID на параметры из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы заказов. </ht
	*
	* @param array $arFields  Ассоциативный массив параметров группы свойств, в котором
	* ключами являются названия параметров, а значениями - новые
	* значения.<br><br> Допустимые ключи: <ul> <li> <b>PERSON_TYPE_ID</b> - тип
	* плательщика;</li> <li> <b>NAME</b> - название группы (группа привязывается
	* к типу плательщика, тип плательщика привязывается к сайту, сайт
	* привязывается к языку, название задается на этом языке);</li> <li>
	* <b>SORT</b> - индекс сортировки.</li> </ul>
	*
	* @return int <p>Возвращается код добавленной группы или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsgroup/csaleorderpropsgroup__update.169e4e27.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);

		if (!CSaleOrderPropsGroup::CheckFields("UPDATE", $arFields, $ID)) return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_props_group", $arFields);

		$strSql = "UPDATE b_sale_order_props_group SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	
	/**
	* <p>Метод удаляет группу свойств с кодом ID. Так же удаляются свойства этой группы и другие сопутствующие данные. Значения свойств этой группы, привязанные к заказам, отвязываются от удаляемых свойств. <br> Если необходимо удалить только группу, то сначала от нее необходимо отвязать все свойства. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы свойств. </ht
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsgroup/csaleorderpropsgroup__delete.cae2758a.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$db_orderProps = CSaleOrderProps::GetList(($by="PROPS_GROUP_ID"), ($order="ASC"), Array("PROPS_GROUP_ID"=>$ID));
		while ($arOrderProps = $db_orderProps->Fetch())
		{
			$DB->Query("DELETE FROM b_sale_order_props_variant WHERE ORDER_PROPS_ID = ".$arOrderProps["ID"]."", true);
			$DB->Query("UPDATE b_sale_order_props_value SET ORDER_PROPS_ID = NULL WHERE ORDER_PROPS_ID = ".$arOrderProps["ID"]."", true);
			$DB->Query("DELETE FROM b_sale_order_props_relation WHERE PROPERTY_ID = ".$arOrderProps["ID"]."", true);
			$DB->Query("DELETE FROM b_sale_user_props_value WHERE ORDER_PROPS_ID = ".$arOrderProps["ID"]."", true);
		}
		$DB->Query("DELETE FROM b_sale_order_props WHERE PROPS_GROUP_ID = ".$ID."", true);
		CSaleOrderUserProps::ClearEmpty();

		return $DB->Query("DELETE FROM b_sale_order_props_group WHERE ID = ".$ID."", true);
	}
}
?>