<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/index.php
 * @author Bitrix
 */
class CAllCatalogGroup
{
	protected static $arBaseGroupCache = array();

	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>;</li>
	* <li> <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>.</li>
	* </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров типа цены. Допустимые ключи: <ul>
	* <li>BASE - Флаг (Y/N) является ли тип базовым.</li> <li>NAME - Внутреннее
	* название типа цены. Ключ является обязательным, если $ACTION = 'ADD'.</li>
	* <li>SORT - Индекс сортировки.</li> <li>XML_ID - Внешний код.</li> <li>CREATED_BY - ID
	* создателя типа цен.</li> <li>MODIFIED_BY - ID последнего изменившего тип
	* цен.</li> <li>USER_GROUP - Массив кодов групп пользователей, члены которых
	* могут видеть цены этого типа. Ключ является обязательным, если
	* $ACTION = 'ADD'.</li> <li>USER_GROUP_BUY - Массив кодов групп пользователей, члены
	* которых могут покупать товары по ценам этого типа. Ключ является
	* обязательным, если $ACTION = 'ADD'.</li> <li>USER_LANG - Ассоциативный массив
	* языкозависимых параметров типа цены, ключами которого являются
	* коды языков, а значениями - названия этого типа цены на
	* соответствующем языке.</li> </ul>
	*
	* @param int $ID = 0 Код типа цен. Параметр является необязательным и имеет смысл
	* только для $ACTION = 'UPDATE'.
	*
	* @return bool <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если метод вернул false, с помощью $APPLICATION-&gt;GetException() можно
	* получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a></li>
	* </ul> </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		global $USER;
		global $DB;

		$boolResult = true;
		$arMsg = array();

