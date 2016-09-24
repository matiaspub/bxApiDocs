<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

// define("MODULE_NOT_FOUND", 0);
// define("MODULE_INSTALLED", 1);
// define("MODULE_DEMO", 2);
// define("MODULE_DEMO_EXPIRED", 3);


/**
 * <b>CModule</b> - класс для работы с модулями.<br><br>Все классы представляющие из себя описание конкретных модулей системы должны наследоваться от класса CModule. Классы описывающие тот или иной модуль должны иметь имя равное <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">ID модуля</a> и их описание должно располагаться в файле <code>/bitrix/modules/<i>ID модуля</i>/install/index.php</code>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/index.php
 * @author Bitrix
 */
class CModule
{
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_ID;
	var $MODULE_SORT = 10000;
	var $SHOW_SUPER_ADMIN_GROUP_RIGHTS;
	var $MODULE_GROUP_RIGHTS;
	var $PARTNER_NAME;
	var $PARTNER_URI;

	public static function AddAutoloadClasses($module, $arParams = array())
	{
		if ($module === '')
			$module = null;

		\Bitrix\Main\Loader::registerAutoLoadClasses($module, $arParams);
		return true;
	}

	public static function AutoloadClassDefined($className)
	{
		return \Bitrix\Main\Loader::isAutoLoadClassRegistered($className);
	}

	public static function RequireAutoloadClass($className)
	{
		\Bitrix\Main\Loader::autoLoad($className);
	}

	public static function _GetCache()
	{
		return \Bitrix\Main\ModuleManager::getInstalledModules();
	}

	public static function InstallDB()
	{
		return false;
	}

	public static function UnInstallDB()
	{
	}

	function InstallEvents()
	{
	}

	public static function UnInstallEvents()
	{
	}

	function InstallFiles()
	{
	}

	public static function UnInstallFiles()
	{
	}

	
	/**
	* <p>Запускает процедуру инсталляции модуля. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // если нажали кнопку "Установить" или "Деинсталлировать" то
	* if(strlen($uninstall)&gt;0 || strlen($install)&gt;0)
	* {
	*     // проверим наличие обязательного файла в каталоге модуля
	*     if(@file_exists($DOCUMENT_ROOT."/bitrix/modules/".$module_id."/install/index.php"))
	*     {
	*         include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/index.php");
	*         $obModule = new $module_id;
	*         if($obModule-&gt;IsInstalled() &amp;&amp; strlen($uninstall)&gt;0) $obModule-&gt;DoUninstall();
	*         elseif(!$obModule-&gt;IsInstalled() &amp;&amp; strlen($install)&gt;0) <b>$obModule-&gt;DoInstall</b>();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/registermodule.php">RegisterModule</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/doinstall.php
	* @author Bitrix
	*/
	function DoInstall()
	{
	}

	static public function GetModuleTasks()
	{
		return array(
			/*
			"NAME" => array(
				"LETTER" => "",
				"BINDING" => "",
				"OPERATIONS" => array(
					"NAME",
					"NAME",
				),
			),
			*/
		);
	}

