<?php
IncludeModuleLangFile(__FILE__);


/**
 * <b>CCatalogStore</b> - класс для работы со складами. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/index.php
 * @author Bitrix
 */
class CAllCatalogStore
{
	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $action  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a>;</li> <li>
	* <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a>.</li> </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров склада. Допустимые ключи: <ul>
	* <li>TITLE - название склада;</li> <li>ACTIVE - активность склада('Y' - активен, 'N'
	* - не активен);</li> <li>ADDRESS - адрес склада;</li> <li>DESCRIPTION - описание
	* склада;</li> <li>GPS_N - GPS координата(широта);</li> <li>GPS_S - GPS
	* координата(долгота);</li> <li>IMAGE_ID - ID картинки склада;</li> <li>PHONE -
	* телефон;</li> <li>SCHEDULE - расписание работы склада;</li> <li>XML_ID - XML_ID
	* склада для экспорта\импорта из 1С;</li> </ul>
	*
	* @return bool <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если функция вернула false, с помощью $APPLICATION-&gt;GetException()
	* можно получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a></li> </ul>
	* </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/checkfields.php
	* @author Bitrix
	*/
	protected function CheckFields($action, &$arFields)
	{
		if(is_set($arFields["ADDRESS"]) && strlen($arFields["ADDRESS"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_EMPTY_ADDRESS"));
			$arFields["ADDRESS"] = ' ';
		}
		if(($action == 'ADD') &&
			((is_set($arFields, "IMAGE_ID") && strlen($arFields["IMAGE_ID"]) < 0)))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_WRONG_IMG"));
			return false;
		}
		if(($action == 'ADD') &&
			((is_set($arFields, "LOCATION_ID") && intval($arFields["LOCATION_ID"]) <= 0)))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_WRONG_LOC"));
			return false;
		}
		if(($action == 'UPDATE') && is_set($arFields, "ID"))
			unset($arFields["ID"]);

		if(($action == 'UPDATE') && strlen($arFields["IMAGE_ID"]) <= 0)
			unset($arFields["IMAGE_ID"]);

		if(isset($arFields["ISSUING_CENTER"]) && ($arFields["ISSUING_CENTER"]) !== 'Y')
		{
			$arFields["ISSUING_CENTER"] = 'N';
		}
		if(isset($arFields["SHIPPING_CENTER"]) && ($arFields["SHIPPING_CENTER"]) !== 'Y')
		{
			$arFields["SHIPPING_CENTER"] = 'N';
		}
		if(isset($arFields["SITE_ID"]) && ($arFields["SITE_ID"]) === '0')
		{
			$arFields["SITE_ID"] = '';
		}
		return true;
	}

	
	/**
	* <p>Метод изменяет параметры склада с кодом ID, в соответствии с данными из массива arFields. Метод статический.</p>
	*
	*
	* @param int $Id  Код склада для изменения.
	*
	* @param array $arFields  Ассоциативный массив параметров склада, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <br><ul> <li>TITLE - название склада;</li> <li>ACTIVE -
	* активность склада('Y' - активен, 'N' - не активен);</li> <li>ADDRESS - адрес
	* склада;</li> <li>DESCRIPTION - описание склада;</li> <li>GPS_N - GPS
	* координата(широта);</li> <li>GPS_S - GPS координата(долгота);</li> <li>IMAGE_ID - ID
	* картинки склада;</li> <li>PHONE - телефон;</li> <li>SCHEDULE - расписание работы
	* склада;</li> <li>XML_ID - XML_ID склада для экспорта\импорта из 1С;</li> </ul>
	*
	* @return int <p>Возвращает <i>ID</i> измененного склада, если операция прошла
	* успешно, в противном случае - <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $arFields = Array(
	* 		"TITLE" =&gt; $TITLE,
	* 		"ACTIVE" =&gt; $ACTIVE,
	* 		"ADDRESS" =&gt; $ADDRESS,
	* 		"DESCRIPTION" =&gt; $DESCRIPTION,
	* 		"IMAGE_ID" =&gt; $fid,
	* 		"GPS_N" =&gt; $GPS_N,
	* 		"GPS_S" =&gt; $GPS_S,
	* 		"PHONE" =&gt; $PHONE,
	* 		"SCHEDULE" =&gt; $SCHEDULE,
	* 		"XML_ID" =&gt; $XML_ID,
	* 	);
	* 	
	* 	$ID = CCatalogStore::Update(1, $arFields);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php
	* @author Bitrix
	*/
	static function Update($id, $arFields)
	{
		global $DB;
		$id = intval($id);
		if (0 >= $id)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforeCatalogStoreUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($id, &$arFields))===false)
				return false;
		}

		$bNeedConversion = false;
		if(array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if(array_key_exists('DATE_MODIFY', $arFields))
			unset($arFields['DATE_MODIFY']);
		if(array_key_exists('DATE_STATUS', $arFields))
			unset($arFields['DATE_STATUS']);
		if(array_key_exists('CREATED_BY', $arFields))
			unset($arFields['CREATED_BY']);

		$arFields['~DATE_MODIFY'] = $DB->GetNowFunction();

		$dbStore = CCatalogStore::GetList(array(), array("ID" => $id), false, false, array("ACTIVE"));
		if($arStore = $dbStore->Fetch())
		{
			if($arStore["ACTIVE"] != $arFields["ACTIVE"])
				$bNeedConversion = true;
		}

		if($id <= 0 || !self::CheckFields('UPDATE',$arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_catalog_store", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "update b_catalog_store set ".$strUpdate." where ID = ".$id;
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
			CCatalogStoreControlUtil::clearStoreName($id);
		}
		if($bNeedConversion)
		{
			self::recalculateStoreBalances($id);
		}

		foreach(GetModuleEvents("catalog", "OnCatalogStoreUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, $arFields));

		return $id;
	}

	
	/**
	* <p>Метод удаляет склад с кодом ID. При этом удаляются так же все остатки товаров, относящиеся к этому складу. Метод статический.</p>
	*
	*
	* @param int $Id  Код склада для удаления.
	*
	* @return bool <p>Возвращает <i>true</i>, если операция прошла успешно, в противном
	* случае - <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $result = CCatalogStore::Delete($id);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/delete.php
	* @author Bitrix
	*/
	static function Delete($id)
	{
		global $DB;
		$id = intval($id);
		if($id > 0)
		{
			foreach (GetModuleEvents("catalog", "OnBeforeCatalogStoreDelete", true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array($id))===false)
					return false;
			}

			$dbDocs = $DB->Query("select ID from b_catalog_docs_element where STORE_FROM = ".$id." or STORE_TO = ".$id, true);
			if($bStoreHaveDocs = $dbDocs->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_STORE_HAVE_DOCS"));
				return false;
			}

			$DB->Query("delete from b_catalog_store_product where STORE_ID = ".$id, true);
			$DB->Query("delete from b_catalog_store where ID = ".$id, true);

			foreach(GetModuleEvents("catalog", "OnCatalogStoreDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));

			self::recalculateStoreBalances($id);
			CCatalogStoreControlUtil::clearStoreName($id);
			return true;
		}
		return false;
	}

	public static function recalculateStoreBalances($id)
	{
		global $DB;
		$arFields = array();
		if(COption::GetOptionString('catalog','default_use_store_control','N') != 'Y')
		{
			return true;
		}
		$dbStoreProduct = CCatalogStoreProduct::GetList(array(), array("STORE_ID" => $id, "!AMOUNT" => 0), false, false, array("PRODUCT_ID", "AMOUNT"));
		while($arStoreProduct = $dbStoreProduct->Fetch())
		{
			$dbAmount = $DB->Query("select SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED, CS.ACTIVE FROM b_catalog_store_product SP inner join b_catalog_product CP on SP.PRODUCT_ID = CP.ID inner join b_catalog_store CS on SP.STORE_ID = CS.ID where SP.PRODUCT_ID = ".$arStoreProduct['PRODUCT_ID']." and CS.ACTIVE = 'Y' group by QUANTITY_RESERVED, ACTIVE", true);
			if($arAmount = $dbAmount->Fetch())
			{
				$arFields["QUANTITY"] = doubleval($arAmount["SUM"] - $arAmount["RESERVED"]);
			}
			else
			{
				if($arReservAmount = CCatalogProduct::GetByID($arStoreProduct['PRODUCT_ID']))
				{
					$arFields["QUANTITY"] = doubleval(0 - $arReservAmount["QUANTITY_RESERVED"]);
				}
			}
			CCatalogProduct::Update($arStoreProduct["PRODUCT_ID"], $arFields);
		}
		return true;
	}
}