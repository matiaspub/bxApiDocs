<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["CATALOG_BASE_GROUP"] = array();


/**
 * 
 *
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
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$boolResult = true;
		$arMsg = array();

		if (is_set($arFields, "NAME") || $strAction=="ADD")
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if (0 >= strlen($arFields["NAME"]))
			{
				$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('BT_MOD_CAT_GROUP_ERR_EMPTY_NAME'));
				$boolResult = false;
			}
		}

		if ((is_set($arFields, "BASE") || $ACTION=="ADD") && $arFields["BASE"] != "Y")
		{
			$arFields["BASE"] = "N";
		}

		if (is_set($arFields, "SORT") || $ACTION=="ADD")
		{
			$arFields["SORT"] = intval($arFields["SORT"]);
			if (0 >= $arFields["SORT"])
				$arFields["SORT"] = 100;
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

		if (count($arUserGroups) <= 0)
			$arUserGroups = $USER->GetUserGroupArray();

		$arUserGroupsFilter = array();
		for ($i = 0, $cnt = count($arUserGroups); $i < $cnt; $i++)
		{
			$arUserGroups[$i] = IntVal($arUserGroups[$i]);
			if ($arUserGroups[$i] > 0)
				$arUserGroupsFilter[] = $arUserGroups[$i];
		}

		$arResult = array();
		$arResult["view"] = array();
		$arResult["buy"] = array();

		if (count($arUserGroupsFilter) <= 0)
			return $arResult;

		$arData = array();

		if (defined("CATALOG_SKIP_CACHE") && CATALOG_SKIP_CACHE)
		{
			$dbPriceGroups = CCatalogGroup::GetGroupsList(array("GROUP_ID" => $arUserGroupsFilter));
			while ($arPriceGroup = $dbPriceGroups->Fetch())
			{
				$arPriceGroup["CATALOG_GROUP_ID"] = IntVal($arPriceGroup["CATALOG_GROUP_ID"]);

				$key = (($arPriceGroup["BUY"] == "Y") ? "buy" : "view");
				if ($key == "view")
					if (count($arCatalogGroupsFilter) > 0)
						if (!in_array($arPriceGroup["CATALOG_GROUP_ID"], $arCatalogGroupsFilter))
							continue;

				if (!in_array($arPriceGroup["CATALOG_GROUP_ID"], $arResult[$key]))
					$arResult[$key][] = $arPriceGroup["CATALOG_GROUP_ID"];
			}

			return $arResult;
		}

		$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
		if (defined("CATALOG_CACHE_TIME"))
			$cacheTime = IntVal(CATALOG_CACHE_TIME);

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
				$arPriceGroup["GROUP_ID"] = IntVal($arPriceGroup["GROUP_ID"]);
				$arPriceGroup["CATALOG_GROUP_ID"] = IntVal($arPriceGroup["CATALOG_GROUP_ID"]);

				$key = (($arPriceGroup["BUY"] == "Y") ? "buy" : "view");

				$arData[$arPriceGroup["GROUP_ID"]][$key][] = IntVal($arPriceGroup["CATALOG_GROUP_ID"]);
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

		if (count($arCatalogGroupsFilter) > 0)
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
				$cacheTime = IntVal(CATALOG_CACHE_TIME);

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
	 * <p>Функция возвращает код и внутреннее название базового типа цен. Результат работы функции кэшируется, поэтому повторные вызовы этой функции в рамках одной страницы не приводят к дополнительным запросам базы данных.</p> <p>От цены базового типа расчитываются цены других типов, если они указаны с использованием системы наценок. Понятие базового типа цены используется только в административной части и не оказывает влияния на публичную часть. </p>
	 *
	 *
	 *
	 *
	 * @return array <p>Функция возвращает ассоциативный массив с ключами:</p><table
	 * class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	 * <td>Код базового типа цен.</td> </tr> <tr> <td>NAME</td> <td>Внутреннее название
	 * базового типа цен.</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getbasegroup.e06a3542.php
	 * @author Bitrix
	 */
	public static function GetBaseGroup()
	{
		global $CATALOG_BASE_GROUP;
		if (!isset($CATALOG_BASE_GROUP) || empty($CATALOG_BASE_GROUP) || !is_array($CATALOG_BASE_GROUP))
		{
			$rsGroups = CCatalogGroup::GetListEx(array(), array('BASE' => 'Y'), false, false, array('ID', 'NAME', 'NAME_LANG', 'XML_ID'));
			if ($arGroup = $rsGroups->Fetch())
			{
				$arGroup['ID'] = intval($arGroup['ID']);
				$arGroup['NAME_LANG'] = strval($arGroup['NAME_LANG']);
				$arGroup['XML_ID'] = strval($arGroup['XML_ID']);

				$CATALOG_BASE_GROUP = $arGroup;
			}
			else
			{
				if (isset($CATALOG_BASE_GROUP))
					unset($CATALOG_BASE_GROUP);
				return false;
			}
		}
		return $CATALOG_BASE_GROUP;
	}
}
?>