<?
/** global array $CATALOG_BASE_GROUP */
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/cataloggroup.php");


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
class CCatalogGroup extends CAllCatalogGroup
{
	
	/**
	* <p>Метод возвращает параметры типа цен с кодом ID, включая языкозависимые параметры для языка lang. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код типа цены.
	*
	* @param string $lang = LANGUAGE_ID Код языка, по умолчанию равен текущему языку.
	*
	* @return array <p>Возвращает ассоциативный массив со следующими ключами:</p> <table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> <th width="15%">С
	* версии</th> </tr> <tr> <td>ID</td> <td>Код типа цены.</td> <td></td> </tr> <tr> <td>NAME</td>
	* <td>Внутреннее название типа цены.</td> <td></td> </tr> <tr> <td>BASE</td> <td>Флаг (Y/N)
	* является ли тип базовым.</td> <td></td> </tr> <tr> <td>SORT</td> <td>Индекс
	* сортировки.</td> <td></td> </tr> <tr> <td>XML_ID</td> <td>Внешний код.</td> <td>12.0.9</td> </tr>
	* <tr> <td>CAN_ACCESS</td> <td>Флаг (Y/N) имеет ли текущий пользователь право на
	* видеть цены этого типа.</td> <td></td> </tr> <tr> <td>CAN_BUY</td> <td>Флаг (Y/N) имеет ли
	* текущий пользователь право покупать товары по ценам этого
	* типа.</td> <td></td> </tr> <tr> <td>NAME_LANG</td> <td>Название типа цены на языке lang.</td>
	* <td></td> </tr> <tr> <td>CREATED_BY</td> <td>Код пользователя, создавшего тип цен.</td>
	* <td>12.5.5</td> </tr> <tr> <td>MODIFIED_BY</td> <td>Код последнего пользователя,
	* изменившего тип цен.</td> <td>12.5.5</td> </tr> <tr> <td>TIMESTAMP_X</td> <td>Дата
	* последнего изменения типа цен.</td> <td>12.5.5</td> </tr> <tr> <td>DATE_CREATE</td>
	* <td>Дата создания типа цен.</td> <td>12.5.5</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $rn = CCatalogGroup::GetByID($ID);
	* if ($rn["CAN_ACCESS"]=="Y")
	*    echo "Вы можете видеть цены типа ".$rn["NAME_LANG"]."&lt;br&gt;";
	* if ($rn["CAN_BUY"]=="Y")
	*    echo "Вы можете покупать товары по ценам типа ".$rn["NAME_LANG"]."&lt;br&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getbyid.cc56219b.php
	* @author Bitrix
	*/
	public static function GetByID($ID, $lang = LANGUAGE_ID)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		global $DB, $USER;

		$strUserGroups = (CCatalog::IsUserExists() ? $USER->GetGroups() : '2');