		$ACTION = strtoupper($ACTION);
		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		if (array_key_exists("NAME", $arFields) || $ACTION=="ADD")
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if ('' == $arFields["NAME"])
			{
				$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_NAME'));
				$boolResult = false;
			}
		}

		if ((array_key_exists("BASE", $arFields) || $ACTION=="ADD") && $arFields["BASE"] != "Y")
		{
			$arFields["BASE"] = "N";
		}

		if (array_key_exists("SORT", $arFields) || $ACTION=="ADD")
		{
			$arFields["SORT"] = intval($arFields["SORT"]);
			if (0 >= $arFields["SORT"])
				$arFields["SORT"] = 100;
		}

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = intval($USER->GetID());
		$strDateFunction = $DB->GetNowFunction();
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ('ADD' == $ACTION)
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}
		if ('UPDATE' == $ACTION)
		{
			if (array_key_exists('CREATED_BY', $arFields))
				unset($arFields['CREATED_BY']);
		}

		if (is_set($arFields, 'USER_GROUP') || $ACTION=="ADD")
		{
			if (!is_array($arFields['USER_GROUP']) || empty($arFields['USER_GROUP']))
			{
				$arMsg[] = array('id' => 'USER_GROUP', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_USER_GROUP'));
				$boolResult = false;
			}
			else
			{
				$arValid = array();
				foreach ($arFields['USER_GROUP'] as &$intValue)
				{
					$intValue = intval($intValue);
					if (0 < $intValue)
						$arValid[] = $intValue;
				}
				if (isset($intValue))
					unset($intValue);
				if (!empty($arValid))
				{
					$arFields['USER_GROUP'] = array_values(array_unique($arValid));
				}
				else
				{
					$arMsg[] = array('id' => 'USER_GROUP', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_USER_GROUP'));
					$boolResult = false;
				}
			}
		}

		if (is_set($arFields, 'USER_GROUP_BUY') || $ACTION=="ADD")
		{
			if (!is_array($arFields['USER_GROUP_BUY']) || empty($arFields['USER_GROUP_BUY']))
			{
				$arMsg[] = array('id' => 'USER_GROUP_BUY', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_USER_GROUP_BUY'));
				$boolResult = false;
			}
			else
			{
				$arValid = array();
				foreach ($arFields['USER_GROUP_BUY'] as &$intValue)
				{
					$intValue = intval($intValue);
					if (0 < $intValue)
						$arValid[] = $intValue;
				}
				if (isset($intValue))
					unset($intValue);
				if (!empty($arValid))
				{
					$arFields['USER_GROUP_BUY'] = array_values(array_unique($arValid));
				}
				else
				{
					$arMsg[] = array('id' => 'USER_GROUP_BUY', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_USER_GROUP_BUY'));
					$boolResult = false;
				}
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	public static function GetGroupsPerms($arUserGroups = array(), $arCatalogGroupsFilter = array())
	{
		global $USER;

		if (!is_array($arUserGroups))
			$arUserGroups = array($arUserGroups);

		if (empty($arUserGroups))
		{
			$arUserGroups = (CCatalog::IsUserExists() ? $USER->GetUserGroupArray() : array(2));
		}

		$arUserGroupsFilter = array();
		foreach ($arUserGroups as &$intUserGroupID)
		{
			$intUserGroupID = intval($intUserGroupID);
			if (0 < $intUserGroupID)
				$arUserGroupsFilter[] = $intUserGroupID;
		}
		if (isset($intUserGroupID))
			unset($intUserGroupID);

		if (!is_array($arCatalogGroupsFilter))
			$arCatalogGroupsFilter = array($arCatalogGroupsFilter);

		$arResult = array();
		$arResult["view"] = array();
		$arResult["buy"] = array();

		if (empty($arUserGroupsFilter))
			return $arResult;

		$arData = array();

		if (defined("CATALOG_SKIP_CACHE") && CATALOG_SKIP_CACHE)
		{
			$dbPriceGroups = CCatalogGroup::GetGroupsList(array("GROUP_ID" => $arUserGroupsFilter));
			while ($arPriceGroup = $dbPriceGroups->Fetch())
			{
				$arPriceGroup["CATALOG_GROUP_ID"] = intval($arPriceGroup["CATALOG_GROUP_ID"]);

				$key = (($arPriceGroup["BUY"] == "Y") ? "buy" : "view");
				if ($key == "view")
					if (!empty($arCatalogGroupsFilter))
						if (!in_array($arPriceGroup["CATALOG_GROUP_ID"], $arCatalogGroupsFilter))
							continue;

				if (!in_array($arPriceGroup["CATALOG_GROUP_ID"], $arResult[$key]))
					$arResult[$key][] = $arPriceGroup["CATALOG_GROUP_ID"];
			}

			return $arResult;
		}

		$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
		if (defined("CATALOG_CACHE_TIME"))
			$cacheTime = intval(CATALOG_CACHE_TIME);

		global $CACHE_MANAGER;
		if ($CACHE_MANAGER->Read($cacheTime, "catalog_group_perms"))
		{
			$arData = $CACHE_MANAGER->Get("catalog_group_perms");
		}
		else
		{
			$dbPriceGroups = CCatalogGroup::GetGroupsList(array());
			while ($arPriceGroup = $dbPriceGroups->Fetch())
			{
				$arPriceGroup["GROUP_ID"] = intval($arPriceGroup["GROUP_ID"]);
				$arPriceGroup["CATALOG_GROUP_ID"] = intval($arPriceGroup["CATALOG_GROUP_ID"]);

				$key = (($arPriceGroup["BUY"] == "Y") ? "buy" : "view");

				$arData[$arPriceGroup["GROUP_ID"]][$key][] = intval($arPriceGroup["CATALOG_GROUP_ID"]);
			}
			$CACHE_MANAGER->Set("catalog_group_perms", $arData);
		}

		for ($i = 0, $cnt = count($arUserGroupsFilter); $i < $cnt; $i++)
		{
			if (array_key_exists($arUserGroupsFilter[$i], $arData))
			{
				if (array_key_exists("view", $arData[$arUserGroupsFilter[$i]]))
					$arResult["view"] = array_merge($arResult["view"], $arData[$arUserGroupsFilter[$i]]["view"]);
				if (array_key_exists("buy", $arData[$arUserGroupsFilter[$i]]))
					$arResult["buy"] = array_merge($arResult["buy"], $arData[$arUserGroupsFilter[$i]]["buy"]);
			}
		}

		$arResult["view"] = array_unique($arResult["view"]);
		$arResult["buy"] = array_unique($arResult["buy"]);

		if (!empty($arCatalogGroupsFilter))
		{
			$arTmp = array();
			foreach ($arResult["view"] as $i => $arView)
			//for ($i = 0, $cnt = count($arResult["view"]); $i < $cnt; $i++)
			{
				if (in_array($arResult["view"][$i], $arCatalogGroupsFilter))
					$arTmp[] = $arResult["view"][$i];
			}
			$arResult["view"] = $arTmp;
		}

		return $arResult;
	}

	public static function GetListArray()
	{
		$arResult = array();

		if (defined("CATALOG_SKIP_CACHE") && CATALOG_SKIP_CACHE)
		{
			$dbRes = CCatalogGroup::GetListEx(
				array("SORT" => "ASC"),
				array(),
				false,
				false,
				array("ID", "NAME", "BASE", "SORT", "NAME_LANG")
			);
			while ($arRes = $dbRes->Fetch())
				$arResult[$arRes["ID"]] = $arRes;
		}
		else
		{
			$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
			if (defined("CATALOG_CACHE_TIME"))
				$cacheTime = intval(CATALOG_CACHE_TIME);

			global $CACHE_MANAGER;
			if ($CACHE_MANAGER->Read($cacheTime, "catalog_group_".LANGUAGE_ID, "catalog_group"))
			{
				$arResult = $CACHE_MANAGER->Get("catalog_group_".LANGUAGE_ID);
			}
			else
			{
				$dbRes = CCatalogGroup::GetListEx(
					array("SORT" => "ASC"),
					array(),
					false,
					false,
					array("ID", "NAME", "BASE", "SORT", "NAME_LANG")
				);
				while ($arRes = $dbRes->Fetch())
					$arResult[$arRes["ID"]] = $arRes;

				$CACHE_MANAGER->Set("catalog_group_".LANGUAGE_ID, $arResult);
			}
		}

		return $arResult;
	}

	
	/**
	* <p>Метод возвращает код и внутреннее название базового типа цен. Результат работы метода кешируется, поэтому повторные вызовы этого метода в рамках одной страницы не приводят к дополнительным запросам базы данных. Метод динамичный.</p> <p>От цены базового типа расчитываются цены других типов, если они указаны с использованием системы наценок. Понятие базового типа цены используется только в административной части и не оказывает влияния на публичную часть. </p>
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* базового типа цен.</td> </tr> <tr> <td>NAME</td> <td>Внутреннее название
	* базового типа цен.</td> </tr> <tr> <td>NAME_LANG</td> <td>Базовая цена.</td> </tr> <tr>
	* <td>XML_ID</td> <td>XML ID базовой цены.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getbasegroup.e06a3542.php
	* @author Bitrix
	*/
	public static function GetBaseGroup()
	{
		if (empty(self::$arBaseGroupCache) && is_array(self::$arBaseGroupCache))
		{
			$rsGroups = CCatalogGroup::GetListEx(
				array(),
				array('BASE' => 'Y'),
				false,
				false,
				array('ID', 'NAME', 'NAME_LANG', 'XML_ID')
			);
			if ($arGroup = $rsGroups->Fetch())
			{
				$arGroup['ID'] = intval($arGroup['ID']);
				$arGroup['NAME_LANG'] = strval($arGroup['NAME_LANG']);
				$arGroup['XML_ID'] = strval($arGroup['XML_ID']);

				self::$arBaseGroupCache = $arGroup;
			}
			else
			{
				self::$arBaseGroupCache = false;
			}
			if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
			{
				global $CATALOG_BASE_GROUP;
				$CATALOG_BASE_GROUP = self::$arBaseGroupCache;
			}
		}
		return self::$arBaseGroupCache;
	}
}
?>