	public function InstallTasks()
	{
		global $DB, $CACHE_MANAGER;

		$sqlMODULE_ID = $DB->ForSQL($this->MODULE_ID, 50);

		$arDBOperations = array();
		$rsOperations = $DB->Query("SELECT NAME FROM b_operation WHERE MODULE_ID = '$sqlMODULE_ID'");
		while($ar = $rsOperations->Fetch())
			$arDBOperations[$ar["NAME"]] = $ar["NAME"];

		$arDBTasks = array();
		$rsTasks = $DB->Query("SELECT NAME FROM b_task WHERE MODULE_ID = '$sqlMODULE_ID' AND SYS = 'Y'");
		while($ar = $rsTasks->Fetch())
			$arDBTasks[$ar["NAME"]] = $ar["NAME"];

		$arModuleTasks = $this->GetModuleTasks();
		foreach($arModuleTasks as $task_name => $arTask)
		{
			$sqlBINDING = isset($arTask["BINDING"]) && $arTask["BINDING"] <> ''? $DB->ForSQL($arTask["BINDING"], 50): 'module';
			$sqlTaskOperations = array();

			if(isset($arTask["OPERATIONS"]) && is_array($arTask["OPERATIONS"]))
			{
				foreach($arTask["OPERATIONS"] as $operation_name)
				{
					$operation_name = substr($operation_name, 0, 50);

					if(!isset($arDBOperations[$operation_name]))
					{
						$DB->Query("
							INSERT INTO b_operation
							(NAME, MODULE_ID, BINDING)
							VALUES
							('".$DB->ForSQL($operation_name)."', '$sqlMODULE_ID', '$sqlBINDING')
						", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

						$arDBOperations[$operation_name] = $operation_name;
					}

					$sqlTaskOperations[] = $DB->ForSQL($operation_name);
				}
			}

			$task_name = substr($task_name, 0, 100);
			$sqlTaskName = $DB->ForSQL($task_name);

			if(!isset($arDBTasks[$task_name]) && $task_name <> '')
			{
				$DB->Query("
					INSERT INTO b_task
					(NAME, LETTER, MODULE_ID, SYS, BINDING)
					VALUES
					('$sqlTaskName', '".$DB->ForSQL($arTask["LETTER"], 1)."', '$sqlMODULE_ID', 'Y', '$sqlBINDING')
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}

			if(!empty($sqlTaskOperations) && $task_name <> '')
			{
				$DB->Query("
					INSERT INTO b_task_operation
					(TASK_ID,OPERATION_ID)
					SELECT T.ID TASK_ID, O.ID OPERATION_ID
					FROM
						b_task T
						,b_operation O
					WHERE
						T.SYS='Y'
						AND T.NAME='$sqlTaskName'
						AND O.NAME in ('".implode("','", $sqlTaskOperations)."')
						AND O.NAME not in (
							SELECT O2.NAME
							FROM
								b_task T2
								inner join b_task_operation TO2 on TO2.TASK_ID = T2.ID
								inner join b_operation O2 on O2.ID = TO2.OPERATION_ID
							WHERE
								T2.SYS='Y'
								AND T2.NAME='$sqlTaskName'
						)
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}
		}

		if(is_object($CACHE_MANAGER))
		{
			$CACHE_MANAGER->CleanDir("b_task");
			$CACHE_MANAGER->CleanDir("b_task_operation");
		}
	}

	public function UnInstallTasks()
	{
		global $DB, $CACHE_MANAGER;

		$sqlMODULE_ID = $DB->ForSQL($this->MODULE_ID, 50);

		$DB->Query("
			DELETE FROM b_group_task
			WHERE TASK_ID IN (
				SELECT T.ID
				FROM b_task T
				WHERE T.MODULE_ID = '$sqlMODULE_ID'
			)
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$DB->Query("
			DELETE FROM b_task_operation
			WHERE TASK_ID IN (
				SELECT T.ID
				FROM b_task T
				WHERE T.MODULE_ID = '$sqlMODULE_ID')
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$DB->Query("
			DELETE FROM b_operation
			WHERE MODULE_ID = '$sqlMODULE_ID'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$DB->Query("
			DELETE FROM b_task
			WHERE MODULE_ID = '$sqlMODULE_ID'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if(is_object($CACHE_MANAGER))
		{
			$CACHE_MANAGER->CleanDir("b_task");
			$CACHE_MANAGER->CleanDir("b_task_operation");
		}
	}

	
	/**
	* <p>Определяет установлен ли модуль. Возвращает "true", если модуль установлен и "false" - в противном случае. Нестатический метод.</p> <p class="note"><b>Примечание</b>. Для использования функций и методов того или иного модуля, его необходимо предварительно подключить с помощью метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // если нажали кнопку "Установить" или "Деинсталлировать" то
	* if(strlen($uninstall)&gt;0 || strlen($install)&gt;0)
	* {
	*     // проверим наличие обязательного файла в каталоге модуля
	*     if(@file_exists($DOCUMENT_ROOT."/bitrix/modules/".$module_id."/install/index.php"))
	*     {
	*         include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/index.php");
	*         $obModule = new $module_id;
	*         if(<b>$obModule-&gt;IsInstalled()</b> &amp;&amp; strlen($uninstall)&gt;0) $obModule-&gt;DoUninstall();
	*         elseif(!<b>$obModule-&gt;IsInstalled()</b> &amp;&amp; strlen($install)&gt;0) $obModule-&gt;DoInstall();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/ismoduleinstalled.php">IsModuleInstalled</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификаторы модулей</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/isinstalled.php
	* @author Bitrix
	*/
	public function IsInstalled()
	{
		return \Bitrix\Main\ModuleManager::isModuleInstalled($this->MODULE_ID);
	}

	
	/**
	* <p>Запускает процедуру деинсталляции модуля. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // если нажали кнопку "Установить" или "Деинсталлировать" то
	* if(strlen($uninstall)&gt;0 || strlen($install)&gt;0)
	* {
	*     // проверим наличие обязательного файла в каталоге модуля
	*     if(@file_exists($DOCUMENT_ROOT."/bitrix/modules/".$module_id."/install/index.php"))
	*     {
	*         include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/index.php");
	*         $obModule = new $module_id;
	*         if($obModule-&gt;IsInstalled() &amp;&amp; strlen($uninstall)&gt;0) <b>$obModule-&gt;DoUninstall</b>();
	*         elseif(!$obModule-&gt;IsInstalled() &amp;&amp; strlen($install)&gt;0) $obModule-&gt;DoInstall();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/douninstall.php
	* @author Bitrix
	*/
	public static function DoUninstall()
	{
	}

	
	/**
	* <p>Удаляет регистрационную запись о модуле из базы данных. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function UnRegisterModule($id)
	* {
	*     $m = new CModule;
	*     $m-&gt;MODULE_ID = $id;
	*     <b>$m-&gt;Remove</b>();
	*     CAllMain::DelGroupRight($id);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/add.php">CModule::Add</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/remove.php
	* @author Bitrix
	*/
	public function Remove()
	{
		\Bitrix\Main\ModuleManager::delete($this->MODULE_ID);
	}

	
	/**
	* <p>Вставляет идентификатор модуля в таблицу <b>b_module</b>. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $m = new CModule;
	* $m-&gt;MODULE_ID = "iblock";
	* <b>$m-&gt;Add</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/registermodule.php">RegisterModule</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/remove.php">CModule::Remove</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/add.php
	* @author Bitrix
	*/
	public function Add()
	{
		\Bitrix\Main\ModuleManager::add($this->MODULE_ID);
	}

	
	/**
	* <p>Возвращает список модулей в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsInstalledModules = <b>CModule::GetList</b>();
	* while ($ar = $rsInstalledModules-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/getdropdownlist.php">CModule::GetDropDownList</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/getlist.php
	* @author Bitrix
	*/
	public static function GetList()
	{
		$result = new CDBResult;
		$result->InitFromArray(CModule::_GetCache());
		return $result;
	}

	/**
	 * Makes module classes and function available. Returns true on success.
	 *
	 * @param string $module_name
	 * @return bool
	 */
	
	/**
	* <p>Проверяет установлен ли модуль и если установлен, то подключает его (точнее подключает файл <code>/bitrix/modules/<i>ID модуля</i>/include.php</code>). Возвращает "true", если модуль установлен, иначе - "false". Статический метод.</p> <p>Аналог этого модуля в D7 - <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/includemodule.php" >\Bitrix\Main\Loader::includeModule</a>.</p>
	*
	*
	* @param string $module_name  Идентификатор модуля.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // проверим установлен ли модуль "Информационные блоки" и если да то подключим его
	* if (<b>CModule::IncludeModule</b>("iblock")):
	*     // здесь необходимо использовать метода модуля "Информационные блоки"
	*     ...
	* endif;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/ismoduleinstalled.php">IsModuleInstalled</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/isinstalled.php">CModule::IsInstalled</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификаторы
	* модулей</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php
	* @author Bitrix
	*/
	public static function IncludeModule($module_name)
	{
		return \Bitrix\Main\Loader::includeModule($module_name);
	}

	public static function IncludeModuleEx($module_name)
	{
		return \Bitrix\Main\Loader::includeSharewareModule($module_name);
	}

	public static function err_mess()
	{
		return "<br>Class: CModule;<br>File: ".__FILE__;
	}

	public static function GetDropDownList($strSqlOrder="ORDER BY ID")
	{
		global $DB;
		$err_mess = (CModule::err_mess())."<br>Function: GetDropDownList<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				ID as REFERENCE
			FROM
				b_module
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	/**
	 * @param string $moduleId
	 * @return CModule|bool
	 */
	public static function CreateModuleObject($moduleId)
	{
		$moduleId = trim($moduleId);
		$moduleId = preg_replace("/[^a-zA-Z0-9_.]+/i", "", $moduleId);
		if ($moduleId == '')
			return false;

		$path = getLocalPath("modules/".$moduleId."/install/index.php");
		if ($path === false)
			return false;

		include_once($_SERVER["DOCUMENT_ROOT"].$path);

		$className = str_replace(".", "_", $moduleId);
		if (!class_exists($className))
			return false;

		return new $className;
	}
}


/**
 * <p>Регистрация модуля в системе. Как правило регистрация модуля является неотъемлемой частью процесса <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3475" >инсталляции модуля</a>.</p>
 *
 *
 * @param mixed $stringm  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 * @param string $dule_id  
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/statistic/install/step2.php 
 * <b>RegisterModule</b>("statistic");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/doinstall.php">CModule::DoInstall</a> </li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/registermodule.php
 * @author Bitrix
 */
function RegisterModule($id)
{
	\Bitrix\Main\ModuleManager::registerModule($id);
}


/**
 * <p>Удаляет регистрационную запись, а также все настройки модуля из базы данных. Как правило удаление регистрационной записи модуля является неотъемлемой частью процесса <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3475" >деинсталляции модуля</a>.</p>
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/statistic/install/unstep2.php 
 * <b>UnRegisterModule</b>("statistic");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/registermodule.php">RegisterModule</a> </li>
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/douninstall.php">CModule::DoUninstall</a> </li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermodule.php
 * @author Bitrix
 */
function UnRegisterModule($id)
{
	\Bitrix\Main\ModuleManager::unRegisterModule($id);
}


/**
 * <p>Регистрирует произвольный обработчик <i>callback</i> события <i>event_id</i> модуля <i>from_module_id</i>. Если указан полный путь к файлу с обработчиком <i>full_path</i>, то он будет автоматически подключен перед вызовом обработчика. Вызывается на каждом хите и работает до момента окончания работы скрипта.</p> <p>Аналоги функции в новом ядре D7: <i>Bitrix\Main\EventManager::addEventHandler</i> (новый формат) и <i>Bitrix\Main\EventManager::addEventHandlerCompatible</i>.</p>
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>
 * который будет инициировать событие.
 *
 * @param string $MESSAGE_ID  Идентификатор события.
 *
 * @param mixed $callback  Название функции обработчика. Если это метод класса, то массив
 * вида Array(класс(объект), название метода).
 *
 * @param int $sort = 100 Очередность (порядок), в котором выполняется данный обработчик
 * (обработчиков данного события может быть больше
 * одного).<br>Необязательный параметр, по умолчанию равен 100.
 *
 * @param mixed $full_path = false Полный путь к файлу для подключения при возникновении события
 * перед вызовом <i>callback</i>.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // скрипт в файле /bitrix/php_interface/init.php
 * AddEventHandler("main", "OnBeforeUserLogin", Array("MyClass", "BeforeLogin"));<br>class MyClass
 * {
 *   function BeforeLogin(&amp;$arFields)
 *   {
 *         if(strtolower($arFields["LOGIN"])=="guest")
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("Пользователь с именем входа Guest не может быть авторизован.");
 *             return false;
 *         }
 *   }
 * }
 * ?&gt;Смотрите также
 * <li><a href="http://dev.1c-bitrix.ru/community/webdev/user/11948/blog/8096/">Заголовок страницы при постраничной навигации</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/community/webdev/user/11948/blog/9746/">Отправка логина/пароля при создании заказа</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/community/webdev/user/81099/blog/bitrix-i-oshibka-404/">Ошибка 404</a></li>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2825" >Связи и
 * взаимодействие модулей</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/module/registermoduledependences.php">RegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/addeventhandler.php
 * @author Bitrix
 */
function AddEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $SORT=100, $FULL_PATH = false)
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	return $eventManager->addEventHandlerCompatible($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $FULL_PATH, $SORT);
}

function RemoveEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $iEventHandlerKey)
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	return $eventManager->removeEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $iEventHandlerKey);
}


/**
 * <p>Возвращает список обработчиков события <i>event_id</i> модуля <i>module_id</i> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> <p>Аналог метода в новом ядре: <i>Bitrix\Main\EventManager::findEventHandlers</i>.</p>
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 * @param string $event_id  Идентификатор события.
 *
 * @param string $bReturnArray = false Необязательный. По умолчанию "false". Рекомендуется использовать
 * "true". В этом случае вернёт массив параметров, а не <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.
 *
 * @return CDBResult 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // проверка возможности удаления форума
 * 
 * // флаг запрещающий или разрешающий удалять форум
 * $bCanDelete = true;
 * 
 * // получим данные по всем обработчикам события "OnBeforeForumDelete"
 * // принадлежащего модулю с идентификатором "forum"
 * $rsEvents = <b>GetModuleEvents</b>("forum", "OnBeforeForumDelete");
 * while ($arEvent = $rsEvents-&gt;Fetch())
 * {
 *     // запустим на выполнение очередной обработчик события "OnBeforeForumDelete"
 *     // если функция-обработчик возвращает false, то
 *     if (ExecuteModuleEvent($arEvent, $del_id)===false)
 *     {
 *         // запрещаем удалять форум
 *         $bCanDelete = false;
 *         break;
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/executemoduleevent.php">ExecuteModuleEvent</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleevents.php
 * @author Bitrix
 */
function GetModuleEvents($MODULE_ID, $MESSAGE_ID, $bReturnArray = false)
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$arrResult = $eventManager->findEventHandlers($MODULE_ID, $MESSAGE_ID);

	foreach($arrResult as $k => $event)
	{
		$arrResult[$k]['FROM_MODULE_ID'] = $MODULE_ID;
		$arrResult[$k]['MESSAGE_ID'] = $MESSAGE_ID;
	}

	if($bReturnArray)
	{
		return $arrResult;
	}
	else
	{
		$resRS = new CDBResult;
		$resRS->InitFromArray($arrResult);
		return $resRS;
	}
}

/**
 * @param $arEvent
 * @param null $param1
 * @param null $param2
 * @param null $param3
 * @param null $param4
 * @param null $param5
 * @param null $param6
 * @param null $param7
 * @param null $param8
 * @param null $param9
 * @param null $param10
 * @return bool|mixed|null
 *
 * @deprecated
 */

/**
 * <p>Запускает обработчик события на выполнение. Возвращает то значение, которое возвращает конкретный обработчик события.</p>
 *
 *
 * @param array $event  Массив описывающий одну регистрационную запись хранящую связь
 * между событием и обработчиком этого события (подобные записи
 * хранятсяв таблице b_module_to_module). Ключи данного массива: 	<ul> <li> <b>ID</b> -
 * ID записи 		</li> <li> <b>TIMESTAMP_X</b> - время изменения записи 		</li> <li> <b>SORT</b> -
 * сортировка 		</li> <li> <b>FROM_MODULE_ID</b> - какой модуль инициализирует
 * событие 		</li> <li> <b>MESSAGE_ID</b> - идентификатор события 		</li> <li>
 * <b>TO_MODULE_ID</b> - какой модуль содержит обработчик события 		</li> <li>
 * <b>TO_CLASS</b> - какой класс содержит обработчик события 		</li> <li>
 * <b>TO_METHOD</b> - метод класса являющийся по сути обработчиком события
 * 	</li> </ul>
 *
 * @param mixed $param1 = NULL Произвольный набор значений, которые передаются в качестве
 * параметров в обработчик события.
 *
 * @param mixed $param2 = NULL 
 *
 * @param mixed $param3 = NULL 
 *
 * @param mixed $param4 = NULL 
 *
 * @param mixed $param5 = NULL 
 *
 * @param mixed $param6 = NULL 
 *
 * @param mixed $param7 = NULL 
 *
 * @param mixed $param8 = NULL 
 *
 * @param mixed $param9 = NULL 
 *
 * @param mixed $param10 = NULL 
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // проверка возможности удаления форума
 * 
 * // флаг запрещающий или разрешающий удалять форум
 * $bCanDelete = true;
 * 
 * // получим данные по всем обработчикам события "OnBeforeForumDelete"
 * // принадлежащего модулю с идентификатором "forum"
 * $rsEvents = GetModuleEvents("forum", "OnBeforeForumDelete");
 * while ($arEvent = $rsEvents-&gt;Fetch())
 * {
 *     // запустим на выполнение очередной обработчик события "OnBeforeForumDelete"
 *     // если функция-обработчик возвращает false, то
 *     if (<b>ExecuteModuleEvent</b>($arEvent, $del_id)===false)
 *     {
 *         // запрещаем удалять форум
 *         $bCanDelete = false;
 *         break;
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleevents.php">GetModuleEvents</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/executemoduleevent.php
 * @author Bitrix
 * @deprecated
 */
function ExecuteModuleEvent($arEvent, $param1=NULL, $param2=NULL, $param3=NULL, $param4=NULL, $param5=NULL, $param6=NULL, $param7=NULL, $param8=NULL, $param9=NULL, $param10=NULL)
{
	$CNT_PREDEF = 10;
	$r = true;
	if($arEvent["TO_MODULE_ID"] <> '' && $arEvent["TO_MODULE_ID"] <> 'main')
	{
		if(!CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			return null;
		$r = include_once($_SERVER["DOCUMENT_ROOT"].getLocalPath("modules/".$arEvent["TO_MODULE_ID"]."/include.php"));
	}
	elseif($arEvent["TO_PATH"] <> '' && file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]))
	{
		$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]);
	}
	elseif($arEvent["FULL_PATH"]<>"" && file_exists($arEvent["FULL_PATH"]))
	{
		$r = include_once($arEvent["FULL_PATH"]);
	}

	if(($arEvent["TO_CLASS"] == '' || $arEvent["TO_METHOD"] == '') && !is_set($arEvent, "CALLBACK"))
		return $r;

	$args = array();
	if (is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]) > 0)
	{
		foreach ($arEvent["TO_METHOD_ARG"] as $v)
			$args[] = $v;
	}

	$nArgs = func_num_args();
	for($i = 1; $i <= $CNT_PREDEF; $i++)
	{
		if($i > $nArgs)
			break;
		$args[] = &${"param".$i};
	}

	for($i = $CNT_PREDEF + 1; $i < $nArgs; $i++)
		$args[] = func_get_arg($i);

	//TODO: Возможно заменить на EventManager::getInstance()->getLastEvent();
	global $BX_MODULE_EVENT_LAST;
	$BX_MODULE_EVENT_LAST = $arEvent;

	if(is_set($arEvent, "CALLBACK"))
	{
		$resmod = call_user_func_array($arEvent["CALLBACK"], $args);
	}
	else
	{
		//php bug: http://bugs.php.net/bug.php?id=47948
		class_exists($arEvent["TO_CLASS"]);
		$resmod = call_user_func_array(array($arEvent["TO_CLASS"], $arEvent["TO_METHOD"]), $args);
	}

	return $resmod;
}


