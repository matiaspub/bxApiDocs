<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog_export.php");


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/index.php
 * @author Bitrix
 */
class CCatalogExport extends CAllCatalogExport
{
	
	/**
	 * <p>Метод добавляет новый профиль экспорта.</p> <p><b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения:</p> <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowFunction()</pre>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Доступные поля: <ul> <li> <b>CREATED_BY</b> - ID создавшего профиль. Если
	 * значение данного поля не передается, то оно будет взято из
	 * параметра CUser при наличии $USER и авторизованности. В противном
	 * случае значение данного поля будет выставлено в NULL;</li> <li>
	 * <b>MODIFIED_BY</b> - ID изменившего профиль. Если значение данного поля не
	 * передается, то оно будет взято из параметра CUser при наличии $USER и
	 * авторизованности. В противном случае значение данного поля будет
	 * выставлено в NULL;</li> <li> <b>TIMESTAMP_X</b> - время последнего изменения
	 * профиля в формате сайта. Значение данного поля невозможно задать
	 * вручную;</li> <li> <b>DATE_CREATE</b> - дата создания профиля в формате сайта.
	 * Значение данного поля невозможно задать вручную;</li> <li> <b>FILE_NAME</b> -
	 * имя файла профиля со скриптом, осуществляющего экспорт;</li> <li>
	 * <b>NAME</b> - название профиля экспорта;</li> <li> <b>IN_MENU</b> - [Y|N] флаг
	 * отображения профиля в административном меню;</li> <li> <b>DEFAULT_PROFILE</b> -
	 * [Y|N] признак использования профиля по умолчанию;</li> <li> <b>IN_AGENT</b> -
	 * [Y|N] флаг наличия агента, осуществляющего автоматическое
	 * выполнение профиля экспорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки
	 * профиля к утилите <i>cron</i> для автоматической периодической
	 * выгрузки (только для Unix-систем);</li> <li> <b>SETUP_VARS</b> - параметры
	 * настройки профиля в виде url-строки;</li> <li> <b>NEED_EDIT</b> - [Y|N] флаг
	 * означает неполную настройку профиля (до тех пор, пока профиль не
	 * будет отредактирован, он выполняться не будет). </li> </ul>
	 *
	 *
	 *
	 * @return mixed <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	 * ошибки.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;
		global $USER;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				if ('=LAST_USE' == $key)
				{
					if ($value == $DB->GetNowFunction())
						$arFields1['LAST_USE'] = $DB->GetNowFunction();
				}
				unset($arFields[$key]);
			}
		}

		if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
		{
			if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
				$arFields["CREATED_BY"] = intval($USER->GetID());
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = intval($USER->GetID());
		}
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);

		$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();
		$arFields1['DATE_CREATE'] = $DB->GetNowFunction();

		$arFields["IS_EXPORT"] = "Y";

		if (!CCatalogExport::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_export", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0)
			{
				$arInsert[0] .= ", ";
				$arInsert[1] .= ", ";
			}
			$arInsert[0] .= $key;
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_catalog_export(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		return $ID;
	}

	
	/**
	 * <p>Функция изменяет параметры профиля экспорта с кодом <i>ID</i> на значения из массива <i>arFields</i>. </p> <p><b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения:</p> <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowFunction()</pre>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код изменяемого профиля экспорта.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров профиля экспорта, ключами
	 * которого являются названия параметров, а значениями - новые
	 * значения. Допустимые параметры: <ul> <li> <b>MODIFIED_BY</b> - ID пользователя,
	 * изменившего профиль;</li> <li> <b>FILE_NAME</b> - имя файла профиля со
	 * скриптом, осуществляющего экспорт;</li> <li> <b>NAME</b> - название профиля
	 * экспорта;</li> <li> <b>IN_MENU</b> - [Y|N] флаг отображения профиля в
	 * административном меню;</li> <li> <b>DEFAULT_PROFILE</b> - [Y|N] признак
	 * использования профиля по умолчанию;</li> <li> <b>IN_AGENT</b> - [Y|N] флаг
	 * наличия агента, осуществляющего автоматическое выполнение
	 * профиля экспорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки профиля к
	 * утилите <i>cron</i> для автоматической периодической выгрузки (только
	 * для Unix-систем);</li> <li> <b>SETUP_VARS</b> - параметры настройки профиля в
	 * виде url-строки;</li> <li> <b>NEED_EDIT</b> - [Y|N] флаг означает неполную
	 * настройку профиля (до тех пор, пока профиль не будет
	 * отредактирован, он выполняться не будет). </li> </ul>
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i> в случае успешного изменения параметров
	 * профиля экспорта и <i>false</i> - в случае ошибки.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		global $USER;

		$ID = intval($ID);

		$boolNoUpdate = false;
		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				if ('=LAST_USE' == $key)
				{
					if ($value == $DB->GetNowFunction())
					{
						$arFields1['LAST_USE'] = $DB->GetNowFunction();
						$boolNoUpdate = true;
					}
				}
				unset($arFields[$key]);
			}
		}

		if (array_key_exists('CREATED_BY',$arFields))
			unset($arFields['CREATED_BY']);
		if (array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);

		if (!$boolNoUpdate)
		{
			if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
			{
				if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
					$arFields["MODIFIED_BY"] = intval($USER->GetID());
			}
			$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();
		}
		else
		{
			if (array_key_exists('MODIFIED_BY',$arFields))
				unset($arFields['MODIFIED_BY']);
		}
		$arFields["IS_EXPORT"] = "Y";

		if (!CCatalogExport::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_export", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql =
			"UPDATE b_catalog_export SET ".$strUpdate." WHERE ID = ".$ID." AND IS_EXPORT = 'Y'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>