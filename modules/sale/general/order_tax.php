<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/index.php
 * @author Bitrix
 */
class CAllSaleOrderTax
{
	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "ORDER_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_ORDER_ID"), "ERROR_NO_ORDER_ID");
			return false;
		}
		if ((is_set($arFields, "TAX_NAME") || $ACTION=="ADD") && strlen($arFields["TAX_NAME"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_TAX_NAME"), "ERROR_NO_TAX_NAME");
			return false;
		}
		if ((is_set($arFields, "IS_PERCENT") || $ACTION=="ADD") && $arFields["IS_PERCENT"]!="Y" && $arFields["IS_PERCENT"]!="N")
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_TAX_VALUE"), "ERROR_NO_IS_PERCENT");
			return false;
		}
		if ((is_set($arFields, "IS_IN_PRICE") || $ACTION=="ADD") && $arFields["IS_IN_PRICE"]!="Y" && $arFields["IS_IN_PRICE"]!="N")
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_IN_PRICE"), "ERROR_NO_IS_IN_PRICE");
			return false;
		}

		if (is_set($arFields, "VALUE") || $ACTION=="ADD")
		{
			$arFields["VALUE"] = str_replace(",", ".", $arFields["VALUE"]);
			$arFields["VALUE"] = DoubleVal($arFields["VALUE"]);
			if ($arFields["VALUE"] <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_SUM"), "ERROR_NO_VALUE");
				return false;
			}
		}
		if (is_set($arFields, "VALUE_MONEY") || $ACTION=="ADD")
		{
			$arFields["VALUE_MONEY"] = str_replace(",", ".", $arFields["VALUE_MONEY"]);
			$arFields["VALUE_MONEY"] = DoubleVal($arFields["VALUE_MONEY"]);
		}
		if ((is_set($arFields, "VALUE_MONEY") || $ACTION=="ADD") && $arFields["VALUE_MONEY"]<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOT_EMPTY_SUM_MONEY"), "ERROR_NO_VALUE_MONEY");
			return false;
		}

		if (is_set($arFields, "ORDER_ID"))
		{
			if (!($arOrder = CSaleOrder::GetByID($arFields["ORDER_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_ID"], GetMessage("SKGOT_NO_ORDER")), "ERROR_NO_ORDER");
				return false;
			}
		}

		if ((is_set($arFields, "CODE") || $ACTION=="ADD") && strlen($arFields["CODE"])<=0)
			$arFields["CODE"] = false;

		return true;
	}

	
	/**
	* <p>Метод изменяет сумму налога с кодом ID на основании массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код суммы налога.
	*
	* @param array $arFields  Ассоциативный массив новых параметров записи, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения.<br> Допустимые ключи:<ul> <li> <b>ORDER_ID</b> - код заказа;</li> <li>
	* <b>TAX_NAME</b> - название налога;</li> <li> <b>VALUE</b> - величина налога;</li> <li>
	* <b>VALUE_MONEY</b> - общая сумма этого налога;</li> <li> <b>APPLY_ORDER</b> - порядок
	* применения;</li> <li> <b>CODE</b> - символьный код налога;</li> <li> <b>IS_PERCENT</b> -
	* должно быть значение "Y";</li> <li> <b>IS_IN_PRICE</b> - флаг (Y/N) входит ли налог
	* уже в цену товара.</li> </ul>
	*
	* @return int <p>Возвращается код измененной суммы налога или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__update.7e4a73a5.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if (!CSaleOrderTax::CheckFields("UPDATE", $arFields)) return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_tax", $arFields);
		$strSql = "UPDATE b_sale_order_tax SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	
	/**
	* <p>Метод удаляет сумму налога с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код суммы налога.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__delete.ae826565.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_order_tax WHERE ID = ".$ID."", true);
	}

	
	/**
	* <p>Метод удаляет все суммы налогов для заказа с кодом ORDER_ID. Метод динамичный.</p>
	*
	*
	* @param int $ORDER_ID  Код заказа.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__deleteex.b5025ef6.php
	* @author Bitrix
	*/
	public static function DeleteEx($ORDER_ID)
	{
		global $DB;
		$ORDER_ID = IntVal($ORDER_ID);
		return $DB->Query("DELETE FROM b_sale_order_tax WHERE ORDER_ID = ".$ORDER_ID."", true);
	}

	
	/**
	* <p>Метод возвращает параметры суммы налогов с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код суммы налогов. </ht
	*
	* @return array <p>Возвращается ассоциативный массив с ключами</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код суммы
	* налогов.</td> </tr> <tr> <td>ORDER_ID</td> <td>Код заказа.</td> </tr> <tr> <td>TAX_NAME</td>
	* <td>Название налога.</td> </tr> <tr> <td>VALUE</td> <td>Ставка налога.</td> </tr> <tr>
	* <td>VALUE_MONEY</td> <td>Сумма налога.</td> </tr> <tr> <td>APPLY_ORDER</td> <td>Порядок
	* применения.</td> </tr> <tr> <td>CODE</td> <td>Символьный код налога.</td> </tr> <tr>
	* <td>IS_IN_PRICE</td> <td>Флаг (Y/N) включен ли налог в цену товара.</td> </tr> <tr>
	* <td>IS_PERCENT</td> <td>Y</td> </tr> </table> <p>  </p
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__getbyid.122460db.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT ID, ORDER_ID, TAX_NAME, VALUE, VALUE_MONEY, APPLY_ORDER, CODE, IS_PERCENT, IS_IN_PRICE ".
			"FROM b_sale_order_tax ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	// The function does not handle fixed-rate taxes. Only with interest!
	// any tax returns for the price
	// the second argument ($ arTaxList [] ["TAX_VAL"]) returns the value of the tax for that price
	
	/**
	* <p>Метод вычисляет общую сумму налогов на товар стоимостью Price. Налоги задаются в массиве arTaxList. Метод динамичный.</p>
	*
	*
	* @param float $Price  Стоимость товара. </h
	*
	* @param array &$arTaxList  Массив налогов, представляет собой массив ассоциативных
	* массивов вида: <pre class="syntax"> array("APPLY_ORDER"=&gt;порядок_применения,
	* "VALUE"=&gt;величина_налога_в_процентах,
	* "IS_IN_PRICE"=&gt;"налог_входит_в_цену_Y/N")</pre>
	*
	* @param string $DefCurrency  Базовая валюта для заказа.
	*
	* @return float <p>Возвращается общая сумма налогов на товар. Кроме того в массиве
	* arTaxList создаётся дополнительный ключ TAX_VAL, который содержит
	* величину данного налога на товар. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Заполним массив активных налогов для текущего сайта, типа плательщика с кодом 2 и местоположения для начисления налогов с кодом 48
	* $arTaxList = array();
	* 
	* $arTaxFilter = array(
	*    "LID" =&gt; SITE_ID,
	*    "PERSON_TYPE_ID" =&gt; 2,
	*    "ACTIVE" =&gt; "Y",
	*    "LOCATION" =&gt; 48
	* );
	* $db_tax_rate_tmp = CSaleTaxRate::GetList(array("APPLY_ORDER"=&gt;"ASC"), $arTaxFilter);
	* while ($ar_tax_rate_tmp = $db_tax_rate_tmp-&gt;Fetch())
	* {
	*    $arTaxList[] = $ar_tax_rate_tmp;
	* }
	* 
	* // Вычислим величину налогов на товар стоимостью 38.95
	* $TAX_PRICE_tmp = CSaleOrderTax::CountTaxes(38.95, $arTaxList, "RUR");
	* 
	* echo "Общая величина налогов: ".$TAX_PRICE_tmp."&lt;br&gt;";
	* echo "в том числе ";
	* for ($i = 0; $i&lt;count($arTaxList); $i++)
	* {
	*    echo $arTaxList[$di]["NAME"]." - ".$arTaxList[$di]["TAX_VAL"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__counttaxes.29643259.php
	* @author Bitrix
	*/
	public static function CountTaxes($Price, &$arTaxList, $DefCurrency)
	{
		//1. Untwist stack tax included in the price for the determination of the initial price
		$part_sum = 0.00;
		$tax_koeff = 1.00;
		$minus = 0.00;
		for ($i = 0; $i < count($arTaxList); $i++)
		{
			if ($i == 0)
				$prevOrder = IntVal($arTaxList[$i]["APPLY_ORDER"]);

			if ($prevOrder != IntVal($arTaxList[$i]["APPLY_ORDER"]))
			{
				$tax_koeff += $part_sum;
				$part_sum = 0.00;
				$prevOrder = IntVal($arTaxList[$i]["APPLY_ORDER"]);
			}

			$val = $tax_koeff * DoubleVal($arTaxList[$i]["VALUE"]) / 100.00;
			$part_sum += $val;

			if ($arTaxList[$i]["IS_IN_PRICE"] != "Y")
				$minus += $val;
		}
		$tax_koeff += $part_sum;
		$item_price = $Price/($tax_koeff-$minus);

		//2. collect taxes
		$part_sum = 0.00;
		$tax_koeff = 1.00;
		$plus = 0.00;
		$total_tax = 0.00;
		for ($i = 0; $i < count($arTaxList); $i++)
		{
			if ($i==0)
				$prevOrder = IntVal($arTaxList[$i]["APPLY_ORDER"]);

			if ($prevOrder <> IntVal($arTaxList[$i]["APPLY_ORDER"]))
			{
				$tax_koeff += $part_sum;
				$part_sum = 0.00;
				$prevOrder = IntVal($arTaxList[$i]["APPLY_ORDER"]);
			}

			$val = $tax_koeff * DoubleVal($arTaxList[$i]["VALUE"]) / 100.00;
			$tax_val = $val*$item_price;
			$part_sum += $val;
			$total_tax += $tax_val;

			$arTaxList[$i]["TAX_VAL"] = $tax_val;
		}
		return $total_tax;
	}
}
?>