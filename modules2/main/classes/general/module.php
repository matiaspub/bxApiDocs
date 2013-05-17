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

class CModule
{
	private static $includedModules = array("main" => true);
	private static $includedModulesEx = array();
	private static $classes = array();
	public static $installedModules = false;

	public static $events = array();

	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_VERSION;
	var $MODULE_ID;
	var $MODULE_SORT = 10000;
	var $SHOW_SUPER_ADMIN_GROUP_RIGHTS;
	var $MODULE_GROUP_RIGHTS;

	public static function AddAutoloadClasses($module, $arParams = array())
	{
		if (!is_array($arParams) || empty($arParams))
			return false;

		$module = trim($module);

		if (defined("NO_BITRIX_AUTOLOAD") && NO_BITRIX_AUTOLOAD)
		{
			foreach ($arParams as $value)
				include_once($_SERVER["DOCUMENT_ROOT"].($module <> ''? BX_ROOT."/modules/".$module."/" : "").$value);
		}
		else
		{
			static $search  = 'QWERTYUIOPASDFGHJKLZXCVBNM';
			static $replace = 'qwertyuiopasdfghjklzxcvbnm';
			foreach ($arParams as $key => $value)
			{
				self::$classes[strtr($key, $search, $replace)] = array(
					"module" => $module,
					"file" => $value
				);
			}
		}

		return true;
	}

	public static function AutoloadClassDefined($className)
	{
		$className = trim($className);
		if ($className == '')
			return false;

		$className = strtolower($className);

		return array_key_exists($className, self::$classes);
	}

	static function RequireAutoloadClass($className)
	{
		$className = trim($className);
		if ($className == '')
			return false;

		static $search  = 'QWERTYUIOPASDFGHJKLZXCVBNM';
		static $replace = 'qwertyuiopasdfghjklzxcvbnm';

		$className = strtr($className, $search, $replace);

		if (isset(self::$classes[$className]))
		{
			if (self::$classes[$className]['module'] != '')
				$dir = BX_ROOT.'/modules/'.self::$classes[$className]['module'].'/';
			else
				$dir = '';

			require_once($_SERVER["DOCUMENT_ROOT"].$dir.self::$classes[$className]["file"]);
			return true;
		}

		return false;
	}

	function _GetCache()
	{
		global $DB, $CACHE_MANAGER;

		if (!self::$installedModules)
		{
			if($CACHE_MANAGER->Read(3600, "b_module"))
				self::$installedModules = $CACHE_MANAGER->Get("b_module");

			if(self::$installedModules === false)
			{
				self::$installedModules = array();
				$rs = $DB->Query("SELECT m.* FROM b_module m ORDER BY m.ID");
				while($ar = $rs->Fetch())
					self::$installedModules[$ar['ID']] = $ar;
				$CACHE_MANAGER->Set("b_module", self::$installedModules);
			}
		}

		return self::$installedModules;
	}

