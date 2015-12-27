<?

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/index.php
 * @author Bitrix
 */
class CAllCatalogImport
{
	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/add.php">CCatalogImport::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/update.php">CCatalogImport::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/add.php">CCatalogImport::Add</a>;</li> <li>
	* <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/update.php">CCatalogImport::Update</a>.</li> </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров профиля импорта. Допустимые
	* ключи: <ul> <li> <b>CREATED_BY</b> - ID пользователя, создавшего профиль;</li> <li>
	* <b>MODIFIED_BY</b> - ID пользователя, изменившего профиль;</li> <li> <b>TIMESTAMP_X</b> -
	* время последнего изменения профиля в формате сайта;</li> <li>
	* <b>DATE_CREATE</b> - дата создания профиля в формате сайта;</li> <li> <b>FILE_NAME</b> -
	* имя файла профиля со скриптом, осуществляющего импорт. Ключ
	* является обязательным, если $ACTION = 'ADD';</li> <li> <b>NAME</b> - название
	* профиля импорта. Ключ является обязательным, если $ACTION = 'ADD';</li> <li>
	* <b>IN_MENU</b> - [Y|N] флаг отображения профиля в административном меню;</li>
	* <li> <b>DEFAULT_PROFILE</b> - [Y|N] признак использования профиля по
	* умолчанию;</li> <li> <b>IN_AGENT</b> - [Y|N] флаг наличия агента,
	* осуществляющего автоматическое выполнение профиля импорта; </li>
	* <li> <b>IN_CRON</b> - [Y|N] флаг привязки профиля к утилите <i>cron</i> для
	* автоматической периодической выгрузки (только для Unix-систем);</li>
	* <li> <b>NEED_EDIT</b> - [Y|N] флаг означает неполную настройку профиля (до тех
	* пор, пока профиль не будет отредактирован, он выполняться не
	* будет). </li> </ul>
	*
	* @return bool <p>В случае корректности переданных параметров возвращает <i>true</i>,
	* иначе - <i>false</i>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/add.php">CCatalogImport::Add</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/update.php">CCatalogImport::Update</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;
		global $USER;

		$ACTION = strtoupper($ACTION);
		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		if ((is_set($arFields, "FILE_NAME") || $ACTION=="ADD") && strlen($arFields["FILE_NAME"])<=0)
			return false;
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"])<=0)
			return false;

		if ((is_set($arFields, "IN_MENU") || $ACTION=="ADD") && $arFields["IN_MENU"]!="Y")
			$arFields["IN_MENU"]="N";
		if ((is_set($arFields, "DEFAULT_PROFILE") || $ACTION=="ADD") && $arFields["DEFAULT_PROFILE"]!="Y")
			$arFields["DEFAULT_PROFILE"]="N";
		if ((is_set($arFields, "IN_AGENT") || $ACTION=="ADD") && $arFields["IN_AGENT"]!="Y")
			$arFields["IN_AGENT"]="N";
		if ((is_set($arFields, "IN_CRON") || $ACTION=="ADD") && $arFields["IN_CRON"]!="Y")
			$arFields["IN_CRON"]="N";
		if ((is_set($arFields, "NEED_EDIT") || $ACTION=="ADD") && $arFields["NEED_EDIT"] != "Y")
			$arFields["NEED_EDIT"]="N";

