<?

/**
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
	* <p>Метод возвращает параметры свойства с кодом ID профиля покупателя. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код свойства профиля покупателя.
	*
	* @return array <p>Возвращается ассоциативный массив параметров свойства с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код свойства профиля покупателя.</td> </tr> <tr>
	* <td>USER_PROPS_ID</td> <td>Код профиля покупателя.</td> </tr> <tr> <td>ORDER_PROPS_ID</td>
	* <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название свойства
	* заказа.</td> </tr> <tr> <td>VALUE</td> <td>Значение свойства заказа, сохраненное
	* в профиле покупателя.</td> </tr> </table> <p>  </p
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__getbyid.51200d18.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if(CSaleLocation::isLocationProMigrated())
		{
			$strSql =
				"SELECT V.ID, V.USER_PROPS_ID, V.ORDER_PROPS_ID, V.NAME, ".self::getPropertyValueFieldSelectSql('V').", P.TYPE ".
				"FROM b_sale_user_props_value V ".
				"INNER JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID) ".
				self::getLocationTableJoinSql('V').
				"WHERE V.ID = ".$ID."";
		}
		else
		{
			$strSql =
				"SELECT * ".
				"FROM b_sale_user_props_value ".
				"WHERE ID = ".$ID."";
		}

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Метод удаляет свойство с кодом ID профиля покупателя. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код свойства профиля покупателя.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае. </p> <a name="examples"></a>
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
	* <p>Метод удаляет все свойства профиля покупателя для профиля с кодом ID. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код профиля покупателя.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
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
	
	
	/**
	* <p>Метод обновляет свойство профиля покупателя в соответствии с массивом параметров arFields. Метод динамичный.</p>
	*
	*
	* @param mixed $ID  Код значения свойства профиля покупателя.
	*
	* @param mixed $arFields  Массив значений свойств.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		// need to check here if we got CODE or ID came
		if(isset($arFields['VALUE']) && ((string) $arFields['VALUE'] != '') && CSaleLocation::isLocationProMigrated())
		{
			$propValue = self::GetByID($ID);

			if($propValue['TYPE'] == 'LOCATION')
			{
				$arFields['VALUE'] = CSaleLocation::tryTranslateIDToCode($arFields['VALUE']);
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_sale_user_props_value", $arFields);
		$strSql = 
			"UPDATE b_sale_user_props_value SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	protected static function getPropertyValueFieldSelectSql($tableAlias = 'PV', $propTableAlias = 'P')
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);
		$propTableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($propTableAlias);

		if(CSaleLocation::isLocationProMigrated())
			return "
				CASE

					WHEN
						".$propTableAlias.".TYPE = 'LOCATION'
					THEN
						CAST(L.ID as ".\Bitrix\Sale\Location\DB\Helper::getSqlForDataType('char', 255).")

					ELSE
						".$tableAlias.".VALUE
				END as VALUE, ".$tableAlias.".VALUE as VALUE_ORIG";
		else
			return $tableAlias.".VALUE";
	}

	protected static function getLocationTableJoinSql($tableAlias = 'PV', $propTableAlias = 'P')
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);
		$propTableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($propTableAlias);

		if(CSaleLocation::isLocationProMigrated())
			return "LEFT JOIN b_sale_location L ON (".$propTableAlias.".TYPE = 'LOCATION' AND ".$tableAlias.".VALUE IS NOT NULL AND (".$tableAlias.".VALUE = L.CODE))";
		else
			return " ";
	}

	protected static function translateLocationIDToCode($id, $orderPropId)
	{
		if(!CSaleLocation::isLocationProMigrated())
			return $id;

		$prop = CSaleOrderProps::GetByID($orderPropId);
		if(isset($prop['TYPE']) && $prop['TYPE'] == 'LOCATION')
		{
			if((string) $id === (string) intval($id)) // real ID, need to translate
			{
				return CSaleLocation::tryTranslateIDToCode($id);
			}
		}

		return $id;
	}

	protected static function addPropertyValueField($tableAlias = 'V', &$arFields, &$arSelectFields)
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);

		// locations kept in CODEs, but must be shown as IDs
		if(CSaleLocation::isLocationProMigrated())
		{
			$arSelectFields = array_merge(array('PROP_TYPE'), $arSelectFields); // P.TYPE should be there and go above our join

			$arFields['VALUE'] = array("FIELD" => "
				CASE

					WHEN
						P.TYPE = 'LOCATION'
					THEN
						CAST(L.ID as ".\Bitrix\Sale\Location\DB\Helper::getSqlForDataType('char', 255).")

					ELSE
						".$tableAlias.".VALUE
				END
			", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_location L ON (P.TYPE = 'LOCATION' AND ".$tableAlias.".VALUE IS NOT NULL AND ".$tableAlias.".VALUE = L.CODE)");
			$arFields['VALUE_ORIG'] = array("FIELD" => $tableAlias.".VALUE", "TYPE" => "string");
		}
		else
		{
			$arFields['VALUE'] = array("FIELD" => $tableAlias.".VALUE", "TYPE" => "string");
		}
	}

//	protected static function getList15($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
//	{
//		$query = new \Bitrix\Sale\Compatible\OrderQueryLocation(Bitrix\Sale\Internals\UserPropsValueTable::getEntity());
//		$query->addLocationRuntimeField('DEFAULT_VALUE');????
//		$query->addAliases(array(
//			// for GetList
//			'PROP_ID'              => 'PROPERTY.ID',
//			'PROP_PERSON_TYPE_ID'  => 'PROPERTY.PERSON_TYPE_ID',
//			'PROP_NAME'            => 'PROPERTY.NAME',
//			'PROP_TYPE'            => 'PROPERTY.TYPE',
//			'PROP_REQUIED'         => 'PROPERTY.REQUIRED',
//			'PROP_DEFAULT_VALUE'   => 'PROPERTY.DEFAULT_VALUE',
//			'PROP_SORT'            => 'PROPERTY.SORT',
//			'PROP_USER_PROPS'      => 'PROPERTY.USER_PROPS',
//			'PROP_IS_LOCATION'     => 'PROPERTY.IS_LOCATION',
//			'PROP_PROPS_GROUP_ID'  => 'PROPERTY.PROPS_GROUP_ID',
//			'PROP_DESCRIPTION'     => 'PROPERTY.DESCRIPTION',
//			'PROP_IS_EMAIL'        => 'PROPERTY.IS_EMAIL',
//			'PROP_IS_PROFILE_NAME' => 'PROPERTY.IS_PROFILE_NAME',
//			'PROP_IS_PAYER'        => 'PROPERTY.IS_PAYER',
//			'PROP_IS_LOCATION4TAX' => 'PROPERTY.IS_LOCATION4TAX',
//			'PROP_IS_ZIP'          => 'PROPERTY.IS_ZIP',
//			'PROP_CODE'            => 'PROPERTY.CODE',
//			'PROP_ACTIVE'          => 'PROPERTY.ACTIVE',
//			'PROP_UTIL'            => 'PROPERTY.UTIL',
//
//
//
//			"PROP_SIZE1" => array("FIELD" => "P.SIZE1", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
//			"PROP_SIZE2" => array("FIELD" => "P.SIZE2", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
//
//			"VARIANT_ID" => array("FIELD" => "PV.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//			"VARIANT_ORDER_PROPS_ID" => array("FIELD" => "PV.ORDER_PROPS_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//			"VARIANT_NAME" => array("FIELD" => "PV.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//			"VARIANT_VALUE" => array("FIELD" => "PV.VALUE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//			"VARIANT_SORT" => array("FIELD" => "PV.SORT", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//			"VARIANT_DESCRIPTION" => array("FIELD" => "PV.DESCRIPTION", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
//
//			"USER_VALUE_NAME" => array("FIELD" => "PV.NAME", "TYPE" => "string"),
//			"TYPE" => array("FIELD" => "P.TYPE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
//			"SORT" => array("FIELD" => "P.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
//			"CODE" => array("FIELD" => "P.CODE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
//
//		));
//	}
}
?>