<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/tax_rate.php");


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
class CSaleTaxRate extends CAllSaleTaxRate
{
	
	/**
	* <p>Метод добавляет новую ставку налога с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой ставки налога. Ключами
	* являются названия параметров ставки, а значениями -
	* соответствующие значения.<br> Допустимые ключи: <ul> <li> <b>TAX_ID</b> - код
	* налога;</li> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>VALUE</b> - величина
	* налога (в процентах);</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>IS_PERCENT</b> -
	* всегда значение "Y";</li> <li> <b>IS_IN_PRICE</b> - налог уже включен в цену
	* товара;</li> <li> <b>APPLY_ORDER</b> - порядок применения;</li> <li> <b>ACTIVE</b> - флаг
	* (Y/N) активности налога;</li> <li> <b>TAX_LOCATION</b> - массив для указания
	* местоположений и групп местоположений, для которых действует эта
	* ставка. Каждый элемент массива представляет собой ассоциативный
	* массив с ключами: <ul> <li> <b>LOCATION_ID</b> - код местоположения или группы
	* местоположений;</li> <li> <b>LOCATION_TYPE</b> - "L" для местоположения и "G" для
	* группы местоположений.</li> </ul> </li> </ul>
	*
	* @return int <p>Возвращается код добавленной ставки налога или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/csaletaxrate__add.40a40d86.php
	* @author Bitrix
	*/
	public static function Add($arFields, $arOptions = array())
	{
		global $DB;
		if (!CSaleTaxRate::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_tax_rate", $arFields);
		$strSql =
			"INSERT INTO b_sale_tax_rate(".$arInsert[0].", TIMESTAMP_X) ".
			"VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		if (is_set($arFields, "TAX_LOCATION"))
		{
			CSaleTaxRate::SetTaxRateLocation($ID, $arFields["TAX_LOCATION"], $arOptions);
		}

		return $ID;
	}

	
	/**
	* <p>Метод возвращает набор ставок налога, удовлетворяющих фильтру arFilter. Набор упорядочен в соответствии с массивом arOrder. Метод динамичный.</p>
	*
	*
	* @param array $arrayarOrder = array("APPLY_ORDER"=>"ASC") Ассоциативный массив для сортировки результирующего набора
	* ставок налогов. Набор сортируется последовательно по каждой паре
	* ключ-значение массива. Ключами массива являются названия
	* параметров ставки налога, по значениям которых осуществляется
	* сортировка. Значениями являются направления сортировки.<br><br>
	* Допустимые ключи: <ul> <li> <b>APPLY_ORDER</b> - порядок применения;</li> <li> <b>ID</b>
	* - код ставки налога;</li> <li> <b>LID</b> - сайт налога;</li> <li> <b>CODE</b> -
	* символьный код налога;</li> <li> <b>TIMESTAMP_X</b> - дата последнего
	* изменения параметров ставки;</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	* ставки;</li> <li> <b>NAME</b> - название налога;</li> <li> <b>PERSON_TYPE_ID</b> - тип
	* плательщика ставки;</li> <li> <b>IS_IN_PRICE</b> - флаг (Y/N) входит ли ставка
	* налога в цену</li> </ul> Допустимые значения: <ul> <li>ASC - по
	* возрастанию;</li> <li>DESC - по убыванию.</li> </ul>
	*
	* @param array $arrayarFilter = array() Ассоциативный массив условий для отбора (фильтрации) ставок
	* налогов. Ключами являются названия фильтруемых параметров
	* ставки налога, а значениями - условия на значения.<br><br> Допустимые
	* ключи: <ul> <li> <b>ID</b> - код ставки налога;</li> <li> <b>LID</b> - сайт налога;</li>
	* <li> <b>CODE</b> - символьный код налога;</li> <li> <b>TAX_ID</b> - код налога;</li> <li>
	* <b>PERSON_TYPE_ID</b> - тип плательщика ставки налога; </li> <li> <b>IS_IN_PRICE</b> -
	* флаг (Y/N) входит ли налог в цену;</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	* ставки налога;</li> <li> <b>APPLY_ORDER</b> - порядок применения;</li> <li> <b>LOCATION</b>
	* - код местоположения, в котором действует ставка.</li> </ul>
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров ставок налогов с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* ставки налога.</td> </tr> <tr> <td>TAX_ID</td> <td>Код налога.</td> </tr> <tr>
	* <td>PERSON_TYPE_ID</td> <td>Тип плательщика.</td> </tr> <tr> <td>VALUE</td> <td>Величина
	* налога (в процентах) </td> </tr> <tr> <td>CURRENCY</td> <td>Валюта.</td> </tr> <tr>
	* <td>IS_PERCENT</td> <td>Y</td> </tr> <tr> <td>IS_IN_PRICE</td> <td>Флаг (Y/N) входит ли уже налог в
	* цену.</td> </tr> <tr> <td>APPLY_ORDER</td> <td>Порядок применения.</td> </tr> <tr>
	* <td>TIMESTAMP_X</td> <td>Дата последнего изменения записи.</td> </tr> <tr> <td>LID</td>
	* <td>Сайт налога.</td> </tr> <tr> <td>NAME</td> <td>Название налога.</td> </tr> <tr>
	* <td>CODE</td> <td>Символьный код налога.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание
	* налога.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N) активности ставки.</td> </tr> </table>
	* <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Заполним массив активных ставок налогов на текущем сайте для типа плательщика 
	* // с кодом $PERSON_TYPE и местоположением плательщика с кодом $TAX_LOCATION
	* 
	* $arTaxList = array();
	* 
	* $arTaxFilter = array(
	*    "LID" =&gt; SITE_ID,
	*    "PERSON_TYPE_ID" =&gt; $PERSON_TYPE,
	*    "ACTIVE" =&gt; "Y",
	*    "LOCATION" =&gt; $TAX_LOCATION
	* );
	* 
	* $db_tax_rate_tmp = CSaleTaxRate::GetList(array("APPLY_ORDER"=&gt;"ASC"), $arTaxFilter);
	* while ($ar_tax_rate_tmp = $db_tax_rate_tmp-&gt;Fetch())
	* {
	*    $arTaxList[] = $ar_tax_rate_tmp;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletaxrate/csaletaxrate__getlist.63346fa6.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("APPLY_ORDER"=>"ASC"), $arFilter = array())
	{
		global $DB;
		$arSqlSearch = Array();
		$arSqlSearchFrom = Array();

		if (!is_array($arFilter)) 
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilteKey = count($filter_keys);
		for ($i=0; $i < $countFilteKey; $i++)
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
				case "ID":
					$arSqlSearch[] = "TR.ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "LID":
					$arSqlSearch[] = "T.LID ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "CODE":
					$arSqlSearch[] = "T.CODE ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "TAX_ID":
					$arSqlSearch[] = "TR.TAX_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "PERSON_TYPE_ID":
					$arSqlSearch[] = " (TR.PERSON_TYPE_ID ".($bInvert?"<>":"=")." ".IntVal($val)." OR TR.PERSON_TYPE_ID = 0 OR TR.PERSON_TYPE_ID IS NULL) ";
					break;
				case "CURRENCY":
					$arSqlSearch[] = "TR.CURRENCY ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "IS_PERCENT":
					$arSqlSearch[] = "TR.IS_PERCENT ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "IS_IN_PRICE":
					$arSqlSearch[] = "TR.IS_IN_PRICE ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "ACTIVE":
					$arSqlSearch[] = "TR.ACTIVE ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "APPLY_ORDER":
					$arSqlSearch[] = "TR.APPLY_ORDER ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "LOCATION":

					if(CSaleLocation::isLocationProMigrated())
					{
						try
						{
							$class = self::CONN_ENTITY_NAME.'Table';
							$arSqlSearch[] = "	TR.ID in (".$class::getConnectedEntitiesQuery(IntVal($val), 'id', array('select' => array('ID'))).") ";
						}
						catch(Exception $e)
						{
						}
					}
					else
					{
						$arSqlSearch[] = 
							"	TR.ID = TR2L.TAX_RATE_ID ".
							"	AND (TR2L.LOCATION_CODE = ".IntVal($val)." AND TR2L.LOCATION_TYPE = 'L' ".
							"		OR L2LG.LOCATION_ID = ".IntVal($val)." AND TR2L.LOCATION_TYPE = 'G') ";
						$arSqlSearchFrom[] = 
							", b_sale_tax2location TR2L ".
							"	LEFT JOIN b_sale_location2location_group L2LG ON (TR2L.LOCATION_TYPE = 'G' AND TR2L.LOCATION_CODE = L2LG.LOCATION_GROUP_ID) ";
					}

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

		$strSqlSearchFrom = "";
		$countSqlSearchForm = count($arSqlSearchFrom);
		for($i=0; $i < $countSqlSearchForm; $i++)
		{
			$strSqlSearchFrom .= " ".$arSqlSearchFrom[$i]." ";
		}

		$strSql = 
			"SELECT DISTINCT TR.ID, TR.TAX_ID, TR.PERSON_TYPE_ID, TR.VALUE, TR.CURRENCY, ".
			"	TR.IS_PERCENT, TR.IS_IN_PRICE, TR.APPLY_ORDER, ".$DB->DateToCharFunction("TR.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	T.LID, T.NAME, T.DESCRIPTION, TR.ACTIVE, T.CODE ".
			"FROM b_sale_tax_rate TR, b_sale_tax T ".
			"	".$strSqlSearchFrom." ".
			"WHERE TR.TAX_ID = T.ID ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);
			if ($order!="ASC")
				$order = "DESC";

			if ($by == "ID") $arSqlOrder[] = " TR.ID ".$order." ";
			elseif ($by == "LID") $arSqlOrder[] = " T.LID ".$order." ";
			elseif ($by == "CODE") $arSqlOrder[] = " T.CODE ".$order." ";
			elseif ($by == "TIMESTAMP_X") $arSqlOrder[] = " TR.TIMESTAMP_X ".$order." ";
			elseif ($by == "ACTIVE") $arSqlOrder[] = " TR.ACTIVE ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " T.NAME ".$order." ";
			elseif ($by == "PERSON_TYPE_ID") $arSqlOrder[] = " TR.PERSON_TYPE_ID ".$order." ";
			elseif ($by == "IS_IN_PRICE") $arSqlOrder[] = " TR.IS_IN_PRICE ".$order." ";
			else
			{
				$arSqlOrder[] = " TR.APPLY_ORDER ".$order." ";
				$by = "APPLY_ORDER";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;
		//echo "<br>".htmlspecialcharsbx($strSql)."<br>";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

}
?>