		$arFields["IS_EXPORT"] = "N";

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = intval($USER->GetID());
		$strDateFunction = $DB->GetNowFunction();
		$boolNoUpdate = false;
		if (isset($arFields['=LAST_USE']) && $strDateFunction == $arFields['=LAST_USE'])
		{
			$arFields['~LAST_USE'] = $strDateFunction;
			$boolNoUpdate = ('UPDATE' == $ACTION);
		}
		foreach ($arFields as $key => $value)
		{
			if (0 == strncmp($key, '=', 1))
				unset($arFields[$key]);
		}

		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);

		if ('ADD' == $ACTION)
		{
			$arFields['~TIMESTAMP_X'] = $strDateFunction;
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
					$arFields["CREATED_BY"] = $intUserID;
				if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
					$arFields["MODIFIED_BY"] = $intUserID;
			}
		}
		if ('UPDATE' == $ACTION)
		{
			if (array_key_exists('CREATED_BY', $arFields))
				unset($arFields['CREATED_BY']);
			if ($boolNoUpdate)
			{
				if (array_key_exists('MODIFIED_BY',$arFields))
					unset($arFields['MODIFIED_BY']);
			}
			else
			{
				if ($boolUserExist)
				{
					if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
						$arFields["MODIFIED_BY"] = $intUserID;
				}
				$arFields['~TIMESTAMP_X'] = $strDateFunction;
			}
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет профиль импорта с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код удаляемого профиля импорта.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_catalog_export WHERE ID = ".$ID." AND IS_EXPORT = 'N'", true, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	
	/**
	* <p>Возвращает список профилей импорта по фильтру <i>arFilter</i>, отсортированый в соответствии с <i>arOrder</i>. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array("ID"=>"ASC") Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* могут использоваться: <ul> <li> <i>NAME</i> - название профиля импорта;</li>
	* <li> <i>FILE_NAME</i> - имя файла профиля со скриптом, осуществляющего
	* импорт;</li> <li> <i>DEFAULT_PROFILE</i> - Y|N] флаг использования профиля по
	* умолчанию;</li> <li> <i>IN_MENU</i> - [Y|N] флаг отображения профиля в
	* административном меню;</li> <li> <i>LAST_USE</i> - дата и время последнего
	* выполнения профиля в формате базы;</li> <li> <i>IN_AGENT</i> -[Y|N] флаг наличия
	* агента, осуществляющего автоматическое выполнение профиля
	* импорта;</li> <li> <i>IN_CRON</i> - [Y|N] флаг привязки профиля к утилите <i>cron</i>
	* для автоматической периодической выгрузки;</li> <li> <i>NEED_EDIT</i> - [Y|N]
	* флаг означает неполную настройку профиля (до тех пор, пока
	* профиль не будет отредактирован, он выполняться не будет).</li> </ul>
	* Попытка сортировки по остальным полям профиля приведет к
	* сортировке по <i>ID</i>.<br><br> В качестве "направление_сортировки<i>X</i>"
	* могут быть значения "<i>ASC</i>" (по возрастанию) и "<i>DESC</i>" (по
	* убыванию).<br><br> Если массив сортировки имеет несколько элементов,
	* то результирующий набор сортируется последовательно по каждому
	* элементу (т.е. сначала сортируется по первому элементу, потом
	* результат сортируется по второму и т.д.).
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи профилей
	* импорта. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор]название_поля1" =&gt; "значение1",
	* "[модификатор]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимым является следующий модификатор:
	* <ul> <li> <b> !</b> - отрицание;</li> </ul> В качестве "название_поляX" может
	* стоять любое поле профиля импорта, кроме <i>SETUP_VARS</i>, <i>TIMESTAMP_X</i> и
	* <i>DATE_CREATE</i>.
	*
	* @param bool $bCount = false Если параметр равен <i>true</i>, то возвращается только количество
	* профилей, которое соответствует установленному фильтру.
	* Необязательный. По умолчанию равен <i>false</i>.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий коллекцию
	* ассоциативных массивов с ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li>
	* <b>FILE_NAME</b> - имя файла профиля со скриптом, осуществляющего
	* импорт;</li> <li> <b>NAME</b> - название профиля импорта;</li> <li> <b>IN_MENU</b> - [Y|N]
	* флаг отображения профиля в административном меню;</li> <li> <b>IN_AGENT</b>
	* -[Y|N] флаг наличия агента, осуществляющего автоматическое
	* выполнение профиля импорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки
	* профиля к утилите <i>cron</i> для автоматической периодической
	* выгрузки;</li> <li> <b>SETUP_VARS</b> - параметры настройки профиля в виде
	* url-строки;</li> <li> <b>DEFAULT_PROFILE</b> - [Y|N] флаг использования профиля по
	* умолчанию;</li> <li> <b>LAST_USE</b> - дата и время последнего выполнения
	* профиля в формате базы;</li> <li> <b>NEED_EDIT</b> - [Y|N] флаг означает неполную
	* настройку профиля (до тех пор, пока профиль не будет
	* отредактирован, он выполняться не будет); </li> <li> <b>LAST_USE_FORMAT</b> - дата
	* и время последнего использования профиля в формате сайта;</li> <li>
	* <b>CREATED_BY</b> - ID пользователя, создавшего профиль;</li> <li> <b>MODIFIED_BY</b> - ID
	* пользователя, изменившего профиль;</li> <li> <b>TIMESTAMP_X</b> - дата и время
	* последнего изменения профиля в формате сайта;</li> <li> <b>DATE_CREATE</b> -
	* дата и время создания профиля в формате сайта.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/gelist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=Array("ID"=>"ASC"), $arFilter=Array(), $bCount = false)
	{
		global $DB;
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i = 0, $intCount = count($filter_keys); $i < $intCount; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$bInvert = false;
			$key = $filter_keys[$i];
			if (substr($key,0,1) == "!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}

			switch(strtoupper($key))
			{
			case "ID":
				$arSqlSearch[] = "CE.ID ".($bInvert?"<>":"=")." ".IntVal($val)."";
				break;
			case "FILE_NAME":
				$arSqlSearch[] = "CE.FILE_NAME ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "NAME":
				$arSqlSearch[] = "CE.NAME ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "DEFAULT_PROFILE":
				$arSqlSearch[] = "CE.DEFAULT_PROFILE ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "IN_MENU":
				$arSqlSearch[] = "CE.IN_MENU ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "IN_AGENT":
				$arSqlSearch[] = "CE.IN_AGENT ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case "IN_CRON":
				$arSqlSearch[] = "CE.IN_CRON ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case 'NEED_EDIT':
				$arSqlSearch[] = "CE.NEED_EDIT ".($bInvert?"<>":"=")." '".$val."'";
				break;
			case 'CREATED_BY':
				$arSqlSearch[] = "CE.CREATED_BY ".($bInvert?"<>":"=")." '".intval($val)."'";
				break;
			case 'MODIFIED_BY':
				$arSqlSearch[] = "CE.MODIFIED_BY ".($bInvert?"<>":"=")." '".intval($val)."'";
				break;
			}
		}

		$strSqlSearch = "";
		if (!empty($arSqlSearch))
		{
			$strSqlSearch = ' AND ('.implode(') AND (', $arSqlSearch).') ';
		}

		$strSqlSelect =
			"SELECT CE.ID, CE.FILE_NAME, CE.NAME, CE.IN_MENU, CE.IN_AGENT, ".
			"	CE.IN_CRON, CE.SETUP_VARS, CE.DEFAULT_PROFILE, CE.LAST_USE, CE.NEED_EDIT, ".
			"	".$DB->DateToCharFunction("CE.LAST_USE", "FULL")." as LAST_USE_FORMAT, ".
			" CE.CREATED_BY, CE.MODIFIED_BY, ".$DB->DateToCharFunction('CE.TIMESTAMP_X', 'FULL').' as TIMESTAMP_X, '.$DB->DateToCharFunction('CE.DATE_CREATE', 'FULL').' as DATE_CREATE ';

		$strSqlFrom =
			"FROM b_catalog_export CE ";

		if ($bCount)
		{
			$strSql =
				"SELECT COUNT(CE.ID) as CNT ".
				$strSqlFrom.
				"WHERE CE.IS_EXPORT = 'N' ".
				$strSqlSearch;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch())
			{
				$iCnt = IntVal($ar_res["CNT"]);
			}
			return $iCnt;
		}

		$strSql =
			$strSqlSelect.
			$strSqlFrom.
			"WHERE CE.IS_EXPORT = 'N' ".
			$strSqlSearch;

		$arSqlOrder = array();
		$arOrderKeys = array();
		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if (!in_array($by, $arOrderKeys))
			{
				if ($by == "NAME") $arSqlOrder[] = "CE.NAME ".$order;
				elseif ($by == "FILE_NAME") $arSqlOrder[] = "CE.FILE_NAME ".$order;
				elseif ($by == "DEFAULT_PROFILE") $arSqlOrder[] = "CE.DEFAULT_PROFILE ".$order;
				elseif ($by == "IN_MENU") $arSqlOrder[] = "CE.IN_MENU ".$order;
				elseif ($by == "LAST_USE") $arSqlOrder[] = "CE.LAST_USE ".$order;
				elseif ($by == "IN_AGENT") $arSqlOrder[] = "CE.IN_AGENT ".$order;
				elseif ($by == "IN_CRON") $arSqlOrder[] = "CE.IN_CRON ".$order;
				elseif ($by == "NEED_EDIT") $arSqlOrder[] = "CE.NEED_EDIT ".$order;
				else
				{
					$by = "ID";
					if (in_array($by, $arOrderKeys))
						continue;
					$arSqlOrder[] = "CE.ID ".$order;
				}
				$arOrderKeys[] = $by;
			}
		}

		$strSqlOrder = "";
		if (!empty($arSqlOrder))
		{
			$strSqlOrder = ' ORDER BY '.implode(', ', $arSqlOrder);
		}

		$strSql .= $strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	
	/**
	* <p>Метод возвращает информацию о профиле импорта с заданным ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код записи.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров профиля с
	* ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li> <b>FILE_NAME</b> - имя файла
	* профиля со скриптом, осуществляющего импорт;</li> <li> <b>NAME</b> -
	* название профиля импортп;</li> <li> <b>IN_MENU</b> - [Y|N] флаг отображения
	* профиля в административном меню;</li> <li> <b>IN_AGENT</b> -[Y|N] флаг наличия
	* агента, осуществляющего автоматическое выполнение профиля
	* импорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки профиля к утилите <i>cron</i>
	* для автоматической периодической выгрузки;</li> <li> <b>SETUP_VARS</b> -
	* параметры настройки профиля в виде url-строки;</li> <li> <b>DEFAULT_PROFILE</b> -
	* [Y|N] флаг использования профиля по умолчанию;</li> <li> <b>LAST_USE</b> - дата
	* и время последнего выполнения профиля в формате базы;</li> <li>
	* <b>NEED_EDIT</b> - [Y|N] флаг означает неполную настройку профиля (до тех
	* пор, пока профиль не будет отредактирован, он выполняться не
	* будет); </li> <li> <b>LAST_USE_FORMAT</b> - дата и время последнего использования
	* профиля в формате сайта;</li> <li> <b>CREATED_BY</b> - ID пользователя,
	* создавшего профиль;</li> <li> <b>MODIFIED_BY</b> - ID пользователя, изменившего
	* профиль;</li> <li> <b>TIMESTAMP_X</b> - дата и время последнего изменения
	* профиля в формате сайта;</li> <li> <b>DATE_CREATE</b> - дата и время создания
	* профиля в формате сайта.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$strSql =
			"SELECT CE.ID, CE.FILE_NAME, CE.NAME, CE.IN_MENU, CE.IN_AGENT, ".
			"	CE.IN_CRON, CE.SETUP_VARS, CE.DEFAULT_PROFILE, CE.LAST_USE, CE.NEED_EDIT, ".
			"	".$DB->DateToCharFunction("CE.LAST_USE", "FULL")." as LAST_USE_FORMAT, ".
			" CE.CREATED_BY, CE.MODIFIED_BY, ".$DB->DateToCharFunction('CE.TIMESTAMP_X', 'FULL').' as TIMESTAMP_X, '.$DB->DateToCharFunction('CE.DATE_CREATE', 'FULL').' as DATE_CREATE '.
			"FROM b_catalog_export CE ".
			"WHERE CE.ID = ".intval($ID)." ".
			"	AND CE.IS_EXPORT = 'N'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return false;
	}

	
	/**
	* <p>Метод выполняет профиль <i>profile_id</i> на агенте. Метод динамичный.</p>
	*
	*
	* @param int $profile_id  Код выполняемого профиля.
	*
	* @return mixed <p>В случае успешного выполнения профиля импорта метод возвращает
	* строку для следующего вызова агента. В противном случае метод
	* вернет <i>false</i>.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/pregenerateimport.php
	* @author Bitrix
	*/
	public static function PreGenerateImport($profile_id)
	{
		global $DB;

		$profile_id = (int)$profile_id;
		if ($profile_id <= 0)
			return false;

		$ar_profile = CCatalogImport::GetByID($profile_id);
		if ((!$ar_profile) || ('Y' == $ar_profile['NEED_EDIT']))
			return false;

		$strFile = CATALOG_PATH2IMPORTS.$ar_profile["FILE_NAME"]."_run.php";
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
		{
			$strFile = CATALOG_PATH2IMPORTS_DEF.$ar_profile["FILE_NAME"]."_run.php";
			if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
			{
				CCatalogDiscountSave::Enable();
				return false;
			}
		}

		$bFirstLoadStep = true;

		if (!defined("CATALOG_LOAD_NO_STEP"))
			// define("CATALOG_LOAD_NO_STEP", true);

		$strImportErrorMessage = "";
		$strImportOKMessage = "";

		$bAllDataLoaded = true;

		$arSetupVars = array();
		$intSetupVarsCount = 0;
		if ('Y' != $ar_profile["DEFAULT_PROFILE"])
		{
			parse_str($ar_profile["SETUP_VARS"], $arSetupVars);
			if (!empty($arSetupVars) && is_array($arSetupVars))
			{
				$intSetupVarsCount = extract($arSetupVars, EXTR_SKIP);
			}
		}

		global $arCatalogAvailProdFields;
		$arCatalogAvailProdFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_ELEMENT);
		global $arCatalogAvailPriceFields;
		$arCatalogAvailPriceFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_CATALOG);
		global $arCatalogAvailValueFields;
		$arCatalogAvailValueFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_PRICE);
		global $arCatalogAvailQuantityFields;
		$arCatalogAvailQuantityFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_PRICE_EXT);
		global $arCatalogAvailGroupFields;
		$arCatalogAvailGroupFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_SECTION);

		global $defCatalogAvailProdFields;
		$defCatalogAvailProdFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_ELEMENT);
		global $defCatalogAvailPriceFields;
		$defCatalogAvailPriceFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_CATALOG);
		global $defCatalogAvailValueFields;
		$defCatalogAvailValueFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_PRICE);
		global $defCatalogAvailQuantityFields;
		$defCatalogAvailQuantityFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_PRICE_EXT);
		global $defCatalogAvailGroupFields;
		$defCatalogAvailGroupFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_SECTION);
		global $defCatalogAvailCurrencies;
		$defCatalogAvailCurrencies = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_CURRENCY);

		CCatalogDiscountSave::Disable();
		include($_SERVER["DOCUMENT_ROOT"].$strFile);
		CCatalogDiscountSave::Enable();

		CCatalogImport::Update($profile_id, array(
			"=LAST_USE" => $DB->GetNowFunction()
			)
		);

		return "CCatalogImport::PreGenerateImport(".$profile_id.");";
	}
}
?>