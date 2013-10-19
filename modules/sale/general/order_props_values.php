<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/index.php
 * @author Bitrix
 */
class CAllSaleOrderPropsValue
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "ORDER_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_ORDER_ID"), "EMPTY_ORDER_ID");
			return false;
		}
		
		if ((is_set($arFields, "ORDER_PROPS_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_PROPS_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_PROP_ID"), "EMPTY_ORDER_PROPS_ID");
			return false;
		}

		if (is_set($arFields, "ORDER_ID"))
		{
			if (!($arOrder = CSaleOrder::GetByID($arFields["ORDER_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_ID"], GetMessage("SKGOPV_NO_ORDER_ID")), "ERROR_NO_ORDER");
				return false;
			}
		}

		if (is_set($arFields, "ORDER_PROPS_ID"))
		{
			if (!($arOrder = CSaleOrderProps::GetByID($arFields["ORDER_PROPS_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_PROPS_ID"], GetMessage("SKGOPV_NO_PROP_ID")), "ERROR_NO_PROPERY");
				return false;
			}
			
			if (is_set($arFields, "ORDER_ID"))
			{
				$arFilter = Array(
						"ORDER_ID" => $arFields["ORDER_ID"],
						"ORDER_PROPS_ID" => $arFields["ORDER_PROPS_ID"],
					);
				if(IntVal($ID) > 0)
					$arFilter["!ID"] = $ID;
				$dbP = CSaleOrderPropsValue::GetList(Array(), $arFilter);
				if($arP = $dbP->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_DUPLICATE_PROP_ID", Array("#ID#" => $arFields["ORDER_PROPS_ID"], "#ORDER_ID#" => $arFields["ORDER_ID"])), "ERROR_DUPLICATE_PROP_ID");
					return false;
				}
			}
		}

		return True;
	}

	
	/**
	 * <p>Функция возвращает параметры значения с кодом ID свойства заказа.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код значения свойства заказа.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив параметров значения
	 * свойства с ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	 * <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код значения свойства заказа.</td> </tr>
	 * <tr> <td>ORDER_ID</td> <td>Код заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td> <td>Код
	 * свойства.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr> <td>VALUE</td>
	 * <td>Значение свойства.</td> </tr> <tr> <td>CODE</td> <td>Мнемонический код
	 * свойства.</td> </tr> </table><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getbyid.54043fd5.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_order_props_value ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция обновляет параметры значения с кодом ID свойства заказа на параметры из массива arFields </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код значения свойства заказа.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров значения свойства, ключами в
	 * котором являются названия параметров значения свойства, а
	 * значениями - соответствующие новые значения. <br><br> Допустимые
	 * ключи: <ul> <li> <b>ORDER_ID</b> - код заказа;</li> <li> <b>ORDER_PROPS_ID</b> - код
	 * свойства;</li> <li> <b>NAME</b> - название свойства;</li> <li> <b>VALUE</b> - значение
	 * свойства;</li> <li> <b>CODE</b> - мнемонический код свойства.</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Функция возвращает код обновленного значения свойства или
	 * <i>false</i> в случае ошибки.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>CSaleOrderPropsValue::Update(8, array("CODE"=&gt;"ADDRESS"));<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__update.4d3a46b6.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if (!CSaleOrderPropsValue::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_props_value", $arFields);
		$strSql = 
			"UPDATE b_sale_order_props_value SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ID = ".$ID." ";
		return $DB->Query($strSql, True);
	}

	public static function DeleteByOrder($orderID)
	{
		global $DB;
		$orderID = IntVal($orderID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ORDER_ID = ".$orderID." ";
		return $DB->Query($strSql, True);
	}
}
?>