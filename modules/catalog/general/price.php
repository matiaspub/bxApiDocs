<?
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/index.php
 * @author Bitrix
 */
class CAllPrice
{
	
	/**
	* <p>Метод проверяет (и модифицирует) массив данных цены перед его записью в таблицу или обновлением. Вызывается в методах <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения
	* (регистр важен): <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a>;</li> <li> <b>UPDATE</b> - для
	* метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a>.</li> </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров ценового предложения.
	* Передается по ссылке, после вызова метода содержимое массива
	* может измениться. Допустимые ключи: <ul> <li> <b>PRODUCT_ID </b> - код
	* товара;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>CURRENCY</b> - валюта
	* цены.</li> </ul>
	*
	* @param int $ID = 0 Идентификатор ценового предложения. Параметр является
	* необязательным и имеет смысл только для $ACTION = 'UPDATE'.
	*
	* @return bool <p>В случае корректности переданных параметров возвращает <i>true</i>,
	* иначе - <i>false</i>. Если метод вернул <i>false</i>, то запись не будет
	* добавлена/сохранена и с помощью <b>$APPLICATION-&gt;GetException()</b> можно
	* получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a></li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		if ((is_set($arFields, "PRODUCT_ID") || $ACTION=="ADD") && intval($arFields["PRODUCT_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("KGP_EMPTY_PRODUCT"), "EMPTY_PRODUCT_ID");
			return false;
		}
		if ((is_set($arFields, "CATALOG_GROUP_ID") || $ACTION=="ADD") && intval($arFields["CATALOG_GROUP_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("KGP_EMPTY_CATALOG_GROUP"), "EMPTY_CATALOG_GROUP_ID");
			return false;
		}
		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("KGP_EMPTY_CURRENCY"), "EMPTY_CURRENCY");
			return false;
		}
		if (isset($arFields['CURRENCY']))
		{
			if (!($arCurrency = CCurrency::GetByID($arFields["CURRENCY"])))
			{
				$APPLICATION->ThrowException(Loc::getMessage("KGP_NO_CURRENCY", array('#ID#' => $arFields["CURRENCY"])), "CURRENCY");
				return false;
			}
		}

		if (is_set($arFields, "PRICE") || $ACTION=="ADD")
		{
			$arFields["PRICE"] = str_replace(",", ".", $arFields["PRICE"]);
			$arFields["PRICE"] = DoubleVal($arFields["PRICE"]);
		}

		if ((is_set($arFields, "QUANTITY_FROM") || $ACTION=="ADD") && intval($arFields["QUANTITY_FROM"]) <= 0)
			$arFields["QUANTITY_FROM"] = false;
		if ((is_set($arFields, "QUANTITY_TO") || $ACTION=="ADD") && intval($arFields["QUANTITY_TO"]) <= 0)
			$arFields["QUANTITY_TO"] = false;

		return true;
	}

	
	/**
	* <p>Метод изменяет параметры ценового предложения (цены) для товара с кодом ID на значения из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ценового предложения.
	*
	* @param array $arFields  Ассоциативный массив новых параметров ценового предложения,
	* ключами в котором являются названия полей предложения, а
	* значениями - новые значения. <br> Допустимые ключи: <ul> <li> <b>PRODUCT_ID</b> -
	* код товара или торгового предложения (ID элемента инфоблока);</li> <li>
	* <b>EXTRA_ID</b> - код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li>
	* <b>PRICE </b>- цена;</li> <li> <b>CURRENCY</b> - валюта цены</li> <li> <b>QUANTITY_FROM</b> -
	* количество товара, начиная с приобретения которого действует эта
	* цена.</li> <li> <b>QUANTITY_TO</b> - количество товара, при приобретении
	* которого заканчивает действие эта цена. <p></p> <div class="note">
	* <b>Примечание:</b> если необходимо, чтобы значения параметров
	* <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были заданы, необходимо указать у них в
	* качестве значения <i>false</i> либо не задавать поля <b>QUANTITY_FROM</b> и
	* <b>QUANTITY_TO</b> в <b>Update</b> вообще.</div> </li> </ul> Если установлен код наценки,
	* то появляется возможность автоматически пересчитывать эту цену
	* при изменении базовой цены или процента наценки.
	*
	* @param boolean $boolRecalc = false Пересчитать цены. Если передать <i>true</i>, то включается механизм
	* пересчета цен. <br> Если обновляется базовая цена (в <b>CATALOG_GROUP_ID</b>
	* задан тип цен, являющийся базовым), будут пересчитаны все
	* остальные типы цен для товара, если у них задан код наценки. <br>
	* Если обновляется иная цена (не базовая), для нее задан код наценки
	* и уже существует базовая - значения <b>PRICE</b> и <b>CURRENCY</b> буду
	* пересчитаны. <br> Необязательный параметр. По умолчанию - <i>false</i>.
	*
	* @return bool <p>Возвращает ID обновляемой цены в случае успешного сохранения
	* цены и <i>false</i> - в противном случае. Для получения детальной
	* информации об ошибке следует вызвать <b>$APPLICATION-&gt;GetException()</b>.</p>
	* <h4>События</h4></bod<p>Метод работает с событиями <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceupdate.php">OnBeforePriceUpdate</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceupdate.php">OnPriceUpdate</a>.</p> <p></p><div
	* class="note"> <b>Примечания:</b> <ul> <li>Если параметр $boolRecalc = true, все равно
	* необходимо указывать цену и валюту (в том случае, когда тип цены -
	* не базовый). Если существует базовая цена, значения цены и валюты
	* будут изменены, если нет - код наценки будет изменен на ноль.</li>
	* <li>В обработчиках события OnBeforePriceUpdate можно запретить или,
	* наоборот, включить пересчет цены. За это отвечает ключ RECALC
	* массива данных, передаваемых в обработчик.</li> </ul> </div>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Установим для товара с кодом 15 цену типа 2 в значение 29.95 USD
	* $PRODUCT_ID = 15;
	* $PRICE_TYPE_ID = 2;
	* 
	* $arFields = Array(
	*     "PRODUCT_ID" =&gt; $PRODUCT_ID,
	*     "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID,
	*     "PRICE" =&gt; 29.95,
	*     "CURRENCY" =&gt; "USD",
	*     "QUANTITY_FROM" =&gt; 1,
	*     "QUANTITY_TO" =&gt; 10
	* );
	* 
	* $res = CPrice::GetList(
	*         array(),
	*         array(
	*                 "PRODUCT_ID" =&gt; $PRODUCT_ID,
	*                 "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID
	*             )
	*     );
	* 
	* if ($arr = $res-&gt;Fetch())
	* {
	*     CPrice::Update($arr["ID"], $arFields);
	* }
	* else
	* {
	*     CPrice::Add($arFields);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/checkfields.php">CPrice::CheckFields</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceupdate.php">Событие
	* OnBeforePriceUpdate</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceupdate.php">Событие OnPriceUpdate</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields,$boolRecalc = false)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$boolBase = false;
		$arFields['RECALC'] = ($boolRecalc === true);

		foreach (GetModuleEvents("catalog", "OnBeforePriceUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;
		}

		if (!CPrice::CheckFields("UPDATE", $arFields, $ID))
			return false;

		if (isset($arFields['RECALC']) && $arFields['RECALC'] === true)
		{
			CPrice::ReCountFromBase($arFields, $boolBase);
			if (!$boolBase && $arFields['EXTRA_ID'] <= 0)
				return false;
		}

		$strUpdate = $DB->PrepareUpdate("b_catalog_price", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_price SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if ($boolBase)
			CPrice::ReCountForBase($arFields);

		foreach (GetModuleEvents("catalog", "OnPriceUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет ценовое предложение с кодом ID. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание</b>: метод работает с двумя событиями: OnBeforePriceDelete и OnPriceDelete. Событие OnBeforePriceDelete позволяет отменить удаление ценового предложения. Событие OnPriceDelete дает возможность провести какие-то операции одновременно с удалением цены.</div>
	*
	*
	* @param int $ID  Код ценового предложения.
	*
	* @return bool <p>Возвращает значение <i>true</i> в случае успешного удаления и <i>false</i>
	* - в противном случае.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Удалим цену с кодом 11<br>CPrice::Delete(11);<br>?&gt;
	* </ht
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <p><b>Методы</b></p></bo<ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li>
	* </ul><p><b>События</b></p></bod<ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepricedelete.php">OnProductPriceDelete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onpricedelete.php">OnPriceDelete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforePriceDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;
		}

		$mxRes = $DB->Query("DELETE FROM b_catalog_price WHERE ID = ".$ID, true);

		foreach (GetModuleEvents("catalog", "OnPriceDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		return $mxRes;
	}

	
	/**
	* <p>Метод возвращает базовую цену товара с кодом PRODUCT_ID. Базовая цена - это цена базового типа цен. Метод динамичный.</p>
	*
	*
	* @param int $productID  Код товара или торгового предложения (ID элемента инфоблока). <br><br>
	* До версии <b>4.0.4</b> параметр назывался <b>PRODUCT_ID</b>.
	*
	* @param  $int  Количество товара, начиная с приобретения которого действует эта
	* цена.
	*
	* @param quantityFro $m = false[ Количество товара, при приобретении которого заканчивает
	* действие эта цена.
	*
	* @param int $quantityTo = false]] 
	*
	* @return array <p>Возвращает ассоциативный массив с ключами: </p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Код</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* ценового предложения.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>Код товара или
	* торгового предложения (ID элемента инфоблока).</td> </tr> <tr> <td>EXTRA_ID</td>
	* <td>Код наценки.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа цены.</td> </tr> <tr>
	* <td>PRICE</td> <td>Базовая цена.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта базовой
	* цены.</td> </tr> <tr> <td>QUANTITY_FROM</td> <td>Количество товара, начиная с
	* приобретения которого действует эта цена.</td> </tr> <tr> <td>QUANTITY_TO</td>
	* <td>Количество товара, при приобретении которого заканчивает
	* действие эта цена.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ar_res = CPrice::GetBasePrice(11, 1, 10);
	* echo "Базовая цена товара с кодом 11 (при приобретении от ".
	*       $ar_res["QUANTITY_FROM"]." до ".
	*       $ar_res["QUANTITY_TO"]." единиц товара) равна ".
	*       $ar_res["PRICE"]." ".$ar_res["CURRENCY"]."&lt;br&gt;";
	* echo "Отформатированая базовая цена: ".
	*       CurrencyFormat($ar_res["PRICE"], $ar_res["CURRENCY"]);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getbaseprice.9dc276c9.php
	* @author Bitrix
	*/
	public static function GetBasePrice($productID, $quantityFrom = false, $quantityTo = false, $boolExt = true)
	{
		$productID = (int)$productID;
		if ($productID <= 0)
			return false;

		$arBaseType = CCatalogGroup::GetBaseGroup();
		if (empty($arBaseType))
			return false;

		$arFilter = array(
			'PRODUCT_ID' => $productID,
			'CATALOG_GROUP_ID' => $arBaseType['ID']
		);

		if ($quantityFrom !== false)
			$arFilter['QUANTITY_FROM'] = (int)$quantityFrom;
		if ($quantityTo !== false)
			$arFilter['QUANTITY_TO'] = (int)$quantityTo;

		if ($boolExt === false)
		{
			$arSelect = array('ID', 'PRODUCT_ID', 'EXTRA_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'TIMESTAMP_X',
				'QUANTITY_FROM', 'QUANTITY_TO', 'TMP_ID'
			);
		}
		else
		{
			$arSelect = array('ID', 'PRODUCT_ID', 'EXTRA_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'TIMESTAMP_X',
				'QUANTITY_FROM', 'QUANTITY_TO', 'TMP_ID',
				'PRODUCT_QUANTITY', 'PRODUCT_QUANTITY_TRACE', 'PRODUCT_CAN_BUY_ZERO',
				'PRODUCT_NEGATIVE_AMOUNT_TRACE', 'PRODUCT_WEIGHT', 'ELEMENT_IBLOCK_ID'
			);
		}

		$db_res = CPrice::GetListEx(
			array('QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC'),
			$arFilter,
			false,
			array('nTopCount' => 1),
			$arSelect
		);
		if ($res = $db_res->Fetch())
		{
			$res['BASE'] = 'Y';
			$res['CATALOG_GROUP_NAME'] = $arBaseType['NAME'];
			return $res;
		}

		return false;
	}

	
	/**
	* <p>Метод устанавливает базовую цену товара с кодом <i>ProductID</i>. Базовая цена - это цена базового типа цен. Метод динамичный.</p> <p></p> <div class="note"><b>Важно! Рекомендуется использовать метод <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a> взамен данного.</b></div>
	*
	*
	* @param int $ProductID  Код товара или торгового предложения (ID элемента инфоблока). <br><br>
	* До версии 4.0.4 параметр назывался PRODUCT_ID.
	*
	* @param float $Price  Новая базовая цена. <br><br> До версии 4.0.4 параметр назывался PRICE.
	*
	* @param string $Currency  Валюта новой базовой цены. <br><br> До версии 4.0.4 параметр назывался
	* CURRENCY.
	*
	* @param  $int  Количество товара, начиная с приобретения которого действует эта
	* цена. <br><br> Значение по умолчанию, начиная с версии <b>14.5.0</b>, равно
	* <b>false</b>. <br><br> До версии <b>14.5.0</b> значение по умолчанию было равно
	* <b>0</b>, но его использование приводило к ошибке. Поэтому при
	* использовании функции на установках, где версия модуля ниже
	* <b>14.5.0</b>, необходимо явно указывать значение <b>false</b>. <br><br> Цифровое
	* значение (любое) можно ставить только в том случае, если базовая
	* цена создается в расширенном режиме цен (зависимости цены от
	* количества).
	*
	* @param boolean $quantityFrom = false[ Количество товара, при приобретении которого заканчивает
	* действие эта цена. <br><br> Значение по умолчанию, начиная с версии
	* <b>14.5.0</b>, равно <b>false</b>. <br><br> До версии <b>14.5.0</b> значение по
	* умолчанию было равно <b>0</b>, но его использование приводило к
	* ошибке. Поэтому при использовании функции на установках, где
	* версия модуля ниже <b>14.5.0</b>, необходимо явно указывать значение
	* <b>false</b>.<br><br> Цифровое значение (любое) можно ставить только в том
	* случае, если базовая цена создается в расширенном режиме цен
	* (зависимости цены от количества).
	*
	* @param in $t  Если задано значение <i>false</i>, то будет возвращено <i>true</i> после
	* установки цены. Иначе задается код записи в таблице.
	*
	* @param boolean $quantityTo = false[ 
	*
	* @param boolean $bGetID = false]]] 
	*
	* @return bool <p>Возвращает значение <i>true</i> в случае успешного сохранения цены и
	* <i>false</i> - в противном случае. </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__setbaseprice.a8de1fcf.php
	* @author Bitrix
	*/
	public static function SetBasePrice($ProductID, $Price, $Currency, $quantityFrom = false, $quantityTo = false, $bGetID = false)
	{
		$bGetID = ($bGetID == true);

		$arFields = array();
		$arFields["PRICE"] = (float)$Price;
		$arFields["CURRENCY"] = $Currency;
		$arFields["QUANTITY_FROM"] = ($quantityFrom == false ? false : (int)$quantityFrom);
		$arFields["QUANTITY_TO"] = ($quantityTo == false ? false : (int)$quantityTo);
		$arFields["EXTRA_ID"] = false;

		if ($arBasePrice = CPrice::GetBasePrice($ProductID, $quantityFrom, $quantityTo, false))
		{
			$ID = CPrice::Update($arBasePrice["ID"], $arFields);
		}
		else
		{
			$arBaseGroup = CCatalogGroup::GetBaseGroup();
			$arFields["CATALOG_GROUP_ID"] = $arBaseGroup["ID"];
			$arFields["PRODUCT_ID"] = $ProductID;

			$ID = CPrice::Add($arFields);
		}
		if (!$ID)
			return false;

		return ($bGetID ? $ID : true);
	}

	
	/**
	* <p>Метод выполняет пересчет всех цен товара или пересчет всех цен, имеющих определенный <i>ID</i> наценки. Метод динамичный.</p>
	*
	*
	* @param string $TYPE  Параметр определяет, что передается в параметре <b>ID</b>:
	* идентификатор товара или идентификатор наценки. <br><br> Если
	* принимает значение <b>EXTRA</b> (регистр важен), то будет выполняться
	* пересчет всех цен с определенной наценкой. При любом другом
	* значении будет выполняться пересчет всех цен конкретного товара.
	*
	* @param int $ID  Идентификатор товара или идентификатор наценки.
	*
	* @param int $VAL  Новое значение процента наценки, если параметр <b>TYPE</b> принимает
	* значение <b>EXTRA</b>. В противном случае - новая базовая цена, от
	* которой будут рассчитываться наценки.
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/recalculate.php
	* @author Bitrix
	*/
	public static function ReCalculate($TYPE, $ID, $VAL)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return;

