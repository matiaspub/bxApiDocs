<?
use Bitrix\Main\Application,
	Bitrix\Main,
	Bitrix\Catalog;

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
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>. Нестатический метод.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения: 
	* 			<br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>;</li>
	* 				<li> <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>.</li>
	* 			</ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров типа цены. Допустимые ключи:  			   
	*      <ul> <li>BASE - Флаг (Y/N) является ли тип базовым.</li>           <li>NAME -
	* Внутреннее название типа цены. Ключ является обязательным, если
	* $ACTION = 'ADD'.</li> 		  <li>SORT - Индекс сортировки.</li> 		  <li>XML_ID - Внешний
	* код.</li> 		  <li>CREATED_BY - ID создателя типа цен.</li> 		  <li>MODIFIED_BY - ID
	* последнего изменившего тип цен.</li> 		  <li>USER_GROUP - Массив кодов групп
	* пользователей, члены которых могут видеть цены этого типа. Ключ
	* является обязательным, если $ACTION = 'ADD'.</li> 		  <li>USER_GROUP_BUY - Массив
	* кодов групп пользователей, члены которых могут покупать товары
	* по ценам этого типа. Ключ является обязательным, если $ACTION = 'ADD'.</li>
	* 		  <li>USER_LANG - Ассоциативный массив языкозависимых параметров типа
	* цены, ключами которого являются коды языков, а значениями -
	* названия этого типа цены на соответствующем языке.</li>         </ul>
	*
	* @param int $intID = 0 Код типа цен. Параметр является необязательным и имеет смысл
	* только для $ACTION = 'UPDATE'.
	*
	* @return bool <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если метод вернул false, с помощью $APPLICATION-&gt;GetException() можно
	* получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li>
	* 	<li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a></li>
	* 	<li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a></li>
	* </ul><br><br>
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
			$arUserGroups = (CCatalog::IsUserExists() ? $USER->GetUserGroupArray() : array(2));
		Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups);

		if (!is_array($arCatalogGroupsFilter))
			$arCatalogGroupsFilter = array($arCatalogGroupsFilter);
		Main\Type\Collection::normalizeArrayValuesByInt($arCatalogGroupsFilter);
		if (!empty($arCatalogGroupsFilter))
			$arCatalogGroupsFilter = array_fill_keys($arCatalogGroupsFilter, true);

		$result = array(
			'view' => array(),
			'buy' => array()
		);

		if (empty($arUserGroups))
			return $result;

		if (defined('CATALOG_SKIP_CACHE') && CATALOG_SKIP_CACHE)
		{
			$priceTypeIterator = CCatalogGroup::GetGroupsList(array('@GROUP_ID' => $arUserGroups));
			while ($priceType = $priceTypeIterator->Fetch())
			{
				$priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];;
				$key = ($priceType['BUY'] == 'Y' ? 'buy' : 'view');
				if ($key == 'view' && !empty($arCatalogGroupsFilter) && !isset($arCatalogGroupsFilter[$priceTypeId]))
					continue;
				$result[$key][$priceTypeId] = $priceTypeId;
				unset($key, $priceTypeId);
			}
			unset($priceType, $priceTypeIterator);
			if (!empty($result['view']))
				$result['view'] = array_values($result['view']);
			if (!empty($result['buy']))
				$result['buy'] = array_values($result['buy']);

			return $result;
		}

		$data = array();
		$cacheTime = (int)(defined('CATALOG_CACHE_TIME') ? CATALOG_CACHE_TIME : CATALOG_CACHE_DEFAULT_TIME);
		$managedCache = Application::getInstance()->getManagedCache();
		if ($managedCache->read($cacheTime, 'catalog_group_perms'))
		{
			$data = $managedCache->get('catalog_group_perms');
		}
		else
		{
			$priceTypeIterator = CCatalogGroup::GetGroupsList();
			while ($priceType = $priceTypeIterator->Fetch())
			{
				$priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];
				$groupId = (int)($priceType['GROUP_ID']);
				$key = ($priceType['BUY'] == 'Y' ? 'buy' : 'view');

				if (!isset($data[$groupId]))
					$data[$groupId] = array(
						'view' => array(),
						'buy' => array()
					);
				$data[$groupId][$key][$priceTypeId] = $priceTypeId;
				unset($key, $groupId, $priceTypeId);
			}
			unset($priceType, $priceTypeIterator);
			if (!empty($data))
			{
				foreach ($data as &$groupData)
				{
					if (!empty($groupData['view']))
						$groupData['view'] = array_values($groupData['view']);
					if (!empty($groupData['buy']))
						$groupData['buy'] = array_values($groupData['buy']);
				}
				unset($groupData);
			}
			$managedCache->set('catalog_group_perms', $data);
		}

		foreach ($arUserGroups as &$groupId)
		{
			if (!isset($data[$groupId]))
				continue;
			if (!empty($data[$groupId]['view']))
			{
				$priceTypeList = $data[$groupId]['view'];
				foreach ($priceTypeList as &$priceTypeId)
				{
					if (!empty($arCatalogGroupsFilter) && !isset($arCatalogGroupsFilter[$priceTypeId]))
						continue;
					$result['view'][$priceTypeId] = $priceTypeId;
				}
				unset($priceTypeId, $priceTypeList);
			}
			if (!empty($data[$groupId]['buy']))
			{
				$priceTypeList = $data[$groupId]['buy'];
				foreach ($priceTypeList as &$priceTypeId)
					$result['buy'][$priceTypeId] = $priceTypeId;
				unset($priceTypeId, $priceTypeList);
			}
		}
		unset($groupId);

		if (!empty($result['view']))
			$result['view'] = array_values($result['view']);
		if (!empty($result['buy']))
			$result['buy'] = array_values($result['buy']);

		return $result;
	}

	public static function GetListArray()
	{
		$result = array();

		if (defined('CATALOG_SKIP_CACHE') && CATALOG_SKIP_CACHE)
		{
			$groupIterator = Catalog\GroupTable::getList(array(
				'select' => array('ID', 'NAME', 'BASE', 'SORT', 'XML_ID', 'NAME_LANG' =>'CURRENT_LANG.NAME'),
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
			));
			while ($group = $groupIterator->fetch())
				$result[$group['ID']] = $group;
			unset($group, $groupIterator);
		}
		else
		{

			$cacheTime = (int)(defined('CATALOG_CACHE_TIME') ? CATALOG_CACHE_TIME : CATALOG_CACHE_DEFAULT_TIME);
			$managedCache = Application::getInstance()->getManagedCache();
			if ($managedCache->read($cacheTime, 'catalog_group_'.LANGUAGE_ID, 'catalog_group'))
			{
				$result = $managedCache->get('catalog_group_'.LANGUAGE_ID);
			}
			else
			{
				$groupIterator = Catalog\GroupTable::getList(array(
					'select' => array('ID', 'NAME', 'BASE', 'SORT', 'XML_ID', 'NAME_LANG' =>'CURRENT_LANG.NAME'),
					'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
				));
				while ($group = $groupIterator->fetch())
					$result[$group['ID']] = $group;
				unset($group, $groupIterator);
				$managedCache->set('catalog_group_'.LANGUAGE_ID, $result);
			}
			unset($managedCache, $cacheTime);
		}

		return $result;
	}

	
	/**
	* <p>Метод возвращает код и внутреннее название базового типа цен. Результат работы метода кешируется, поэтому повторные вызовы этого метода в рамках одной страницы не приводят к дополнительным запросам базы данных. Нестатический метод.</p> <p>От цены базового типа расчитываются цены других типов, если они указаны с использованием системы наценок. Понятие базового типа цены используется только в административной части и не оказывает влияния на публичную часть. </p>
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив с ключами:</p><table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th>     <th>Описание</th>   </tr> <tr> <td>ID</td> <td>Код
	* базового типа цен.</td> </tr> <tr> <td>NAME</td> <td>Внутреннее название
	* базового типа цен.</td> </tr> <tr> <td>NAME_LANG</td> <td>Базовая цена.</td> </tr> <tr>
	* <td>XML_ID</td> <td>XML ID базовой цены.</td> </tr> </table><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getbasegroup.e06a3542.php
	* @author Bitrix
	*/
	public static function GetBaseGroup()
	{
		if (empty(self::$arBaseGroupCache) && is_array(self::$arBaseGroupCache))
		{
			self::$arBaseGroupCache = false;
			$group = Catalog\GroupTable::getList(array(
				'select' => array('ID', 'NAME', 'BASE', 'SORT', 'XML_ID', 'NAME_LANG' =>'CURRENT_LANG.NAME'),
				'filter' => array('=BASE' => 'Y')
			))->fetch();
			if (!empty($group))
			{
				$group['ID'] = (int)$group['ID'];
				$group['NAME_LANG'] = (string)$group['NAME_LANG'];
				$group['XML_ID'] = (string)$group['XML_ID'];

				self::$arBaseGroupCache = $group;
			}
			unset($group);
		}
		return self::$arBaseGroupCache;
	}
}