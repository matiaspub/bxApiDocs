<?php
IncludeModuleLangFile(__FILE__);


/**
 * <b>CCatalogStore</b> - класс для работы со складами.
 *
 *
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
	protected function CheckFields($action, &$arFields)
	{
		if (is_set($arFields["ADDRESS"]) && strlen($arFields["ADDRESS"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_EMPTY_ADDRESS"));
			$arFields["ADDRESS"] = ' ';
		}
		if (($action == 'ADD') &&
			((is_set($arFields, "IMAGE_ID") && strlen($arFields["IMAGE_ID"])<0)))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_WRONG_IMG"));
			return false;
		}
		if (($action == 'ADD') &&
			((is_set($arFields, "LOCATION_ID") && intval($arFields["LOCATION_ID"])<=0)))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_WRONG_LOC"));
			return false;
		}
		if (($action == 'UPDATE') && is_set($arFields, "ID"))
			unset($arFields["ID"]);

		if (($action == 'UPDATE') && strlen($arFields["IMAGE_ID"])<=0)
			unset($arFields["IMAGE_ID"]);

		return true;
	}

	
	/**
	 * <p>Метод изменяет параметры склада с кодом ID, в соответствии с данными из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param int $Id  Код склада для изменения.
	 *
	 *
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
	 *
	 *
	 * @return int <p>Возвращает <i>ID</i> измененного склада, если операция прошла
	 * успешно, в противном случае - <i>false</i>.</p><a name="examples"></a>
	 *
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
			$strSql = "UPDATE b_catalog_store SET ".$strUpdate." WHERE ID = ".$id." ";
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
		if($bNeedConversion)
		{
			self::recalculateStoreBalances($id);
		}

		return $id;
	}

	
	/**
	 * <p>Метод удаляет склад с кодом ID. При этом удаляются так же все остатки товаров, относящиеся к этому складу.</p>
	 *
	 *
	 *
	 *
	 * @param int $Id  Код склада для удаления.
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i>, если операция прошла успешно, в противном
	 * случае - <i>false</i>.</p><a name="examples"></a>
	 *
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
		if ($id > 0)
		{
			$dbDocs = $DB->Query("SELECT ID FROM b_catalog_docs_element WHERE STORE_FROM = ".$id." OR STORE_TO = ".$id." ", true);
			if($bStoreHaveDocs = $dbDocs->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_STORE_HAVE_DOCS"));
				return false;
			}

			$DB->Query("DELETE FROM b_catalog_store_product WHERE STORE_ID = ".$id." ", true);
			$DB->Query("DELETE FROM b_catalog_store WHERE ID = ".$id." ", true);

			foreach(GetModuleEvents("catalog", "OnCatalogStoreDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(intval($id)));

			self::recalculateStoreBalances($id);
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
			return false;
		}
		$dbStoreProduct = CCatalogStoreProduct::GetList(array(), array("STORE_ID" => $id, "!AMOUNT" => 0), false, false, array("PRODUCT_ID", "AMOUNT"));
		while($arStoreProduct = $dbStoreProduct->Fetch())
		{
			$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED, CS.ACTIVE FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arStoreProduct['PRODUCT_ID']." AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED, ACTIVE ", true);
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
			if(!CCatalogProduct::Update($arStoreProduct["PRODUCT_ID"], $arFields))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
				return false;
			}
		}
		return true;
	}
}