	function _GetName($arEvent)
	{
		$strName = '';
		if(array_key_exists("CALLBACK", $arEvent))
		{
			if(is_array($arEvent["CALLBACK"]))
				$strName .= (is_object($arEvent["CALLBACK"][0]) ? get_class($arEvent["CALLBACK"][0]) : $arEvent["CALLBACK"][0]).'::'.$arEvent["CALLBACK"][1];
			else
				$strName .= $arEvent["CALLBACK"];
		}
		else
		{
			$strName .= $arEvent["TO_CLASS"].'::'.$arEvent["TO_METHOD"];
		}
		if(isset($arEvent['TO_MODULE_ID']) && $arEvent['TO_MODULE_ID'] <> '')
			$strName .= ' ('.$arEvent['TO_MODULE_ID'].')';
		return $strName;
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
	 * <p>Запускает процедуру инсталляции модуля.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/registermodule.php">RegisterModule</a>
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

	public static function GetModuleTasks()
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

	public static function InstallTasks()
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

	public static function UnInstallTasks()
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
	 * <p>Определяет установлен ли модуль. Возвращает "true", если модуль установлен и "false" - в противном случае.</p> <p class="note">Для использования функций и классов того или иного модуля, его необходимо предварительно подключить с помощью функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/ismoduleinstalled.php">IsModuleInstalled</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификаторы
	 * модулей</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/isinstalled.php
	 * @author Bitrix
	 */
	public static function IsInstalled()
	{
		if (!self::$installedModules)
			CModule::_GetCache();
		return isset(self::$installedModules[$this->MODULE_ID]);
	}

	
	/**
	 * <p>Запускает процедуру деинсталляции модуля.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
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
	 * <p>Удаляет регистрационную запись о модуле из базы данных.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/add.php">CModule::Add</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/remove.php
	 * @author Bitrix
	 */
	public static function Remove()
	{
		global $DB,$CACHE_MANAGER;
		$DB->Query("DELETE FROM b_module WHERE ID='".$this->MODULE_ID."'");
		$CACHE_MANAGER->Clean("b_module");
		self::$installedModules = false;
	}

	
	/**
	 * <p>Вставляет идентификатор модуля в таблицу b_module.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $m = new CModule;
	 * $m-&gt;MODULE_ID = "iblock";
	 * <b>$m-&gt;Add</b>();
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/registermodule.php">RegisterModule</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/remove.php">CModule::Remove</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/add.php
	 * @author Bitrix
	 */
	public static function Add()
	{
		global $DB, $CACHE_MANAGER;
		$DB->Query(
			"INSERT INTO b_module(ID) ".
			"VALUES('".$this->MODULE_ID."')"
		);
		unset(self::$includedModules[$this->MODULE_ID]);
		unset(self::$includedModulesEx[$this->MODULE_ID]);
		$CACHE_MANAGER->Clean("b_module");
		self::$installedModules = false;
	}

	
	/**
	 * <p>Возвращает список модулей в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/getdropdownlist.php">CModule::GetDropDownList</a>
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
	 * <p>Проверяет установлен ли модуль и если установлен, то подключает его (точнее подключает файл <nobr><b>/bitrix/modules/</b><i>ID модуля</i><b>/include.php</b></nobr>). Возвращает "true", если модуль установлен, иначе - "false".</p>
	 *
	 *
	 *
	 *
	 * @param string $module_id  Идентификатор модуля.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // проверим установлен ли модуль "Информационные блоки" и если да то подключим его
	 * if (<b>CModule::IncludeModule</b>("iblock")):
	 *     // здесь необходимо использовать функции модуля "Информационные блоки"
	 *     ...
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/ismoduleinstalled.php">IsModuleInstalled</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/isinstalled.php">CModule::IsInstalled</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификаторы
	 * модулей</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php
	 * @author Bitrix
	 */
	public static function IncludeModule($module_name)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $DB, $MESS;

		if(defined("SM_SAFE_MODE") && SM_SAFE_MODE===true)
		{
			if(!in_array($module_name, array("main", "fileman")))
				return false;
		}

		if(isset(self::$includedModules[$module_name]))
			return self::$includedModules[$module_name];

		if (!self::$installedModules)
			CModule::_GetCache();

		if(!array_key_exists($module_name, self::$installedModules))
		{
			self::$includedModules[$module_name] = false;
			return false;
		}

		if(!file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$module_name."/include.php"))
		{
			self::$includedModules[$module_name] = false;
			return false;
		}

		$res = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$module_name."/include.php");
		if($res === false)
		{
			self::$includedModules[$module_name] = false;
			return false;
		}

		self::$includedModules[$module_name] = true;
		return true;
	}

	public static function IncludeModuleEx($module_name)
	{
		if (is_set(self::$includedModulesEx, $module_name))
			return self::$includedModulesEx[$module_name];

		$module_name_tmp = str_replace(".", "_", $module_name);

		if (CModule::IncludeModule($module_name))
		{
			if (defined($module_name_tmp."_DEMO") && constant($module_name_tmp."_DEMO") == "Y")
				self::$includedModulesEx[$module_name] = MODULE_DEMO;
			else
				self::$includedModulesEx[$module_name] = MODULE_INSTALLED;

			return self::$includedModulesEx[$module_name];
		}

		if (defined($module_name_tmp."_DEMO") && constant($module_name_tmp."_DEMO") == "Y")
		{
			self::$includedModulesEx[$module_name] = MODULE_DEMO_EXPIRED;
			return MODULE_DEMO_EXPIRED;
		}

		self::$includedModulesEx[$module_name] = MODULE_NOT_FOUND;
		return MODULE_NOT_FOUND;
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

	public static function CreateModuleObject($moduleId)
	{
		$moduleId = trim($moduleId);
		$moduleId = preg_replace("/[^a-zA-Z0-9_.]+/i", "", $moduleId);
		if ($moduleId == '')
			return false;

		$path = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$moduleId."/install/index.php";
		if (!file_exists($path))
			return false;

		include_once($path);

		$className = str_replace(".", "_", $moduleId);
		if (!class_exists($className))
			return false;

		return new $className;
	}
}


