<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog_import.php");


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
class CCatalogImport extends CAllCatalogImport
{
	
	/**
	* <p>Метод добавляет новый профиль импорта. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения: <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowFunction()</pre> </div>
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
	* имя файла профиля со скриптом, осуществляющего импорт;</li> <li>
	* <b>NAME</b> - название профиля импорта;</li> <li> <b>IN_MENU</b> - [Y|N] флаг
	* отображения профиля в административном меню;</li> <li> <b>DEFAULT_PROFILE</b> -
	* [Y|N] признак использования профиля по умолчанию;</li> <li> <b>IN_AGENT</b> -
	* [Y|N] флаг наличия агента, осуществляющего автоматическое
	* выполнение профиля импорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки
	* профиля к утилите <i>cron</i> для автоматической периодической
	* выгрузки (только для Unix-систем);</li> <li> <b>SETUP_VARS</b> - параметры
	* настройки профиля в виде url-строки;</li> <li> <b>NEED_EDIT</b> - [Y|N] флаг
	* означает неполную настройку профиля (до тех пор, пока профиль не
	* будет отредактирован, он выполняться не будет). </li> </ul>
	*
	* @return mixed <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	* ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* Добавление предустановленного профиля csv-импорта, требующего настройки:
	* 
	* 
	* $arFields = array(  
	*    "FILE_NAME" =&gt; "csv_new",   
	*    "NAME" =&gt; "Мой импорт", 
	*    "NEED_EDIT" =&gt; "Y", 
	*    "SETUP_VARS"    =&gt; false
	* );  
	* CCatalogImport::Add($arFields);
	* 
	* Добавление профиля с указанием времени последнего использования (время может быть только текущим) и занесением в левое меню:
	* 
	* 
	* $arFields = array(  
	*    "FILE_NAME" =&gt; "csv_new", 
	*    "NAME" =&gt; "Мой второй импорт", 
	*    "=LAST_USE" =&gt; $DB-&gt;GetNowFunction,  
	*         "IN_MENU" =&gt; "Y",
	*    "SETUP_VARS" =&gt; "IBLOCK_ID=15&amp;FILE_DATE=export.csv" //  набор параметров довольно условный
	* ); 
	* CCatalogImport::Add($arFields);
	* 
	* 
	* Добавление профиля с явным указанием создавшего профиль:
	* 
	* 
	* $arFields = array(  
	*    "FILE_NAME" =&gt; "csv_new", 
	*    "NAME" =&gt; "Мой второй импорт", 
	*    "=LAST_USE" =&gt; $DB-&gt;GetNowFunction,  
	*         "IN_MENU" =&gt; "Y",
	*    "SETUP_VARS" =&gt; "IBLOCK_ID=15&amp;FILE_DATE=export.csv", //  набор параметров довольно условный 
	*    "CREATED_BY" =&gt; 15, // пользователь с ID = 15 
	*    "MODIFIED_BY" =&gt; 15 // должен совпадать с CREATED_BY
	* ); 
	* CCatalogImport::Add($arFields);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CCatalogImport::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_export", $arFields);

		$strSql = "insert into b_catalog_export(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры профиля импорта с кодом <i>ID</i> на значения из массива <i>arFields</i>. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения: <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowFunction()</pre> </div>
	*
	*
	* @param int $ID  Код изменяемого профиля импорта.
	*
	* @param array $arFields  Ассоциативный массив параметров профиля импорта, ключами
	* которого являются названия параметров, а значениями - новые
	* значения. Допустимые параметры: <ul> <li> <b>MODIFIED_BY</b> - ID пользователя,
	* изменившего профиль;</li> <li> <b>FILE_NAME</b> - имя файла профиля со
	* скриптом, осуществляющего импорт;</li> <li> <b>NAME</b> - название профиля
	* импорта;</li> <li> <b>IN_MENU</b> - [Y|N] флаг отображения профиля в
	* административном меню;</li> <li> <b>DEFAULT_PROFILE</b> - [Y|N] признак
	* использования профиля по умолчанию;</li> <li> <b>IN_AGENT</b> - [Y|N] флаг
	* наличия агента, осуществляющего автоматическое выполнение
	* профиля импорта; </li> <li> <b>IN_CRON</b> - [Y|N] флаг привязки профиля к
	* утилите <i>cron</i> для автоматической периодической выгрузки (только
	* для Unix-систем);</li> <li> <b>SETUP_VARS</b> - параметры настройки профиля в
	* виде url-строки;</li> <li> <b>NEED_EDIT</b> - [Y|N] флаг означает неполную
	* настройку профиля (до тех пор, пока профиль не будет
	* отредактирован, он выполняться не будет). </li> </ul>
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного изменения параметров
	* профиля импорта и <i>false</i> - в случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogimport/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if (!CCatalogImport::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_export", $arFields);

		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_export set ".$strUpdate." where ID = ".$ID." and IS_EXPORT = 'N'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}
}
?>