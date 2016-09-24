<?
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2006 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

global $MAIN_OPTIONS;
$MAIN_OPTIONS = array();

/**
 * <b>COption</b> - класс для работы с параметрами модулей, хранимых в базе данных.<br><br> Как правило управление параметрами модулей осуществляется в административном интерфейсе в настройках соответствующих модулей.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/index.php
 * @author Bitrix
 */
class CAllOption
{
	public static function err_mess()
	{
		return "<br>Class: CAllOption<br>File: ".__FILE__;
	}

	
	/**
	* <p>Возвращает строковое значение параметра <i>option_id</i>, принадлежащего модулю <i>module_id</i>. Если не установлен параметр <i>site_id</i> то делается попытка найти числовой параметр <i>option_id</i>, принадлежащий модулю <i>module_id</i> для текущего сайта. Если такого параметра нет, возвращается параметр, общий для всех сайтов. Статический метод.</p> <p>В новом ядре D7 аналог этой функции - <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/get.php" >Bitrix\Main\Config\Option::get</a>.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Идентификатор параметра.
	*
	* @param mixed $def = false Значение по умолчанию.<br>Если <i>default_value</i> не задан, то значение для
	* <i>default_value</i> будет браться из массива с именем
	* 		${<i>module_id</i>."_default_option"} заданного в файле
	* <b>/bitrix/modules/</b><i>module_id</i><b>/default_option.php</b>.
	*
	* @param string $site = false Идентификатор сайта для которого будут возвращены параметры.
	* Необязательный. 	По умолчанию - false (для текущего сайта или если не
	* установлены то общие для всех 	сайтов)
	*
	* @param bool $ExactSite = false Необязательный. По умолчанию "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // получим поле "При регистрации добавлять в группу" 
	* // из настроек главного модуля
	* $default_group = <b>COption::GetOptionString</b>("main", "new_user_registration_def_group", "2");
	* if($default_group!="")
	*     $arrGroups = explode(",",$default_group);
	* ?&gt;Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/webdev/user/11948/blog/7799/">В многосайтовой конфигурации на втором сайте сделаем e-mail НЕ уникальным при регистрации. </a></li>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2824"
	* >Параметры модуля</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/settings.php">Настройки главного модуля</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/coption/getoptionint.php">COption::GetOptionInt</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/getoptionstring.php
	* @author Bitrix
	*/
	public static function GetOptionString($module_id, $name, $def="", $site=false, $bExactSite=false)
	{
		$v = null;

		try
		{
			if ($bExactSite)
			{
				$v = \Bitrix\Main\Config\Option::getRealValue($module_id, $name, $site);
				return $v === null ? false : $v;
			}

			$v = \Bitrix\Main\Config\Option::get($module_id, $name, $def, $site);
		}
		catch (\Bitrix\Main\ArgumentNullException $e)
		{

		}

		return $v;
	}

	
	/**
	* <p>Устанавливает строковое значение параметра <i>option_id</i> для модуля <i>module_id</i>. Если указан <i>site_id</i>, параметр установится только для этого сайта и не будет влиять на аналогичный параметр другого сайта. Возвращает <i>true</i>, если операция прошла успешна, в противном случае - <i>false</i>. Статический метод.</p> <p>В новом ярде D7 имеет аналог: <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/set.php" >\Bitrix\Main\Config\Option::set</a></p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	* Длина не более 50 символов.
	*
	* @param string $name  Идентификатор параметра. Длина не более 50 символов.
	*
	* @param string $value = "" Значение параметра.          <br>        Необязательный. По умолчанию - "".
	* Максимальная сохраняемая длина значения - 2000 символов.
	*
	* @param mixed $desc = false Описание параметра.          <br>       Необязательный. По умолчанию - "false"
	* (описание отсутствует).
	*
	* @param string $site = "" Идентификатор сайта, для которого устанавливается параметр.
	* Необязательный. Если установлен <i>false</i>, то будет текущий сайт (с
	* 14.0).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // установим значение для поля 
	* // "E-Mail администратора сайта (отправитель по умолчанию)" 
	* // из настроек главного модуля
	* <b>COption::SetOptionString</b>("main","email_from","admin@site.com");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2824"
	* >Параметры модуля</a></li>     <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/settings.php">Настройки главного модуля</a> </li>    
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/coption/setoptionint.php">COption::SetOptionInt</a> </li> 
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/setoptionstring.php
	* @author Bitrix
	*/
	public static function SetOptionString($module_id, $name, $value="", $desc=false, $site="")
	{
		\Bitrix\Main\Config\Option::set($module_id, $name, $value, $site);
		return true;
	}

	
	/**
	* <p>Удаляет значение одного (<i>option_id</i>) или всех параметров модуля <i>module_id</i> из базы. Если не установлен параметр <i>site_id</i> то делается попытка найти числовой параметр <i>option_id</i>, принадлежащий модулю <i>module_id</i> для текущего сайта. Если такого параметра нет, возвращается параметр, общий для всех сайтов. Статический метод.</p> <p>В новом ядре D7 имеет аналог: <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/delete.php" >\Bitrix\Main\Config\Option::delete</a>.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name = "" Идентификатор параметра.<br>Необязательный. По умолчанию - ""
	* (удалить все значения параметров модуля).
	*
	* @param string $site = false Идентификатор сайта для которого будут возвращены параметры.
	* Необязательный. 	По умолчанию - false (для текущего сайта или если не
	* установлены то общие для всех 	сайтов)
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // удалим значение параметра "Количество результатов на одной странице" 
	* // для модуля "Веб-формы" из базы
	* <b>COption::RemoveOption</b>("form", "RESULTS_PAGEN");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2824"
	* >Параметры модуля</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/settings.php">Настройки главного модуля</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/removeoption.php
	* @author Bitrix
	*/
	public static function RemoveOption($module_id, $name="", $site=false)
	{
		$filter = array();
		if (strlen($name) > 0)
			$filter["name"] = $name;
		if (strlen($site) > 0)
			$filter["site_id"] = $site;
		\Bitrix\Main\Config\Option::delete($module_id, $filter);
	}

	
	/**
	* <p>Возвращает числовое значение параметра <i>option_id</i>, принадлежащего модулю <i>module_id</i>. Если не установлен параметр <i>site_id</i> то делается попытка найти числовой параметр <i>option_id</i>, принадлежащий модулю <i>module_id</i> для текущего сайта. Если такого параметра нет, возвращается параметр, общий для всех сайтов. Статический метод.</p>   <p>Метод - обёртка над методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/coption/getoptionstring.php">GetOptionString</a>.</p> <p>В новом ядре D7 аналог этой функции - <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/get.php" >Bitrix\Main\Config\Option::get</a>.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	* Длина не более 50 символов.
	*
	* @param string $name  Идентификатор параметра. Длина не более 50 символов.
	*
	* @param mixed $def = false Значение по умолчанию.         <br>       Если <i>default_value</i> не задан, то
	* значение для <i>default_value</i> будет браться из массива с именем
	* ${<i>module_id</i>."_default_option"} заданного в файле
	* <code>/bitrix/modules/<i>module_id</i>/default_option.php</code>.
	*
	* @param string $site = false Идентификатор сайта для которого будут возвращены параметры.
	* Необязательный. 	По умолчанию - false (для текущего сайта или если не
	* установлены то общие для всех 	сайтов)
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // получим поле "Ответственный по умолчанию" 
	* // из настроек модуля "Техподдержка"
	* $RESPONSIBLE_USER_ID = <b>COption::GetOptionInt</b>("support", "DEFAULT_RESPONSIBLE_ID");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2824"
	* >Параметры модуля</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/settings.php">Настройки главного модуля</a> </li>  
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/coption/getoptionstring.php">COption::GetOptionString</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/getoptionint.php
	* @author Bitrix
	*/
	public static function GetOptionInt($module_id, $name, $def="", $site=false)
	{
		return COption::GetOptionString($module_id, $name, $def, $site);
	}

	
	/**
	* <p>Устанавливает числовое значение параметра <i>option_id</i> для модуля <i>module_id</i>. Если указан <i>site_id</i>, параметр установится только для этого сайта и не будет влиять на аналогичный параметр другого сайта. Возвращает "true", если операция прошла успешна, в противном случае - "false". Статический метод.</p> <p>В новом ярде D7 имеет аналог: <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/option/set.php" >\Bitrix\Main\Config\Option::set</a></p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Идентификатор параметра.
	*
	* @param mixed $value = "" Значение параметра.<br>Необязательный. По умолчанию - "".
	*
	* @param mixed $desc = false Описание параметра.<br>Необязательный. По умолчанию - "false"
	* (описание отсутствует).
	*
	* @param string $site = false Идентификатор сайта, для которого устанавливается параметр.
	* Необязательный. 	По умолчанию - false (общий для всех сайтов
	* параметр).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // установим значение для поля 
	* // "Количество дополнительных параметров меню" 
	* // из настроек модуля "Управление структурой сайта"
	* <b>COption::SetOptionInt</b>("fileman", "num_menu_param", 2);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2824"
	* >Параметры модуля</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/settings.php">Настройки главного модуля</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/coption/setoptionstring.php">COption::SetOptionString</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/setoptionint.php
	* @author Bitrix
	*/
	public static function SetOptionInt($module_id, $name, $value="", $desc="", $site="")
	{
		return COption::SetOptionString($module_id, $name, IntVal($value), $desc, $site);
	}
}