/**
 * <p>Запускает обработчик события на выполнение.</p>
 *
 *
 * @param array $arEvent  Структура данных описывающая один обработчик события. Массив
 * описаний обработчиков возвращает метод <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleevents.php">GetModuleEvents</a>
 *
 * @param $arEven $arParams = array() Перечень параметров передаваемых в обработчики события. Этот
 * перечень определяется автором события и индивидуален для
 * каждого события. Параметры могут передаваться как по ссылке так и
 * по значению. Параметры переданные по значению могут быть
 * изменены внутри обработчика. Для передачи параметра по значению
 * в массив должна быть добавлена ссылка на него.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * ExecuteModuleEventEx($arEvent, array($ID, &amp;$arFields))
 * В этом случае обработчик получит два параметра - $ID и $arFields. Значения второго он может менять, так как передан по ссылке.
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleevents.php">GetModuleEvents</a></li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/executemoduleeventex.php
 * @author Bitrix
 */
function ExecuteModuleEventEx($arEvent, $arParams = array())
{
	$r = true;

	if(
		isset($arEvent["TO_MODULE_ID"])
		&& $arEvent["TO_MODULE_ID"]<>""
		&& $arEvent["TO_MODULE_ID"]<>"main"
	)
	{
		if(!CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			return null;
	}
	elseif(
		isset($arEvent["TO_PATH"])
		&& $arEvent["TO_PATH"]<>""
		&& file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"])
	)
	{
		$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]);
	}
	elseif(
		isset($arEvent["FULL_PATH"])
		&& $arEvent["FULL_PATH"]<>""
		&& file_exists($arEvent["FULL_PATH"])
	)
	{
		$r = include_once($arEvent["FULL_PATH"]);
	}

	if(array_key_exists("CALLBACK", $arEvent))
	{
		//TODO: Возможно заменить на EventManager::getInstance()->getLastEvent();
		global $BX_MODULE_EVENT_LAST;
		$BX_MODULE_EVENT_LAST = $arEvent;

		if(isset($arEvent["TO_METHOD_ARG"]) && is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]))
			$args = array_merge($arEvent["TO_METHOD_ARG"], $arParams);
		else
			$args = $arParams;

		return call_user_func_array($arEvent["CALLBACK"], $args);
	}
	elseif($arEvent["TO_CLASS"] != "" && $arEvent["TO_METHOD"] != "")
	{
		//TODO: Возможно заменить на EventManager::getInstance()->getLastEvent();
		global $BX_MODULE_EVENT_LAST;
		$BX_MODULE_EVENT_LAST = $arEvent;

		if(is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]))
			$args = array_merge($arEvent["TO_METHOD_ARG"], $arParams);
		else
			$args = $arParams;

		//php bug: http://bugs.php.net/bug.php?id=47948
		class_exists($arEvent["TO_CLASS"]);
		return call_user_func_array(array($arEvent["TO_CLASS"], $arEvent["TO_METHOD"]), $args);
	}
	else
	{
		return $r;
	}
}


