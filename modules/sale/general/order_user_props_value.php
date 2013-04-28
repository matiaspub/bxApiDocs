<?

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/index.php
 * @author Bitrix
 */
class CAllSaleOrderUserPropsValue
{
	
	/**
	 * <p>Функция возвращает параметры свойства с кодом ID профиля покупателя.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства профиля покупателя.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив параметров свойства с
	 * ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	 * </tr> <tr> <td>ID</td> <td>Код свойства профиля покупателя.</td> </tr> <tr>
	 * <td>USER_PROPS_ID</td> <td>Код профиля покупателя.</td> </tr> <tr> <td>ORDER_PROPS_ID</td>
	 * <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название свойства
	 * заказа.</td> </tr> <tr> <td>VALUE</td> <td>Значение свойства заказа, сохраненное
	 * в профиле покупателя.</td> </tr> </table><p>  </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__getbyid.51200d18.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_user_props_value ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция удаляет свойство с кодом ID профиля покупателя.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства профиля покупателя.
	 *
	 *
	 *
	 * @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	 * противном случае. </p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * CSaleOrderUserPropsValue::Delete(17);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__delete.7044751a.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_user_props_value WHERE ID = ".$ID."", true);
	}

	
	/**
	 * <p>Функция удаляет все свойства профиля покупателя для профиля с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код профиля покупателя.
	 *
	 *
	 *
	 * @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	 * противном случае.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__deleteall.96a04722.php
	 * @author Bitrix
	 */
	public static function DeleteAll($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_user_props_value WHERE USER_PROPS_ID = ".$ID."", true);
	}
	
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		$strUpdate = $DB->PrepareUpdate("b_sale_user_props_value", $arFields);
		$strSql = 
			"UPDATE b_sale_user_props_value SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

}
?>