		$strSql =
			"SELECT CG.ID, CG.NAME, CG.BASE, CG.SORT, CG.XML_ID, IF(CGG.ID IS NULL, 'N', 'Y') as CAN_ACCESS, CGL.NAME as NAME_LANG, IF(CGG1.ID IS NULL, 'N', 'Y') as CAN_BUY, ".
			"CG.CREATED_BY, CG.MODIFIED_BY, ".$DB->DateToCharFunction('CG.TIMESTAMP_X', 'FULL').' as TIMESTAMP_X, '.$DB->DateToCharFunction('CG.DATE_CREATE', 'FULL')." as DATE_CREATE ".
			"FROM b_catalog_group CG ".
			"	LEFT JOIN b_catalog_group2group CGG ON (CG.ID = CGG.CATALOG_GROUP_ID AND CGG.GROUP_ID IN (".$strUserGroups.") AND CGG.BUY <> 'Y') ".
			"	LEFT JOIN b_catalog_group2group CGG1 ON (CG.ID = CGG1.CATALOG_GROUP_ID AND CGG1.GROUP_ID IN (".$strUserGroups.") AND CGG1.BUY = 'Y') ".
			"	LEFT JOIN b_catalog_group_lang CGL ON (CG.ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".$DB->ForSql($lang)."') ".
			"WHERE CG.ID = ".$ID." GROUP BY CG.ID, CG.NAME, CG.BASE, CG.XML_ID, CG.MODIFIED_BY, CG.CREATED_BY, CG.DATE_CREATE, CG.TIMESTAMP_X, CGL.NAME";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;
		return false;
	}

	
	/**
	* <p>Метод добавляет новый тип цен. При этом сохраняются как языкозависимые параметры типа, так и параметры, которые не зависят от языка. Так же есть возможность указать группы пользователей, члены которых могут просматривать и покупать товары по ценам этого типа. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров типа цены, ключами которого
	* являются названия параметров, а значениями - новые значения.<br>
	* Допустимые параметры: <ul> <li>BASE - флаг (Y/N) является ли тип базовым
	* (если для добавляемого типа цен указано <i>Y</i> и в системе уже есть
	* некоторый базовый тип цен, то флаг с существующего типа будет
	* снят);</li> <li>NAME - внутреннее название типа цены;</li> <li>SORT - индекс
	* сортировки;</li> <li>XML_ID - внешний код;</li> <li>CREATED_BY - ID создателя типа
	* цен;</li> <li>MODIFIED_BY - ID последнего изменившего тип цен;</li> <li>USER_GROUP -
	* массив кодов групп пользователей, члены которых могут видеть
	* цены этого типа;</li> <li>USER_GROUP_BUY - массив кодов групп пользователей,
	* члены которых могут покупать товары по ценам этого типа;</li>
	* <li>USER_LANG - ассоциативный массив языкозависимых параметров типа
	* цены, ключами которого являются коды языков, а значениями -
	* названия этого типа цены на соответствующем языке. </li> </ul>
	*
	* @return int <p>Возвращает код добавленного типа цены или <i>false</i> в случае
	* ошибки </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "NAME" =&gt; "retail",
	*    "SORT" =&gt; 100,
	*    "USER_GROUP" =&gt; array(2, 4),   // видят цены члены групп 2 и 4
	*    "USER_GROUP_BUY" =&gt; array(2),  // покупают по этой цене
	*                                   // только члены группы 2
	*    "USER_LANG" =&gt; array(
	*       "ru" =&gt; "Розничная",
	*       "en" =&gt; "Retail"
	*       )
	* );
	* 
	* $ID = CCatalogGroup::Add($arFields);
	* if ($ID&lt;=0)
	*    echo "Ошибка добавления типа цены";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER, $stackCacheManager;

		foreach(GetModuleEvents("catalog", "OnBeforeGroupAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;
		}

		if (!CCatalogGroup::CheckFields("ADD", $arFields, 0))
			return false;

		if ($arFields["BASE"] == "Y")
		{
			$strUpdate = "BASE = 'N', TIMESTAMP_X = ".$DB->GetNowFunction();
			if (array_key_exists('MODIFIED_BY', $arFields))
			{
				$strUpdate .= ", MODIFIED_BY = ".$arFields["MODIFIED_BY"];
			}
			$strSql = "UPDATE b_catalog_group SET ".$strUpdate." WHERE BASE = 'Y'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			self::$arBaseGroupCache = array();
			if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
			{
				global $CATALOG_BASE_GROUP;
				$CATALOG_BASE_GROUP = self::$arBaseGroupCache;
			}
		}

		$arInsert = $DB->PrepareInsert("b_catalog_group", $arFields);

		$strSql = "INSERT INTO b_catalog_group(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$groupID = (int)$DB->LastID();

		foreach ($arFields["USER_GROUP"] as &$intValue)
		{
			$strSql = "INSERT INTO b_catalog_group2group(CATALOG_GROUP_ID, GROUP_ID, BUY) VALUES(".$groupID.", ".$intValue.", 'N')";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (isset($intValue))
			unset($intValue);

		foreach ($arFields["USER_GROUP_BUY"] as &$intValue)
		{
			$strSql = "INSERT INTO b_catalog_group2group(CATALOG_GROUP_ID, GROUP_ID, BUY) VALUES(".$groupID.", ".$intValue.", 'Y')";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (isset($intValue))
			unset($intValue);

		if (isset($arFields["USER_LANG"]) && is_array($arFields["USER_LANG"]) && !empty($arFields["USER_LANG"]))
		{
			foreach ($arFields["USER_LANG"] as $key => $value)
			{
				$strSql =
					"INSERT INTO b_catalog_group_lang(CATALOG_GROUP_ID, LANG, NAME) VALUES(".$groupID.", '".$DB->ForSql($key)."', '".$DB->ForSql($value)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		if (!defined("CATALOG_SKIP_CACHE") || !CATALOG_SKIP_CACHE)
		{
			$CACHE_MANAGER->CleanDir("catalog_group");
			$CACHE_MANAGER->Clean("catalog_group_perms");
		}

		$stackCacheManager->Clear("catalog_GetQueryBuildArrays");
		$stackCacheManager->Clear("catalog_discount");

		foreach(GetModuleEvents("catalog", "OnGroupAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($groupID, $arFields));
		}
		// strange copy-paste bug
		foreach(GetModuleEvents("catalog", "OnGroupUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($groupID, $arFields));
		}

		return $groupID;
	}

	
	/**
	* <p>Метод изменяет параметры типа цены с кодом ID на значения из массива arFields. При этом сохраняются как языкозависимые параметры типа, так и параметры, которые не зависят от языка. Так же есть возможность указать группы пользователей, члены которых могут просматривать и покупать товары по ценам этого типа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код изменяемого типа цены.
	*
	* @param array $arFields  Ассоциативный массив параметров типа цены, ключами которого
	* являются названия параметров, а значениями - новые значения.
	* Допустимые параметры: <ul> <li>BASE - флаг (Y/N) является ли тип
	* базовым;</li> <li>NAME - внутреннее название типа цены;</li> <li>SORT - индекс
	* сортировки;</li> <li>XML_ID - внешний код;</li> <li>MODIFIED_BY - ID последнего
	* изменившего тип цен;</li> <li>USER_GROUP - массив кодов групп
	* пользователей, члены которых могут видеть цены этого типа;</li>
	* <li>USER_GROUP_BUY - массив кодов групп пользователей, члены которых могут
	* покупать товары по ценам этого типа;</li> <li>USER_LANG - ассоциативный
	* массив языкозависимых параметров типа цены, ключами которого
	* являются коды языков, а значениями - названия этого типа цены на
	* соответствующем языке.</li> </ul>
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного изменения параметров типа
	* цени и <i>false</i> - в случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "NAME" =&gt; "retail",
	*    "SORT" =&gt; 100,
	*    "USER_GROUP" =&gt; array(2, 4),   // видят цены члены групп 2 и 4
	*    "USER_GROUP_BUY" =&gt; array(2),  // покупают по этой цене
	*                                   // только члены группы 2
	*    "USER_LANG" =&gt; array(
	*       "ru" =&gt; "Розничная",
	*       "en" =&gt; "Retail"
	*       )
	* );
	* 
	* if (!CCatalogGroup::Update($ID, $arFields))
	*    echo "Ошибка добавления типа цены";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER, $stackCacheManager;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		foreach(GetModuleEvents("catalog", "OnBeforeGroupUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;
		}

		if (!CCatalogGroup::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_group", $arFields);
		if (!empty($strUpdate))
		{
			if (isset($arFields["BASE"]) && $arFields["BASE"] == "Y")
			{
				$strBaseUpdate = "BASE = 'N', TIMESTAMP_X = ".$DB->GetNowFunction();
				if (array_key_exists('MODIFIED_BY', $arFields))
				{
					$strBaseUpdate .= ", MODIFIED_BY = ".$arFields["MODIFIED_BY"];
				}
				$strSql = "UPDATE b_catalog_group SET ".$strBaseUpdate." WHERE ID != ".$ID." AND BASE = 'Y'";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				self::$arBaseGroupCache = array();
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					global $CATALOG_BASE_GROUP;
					$CATALOG_BASE_GROUP = self::$arBaseGroupCache;
				}
			}

			$strSql = "UPDATE b_catalog_group SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (isset($arFields["USER_GROUP"]) && is_array($arFields["USER_GROUP"]) && !empty($arFields["USER_GROUP"]))
		{
			$DB->Query("DELETE FROM b_catalog_group2group WHERE CATALOG_GROUP_ID = ".$ID." AND BUY <> 'Y'");
			foreach ($arFields["USER_GROUP"] as &$intValue)
			{
				$strSql = "INSERT INTO b_catalog_group2group(CATALOG_GROUP_ID, GROUP_ID, BUY) VALUES(".$ID.", ".$intValue.", 'N')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (isset($intValue))
				unset($intValue);
		}

		if (isset($arFields["USER_GROUP_BUY"]) && is_array($arFields["USER_GROUP_BUY"]) && !empty($arFields["USER_GROUP_BUY"]))
		{
			$DB->Query("DELETE FROM b_catalog_group2group WHERE CATALOG_GROUP_ID = ".$ID." AND BUY = 'Y'");
			foreach ($arFields["USER_GROUP_BUY"] as &$intValue)
			{
				$strSql = "INSERT INTO b_catalog_group2group(CATALOG_GROUP_ID, GROUP_ID, BUY) VALUES(".$ID.", ".$intValue.", 'Y')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (isset($intValue))
				unset($intValue);
		}

		if (isset($arFields["USER_LANG"]) && is_array($arFields["USER_LANG"]) && !empty($arFields["USER_LANG"]))
		{
			$DB->Query("DELETE FROM b_catalog_group_lang WHERE CATALOG_GROUP_ID = ".$ID);
			foreach ($arFields["USER_LANG"] as $key => $value)
			{
				$strSql =
					"INSERT INTO b_catalog_group_lang(CATALOG_GROUP_ID, LANG, NAME) VALUES(".$ID.", '".$DB->ForSql($key)."', '".$DB->ForSql($value)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		if (!defined("CATALOG_SKIP_CACHE") || !CATALOG_SKIP_CACHE)
		{
			$CACHE_MANAGER->CleanDir("catalog_group");
			$CACHE_MANAGER->Clean("catalog_group_perms");
		}

		$stackCacheManager->Clear("catalog_GetQueryBuildArrays");
		$stackCacheManager->Clear("catalog_discount");

		foreach(GetModuleEvents("catalog", "OnGroupUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет тип цены с кодом ID. При этом цены этого типа так же удаляются. Базовый тип цен удалить невозможно. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код удаляемого типа цены.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER, $stackCacheManager, $APPLICATION;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if ($res = CCatalogGroup::GetByID($ID))
		{
			if ($res["BASE"] != "Y")
			{
				foreach(GetModuleEvents("catalog", "OnBeforeGroupDelete", true) as $arEvent)
				{
					if (ExecuteModuleEventEx($arEvent, array($ID))===false)
						return false;
				}

				foreach(GetModuleEvents("catalog", "OnGroupDelete", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array($ID));
				}

				if (!defined("CATALOG_SKIP_CACHE") || !CATALOG_SKIP_CACHE)
				{
					$CACHE_MANAGER->CleanDir("catalog_group");
					$CACHE_MANAGER->Clean("catalog_group_perms");
				}

				$stackCacheManager->Clear("catalog_GetQueryBuildArrays");
				$stackCacheManager->Clear("catalog_discount");

				$DB->Query("DELETE FROM b_catalog_price WHERE CATALOG_GROUP_ID = ".$ID);
				$DB->Query("DELETE FROM b_catalog_group2group WHERE CATALOG_GROUP_ID = ".$ID);
				$DB->Query("DELETE FROM b_catalog_group_lang WHERE CATALOG_GROUP_ID = ".$ID);
				return $DB->Query("DELETE FROM b_catalog_group WHERE ID = ".$ID, true);
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage('BT_MOD_CAT_GROUP_ERR_CANNOT_DELETE_BASE_TYPE'), 'BASE');
			}
		}

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из типов цен каталога в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле цен каталога, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи типов цен
	* каталога. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	* значение поля меньше или равно передаваемой в фильтр
	* величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	* фильтр величины;</b></li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле цен каталога.<br><br> Пример фильтра: <pre
	* class="syntax">array("SUBSCRIPTION" =&gt; "Y")</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле SUBSCRIPTION (флаг "Продажа контента")
	* равно Y".<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи типов цен каталога.
	* Массив имеет вид: <pre class="syntax">array("название_поля1", "название_поля2", .
	* . .)</pre> В качестве "название_поля<i>N</i>" может стоять любое поле
	* типов цен каталога. <br><br> Если массив пустой, то метод вернет число
	* записей, удовлетворяющих фильтру.<br><br> Значение по умолчанию -
	* <i>false</i> - означает, что результат группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Объект класса CDBResult, содержащий набор ассоциативных массивов с
	* ключами: </p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* <th width="15%">С версии</th> </tr> <tr> <td>ID</td> <td>Код типа цены. </td> <td></td> </tr> <tr>
	* <td>NAME</td> <td>Внутреннее название типа цены. </td> <td></td> </tr> <tr> <td>BASE</td>
	* <td>Флаг (Y/N) является ли тип базовым. </td> <td></td> </tr> <tr> <td>SORT</td>
	* <td>Индекс сортировки. </td> <td></td> </tr> <tr> <td>CAN_ACCESS</td> <td>Флаг (Y/N) имеет ли
	* текущий пользователь право видеть цены этого типа. </td> <td></td> </tr> <tr>
	* <td>CAN_BUY</td> <td>Флаг (Y/N) имеет ли текущий пользователь право покупать
	* товары по ценам этого типа. </td> <td></td> </tr> <tr> <td>NAME_LANG</td> <td>Название
	* типа цены на языке lang.</td> <td></td> </tr> <tr> <td>XML_ID</td> <td>Внешний код.</td>
	* <td>12.0.9</td> </tr> <tr> <td>CREATED_BY</td> <td>Код пользователя, создавшего тип
	* цен.</td> <td>12.5.5</td> </tr> <tr> <td>MODIFIED_BY</td> <td>Код последнего пользователя,
	* изменившего тип цен.</td> <td>12.5.5</td> </tr> <tr> <td>TIMESTAMP_X</td> <td>Дата
	* последнего изменения типа цен.</td> <td>12.5.5</td> </tr> <tr> <td>DATE_CREATE</td>
	* <td>Дата создания типа цен.</td> <td>12.5.5</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем типы цен с внутренним именем retail
	* $dbPriceType = CCatalogGroup::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array("NAME" =&gt; "retail")
	*     );
	* while ($arPriceType = $dbPriceType-&gt;Fetch())
	* {
	*     echo $arPriceType["NAME_LANG"]." - ".$arPriceType["CAN_ACCESS"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getlist.ae5063fc.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER;

		// for old-style execution
		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if ('' != $arOrder && '' != $arFilter)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;
			if ($arNavStartParams != false && '' != $arNavStartParams)
				$arFilter["LID"] = $arNavStartParams;
			else
				$arFilter["LID"] = LANGUAGE_ID;
		}
		if (!isset($arFilter['LID']))
			$arFilter['LID'] = LANGUAGE_ID;

		$strUserGroups = (CCatalog::IsUserExists() ? $USER->GetGroups() : '2');

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "NAME", "BASE", "SORT", "NAME_LANG", "CAN_ACCESS", "CAN_BUY", "XML_ID", "MODIFIED_BY", "CREATED_BY", "DATE_CREATE", "TIMESTAMP_X");
		if ($arGroupBy == false)
			$arGroupBy = array("ID", "NAME", "BASE", "SORT", "XML_ID", "MODIFIED_BY", "CREATED_BY", "DATE_CREATE", "TIMESTAMP_X", "NAME_LANG");

		$arFields = array(
			"ID" => array("FIELD" => "CG.ID", "TYPE" => "int"),
			"NAME" => array("FIELD" => "CG.NAME", "TYPE" => "string"),
			"BASE" => array("FIELD" => "CG.BASE", "TYPE" => "char"),
			"SORT" => array("FIELD" => "CG.SORT", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "CG.XML_ID", "TYPE" => "string"),
			"TIMESTAMP_X" => array("FIELD" => "CG.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "CG.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CG.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CG.CREATED_BY", "TYPE" => "int"),
			"NAME_LANG" => array("FIELD" => "CGL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_group_lang CGL ON (CG.ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".$DB->ForSql($arFilter["LID"], 2)."')"),
		);

		$arFields["CAN_ACCESS"] = array(
			"FIELD" => "IF(CGG.ID IS NULL, 'N', 'Y')",
			"TYPE" => "char",
			"FROM" => "LEFT JOIN b_catalog_group2group CGG ON (CG.ID = CGG.CATALOG_GROUP_ID AND CGG.GROUP_ID IN (".$strUserGroups.") AND CGG.BUY <> 'Y')",
			"GROUPED" => "N"
		);
		$arFields["CAN_BUY"] = array(
			"FIELD" => "IF(CGG1.ID IS NULL, 'N', 'Y')",
			"TYPE" => "char",
			"FROM" => "LEFT JOIN b_catalog_group2group CGG1 ON (CG.ID = CGG1.CATALOG_GROUP_ID AND CGG1.GROUP_ID IN (".$strUserGroups.") AND CGG1.BUY = 'Y')",
			"GROUPED" => "N"
		);

		$arSqls = CCatalog::_PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group CG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
			if (!empty($arSqls["HAVING"]))
				$strSql .= " HAVING ".$arSqls["HAVING"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group CG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["HAVING"]))
			$strSql .= " HAVING ".$arSqls["HAVING"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_group CG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];
			if (!empty($arSqls["HAVING"]))
				$strSql_tmp .= " HAVING ".$arSqls["HAVING"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "NAME", "BASE", "SORT", "NAME_LANG", "XML_ID", "MODIFIED_BY", "CREATED_BY", "DATE_CREATE", "TIMESTAMP_X");

		$arFields = array(
			"ID" => array("FIELD" => "CG.ID", "TYPE" => "int"),
			"NAME" => array("FIELD" => "CG.NAME", "TYPE" => "string"),
			"BASE" => array("FIELD" => "CG.BASE", "TYPE" => "char"),
			"SORT" => array("FIELD" => "CG.SORT", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "CG.XML_ID", "TYPE" => "string"),
			"TIMESTAMP_X" => array("FIELD" => "CG.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "CG.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CG.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CG.CREATED_BY", "TYPE" => "int"),

			"GROUP_ID" => array("FIELD" => "CG2G.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group2group CG2G ON (CG.ID = CG2G.CATALOG_GROUP_ID)"),
			"GROUP_CATALOG_GROUP_ID" => array("FIELD" => "CG2G.CATALOG_GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group2group CG2G ON (CG.ID = CG2G.CATALOG_GROUP_ID)"),
			"GROUP_GROUP_ID" => array("FIELD" => "CG2G.GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group2group CG2G ON (CG.ID = CG2G.CATALOG_GROUP_ID)"),
			"GROUP_BUY" => array("FIELD" => "CG2G.BUY", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_group2group CG2G ON (CG.ID = CG2G.CATALOG_GROUP_ID)"),

			"NAME_LANG" => array("FIELD" => "CGL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_group_lang CGL ON (CG.ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".LANGUAGE_ID."')"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group CG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
			if (!empty($arSqls["HAVING"]))
				$strSql .= " HAVING ".$arSqls["HAVING"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group CG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["HAVING"]))
			$strSql .= " HAVING ".$arSqls["HAVING"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_group CG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];
			if (!empty($arSqls["HAVING"]))
				$strSql_tmp .= " HAVING ".$arSqls["HAVING"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	
	/**
	* <p>Метод возвращает записи из таблицы связей между типами цен и группами пользователей сайта по фильтру arFilter. Метод динамичный.</p>
	*
	*
	* @param array $arrayarFilter = Array() Фильтр задается в виде ассоциативного массива, ключами в котором
	* являются названия полей, а значениями - условия на значения.<br>
	* Допустимые ключи:<br><ul> <li>CATALOG_GROUP_ID - код типа цен;</li> <li>GROUP_ID - код
	* группы пользователей;</li> <li>BUY - флаг со значениями: Y - запись о
	* разрешении пользователям данной группы покупать товары по ценам
	* данного типа, N - запись о разрешении пользователям данной группы
	* видеть цены данного типа; </li> <li>ID - код записи</li> </ul>
	*
	* @return CDBResult <p>Объект класса CDBResult, содержащий набор ассоциативных массивов с
	* ключами </p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код записи.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа
	* цен.</td> </tr> <tr> <td>GROUP_ID</td> <td>Код группы пользователей.</td> </tr> <tr>
	* <td>BUY</td> <td>Флаг со значениями: Y - запись о разрешении пользователям
	* данной группы покупать товары по ценам данного типа, N - запись о
	* разрешении пользователям данной группы видеть цены данного
	* типа.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем коды типов цен, по которым все пользователи
	* // (т.е. группа 2) могут покупать товары
	* $db_res = CCatalogGroup::GetGroupsList(array("GROUP_ID"=&gt;2, "BUY"=&gt;"Y"));
	* while ($ar_res = $db_res-&gt;Fetch())
	* {
	*    echo $ar_res["CATALOG_GROUP_ID"].", ";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__getgroupslist.cb402ee8.php
	* @author Bitrix
	*/
	public static function GetGroupsList($arFilter = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "CGG.ID", "TYPE" => "int"),
			"CATALOG_GROUP_ID" => array("FIELD" => "CGG.CATALOG_GROUP_ID", "TYPE" => "int"),
			"GROUP_ID" => array("FIELD" => "CGG.GROUP_ID", "TYPE" => "int"),
			"BUY" => array("FIELD" => "CGG.BUY", "TYPE" => "char")
		);

		$arSqls = CCatalog::PrepareSql($arFields, array(), $arFilter, false, false);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group2group CGG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	
	/**
	* <p>Метод возвращает языкозависимые названия типов цен. Метод динамичный.</p>
	*
	*
	* @param array $arrayarFilter = Array() Фильтр задается в виде ассоциативного массива, ключами в котором
	* являются названия полей, а значениями - условия на значения.<br>
	* Допустимые ключи:<br><ul> <li>ID - код записи;</li> <li>CATALOG_GROUP_ID - код типа
	* цен;</li> <li>LID - код языка;</li> <li>NAME - название типа цен в зависимости
	* от языка интерфейса. </li> </ul>
	*
	* @return CDBResult <p>Объект класса CDBResult, содержащий набор ассоциативных массивов с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код записи.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа
	* цен.</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr> <td>NAME</td> <td>Название типа
	* цен в зависимости от языка интерфейса.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/getlanglist.php
	* @author Bitrix
	*/
	public static function GetLangList($arFilter = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "CGL.ID", "TYPE" => "int"),
			"CATALOG_GROUP_ID" => array("FIELD" => "CGL.CATALOG_GROUP_ID", "TYPE" => "int"),
			"LID" => array("FIELD" => "CGL.LANG", "TYPE" => "string"),
			"LANG" => array("FIELD" => "CGL.LANG", "TYPE" => "string"),
			"NAME" => array("FIELD" => "CGL.NAME", "TYPE" => "string")
		);

		$arSqls = CCatalog::PrepareSql($arFields, array(), $arFilter, false, false);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_group_lang CGL ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}
}
?>