// register autoload
if (!function_exists("__autoload"))
{
	if (function_exists('spl_autoload_register'))
	{
		spl_autoload_register(array('CModule', 'RequireAutoloadClass'));
	}
	else
	{
		function __autoload($className)
		{
			CModule::RequireAutoloadClass($className);
		}
	}

	define("NO_BITRIX_AUTOLOAD", false);
}
else
{
	// define("NO_BITRIX_AUTOLOAD", true);
}


/**
 * <p>Регистрация модуля в системе. Как правило регистрация модуля является неотъемлемой частью процесса [link=89619]инсталляции модуля[/link].</p>
 *
 *
 *
 *
 * @param string $m  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 *
 *
 * @param dule_i $d  
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // файл /bitrix/modules/statistic/install/step2.php 
 * <b>RegisterModule</b>("statistic");
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/unregistermodule.php">UnRegisterModule</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/doinstall.php">CModule::DoInstall</a> </li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/registermodule.php
 * @author Bitrix
 */
function RegisterModule($id)
{
	$m = new CModule;
	$m->MODULE_ID = $id;
	$m->Add();

	foreach(GetModuleEvents("main", "OnAfterRegisterModule", true) as $arEvent)
		ExecuteModuleEventEx($arEvent, array($id));
}


/**
 * <p>Удаляет регистрационную запись, а также все настройки модуля из базы данных. Как правило удаление регистрационной записи модуля является неотъемлемой частью процесса [link=89620]деинсталляции модуля[/link].</p>
 *
 *
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // файл /bitrix/modules/statistic/install/unstep2.php 
 * <b>UnRegisterModule</b>("statistic");
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/registermodule.php">RegisterModule</a> </li>
 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/douninstall.php">CModule::DoUninstall</a> </li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermodule.php
 * @author Bitrix
 */
function UnRegisterModule($id)
{
	global $DB;

	$DB->Query("DELETE FROM b_agent WHERE MODULE_ID='".$DB->ForSQL($id)."'");
	CMain::DelGroupRight($id);

	$m = new CModule;
	$m->MODULE_ID = $id;
	$m->Remove();

	foreach(GetModuleEvents("main", "OnAfterUnRegisterModule", true) as $arEvent)
		ExecuteModuleEventEx($arEvent, array($id));
}


/**
 * <p>Регистрирует произвольный обработчик <i>callback</i> события <i>event_id</i> модуля <i>from_module_id</i>. Если указан полный путь к файлу с обработчиком <i>full_path</i>, то он будет автоматически подключен перед вызовом обработчика. Вызывается на каждом хите и работает до момента окончания работы скрипта.</p>
 *
 *
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>
 * который будет инициировать событие.
 *
 *
 *
 * @param string $event_id  Идентификатор события.
 *
 *
 *
 * @param mixed $callback  Название функции обработчика. Если это метод класса, то массив
 * вида Array(класс(объект), название метода).
 *
 *
 *
 * @param int $sort = 100 Очередность (порядок), в котором выполняется данный обработчик
 * (обработчиков данного события может быть больше
 * одного).<br>Необязательный параметр, по умолчанию равен 100.
 *
 *
 *
 * @param mixed $full_path = false Полный путь к файлу для подключения при возникновении события
 * перед вызовом <i>callback</i>.
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li>[link=89622]Связи и взаимодействие модулей[/link] </li> <li> <a
 * href="http://dev.1c-bitrix.ruapi_help/main/functions/module/registermoduledependences.php">RegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/addeventhandler.php
 * @author Bitrix
 */
function AddEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $SORT=100, $FULL_PATH = false)
{
	$arEvent = array("FROM_MODULE_ID"=>$FROM_MODULE_ID, "MESSAGE_ID"=>$MESSAGE_ID, "CALLBACK"=>$CALLBACK, "SORT"=>$SORT, "FULL_PATH"=>$FULL_PATH, "VERSION" => 1);
	$arEvent['TO_NAME'] = CModule::_GetName($arEvent);

	$FROM_MODULE_ID = strtoupper($FROM_MODULE_ID);
	$MESSAGE_ID = strtoupper($MESSAGE_ID);

	if (!isset(CModule::$events[$FROM_MODULE_ID]) || !is_array(CModule::$events[$FROM_MODULE_ID]))
		CModule::$events[$FROM_MODULE_ID] = array();

	$arEvents = &CModule::$events[$FROM_MODULE_ID];

	if (!isset($arEvents[$MESSAGE_ID]) || !is_array($arEvents[$MESSAGE_ID]))
		$arEvents[$MESSAGE_ID] = array();

	$iEventHandlerKey = count($arEvents[$MESSAGE_ID]);

	$arEvents[$MESSAGE_ID][$iEventHandlerKey] = $arEvent;

	uasort($arEvents[$MESSAGE_ID], create_function('$a, $b', 'if($a["SORT"] == $b["SORT"]) return 0; return ($a["SORT"] < $b["SORT"])? -1 : 1;'));

	if (class_exists("\\Bitrix\\Main\\EventManager"))
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		/** @noinspection PhpDeprecationInspection */
		$eventManager->addEventHandlerOld($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $FULL_PATH, $SORT);
	}

	return $iEventHandlerKey;
}

function RemoveEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $iEventHandlerKey)
{
	$FROM_MODULE_ID = strtoupper($FROM_MODULE_ID);
	$MESSAGE_ID = strtoupper($MESSAGE_ID);

	if(is_array(CModule::$events[$FROM_MODULE_ID][$MESSAGE_ID]))
	{
		if(isset(CModule::$events[$FROM_MODULE_ID][$MESSAGE_ID][$iEventHandlerKey]))
		{
			unset(CModule::$events[$FROM_MODULE_ID][$MESSAGE_ID][$iEventHandlerKey]);
			return true;
		}
	}

	return false;
}


/**
 * <p>Возвращает список обработчиков события <i>event_id</i> модуля <i>module_id</i> в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
 *
 *
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 *
 *
 * @param string $event_id  Идентификатор события.
 *
 *
 *
 * @return CDBResult 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/executemoduleevent.php">ExecuteModuleEvent</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/getmoduleevents.php
 * @author Bitrix
 */
