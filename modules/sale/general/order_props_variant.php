<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/index.php
 * @author Bitrix
 */
class CAllSaleOrderPropsVariant
{
	
	/**
	* <p>Метод возвращает параметры варианта значения свойства заказа по коду свойства заказа и значению. Метод динамичный.</p>
	*
	*
	* @param int $PropID  Код свойства заказа. </ht
	*
	* @param string $Value  Значение свойства. </h
	*
	* @return array <p>Возвращается ассоциативный массив значений параметров заказа
	* с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Код</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код варианта значения свойства заказа.</td> </tr> <tr>
	* <td>ORDER_PROPS_ID</td> <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название
	* варианта.</td> </tr> <tr> <td>VALUE</td> <td>Значение варианта.</td> </tr> <tr> <td>SORT</td>
	* <td>Индекс сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание варианта
	* значения свойства заказа.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arVal = CSaleOrderPropsVariant::GetByValue(12, "F");
	* echo $arVal["NAME"];
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__getbyvalue.48f24a76.php
	* @author Bitrix
	*/
	public static function GetByValue($PropID, $Value)
	{
		$PropID = IntVal($PropID);
		$db_res = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=>$PropID, "VALUE"=>$Value));
		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Метод возвращает параметры варианта с кодом ID значения свойства заказа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код варианта значения свойства заказа.
	*
	* @return array <p>Возвращается ассоциативный массив значений параметров заказа
	* с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Код</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код варианта значения свойства заказа.</td> </tr> <tr>
	* <td>ORDER_PROPS_ID</td> <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название
	* варианта.</td> </tr> <tr> <td>VALUE</td> <td>Значение варианта.</td> </tr> <tr> <td>SORT</td>
	* <td>Индекс сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание варианта
	* значения свойства заказа.</td> </tr> </table> <p>  </p
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__getbyid.4e5836a2.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_order_props_variant ".
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

		if ((is_set($arFields, "VALUE") || $ACTION=="ADD") && strlen($arFields["VALUE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_VAR"), "ERROR_NO_VALUE");
			return false;
		}
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_NAME"), "ERROR_NO_NAME");
			return false;
		}
		if ((is_set($arFields, "ORDER_PROPS_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_PROPS_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_CODE"), "ERROR_NO_ORDER_PROPS_ID");
			return false;
		}

		if (is_set($arFields, "ORDER_PROPS_ID"))
		{
			if (!($arOrder = CSaleOrderProps::GetByID($arFields["ORDER_PROPS_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_PROPS_ID"], GetMessage("SKGOPV_NO_PROP")), "ERROR_NO_PROPERY");
				return false;
			}
		}

		return True;
	}

	
	/**
	* <p>Метод обновляет параметры варианта с кодом ID на значения из массива arFields. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код варианта значения свойства заказа.
	*
	* @param array $arFields  Ассоциативный массив параметров нового варианта значения
	* свойства заказа, ключами в котором являются названия
	* параметров.<br><br> Допустимые ключи:<ul> <li> <b>ORDER_PROPS_ID</b> - код свойства
	* заказа;</li> <li> <b>NAME</b> - название варианта;</li> <li> <b>VALUE</b> - значение
	* варианта;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>DESCRIPTION</b> -
	* описание варианта.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного варианта значения или <i>false</i> в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__update.c68428cc.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		
		if (!CSaleOrderPropsVariant::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_props_variant", $arFields);

		$strSql = "UPDATE b_sale_order_props_variant SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	
	/**
	* <p>Метод удаляет вариант с кодом ID значения свойства заказа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код варианта значения свойства заказа.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CSaleOrderPropsVariant::Delete(12);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__delete.c78c095e.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_order_props_variant WHERE ID = ".$ID."", true);
	}

	
	/**
	* <p>Метод удаляет все варианты значений для свойства с кодом ID заказа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код свойства заказа. </ht
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__deleteall.d5643ee4.php
	* @author Bitrix
	*/
	public static function DeleteAll($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_order_props_variant WHERE ORDER_PROPS_ID = ".$ID."", true);
	}
}
?>