/**
 * <p>Удаляет регистрационную запись обработчика события.</p> <p>Аналог метода в новом ядре: <i>Bitrix\Main\EventManager::unRegisterEventHandler</i>.</p>
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>
 * который инициирует событие.
 *
 * @param string $MESSAGE_ID  Идентификатор события.
 *
 * @param string $to_module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>
 * содержащий функцию-обработчик события.
 *
 * @param string $to_class = "" Класс принадлежащий модулю <i>module</i>, метод которого является
 * функцией-обработчиком события.<br>Необязательный параметр. По
 * умолчанию - "".
 *
 * @param string $to_method = "" Метод класса <i>to_class</i> являющийся функцией-обработчиком
 * события.<br>Необязательный параметр. По умолчанию - "".
 *
 * @param string $TO_PATH = "" Необязательный параметр, по умолчанию пустой.
 *
 * @param array $TO_METHOD_ARG = array() Массив аргументов для функции-обработчика событий. <br>      
 * Необязательный параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * <b>UnRegisterModuleDependences</b>("main", "OnUserDelete", "forum", "CForum", "OnUserDelete");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2825" >Связи и
 * взаимодействие модулей</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/module/registermoduledependences.php">RegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermoduledependences.php
 * @author Bitrix
 */
function UnRegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $TO_PATH="", $TO_METHOD_ARG = array())
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->unRegisterEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS, $TO_METHOD, $TO_PATH, $TO_METHOD_ARG);
}