		$iblockList = array();

		if ($TYPE == 'EXTRA')
		{
			$baseType = CCatalogGroup::GetBaseGroup();
			if (empty($baseType))
				return;

			$db_res = CPrice::GetListEx(
				array(),
				array('EXTRA_ID' => $ID),
				false,
				false,
				array('ID', 'PRODUCT_ID', 'EXTRA_ID', 'QUANTITY_FROM', 'QUANTITY_TO')
			);
			while ($res = $db_res->Fetch())
			{
				$parentFilter = array(
					'PRODUCT_ID' => $res['PRODUCT_ID'],
					'CATALOG_GROUP_ID' => $baseType['ID'],
					'QUANTITY_FROM' => ($res['QUANTITY_FROM'] === null ? false : $res['QUANTITY_FROM']),
					'QUANTITY_TO' => ($res['QUANTITY_TO'] === null ? false : $res['QUANTITY_TO'])
				);
				$parentIterator = CPrice::GetListEx(
					array(),
					$parentFilter,
					false,
					false,
					array('ID', 'PRODUCT_ID', 'PRICE', 'CURRENCY', 'ELEMENT_IBLOCK_ID')
				);
				$basePrice = $parentIterator->Fetch();
				if (!empty($basePrice))
				{
					$basePrice['ELEMENT_IBLOCK_ID'] = (int)$basePrice['ELEMENT_IBLOCK_ID'];
					$fields = array(
						'PRICE' => roundex($basePrice['PRICE'] * (1 + 1 * $VAL / 100), 2),
						'CURRENCY' => $basePrice['CURRENCY']
					);
					CPrice::Update($res['ID'], $fields);
					unset($arFields);
					$iblockList[$basePrice['ELEMENT_IBLOCK_ID']] = $basePrice['ELEMENT_IBLOCK_ID'];
				}
				unset($basePrice, $parentIterator);
			}
			unset($res, $db_res, $baseType);
		}
		else
		{
			$db_res = CPrice::GetListEx(
				array(),
				array("PRODUCT_ID" => $ID),
				false,
				false,
				array('ID', 'PRODUCT_ID', 'EXTRA_ID', 'ELEMENT_IBLOCK_ID')
			);
			while ($res = $db_res->Fetch())
			{
				$res['ELEMENT_IBLOCK_ID'] = (int)$res['ELEMENT_IBLOCK_ID'];
				$res["EXTRA_ID"] = (int)$res["EXTRA_ID"];
				if ($res["EXTRA_ID"] > 0)
				{
					$res1 = CExtra::GetByID($res["EXTRA_ID"]);
					$arFields = array(
						"PRICE" => $VAL * (1 + 1 * $res1["PERCENTAGE"] / 100),
					);
					CPrice::Update($res["ID"], $arFields);
					$iblockList[$res['ELEMENT_IBLOCK_ID']] = $res['ELEMENT_IBLOCK_ID'];
				}
			}
			unset($res, $db_res);
		}