global $MAIN_PAGE_OPTIONS;
$MAIN_PAGE_OPTIONS = array();

/**
 * <b>CPageOption</b> - класс для работы с <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params" >параметрами страницы</a>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/index.php
 * @author Bitrix
 */
class CAllPageOption
{
	
	/**
	* <p>Возвращает строковое значение параметра <i>page_option_id</i>, принадлежащего модулю <i>module_id</i>. Статический метод.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Произвольный идентификатор параметра страницы.
	*
	* @param mixed $def = false Значение по умолчанию.
	*
	* @param string $site = false Идентификатор сайта. Значение по умолчанию - "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $my_parameter = <b>CPageOption::GetOptionString</b>("main", "MY_PARAMETER", "Y");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params"
	* >Параметры страницы</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/getoptionint.php">CPageOption::GetOptionInt</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/getoptionstring.php
	* @author Bitrix
	*/
	public static function GetOptionString($module_id, $name, $def="", $site=false)
	{
		global $MAIN_PAGE_OPTIONS;

		if($site===false)
			$site = SITE_ID;

		if(isset($MAIN_PAGE_OPTIONS[$site][$module_id][$name]))
			return $MAIN_PAGE_OPTIONS[$site][$module_id][$name];
		elseif(isset($MAIN_PAGE_OPTIONS["-"][$module_id][$name]))
			return $MAIN_PAGE_OPTIONS["-"][$module_id][$name];
		return $def;
	}

	
	/**
	* <p>Устанавливает строковое значение параметра <i>page_option_id</i> для модуля <i>module_id</i>. Возвращает "true", если операция прошла успешна, в противном случае - "false". Нестатический метод.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Произвольный идентификатор параметра страницы.
	*
	* @param string $value = "" Значение параметра.<br>Необязательный. По умолчанию - "".
	*
	* @param mixed $desc = false Необязательный. Значение по умолчанию - "false".
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params"
	* >Параметры страницы</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/setoptionint.php">CPageOption::SetOptionInt</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/setoptionstring.php
	* @author Bitrix
	*/
	public static function SetOptionString($module_id, $name, $value="", $desc=false, $site="")
	{
		global $MAIN_PAGE_OPTIONS;

		if($site===false)
			$site = SITE_ID;
		if(strlen($site)<=0)
			$site = "-";

		$MAIN_PAGE_OPTIONS[$site][$module_id][$name] = $value;
		return true;
	}

	
	/**
	* <p>Удаляет значение одного (<i>page_option_id</i>) или всех параметров модуля <i>module_id</i> для данной страницы. Статический метод.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name = "" Произвольный идентификатор параметра
	* страницы.<br>Необязательный. По умолчанию - "" (удалить все значения
	* параметров страницы для модуля <i>module_id</i>).
	*
	* @param string $site = false Идентификатор сайта. Значение по умолчанию - "false".
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // удалим значение параметра MY_PARAMETER для текущей страницы
	* <b>CPageOption::RemoveOption</b>("main", "MY_PARAMETER");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params"
	* >Параметры страницы</a> </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/removeoption.php
	* @author Bitrix
	*/
	public static function RemoveOption($module_id, $name="", $site=false)
	{
		global $MAIN_PAGE_OPTIONS;

		if ($site === false)
		{
			foreach ($MAIN_PAGE_OPTIONS as $site => $temp)
			{
				if ($name == "")
					unset($MAIN_PAGE_OPTIONS[$site][$module_id]);
				else
					unset($MAIN_PAGE_OPTIONS[$site][$module_id][$name]);
			}
		}
		else
		{
			if ($name == "")
				unset($MAIN_PAGE_OPTIONS[$site][$module_id]);
			else
				unset($MAIN_PAGE_OPTIONS[$site][$module_id][$name]);
		}
	}

	
	/**
	* <p>Возвращает числовое значение параметра <i>page_option_id</i>, принадлежащего модулю <i>module_id</i>. Статический метод.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Произвольный идентификатор параметра страницы.
	*
	* @param mixed $def = false Значение по умолчанию.
	*
	* @param string $site = false Идентификатор сайта. Значение по умолчанию - "false".
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $my_parameter = <b>CPageOption::GetOptionInt</b>("main", "MY_PARAMETER", 21);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params"
	* >Параметры страницы</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/getoptionstring.php">CPageOption::GetOptionString</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/getoptionint.php
	* @author Bitrix
	*/
	public static function GetOptionInt($module_id, $name, $def="", $site=false)
	{
		return CPageOption::GetOptionString($module_id, $name, $def, $site);
	}

	
	/**
	* <p>Устанавливает числовое значение параметра <i>page_option_id</i> для модуля <i>module_id</i>. Возвращает "true", если операция прошла успешна, в противном случае - "false". Статический метод.</p>
	*
	*
	* @param string $module_id  <a href="http://dev.1c-bitrix.ru/api_help/main/general/identifiers.php">Идентификатор модуля</a>.
	*
	* @param string $name  Произвольный идентификатор параметра страницы.
	*
	* @param mixed $value = "" Значение параметра.<br>Необязательный. По умолчанию - "".
	*
	* @param mixed $desc = "" 
	*
	* @param string $site = false Идентификатор сайта. Значение по умолчанию - "false".
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* <b>CPageOption::SetOptionInt</b>("main", "MY_PARAMETER", 2);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params"
	* >Параметры страницы</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/setoptionstring.php">CPageOption::SetOptionString</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/setoptionint.php
	* @author Bitrix
	*/
	public static function SetOptionInt($module_id, $name, $value="", $desc="", $site="")
	{
		return CPageOption::SetOptionString($module_id, $name, IntVal($value), $desc, $site);
	}
}
?>