/**
 * <p>Регистрирует обработчик события. Выполняется один раз (при установке модуля) и этот  обработчик события действует до момента вызова события <b>UnRegisterModuleDependences</b>. </p> <p>Аналог функции в новом ядре: <i>Bitrix\Main\EventManager::registerEventHandler </i>.</p>
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>,
 * который будет инициировать событие.
 *
 * @param string $MESSAGE_ID  Идентификатор события.
 *
 * @param string $to_module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>,
 * содержащий функцию-обработчик события.
 *
 * @param string $to_class = "" Класс принадлежащий модулю <i>module</i>, метод которого является
 * функцией-обработчиком события.         <br>       Необязательный
 * параметр. По умолчанию - "" (будет просто подключен файл
 * /bitrix/modules/<i>to_module_id</i>/include.php).
 *
 * @param string $to_method = "" Метод класса <i>to_class</i> являющийся функцией-обработчиком события.  
 *       <br>       Необязательный параметр. По умолчанию - "" (будет просто
 * подключен файл /bitrix/modules/<i>to_module_id</i>/include.php).
 *
 * @param int $sort = 100 Очередность (порядок), в котором выполняется данный обработчик
 * (обработчиков данного события может быть больше одного).         <br>    
 *   Необязательный параметр, по умолчанию равен 100.
 *
 * @param mixed $TO_PATH = "" Необязательный параметр, по умолчанию пустой.
 *
 * @param mixed $TO_METHOD_ARG = array() Массив аргументов для функции-обработчика событий. <br>      
 * Необязательный параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>// Для того, чтобы при удалении пользователя сайта <br>// производилась соответствующая очистка данных форума, <br>// при установке форума выполняется регистрация нового <br>// обработчика события "OnUserDelete" модуля main. <br>// Этим обработчиком является метод OnUserDelete класса CForum модуля forum.<br><br><b>RegisterModuleDependences</b>("main", "OnUserDelete", "forum", "CForum", "OnUserDelete");<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2825" >Связи и
 * взаимодействие модулей</a></li>   <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermoduledependences.php">UnRegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/registermoduledependences.php
 * @author Bitrix
 */
function RegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $SORT=100, $TO_PATH="", $TO_METHOD_ARG = array())
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->registerEventHandlerCompatible($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS, $TO_METHOD, $SORT, $TO_PATH, $TO_METHOD_ARG);
}


/**
 * <p>Проверяет установлен ли модуль. Возвращает "true", если модуль установлен. Иначе - "false".</p> <p class="note"><b>Примечание</b>. Для использования функций и классов того или иного модуля, его необходимо предварительно подключить с помощью функции <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>.</p>
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 * @return bool 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * if (<b>IsModuleInstalled</b>("iblock")):
 * 	
 *     echo "Модуль информационных блоков установлен";
 * 
 * endif;
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/isinstalled.php">CModule::IsInstalled</a>
 * </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a> </li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/ismoduleinstalled.php
 * @author Bitrix
 */
function IsModuleInstalled($module_id)
{
	return \Bitrix\Main\ModuleManager::isModuleInstalled($module_id);
}


/**
 * <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">идентификатор модуля</a>, которому принадлежит файл.</p>
 *
 *
 * @param string $path  Путь к файлу лежащему в каталоге <b>/bitrix/modules/</b>.
 *
 * @return string 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * echo <b>GetModuleID</b>("/bitrix/modules/main/include.php"); // main
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleid.php
 * @author Bitrix
 */
