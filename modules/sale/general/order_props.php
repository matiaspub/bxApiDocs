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
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/index.php
 * @author Bitrix
 */
class CAllSaleOrderProps
{
	static function DoProcessOrder(&$arOrder, $arOrderPropsValues, &$arErrors, &$arWarnings)
	{
		if (!is_array($arOrderPropsValues))
			$arOrderPropsValues = array();

		$arUser = null;

		$dbOrderProps = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			//array("PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"], "ACTIVE" => "Y", "UTIL" => "N"),
			array("PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"], "ACTIVE" => "Y"),
			false,
			false,
			array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", "IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL",
				"REQUIED", "SORT", "IS_ZIP", "CODE", "DEFAULT_VALUE")
		);
		while ($arOrderProp = $dbOrderProps->Fetch())
		{
			if (!array_key_exists($arOrderProp["ID"], $arOrderPropsValues))
			{
				$curVal = $arOrderProp["DEFAULT_VALUE"];
				if (strlen($curVal) <= 0)
				{
					if ($arOrderProp["IS_EMAIL"] == "Y" || $arOrderProp["IS_PAYER"] == "Y")
					{
						if ($arUser == null)
						{
							$dbUser = CUser::GetList($by = "ID", $order = "desc", array("ID_EQUAL_EXACT" => $arOrder["USER_ID"]));
							$arUser = $dbUser->Fetch();
						}
						if ($arOrderProp["IS_EMAIL"] == "Y")
							$curVal = is_array($arUser) ? $arUser["EMAIL"] : "";
						elseif ($arOrderProp["IS_PAYER"] == "Y")
							$curVal = is_array($arUser) ? $arUser["NAME"].(strlen($arUser["NAME"]) <= 0 || strlen($arUser["LAST_NAME"]) <= 0 ? "" : " ").$arUser["LAST_NAME"] : "";
					}
				}
			}
			else
			{
				$curVal = $arOrderPropsValues[$arOrderProp["ID"]];
			}

			if ((!is_array($curVal) && strlen($curVal) > 0) || (is_array($curVal) && count($curVal) > 0))
			{
				//if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "MULTISELECT" || $arOrderProp["TYPE"] == "RADIO")
				if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "RADIO")
				{
					$arVariants = array();
					$dbVariants = CSaleOrderPropsVariant::GetList(
						array("SORT" => "ASC", "NAME" => "ASC"),
						array("ORDER_PROPS_ID" => $arOrderProp["ID"]),
						false,
						false,
						array("*")
					);
					while ($arVariant = $dbVariants->Fetch())
						$arVariants[] = $arVariant["VALUE"];

					if (!is_array($curVal))
						$curVal = array($curVal);

					$arKeys = array_keys($curVal);
					foreach ($arKeys as $k)
					{
						if (!in_array($curVal[$k], $arVariants))
							unset($curVal[$k]);
					}

					if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "RADIO")
						$curVal = array_shift($curVal);
				}
				elseif ($arOrderProp["TYPE"] == "LOCATION")
				{
					if (is_array($curVal))
						$curVal = array_shift($curVal);
					$curVal = intval($curVal);
					$dbVariants = CSaleLocation::GetList(
						array(),
						array("ID" => $curVal),
						false,
						false,
						array("ID")
					);
					if ($arVariant = $dbVariants->Fetch())
						$curVal = intval($arVariant["ID"]);
					else
						$curVal = null;
				}
			}

			if ($arOrderProp["TYPE"] == "LOCATION" && ($arOrderProp["IS_LOCATION"] == "Y" || $arOrderProp["IS_LOCATION4TAX"] == "Y"))
			{
				$curVal = intval($curVal);
				if ($arOrderProp["IS_LOCATION"] == "Y")
					$arOrder["DELIVERY_LOCATION"] = $curVal;
				if ($arOrderProp["IS_LOCATION4TAX"] == "Y")
					$arOrder["TAX_LOCATION"] = $curVal;

				if ($curVal <= 0)
					$bErrorField = true;
			}
			elseif ($arOrderProp["IS_PROFILE_NAME"] == "Y" || $arOrderProp["IS_PAYER"] == "Y" || $arOrderProp["IS_EMAIL"] == "Y" || $arOrderProp["IS_ZIP"] == "Y")
			{
				$curVal = trim($curVal);
				if ($arOrderProp["IS_PROFILE_NAME"] == "Y")
					$arOrder["PROFILE_NAME"] = $curVal;
				if ($arOrderProp["IS_PAYER"] == "Y")
					$arOrder["PAYER_NAME"] = $curVal;
				if ($arOrderProp["IS_ZIP"] == "Y")
					$arOrder["DELIVERY_LOCATION_ZIP"] = $curVal;
				if ($arOrderProp["IS_EMAIL"] == "Y")
				{
					$arOrder["USER_EMAIL"] = $curVal;
					if (!check_email($curVal))
						$arWarnings[] = array("CODE" => "PARAM", "TEXT" => str_replace(array("#EMAIL#", "#NAME#"), array(htmlspecialcharsbx($curVal), htmlspecialcharsbx($arOrderProp["NAME"])), GetMessage("SALE_GOPE_WRONG_EMAIL")));
				}

				if (strlen($curVal) <= 0)
					$bErrorField = true;
			}
			elseif ($arOrderProp["REQUIED"] == "Y")
			{
				if ($arOrderProp["TYPE"] == "TEXT" || $arOrderProp["TYPE"] == "TEXTAREA" || $arOrderProp["TYPE"] == "RADIO" || $arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "CHECKBOX")
				{
					if (strlen($curVal) <= 0)
						$bErrorField = true;
				}
				elseif ($arOrderProp["TYPE"] == "LOCATION")
				{
					if (intval($curVal) <= 0)
						$bErrorField = true;
				}
				elseif ($arOrderProp["TYPE"] == "MULTISELECT")
				{
					//if (!is_array($curVal) || count($curVal) <= 0)
					if (strlen($curVal) <= 0)
						$bErrorField = true;
				}
			}

			if ($bErrorField) 
			{
				$arWarnings[] = array("CODE" => "PARAM", "TEXT" => str_replace("#NAME#", htmlspecialcharsbx($arOrderProp["NAME"]), GetMessage("SALE_GOPE_FIELD_EMPTY")));
				$bErrorField = false;
			}

			$arOrder["ORDER_PROP"][$arOrderProp["ID"]] = $curVal;
		}
	}

	static function DoSaveOrderProps($orderId, $personTypeId, $arOrderProps, &$arErrors)
	{
		$arIDs = array();
		$dbResult = CSaleOrderPropsValue::GetList(
			array(),
			//array("ORDER_ID" => $orderId, "PROP_UTIL" => "N"),
			array("ORDER_ID" => $orderId),
			false,
			false,
			array("ID", "ORDER_PROPS_ID")
		);
		while ($arResult = $dbResult->Fetch())
			$arIDs[$arResult["ORDER_PROPS_ID"]] = $arResult["ID"];

		$dbOrderProperties = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			//array("PERSON_TYPE_ID" => $personTypeId, "ACTIVE" => "Y", "UTIL" => "N"),
			array("PERSON_TYPE_ID" => $personTypeId, "ACTIVE" => "Y"),
			false,
			false,
			array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT")
		);
		while ($arOrderProperty = $dbOrderProperties->Fetch())
		{
			$curVal = $arOrderProps[$arOrderProperty["ID"]];
			if (($arOrderProperty["TYPE"] == "MULTISELECT") && is_array($curVal))
				$curVal = implode(",", $curVal);

			if (strlen($curVal) > 0)
			{
				$arFields = array(
					"ORDER_ID" => $orderId,
					"ORDER_PROPS_ID" => $arOrderProperty["ID"],
					"NAME" => $arOrderProperty["NAME"],
					"CODE" => $arOrderProperty["CODE"],
					"VALUE" => $curVal
				);
				
				if (array_key_exists($arOrderProperty["ID"], $arIDs))
				{
					CSaleOrderPropsValue::Update($arIDs[$arOrderProperty["ID"]], $arFields);
					unset($arIDs[$arOrderProperty["ID"]]);
				}
				else
				{
					CSaleOrderPropsValue::Add($arFields);
				}
			}
		}

		foreach ($arIDs as $id)
			CSaleOrderPropsValue::Delete($id);
	}

	
	/**
	 * <p>Функция возвращает параметры свойства с кодом ID заказа.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства заказа.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив параметров свойства с
	 * ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	 * </tr> <tr> <td>ID</td> <td>Код свойства заказа.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	 * плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr>
	 * <td>TYPE</td> <td>Тип свойства. Допустимые значения:<ul> <li> <b>CHECKBOX</b> -
	 * флаг,</li> <li> <b>TEXT</b> - строка текста,</li> <li> <b>SELECT</b> - выпадающий список
	 * значений, </li> <li> <b>MULTISELECT</b> - список со множественным выбором,</li> <li>
	 * <b>TEXTAREA</b> - многострочный текст,</li> <li> <b>LOCATION</b> - местоположение,</li>
	 * <li> <b>RADIO</b> - переключатель.</li> </ul> </td> </tr> <tr> <td>REQUIED</td> <td>Флаг (Y/N)
	 * обязательное ли поле.</td> </tr> <tr> <td>DEFAULT_VALUE</td> <td>Значение по
	 * умолчанию.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr>
	 * <td>USER_PROPS</td> <td>Флаг (Y/N) входит ли это свойство в профиль
	 * покупателя.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг (Y/N) использовать ли
	 * значение свойства как местоположение покупателя для расчёта
	 * стоимости доставки (только для свойств типа LOCATION)</td> </tr> <tr>
	 * <td>PROPS_GROUP_ID</td> <td>Код группы свойств.</td> </tr> <tr> <td>SIZE1</td> <td>Ширина поля
	 * (размер по горизонтали).</td> </tr> <tr> <td>SIZE2</td> <td>Высота поля (размер по
	 * вертикали).</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание свойства.</td> </tr> <tr>
	 * <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать ли значение свойства как E-Mail
	 * покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td> <td>Флаг (Y/N) использовать ли
	 * значение свойства как название профиля покупателя.</td> </tr> <tr>
	 * <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли значение свойства как имя
	 * плательщика.</td> </tr> <tr> <td>IS_LOCATION4TAX</td> <td>Флаг (Y/N) использовать ли
	 * значение свойства как местоположение покупателя для расчёта
	 * налогов (только для свойств типа LOCATION)</td> </tr> <tr> <td>CODE</td>
	 * <td>Мнемонический код свойства.</td> </tr> </table><p>  </p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if ($arOrderProps = CSaleOrderProps::GetByID($ID))
	 * {
	 *    echo "&lt;pre&gt;";
	 *    print_r($arOrderProps);
	 *    echo "&lt;/pre&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__getbyid.39564dea.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_order_props ".
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

		if (is_set($arFields, "PERSON_TYPE_ID") && $ACTION != "ADD")
			UnSet($arFields["PERSON_TYPE_ID"]);

		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_EMPTY_PERS_TYPE"), "ERROR_NO_PERSON_TYPE");
			return false;
		}
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_EMPTY_PROP_NAME"), "ERROR_NO_NAME");
			return false;
		}
		if ((is_set($arFields, "TYPE") || $ACTION=="ADD") && strlen($arFields["TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_EMPTY_PROP_TYPE"), "ERROR_NO_TYPE");
			return false;
		}

		if (is_set($arFields, "REQUIED") && $arFields["REQUIED"]!="Y")
			$arFields["REQUIED"]="N";
		if (is_set($arFields, "USER_PROPS") && $arFields["USER_PROPS"]!="Y")
			$arFields["USER_PROPS"]="N";
		if (is_set($arFields, "IS_LOCATION") && $arFields["IS_LOCATION"]!="Y")
			$arFields["IS_LOCATION"]="N";
		if (is_set($arFields, "IS_LOCATION4TAX") && $arFields["IS_LOCATION4TAX"]!="Y")
			$arFields["IS_LOCATION4TAX"]="N";
		if (is_set($arFields, "IS_EMAIL") && $arFields["IS_EMAIL"]!="Y")
			$arFields["IS_EMAIL"]="N";
		if (is_set($arFields, "IS_PROFILE_NAME") && $arFields["IS_PROFILE_NAME"]!="Y")
			$arFields["IS_PROFILE_NAME"]="N";
		if (is_set($arFields, "IS_PAYER") && $arFields["IS_PAYER"]!="Y")
			$arFields["IS_PAYER"]="N";
		if (is_set($arFields, "IS_FILTERED") && $arFields["IS_FILTERED"]!="Y")
			$arFields["IS_FILTERED"]="N";
		if (is_set($arFields, "IS_ZIP") && $arFields["IS_ZIP"]!="Y")
			$arFields["IS_ZIP"]="N";

		if (is_set($arFields, "IS_LOCATION") && is_set($arFields, "TYPE") && $arFields["IS_LOCATION"]=="Y" && $arFields["TYPE"]!="LOCATION")
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_WRONG_PROP_TYPE"), "ERROR_WRONG_TYPE1");
			return false;
		}
		if (is_set($arFields, "IS_LOCATION4TAX") && is_set($arFields, "TYPE") && $arFields["IS_LOCATION4TAX"]=="Y" && $arFields["TYPE"]!="LOCATION")
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_WRONG_PROP_TYPE"), "ERROR_WRONG_TYPE2");
			return false;
		}

		if ((is_set($arFields, "PROPS_GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["PROPS_GROUP_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOP_EMPTY_PROP_GROUP"), "ERROR_NO_GROUP");
			return false;
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], GetMessage("SKGOP_NO_PERS_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		return True;
	}

	
	/**
	 * <p>Функция изменяет параметры свойства с кодом ID заказа на значения из массива arFields </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства заказа.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив новых значений параметров свойства заказа,
	 * в котором ключами являются названия параметров свойства, а
	 * значениями - значения этих параметров.<br> Допустимые ключи: <ul> <li>
	 * <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>NAME</b> - название свойства (тип
	 * плательщика зависит от сайта, а сайт - от языка; название должно
	 * быть на соответствующем языке);</li> <li> <b>TYPE</b> - тип свойства.
	 * Допустимые значения: <ul> <li> <b>CHECKBOX</b> - флаг;</li> <li> <b>TEXT</b> - строка
	 * текста;</li> <li> <b>SELECT</b> - выпадающий список значений;</li> <li> <b>MULTISELECT</b>
	 * - список со множественным выбором;</li> <li> <b>TEXTAREA</b> - многострочный
	 * текст;</li> <li> <b>LOCATION</b> - местоположение;</li> <li> <b>RADIO</b> -
	 * переключатель.</li> </ul> </li> <li> <b>REQUIED</b> - флаг (Y/N) обязательное ли
	 * поле;</li> <li> <b>DEFAULT_VALUE</b> - значение по умолчанию;</li> <li> <b>SORT</b> -
	 * индекс сортировки;</li> <li> <b>USER_PROPS</b> - флаг (Y/N) входит ли это
	 * свойство в профиль покупателя;</li> <li> <b>IS_LOCATION</b> - флаг (Y/N)
	 * использовать ли значение свойства как местоположение покупателя
	 * для расчёта стоимости доставки (только для свойств типа LOCATION); </li>
	 * <li> <b>PROPS_GROUP_ID</b> - код группы свойств;</li> <li> <b>SIZE1</b> - ширина поля
	 * (размер по горизонтали);</li> <li> <b>SIZE2</b> - высота поля (размер по
	 * вертикали);</li> <li> <b>DESCRIPTION</b> - описание свойства;</li> <li> <b>IS_EMAIL</b> -
	 * флаг (Y/N) использовать ли значение свойства как E-Mail покупателя;</li>
	 * <li> <b>IS_PROFILE_NAME</b> - флаг (Y/N) использовать ли значение свойства как
	 * название профиля покупателя; </li> <li> <b>IS_PAYER</b> - флаг (Y/N)
	 * использовать ли значение свойства как имя плательщика;</li> <li>
	 * <b>IS_LOCATION4TAX</b> - флаг (Y/N) использовать ли значение свойства как
	 * местоположение покупателя для расчёта налогов (только для
	 * свойств типа <b>LOCATION</b>);</li> <li> <b>CODE</b> - мнемонический код
	 * свойства.</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код измененного свойства заказа.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = array(
	 *    "PERSON_TYPE_ID" =&gt; 2,
	 *    "NAME" =&gt; "Комплектация",
	 *    "TYPE" =&gt; "RADIO",
	 *    "REQUIED" =&gt; "Y",
	 *    "DEFAULT_VALUE" =&gt; "F",
	 *    "SORT" =&gt; 100,
	 *    "CODE" =&gt; "COMPLECT",
	 *    "USER_PROPS" =&gt; "N",
	 *    "IS_LOCATION" =&gt; "N",
	 *    "IS_LOCATION4TAX" =&gt; "N",
	 *    "PROPS_GROUP_ID" =&gt; 1,
	 *    "SIZE1" =&gt; 0,
	 *    "SIZE2" =&gt; 0,
	 *    "DESCRIPTION" =&gt; "",
	 *    "IS_EMAIL" =&gt; "N",
	 *    "IS_PROFILE_NAME" =&gt; "N",
	 *    "IS_PAYER" =&gt; "N"
	 * );
	 *  
	 * // Если установлен код свойства, то изменяем свойство с этим кодом,
	 * // иначе добавляем новой свойство
	 * if ($ID&gt;0)
	 * {
	 *    if (!CSaleOrderProps::Update($ID, $arFields))
	 *    {
	 *       echo "Ошибка изменения параметров свойства";
	 *    }
	 *    else
	 *    {
	 *       // Обновим мнемонический код у значений свойства
	 *       // (хранение избыточных данных для оптимизации работы)
	 *       $db_order_props_tmp = 
	 *           CSaleOrderPropsValue::GetList(($b="NAME"), 
	 *                                         ($o="ASC"),
	 *                                         Array("ORDER_PROPS_ID"=&gt;$ID));
	 *       while ($ar_order_props_tmp = $db_order_props_tmp-&gt;Fetch())
	 *       {
	 *          CSaleOrderPropsValue::Update($ar_order_props_tmp["ID"],
	 *                                       array("CODE" =&gt; "COMPLECT"));
	 *       }
	 *    }
	 * }
	 * else
	 * {
	 *    $ID = CSaleOrderProps::Add($arFields);
	 *    if ($ID&lt;=0)
	 *       echo "Ошибка добавления свойства";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__update.6e284623.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		
		$ID = IntVal($ID);

		if (!CSaleOrderProps::CheckFields("UPDATE", $arFields, $ID)) return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_props", $arFields);

		$strSql = "UPDATE b_sale_order_props SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	
	/**
	 * <p>Функция удаляет свойство с кодом ID заказа. Существующие в базе значения этого свойства отвязываются от удаляемого свойства. Удаляются связанные значения из профиля покупателя. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код удаляемого свойства.
	 *
	 *
	 *
	 * @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	 * случае ошибки.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (!CSaleOrderProps::Delete(12))
	 *    echo "Ошибка удаления свойства";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__delete.75442e5e.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_sale_order_props_variant WHERE ORDER_PROPS_ID = ".$ID."", true);
		$DB->Query("UPDATE b_sale_order_props_value SET ORDER_PROPS_ID = NULL WHERE ORDER_PROPS_ID = ".$ID."", true);
		$DB->Query("DELETE FROM b_sale_user_props_value WHERE ORDER_PROPS_ID = ".$ID."", true);
		CSaleOrderUserProps::ClearEmpty();

		return $DB->Query("DELETE FROM b_sale_order_props WHERE ID = ".$ID."", true);
	}

	public static function GetRealValue($propertyID, $propertyCode, $propertyType, $value, $lang = false)
	{
		$propertyID = IntVal($propertyID);
		$propertyCode = Trim($propertyCode);
		$propertyType = Trim($propertyType);

		if ($lang === false)
			$lang = LANGUAGE_ID;

		$arResult = array();

		$curKey = ((strlen($propertyCode) > 0) ? $propertyCode : $propertyID);

		if ($propertyType == "SELECT" || $propertyType == "RADIO")
		{
			$arValue = CSaleOrderPropsVariant::GetByValue($propertyID, $value);
			$arResult[$curKey] = $arValue["NAME"];
		}
		elseif ($propertyType == "MULTISELECT")
		{
			$curValue = "";

			if (!is_array($value))
				$value = explode(",", $value);

			for ($i = 0; $i < count($value); $i++)
			{
				if ($arValue1 = CSaleOrderPropsVariant::GetByValue($propertyID, $value[$i]))
				{
					if ($i > 0)
						$curValue .= ",";
					$curValue .= $arValue1["NAME"];
				}
			}

			$arResult[$curKey] = $curValue;
		}
		elseif ($propertyType == "LOCATION")
		{
			$arValue = CSaleLocation::GetByID($value, $lang);
			$curValue = $arValue["COUNTRY_NAME"].((strlen($arValue["COUNTRY_NAME"])<=0 || strlen($arValue["CITY_NAME"])<=0) ? "" : " - ").$arValue["CITY_NAME"];
			$arResult[$curKey] = $curValue;
			$arResult[$curKey."_COUNTRY"] = $arValue["COUNTRY_NAME"];
			$arResult[$curKey."_CITY"] = $arValue["CITY_NAME"];
		}
		else
		{
			$arResult[$curKey] = $value;
		}

		return $arResult;
	}
}
?>