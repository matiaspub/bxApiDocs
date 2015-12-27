<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Sale\Location;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;
use Bitrix\Main\DB;


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/index.php
 * @author Bitrix
 */
class CAllSaleTaxRate
{
	const CONN_ENTITY_NAME = 'Bitrix\Sale\Tax\RateLocation';

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "TAX_ID") || $ACTION=="ADD") && IntVal($arFields["TAX_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGTR_EMPTY_TAX_ID"), "ERROR_NO_TAX_ID");
			return false;
		}

		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"])<=0)
			$arFields["PERSON_TYPE_ID"] = false;

		if (is_set($arFields, "VALUE") || $ACTION=="ADD")
		{
			$arFields["VALUE"] = str_replace(",", ".", $arFields["VALUE"]);
			$arFields["VALUE"] = DoubleVal($arFields["VALUE"]);
		}

		if ((is_set($arFields, "IS_PERCENT") || $ACTION=="ADD") && ($arFields["IS_PERCENT"]!="N"))
			$arFields["IS_PERCENT"] = "Y";

		if ($arFields["IS_PERCENT"] == "Y")
		{
			$arFields["CURRENCY"] = false;
		}
		elseif ($arFields["IS_PERCENT"] == "N" && (!is_set($arFields, "CURRENCY") || strlen($arFields["CURRENCY"])<=0))
		{
			return False;
		}
		elseif (!is_set($arFields, "IS_PERCENT") && (is_set($arFields, "CURRENCY") && $arFields["CURRENCY"]!==false))
		{
			return False;
		}

		if (is_set($arFields, "TAX_ID"))
		{
			if (!($arTax = CSaleTax::GetByID($arFields["TAX_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["TAX_ID"], GetMessage("SKGTR_NO_TAX")), "ERROR_NO_TAX");
				return false;
			}
		}

		if (is_set($arFields, "PERSON_TYPE_ID") && $arFields["PERSON_TYPE_ID"] !== false)
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], GetMessage("SKGTR_NO_PERS_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		if ((is_set($arFields, "IS_IN_PRICE") || $ACTION=="ADD") && ($arFields["IS_IN_PRICE"]!="Y"))
			$arFields["IS_IN_PRICE"] = "N";
		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && ($arFields["ACTIVE"]!="N"))
			$arFields["ACTIVE"] = "Y";
		if ((is_set($arFields, "APPLY_ORDER") || $ACTION=="ADD") && IntVal($arFields["APPLY_ORDER"])<=0)
			$arFields["APPLY_ORDER"] = "100";

		return true;
	}

	public static function SetTaxRateLocation($ID, $arFields, $arOptions = array())
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			Helper::resetLocationsForEntity($ID, $arFields, self::CONN_ENTITY_NAME, !!$arOptions['EXPECT_LOCATION_CODES']);
		}
		else
		{
			global $DB;
			$ID = intval($ID);
			if (0 >= $ID)
				return;

			$DB->Query("DELETE FROM b_sale_tax2location WHERE TAX_RATE_ID = ".$ID);
			if (is_array($arFields))
			{
				$countField = count($arFields);
				for ($i = 0; $i < $countField; $i++)
				{
					$arFields[$i]["LOCATION_ID"] = intval($arFields[$i]["LOCATION_ID"]);
					if ($arFields[$i]["LOCATION_TYPE"]!="G") $arFields[$i]["LOCATION_TYPE"] = "L";
					if ($arFields[$i]["LOCATION_ID"]>0)
					{
						$strSql =
							"INSERT INTO b_sale_tax2location(TAX_RATE_ID, LOCATION_CODE, LOCATION_TYPE) ".
							"VALUES(".$ID.", ".$arFields[$i]["LOCATION_ID"].", '".$arFields[$i]["LOCATION_TYPE"]."')";
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
				}
			}
		}
	}

	
	/**
	* <p>Метод изменяет параметры ставки налога с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки налога. </ht
	*
	* @param array $arFields  Ассоциативный массив новых параметров ставки налога. Ключами
	* являются названия параметров ставки, а значениями -
	* соответствующие значения.<br><br> Допустимые ключи: <ul> <li> <b>TAX_ID</b> -
	* код налога;</li> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>VALUE</b> -
	* величина налога (в процентах);</li> <li> <b>CURRENCY</b> - валюта;</li> <li>
	* <b>IS_PERCENT</b> - всегда значение "Y";</li> <li> <b>IS_IN_PRICE</b> - налог уже включен
	* в цену товара;</li> <li> <b>APPLY_ORDER</b> - порядок применения;</li> <li> <b>ACTIVE</b> -
	* флаг (Y/N) активности налога;</li> <li> <b>TAX_LOCATION</b> - массив для указания
	* местоположений и групп местоположений, для которых действует эта
	* ставка. Каждый элемент массива представляет собой ассоциативный
	* массив с ключами: <ul> <li> <b>LOCATION_ID</b> - код местоположения или группы
	* местоположений;</li> <li> <b>LOCATION_TYPE</b> - "L" для местоположения и "G" для
	* группы местоположений.</li> </ul> </li> </ul>
	*
	* @return int <p>Возвращается код измененной ставки налога или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/csaletaxrate__update.bd202837.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields, $arOptions = array())
	{
		global $DB;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if (!CSaleTaxRate::CheckFields("UPDATE", $arFields)) return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_tax_rate", $arFields);
		$strSql = "UPDATE b_sale_tax_rate SET TIMESTAMP_X = ".$DB->GetNowFunction().", ".$strUpdate." WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (array_key_exists("TAX_LOCATION", $arFields))
			CSaleTaxRate::SetTaxRateLocation($ID, $arFields["TAX_LOCATION"], $arOptions);

		return $ID;
	}

	
	/**
	* <p>Метод удаляет ставку налога с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки налога. </ht
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/csaletaxrate__delete.c72c6dd5.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		$DB->Query("DELETE FROM b_sale_tax2location WHERE TAX_RATE_ID = ".$ID, true);
		return $DB->Query("DELETE FROM b_sale_tax_rate WHERE ID = ".$ID, true);
	}

	
	/**
	* <p>Метод возвращает параметры ставки налога с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки налога. </ht
	*
	* @return array <p>Возвращается ассоциативный массив параметров ставки налога с
	* ключами</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код ставки налога.</td> </tr> <tr> <td>TAX_ID</td> <td>Код
	* налога.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип плательщика.</td> </tr> <tr> <td>VALUE</td>
	* <td>Величина налога (в процентах) </td> </tr> <tr> <td>CURRENCY</td> <td>Валюта.</td>
	* </tr> <tr> <td>IS_PERCENT</td> <td>Y</td> </tr> <tr> <td>IS_IN_PRICE</td> <td>Флаг (Y/N) входит ли уже
	* налог в цену.</td> </tr> <tr> <td>APPLY_ORDER</td> <td>Порядок применения.</td> </tr> <tr>
	* <td>TIMESTAMP_X</td> <td>Дата последнего изменения записи.</td> </tr> <tr> <td>ACTIVE</td>
	* <td>Флаг (Y/N) активности ставки.</td> </tr> </table> <p>  </p
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/csaletaxrate__getbyid.e4dc7ca3.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		$strSql =
			"SELECT ID, TAX_ID, PERSON_TYPE_ID, VALUE, CURRENCY, IS_PERCENT, IS_IN_PRICE, APPLY_ORDER, ".$DB->DateToCharFunction("TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ACTIVE ".
			"FROM b_sale_tax_rate WHERE ID = ".$ID;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return false;
	}

	/**
	 * Get locations connected with tax rate.
	 * This method is deprecated. It will be removed in future releases.
	 * 
	 * 
	 */
	public static function GetLocationList($arFilter=Array())
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			try
			{
				return CSaleLocation::getDenormalizedLocationList(self::CONN_ENTITY_NAME, $arFilter);
			}
			catch(Exception $e)
			{
				return new DB\ArrayResult(array());
			}
		}
		else
		{
			global $DB;
			$arSqlSearch = Array();

			if (!is_array($arFilter))
				$filter_keys = Array();
			else
				$filter_keys = array_keys($arFilter);

			$countFilterKey = count($filter_keys);
			for ($i=0; $i < $countFilterKey; $i++)
			{
				$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
				if (strlen($val)<=0) continue;

				$key = $filter_keys[$i];
				if ($key[0]=="!")
				{
					$key = substr($key, 1);
					$bInvert = true;
				}
				else
					$bInvert = false;

				switch (ToUpper($key))
				{
					case "TAX_RATE_ID":
						$arSqlSearch[] = "TR2L.TAX_RATE_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
						break;
					case "LOCATION_ID":
						$arSqlSearch[] = "TR2L.LOCATION_CODE ".($bInvert?"<>":"=")." ".IntVal($val)." ";
						break;
					case "LOCATION_TYPE":
						$arSqlSearch[] = "TR2L.LOCATION_TYPE ".($bInvert?"<>":"=")." '".$val."' ";
						break;
				}
			}

			$strSqlSearch = "";
			$countSqlSearch = count($arSqlSearch);
			for($i=0; $i < $countSqlSearch; $i++)
			{
				$strSqlSearch .= " AND ";
				$strSqlSearch .= " (".$arSqlSearch[$i].") ";
			}

			$strSql =
				"SELECT TR2L.TAX_RATE_ID, TR2L.LOCATION_CODE as LOCATION_ID, TR2L.LOCATION_TYPE ".
				"FROM b_sale_tax2location TR2L ".
				"WHERE 1 = 1 ".
				"	".$strSqlSearch." ";

			$strSql .= $strSqlOrder;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return $db_res;

		}
	}
}
?>