function GetModuleID($str)
{
	$arr = explode("/",$str);
	$i = array_search("modules",$arr);
	return $arr[$i+1];
}

/**
 * Returns TRUE if version1 >= version2
 * version1 = "XX.XX.XX"
 * version2 = "XX.XX.XX"
 */

/**
 * <p>Сравнивает версии в форматах <b>XX.XX.XX</b>. Возвращает true, если первая версия, переданная в параметре <i>version1</i>, больше или равна второй версии, переданной в параметре <i>version2</i>, иначе - false.</p>
 *
 *
 * @param string $version1  Первая версия в формате "XX.XX.XX"
 *
 * @param string $version2  Вторая версия в формате "XX.XX.XX"
 *
 * @return bool 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * $ver1 = "3.0.15";
 * $ver2 = "4.0.0";
 * 
 * $res = <b>CheckVersion</b>($ver1, $ver2);
 * 
 * echo ($res) ? $ver1." &gt;= ".$ver2 : $ver1." &lt; ".$ver2; 
 * 
 * // результат: 3.0.15 &lt; 4.0.0
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/checkversion.php
 * @author Bitrix
 */
function CheckVersion($version1, $version2)
{
	$arr1 = explode(".",$version1);
	$arr2 = explode(".",$version2);
	if (intval($arr2[0])>intval($arr1[0])) return false;
	elseif (intval($arr2[0])<intval($arr1[0])) return true;
	else
	{
		if (intval($arr2[1])>intval($arr1[1])) return false;
		elseif (intval($arr2[1])<intval($arr1[1])) return true;
		else
		{
			if (intval($arr2[2])>intval($arr1[2])) return false;
			elseif (intval($arr2[2])<intval($arr1[2])) return true;
			else return true;
		}
	}
}