		if (!empty($iblockList) && Main\Loader::includeModule('iblock'))
		{
			foreach ($iblockList as &$iblock)
				CIblock::clearIblockTagCache($iblock);
			unset($iblock);
		}
		unset($iblockList);
	}

	
	/**
	* <p>Метод удаляет все цены в валюте <i>Currency</i>. Является обработчиком события <a href="http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencydelete.php">OnCurrencyDelete</a> модуля <b>Валюты</b>. Метод динамичный.</p>
	*
	*
	* @param string $Currency  Идентификатор удаляемой валюты.
	*
	* @return mixed <p>В случае успешного удаления возвращает результат метода <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a>, а в случае
	* ошибки - <i>false</i>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencydelete.php">Событие
	* OnCurrencyDelete</a></li> </ul></bod<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/oncurrencydelete.php
	* @author Bitrix
	*/
	public static function OnCurrencyDelete($Currency)
	{
		global $DB;
		if ($Currency == '')
			return false;

		$strSql = "DELETE FROM b_catalog_price WHERE CURRENCY = '".$DB->ForSql($Currency)."'";
		return $DB->Query($strSql, true);
	}

	
	/**
	* <p>Метод удаляет все цены для элемента с идентификатором <i>ProductID</i>. Метод динамичный. Является обработчиком события <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementdelete.php">OnIBlockElementDelete</a> модуля <b>Информационные блоки</b>.</p>
	*
	*
	* @param int $ProductID  Идентификатор удаляемого элемента.
	*
	* @return mixed <p>В случае успешного удаления возвращает результат метода <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a>, а в случае
	* ошибки - <i>false</i>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementdelete.php">Событие
	* OnIBlockElementDelete</a></li> </ul></bod<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/oniblockelementdelete.php
	* @author Bitrix
	*/
	public static function OnIBlockElementDelete($ProductID)
	{
		global $DB;
		$ProductID = (int)$ProductID;
		if ($ProductID <= 0)
			return false;
		return $DB->Query("DELETE FROM b_catalog_price WHERE PRODUCT_ID = ".$ProductID, true);
	}

	
	/**
	* <p>Метод удаляет цены для товара. В качестве аргументов методу передаются код (ID) товара и, опционально, массив кодов (ID) цен, которые необходимо оставить. Если второй аргумент - пустой, удаляются все цены. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание</b>: метод работает с двумя событиями: <a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a>. Событие <a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a> позволяет отменить удаление либо изменить перечень цен, которые будут оставлены. Событие <a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a> дает возможность провести какие-то операции одновременно с удалением цен.</div>
	*
	*
	* @param int $ProductID  Код товара или торгового предложения (ID элемента инфоблока), у
	* которого необходимо удалить цены
	*
	* @param array $arExceptionIDs = array() Массив кодов (ID) цен, которые будут оставлены. Если массив пуст,
	* будут удалены все цены товара.
	*
	* @return boolean <ul> <li> <i>true</i> в случае успеха </li> <li> <i>false</i>, если произошла ошибка
	* или удаление было отменено. </li> </ul>
	*
	* <h4>Example</h4> 
	* <pre>
	* <b>Удаление всех цен товара</b>
	* $boolResult = CPrice::DeleteByProduct(241);<br>
	* <b>Удаление всех цен товара, кроме двух</b>
	* 
	* $boolResult = CPrice::DeleteByProduct(241,array(426,456));<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <p><b>Методы</b></p></bo<ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a> </li>
	* </ul><p><b>События</b></p></bod<ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php
	* @author Bitrix
	*/
	public static function DeleteByProduct($ProductID, $arExceptionIDs = array())
	{
		global $DB;

		$ProductID = (int)$ProductID;
		if ($ProductID <= 0)
			return false;
		foreach (GetModuleEvents("catalog", "OnBeforeProductPriceDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ProductID, &$arExceptionIDs))===false)
				return false;
		}

		if (!empty($arExceptionIDs))
			CatalogClearArray($arExceptionIDs, false);

		if (!empty($arExceptionIDs))
		{
			$strSql = "DELETE FROM b_catalog_price WHERE PRODUCT_ID = ".$ProductID." AND ID NOT IN (".implode(',',$arExceptionIDs).")";
		}
		else
		{
			$strSql = "DELETE FROM b_catalog_price WHERE PRODUCT_ID = ".$ProductID;
		}

		$mxRes = $DB->Query($strSql, true);

		foreach (GetModuleEvents("catalog", "OnProductPriceDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ProductID,$arExceptionIDs));
		}

		return $mxRes;
	}

	function ReCountForBase(&$arFields)
	{
		static $arExtraList = array();
		$boolSearch = false;

		$arFilter = array('PRODUCT_ID' => $arFields['PRODUCT_ID'],'!CATALOG_GROUP_ID' => $arFields['CATALOG_GROUP_ID']);
		if (isset($arFields['QUANTITY_FROM']))
			$arFilter['QUANTITY_FROM'] = $arFields['QUANTITY_FROM'];
		if (isset($arFields['QUANTITY_TO']))
			$arFilter['QUANTITY_TO'] = $arFields['QUANTITY_TO'];

		$rsPrices = CPrice::GetListEx(
			array('CATALOG_GROUP_ID' => 'ASC',"QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
			$arFilter,
			false,
			false,
			array('ID','EXTRA_ID')
		);
		while ($arPrice = $rsPrices->Fetch())
		{
			$arPrice['EXTRA_ID'] = (int)$arPrice['EXTRA_ID'];
			if ($arPrice['EXTRA_ID'] > 0)
			{
				$boolSearch = isset($arExtraList[$arPrice['EXTRA_ID']]);
				if (!$boolSearch)
				{
					$arExtra = CExtra::GetByID($arPrice['EXTRA_ID']);
					if (!empty($arExtra))
					{
						$boolSearch = true;
						$arExtraList[$arExtra['ID']] = $arExtra['PERCENTAGE'];
					}
				}
				if ($boolSearch)
				{
					$arNewPrice = array(
						'CURRENCY' => $arFields['CURRENCY'],
						'PRICE' => RoundEx($arFields["PRICE"] * (1 + DoubleVal($arExtraList[$arPrice['EXTRA_ID']])/100), CATALOG_VALUE_PRECISION),
					);
					CPrice::Update($arPrice['ID'],$arNewPrice,false);
				}
			}
		}
	}

	public static function ReCountFromBase(&$arFields, &$boolBase)
	{
		$arBaseGroup = CCatalogGroup::GetBaseGroup();
		if (!empty($arBaseGroup))
		{
			if ($arFields['CATALOG_GROUP_ID'] == $arBaseGroup['ID'])
			{
				$boolBase = true;
			}
			else
			{
				if (!empty($arFields['EXTRA_ID']) && intval($arFields['EXTRA_ID']) > 0)
				{
					$arExtra = CExtra::GetByID($arFields['EXTRA_ID']);
					if (!empty($arExtra))
					{
						$arFilter = array('PRODUCT_ID' => $arFields['PRODUCT_ID'],'CATALOG_GROUP_ID' => $arBaseGroup['ID']);
						if (isset($arFields['QUANTITY_FROM']))
							$arFilter['QUANTITY_FROM'] = $arFields['QUANTITY_FROM'];
						if (isset($arFields['QUANTITY_TO']))
							$arFilter['QUANTITY_TO'] = $arFields['QUANTITY_TO'];
						$rsBasePrices = CPrice::GetListEx(
							array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
							$arFilter,
							false,
							array('nTopCount' => 1),
							array('PRICE','CURRENCY')
						);
						if ($arBasePrice = $rsBasePrices->Fetch())
						{
							$arFields['CURRENCY'] = $arBasePrice['CURRENCY'];
							$arFields['PRICE'] = RoundEx($arBasePrice["PRICE"] * (1 + DoubleVal($arExtra["PERCENTAGE"])/100), CATALOG_VALUE_PRECISION);
						}
						else
						{
							$arFields['EXTRA_ID'] = 0;
						}
					}
					else
					{
						$arFields['EXTRA_ID'] = 0;
					}
				}
			}
		}
	}
}