function GetModuleEvents($MODULE_ID, $MESSAGE_ID, $bReturnArray = false)
{
	global $DB, $CACHE_MANAGER;
	static $init = false;

	if($init === false)
	{
		if($CACHE_MANAGER->Read(3600, "b_module_to_module"))
		{
			$arEvents = $CACHE_MANAGER->Get("b_module_to_module");
		}
		else
		{
			$arEvents = array();
			$rs = $DB->Query("
				SELECT
					*
				FROM
					b_module_to_module m2m
				INNER JOIN b_module m ON (m2m.TO_MODULE_ID = m.ID)
				ORDER BY SORT
			");
			while($ar = $rs->Fetch())
			{
				$ar['TO_NAME'] = CModule::_GetName($ar);
				$ar["~FROM_MODULE_ID"] = strtoupper($ar["FROM_MODULE_ID"]);
				$ar["~MESSAGE_ID"] = strtoupper($ar["MESSAGE_ID"]);
				if ($ar["TO_METHOD_ARG"] <> '')
					$ar["TO_METHOD_ARG"] = unserialize($ar["TO_METHOD_ARG"]);
				else
					$ar["TO_METHOD_ARG"] = array();
				$arEvents[] = $ar;
			}
			$CACHE_MANAGER->Set("b_module_to_module", $arEvents);
		}

		if(!is_array($arEvents))
			$arEvents = array();

		$copy_MAIN_MODULE_EVENTS = CModule::$events;

		foreach($arEvents as $ar)
		{
			if (intval($ar["VERSION"]) < 2)
				CModule::$events[$ar["~FROM_MODULE_ID"]][$ar["~MESSAGE_ID"]][] = $ar;
		}

		// need to re-sort because of AddEventHandler() calls
		foreach($copy_MAIN_MODULE_EVENTS as $module => $temp1)
			foreach($copy_MAIN_MODULE_EVENTS[$module] as $message => $temp2)
				sortByColumn(CModule::$events[$module][$message], "SORT");

		$init = true;
	}

	$MODULE_ID = strtoupper($MODULE_ID);
	$MESSAGE_ID = strtoupper($MESSAGE_ID);
	if(array_key_exists($MODULE_ID, CModule::$events) && array_key_exists($MESSAGE_ID, CModule::$events[$MODULE_ID]))
		$arrResult = CModule::$events[$MODULE_ID][$MESSAGE_ID];
	else
		$arrResult = array();

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
 * <p>Запускает обработчик события на выполнение. Возвращает то значение, которое возвращает конкретный обработчик события.</p>
 *
 *
 *
 *
 * @param array $event  Массив описывающий одну регистрационную запись хранящую связь
 * между событием и обработчиком этого события (подобные записи
 * хранятсяв таблице b_module_to_module). Ключи данного массива: <ul> <li> <b>ID</b> -
 * ID записи </li> <li> <b>TIMESTAMP_X</b> - время изменения записи </li> <li> <b>SORT</b> -
 * сортировка </li> <li> <b>FROM_MODULE_ID</b> - какой модуль инициализирует
 * событие </li> <li> <b>MESSAGE_ID</b> - идентификатор события </li> <li> <b>TO_MODULE_ID</b> -
 * какой модуль содержит обработчик события </li> <li> <b>TO_CLASS</b> - какой
 * класс содержит обработчик события </li> <li> <b>TO_METHOD</b> - метод класса
 * являющийся по сути обработчиком события </li> </ul>
 *
 *
 *
 * @param mixed $param1 = NULL Произвольный набор значений, которые передаются в качестве
 * параметров в обработчик события.
 *
 *
 *
 * @param mixed $param2 = NULL 
 *
 *
 *
 * @param mixed $param3 = NULL 
 *
 *
 *
 * @param mixed $param4 = NULL 
 *
 *
 *
 * @param mixed $param5 = NULL 
 *
 *
 *
 * @param mixed $param6 = NULL 
 *
 *
 *
 * @param mixed $param7 = NULL 
 *
 *
 *
 * @param mixed $param8 = NULL 
 *
 *
 *
 * @param mixed $param9 = NULL 
 *
 *
 *
 * @param mixed $param10 = NULL 
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/module/getmoduleevents.php">GetModuleEvents</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/executemoduleevent.php
 * @author Bitrix
 */
function ExecuteModuleEvent($arEvent, $param1=NULL, $param2=NULL, $param3=NULL, $param4=NULL, $param5=NULL, $param6=NULL, $param7=NULL, $param8=NULL, $param9=NULL, $param10=NULL)
{
	$CNT_PREDEF = 10;
	$r = true;
	if($arEvent["TO_MODULE_ID"] <> '' && $arEvent["TO_MODULE_ID"] <> 'main')
	{
		if(!CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			return null;
		$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$arEvent["TO_MODULE_ID"]."/include.php");
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

function ExecuteModuleEventEx($arEvent, $arParams = array())
{
	$r = true;

	if(
		isset($arEvent["TO_MODULE_ID"])
		&& $arEvent["TO_MODULE_ID"]<>""
		&& $arEvent["TO_MODULE_ID"]<>"main"
	)
	{
		if(CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$arEvent["TO_MODULE_ID"]."/include.php");
		else
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
		if(isset($arEvent["TO_METHOD_ARG"]) && is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]))
			$args = array_merge($arEvent["TO_METHOD_ARG"], $arParams);
		else
			$args = $arParams;

		return call_user_func_array($arEvent["CALLBACK"], $args);
	}
	elseif($arEvent["TO_CLASS"] != "" && $arEvent["TO_METHOD"] != "")
	{
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
 * <p>Удаляет регистрационную запись обработчика события.</p>
 *
 *
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>
 * который инициирует событие.
 *
 *
 *
 * @param string $event_id  Идентификатор события.
 *
 *
 *
 * @param string $to_module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>
 * содержащий функцию-обработчик события.
 *
 *
 *
 * @param string $to_class = "" Класс принадлежащий модулю <i>module</i>, метод которого является
 * функцией-обработчиком события.<br>Необязательный параметр. По
 * умолчанию - "".
 *
 *
 *
 * @param string $to_method = "" Метод класса <i>to_class</i> являющийся функцией-обработчиком
 * события.<br>Необязательный параметр. По умолчанию - "".
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * <b>UnRegisterModuleDependences</b>("main", "OnUserDelete", "forum", "CForum", "OnUserDelete");
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li>[link=89622]Связи и взаимодействие модулей[/link] </li> <li> <a
 * href="http://dev.1c-bitrix.ruapi_help/main/functions/module/registermoduledependences.php">RegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/unregistermoduledependences.php
 * @author Bitrix
 */
function UnRegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $TO_PATH="", $TO_METHOD_ARG = array())
{
	global $DB, $CACHE_MANAGER;

	$TO_METHOD_ARG = ((!is_array($TO_METHOD_ARG) || is_array($TO_METHOD_ARG) && count($TO_METHOD_ARG) <= 0) ? "" : serialize($TO_METHOD_ARG));

	$strSql = "DELETE FROM b_module_to_module ".
			"WHERE FROM_MODULE_ID='".$DB->ForSql($FROM_MODULE_ID)."'".
			"	AND MESSAGE_ID='".$DB->ForSql($MESSAGE_ID)."' ".
			"	AND TO_MODULE_ID='".$DB->ForSql($TO_MODULE_ID)."' ".
			($TO_CLASS <> ''?
				"	AND TO_CLASS='".$DB->ForSql($TO_CLASS)."' ":
				"	AND (TO_CLASS='' OR TO_CLASS IS NULL) ").
			($TO_METHOD <> ''?
				"	AND TO_METHOD='".$DB->ForSql($TO_METHOD)."'":
				"	AND (TO_METHOD='' OR TO_METHOD IS NULL) ").
			($TO_PATH <> '' && $TO_PATH !== 1/*controller disconnect correction*/?
				"	AND TO_PATH='".$DB->ForSql($TO_PATH)."'":
				"	AND (TO_PATH='' OR TO_PATH IS NULL) ").
			($TO_METHOD_ARG <> ''?
				"	AND TO_METHOD_ARG='".$DB->ForSql($TO_METHOD_ARG)."'":
				"	AND (TO_METHOD_ARG='' OR TO_METHOD_ARG IS NULL) ");
	$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	$CACHE_MANAGER->Clean("b_module_to_module");
}


/**
 * <p>Регистрирует обработчик события. Выполняется один раз (при установке модуля) и этот обработчик события действует до момента вызова события <b>UnRegisterModuleDependences</b>. </p>
 *
 *
 *
 *
 * @param string $from_module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>,
 * который будет инициировать событие.
 *
 *
 *
 * @param string $event_id  Идентификатор события.
 *
 *
 *
 * @param string $to_module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>,
 * содержащий функцию-обработчик события.
 *
 *
 *
 * @param string $to_class = "" Класс принадлежащий модулю <i>module</i>, метод которого является
 * функцией-обработчиком события. <br> Необязательный параметр. По
 * умолчанию - "" (будет просто подключен файл
 * /bitrix/modules/<i>to_module_id</i>/include.php).
 *
 *
 *
 * @param string $to_method = "" Метод класса <i>to_class</i> являющийся функцией-обработчиком события.
 * <br> Необязательный параметр. По умолчанию - "" (будет просто
 * подключен файл /bitrix/modules/<i>to_module_id</i>/include.php).
 *
 *
 *
 * @param int $sort = 100 Очередность (порядок), в котором выполняется данный обработчик
 * (обработчиков данного события может быть больше одного). <br>
 * Необязательный параметр, по умолчанию равен 100.
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>// Для того, чтобы при удалении пользователя сайта <br>// производилась соответствующая очистка данных форума, <br>// при установке форума выполняется регистрация нового <br>// обработчика события "OnUserDelete" модуля main. <br>// Этим обработчиком является метод OnUserDelete класса CForum модуля forum.<br><br><b>RegisterModuleDependences</b>("main", "OnUserDelete", "forum", "CForum", "OnUserDelete");<br>?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li>[link=89622]Связи и взаимодействие модулей[/link] </li> <li> <a
 * href="http://dev.1c-bitrix.ruapi_help/main/functions/module/unregistermoduledependences.php">UnRegisterModuleDependences</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/registermoduledependences.php
 * @author Bitrix
 */
function RegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $SORT=100, $TO_PATH="", $TO_METHOD_ARG = array())
{
	global $DB, $CACHE_MANAGER;

	$TO_METHOD_ARG = ((!is_array($TO_METHOD_ARG) || is_array($TO_METHOD_ARG) && count($TO_METHOD_ARG) <= 0) ? "" : serialize($TO_METHOD_ARG));

	$r = $DB->Query(
		"SELECT 'x' ".
		"FROM b_module_to_module ".
		"WHERE FROM_MODULE_ID='".$DB->ForSql($FROM_MODULE_ID)."'".
		"	AND MESSAGE_ID='".$DB->ForSql($MESSAGE_ID)."' ".
		"	AND TO_MODULE_ID='".$DB->ForSql($TO_MODULE_ID)."' ".
		"	AND TO_CLASS='".$DB->ForSql($TO_CLASS)."' ".
		"	AND TO_METHOD='".$DB->ForSql($TO_METHOD)."'".
		($TO_PATH == ''?
			"	AND (TO_PATH='' OR TO_PATH IS NULL)"
			:"	AND TO_PATH='".$DB->ForSql($TO_PATH)."'"
		).
		($TO_METHOD_ARG == ''?
			"	AND (TO_METHOD_ARG='' OR TO_METHOD_ARG IS NULL)"
			:"	AND TO_METHOD_ARG='".$DB->ForSql($TO_METHOD_ARG)."'"
		)
	);

	if(!$r->Fetch())
	{
		$arFields = array(
			"SORT" => intval($SORT),
			"FROM_MODULE_ID" => "'".$DB->ForSql($FROM_MODULE_ID)."'",
			"MESSAGE_ID" => "'".$DB->ForSql($MESSAGE_ID)."'",
			"TO_MODULE_ID" => "'".$DB->ForSql($TO_MODULE_ID)."'",
			"TO_CLASS" => "'".$DB->ForSql($TO_CLASS)."'",
			"TO_METHOD" => "'".$DB->ForSql($TO_METHOD)."'",
			"TO_PATH" => "'".$DB->ForSql($TO_PATH)."'",
			"TO_METHOD_ARG" => "'".$DB->ForSql($TO_METHOD_ARG)."'",
			"VERSION" => 1,
		);
		$DB->Insert("b_module_to_module",$arFields, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		$CACHE_MANAGER->Clean("b_module_to_module");
	}
}


/**
 * <p>Проверяет установлен ли модуль. Возвращает "true", если модуль установлен. Иначе - "false".</p> <p class="note">Для использования функций и классов того или иного модуля, его необходимо предварительно подключить с помощью функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>.</p>
 *
 *
 *
 *
 * @param string $module_id  <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">Идентификатор модуля</a>.
 *
 *
 *
 * @return bool 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/isinstalled.php">CModule::IsInstalled</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/includemodule.php">CModule::IncludeModule</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/module/ismoduleinstalled.php
 * @author Bitrix
 */
function IsModuleInstalled($module_id)
{
	if (!CModule::$installedModules)
		CModule::_GetCache();
	return isset(CModule::$installedModules[$module_id]);
}


/**
 * <p>Возвращает <a href="http://dev.1c-bitrix.ruapi_help/main/general/identifiers.php">идентификатор модуля</a>, которому принадлежит файл.</p>
 *
 *
 *
 *
 * @param string $path  Путь к файлу лежащему в каталоге <b>/bitrix/modules/</b>.
 *
 *
 *
 * @return string 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
 *
 *
 * @param string $version1  Первая версия в формате "XX.XX.XX"
 *
 *
 *
 * @param string $version2  Вторая версия в формате "XX.XX.XX"
 *
 *
 *
 * @return bool 
 *
 *
 * <h4>Example</h4> 
 * <pre>
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
