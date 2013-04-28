<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

// define('BX_SPREAD_SITES', 2);
// define('BX_SPREAD_DOMAIN', 4);

// define('BX_RESIZE_IMAGE_PROPORTIONAL_ALT', 0);
// define('BX_RESIZE_IMAGE_PROPORTIONAL', 1);
// define('BX_RESIZE_IMAGE_EXACT', 2);

global $BX_CACHE_DOCROOT;
$BX_CACHE_DOCROOT = array();
global $MODULE_PERMISSIONS;
$MODULE_PERMISSIONS = array();

abstract class CAllMain
{
	var $ma, $mapos;
	var $sDocPath2, $sDirPath, $sUriParam;
	var $sDocTitle;
	var $sDocTitleChanger = null;
	var $arPageProperties = array();
	var $arPagePropertiesChanger = array();
	var $arDirProperties = array();
	var $bDirProperties = false;
	var $sLastError;
	var $sPath2css = array();
	var $iHeaderLastCss = 0;
	var $iWorkAreaLastCss = 0;
	var $iHeaderLastJs = 0;
	var $iWorkAreaLastJs = 0;
	var $arHeadStrings = array();
	var $arHeadScripts = array();
	var $arHeadAdditionalCss = array();
	var $arHeadAdditionalScripts = array();
	var $arHeadAdditionalStrings = array();
	var $arLangJS = array();
	var $arAdditionalJS = array();
	var $bShowHeadString = false;
	var $bShowHeadScript = false;
	var $bInAjax = false;
	var $version;
	var $arAdditionalChain = array();
	var $FILE_PERMISSION_CACHE = array();
	var $arPanelButtons = array();
	var $arPanelFutureButtons = array();
	var $ShowLogout = false;
	var $ShowPanel = NULL, $PanelShowed = false;
	var $arrSPREAD_COOKIE = array();
	var $buffer_content = array();
	var $buffer_content_type = array();
	var $buffer_man = false;
	var $buffer_manual = false;
	var $auto_buffer_cleaned, $buffered = false;
	/**
	 * @var CApplicationException
	 */
	var $LAST_ERROR = false;
	var $ERROR_STACK = array();
	var $arIncludeDebug = array();
	var $aCachedComponents = array();
	var $ShowIncludeStat = false;
	var $_menu_recalc_counter = 0;
	var $__view = array();
	/** @var CEditArea */
	var $editArea = false;
	/** @var array */
	var $arComponentMatch = false;
	var $arAuthResult;

	function __construct()
	{
		$this->CMain();
	}

	public static function CMain()
	{
		global $QUERY_STRING;
		$this->sDocPath2 = GetPagePath(false, true);
		$this->sDirPath = GetDirPath($this->sDocPath2);
		$this->sUriParam = (strlen($_SERVER["QUERY_STRING"])>0) ? $_SERVER["QUERY_STRING"] : $QUERY_STRING;
	}

	public static function reinitPath()
	{
		$this->sDocPath2 = GetPagePath(false, true);
		$this->sDirPath = GetDirPath($this->sDocPath2);
	}

	
	/**
	 * <p>Возвращает путь к текущей странице относительно корня без параметров.</p> <p class="note">Если файл текущей страницы явно не определён, то определение индексного файла каталога будет проходить по алгоритму представленному в описании функции <a href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirindex.php">GetDirIndex</a>.</p>
	 *
	 *
	 *
	 *
	 * @param bool $get_index_page = null Параметр указывает, нужно ли для индексной страницы раздела
	 * возвращать путь, заканчивающийся на "index.php". Если значение
	 * параметра равно <i>true</i>, то возвращается путь с "index.php", иначе - путь,
	 * заканчивающийся на "/". Параметр добавлен в версии ядра 8.5.0. <br><br>
	 * Поведение параметра по умолчанию регулируется константой
	 * <b>BX_DISABLE_INDEX_PAGE</b>. Если значение константы <i>true</i>, то значение
	 * параметра по умолчанию get_index_page=false.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // текущая страница: /ru/?id=3&amp;s=5
	 * $page = <b>$APPLICATION-&gt;GetCurPage</b>(); // результат - /ru/index.php
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcururi.php">CMain::GetCurUri</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurdir.php">CMain::GetCurDir</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpageparam.php">CMain::GetCurPageParam</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setcurpage.php">CMain::SetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getpagepath.php">GetPagePath</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirpath.php">GetDirPath</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurpage.php
	 * @author Bitrix
	 */
	public static function GetCurPage($get_index_page=null)
	{
		if (null === $get_index_page)
		{
			if (defined('BX_DISABLE_INDEX_PAGE'))
				$get_index_page = !BX_DISABLE_INDEX_PAGE;
			else
				$get_index_page = true;
		}

		$str = $this->sDocPath2;

		if (!$get_index_page)
		{
			if (($i = strpos($str, '/index.php')) !== false)
				$str = substr($str, 0, $i).'/';
		}

		return $str;
	}

	
	/**
	 * <p>Устанавливает в объекте класса CMain текущую страницу и ее параметры.</p>
	 *
	 *
	 *
	 *
	 * @param string $page  Адрес страницы. Например, "/ru/index.php".
	 *
	 *
	 *
	 * @param mixed $params = false Строка параметров. Например, "id=2&amp;s=3&amp;t=4". Параметр
	 * необязательный. Если его не задавать, то параметры страницы в
	 * объекте класса CMain остаются без изменений.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;SetCurPage</b>("/ru/index.php", "id=2&amp;s=3");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcururi.php">CMain::GetCurUri</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpage.php">CMain::GetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpageparam.php">CMain::GetCurPageParam</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurdir.php">CMain::GetCurDir</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setcurpage.php
	 * @author Bitrix
	 */
	public static function SetCurPage($page, $param=false)
	{
		$this->sDocPath2 = GetPagePath($page);
		$this->sDirPath = GetDirPath($this->sDocPath2);
		if($param !== false)
			$this->sUriParam = $param;
	}

	
	/**
	 * <p>Возвращает путь к текущей странице относительно корня вместе с параметрами.</p> <p class="note">Если файл текущей страницы явно не определён, то определение индексного файла каталога будет проходить по алгоритму представленному в описании функции <a href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirindex.php">GetDirIndex</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $add_params = "" Строка параметров добавляемая к возвращаемому
	 * значению.<br>Необязательный. По умолчанию - "".
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // текущая страница: /ru/?id=3&amp;s=5
	 * $uri = <b>$APPLICATION-&gt;GetCurUri</b>("r=1&amp;t=2"); // результат - /ru/index.php?id=3&amp;s=5&amp;r=1&amp;t=2
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpage.php">CMain::GetCurPage</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurdir.php">CMain::GetCurDir</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpageparam.php">CMain::GetCurPageParam</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setcurpage.php">CMain::SetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getpagepath.php">GetPagePath</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirpath.php">GetDirPath</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcururi.php
	 * @author Bitrix
	 */
	public static function GetCurUri($addParam="", $get_index_page=null)
	{
		$page = $this->GetCurPage($get_index_page);
		$param = $this->GetCurParam();
		if(strlen($param)>0)
			$url = $page."?".$param.($addParam!=""? "&".$addParam: "");
		else
			$url = $page.($addParam!=""? "?".$addParam: "");
		return $url;
	}

	
	/**
	 * <p>Возвращает путь к текущей странице относительно корня c добавленными новыми и(или) удаленными текущими параметрами.</p> <p class="note">Если файл текущей страницы явно не определён, то определение индексного файла каталога будет проходить по алгоритму представленному в описании функции <a href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirindex.php">GetDirIndex</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $add_params = "" Строка с параметрами которые нужно добавить к возвращаемому
	 * значению. <br> Необязательный. По умолчанию "".
	 *
	 *
	 *
	 * @param array $remove_params = array() Массив параметров, которые необходимо удалить из URL-а страницы. <br>
	 * Необязательный. По умолчанию - пустой массив.
	 *
	 *
	 *
	 * @param bool $get_index_page = null Параметр указывает, нужно ли для индексной страницы раздела
	 * возвращать путь, заканчивающийся на "index.php". Если значение
	 * параметра равно <i>true</i>, то возвращается путь с "index.php", иначе - путь,
	 * заканчивающийся на "/". <br><br> Поведение параметра по умолчанию
	 * регулируется константой <b>BX_DISABLE_INDEX_PAGE</b>. Если значение константы
	 * <i>true</i>, то значение параметра по умолчанию get_index_page=false.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // текущая страница: /ru/?id=3&amp;s=5&amp;d=34
	 * $page = <b>$APPLICATION-&gt;GetCurPageParam</b>("id=45", array("id", "d")); 
	 * // результат - /ru/index.php?id=45&amp;s=5
	 * ?&gt;// пример формирование ссылок "Logout" и "Регистрация"
	 * 
	 * &lt;?if ($USER-&gt;IsAuthorized()):?&gt;
	 * 
	 *  &lt;a href="&lt;?echo <b>$APPLICATION-&gt;GetCurPageParam</b>("logout=yes", array(
	 *      "login",
	 *      "logout",
	 *      "register",
	 *      "forgot_password",
	 *      "change_password"));?&gt;"&gt;Закончить сеанс (logout)&lt;/a&gt;
	 * 
	 * &lt;?else:?&gt;
	 *  
	 *  &lt;a href="&lt;?echo <b>$APPLICATION-&gt;GetCurPageParam</b>("register=yes", array(
	 *      "login",
	 *      "logout",
	 *      "forgot_password",
	 *      "change_password"));?&gt;"&gt;Регистрация&lt;/a&gt;
	 * 
	 * &lt;?endif;?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/other/deleteparam.php">DeleteParam</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpage.php">CMain::GetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcururi.php">CMain::GetCurUri</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurdir.php">CMain::GetCurDir</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getpagepath.php">GetPagePath</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirpath.php">GetDirPath</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurpageparam.php
	 * @author Bitrix
	 */
	public static function GetCurPageParam($strParam="", $arParamKill=array(), $get_index_page=null)
	{
		$sUrlPath = $this->GetCurPage($get_index_page);
		$strNavQueryString = DeleteParam($arParamKill);
		if($strNavQueryString <> "" && $strParam <> "")
			$strNavQueryString = "&".$strNavQueryString;
		if($strNavQueryString == "" && $strParam == "")
			return $sUrlPath;
		else
			return $sUrlPath."?".$strParam.$strNavQueryString;
	}

	public static function GetCurParam()
	{
		return $this->sUriParam;
	}

	
	/**
	 * <p>Возвращает каталог текущей страницы относительно корня.</p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // текущая страница: /ru/support/index.php?id=3&amp;s=5
	 * global $APPLICATION;
	 * $dir = <b>$APPLICATION-&gt;GetCurDir</b>();
	 * // в $dir будет значение "/ru/support/"
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcururi.php">CMain::GetCurUri</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpage.php">CMain::GetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcurpageparam.php">CMain::GetCurPageParam</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setcurpage.php">CMain::SetCurPage</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getdirpath.php">GetDirPath</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/getpagepath.php">GetPagePath</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurdir.php
	 * @author Bitrix
	 */
	public static function GetCurDir()
	{
		return $this->sDirPath;
	}

	
	/**
	 * <p>Ищет файл с заданным именем последовательно вверх по иерархии папок. Если файл найден - возвращает путь к найденному файлу относительно корня, в противном случае возвращает "false".</p>
	 *
	 *
	 *
	 *
	 * @param string $file  Имя файла.
	 *
	 *
	 *
	 * @param mixed $dir  Путь к разделу с которого нужно начинать поиск файла. Если "false", то
	 * поиск будет начинаться с текущего раздела.<br>Необязателен. По
	 * умолчанию - "false".
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // ищем файл params.php вверх по иерархии разделов начиная с текущего каталога
	 * // если файл найден то подключаем его как PHP код
	 * include($_SERVER["DOCUMENT_ROOT"].<b>$APPLICATION-&gt;GetFileRecursive</b>("params.php"));
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getfilerecursive.php
	 * @author Bitrix
	 */
	public static function GetFileRecursive($strFileName, $strDir=false)
	{
		if($strDir === false)
			$strDir = $this->GetCurDir();

		$io = CBXVirtualIo::GetInstance();
		$fn = $io->CombinePath("/", $strDir, $strFileName);

		$p = false;
		while(!$io->FileExists($io->RelativeToAbsolutePath($fn)))
		{
			$p = bxstrrpos($strDir, "/");
			if($p === false)
				break;
			$strDir = substr($strDir, 0, $p);
			$fn = $io->CombinePath("/", $strDir, $strFileName);
		}
		if($p === false)
			return false;

		return $fn;
	}

	
	/**
	 * <p>Подключает скрипт с административным прологом и эпилогом.</p>
	 *
	 *
	 *
	 *
	 * @param string $title  Заголовок страницы.
	 *
	 *
	 *
	 * @param string $abs_path  Абсолютный путь к подключаемому файлу.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * function DoInstall()
	 * {
	 *     global $DB, $APPLICATION, $step;
	 *     $FORM_RIGHT = $APPLICATION-&gt;GetGroupRight("form");
	 *     if ($FORM_RIGHT=="W")
	 *     {
	 *         $step = IntVal($step);
	 *         if($step&lt;2)
	 *             <b>$APPLICATION-&gt;IncludeAdminFile</b>(GetMessage("FORM_INSTALL_TITLE"),
	 *             $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/install/step1.php");
	 *         elseif($step==2)
	 *             <b>$APPLICATION-&gt;IncludeAdminFile</b>(GetMessage("FORM_INSTALL_TITLE"),
	 *             $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/install/step2.php");
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li>[link=89618]Описание модуля[/link] </li></ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/includeadminfile.php
	 * @author Bitrix
	 */
	public static function IncludeAdminFile($strTitle, $filepath)
	{
		//define all global vars
		$keys = array_keys($GLOBALS);
		$keys_count = count($keys);
		for($i=0; $i<$keys_count; $i++)
			if($keys[$i]!="i" && $keys[$i]!="GLOBALS" && $keys[$i]!="strTitle" && $keys[$i]!="filepath")
				global ${$keys[$i]};

		//title
		/** @global CMain $APPLICATION */
		$APPLICATION->SetTitle($strTitle);

		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
		include($filepath);
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
		die();
	}

	public static function SetAuthResult($arAuthResult)
	{
		$this->arAuthResult = $arAuthResult;
	}

	
	/**
	 * <p>Функция подключает ряд компонентов в зависимости от параметров пришедших на страницу: </p> <table class="tnormal" width="100%"><tbody> <tr> <th width="25%">Параметр</th> <th width="20%">Значение</th> <th width="30%">Название компонента</th> <th>Путь к компоненту</th> </tr> <tr> <td>forgot_password</td> <td>yes</td> <td>"Форма отсылки забытого пароля"</td> <td>main/auth/forgot_password.php</td> </tr> <tr> <td>change_password</td> <td>yes</td> <td>"Форма смены забытого пароля"</td> <td>main/auth/change_password.php</td> </tr> <tr> <td>register</td> <td>yes</td> <td>"Форма регистрации"</td> <td>main/auth/registration.php</td> </tr> <tr> <td>authorize_registration</td> <td>yes</td> <td>"Форма авторизации и регистрации"</td> <td>main/auth/authorize_registration.php</td> </tr> </tbody></table> <br> Если не указан ни один из параметров, то по умолчанию функция подключит компонент "Форма авторизации" (main/auth/authorize.php). <p class="note">После вывода соответствующего компонента функция завершает выполнение страницы.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $message  main/auth/forgot_password.php
	 *
	 *
	 *
	 * @param bool $show_header = true main/auth/change_password.php
	 *
	 *
	 *
	 * @param bool $show_footer = true main/auth/registration.php
	 *
	 *
	 *
	 * @param string $not_show_links = "N" main/auth/authorize_registration.php
	 *
	 *
	 *
	 * @param bool $do_die = true 
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // определим право чтения на файл "/download/document.doc" у текущего пользователя
	 * $FILE_PERM = $APPLICATION-&gt;GetFileAccessPermission("/download/document.doc");
	 * $FILE_PERM = (strlen($FILE_PERM)&gt;0 ? $FILE_PERM : "D");
	 * // если право чтения нет, то выводем форму авторизации
	 * if($FILE_PERM &lt; "R") <b>$APPLICATION-&gt;AuthForm</b>("У вас нет права доступа к данному файлу.");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89637]Компоненты[/link] </li> <li> <a
	 * href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права доступа</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/functions/other/showmessage.php">ShowMessage</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/authform.php
	 * @author Bitrix
	 */
	public static function AuthForm($mess, $show_prolog=true, $show_epilog=true, $not_show_links="N", $do_die=true)
	{
		$excl = array("excl"=>1, "key"=>1, "GLOBALS"=>1, "mess"=>1, "show_epilog"=>1, "not_show_links"=>1, "do_die"=>1);
		foreach($GLOBALS as $key => $value)
			if(!array_key_exists($key , $excl))
				global ${$key};

		if(substr($this->GetCurDir(), 0, strlen(BX_ROOT."/admin/")) == BX_ROOT."/admin/" || (defined("ADMIN_SECTION") && ADMIN_SECTION===true))
			$isAdmin = "_admin";
		else
			$isAdmin = "";

		if(isset($this->arAuthResult) && $this->arAuthResult !== true && (is_array($this->arAuthResult) || strlen($this->arAuthResult)>0))
			$arAuthResult = $this->arAuthResult;
		else
			$arAuthResult = $mess;

		/** @global CMain $APPLICATION */
		global $APPLICATION, $forgot_password, $change_password, $register, $confirm_registration, $authorize_registration;

		//page title
		$APPLICATION->SetTitle(GetMessage("AUTH_TITLE"));

		//last login from cookie
		$last_login = ${COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_LOGIN"};

		$comp_name = "";
		if($forgot_password=="yes")
		{
			//pass request form
			$APPLICATION->SetTitle(GetMessage("AUTH_TITLE_SEND_PASSWORD"));
			$comp_name = "system.auth.forgotpasswd";
			$inc_file = "forgot_password";
		}
		elseif($change_password=="yes")
		{
			//pass change form
			$APPLICATION->SetTitle(GetMessage("AUTH_TITLE_CHANGE_PASSWORD"));
			$comp_name = "system.auth.changepasswd";
			$inc_file = "change_password";
		}
		elseif($register=="yes" && $isAdmin==""	&& COption::GetOptionString("main", "new_user_registration", "N")=="Y")
		{
			//registration form
			$APPLICATION->SetTitle(GetMessage("AUTH_TITLE_REGISTER"));
			$comp_name = "system.auth.registration";
			$inc_file = "registration";
		}
		elseif(($confirm_registration === "yes") && ($isAdmin === "") && (COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") === "Y"))
		{
			//confirm registartion
			$APPLICATION->SetTitle(GetMessage("AUTH_TITLE_CONFIRM"));
			$comp_name = "system.auth.confirmation";
			$inc_file = "confirmation";
		}
		elseif($authorize_registration=="yes" && $isAdmin=="")
		{
			//combined auth and reg form
			$inc_file = "authorize_registration";
		}
		else
		{
			//auth form
			$comp_name = "system.auth.authorize";
			$inc_file = "authorize";
		}

		if($show_prolog)
		{
			CMain::PrologActions();

			// define("BX_AUTH_FORM", true);
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog".$isAdmin. "_after.php");
		}

		if($isAdmin == "")
		{
			// form by Components 2.0
			if(COption::GetOptionString("main", "auth_comp2", "N") == "Y" && $comp_name <> "")
			{
				$this->IncludeComponent("bitrix:".$comp_name, "", array(
					"AUTH_RESULT" => $arAuthResult,
					"NOT_SHOW_LINKS" => $not_show_links,
				));
			}
			else
			{
				$this->IncludeFile("main/auth/".$inc_file.".php", array("last_login"=>$last_login, "arAuthResult"=>$arAuthResult, "not_show_links" => $not_show_links));
			}
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/wrapper.php");
		}

		if($show_epilog)
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog".$isAdmin.".php");

		if($do_die)
			die();
	}

	public static function ShowAuthForm($message)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$APPLICATION->AuthForm($message, false, false, "N", false);
	}

	public static function NeedCAPTHAForLogin($login)
	{
		//When last login was failed then ask for CAPTCHA
		if(isset($_SESSION["BX_LOGIN_NEED_CAPTCHA"]) && $_SESSION["BX_LOGIN_NEED_CAPTCHA"])
		{
			return true;
		}

		//This is local cache. May save one query.
		$USER_ATTEMPTS = false;

		//Check if SESSION cache for POLICY_ATTEMPTS is actual for given login
		if(
			!array_key_exists("BX_LOGIN_NEED_CAPTCHA_LOGIN", $_SESSION)
			|| $_SESSION["BX_LOGIN_NEED_CAPTCHA_LOGIN"]["LOGIN"] !== $login
		)
		{
			$POLICY_ATTEMPTS = 0;
			if($login <> '')
			{
				$rsUser = CUser::GetList(($o='LOGIN'), ($b='DESC'), array("LOGIN_EQUAL_EXACT" => $login), array('FIELDS' => array('ID', 'LOGIN', 'LOGIN_ATTEMPTS')));
				$arUser = $rsUser->Fetch();
				if($arUser)
				{
					$arPolicy = CUser::GetGroupPolicy($arUser["ID"]);
					$POLICY_ATTEMPTS = intval($arPolicy["LOGIN_ATTEMPTS"]);
					$USER_ATTEMPTS = intval($arUser["LOGIN_ATTEMPTS"]);
				}
			}
			$_SESSION["BX_LOGIN_NEED_CAPTCHA_LOGIN"] = array(
				"LOGIN" => $login,
				"POLICY_ATTEMPTS" => $POLICY_ATTEMPTS,
			);
		}

		//For users who had sucsessful login and if policy is set
		//check for CAPTCHA display
		if(
			$login <> ''
			&& $_SESSION["BX_LOGIN_NEED_CAPTCHA_LOGIN"]["POLICY_ATTEMPTS"] > 0
		)
		{
			//We need to know how many attempts user made
			if($USER_ATTEMPTS === false)
			{
				$rsUser = CUser::GetList(($o='LOGIN'), ($b='DESC'), array("LOGIN_EQUAL_EXACT" => $login), array('FIELDS' => array('ID', 'LOGIN', 'LOGIN_ATTEMPTS')));
				$arUser = $rsUser->Fetch();
				if($arUser)
					$USER_ATTEMPTS = intval($arUser["LOGIN_ATTEMPTS"]);
				else
					$USER_ATTEMPTS = 0;
			}
			//When user login attempts exceeding the policy we'll show the CAPTCHA
			if($USER_ATTEMPTS >= $_SESSION["BX_LOGIN_NEED_CAPTCHA_LOGIN"]["POLICY_ATTEMPTS"])
				return true;
		}

		return false;
	}

	
	/**
	 * <p>Возвращает HTML-код для отображения меню заданного типа. В отличии от функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtmlex.php">CMain::GetMenuHtmlEx</a> шаблон меню будет подключаться на каждый пункт меню.</p>
	 *
	 *
	 *
	 *
	 * @param string $type = "left" Тип меню.<br>Необязателен. По умолчанию "left".
	 *
	 *
	 *
	 * @param bool $use_ext = false Если значение - "true", то для формирования массива меню, помимо
	 * файлов <nobr><b>.</b><i>тип меню</i><b>.menu.php</b></nobr> будут также подключаться
	 * файлы с именами вида <nobr><b>.</b><i>тип меню</i><b>.menu_ext.php</b></nobr>. В которых
	 * вы можете манипулировать массивом меню <b>$aMenuLinks</b> произвольно, по
	 * вашему усмотрению (например, дополнять пункты меню значениями из
	 * инфо-блоков).<br>Необязателен. По умолчанию - "false".
	 *
	 *
	 *
	 * @param mixed $template_path = false Путь относительно корня к шаблону меню. <br>Необязателен. По
	 * умолчанию - "false", что означает искать по алгоритму указанному на
	 * странице <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3254"
	 * >Построение и показ меню</a>.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Каталог для которого будет строится меню.<br>Необязателен. По
	 * умолчанию - "false", что означает - текущий каталог.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Вывод меню в файле /bitrix/templates/demo/header.php:
	 * &lt;?
	 * echo <b>$APPLICATION-&gt;GetMenuHtml</b>("left", true);
	 * ?&gt;Файл /.left.menu.php:
	 * &lt;?
	 * // основной файл меню
	 * // данный файл может редактироваться в редакторе меню
	 * $aMenuLinks = Array(
	 * );
	 * ?&gt;Файл /.left.menu_ext.php:
	 * &lt;?
	 * // дополнительный файл меню
	 * // добавляет в основной массив меню разделы каталога телефонов
	 * if (CModule::IncludeModule("iblock")):
	 * 
	 *     $IBLOCK_TYPE = "catalog";   // тип инфо-блока
	 *     $IBLOCK_ID = 21;            // ID инфо-блока
	 *     $CACHE_TIME = 3600;         // кэшируем на 1 час
	 * 
	 *     $aMenuLinksNew = array();
	 * 
	 *     // создаем объект для кэширования меню
	 *     $CACHE_ID = __FILE__.$IBLOCK_ID;
	 *     $obMenuCache = new CPHPCache;
	 *     // если массив закэширован то
	 *     if($obMenuCache-&gt;InitCache($CACHE_TIME, $CACHE_ID, "/"))
	 *     {
	 *         // берем данные из кэша
	 *         $arVars = $obMenuCache-&gt;GetVars();
	 *         $aMenuLinksNew = $arVars["aMenuLinksNew"];
	 *     }
	 *     else
	 *     {
	 *         // иначе собираем разделы
	 *         $rsSections = GetIBlockSectionList($IBLOCK_ID, false, array("SORT" =&gt; "ASC", "ID" =&gt; "ASC"), false, array("ACTIVE"=&gt;"Y"));
	 *         while ($arSection = $rsSections-&gt;Fetch())
	 *         {
	 *             $arrAddLinks = array(SITE_DIR."catalog/phone/element.php?SECTION_ID=".$arSection["ID"]);
	 *             // пройдемся по элементам раздела
	 *             if ($rsElements = GetIBlockElementListEx($IBLOCK_TYPE, false, false, array(), false, array("ACTIVE" =&gt; "Y", "IBLOCK_ID" =&gt; $IBLOCK_ID, "SECTION_ID" =&gt; $arSection["ID"]), array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL")))
	 *             {
	 *                 while ($arElement = $rsElements-&gt;GetNext()) $arrAddLinks[] = $arElement["DETAIL_PAGE_URL"];
	 *             }
	 *             $aMenuLinksNew[] = array(
	 *                 $arSection["NAME"], 
	 *                 SITE_DIR."catalog/phone/section.php?SECTION_ID=".$arSection["ID"],
	 *                 $arrAddLinks);        
	 *         }
	 *     }
	 *     // сохраняем данные в кэше
	 *     if($obMenuCache-&gt;StartDataCache())
	 *     {
	 *         $obMenuCache-&gt;EndDataCache(Array("aMenuLinksNew" =&gt; $aMenuLinksNew));
	 *     }
	 * 
	 *     // объединяем основной массив меню с дополнительным
	 *     $aMenuLinks = array_merge($aMenuLinksNew, $aMenuLinks);
	 * 
	 * endif;
	 * ?&gt;Шаблон меню /bitrix/templates/demo/left.menu_template.php:
	 * &lt;?
	 * // This file is the template for one menu item iteration
	 * 
	 * // Set item mark: selected folder, folder, page
	 * if ($ITEM_TYPE=="D")
	 * {
	 *     if ($SELECTED)
	 *         $strDir = "&lt;td width='0%' bgcolor='#A0C4E0' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_folder_open.gif' width='17' border='0'&gt;&lt;/td&gt;";
	 *     else
	 *         $strDir = "&lt;td width='0%' bgcolor='#CCDFEE' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_folder.gif' width='17' border='0'&gt;&lt;/td&gt;";
	 * }
	 * else
	 * {
	 *     if ($SELECTED)
	 *     {
	 *         $strDir = "&lt;td width='0%' bgcolor='#A0C4E0' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_bullet.gif' width='17' border='0'&gt;&lt;/td&gt;";
	 *         $strDir_d = "&lt;td width='0%' bgcolor='#A0C4E0' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_bullet_d.gif' width='17' border='0' alt='Закрытый раздел'&gt;&lt;/td&gt;";
	 *     }
	 *     else
	 *     {
	 *         $strDir = "&lt;td width='0%' bgcolor='#CCDFEE' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_bullet.gif' width='17' border='0'&gt;&lt;/td&gt;";
	 *         $strDir_d = "&lt;td width='0%' bgcolor='#CCDFEE' valign='middle' align='center'&gt;&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/left_bullet_d.gif' width='17' border='0' alt='Закрытый раздел'&gt;&lt;/td&gt;";
	 *     }
	 * }
	 * 
	 * // if $SELECTED then this item is current (active) item
	 * if ($SELECTED)
	 *     $strtext = "leftmenuact";
	 * else
	 *     $strtext = "leftmenu";
	 *     
	 * //if $PARAMS["SEPARATOR"]=="Y" this item should be shown with different style applied
	 * 
	 * if ($PARAMS["SEPARATOR"]=="Y")
	 * {
	 *     $strstyle = " style='background-color: #D5ECE6; border-top: 1px solid #A6D0D7; border-bottom: 1px solid #A6D0D7; padding:8;'";
	 *     $strDir = "&lt;img height='13' src='//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif' width='17' border='0'&gt;";
	 *     $strtext = "leftmenu";
	 * }
	 * else
	 *     $strstyle = " style='padding:8;'";
	 * 
	 * 
	 * // Content of variable $sMenuProlog is typed just before all menu items iterations
	 * // Content of variable $sMenuEpilog is typed just after all menu items iterations
	 * $sMenuProlog = "&lt;table border='0' cellpadding='0' cellspacing='0' width='100%'&gt;";
	 * $sMenuEpilog = '&lt;tr&gt;&lt;td colspan=2 background="/bitrix/templates/demo/images/l_menu_border.gif"&gt;&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif" width="1" height="1"&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;';
	 * 
	 * // if $PERMISSION &gt; "D" then current user can access this page
	 * if ($PERMISSION &gt; "D")
	 * {
	 *     $sMenuBody = '&lt;tr&gt;&lt;td colspan=2 background="/bitrix/templates/demo/images/l_menu_border.gif"&gt;&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif" width="1" height="1"&gt;&lt;/td&gt;&lt;/tr&gt;&lt;tr&gt;'.$strDir.'&lt;td valign="top"'.$strstyle.' width="100%"&gt;&lt;a href="'.$LINK.'" class="'.$strtext.'"&gt;'.$TEXT.'&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;';
	 * }
	 * else
	 * {
	 *     $sMenuBody = '&lt;td colspan=2 background="/bitrix/templates/demo/images/l_menu_border.gif"&gt;&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif" width="1" height="1"&gt;&lt;/td&gt;&lt;/tr&gt;&lt;tr&gt;'.$strDir_d.'&lt;/td&gt;&lt;td valign="top"'.$strstyle.' width="100%"&gt;&lt;a href="'.$LINK.'" class='.$strtext.'&gt;'.$TEXT.'&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;';
	 * 
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04708" >Меню</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/index.php">Класс CMenu</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/init.php">CMenu::Init</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenu.php">CMain::GetMenu</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtmlex.php">CMain::GetMenuHtmlEx</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtml.php
	 * @author Bitrix
	 */
	public static function GetMenuHtml($type="left", $bMenuExt=false, $template = false, $sInitDir = false)
	{
		$menu = $this->GetMenu($type, $bMenuExt, $template, $sInitDir);
		return $menu->GetMenuHtml();
	}

	
	/**
	 * <p>Возвращает HTML-код для отображения меню заданного типа. В отличие от функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtml.php">CMain::GetMenuHtml</a> шаблон меню будет подключаться только один раз.</p> <p class="note">В шаблоне меню, используемом данной функцией, в обязательном порядке необходимо инициализировать переменную <b>$sMenu</b>, в которой должен храниться HTML представляющий из себя все меню целиком.</p>
	 *
	 *
	 *
	 *
	 * @param string $type = "left" Тип меню.<br>Необязателен. По умолчанию "left".
	 *
	 *
	 *
	 * @param bool $use_ext = false Если значение - "true", то для формирования массива меню, помимо
	 * файлов <nobr><b>.</b><i>тип меню</i><b>.menu.php</b></nobr> будут также подключаться
	 * файлы с именами вида <nobr><b>.</b><i>тип меню</i><b>.menu_ext.php</b></nobr>. В которых
	 * вы можете манипулировать массивом меню <b>$aMenuLinks</b> произвольно, по
	 * вашему усмотрению (например, дополнять пункты меню значениями из
	 * инфо-блоков).<br>Необязателен. По умолчанию - "false".
	 *
	 *
	 *
	 * @param mixed $template_path = false Путь относительно корня к шаблону меню. <br>Необязателен. По
	 * умолчанию - "false", что означает искать по алгоритму указанному на
	 * странице <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3254"
	 * >Построение и показ меню</a>.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Каталог для которого будет строится меню.<br>Необязателен. По
	 * умолчанию - "false", что означает - текущий каталог.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Вывод меню в файле /bitrix/templates/demo/header.php:
	 * &lt;script language="JavaScript1.2" src="//www.1c-bitrix.ru.js.1c-bitrix-cdn.ru/bitrix/templates/demo/js/ddnmenu.js"&gt;&lt;/script&gt;
	 * &lt;?echo <b>$APPLICATION-&gt;GetMenuHtmlEx</b>("top");?&gt;Файл /.top.menu.php:
	 * &lt;?
	 * // основной файл меню
	 * // данный файл может редактироваться в редакторе меню
	 * $aMenuLinks = Array(
	 *     Array(
	 *         "Главная", 
	 *         "/index.php", 
	 *         Array(), 
	 *         Array(), 
	 *         "" 
	 *     ),
	 *     Array(
	 *         "Каталог", 
	 *         "/catalog/", 
	 *         Array(), 
	 *         Array(), 
	 *         "" 
	 *     ),
	 *     Array(
	 *         "Поддержка", 
	 *         "/support/", 
	 *         Array(), 
	 *         Array(), 
	 *         "" 
	 *     ),
	 *     Array(
	 *         "Партнёры", 
	 *         "/partners/", 
	 *         Array(), 
	 *         Array(), 
	 *         "" 
	 *     ),
	 *     Array(
	 *         "Компания", 
	 *         "/about/", 
	 *         Array(), 
	 *         Array(), 
	 *         "" 
	 *     )
	 * );
	 * ?&gt;Файл /bitrix/templates/demo/top.menu_template.php:
	 * &lt;?
	 * // шаблон меню
	 * $sMenu = '&lt;table width="100%" border="0" cellspacing="0" cellpadding="0"&gt;&lt;tr&gt;&lt;td width="0%"&gt;&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/top_menu_corner.gif" alt="" width="15" height="19"&gt;&lt;/td&gt;';
	 * 
	 * for($i=0; $i&lt;count($arMENU); $i++)
	 * {
	 *     $MENU_ITEM = $arMENU[$i];
	 *     extract($MENU_ITEM);
	 * 
	 *     if($SELECTED)
	 *         $clrtext = 'topmenuact';
	 *     else
	 *         $clrtext = 'topmenu';
	 * 
	 *     $sMenu .= '&lt;td&gt;';
	 *     $sMenu .= '&lt;table border="0" cellspacing="0" cellpadding="0"&gt;&lt;tr&gt;';
	 *     $sMenu .= '&lt;td bgcolor="#7B9DBB" onmouseover="show('.$i.')" onmouseout="hidden('.$i.')"&gt;&lt;a href="'.$LINK.'" class="'.$clrtext.'"&gt;&lt;nobr&gt; '.$TEXT.' &lt;/nobr&gt;&lt;/a&gt;&lt;/td&gt;';
	 *     $sMenu .= '&lt;td width="0%" bgcolor="#7B9DBB"&gt;';
	 * 
	 *     if($i&lt;count($arMENU)-1) //add vertical divider after all items but not after the last one
	 *         $sMenu .= '&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/top_menu_divider.gif" width="15" height="19" alt=""&gt;';
	 *     else
	 *         $sMenu .= '&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif" width="1" height="19" alt=""&gt;';
	 * 
	 *     $sMenu .= '&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;';
	 * 
	 *     //check if menu of left type exists in the folder that the menu item points to
	 *     $popup_menu = new CMenu("left");
	 *     $popup_menu-&gt;Init($LINK);
	 *     if(count($popup_menu-&gt;arMenu) &gt; 0)
	 *     {
	 *         //if left menu exists then we display it in the hidden layer
	 *         $popup_menu-&gt;template = "/bitrix/templates/demo/popup.menu_template.php";
	 *         $sMenu .= '&lt;div style="position:relative; width: 100%;"&gt;';
	 *         $sMenu .= '&lt;div onMouseOver="show('.$i.')" onMouseOut="hidden('.$i.')" id="menu'.$i.'" style="visibility: hidden; position: absolute; z-index: +1; top: 0px;" &gt;';
	 *         $sMenu .= $popup_menu-&gt;GetMenuHtmlEx();
	 *         $sMenu .= '&lt;/div&gt;&lt;/div&gt;';
	 *     }
	 *     $sMenu .= '&lt;/td&gt;';
	 * }
	 * $sMenu .= '&lt;td width="100%" bgcolor="#7B9DBB"&gt;&lt;img src="//www.1c-bitrix.ru.images.1c-bitrix-cdn.ru/bitrix/templates/demo/images/1.gif" width="50" height="1" alt=""&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;';
	 * ?&gt;Файл /bitrix/templates/demo/popup.menu_template.php:
	 * &lt;?
	 * $sMenu =
	 *     '&lt;table border="0" cellspacing="0" cellpadding="0" width="110"&gt;'.
	 *     '&lt;tr&gt;&lt;td bgcolor="#E6EFF7" valign="top"&gt;&lt;table border="0" cellspacing="0" cellpadding="0" width="100%"&gt;';
	 * 
	 * for($i=0; $i&lt;count($arMENU); $i++)
	 * {
	 *     $MENU_ITEM = $arMENU[$i];
	 *     extract($MENU_ITEM);
	 * 
	 *     if ($PERMISSION &gt; "D")
	 *     {
	 *         $sMenu .=
	 *         '&lt;tr valign="top"&gt;&lt;td nowrap onmouseover="this.className=\'popupmenuact\'" onmouseout="this.className=\'popupmenu\'" onclick="window.location=\''.$LINK.'\'"  class="popupmenu" style="cursor: hand"&gt;'.
	 *         '&lt;nobr&gt;&lt;a href="'.$LINK.'" style="text-decoration: none;"&gt;&lt;font class="popupmenutext"&gt;'.$TEXT.'&lt;/font&gt;&lt;/a&gt;'.
	 *         '&lt;/nobr&gt;&lt;/td&gt;&lt;/tr&gt;';
	 *     }
	 *     else
	 *     {
	 *         $sMenu .=
	 *         '&lt;tr valign="top"&gt;&lt;td nowrap onmouseover="this.className=\'popupmenuact\'" onmouseout="this.className=\'popupmenu\'" onclick="window.location=\''.$LINK.'\'"  class="popupmenu" style="cursor: hand"&gt;'.
	 *         '&lt;nobr&gt;&lt;font class="popupmenuclosed"&gt;'.$TEXT.'&lt;/font&gt;'.
	 *         '&lt;/nobr&gt;&lt;/td&gt;&lt;/tr&gt;';
	 *     }
	 * }
	 * $sMenu .= '&lt;/table&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;';
	 * ?&gt;Файл /bitrix/templates/demo/js/ddnmenu.js:
	 * var brname=navigator.appName, BrVer='';
	 * if(brname.substring(0,2)=="Mi")
	 *     BrVer='E';
	 * var timer = 0;
	 * lastid = -1;
	 * 
	 * function show(id)
	 * {
	 *     if(!((document.all)?document.all['menu'+id]:document.getElementById('menu'+id)))
	 *         return;
	 *     clearTimeout(timer);
	 *     if((id != lastid) &amp;&amp; (lastid!=-1))
	 *         ((document.all)?document.all['menu'+lastid]:document.getElementById('menu'+lastid)).style.visibility = 'hidden';
	 *     hideElement("SELECT", document.getElementById('menu'+lastid));
	 *     lastid = id;
	 *     ((document.all)?document.all['menu'+lastid]:document.getElementById('menu'+lastid)).style.visibility = 'visible';
	 * }
	 * 
	 * function hidden(id)
	 * {
	 *     if(!((document.all)?document.all['menu'+id]:document.getElementById('menu'+id)))
	 *         return;
	 *     showElement("SELECT");
	 *     timer = setTimeout("if('"+id+"' == '"+lastid+"'){((document.all)?document.all['menu"+lastid+"']:document.getElementById('menu"+lastid+"')).style.visibility = 'hidden';}", 500)
	 * }
	 * 
	 * 
	 * function GetPos(el)
	 * {
	 *     if (!el || !el.offsetParent)return false;
	 *     var res=Array()
	 *     res["left"] = el.offsetLeft;
	 *     res["top"] = el.offsetTop;
	 *     var objParent = el.offsetParent;
	 *     while (objParent.tagName.toUpperCase()!="BODY")
	 *     {
	 *         res["left"] += objParent.offsetLeft;
	 *         res["top"] += objParent.offsetTop;
	 *         objParent = objParent.offsetParent;
	 *     }
	 *     res["right"]=res["left"]+el.offsetWidth;
	 *     res["bottom"]=res["top"]+el.offsetHeight;
	 *     return res;
	 * }
	 * 
	 * function hideElement(elName, Menu)
	 * {
	 *     if(BrVer!='E') return;
	 *     for (i = 0; i &lt; document.all.tags(elName).length; i++)
	 *     {
	 *         Obj = document.all.tags(elName)[i];
	 *         if(!(pMenu=GetPos(Menu)))continue;
	 *         if(!(pObj=GetPos(Obj)))continue;
	 * 
	 *         if(pObj["left"]&lt;pMenu["right"] &amp;&amp; pMenu["left"]&lt;pObj["right"] &amp;&amp; pObj["top"]&lt;pMenu["bottom"] &amp;&amp; pMenu["top"]&lt;pObj["bottom"])
	 *             Obj.style.visibility = "hidden";
	 *     }
	 * }
	 * 
	 * function showElement(elName)
	 * {
	 *     if(BrVer!='E') return;
	 *     for (i = 0; i &lt; document.all.tags(elName).length; i++)
	 *     {
	 *         obj = document.all.tags(elName)[i];
	 *         if (!obj || !obj.offsetParent)continue;
	 *         if(obj.style.visibility=="hidden")
	 *             obj.style.visibility = "visible";
	 *     }
	 * }
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04708" >Меню</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/index.php">Класс CMenu</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/init.php">CMenu::Init</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenu.php">CMain::GetMenu</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtml.php">CMain::GetMenuHtml</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtmlex.php
	 * @author Bitrix
	 */
	public static function GetMenuHtmlEx($type="left", $bMenuExt=false, $template = false, $sInitDir = false)
	{
		$menu = $this->GetMenu($type, $bMenuExt, $template, $sInitDir);
		return $menu->GetMenuHtmlEx();
	}

	
	/**
	 * <p>Возвращает объект класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/index.php">CMenu</a>, инициализированный функцией <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/init.php">CMenu::Init</a>. Если произошла ошибка, то текст ошибки будет содержаться в свойстве LAST_ERROR данного объекта.</p>
	 *
	 *
	 *
	 *
	 * @param string $type = "left" Тип меню.<br>Необязателен. По умолчанию "left".
	 *
	 *
	 *
	 * @param bool $use_ext = false Если значение - "true", то для формирования массива меню, помимо
	 * файлов <nobr><b>.</b><i>тип меню</i><b>.menu.php</b></nobr> будут также подключаться
	 * файлы с именами вида <nobr><b>.</b><i>тип меню</i><b>.menu_ext.php</b></nobr>. В которых
	 * вы можете манипулировать массивом меню <b>$aMenuLinks</b> произвольно, по
	 * вашему усмотрению (например, дополнять пункты меню значениями из
	 * инфо-блоков).<br>Необязателен. По умолчанию - "false".
	 *
	 *
	 *
	 * @param mixed $template_path = false Путь относительно корня к шаблону меню. <br>Необязателен. По
	 * умолчанию - "false".
	 *
	 *
	 *
	 * @param mixed $init_dir = false Каталог для которого будет строится меню.<br>Необязателен. По
	 * умолчанию - "false", что означает - текущий каталог.
	 *
	 *
	 *
	 * @return CMenu 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // выводим меню типа "left"
	 * // с поддержкой файлов .<i>тип меню</i>.menu_ext.php
	 * // с четким указанием шаблона и 
	 * // каталога для которого будет построено меню
	 * 
	 * $obMenu = <b>$APPLICATION-&gt;GetMenu</b>(
	 *     "left",
	 *     true,
	 *     "/bitrix/php_interface/".SITE_ID."/left.menu_template.php", 
	 *     SITE_DIR
	 *     );
	 * 
	 * // выводим меню
	 * echo $obMenu-&gt;GetMenuHtml();
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/index.php">Класс CMenu</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmenu/init.php">CMenu::Init</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtml.php">CMain::GetMenuHtml</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmenuhtmlex.php">CMain::GetMenuHtmlEx</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenu.php
	 * @author Bitrix
	 */
	public static function GetMenu($type="left", $bMenuExt=false, $template = false, $sInitDir = false)
	{
		$menu = new CMenu($type);
		if($sInitDir===false)
			$sInitDir = $this->GetCurDir();
		if(!$menu->Init($sInitDir, $bMenuExt, $template))
			$menu->MenuDir = $sInitDir;
		return $menu;
	}

	
	/**
	 * <p>Определяет является ли текущий протокол защищенным (HTTPS).</p>
	 *
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * global $APPLICATION;
	 * // получим полный URI текущий страницы
	 * $CURRENT_PAGE = (<b>CMain::IsHTTPS</b>()) ? "https://" : "http://";
	 * $CURRENT_PAGE .= $_SERVER["HTTP_HOST"];
	 * $CURRENT_PAGE .= $APPLICATION-&gt;GetCurUri();
	 * // в переменной $CURRENT_PAGE значение будет например, 
	 * // "http://www.mysite.ru/ru/index.php?id=23"
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/ishttps.php
	 * @author Bitrix
	 */
	public static function IsHTTPS()
	{
		if($_SERVER["SERVER_PORT"] == 443)
			return true;

		// 'HTTPS' Set to a non-empty value if the script was queried through the HTTPS protocol.
		// Note that when using ISAPI with IIS, the value will be "off" if the request was not made through the HTTPS protocol.
		if(isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) <> "off")
			return true;

		return false;
	}

	
	/**
	 * <p>Возвращает заголовок страницы.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $property_name = false Если указано значение "false", то будет возвращен заголовок
	 * страницы, устанавливаемый с помощью функции SetTitle.<br>В противном
	 * случае в параметре передается идентификатор свойства страницы,
	 * значение которого будет выведено в качестве заголовка (если это
	 * значение задано, например, с помощью функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>).<br>Необязательный.
	 * По умолчанию "false".
	 *
	 *
	 *
	 * @param bool $strip_tags = false Если значение - "true", то из заголовка страницы будут удалены все HTML
	 * теги.<br>Необязательный. По умолчанию - "false".
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showtitle.php">CMain::ShowTitle</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settitle.php">CMain::SetTitle</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/gettitle.php
	 * @author Bitrix
	 */
	public static function GetTitle($property_name = false, $strip_tags = false)
	{
		if($property_name!==false && strlen($this->GetProperty($property_name))>0)
			$res = $this->GetProperty($property_name);
		else
			$res = $this->sDocTitle;
		if($strip_tags)
			return strip_tags($res);
		return $res;
	}

	
	/**
	 * <p>Устанавливает заголовок страницы. Если заголовок страницы у вас выводится с помощью функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showtitle.php">CMain::ShowTitle</a>, то устанавливать его вы можете уже после того как у вас будет выведена пролог сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $title  Заголовок страницы.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;SetTitle</b>("Page title");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showtitle.php">CMain::ShowTitle</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/gettitle.php">CMain::GetTitle</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/settitle.php
	 * @author Bitrix
	 */
	public static function SetTitle($title, $arOptions = null)
	{
		$this->sDocTitle = $title;

		if (is_array($arOptions))
		{
			$this->sDocTitleChanger = $arOptions;
		}
		else
		{
			$arTrace = array_reverse(debug_backtrace());

			foreach ($arTrace as $arTraceRes)
			{
				if (isset($arTraceRes['class']) && isset($arTraceRes['function']))
				{
					if (ToUpper($arTraceRes['class']) == 'CBITRIXCOMPONENT' && ToUpper($arTraceRes['function']) == 'INCLUDECOMPONENT' && is_object($arTraceRes['object']))
					{
						/** @var CBitrixComponent $comp */
						$comp = $arTraceRes['object'];
						$this->sDocTitleChanger = array(
							'COMPONENT_NAME' => $comp->GetName(),
						);

						break;
					}
				}
			}
		}
	}
	
	/**
	 * <p>Отображает заголовок страницы.<br><br>Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, задавать заголовок страницы (например, внутри компонента) уже после того как был выведен пролог сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_name = "title" Идентификатор свойства страницы, значение которого будет
	 * выведено в качестве заголовка (если это значение задано например,
	 * с помощью функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>).<br>Необязательный.
	 * По умолчанию "title".
	 *
	 *
	 *
	 * @param bool $strip_tags = true Если значение - "true", то из заголовка страницы будут удалены все HTML
	 * теги.<br>Необязательный. По умолчанию - "true".
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?= LANG_CHARSET;?&gt;"&gt;
	 * &lt;META NAME="ROBOTS" content="ALL"&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("keywords")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("description")?&gt;
	 * &lt;title&gt;&lt;?<b>$APPLICATION-&gt;ShowTitle</b>()?&gt;&lt;/title&gt;
	 * &lt;?$APPLICATION-&gt;ShowCSS();?&gt;
	 * &lt;/head&gt;
	 * &lt;body link="#525252" alink="#F1555A" vlink="#939393" text="#000000"&gt;
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/gettitle.php">CMain::GetTitle</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settitle.php">CMain::SetTitle</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showtitle.php
	 * @author Bitrix
	 */
	public static function ShowTitle($property_name="title", $strip_tags = true)
	{
		$this->AddBufferContent(array(&$this, "GetTitle"), $property_name, $strip_tags);
	}

	
	/**
	 * <p>Устанавливает [link=89635]свойство[/link] для текущей страницы.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param string $property_value  Значение свойства.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;SetPageProperty</b>("keywords", "веб, разработка, программирование");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setpageproperty.php
	 * @author Bitrix
	 */
	public static function SetPageProperty($PROPERTY_ID, $PROPERTY_VALUE, $arOptions = null)
	{
		$this->arPageProperties[strtoupper($PROPERTY_ID)] = $PROPERTY_VALUE;

		if (is_array($arOptions))
			$this->arPagePropertiesChanger[strtoupper($PROPERTY_ID)] = $arOptions;
	}

	
	/**
	 * <p>Возвращает [link=89635]свойство[/link] текущей страницы.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param mixed $default_value = false Значение свойства по умолчанию.<br>Необязательный. По умолчанию -
	 * "false" - значение свойства по умолчанию не задано.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;GetPageProperty</b>("keywords");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getpageproperty.php
	 * @author Bitrix
	 */
	public static function GetPageProperty($PROPERTY_ID, $default_value = false)
	{
		if(isset($this->arPageProperties[strtoupper($PROPERTY_ID)]))
			return $this->arPageProperties[strtoupper($PROPERTY_ID)];
		return $default_value;
	}

	
	/**
	 * <p>Отображает [link=89635]свойство страницы[/link], учитывая свойства раздела.<br><br> Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, задавать свойство страницы (например, внутри компонента) и использовать его в прологе уже после того как он был выведен.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param mixed $default_value = false Значение свойства по умолчанию.<br>Необязательный. По умолчанию -
	 * "false" - значение свойства по умолчанию не задано.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?=LANG_CHARSET;?&gt;"&gt;
	 * &lt;META NAME="ROBOTS" content="ALL"&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("keywords")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("description")?&gt;
	 * &lt;title&gt;&lt;?<b>$APPLICATION-&gt;ShowProperty</b>("page_title")?&gt;&lt;/title&gt;
	 * &lt;?$APPLICATION-&gt;ShowCSS();?&gt;
	 * &lt;/head&gt;
	 * &lt;body link="#525252" alink="#F1555A" vlink="#939393" text="#000000"&gt;
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li>[link=89635]Свойства страниц и
	 * мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showproperty.php
	 * @author Bitrix
	 */
	public static function ShowProperty($PROPERTY_ID, $default_value = false)
	{
		$this->AddBufferContent(array(&$this, "GetProperty"), $PROPERTY_ID, $default_value);
	}

	
	/**
	 * <p>Возвращает [link=89635]свойство[/link] текущей страницы или раздела. Если на самой странице свойство не задано, то будет возвращено значение свойства вышестоящего раздела (рекурсивно до корня сайта).</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param mixed $default_value = false Значение свойства по умолчанию.<br>Необязательный. По умолчанию -
	 * "false" - значение свойства по умолчанию не задано.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $keywords = <b>$APPLICATION-&gt;GetProperty</b>("keywords");
	 * if (strlen($keywords)&gt;0) echo $keywords;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#properties"
	 * >Свойства страниц и мета-теги</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getproperty.php
	 * @author Bitrix
	 */
	public static function GetProperty($PROPERTY_ID, $default_value = false)
	{
		$propVal = $this->GetPageProperty($PROPERTY_ID);
		if($propVal !== false)
			return $propVal;

		$propVal = $this->GetDirProperty($PROPERTY_ID);
		if($propVal !== false)
			return $propVal;

		return $default_value;
	}

	
	/**
	 * <p>Возвращает массив всех [link=89635]свойств страницы[/link].</p>
	 *
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $props = <b>$APPLICATION-&gt;GetPagePropertyList</b>();
	 * foreach($props as $key=&gt;$val)
	 * 	echo '&lt;meta name="'.$key.'" content="'.htmlspecialchars($val).'"&gt;';
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-тэги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getpagepropertylist.php
	 * @author Bitrix
	 */
	public static function GetPagePropertyList()
	{
		return $this->arPageProperties;
	}

	
	/**
	 * <p>Устанавливает [link=89635]свойство[/link] для текущего раздела.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param string $property_value  Значение свойства.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;SetDirProperty</b>("keywords", "дизайн, веб, сайт");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setdirproperty.php
	 * @author Bitrix
	 */
	public static function SetDirProperty($PROPERTY_ID, $PROPERTY_VALUE)
	{
		$this->arDirProperties[strtoupper($PROPERTY_ID)] = $PROPERTY_VALUE;
	}

	public static function InitPathVars(&$site, &$path)
	{
		$site = false;
		if(is_array($path))
		{
			$site = $path[0];
			$path = $path[1];
		}
		return $path;
	}

	public static function InitDirProperties($path)
	{
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		if($this->bDirProperties)
			return true;

		if($path===false)
			$path = $this->GetCurDir();

		$io = CBXVirtualIo::GetInstance();

		while (true) // until the root
		{
			$path = rtrim($path, "/");
			$section_file_name = $DOC_ROOT.$path."/.section.php";

			if($io->FileExists($section_file_name))
			{
				$arDirProperties = false;
				include($io->GetPhysicalName($section_file_name));
				if(is_array($arDirProperties))
				{
					foreach($arDirProperties as $prid=>$prval)
					{
						$prid = strtoupper($prid);
						if(!isset($this->arDirProperties[$prid]))
							$this->arDirProperties[$prid] = $prval;
					}
				}
			}

			if(strlen($path)<=0)
				break;

			// file or folder
			$pos = bxstrrpos($path, "/");
			if($pos===false)
				break;

			//parent folder
			$path = substr($path, 0, $pos+1);
		}

		$this->bDirProperties = true;
		return true;
	}

	
	/**
	 * <p>Возвращает [link=89635]свойство[/link] текущего раздела.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства.
	 *
	 *
	 *
	 * @param mixed $path = false Путь к каталогу. В случае многосайтовой версии, если DOCUMENT_ROOT у
	 * сайтов разный (задается в поле "Путь к корневой папке веб-сервера"
	 * в настройках сайта), то в данном параметре необходимо передавать
	 * массив вида:<pre>array("ID сайта", "путь")</pre>Необязателен. По умолчанию -
	 * "false" - текущий каталог.
	 *
	 *
	 *
	 * @param mixed $default_value = false Значение свойства по умолчанию.<br>Необязательный. По умолчанию -
	 * "false" - значение свойства по умолчанию не задано.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;GetDirProperty</b>("keywords");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-тэги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getdirpropertylist.php">CMain::GetDirPropertyList</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getdirproperty.php
	 * @author Bitrix
	 */
	public static function GetDirProperty($PROPERTY_ID, $path=false, $default_value = false)
	{
		CMain::InitPathVars($site, $path);

		if($path===false)
			$path = $this->GetCurDir();

		if(!$this->bDirProperties)
			$this->InitDirProperties(array($site, $path));

		if(isset($this->arDirProperties[strtoupper($PROPERTY_ID)]))
			return $this->arDirProperties[strtoupper($PROPERTY_ID)];

		return $default_value;
	}

	
	/**
	 * <p>Возвращает массив [link=89635]свойств раздела[/link], собранных рекурсивно до корня сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $path = false Путь к каталогу. В случае многосайтовой версии, если DOCUMENT_ROOT у
	 * сайтов разный (задается в поле "Путь к корневой папке веб-сервера"
	 * в настройках сайта), то в данном параметре необходимо передавать
	 * массив вида:<pre>array("ID сайта", "путь")</pre>Необязателен. По умолчанию -
	 * "false" - текущий каталог.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $props = <b>$APPLICATION-&gt;GetDirPropertyList</b>();
	 * foreach($props as $key=&gt;$val)
	 * 	echo '&lt;meta name="'.$key.'" content="'.htmlspecialchars($val).'"&gt;';
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-тэги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getproperty.php">CMain::GetProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpageproperty.php">CMain::GetDirProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getpagepropertylist.php">CMain::GetPagePropertyList</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getdirpropertylist.php
	 * @author Bitrix
	 */
	public static function GetDirPropertyList($path=false)
	{
		CMain::InitPathVars($site, $path);

		if($path===false)
			$path = $this->GetCurDir();

		if(!$this->bDirProperties)
			$this->InitDirProperties(array($site, $path));

		if(is_array($this->arDirProperties))
			return $this->arDirProperties;

		return false;
	}

	
	/**
	 * <p>Возвращает [link=89635]свойство страницы[/link] обрамленное тегом &lt;meta&gt;. Если на самой страницы свойство не задано, то будет взято значение свойства вышестоящего раздела (рекурсивно до корня сайта). Если свойство не задано, то метод вернет пустую строку. </p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства страницы или раздела, значение которого
	 * (<i>value</i>) будет выведено в аттрибуте "content" мета-тега: <br> &lt;meta
	 * content="<i>value</i>" ...&gt;
	 *
	 *
	 *
	 * @param string $meta_name = false Атрибут "name" мета-тега: <br> &lt;meta name="<i>meta_name</i>" ...&gt; <br>
	 * Необязательный. По умолчанию равен идентификатору свойства
	 * <i>property_id</i>.
	 *
	 *
	 *
	 * @param bool $bXhtmlStyle = true Параметр, устанавливающий, по какому стандарту оформляются
	 * HTML-теги. Если значение <i>true</i>, то теги выводятся по стандарту XHTML
	 * (&lt;meta /&gt;), иначе по стандарту HTML 4 (&lt;meta&gt;). Параметр появился в
	 * версии 8.5.3 ядра. Необязательный, по умолчанию <i>true</i>.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $meta_keywords = <b>$APPLICATION-&gt;GetMeta</b>("keywords_prop", "keywords");
	 * if (strlen($meta_keywords)&gt;0) echo $meta_keywords;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89635]Свойства страниц и мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showmeta.php">CMain::ShowMeta</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmeta.php
	 * @author Bitrix
	 */
	public static function GetMeta($id, $meta_name=false, $bXhtmlStyle=true)
	{
		if($meta_name==false)
			$meta_name=$id;
		$val = $this->GetProperty($id);
		if(!empty($val))
			return '<meta name="'.htmlspecialcharsbx($meta_name).'" content="'.htmlspecialcharsEx($val).'"'.($bXhtmlStyle? ' /':'').'>'."\n";
		return '';
	}

	
	/**
	 * <p>Подключает модуль рекламы и отображает баннер заданного типа.</p>
	 *
	 *
	 *
	 *
	 * @param string $banner_type  Тип баннера (административный пункт меню "Реклама" &gt; "Типы
	 * баннеров").
	 *
	 *
	 *
	 * @param string $html_before = false HTML код выводимый перед баннером.
	 *
	 *
	 *
	 * @param string $html_after = false HTML код выводимый после баннера.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>// подключим модуль рекламы и выведем баннер типа "LEFT"<br><b>$APPLICATION-&gt;ShowBanner</b>("LEFT", "&lt;div align=\"center\"&gt;", "&lt;br&gt;&lt;/div&gt;&lt;br&gt;");<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p><a href="http://dev.1c-bitrix.ruapi_help/advertising/classes/index.php">Классы модуля
	 * Реклама</a></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showbanner.php
	 * @author Bitrix
	 */
	public static function ShowBanner($type, $html_before="", $html_after="")
	{
		if(!CModule::IncludeModule("advertising"))
			return;

		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$APPLICATION->AddBufferContent(array("CAdvBanner", "Show"), $type, $html_before, $html_after);
	}

	
	/**
	 * <p>Отображает [link=89635]свойство страницы[/link] в виде мета-тега. Функция допускает использование специальных символов (html entities) в значениях свойств.</p> <p>Если на самой странице свойство не задано, то будет взято значение свойства вышестоящего раздела (рекурсивно до корня сайта). Если значение свойства не определено, то метод отобразит пустую строку.</p> <p>Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, задавать значения мета-тегов через свойства страницы или раздела (с помощью функций <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a>, <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a>) уже после того как был выведен пролог сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $property_id  Идентификатор свойства страницы или раздела, значение которого
	 * (<i>value</i>) будет выведено в аттрибуте "content" мета-тега: <br> &lt;meta
	 * content="<i>value</i>" ...&gt;
	 *
	 *
	 *
	 * @param string $meta_name = false Аттрибут "name" мета-тега: <br> &lt;meta name="<i>meta_name</i>" ...&gt; <br>
	 * Необязательный. По умолчанию равен идентификатору свойства
	 * <i>property_id</i>.
	 *
	 *
	 *
	 * @param bool $bXhtmlStyle = true Параметр, устанавливающий, по какому стандарту оформляются
	 * HTML-теги. Если значение <i>true</i>, то теги выводятся по стандарту XHTML
	 * (&lt;meta /&gt;), иначе по стандарту HTML 4 (&lt;meta&gt;). Параметр появился в
	 * версии 8.5.3 ядра. Необязательный, по умолчанию <i>true</i>.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?=LANG_CHARSET?&gt;"&gt;
	 * &lt;META NAME="ROBOTS" content="ALL"&gt;
	 * &lt;?<b>$APPLICATION-&gt;ShowMeta</b>("keywords")?&gt;
	 * &lt;?<b>$APPLICATION-&gt;ShowMeta</b>("description")?&gt;
	 * &lt;title&gt;&lt;?$APPLICATION-&gt;ShowTitle()?&gt;&lt;/title&gt;
	 * &lt;?$APPLICATION-&gt;ShowCSS();?&gt;
	 * &lt;/head&gt;
	 * &lt;body link="#525252" alink="#F1555A" vlink="#939393" text="#000000"&gt;
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li>[link=89635]Свойства страниц и
	 * мета-теги[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getmeta.php">CMain::GetMeta</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setpageproperty.php">CMain::SetPageProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setdirproperty.php">CMain::SetDirProperty</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showmeta.php
	 * @author Bitrix
	 */
	public static function ShowMeta($id, $meta_name=false, $bXhtmlStyle=true)
	{
		$this->AddBufferContent(array(&$this, "GetMeta"), $id, $meta_name, $bXhtmlStyle);
	}

	
	/**
	 * <p>Устанавливает CSS стиль для страницы.</p>
	 *
	 *
	 *
	 *
	 * @param string $path  Путь относительно корня к файлу с CSS стилями.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;SetAdditionalCSS</b>("/bitrix/templates/demo/additional.css");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showcss.php">CMain::ShowCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcss.php">CMain::GetCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settemplatecss.php">CMain::SetTemplateCSS</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setadditionalcss.php
	 * @author Bitrix
	 */
	public static function SetAdditionalCSS($Path2css, $additional=false)
	{
		if($additional)
			$this->arHeadAdditionalCss[] = $Path2css;
		else
			$this->sPath2css[] = $Path2css;
	}

	public static function SetHeaderLastCss($lastKey = 0)
	{
		$this->iHeaderLastCss = intval($lastKey);
	}

	public static function SetWorkAreaLastCss($lastKey = 0)
	{
		$this->iWorkAreaLastCss = intval($lastKey);
	}

	public static function SetHeaderLastJs($lastKey = 0)
	{
		$this->iHeaderLastJs = intval($lastKey);
	}

	public static function SetWorkAreaLastJs($lastKey = 0)
	{
		$this->iWorkAreaLastJs = intval($lastKey);
	}

	static function FixCssIncludes($contents, $css_path)
	{
		$css_path = dirname($css_path);

		$contents = preg_replace_callback(
			'#([;\s:]*(?:url|@import)\s*\(\s*)(\'|"|)(.+?)(\2)\s*\)#si',
			create_function('$matches', 'return $matches[1].CMain::__ReplaceUrlCSS($matches[2].$matches[3].$matches[4], "'.addslashes($css_path).'").")";'),
			$contents
		);

		$contents = preg_replace_callback(
			'#(\s*@import\s*)([\'"][^\'"]+[\'"])\s*\;#si',
			create_function('$matches', 'return $matches[1].CMain::__ReplaceUrlCSS($matches[2], "'.addslashes($css_path).'").";";'),
			$contents
		);

		return $contents;
	}

	public static function GetAdditionalCSS()
	{
		$n = count($this->sPath2css);
		if($n > 0)
			return $this->sPath2css[$n-1];
		return false;
	}

	public static function GetCSSArray()
	{
		return array_unique($this->sPath2css);
	}

	
	/**
	 * <p>Возвращает CSS страницы. CSS страницы может быть задан с помощью функций <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setadditionalcss.php">CMain::SetAdditionalCSS</a> и <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settemplatecss.php">CMain::SetTemplateCSS</a>. Помимо заданных CSS стилей, всегда возвращается CSS текущего шаблона сайта, задаваемого в файле <b>/bitrix/templates/</b><i>ID шаблона</i><b>/styles.css</b>.</p>
	 *
	 *
	 *
	 *
	 * @param bool $external = true Если значение - "true", то выводится HTML представляющий из себя ссылку
	 * на внешний CSS, например: <br><pre>&lt;LINK
	 * href="http://dev.1c-bitrix.rubitrix/templates/demo/styles.css" type="text/css" rel="stylesheet"&gt;</pre> Если
	 * значение "false", то выводится HTML представляющий из себя внутренний
	 * CSS, например: <pre>&lt;style type="text/css"&gt; body { margin: 0px; padding:0px; background-color: #FFFFFF}
	 * ... &lt;/style&gt;</pre> Исключение составляет CSS стили лежащие в каталоге
	 * /bitrix/modules/, они всегда подключаются как внутренний CSS (как правило
	 * это используется в стандартных компонентах).
	 *
	 *
	 *
	 * @param bool $bXhtmlStyle = true Параметр, устанавливающий, по какому стандарту оформляются
	 * HTML-теги. Если значение <i>true</i>, то теги выводятся по стандарту XHTML
	 * (&lt;link /&gt;), иначе по стандарту HTML 4 (&lt;link&gt;). Параметр появился в
	 * версии 8.5.3 ядра. Необязательный, по умолчанию <i>true</i>.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showcss.php">CMain::ShowCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settemplatecss.php">CMain::SetTemplateCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setadditionalcss.php">CMain::SetAdditionalCSS</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcss.php
	 * @author Bitrix
	 */
	public static function GetCSS($cMaxStylesCnt=true, $bXhtmlStyle=true)
	{
		$res = '';
		$site_template = '';
		$arCSS = $this->GetCSSArray();
		$arTemplateCss = array();
		$optimizeCSS = self::IsCSSOptimized();

		if($cMaxStylesCnt === true)
		{
			$cMaxStylesCnt = COption::GetOptionInt('main', 'max_css_files', 15);
		}

		if($optimizeCSS)
		{
			$cMaxStylesCnt -= 3;
		}

		global $USER;
		if(!$this->bInAjax)
		{
			if(isset($_GET['bx_template_preview_mode']) && $_GET['bx_template_preview_mode'] == 'Y' && $USER->CanDoOperation('edit_other_settings'))
			{
				$path = BX_PERSONAL_ROOT."/tmp/templates/__bx_preview/";
				$arTemplateCss[] = $path."styles.css";
				$arTemplateCss[] = $path."template_styles.css";
			}
			elseif(defined("SITE_TEMPLATE_ID"))
			{
				$site_template = SITE_TEMPLATE_ID;
				$path = BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID;
				$arTemplateCss[] = $path."/styles.css";
				$arTemplateCss[] = $path."/template_styles.css";
			}
			else
			{
				$site_template = '.default';
				$path = BX_PERSONAL_ROOT."/templates/.default";
				$arTemplateCss[] = $path."/styles.css";
				$arTemplateCss[] = $path."/template_styles.css";
			}
		}
		if($optimizeCSS == 'Y')
		{
			$cssFile = array();
			$cssSrcFile = array();
			$cssBxFile = array();
			$cssSrcBxFile = array();
			$cssTemplateFile = array();
			$cssSrcTmplateFile = array();

			foreach ($arTemplateCss as $key => $css)
			{
				if(!file_exists($_SERVER['DOCUMENT_ROOT'].$css))
					unset($arTemplateCss[$key]);
			}
		}
		else
		{
			$arCSS = array_merge($arCSS,$arTemplateCss, array_unique($this->arHeadAdditionalCss));
		}

		$isIE = IsIE();
		$cnt = 0;
		$optimCnt = 0;
		$res_content = '';
		$ruleCount = 0;
		foreach($arCSS as $cssKey => $css_path)
		{
			$bExternalLink = (strncmp($css_path, 'http://', 7) == 0 || strncmp($css_path, 'https://', 8) == 0);

			if(!$bExternalLink)
			{
				if(($p = strpos($css_path, "?"))>0)
					$css_file = substr($css_path, 0, $p);
				else
					$css_file = $css_path;

				$filename = $_SERVER["DOCUMENT_ROOT"].$css_file;
			}

			$bLink = ($bExternalLink || substr($css_file, -4, 4) != '.css');
			$addCSS = ((strncmp($css_path, '/bitrix/themes/', 15) != 0) && (strncmp($css_path, '/bitrix/modules/', 16) != 0));
			$optimize = ($optimizeCSS == 'Y' && $addCSS);

			if(!$isIE || $cnt < $cMaxStylesCnt || $optimize || $bLink)
			{
				if($bExternalLink || file_exists($filename))
				{
					if($res_content != '')
					{
						$res .= '<style type="text/css">'."\n".$res_content."\n</style>\n";
						$res_content = '';
					}

					if(!$bExternalLink && strpos($css_path, '?') === false)
					{
						$css_path = CUtil::GetAdditionalFileURL($css_path, true);
					}

					if($optimize && !$bExternalLink)
					{
						if(strncmp($css_path, '/bitrix/js/', 11) != 0)
						{
							if($cssKey > $this->iHeaderLastCss && $cssKey <= $this->iWorkAreaLastCss)
							{
								// Page css
								$cssSrcFile[] = $css_path;
								$cssFile[] = $css_file;
							}
							else
							{
								// Template css
								$cssSrcTmplateFile[] = $css_path;
								$cssTemplateFile[] = $css_file;
							}
						}
						else
						{
							// Kernell css
							$cssSrcBxFile[] = $css_path;
							$cssBxFile[] = $css_file;
						}
						$optimCnt++;
					}
					else
					{
						$res .= '<link href="'.$css_path.'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";
						$cnt++;
					}
				}
			}
			elseif(!$bLink && file_exists($filename) && filesize($filename) > 0)
			{
				if($cnt < $cMaxStylesCnt)
				{
					if(!$bExternalLink && strpos($css_path, '?') === false)
					{
						$css_path = CUtil::GetAdditionalFileURL($css_path, true);
					}

					$res .= '<link href="'.$css_path.'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";
					$cnt++;
				}
				else
				{
					$arTmp = $this->__ShowInlineCssIE($filename, $css_path, $ruleCount);
					$ruleCount = $arTmp['CNT'];
					$res_content .= $arTmp['CONTENT'];
				}
			}
		}

		if($optimizeCSS == 'Y' && $optimCnt > 0)
		{
			$res .= $this->__OptimizeCssJs($cssFile, $cssSrcFile, $site_template, false, 'page', 'css', $bXhtmlStyle);
			$res .= $this->__OptimizeCssJs($cssBxFile, $cssSrcBxFile, $site_template, true, 'kernel', 'css', $bXhtmlStyle);


			$arTemplateCss = array_merge($arTemplateCss, array_unique($this->arHeadAdditionalCss));
			foreach ($arTemplateCss as $key => $css)
			{
				$cssTemplateFile[] = $css;
				$cssSrcTmplateFile[] = CUtil::GetAdditionalFileURL($css);
			}
			$res .= $this->__OptimizeCssJs($cssTemplateFile, $cssSrcTmplateFile, $site_template, false, 'template', 'css', $bXhtmlStyle);
			unset($cssFile, $cssSrcFile, $arTemplateCss, $cssTemplateFile, $cssSrcTmplateFile);
		}

		if($res_content!='')
			$res .= '<style type="text/css">'."\n".$res_content."\n</style>\n";

		return $res;
	}

	/**
	 * Optimize css and js files
	 *
	 * @param array $arFile - Full path for a source css or js file
	 * @param array $arSrcFile - Full path for a source css or js file with timestamp
	 * @param string $site_template - Site template name
	 * @param bool $unique - Create unique file for all site in a current template
	 * @param string $sufix - Some sufix for css or js files
	 * @param string $type - file type, css or js
	 * @param bool $bXhtmlStyle - Xhtml or not
	 * @return string - String for including css or js in html page
	 */
	private function __OptimizeCssJs($arFile = array(), $arSrcFile = array(), $site_template, $unique = false, $sufix = 'default', $type = 'css', $bXhtmlStyle=true)
	{
		if((!is_array($arFile) || count($arFile) < 1) || (!is_array($arSrcFile) || count($arSrcFile) < 1))
		{
			return '';
		}

		$res = '';
		$fileMTime = '';
		$upOptim = 'NO';
		$isIE = IsIE();
		$writeResult = true;
		$unsetKey = array();
		$arFilesInfo = array();
		$arFilesInfo['CUR_SEL_CNT'] = 0;
		$arFilesInfo['CUR_IE_CNT'] = 0;
		$arFilesInfo['FILES'] = array();

		$noCheckOnly = !defined('BX_HEADFILES_CACHE_CHECK_ONLY');

		$sufix = trim($sufix);
		$sufix = strlen($sufix) < 1 ? 'default' : $sufix;

		foreach($arSrcFile as $key => $filePath)
		{
			if(strpos($filePath, '?') === false)
			{
				$arSrcFile[$key] = CUtil::GetAdditionalFileURL($filePath);
			}
		}

		if(!$unique)
		{
			$fileMTime = '_'.md5(implode('_', $arSrcFile));
			$sufix .= '_'.md5(implode('_', $arFile));
		}

		$type = ($type == 'js' ? 'js' : 'css');

		$optimPath = BX_PERSONAL_ROOT.'/cache/'.$type.'/'.SITE_ID.'/'.$site_template.'/'.$sufix.'/';
		$infoFile = BX_PERSONAL_ROOT.'/managed_cache/'.$GLOBALS['DB']->type.'/'.$type.'/'.SITE_ID.'/'.$site_template.'/'.$sufix.'/info.php';

		if($type == 'css')
		{
			$maxAddCssSelect = 3950;
			$maxCssSelect = 4000;
			$optimFName = $optimPath.$sufix.$fileMTime.'.css';
			$cssFNameIE = $optimPath.$sufix.$fileMTime.'#CNT#.css';
		}
		else
		{
			$optimFName = $optimPath.$sufix.$fileMTime.'.js';
		}

		$optimFileExist = file_exists($_SERVER["DOCUMENT_ROOT"].$optimFName);

		if($unique && $optimFileExist)
		{
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$infoFile))
			{
				include($_SERVER['DOCUMENT_ROOT'].$infoFile);

				if(is_array($arFilesInfo['FILES']))
				{
					foreach($arFile as $key => $fp)
					{
						$fp = $_SERVER['DOCUMENT_ROOT'].$fp;
						if(isset($arFilesInfo['FILES'][$fp]))
						{
							if($_SERVER['DOCUMENT_ROOT'].$arSrcFile[$key] != $fp.'?'.$arFilesInfo['FILES'][$fp])
							{
								$upOptim = 'NEW';
								break;
							}
							else
							{
								$unsetKey[] = $key;
							}
						}
						else
						{
							$upOptim = 'UP';
						}
					}
				}
				else
				{
					$upOptim = 'NEW';
				}
			}
			else
			{
				$upOptim = 'NEW';
			}
		}

		if(!$optimFileExist || $upOptim == 'NEW')
		{
			$upOptim = 'NEW';
			$arFilesInfo = array();
			$arFilesInfo['CUR_SEL_CNT'] = 0;
			$arFilesInfo['CUR_IE_CNT'] = 0;
			$arFilesInfo['FILES'] = array();

			$writeResult = false;

			if($unique)
				DeleteDirFilesEx($optimPath);
		}

		$contents = '';
		$arIEContent = array();

		if($upOptim != 'NO')
		{
			if($upOptim == 'UP' && $optimFileExist)
			{
				foreach($unsetKey as $key)
					unset($arFile[$key]);

				if($noCheckOnly)
				{
					$contents .= "\n\n".file_get_contents($_SERVER["DOCUMENT_ROOT"].$optimFName);

					if($type == 'css')
					{
						if($arFilesInfo['CUR_SEL_CNT'] < $maxAddCssSelect)
						{
							$css = str_replace('#CNT#', $arFilesInfo['CUR_IE_CNT'], $cssFNameIE);
							if(file_exists($_SERVER["DOCUMENT_ROOT"].$css))
							{
								$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= file_get_contents($_SERVER["DOCUMENT_ROOT"].$css);
								$arFilesInfo['CUR_SEL_CNT'] = self::__GetCssSelectCnt($arIEContent[$arFilesInfo['CUR_IE_CNT']]);
							}
						}
						elseif($arFilesInfo['CUR_SEL_CNT'] >= $maxAddCssSelect)
						{
							$arFilesInfo['CUR_IE_CNT']++;
							$arFilesInfo['CUR_SEL_CNT'] = 0;
						}
					}
				}
				else
				{
					$writeResult = false;
				}
			}

			if($noCheckOnly)
			{
				foreach($arFile as $key => $filename)
				{
					$filename = $_SERVER['DOCUMENT_ROOT'].$filename;
					$tmp_content = file_get_contents($filename);
					if($tmp_content !== '')
					{
						if($type == 'css')
						{
							$f_cnt = self::__GetCssSelectCnt($tmp_content);
							$new_cnt = $f_cnt + $arFilesInfo['CUR_SEL_CNT'];

							$tmp_content = self::FixCssIncludes($tmp_content, $arSrcFile[$key]);
							$tmp_content = "\n/* Start:".$arFile[$key]."*/\n".$tmp_content."\n/* End */\n";

							if($new_cnt < $maxCssSelect)
							{
								$arFilesInfo['CUR_SEL_CNT'] = $new_cnt;
								$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= $tmp_content;
							}
							else
							{
								$arFilesInfo['CUR_SEL_CNT'] = $f_cnt;
								$arFilesInfo['CUR_IE_CNT']++;
								$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= $tmp_content;
							}

							$contents .= "\n\n".$tmp_content;
						}
						else
						{
							$contents .= "\n; /* Start:".$arFile[$key]."*/\n".$tmp_content."\n/* End */\n;";
						}
					}

					if($unique)
						$arFilesInfo['FILES'][$filename] = self::__GetCssJsTime($arSrcFile[$key]);
				}

				if(($writeResult = self::__WriteCssJsCache($optimFName, $contents)) && $unique)
				{
					$cacheInfo = '<? $arFilesInfo = array( \'FILES\' => array(';

					foreach($arFilesInfo['FILES'] as $key => $time)
						$cacheInfo .= "'".EscapePHPString($key)."' => '".intval($time)."',";

					$cacheInfo .= "), 'CUR_SEL_CNT' => '".$arFilesInfo['CUR_SEL_CNT']."', 'CUR_IE_CNT' => '".$arFilesInfo['CUR_IE_CNT']."'); ?>";
					self::__WriteCssJsCache($infoFile, $cacheInfo, false);
				}

				if($type == 'css' && $writeResult)
				{
					foreach($arIEContent as $key => $ieContent)
					{
							$css = str_replace('#CNT#', $key, $cssFNameIE);
							self::__WriteCssJsCache($css, $ieContent);
					}
				}

				unset($contents, $arIEContent);
			}
		}

		if($type == 'css' && $isIE && $writeResult)
		{
			for($i = 0; $i <= $arFilesInfo['CUR_IE_CNT']; $i++)
			{
				$css = str_replace('#CNT#', $i, $cssFNameIE);
				$res .= '<link href="'.CUtil::GetAdditionalFileURL($css).'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";
			}
		}
		else
		{
			if($type == 'css')
			{
				if($writeResult || !$writeResult && $unique && $upOptim == 'UP')
					$res .= '<link href="'.CUtil::GetAdditionalFileURL($optimFName).'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";

				if(!$writeResult)
				{
					if($isIE)
					{
						$cnt = 0;
						$res_content = '';
						$ruleCount = 0;
						$cMaxStylesCnt = COption::GetOptionInt('main', 'max_css_files', 15);

						foreach($arFile as $key => $src)
						{
							if($cnt < $cMaxStylesCnt)
							{
								$res .= '<link href="'.$arSrcFile[$key].'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";
							}
							elseif(file_exists($_SERVER['DOCUMENT_ROOT'].$arFile[$key]) && filesize($_SERVER['DOCUMENT_ROOT'].$arFile[$key]) > 0)
							{
								$arTmp = $this->__ShowInlineCssIE($_SERVER['DOCUMENT_ROOT'].$arFile[$key], $arFile[$key], $ruleCount);
								$ruleCount = $arTmp['CNT'];
								$res_content .= $arTmp['CONTENT'];
							}
							$cnt++;
						}

						if($res_content != '')
							$res .= '<style type="text/css">'."\n".$res_content."\n</style>\n";
					}
					else
					{
						foreach($arFile as $key => $src)
							$res .= '<link href="'.$arSrcFile[$key].'" type="text/css" rel="stylesheet"'.($bXhtmlStyle? ' /':'').'>'."\n";
					}
				}
			}
			else
			{
				if($writeResult || !$writeResult && $unique && $upOptim == 'UP')
					$res .= '<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL($optimFName).'"></script>'."\n";

				if(!$writeResult)
				{
					foreach ($arFile as $key => $src)
					{
						$res .= '<script type="text/javascript" src="'.$arSrcFile[$key].'"></script>'."\n";
					}
				}

				if($unique && $sufix == 'kernel')
				{
					$arF = array_keys($arFilesInfo['FILES']);
					foreach ($arF as $key => $item)
					{
						$arF[$key] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $item);
					}
					$res .= '<script type="text/javascript">'." BX.setKernelJS(['".implode("','", $arF)."']); </script>";
				}
			}
		}

		if($unique && $sufix == 'kernel' && $type == 'css')
		{
			$arF = array_keys($arFilesInfo['FILES']);
			foreach ($arF as $key => $item)
			{
				$arF[$key] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $item);
			}
			$res .= '<script type="text/javascript">'." var arKernelCSS = new Array('".implode("','", $arF)."'); </script>";
		}

		unset($arFile, $arSrcFile);
		return $res;
	}

	/**
	 * Write optimized css, js files or info file
	 *
	 * @param string $file_path - Path for optimized css, js or info file
	 * @param string $contents - File contents
	 * @param bool $gzip_content - For disabled gzip
	 * @return bool - TRUE or FALSE result
	 */
	function __WriteCssJsCache($file_path, $contents, $gzip_content = true)
	{
		$result = false;

		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$file_path);

		if(!$handle = @fopen($_SERVER["DOCUMENT_ROOT"].$file_path.'.tmp', "wb"))
			return false;

		if(($written = @fwrite($handle, $contents)) === false)
			return false;

		$len = function_exists('mb_strlen') ? mb_strlen($contents, 'latin1'): strlen($contents);
		fclose($handle);

		if($written === $len)
		{
			//This checks for Zend Server CE in order to supress warnings
			if(function_exists('accelerator_reset'))
				@unlink($_SERVER["DOCUMENT_ROOT"].$file_path);
			elseif(file_exists($_SERVER["DOCUMENT_ROOT"].$file_path))
				@unlink($_SERVER["DOCUMENT_ROOT"].$file_path);

			if(@rename($_SERVER["DOCUMENT_ROOT"].$file_path.'.tmp', $_SERVER["DOCUMENT_ROOT"].$file_path) === false)
				return false;

			$result = true;

			if(
				$gzip_content && COption::GetOptionString('main', 'compres_css_js_files', 'N') == 'Y' &&
				extension_loaded('zlib') && function_exists('gzcompress')
			)
			{
				$tmpGz = $_SERVER["DOCUMENT_ROOT"].$file_path.'.tmp.gz';
				if($gz = gzopen($tmpGz, 'wb9f'))
				{
					$written = @gzwrite ($gz, $contents);
					gzclose ($gz);
					$len = function_exists('mb_strlen') ? mb_strlen($contents, 'latin1'): strlen($contents);
					if($written === $len)
						@rename($tmpGz, $_SERVER["DOCUMENT_ROOT"].$file_path.'.gz');
				}
			}
		}
		return $result;
	}

	/**
	 * Show css inline for IE
	 *
	 * @param string $filename - Full path for a source css file
	 * @param $css_path - Path to css without document root, Include timestamp
	 * @param int $ruleCount - Current css selector count
	 * @return array - Return array(cnt - current css selector count, content - css content)
	 */
	public static function __ShowInlineCssIE($filename, $css_path, $ruleCount)
	{
		$res_content = '';
		if(file_exists($filename) && filesize($filename) > 0)
		{
			$contents = file_get_contents($filename);
			if($contents != '')
			{
				$contents = self::FixCssIncludes($contents, $css_path);

				$ruleCountOld = $ruleCount;
				$c = self::__GetCssSelectCnt($contents);
				$ruleCount += $c;
				if($ruleCount > 4000)
				{
					$ruleCount = $c;
					if($ruleCountOld > 0)
					{
						$res_content .= '</style>'."\n".'<style type="text/css">';
					}
				}
				$res_content .= "\n".$contents."\n";
			}
		}

		return array('CNT' => $ruleCount, 'CONTENT' => $res_content);
	}

	static function __GetCssJsTime($css_file)
	{
		$qpos = strpos($css_file, '?');
		if($qpos === false)
			return false;
		$qpos++;
		return intval(substr($css_file, $qpos));
	}

	static function __GetCssSelectCnt($css)
	{
		$matches = array();
		$cnt = preg_match_all("#[^,{]+\\s*(?:\\{[^}]*\\}\\s*;?|,)#is", $css, $matches);
		unset($matches);

		return intval($cnt);
	}

	static function __ReplaceUrlCSS($url, $cssPath)
	{
		if(strpos($url, "://") !== false || strpos($url, "data:") !== false)
			return $url;
		$url = trim(stripslashes($url), "'\" \r\n\t");
		if(substr($url, 0, 1) == "/")
			return $url;
		return "'".$cssPath.'/'.$url."'";
	}

	
	/**
	 * <p>Отображает CSS страницы. <br><br> Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, задавать CSS страницы (например, внутри компонента) уже после того как был выведен пролог сайта.</p>
	 *
	 *
	 *
	 *
	 * @param bool $external = true Если значение - "true", то выводится HTML представляющий из себя ссылку
	 * на внешний CSS, например: <br><pre>&lt;LINK
	 * href="http://dev.1c-bitrix.rubitrix/templates/demo/styles.css" type="text/css" rel="stylesheet"&gt;</pre> Если
	 * значение "false", то выводится HTML представляющий из себя внутренний
	 * CSS, например: <pre>&lt;style type="text/css"&gt; body { margin: 0px; padding:0px; background-color: #FFFFFF}
	 * ... &lt;/style&gt;</pre> Исключение составляет CSS стили лежащие в каталоге
	 * /bitrix/modules/, они всегда подключаются как внутренний CSS (как правило
	 * это используется в стандартных компонентах).
	 *
	 *
	 *
	 * @param bool $bXhtmlStyle = true Параметр, устанавливающий, по какому стандарту оформляются
	 * HTML-теги. Если значение <i>true</i>, то теги выводятся по стандарту XHTML
	 * (&lt;link /&gt;), иначе по стандарту HTML 4 (&lt;link&gt;). Параметр появился в
	 * версии 8.5.3 ядра. Необязательный, по умолчанию <i>true</i>.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?= LANG_CHARSET;?&gt;"&gt;
	 * &lt;META NAME="ROBOTS" content="ALL"&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("keywords")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("description")?&gt;
	 * &lt;title&gt;&lt;?$APPLICATION-&gt;ShowTitle()?&gt;&lt;/title&gt;
	 * &lt;?<b>$APPLICATION-&gt;ShowCSS</b>();?&gt;
	 * &lt;/head&gt;
	 * &lt;body link="#525252" alink="#F1555A" vlink="#939393" text="#000000"&gt;
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcss.php">CMain::GetCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/settemplatecss.php">CMain::SetTemplateCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setadditionalcss.php">CMain::SetAdditionalCSS</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showcss.php
	 * @author Bitrix
	 */
	public static function ShowCSS($cMaxStylesCnt=true, $bXhtmlStyle=true)
	{
		$this->AddBufferContent(array(&$this, "GetCSS"), $cMaxStylesCnt, $bXhtmlStyle);
	}

	
	/**
	 * <p>Функция добавляет строку в секцию &lt;head&gt;…&lt;/head&gt; сайта.</p>
	 *
	 *
	 *
	 *
	 * @param $st $r  строка, которая будет добавлена в секцию …
	 *
	 *
	 *
	 * @param $bUniqu $e = false если <b>true</b> и такая строка уже добавлена в секцию <i>&lt;head&gt;</i>, то
	 * она не будет продублирована. Если <b>false</b>, то строка будет
	 * добавлена в секцию <i>&lt;head&gt;</i> без проверки на уникальность. <br>
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Добавим файл стилей <b>style.css</b> из текущего каталога.&lt;?$APPLICATION-&gt;AddHeadString('&lt;link href=".$APPLICATION-&gt;GetCurDir()."style.css";  type="text/css" rel="stylesheet" /&gt;',true)?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addheadstring.php
	 * @author Bitrix
	 */
	public static function AddHeadString($str, $bUnique=false, $additional=false)
	{
		if($str <> '')
		{
			if($bUnique)
			{
				$check_sum = md5($str);
				if($additional)
				{
					if(!array_key_exists($check_sum, $this->arHeadAdditionalStrings))
						$this->arHeadAdditionalStrings[$check_sum] = $str;
				}
				else
				{
					if(!array_key_exists($check_sum, $this->arHeadStrings))
						$this->arHeadStrings[$check_sum] = $str;
				}
			}
			else
			{
				if($additional)
					$this->arHeadAdditionalStrings[] = $str;
				else
					$this->arHeadStrings[] = $str;
			}
		}
	}

	public static function GetHeadStrings($additional=false)
	{
		if($additional)
		{
			return implode("\n", $this->arHeadAdditionalStrings)."\n";
		}
		else
		{
			$res = '';
			if ($this->IsJSOptimized())
				$res .= $this->GetHeadScripts(1);

			return $res.implode("\n", $this->arHeadStrings)."\n";
		}
	}

	
	/**
	 * <p>Отображает специальные стили, JavaScript либо произвольный html-код. <br><br> Функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a> и используется в шаблоне сайта для вывода произвольного кода. Такой код задается, например, в компонентах с помощью CMain::AddHeadString().</p> <p>ShowHeadStrings - аналог функций ShowMeta, ShowTitle, ShowCSS, только более универсальная.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?=LANG_CHARSET;?&gt;" /&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("robots")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("keywords")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("description")?&gt;
	 * &lt;title&gt;&lt;?$APPLICATION-&gt;ShowTitle()?&gt;&lt;/title&gt;
	 * &lt;?$APPLICATION-&gt;ShowCSS();?&gt;
	 * &lt;?<b>$APPLICATION-&gt;ShowHeadStrings()</b>?&gt;
	 * &lt;?$APPLICATION-&gt;ShowHeadScripts()?&gt;
	 * &lt;/head&gt;
	 * &lt;body&gt;
	 * ...
	 * Рассмотрим пример использования CMain::AddHeadString(). В файле \bitrix\modules\main\include\epilog_after.php используется код: ...
	 * if($bShowStat &amp;&amp; !$USER-&gt;IsAuthorized())
	 * {
	 * require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
	 * $GLOBALS["APPLICATION"]-&gt;AddHeadString($GLOBALS["adminPage"]-&gt;ShowScript());
	 * $GLOBALS["APPLICATION"]-&gt;AddHeadString('&lt;script type="text/javascript" src="/bitrix/js/main/public_tools.js"&gt;&lt;/script&gt;');
	 * $GLOBALS["APPLICATION"]-&gt;AddHeadString('&lt;link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/pubstyles.css" /&gt;');
	 * }
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li>CMain::AddHeadString() </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showheadstrings.php
	 * @author Bitrix
	 */
	public static function ShowHeadStrings()
	{
		if(!$this->bShowHeadString)
		{
			$this->bShowHeadString = true;
			$this->AddBufferContent(array(&$this, "GetHeadStrings"), true);
			$this->AddBufferContent(array(&$this, "GetHeadStrings"), false);
		}
	}

	
	/**
	 * <p>Подключает java скрипты в шаблоне сайта и в шаблоне компонентов. Порядок их включения в страницу и порядок при объединении - соответствует порядку вызовов API. Исключение: в случае объединения вначале сгруппируются скрипты от ядра, а потом выведутся скрипты шаблона и страницы.</p>
	 *
	 *
	 *
	 *
	 * @param $sr $c  путь к скрипту от корня сайта
	 *
	 *
	 *
	 * @param $additiona $l = false  
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addheadscript.php
	 * @author Bitrix
	 */
	public static function AddHeadScript($src, $additional=false)
	{
		if($src <> '')
		{
			if($additional)
				$this->arHeadAdditionalScripts[] = $src;
			else
				$this->arHeadScripts[] = $src;
		}
	}

	public static function AddLangJS($content)
	{
		if($content <> '')
			$this->arLangJS[] = $content;
	}

	public static function GetLangJS()
	{
		return implode("\n", $this->arLangJS)."\n";
	}

	public static function AddAdditionalJS($content)
	{
		if($content <> '')
			$this->arAdditionalJS[] = $content;
	}

	public static function GetAdditionalJS()
	{
		return implode("\n", $this->arAdditionalJS)."\n";
	}

	public static function IsCSSOptimized()
	{
		return defined('SITE_TEMPLATE_ID') && COption::GetOptionString('main', 'optimize_css_files', 'N') == 'Y' && !$this->bInAjax;
	}

	public static function IsJSOptimized()
	{
		return defined('SITE_TEMPLATE_ID') && COption::GetOptionString('main', 'optimize_js_files', 'N') == 'Y' && !$this->bInAjax;
	}

	public static function GetHeadScripts($type = 0)
	{
		$res = "";
		$optimJS = $this->IsJSOptimized();

		$additionalJS = array_unique($this->arHeadAdditionalScripts);

		if($optimJS)
			$arScripts = array_unique($this->arHeadScripts);
		else
			$arScripts = array_merge(array_unique($this->arHeadScripts),$additionalJS);

		static $firstExec = true;
		static $arJS = array('KERNEL' => '', 'TEMPLATE' => '', 'PAGE' => '', 'OTHERS' => '');
		$type = intval($type);

		if($type == 1 && $this->bShowHeadString && !$this->bShowHeadScript)
			$type = 0;

		if($firstExec && $optimJS)
		{
			$arBxFile = array();
			$arSrcBxFile = array();

			$arTemplateFile = array();
			$arSrcTemplateFile = array();

			$arPageFile = array();
			$arSrcPageFile = array();
		}

		if(!$optimJS || $firstExec)
		{
			foreach($arScripts as $jsKey => $src)
			{
				$bExternalLink = (strncmp($src, 'http://', 7) == 0 || strncmp($src, 'https://', 8) == 0);

				if(!$bExternalLink)
				{
					if(strpos($src, '?') === false)
						$src = CUtil::GetAdditionalFileURL($src);
				}

				if(!$bExternalLink && $optimJS)
				{
					if(strncmp($src, '/bitrix/js/', 11) != 0)
					{
						if($jsKey > $this->iHeaderLastJs && $jsKey <= $this->iWorkAreaLastJs)
						{
							$arPageFile[] = $arScripts[$jsKey];
							$arSrcPageFile[] = $src;
						}
						else
						{
							$arTemplateFile[] = $arScripts[$jsKey];
							$arSrcTemplateFile[] = $src;
						}
					}
					else
					{
						$arBxFile[] = $arScripts[$jsKey];
						$arSrcBxFile[] = $src;
					}
				}
				else
				{
					if($optimJS)
						$arJS['PAGE'] .= '<script type="text/javascript" src="'.$src.'"></script>'."\n";
					else
						$res .= '<script type="text/javascript" src="'.$src.'"></script>'."\n";
				}
			}
		}

		if($optimJS)
		{
			if($firstExec)
			{
				$arJS['KERNEL'] .= '<script type="text/javascript">if(!window.BX)window.BX={message:function(mess){if(typeof mess==\'object\') for(var i in mess) BX.message[i]=mess[i]; return true;}};</script>';
				$arJS['KERNEL'] .= $this->GetLangJS();
				$arJS['KERNEL'] .= $this->__OptimizeCssJs($arBxFile, $arSrcBxFile, SITE_TEMPLATE_ID, true, 'kernel', 'js');
				$arJS['KERNEL'] .= $this->GetAdditionalJS();

				$arTemplateFile = array_merge($arTemplateFile, $additionalJS);
				$arSrcTemplateFile  = array_merge($arSrcTemplateFile, $additionalJS);

				$arJS['TEMPLATE'] .= $this->__OptimizeCssJs($arTemplateFile, $arSrcTemplateFile, SITE_TEMPLATE_ID, false, 'template', 'js');
				$arJS['PAGE'] .= $this->__OptimizeCssJs($arPageFile, $arSrcPageFile, SITE_TEMPLATE_ID, false, 'page', 'js');

				$firstExec = false;
			}

			switch ($type)
			{
				case 1:
					return $arJS['KERNEL']."\n";
					break;
				case 2:
					return $arJS['TEMPLATE']."\n".$arJS['PAGE'];
					break;
				default:
					return implode("\n", $arJS);
			}
		}

		return $res;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showheadscripts.php
	 * @author Bitrix
	 */
	public static function ShowHeadScripts()
	{
		$this->bShowHeadScript = true;
		$this->AddBufferContent(array(&$this, "GetHeadScripts"), 2);
	}

	
	/**
	 * <p>Функция предназначена для вывода в шаблоне сайта основных полей тега &lt;head&gt;: мета-теги Content-Type, robots, keywords, description; стили CSS; скрипты, заданные через CMain::AddHeadScript.</p>
	 *
	 *
	 *
	 *
	 * @param bool $bXhtmlStyle = true Параметр, устанавливающий, по какому стандарту оформляются
	 * HTML-теги. Если значение <i>true</i>, то теги выводятся по стандарту XHTML
	 * (&lt;meta /&gt;), иначе по стандарту HTML 4 (&lt;meta&gt;). Параметр появился в
	 * версии 8.5.3 ядра. Необязательный, по умолчанию <i>true</i>.
	 *
	 *
	 *
	 * @return mixed <a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;head&gt;
	 * &lt;?<strong>$APPLICATION-&gt;ShowHead();</strong>?&gt;
	 * &lt;title&gt;&lt;?$APPLICATION-&gt;ShowTitle()?&gt;&lt;/title&gt;
	 * &lt;/head&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/ShowHead.php
	 * @author Bitrix
	 */
	public static function ShowHead($bXhtmlStyle=true)
	{
		echo '<meta http-equiv="Content-Type" content="text/html; charset='.LANG_CHARSET.'"'.($bXhtmlStyle? ' /':'').'>'."\n";
		$this->ShowMeta("robots", false, $bXhtmlStyle);
		$this->ShowMeta("keywords", false, $bXhtmlStyle);
		$this->ShowMeta("description", false, $bXhtmlStyle);
		$this->ShowCSS(true, $bXhtmlStyle);
		$this->ShowHeadStrings();
		$this->ShowHeadScripts();
	}

	public static function ShowAjaxHead($bXhtmlStyle=true)
	{
		$this->RestartBuffer();

		$this->sPath2css = array();
		$this->arAdditionalJS = array();
		$this->arHeadAdditionalCss = array();
		$this->arHeadAdditionalStrings = array();
		$this->arHeadAdditionalScripts = array();
		$this->arHeadScripts = array();
		$this->arHeadStrings = array();
		$this->arLangJS = array();
		$this->iHeaderLastCss = 0;
		$this->iHeaderLastJs = 0;
		$this->iWorkAreaLastCss = 0;
		$this->iWorkAreaLastJs = 0;

		$this->bInAjax = true;

		$this->ShowCSS(true, $bXhtmlStyle);
		$this->ShowHeadStrings();
		$this->ShowHeadScripts();
	}

	public static function SetShowIncludeAreas($bShow=true)
	{
		$_SESSION["SESS_INCLUDE_AREAS"] = $bShow;
	}

	
	/**
	 * <p>Возвращает "true", если кнопка "Показать включаемые области" на <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">панели управления</a> нажата, в противном случае - "false".</p>
	 *
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $text = "Произвольный HTML";
	 * 
	 * // если кнопка "Показать включаемые области" нажата, то
	 * if (<b>$APPLICATION-&gt;GetShowIncludeAreas</b>())
	 * {
	 *     $arIcons = Array();
	 *     $arIcons[] =
	 *             Array(
	 *                 "URL" =&gt; "/bitrix/admin/my_script1.php",
	 *                 "SRC" =&gt; "/images/my_icon1.gif",
	 *                 "ALT" =&gt; "Текст всплывающей подсказки"
	 *             );
	 *     $arIcons[] =
	 *             Array(
	 *                 "URL" =&gt; "/bitrix/admin/my_script2.php",
	 *                 "SRC" =&gt; "/images/my_icon2.gif",
	 *                 "ALT" =&gt; "Текст всплывающей подсказки"
	 *             );
	 * 
	 *     // выведется надпись "Произвольный HTML" обрамленная рамкой,
	 *     // в правом верхнем углу которой будут две иконки my_icon1.gif и my_icon2.gif
	 *     // с заданными на них ссылками на скрипты my_script1.php и my_script2.php
	 * 
	 *     echo $APPLICATION-&gt;IncludeString($text, $arIcons);
	 * }
	 * else 
	 * {
	 *     // иначе просто выводим надпись "Произвольный HTML"
	 *     echo $text;
	 * }
	 * ?&gt;
	 * &lt;?
	 * // файл /bitrix/modules/advertising/classes/general/advertising.php 
	 * // класс CAdvBanner
	 * 
	 * // возвращает HTML произвольного баннера по типу
	 * function Show($TYPE_SID, $HTML_BEFORE="", $HTML_AFTER="")
	 * {
	 *     global $APPLICATION, $USER;
	 *     $arBanner = CAdvBanner::GetRandom($TYPE_SID);
	 *     $strReturn = CAdvBanner::GetHTML($arBanner);
	 *     if (strlen($strReturn)&gt;0)
	 *     {
	 *         CAdvBanner::FixShow($arBanner);
	 *         if (<b>$APPLICATION-&gt;GetShowIncludeAreas</b>())
	 *         {
	 *             $isDemo = CAdvContract::IsDemo();
	 *             $arrPERM = CAdvContract::GetUserPermissions($arBanner["CONTRACT_ID"]);
	 *             if (($isDemo || (is_array($arrPERM) &amp;&amp; count($arrPERM)&gt;0)) &amp;&amp; $USER-&gt;IsAuthorized())
	 *             {
	 *                 $arIcons = Array();
	 *                 $arIcons[] =
	 *                         Array(
	 *                             "URL" =&gt; "/bitrix/admin/adv_banner_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$arBanner["ID"]. "&amp;CONTRACT_ID=".$arBanner["CONTRACT_ID"],
	 *                             "SRC" =&gt; "/bitrix/images/advertising/panel/edit_ad.gif",
	 *                             "ALT" =&gt; GetMessage("AD_PUBLIC_ICON_EDIT_BANNER")
	 *                         );
	 *                 $arIcons[] =
	 *                         Array(
	 *                             "URL" =&gt; "/bitrix/admin/adv_banner_list.php?lang=".LANGUAGE_ID."&amp;find_id=".$arBanner["ID"]. "&amp;find_id_exact_match=Y&amp;find_contract_id[]=".$arBanner["CONTRACT_ID"]. "&amp;find_type_sid[]=".$arBanner["TYPE_SID"]."&amp;set_filter=Y",
	 *                             "SRC" =&gt; "/bitrix/images/advertising/panel/edit_ad_list.gif",
	 *                             "ALT" =&gt; GetMessage("AD_PUBLIC_ICON_BANNER_LIST")
	 *                         );
	 *                 $strReturn = $APPLICATION-&gt;IncludeString($strReturn, $arIcons);
	 *             }
	 *         }
	 *         $strReturn = $HTML_BEFORE.$strReturn.$HTML_AFTER;
	 *         return $strReturn;
	 *     }
	 *     else return false;
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/includestring.php">CMain::IncludeString</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getshowincludeareas.php
	 * @author Bitrix
	 */
	public static function GetShowIncludeAreas()
	{
		global $USER;

		if(!$USER->IsAuthorized() || defined('ADMIN_SECTION') && ADMIN_SECTION == true)
			return false;
		if(isset($_SESSION["SESS_INCLUDE_AREAS"]) && $_SESSION["SESS_INCLUDE_AREAS"])
			return true;
		static $panel_dynamic_mode = null;
		if (!isset($panel_dynamic_mode))
		{
			$aUserOpt = CUserOptions::GetOption("global", "settings", array());
			$panel_dynamic_mode = (isset($aUserOpt["panel_dynamic_mode"]) && $aUserOpt["panel_dynamic_mode"] == "Y");
		}
		return $panel_dynamic_mode;
	}

	public static function SetPublicShowMode($mode)
	{
		$this->SetShowIncludeAreas($mode != 'view');
	}

	
	/**
	 * <p>Функция возвращает текущий режим отображения административной панели.</p>
	 *
	 *
	 *
	 *
	 * @return string <p>Одно из следующих зачений:</p><ul> <li>view - просмотр (по умолчанию) <br>
	 * </li> <li>edit - редактирование <br> </li> <li>configure - редактирование <br> </li>
	 * </ul><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/GetPublicShowMode.php
	 * @author Bitrix
	 */
	public static function GetPublicShowMode()
	{
		return $this->GetShowIncludeAreas() ? 'configure' : 'view';
	}

	public static function SetEditArea($areaId, $arIcons)
	{
		if(!$this->GetShowIncludeAreas())
			return;

		if($this->editArea === false)
			$this->editArea = new CEditArea();

		$this->editArea->SetEditArea($areaId, $arIcons);
	}

	public static function IncludeStringBefore()
	{
		if($this->editArea === false)
			$this->editArea = new CEditArea();
		return $this->editArea->IncludeStringBefore();
	}

	public static function IncludeStringAfter($arIcons=false, $arParams=array())
	{
		return $this->editArea->IncludeStringAfter($arIcons, $arParams);
	}

	
	/**
	 * <p>Выводит произвольную строку (HTML код) обрамленную рамкой, в правом верхнем углу которой выводятся заданные иконки.</p>
	 *
	 *
	 *
	 *
	 * @param string $text  Произвольный текст (HTML код).
	 *
	 *
	 *
	 * @param array $icons = array() Массив иконок, каждый элемент которого представляет из себя
	 * массив описывающий одну иконку, его ключами являются: <ul> <li> <b>URL</b>
	 * - ссылка на иконке </li> <li> <b>SRC</b> - путь к изображению иконки </li> <li>
	 * <b>ALT</b> - текст всплывающей подсказки на иконке </li> </ul>
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $text = "Произвольный HTML";
	 * 
	 * // если кнопка "Показать включаемые области" нажата, то
	 * if ($APPLICATION-&gt;GetShowIncludeAreas())
	 * {
	 *     $arIcons = Array();
	 *     $arIcons[] =
	 *             Array(
	 *                 "URL" =&gt; "/bitrix/admin/my_script1.php",
	 *                 "SRC" =&gt; "/images/my_icon1.gif",
	 *                 "ALT" =&gt; "Текст всплывающей подсказки"
	 *             );
	 *     $arIcons[] =
	 *             Array(
	 *                 "URL" =&gt; "/bitrix/admin/my_script2.php",
	 *                 "SRC" =&gt; "/images/my_icon2.gif",
	 *                 "ALT" =&gt; "Текст всплывающей подсказки"
	 *             );
	 * 
	 *     // выведется надпись "Произвольный HTML" обрамленная рамкой,
	 *     // в правом верхнем углу которой будут две иконки my_icon1.gif и my_icon2.gif
	 *     // с заданными на них ссылками на скрипты my_script1.php и my_script2.php
	 * 
	 *     echo <b>$APPLICATION-&gt;IncludeString</b>($text, $arIcons);
	 * }
	 * else 
	 * {
	 *     // иначе просто выводим надпись "Произвольный HTML"
	 *     echo $text;
	 * }
	 * ?&gt;
	 * &lt;?
	 * // файл /bitrix/modules/advertising/classes/general/advertising.php 
	 * // класс CAdvBanner
	 * 
	 * // возвращает HTML произвольного баннера по типу
	 * function Show($TYPE_SID, $HTML_BEFORE="", $HTML_AFTER="")
	 * {
	 *     global $APPLICATION, $USER;
	 *     $arBanner = CAdvBanner::GetRandom($TYPE_SID);
	 *     $strReturn = CAdvBanner::GetHTML($arBanner);
	 *     if (strlen($strReturn)&gt;0)
	 *     {
	 *         CAdvBanner::FixShow($arBanner);
	 *         if ($APPLICATION-&gt;GetShowIncludeAreas())
	 *         {
	 *             $isDemo = CAdvContract::IsDemo();
	 *             $arrPERM = CAdvContract::GetUserPermissions($arBanner["CONTRACT_ID"]);
	 *             if (($isDemo || (is_array($arrPERM) &amp;&amp; count($arrPERM)&gt;0)) &amp;&amp; $USER-&gt;IsAuthorized())
	 *             {
	 *                 $arIcons = Array();
	 *                 $arIcons[] =
	 *                         Array(
	 *                             "URL" =&gt; "/bitrix/admin/adv_banner_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$arBanner["ID"]. "&amp;CONTRACT_ID=".$arBanner["CONTRACT_ID"],
	 *                             "SRC" =&gt; "/bitrix/images/advertising/panel/edit_ad.gif",
	 *                             "ALT" =&gt; GetMessage("AD_PUBLIC_ICON_EDIT_BANNER")
	 *                         );
	 *                 $arIcons[] =
	 *                         Array(
	 *                             "URL" =&gt; "/bitrix/admin/adv_banner_list.php?lang=".LANGUAGE_ID."&amp;find_id=".$arBanner["ID"]. "&amp;find_id_exact_match=Y&amp;find_contract_id[]=".$arBanner["CONTRACT_ID"]. "&amp;find_type_sid[]=".$arBanner["TYPE_SID"]."&amp;set_filter=Y",
	 *                             "SRC" =&gt; "/bitrix/images/advertising/panel/edit_ad_list.gif",
	 *                             "ALT" =&gt; GetMessage("AD_PUBLIC_ICON_BANNER_LIST")
	 *                         );
	 *                 $strReturn = <b>$APPLICATION-&gt;IncludeString</b>($strReturn, $arIcons);
	 *             }
	 *         }
	 *         $strReturn = $HTML_BEFORE.$strReturn.$HTML_AFTER;
	 *         return $strReturn;
	 *     }
	 *     else return false;
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getshowincludeareas.php">CMain::GetShowIncludeAreas</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/includestring.php
	 * @author Bitrix
	 */
	public static function IncludeString($string, $arIcons=false)
	{
		return $this->IncludeStringBefore().$string.$this->IncludeStringAfter($arIcons);
	}

	
	/**
	 * <p>Возвращает путь от корня сайта к файлу по пути задаваемому для компонента.</p>
	 *
	 *
	 *
	 *
	 * @param string $component_path  Путь к компоненту.<br><br>Алгоритм поиска пути от корня сайта
	 * следующий: <ol> <li>Сначала файл будет искаться в каталоге
	 * <br><b>/bitrix/templates/</b><i>ID текущего шаблона сайта</i><b>/</b><i>component_path</i> </li>
	 * <li>Если файл не найден, он будет искаться в каталоге
	 * <br><b>/bitrix/templates/.default/</b><i>component_path</i> </li> <li>Затем если файл не найден,
	 * он будет искаться в каталоге <br><b>/bitrix/modules/</b><i>ID
	 * модуля</i><b>/install/templates/</b><i>component_path</i><br> здесь <i>ID модуля</i> - это
	 * первый подкаталог в <i>component_path</i> </li> </ol>
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // получим реальный путь к компоненту
	 * $path = <b>$APPLICATION-&gt;GetTemplatePath</b>("iblock/catalog/element.php");
	 * // в переменной $path может быть например, значение 
	 * // "/bitrix/templates/.default/iblock/catalog/element.php"
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89637]Компоненты[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/includefile.php">CMain::IncludeFile</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/gettemplatepath.php
	 * @author Bitrix
	 */
	public static function GetTemplatePath($rel_path)
	{
		if(substr($rel_path, 0, 1)!="/")
		{
			$path = BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/".$rel_path;
			if(file_exists($_SERVER["DOCUMENT_ROOT"].$path))
				return $path;

			$path = BX_PERSONAL_ROOT."/templates/.default/".$rel_path;
			if(file_exists($_SERVER["DOCUMENT_ROOT"].$path))
				return $path;

			$module_id = substr($rel_path, 0, strpos($rel_path, "/"));
			if(strlen($module_id)>0)
			{
				$path = "/bitrix/modules/".$module_id."/install/templates/".$rel_path;
				if(file_exists($_SERVER["DOCUMENT_ROOT"].$path))
					return $path;
			}

			return false;
		}

		return $rel_path;
	}

	
	/**
	 * <p>Устанавливает CSS стиль для компонента.</p>
	 *
	 *
	 *
	 *
	 * @param string $rel_path  Относительный путь к CSS стилю компонента.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <b>$APPLICATION-&gt;SetTemplateCSS</b>("form/form.css");
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showcss.php">CMain::ShowCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getcss.php">CMain::GetCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setadditionalcss.php">CMain::SetAdditionalCSS</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/settemplatecss.php
	 * @author Bitrix
	 */
	public static function SetTemplateCSS($rel_path)
	{
		if($path = $this->GetTemplatePath($rel_path))
			$this->SetAdditionalCSS($path);
	}

	// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	// COMPONENTS 2.0 >>>>>
	
	/**
	 * <p>Метод подключает компонент 2.0.</p>
	 *
	 *
	 *
	 *
	 * @param string $componentName  Имя компонента. Например: "bitrix:news.detail".
	 *
	 *
	 *
	 * @param string $componentTemplate  Имя шаблона компонента. Если имя пустое, то подразумевается имя
	 * ".default".
	 *
	 *
	 *
	 * @param array $arParams = array() Массив входных параметров компонента.
	 *
	 *
	 *
	 * @param object $parentComponent = null Объект родительского комплексного компонента, если компонент
	 * подключается из шаблона комплексного компонента. В шаблоне
	 * комплексного компонента определена переменная <b>$component</b>,
	 * которая содержит объект этого комплексного компонента.
	 *
	 *
	 *
	 * @param array $arFunctionParams = array() Массив, содержащий дополнительные параметры отображения
	 * компонента: <br> "HIDE_ICONS"=&gt;"Y" - не показывать панель настройки
	 * компонента в режиме редактировани/разработки; <br> "ACTIVE_COMPONENT"=&gt;"N" -
	 * отключить компонент (код компонента не подключается). <br>
	 *
	 *
	 *
	 * @return mixed <p>Возвращает код компонента.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * // Подключим компонент каталога с шаблоном "по-умолчанию" на публичной странице сайта
	 * $APPLICATION-&gt;IncludeComponent(
	 *     "bitrix:catalog",
	 *     "",
	 *     Array(
	 *         "SEF_MODE" =&gt; "N",
	 *         "IBLOCK_TYPE_ID" =&gt; "catalog",
	 *         "ACTION_VARIABLE" =&gt; "action",
	 *         "CACHE_TIME" =&gt; 1*24*60*60,
	 *         "BASKET_PAGE_TEMPLATE" =&gt; "/personal/basket.php",
	 *     )
	 * );
	 * // Подключим компонент карточки фотографии с шаблоном "по-умолчанию" в шаблоне 
	 * // комплексного компонента "фотогалерея"
	 * $APPLICATION-&gt;IncludeComponent(
	 *     "bitrix:photo.detail",
	 *     "",
	 *     Array(
	 *          "IBLOCK_TYPE" =&gt; $arParams["IBLOCK_TYPE"],
	 *          "IBLOCK_ID" =&gt; $arParams["IBLOCK_ID"],
	 *          "ELEMENT_ID" =&gt; $arResult["VARIABLES"]["ELEMENT_ID"],
	 *          "ELEMENT_CODE" =&gt; $arResult["VARIABLES"]["ELEMENT_CODE"],
	 *     ),
	 *     $component
	 * );
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/includecomponent.php
	 * @author Bitrix
	 */
	public static function IncludeComponent($componentName, $componentTemplate, $arParams = array(), $parentComponent = null, $arFunctionParams = array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;

		if(is_array($this->arComponentMatch))
		{
			$skipComponent = true;
			foreach($this->arComponentMatch as $cValue)
			{
				if(strpos($componentName, $cValue) !== false)
				{
					$skipComponent = false;
					break;
				}
			}
			if($skipComponent)
				return false;
		}

		$componentRelativePath = CComponentEngine::MakeComponentPath($componentName);
		if (StrLen($componentRelativePath) <= 0)
			return False;

		if($_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"]=="Y" && ($USER->CanDoOperation('edit_php') || $_SESSION["SHOW_SQL_STAT"]=="Y"))
		{
			$debug = new CDebugInfo();
			$debug->Start();
		}
		elseif($APPLICATION->ShowIncludeStat)
		{
			$debug = new CDebugInfo();
			$debug->Start();
		}
		else
		{
			$debug = null;
		}

		if (is_object($parentComponent))
		{
			if (!($parentComponent instanceof cbitrixcomponent))
				$parentComponent = null;
		}

		$bDrawIcons = ((!isset($arFunctionParams["HIDE_ICONS"]) || $arFunctionParams["HIDE_ICONS"] <> "Y") && $APPLICATION->GetShowIncludeAreas());

		if($bDrawIcons)
			echo $this->IncludeStringBefore();

		$result = null;
		$bComponentEnabled = (!isset($arFunctionParams["ACTIVE_COMPONENT"]) || $arFunctionParams["ACTIVE_COMPONENT"] <> "N");

		$component = new CBitrixComponent();
		if($component->InitComponent($componentName))
		{
			$obAjax = null;
			if($bComponentEnabled)
			{
				if($arParams['AJAX_MODE'] == 'Y')
					$obAjax = new CComponentAjax($componentName, $componentTemplate, $arParams, $parentComponent);

				$result = $component->IncludeComponent($componentTemplate, $arParams, $parentComponent);
			}

			if($bDrawIcons)
			{
				$panel = new CComponentPanel($component, $componentName, $componentTemplate, $parentComponent, $bComponentEnabled);
				$arIcons = $panel->GetIcons();

				echo $s = $this->IncludeStringAfter($arIcons["icons"], $arIcons["parameters"]);
			}

			if($bComponentEnabled && $obAjax)
			{
				$obAjax->Process();
			}
		}

		if($_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"]=="Y" && ($USER->CanDoOperation('edit_php') || $_SESSION["SHOW_SQL_STAT"]=="Y"))
			echo $debug->Output($componentName, "/bitrix/components".$componentRelativePath."/component.php", $arParams["CACHE_TYPE"].$arParams["MENU_CACHE_TYPE"]);
		elseif(is_object($debug))
			$debug->Stop($componentName, "/bitrix/components".$componentRelativePath."/component.php", $arParams["CACHE_TYPE"].$arParams["MENU_CACHE_TYPE"]);


		return $result;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addviewcontent.php
	 * @author Bitrix
	 */
	public static function AddViewContent($view, $content, $pos = 500)
	{
		if(!is_array($this->__view[$view]))
			$this->__view[$view] = array(array($content, $pos));
		else
			$this->__view[$view][] = array($content, $pos);
	}

	
	/**
	 * <p>Метод позволяет установить выводимый контент для функции <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addviewcontent.php">AddViewContent</a>. Применение этих методов позволяет, например, в шаблоне сайта вывести даты отображенных в контентой части новостей. (Для этого достаточно в цикле вывода новостей собрать даты новостей, соединить в одну строку в одну строку и передать в <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addviewcontent.php">AddViewContent</a>). Прежде всего позволяет избежать дублирование компонент и лишних циклов.</p>
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showviewcontent.php
	 * @author Bitrix
	 */
	public static function ShowViewContent($view)
	{
		$this->AddBufferContent(array(&$this, "GetViewContent"), $view);
	}

	public static function GetViewContent($view)
	{
		if(!is_array($this->__view[$view]))
			return '';

		uasort($this->__view[$view], create_function('$a, $b', 'if($a[1] == $b[1]) return 0; return ($a[1] < $b[1])? -1 : 1;'));

		$res = array();
		foreach($this->__view[$view] as $item)
			$res[] = $item[0];

		return implode($res);
	}

	public static function OnChangeFileComponent($path, $site)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		// kind of optimization
		if(!HasScriptExtension($path))
			return;

		$docRoot = CSite::GetSiteDocRoot($site);

		CUrlRewriter::Delete(
			array("SITE_ID" => $site, "PATH" => $path, "ID" => "NULL")
		);

		if (class_exists("\\Bitrix\\Main\\Application", false))
		{
			\Bitrix\Main\Component\ParametersTable::deleteByFilter(
				array("SITE_ID" => $site, "REAL_PATH" => $path)
			);
		}

		$fileSrc = $APPLICATION->GetFileContent($docRoot.$path);
		$arComponents = PHPParser::ParseScript($fileSrc);
		for ($i = 0, $cnt = count($arComponents); $i < $cnt; $i++)
		{
			if (class_exists("\\Bitrix\\Main\\Application", false))
			{
				\Bitrix\Main\Component\ParametersTable::add(
					array(
						'SITE_ID' => $site,
						'COMPONENT_NAME' => $arComponents[$i]["DATA"]["COMPONENT_NAME"],
						'TEMPLATE_NAME' => $arComponents[$i]["DATA"]["TEMPLATE_NAME"],
						'REAL_PATH' => $path,
						'SEF_MODE' => ($arComponents[$i]["DATA"]["PARAMS"]["SEF_MODE"] == "Y") ? \Bitrix\Main\Component\ParametersTable::SEF_MODE : \Bitrix\Main\Component\ParametersTable::NOT_SEF_MODE,
						'SEF_FOLDER' => ($arComponents[$i]["DATA"]["PARAMS"]["SEF_MODE"] == "Y") ? $arComponents[$i]["DATA"]["PARAMS"]["SEF_FOLDER"] : null,
						'START_CHAR' => $arComponents[$i]["START"],
						'END_CHAR' => $arComponents[$i]["END"],
						'PARAMETERS' => serialize($arComponents[$i]["DATA"]["PARAMS"]),
					)
				);
			}

			if (isset($arComponents[$i]["DATA"]["PARAMS"]) && is_array($arComponents[$i]["DATA"]["PARAMS"]))
			{
				if (array_key_exists("SEF_MODE", $arComponents[$i]["DATA"]["PARAMS"])
					&& $arComponents[$i]["DATA"]["PARAMS"]["SEF_MODE"] == "Y")
				{
					CUrlRewriter::Add(
						array(
							"SITE_ID" => $site,
							"CONDITION" => "#^".$arComponents[$i]["DATA"]["PARAMS"]["SEF_FOLDER"]."#",
							"ID" => $arComponents[$i]["DATA"]["COMPONENT_NAME"],
							"PATH" => $path
						)
					);
				}
			}
		}
	}
	// <<<<< COMPONENTS 2.0
	// <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

	// $arParams - do not change!
	
	/**
	 * <p>Данная функция является основной для подключения каких либо файлов в теле страницы, в прологе или в эпилоге и единственной для подключения компонентов. Визуальное содержимое подключенного файла представляет из себя включаемую область.</p>
	 *
	 *
	 *
	 *
	 * @param string $path  Путь к подключаемому файлу. <br><br> Если в данном параметре задан
	 * путь к файлу от корня сайта, то этот файл и будет подключен. Если
	 * такого файла не существует, то при нажатии на панели управления
	 * кнопки "Показать включаемые области", в месте, где указана данная
	 * функция, будет показана голубая иконка, ссылка с которой ведет на
	 * административную страницу создания нового файла по указанному
	 * пути. <br><br> Если же в данном параметре задан путь к основному файлу
	 * компонента, то он будет найден и подключен по следующему
	 * алгоритму: <ol> <li>Сначала файл будет искаться в каталоге
	 * <br><b>/bitrix/templates/</b><i>ID текущего шаблона сайта</i><b>/</b><i>path</i> </li> <li>Если
	 * файл не найден, он будет искаться в каталоге
	 * <br><b>/bitrix/templates/.default/</b><i>path</i> </li> <li>Затем если файл не найден, он
	 * будет искаться дистрибутиве модуля, т.е. в следующем каталоге:
	 * <br><b>/bitrix/modules/</b><i>ID модуля</i><b>/install/templates/</b><i>path</i>, <br> здесь <i>ID
	 * модуля</i> - это первый подкаталог в <i>path</i> </li> </ol>
	 *
	 *
	 *
	 * @param array $params = array() Массив параметров для подключаемого файла. Структура данного
	 * массива: <pre>array( "ИМЯ_ПАРАМЕТРА_1" =&gt; "ЗНАЧЕНИЕ_ПАРАМЕТРА_1",
	 * "ИМЯ_ПАРАМЕТРА_2" =&gt; "ЗНАЧЕНИЕ_ПАРАМЕТРА_2", ...)</pre> В подключаемом
	 * файле будут инициализированы переменные, имена которых - ключи
	 * данного массива, а значения - соответствующие значения данного
	 * массива. Данная операция выполняется стандартной PHP функцией
	 * extract(<i>params</i>).
	 *
	 *
	 *
	 * @param array $function_params = array() Массив настроек данной функции, с ключами: <ul> <li> <b>SHOW_BORDER</b> -
	 * показывать ли рамку и иконки для редактирования, допустимы
	 * следующие значения: <ul> <li> <b>true</b> - показать рамку при нажатии на
	 * панели кнопки "Показать включаемые области" (значение по
	 * умолчанию)</li> <li> <b>false</b> - не показывать рамки</li> </ul> </li> <li> <b>NAME</b> -
	 * текст всплывающей подсказки на иконке редактирования </li> <li>
	 * <b>LANG</b> - двухсимвольный идентификатор языка в котором будет
	 * открыт административный раздел в момент редактирования файла (по
	 * умолчанию - язык текущего сайта) </li> <li> <b>BACK_URL</b> - куда вернуться
	 * после редактирования (по умолчанию - текущая публичная страница)
	 * </li> <li> <b>WORKFLOW</b> - участвует ли подключаемый файл в
	 * документооборте, возможны следующие значения: <ul> <li> <b>true</b> -
	 * ссылка ведущая на редактирование будет указывать на страницу
	 * модуля документооборота </li> <li> <b>false</b> - ссылка ведущая на
	 * редактирование будет указывать на страницу модуля управления
	 * статикой (значение по умолчанию) </li> </ul> </li> <li> <b>MODE</b> - режим
	 * редактирования, допустимы следующие значения: <ul> <li> <b>text</b> - файл
	 * будет редактироваться как текст (ссылка на страницу
	 * редактирования файла в режиме текста) </li> <li> <b>html</b> - файл будет
	 * редактироваться как HTML (ссылка на веб-редактор)(значение по
	 * умолчанию)</li> <li> <b>php</b> - файл будет редактироваться как PHP (ссылка
	 * на страницу редактирования исходников файла) </li> </ul> </li> <li>
	 * <b>TEMPLATE</b> - если в параметре <i>path</i> указан абсолютный путь к
	 * несуществующему файлу, то здесь необходимо указать имя
	 * файла-шаблона для создания нового файла (по умолчанию - первый
	 * шаблон в порядке сортировки задаваемой в файле:
	 * <nobr><b>/bitrix/templates/</b><i>ID текущего шаблона
	 * сайта</i><b>/page_templates/.content.php</b></nobr>) </li> </ul>
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // компонент выводящий детально элемент инфо-блока
	 * <b>$APPLICATION-&gt;IncludeFile</b>("iblock/catalog/element.php", Array(
	 *     "IBLOCK_TYPE"       =&gt; "catalog",                          // Тип инфо-блока
	 *     "IBLOCK_ID"         =&gt; "21",                               // Инфо-блок
	 *     "ELEMENT_ID"        =&gt; $_REQUEST["ID"],                    // ID элемента
	 *     "SECTION_URL"       =&gt; "/catalog/phone/section.php?",      // URL ведущий на страницу с содержимым раздела
	 *     "LINK_IBLOCK_TYPE"  =&gt; "catalog",                          // Тип инфо-блока, элементы которого связаны с текущим элементом
	 *     "LINK_IBLOCK_ID"    =&gt; "22",                               // ID инфо-блока, элементы которого связаны с текущим элементом
	 *     "LINK_PROPERTY_SID" =&gt; "PHONE_ID",                         // Свойство в котором хранится связь
	 *     "LINK_ELEMENTS_URL" =&gt; "/catalog/accessory/byphone.php?",  // URL на страницу где будут показан список связанных элементов
	 *     "arrFIELD_CODE" =&gt; Array(                                  // Поля
	 *          "DETAIL_TEXT",
	 *          "DETAIL_PICTURE"),
	 *     "arrPROPERTY_CODE" =&gt; Array(                               // Свойства
	 *          "YEAR",
	 *          "STANDBY_TIME",
	 *          "TALKTIME",
	 *          "WEIGHT",
	 *          "STANDART",
	 *          "SIZE",
	 *          "BATTERY",
	 *          "SCREEN",
	 *          "WAP",
	 *          "VIBRO",
	 *          "VOICE",
	 *          "PC",
	 *          "MORE_PHOTO",
	 *          "MANUAL"),
	 *     "CACHE_TIME"        =&gt; "3600",                              // Время кэширования (сек.)
	 *     ));
	 * ?&gt;&lt;?
	 * // включаемая область для раздела
	 * <b>$APPLICATION-&gt;IncludeFile</b>($APPLICATION-&gt;GetCurDir()."sect_inc.php", Array(), Array(
	 *     "MODE"      =&gt; "html",                                           // будет редактировать в веб-редакторе
	 *     "NAME"      =&gt; "Редактирование включаемой области раздела",      // текст всплывающей подсказки на иконке
	 *     "TEMPLATE"  =&gt; "section_include_template.php"                    // имя шаблона для нового файла
	 *     ));
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89637]Компоненты[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/localization/includetemplatelangfile.php">IncludeTemplateLangFile</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/includestring.php">CMain::IncludeString</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/includefile.php
	 * @author Bitrix
	 */
	public static function IncludeFile($rel_path, $arParams = array(), $arFunctionParams = array())
	{
		/** @global CMain $APPLICATION */
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB, $MESS, $DOCUMENT_ROOT;

		if($_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"]=="Y" && ($USER->CanDoOperation('edit_php') || $_SESSION["SHOW_SQL_STAT"]=="Y"))
		{
			$debug = new CDebugInfo();
			$debug->Start();
		}
		elseif($APPLICATION->ShowIncludeStat)
		{
			$debug = new CDebugInfo();
			$debug->Start();
		}
		else
		{
			$debug = null;
		}

		$sType = "TEMPLATE";
		$bComponent = false;
		if(substr($rel_path, 0, 1)!="/")
		{
			$bComponent = true;
			$path = BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/".$rel_path;
			if(!file_exists($_SERVER["DOCUMENT_ROOT"].$path))
			{
				$sType = "DEFAULT";
				$path = BX_PERSONAL_ROOT."/templates/.default/".$rel_path;
				if(!file_exists($_SERVER["DOCUMENT_ROOT"].$path))
				{
					$path = BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/".$rel_path;
					$module_id = substr($rel_path, 0, strpos($rel_path, "/"));
					if(strlen($module_id)>0)
					{
						$path = "/bitrix/modules/".$module_id."/install/templates/".$rel_path;
						$sType = "MODULE";
						if(!file_exists($_SERVER["DOCUMENT_ROOT"].$path))
						{
							$sType = "TEMPLATE";
							$path = BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/".$rel_path;
						}
					}
				}
			}
		}
		else
			$path = $rel_path;

		if($arFunctionParams["WORKFLOW"] && !IsModuleInstalled("workflow"))
			$arFunctionParams["WORKFLOW"] = false;
		elseif($sType!="TEMPLATE" && $arFunctionParams["WORKFLOW"])
			$arFunctionParams["WORKFLOW"] = false;

		$bDrawIcons = (
			$arFunctionParams["SHOW_BORDER"] !== false && $APPLICATION->GetShowIncludeAreas()
			&& (
				$USER->CanDoFileOperation('fm_edit_existent_file', array(SITE_ID, $path))
				|| ($arFunctionParams["WORKFLOW"] && $USER->CanDoFileOperation('fm_edit_in_workflow', array(SITE_ID, $path)))
			)
		);

		if($bDrawIcons)
		{
			$path_url = "path=".$path;
			$encSiteTemplateId = urlencode(SITE_TEMPLATE_ID);
			$editor = '';
			$resize = 'false';
			$iSrcLine = 0;
			$sSrcFile = '';

			if (!in_array($arFunctionParams['MODE'], array('html', 'text', 'php')))
			{
				$arFunctionParams['MODE'] = $bComponent ? 'php' : 'html';
			}

			if ($sType != 'TEMPLATE')
			{
				switch ($arFunctionParams['MODE'])
				{
					case 'html':
						$editor = "/bitrix/admin/fileman_html_edit.php?site=".SITE_ID."&";
						break;
					case 'text':
						$editor = "/bitrix/admin/fileman_file_edit.php?site=".SITE_ID."&";
						break;
					case 'php':
						$editor = "/bitrix/admin/fileman_file_edit.php?full_src=Y&site=".SITE_ID."&";
						break;
				}
				$editor .= "templateID=".$encSiteTemplateId."&";
			}
			else
			{
				switch ($arFunctionParams['MODE'])
				{
					case 'html':
						$editor = '/bitrix/admin/public_file_edit.php?bxpublic=Y&from=includefile&templateID='.$encSiteTemplateId.'&';
						$resize = 'false';
						break;

					case 'text':
						$editor = '/bitrix/admin/public_file_edit.php?bxpublic=Y&from=includefile&noeditor=Y&';
						$resize = 'true';
						break;

					case 'php':
						$editor = '/bitrix/admin/public_file_edit_src.php?templateID='.$encSiteTemplateId.'&';
						$resize = 'true';
						break;
				}
			}

			if($arFunctionParams["TEMPLATE"])
				$arFunctionParams["TEMPLATE"] = "&template=".urlencode($arFunctionParams["TEMPLATE"]);

			if($arFunctionParams["BACK_URL"])
				$arFunctionParams["BACK_URL"] = "&back_url=".urlencode($arFunctionParams["BACK_URL"]);
			else
				$arFunctionParams["BACK_URL"] = "&back_url=".urlencode($_SERVER["REQUEST_URI"]);

			if($arFunctionParams["LANG"])
				$arFunctionParams["LANG"] = "&lang=".urlencode($arFunctionParams["LANG"]);
			else
				$arFunctionParams["LANG"] = "&lang=".LANGUAGE_ID;

			$arIcons = array();
			$arPanelParams = array();

			$bDefaultExists = false;
			if($USER->CanDoOperation('edit_php') && $bComponent && function_exists("debug_backtrace"))
			{
				$bDefaultExists = true;
				$arPanelParams["TOOLTIP"] = array(
					'TITLE' => GetMessage("main_incl_component1"),
					'TEXT' => $rel_path
				);

				$aTrace = debug_backtrace();

				$sSrcFile = $aTrace[0]["file"];
				$iSrcLine = intval($aTrace[0]["line"]);
				$arIcons[] = array(
					'URL' => 'javascript:'.$APPLICATION->GetPopupLink(array(
						'URL' => "/bitrix/admin/component_props.php?".
							"path=".urlencode(CUtil::addslashes($rel_path)).
							"&template_id=".urlencode(CUtil::addslashes(SITE_TEMPLATE_ID)).
							"&lang=".LANGUAGE_ID.
							"&src_path=".urlencode(CUtil::addslashes($sSrcFile)).
							"&src_line=".$iSrcLine.
							""
					)),
					'ICON'=>"parameters",
					'TITLE'=>GetMessage("main_incl_file_comp_param"),
					'DEFAULT'=>true
				);
			}

			if($sType == "MODULE")
			{
				$arIcons[] = array(
					'URL'=>'javascript:if(confirm(\''.GetMessage("MAIN_INC_BLOCK_MODULE").'\'))window.location=\''.$editor.'&path='.urlencode(BX_PERSONAL_ROOT.'/templates/'.SITE_TEMPLATE_ID.'/'.$rel_path).$arFunctionParams["BACK_URL"].$arFunctionParams["LANG"].'&template='.$path.'\';',
					'ICON'=>'copy',
					'TITLE'=>str_replace("#MODE#", $arFunctionParams["MODE"], str_replace("#BLOCK_TYPE#", (!is_set($arFunctionParams, "NAME")? GetMessage("MAIN__INC_BLOCK"): $arFunctionParams["NAME"]), GetMessage("main_incl_file_edit_copy")))
				);
			}
			elseif($sType == "DEFAULT")
			{
				$arIcons[] = array(
					'URL'=>'javascript:if(confirm(\''.GetMessage("MAIN_INC_BLOCK_COMMON").'\'))window.location=\''.$editor.$path_url.$arFunctionParams["BACK_URL"].$arFunctionParams["LANG"].$arFunctionParams["TEMPLATE"].'\';',
					'ICON'=>'edit-common',
					'TITLE'=>str_replace("#MODE#", $arFunctionParams["MODE"], str_replace("#BLOCK_TYPE#", (!is_set($arFunctionParams, "NAME")? GetMessage("MAIN__INC_BLOCK"): $arFunctionParams["NAME"]), GetMessage("MAIN_INC_BLOCK_EDIT")))
				);

				$arIcons[] = array(
					'URL'=>$editor.'&path='.urlencode(BX_PERSONAL_ROOT.'/templates/'.SITE_TEMPLATE_ID.'/'.$rel_path).$arFunctionParams["BACK_URL"].$arFunctionParams["LANG"].'&template='.$path,
					'ICON'=>'copy',
					'TITLE'=>str_replace("#MODE#", $arFunctionParams["MODE"], str_replace("#BLOCK_TYPE#", (!is_set($arFunctionParams, "NAME")? GetMessage("MAIN__INC_BLOCK"): $arFunctionParams["NAME"]), GetMessage("MAIN_INC_BLOCK_COMMON_COPY")))
				);
			}
			else
			{
				$arPanelParams["TOOLTIP"] = array(
					'TITLE' => GetMessage('main_incl_file'),
					'TEXT' => $path
				);

				$arIcons[] = array(
					'URL' => 'javascript:'.$APPLICATION->GetPopupLink(
						array(
							'URL' => $editor.$path_url.$arFunctionParams["BACK_URL"].$arFunctionParams["LANG"].$arFunctionParams["TEMPLATE"],
							"PARAMS" => array(
								'width' => 770,
								'height' => 470,
								'resize' => $resize
							)
						)
					),
					//'URL'=>'javascript:jsPopup.ShowDialog(\''.$editor.$path_url.$arFunctionParams["BACK_URL"].$arFunctionParams["LANG"].$arFunctionParams["TEMPLATE"].'\', {width: 770, height: 570, resize: '.$resize.'})',
					'ICON'=>'bx-context-toolbar-edit-icon',
					'TITLE'=>str_replace("#MODE#", $arFunctionParams["MODE"], str_replace("#BLOCK_TYPE#", (!is_set($arFunctionParams, "NAME")? GetMessage("MAIN__INC_BLOCK") : $arFunctionParams["NAME"]), GetMessage("MAIN_INC_ED"))),
					'DEFAULT'=>!$bDefaultExists
				);

				if($arFunctionParams["WORKFLOW"])
				{
					$arIcons[] = array(
						'URL'=>'/bitrix/admin/workflow_edit.php?'.$arFunctionParams["LANG"].'&fname='.urlencode($path).$arFunctionParams["TEMPLATE"].$arFunctionParams["BACK_URL"],
						'ICON'=>'bx-context-toolbar-edit-icon',
						'TITLE'=>str_replace("#BLOCK_TYPE#", (!is_set($arFunctionParams, "NAME")? GetMessage("MAIN__INC_BLOCK"): $arFunctionParams["NAME"]), GetMessage("MAIN_INC_ED_WF"))
					);
				}
			}

			echo $this->IncludeStringBefore();
		}

		$res = null;
		if(is_file($_SERVER["DOCUMENT_ROOT"].$path))
		{
			if(is_array($arParams))
				extract($arParams, EXTR_SKIP);

			$res = include($_SERVER["DOCUMENT_ROOT"].$path);
		}

		if($_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"]=="Y" && ($USER->CanDoOperation('edit_php') || $_SESSION["SHOW_SQL_STAT"]=="Y"))
			echo $debug->Output($rel_path, $path);
		elseif(is_object($debug))
			$debug->Stop($rel_path, $path);

		if($bDrawIcons)
		{
			$comp_id = $path;
			if ($sSrcFile) $comp_id .= '|'.$sSrcFile;
			if ($iSrcLine) $comp_id .= '|'.$iSrcLine;

			$arPanelParams['COMPONENT_ID'] = md5($comp_id);
			echo $this->IncludeStringAfter($arIcons, $arPanelParams);
		}

		return $res;
	}

	
	/**
	 * <p>Добавляет пункт в конец <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04927" >навигационной цепочки</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $title  Заголовок добавляемого пункта навигационной цепочки.
	 *
	 *
	 *
	 * @param string $url = "" URL на который будет указывать добавляемый пункт навигационной
	 * цепочки.
	 *
	 *
	 *
	 * @param bool $convert_html_entity = true Если значение - "true", то в <i>title</i> будут произведены следующие
	 * замены: <ul> <li> <b>&amp;amp;</b> заменяется на <b>&amp;</b> </li> <li> <b>&amp;quot;</b>
	 * заменяется на <b>"</b> </li> <li> <b>&amp;#039;</b> заменяется на <b>'</b> </li> <li>
	 * <b>&amp;lt;</b> заменяется на <b>&lt;</b> </li> <li> <b>&amp;gt;</b> заменяется на <b>&gt;</b>
	 * </li> </ul> В противном случае замены не будут производиться.
	 * <br>Необязательный. По умолчанию - "true".
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;AddChainItem</b>("Форум &amp;quot;Отзывы&amp;quot;", "/ru/forum/list.php?FID=3");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04927"
	 * >Навигационная цепочка</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/shownavchain.php">CMain::ShowNavChain</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getnavchain.php">CMain::GetNavChain</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addchainitem.php
	 * @author Bitrix
	 */
	public static function AddChainItem($title, $link="", $bUnQuote=true)
	{
		if($bUnQuote)
			$title = str_replace(array("&amp;", "&quot;", "&#039;", "&lt;", "&gt;"), array("&", "\"", "'", "<", ">"), $title);
		$this->arAdditionalChain[] = array("TITLE"=>$title, "LINK"=>htmlspecialcharsbx($link));
	}

	
	/**
	 * <p>Возвращает HTML представляющий из себя навигационную цепочку. <br><br>Если вам не нужно показывать навигационную цепочку на какой либо странице, вам достаточно вставить в теле страницы код, инициализирующий свойство страницы "NOT_SHOW_NAV_CHAIN" значением "Y": </p> <pre>$APPLICATION-&gt;SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");</pre> Поддержка этого свойства встроена в данную функцию.
	 *
	 *
	 *
	 *
	 * @param mixed $path = false Путь для которого будет построена навигационная цепочка. В
	 * случае многосайтовой версии, если DOCUMENT_ROOT у сайтов разный
	 * (задается в поле "Путь к корневой папке веб-сервера" в настройках
	 * сайта), то в данном параметре необходимо передавать массив
	 * вида:<pre>array("ID сайта", "путь")</pre>Необязателен. По умолчанию - "false" -
	 * текущий путь.
	 *
	 *
	 *
	 * @param int $start_from = 0 Начиная от какого пункта будет построена навигационная
	 * цепочка<br>Необязателен. По умолчанию - "0".
	 *
	 *
	 *
	 * @param mixed $template_path = false Путь к шаблону навигационной цепочки.<br>Необязателен. По
	 * умолчанию - "false", что предполагает поиск пути к шаблону
	 * навигационной цепочки по алгоритму представленному на странице
	 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3399" >Управление
	 * показом цепочки</a>
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04927"
	 * >Навигационная цепочка</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/shownavchain.php">CMain::ShowNavChain</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addchainitem.php">CMain::AddChainItem</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getnavchain.php
	 * @author Bitrix
	 */
	public static function GetNavChain($path=false, $iNumFrom=0, $sNavChainPath=false, $bIncludeOnce=false, $bShowIcons = true)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		if($APPLICATION->GetProperty("NOT_SHOW_NAV_CHAIN")=="Y")
			return "";

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		if($path===false)
			$path = $this->GetCurDir();

		$arChain = array();
		$strChainTemplate = $DOC_ROOT.BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/chain_template.php";
		if(!file_exists($strChainTemplate))
			$strChainTemplate = $DOC_ROOT.BX_PERSONAL_ROOT."/templates/.default/chain_template.php";

		$io = CBXVirtualIo::GetInstance();

		while(true)//until the root
		{
			$path = rtrim($path, "/");

			$chain_file_name = $DOC_ROOT.$path."/.section.php";
			$section_template_init = false;
			if($io->FileExists($chain_file_name))
			{
				$sChainTemplate = "";
				$sSectionName = "";
				include($io->GetPhysicalName($chain_file_name));
				if(strlen($sSectionName)>0)
					$arChain[] = array("TITLE"=>$sSectionName, "LINK"=>$path."/");
				if(strlen($sChainTemplate)>0 && !$section_template_init)
				{
					$section_template_init = true;
					$strChainTemplate = $sChainTemplate;
				}
			}

			if($path.'/' == SITE_DIR)
				break;

			if(strlen($path)<=0)
				break;

			//file or folder
			$pos = bxstrrpos($path, "/");
			if($pos===false)
				break;

			//parent folder
			$path = substr($path, 0, $pos+1);
		}

		if($sNavChainPath!==false)
			$strChainTemplate = $DOC_ROOT.$sNavChainPath;

		$arChain = array_reverse($arChain);
		$arChain = array_merge($arChain, $this->arAdditionalChain);
		if($iNumFrom>0)
			$arChain = array_slice($arChain, $iNumFrom);

		return $this->_mkchain($arChain, $strChainTemplate, $bIncludeOnce, $bShowIcons);
	}

	function _mkchain($arChain, $strChainTemplate, $bIncludeOnce=false, $bShowIcons = true)
	{
		$strChain = $sChainProlog = $sChainEpilog = "";
		if(file_exists($strChainTemplate))
		{
			$ITEM_COUNT = count($arChain);
			$arCHAIN = $arChain;
			$arCHAIN_LINK = &$arChain;
			$arResult = &$arChain; // for component 2.0
			if($bIncludeOnce)
			{
				$strChain = include($strChainTemplate);
			}
			else
			{
				foreach($arChain as $i => $arChainItem)
				{
					$ITEM_INDEX = $i;
					$TITLE = $arChainItem["TITLE"];
					$LINK = $arChainItem["LINK"];
					$sChainBody = "";
					include($strChainTemplate);
					$strChain .= $sChainBody;
					if($i==0)
						$strChain = $sChainProlog . $strChain;
				}
				if(count($arChain)>0)
					$strChain .= $sChainEpilog;
			}
		}

		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;
		if($APPLICATION->GetShowIncludeAreas() && $USER->CanDoOperation('edit_php') && $bShowIcons)
		{
			$site = CSite::GetSiteByFullPath($strChainTemplate);
			$DOC_ROOT = CSite::GetSiteDocRoot($site);

			if(strpos($strChainTemplate, $DOC_ROOT)===0)
			{
				$path = substr($strChainTemplate, strlen($DOC_ROOT));

				$templ_perm = $APPLICATION->GetFileAccessPermission($path);
				if((!defined("ADMIN_SECTION") || ADMIN_SECTION!==true) && $templ_perm>="W")
				{
					$arIcons = array();
					$arIcons[] = array(
						"URL"=>"/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".$site."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&full_src=Y&path=".urlencode($path),
						"ICON"=>"nav-template",
						"TITLE"=>GetMessage("MAIN_INC_ED_NAV")
					);

					$strChain = $APPLICATION->IncludeString($strChain, $arIcons);
				}
			}
		}
		return $strChain;
	}

	
	/**
	 * <p>Отображает навигационную цепочку.<br><br>Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, добавлять пункты в навигационную цепочку (например, внутри компонента) уже после того как был выведен пролог сайта. <br><br>Если вам не нужно показывать навигационную цепочку на какой либо странице, вам достаточно вставить в теле страницы код, инициализирующий свойство страницы "NOT_SHOW_NAV_CHAIN" значением "Y": </p> <pre>$APPLICATION-&gt;SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");</pre> Поддержка этого свойства встроена в данную функцию.
	 *
	 *
	 *
	 *
	 * @param mixed $path = false Путь для которого будет построена навигационная цепочка. В
	 * случае многосайтовой версии, если DOCUMENT_ROOT у сайтов разный
	 * (задается в поле "Путь к корневой папке веб-сервера" в настройках
	 * сайта), то в данном параметре необходимо передавать массив
	 * вида:<pre>array("ID сайта", "путь")</pre>Необязателен. По умолчанию - "false" -
	 * текущий путь.
	 *
	 *
	 *
	 * @param int $start_from = 0 Номер пункта начиная с которого будет построена навигационная
	 * цепочка. Пункты навигационной цепочки нумеруются с
	 * нуля.<br>Необязателен. По умолчанию - "0".
	 *
	 *
	 *
	 * @param mixed $template_path = false Путь к шаблону навигационной цепочки.<br>Необязателен. По
	 * умолчанию - "false", что предполагает поиск пути к шаблону
	 * навигационной цепочки по алгоритму представленному на странице
	 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3399" >Управление
	 * показом цепочки</a>
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // выведем цепочку навигации для текущей страницы начиная 
	 * // со первого пункта по шаблону "chain_template.php"
	 * // лежащему либо в каталоге "/bitrix/templates/&lt;текущий шаблон сайта&gt;/", 
	 * // либо в каталоге "/bitrix/templates/.default/".
	 * <b>$APPLICATION-&gt;ShowNavChain</b>();
	 * ?&gt;
	 * &lt;?
	 * // выведем цепочку навигации для текущей страницы начиная 
	 * // со 2-го пункта по шаблону chain_template_bottom.php
	 * <b>$APPLICATION-&gt;ShowNavChain</b>(false, 2, "/bitrix/templates/.default/chain_template_bottom.php");
	 * ?&gt;
	 * &lt;?
	 * // файл /bitrix/templates/.default/chain_template.php
	 * 
	 * $sChainProlog = "";   // HTML выводимый перед навигационной цепочкой
	 * $sChainBody = "";     // пункт навигационной цепочки
	 * $sChainEpilog = "";   // HTML выводимый после навигационной цепочки
	 * 
	 * // разделитель
	 * if ($ITEM_INDEX &gt; 0)
	 *    $sChainBody = "&lt;font class=\"chain\"&gt;&amp;nbsp;/&amp;nbsp;&lt;/font&gt;";
	 * 
	 * // если указана ссылка то
	 * if (strlen($LINK)&gt;0)
	 * {
	 *     // выводим ссылку
	 *     $sChainBody .= "&lt;a href=\"".$LINK."\" class=\"chain\"&gt;".htmlspecialchars($TITLE)."&lt;/a&gt;";
	 * }
	 * else // иначе
	 * {
	 *     // текст
	 *     $sChainBody .= "&lt;font class=\"chain\"&gt;".htmlspecialchars($TITLE)."&lt;/font&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04927"
	 * >Навигационная цепочка</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >Отложенные
	 * функции</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addchainitem.php">CMain::AddChainItem</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getnavchain.php">CMain::GetNavChain</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/shownavchain.php
	 * @author Bitrix
	 */
	public static function ShowNavChain($path=false, $iNumFrom=0, $sNavChainPath=false)
	{
		$this->AddBufferContent(array(&$this, "GetNavChain"), $path, $iNumFrom, $sNavChainPath);
	}

	public static function ShowNavChainEx($path=false, $iNumFrom=0, $sNavChainPath=false)
	{
		$this->AddBufferContent(array(&$this, "GetNavChain"), $path, $iNumFrom, $sNavChainPath, true);
	}

	/*****************************************************/

	
	/**
	 * <p>Устанавливает права доступа к файлу или каталогу. Возвращает "true" - если установка прав произведена успешно и "false" - в случае ошибки.</p>
	 *
	 *
	 *
	 *
	 * @param string $path  Путь к файлу или папке относительно корня. В случае многосайтовой
	 * версии, если<b>корневой каталог у сайтов</b> разный, то в данном
	 * параметре необходимо передавать массив вида:<pre>array("ID сайта", "Путь
	 * к файлу или папке относительно корня")</pre>
	 *
	 *
	 *
	 * @param array $permissions  Массив с правами доступа вида Array("ID группы
	 * пользователей"=&gt;"право доступа" [, ...]). В качестве "право доступа"
	 * возможны следующие значения: <ul> <li> <b>D</b> - доступ запрещён </li> <li>
	 * <b>R</b> - чтение (право просмотра содержимого файла) </li> <li> <b>U</b> -
	 * документооборот (право на редактирование файла в режиме
	 * документооборота) </li> <li> <b>W</b> - запись (право на прямое
	 * редактирование) </li> <li> <b>X</b> - полный доступ (право на прямое
	 * редактирование файла и право на изменение прав доступа на данных
	 * файл) </li> </ul> В качестве "ID группы пользователей" также может быть
	 * задан символ "*", что означает - "для всех групп пользователей".
	 *
	 *
	 *
	 * @param bool $overwrite = true Если значение - "true", то существующие права будут
	 * перезаписаны.<br>Необязателен. По умолчанию - "true".
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // установим на файл /ru/index.php следующие права:
	 * // для группы # 23 - право чтения файла
	 * // для группы # 5 - право прямого изменения файла
	 * // для всех остальных групп - доступ к файлу закрыт
	 * if (<b>$APPLICATION-&gt;SetFileAccessPermission</b>("/ru/index.php", 
	 *     array("23" =&gt; "R", "5" =&gt; "W", "*" =&gt; "D")))
	 *     ShowNote("Права на файл успешно установлены.");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getfileaccesspermission.php">CMain::GetFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/copyfileaccesspermission.php">CMain::CopyFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/removefileaccesspermission.php">CMain::RemoveFileAccessPermission</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/events/onchangepermissions.php">Событие
	 * "OnChangePermissions"</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setfileaccesspermission.php
	 * @author Bitrix
	 */
	public static function SetFileAccessPermission($path, $arPermissions, $bOverWrite=true)
	{
		global $CACHE_MANAGER;

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$path = rtrim($path, "/");
		if($path == '')
			$path = "/";

		if(($p = bxstrrpos($path, "/")) !== false)
		{
			$path_file = substr($path, $p+1);
			$path_dir = substr($path, 0, $p);
		}
		else
			return false;

		if($path_file == "" && $path_dir == "")
			$path_file = "/";

		$PERM = array();

		$io = CBXVirtualIo::GetInstance();
		if ($io->FileExists($DOC_ROOT.$path_dir."/.access.php"))
		{
			$fTmp = $io->GetFile($DOC_ROOT.$path_dir."/.access.php");
			//include replaced with eval in order to honor of ZendServer
			eval("?>".$fTmp->GetContents());
		}

		$FILE_PERM = $PERM[$path_file];
		if(!is_array($FILE_PERM))
			$FILE_PERM = array();

		if(!$bOverWrite && count($FILE_PERM)>0)
			return true;

		$bDiff = false;

		$str="<?\n";
		foreach($arPermissions as $group=>$perm)
		{
			if(strlen($perm) > 0)
				$str .= "\$PERM[\"".EscapePHPString($path_file)."\"][\"".EscapePHPString($group)."\"]=\"".EscapePHPString($perm)."\";\n";

			if(!$bDiff)
			{
				//compatibility with group id
				$curr_perm = $FILE_PERM[$group];
				if(!isset($curr_perm) && preg_match('/^G[0-9]+$/', $group))
					$curr_perm = $FILE_PERM[substr($group, 1)];

				if($curr_perm != $perm)
					$bDiff = true;
			}
		}

		foreach($PERM as $file=>$arPerm)
		{
			if(strval($file) !== $path_file)
				foreach($arPerm as $group=>$perm)
					$str .= "\$PERM[\"".EscapePHPString($file)."\"][\"".EscapePHPString($group)."\"]=\"".EscapePHPString($perm)."\";\n";
		}

		if(!$bDiff)
		{
			foreach($FILE_PERM as $group=>$perm)
			{
				//compatibility with group id
				$new_perm = $arPermissions[$group];
				if(!isset($new_perm) && preg_match('/^G[0-9]+$/', $group))
					$new_perm = $arPermissions[substr($group, 1)];

				if($new_perm != $perm)
				{
					$bDiff = true;
					break;
				}
			}
		}

		$str .= "?".">";

		$this->SaveFileContent($DOC_ROOT.$path_dir."/.access.php", $str);
		$CACHE_MANAGER->CleanDir("menu");
		unset($this->FILE_PERMISSION_CACHE[$site."|".$path_dir."/.access.php"]);

		if($bDiff)
		{
			foreach(GetModuleEvents("main", "OnChangePermissions", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(array($site, $path), $arPermissions, $FILE_PERM));

			if(COption::GetOptionString("main", "event_log_file_access", "N") === "Y")
				CEventLog::Log("SECURITY", "FILE_PERMISSION_CHANGED", "main", "[".$site."] ".$path, print_r($FILE_PERM, true)." => ".print_r($arPermissions, true));
		}
		return true;
	}

	
	/**
	 * <p>Удаляет права доступа для файла или каталога. Возвращает "true" - если удаление произведено успешно и "false" - в случае ошибки.</p>
	 *
	 *
	 *
	 *
	 * @param string $path  Путь к файлу или папке относительно корня. В случае многосайтовой
	 * версии, если <b>корневой каталог у сайтов</b> разный, то в данном
	 * параметре необходимо передавать массив вида:<pre>array("ID сайта", "Путь
	 * к файлу или папке относительно корня")</pre>
	 *
	 *
	 *
	 * @param mixed $groups = false Массив групп для которых удалить права доступа. Если значение -
	 * "false", то права доступа будут удалены для всех групп.
	 * <br>Необязательный. По умолчанию - "false".
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // удалим права на файл /ru/index.php для групп пользователей #5 и #23
	 * if (<b>$APPLICATION-&gt;RemoveFileAccessPermission</b>("/ru/index.php", array(5, 23)))
	 *     ShowNote("Права успешно модифицированы.");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getfileaccesspermission.php">CMain::GetFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/copyfileaccesspermission.php">CMain::CopyFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setfileaccesspermission.php">CMain::SetFileAccessPermission</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/removefileaccesspermission.php
	 * @author Bitrix
	 */
	public static function RemoveFileAccessPermission($path, $arGroups=false)
	{
		global $CACHE_MANAGER;

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$path = rtrim($path, "/");
		if($path == '')
			$path = "/";

		if(($p = bxstrrpos($path, "/")) !== false)
		{
			$path_file = substr($path, $p+1);
			$path_dir = substr($path, 0, $p);
		}
		else
			return false;

		$PERM = array();
		$io = CBXVirtualIo::GetInstance();
		if (!$io->FileExists($DOC_ROOT.$path_dir."/.access.php"))
			return true;

		include($io->GetPhysicalName($DOC_ROOT.$path_dir."/.access.php"));

		$str = "<?\n";
		foreach($PERM as $file=>$arPerm)
		{
			if($file != $path_file || $arGroups !== false)
			{
				foreach($arPerm as $group=>$perm)
				{
					if($arGroups !== false)
					{
						//compatibility with group id
						$bExists = false;
						if(in_array($group, $arGroups))
							$bExists = true;
						elseif(preg_match('/^G[0-9]+$/', $group) && in_array(substr($group, 1), $arGroups))
							$bExists = true;
						elseif(preg_match('/^[0-9]+$/', $group) && in_array('G'.$group, $arGroups))
							$bExists = true;
					}
					if($file != $path_file || ($arGroups !== false && !$bExists))
						$str .= "\$PERM[\"".EscapePHPString($file)."\"][\"".EscapePHPString($group)."\"]=\"".EscapePHPString($perm)."\";\n";
				}
			}
		}

		$str .= "?".">";

		$this->SaveFileContent($DOC_ROOT.$path_dir."/.access.php", $str);
		$CACHE_MANAGER->CleanDir("menu");
		unset($this->FILE_PERMISSION_CACHE[$site."|".$path_dir."/.access.php"]);

		foreach(GetModuleEvents("main", "OnChangePermissions", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(array($site, $path), array()));

		return true;
	}

	
	/**
	 * <p>Копирует <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >права доступа</a> одного файла (каталогу) другому файлу (каталогу). Возвращает "true" - если права скопированы успешно и "false" - в случае ошибки.</p>
	 *
	 *
	 *
	 *
	 * @param string $path_from  Путь <em>откуда</em> копировать. В случае многосайтовой версии, если
	 * DOCUMENT_ROOT у сайтов разный (задается в поле "Путь к корневой папке
	 * веб-сервера" в настройках сайта), то в данном параметре необходимо
	 * передавать массив вида:<pre>array("ID сайта", "Путь <em>откуда</em>
	 * копировать")</pre>
	 *
	 *
	 *
	 * @param string $path_to  Путь <em>куда</em> копировать. В случае многосайтовой версии, если
	 * DOCUMENT_ROOT у сайтов разный (задается в поле "Путь к корневой папке
	 * веб-сервера" в настройках сайта), то в данном параметре необходимо
	 * передавать массив вида:<pre>array("ID сайта", "Путь <em>куда</em>
	 * копировать")</pre>
	 *
	 *
	 *
	 * @param bool $overwrite = false Если значение - "true", то существующие права будут
	 * перезаписаны.<br>Необязателен. По умолчанию - "false".
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // установим на файл /ru/index.php следующие права:
	 * // для группы # 23 - право чтения файла
	 * // для группы # 5 - право прямого изменения файла
	 * // для всех остальных групп - доступ к файлу закрыт
	 * if ($APPLICATION-&gt;SetFileAccessPermission("/ru/index.php", array("23" =&gt; "R", "5" =&gt; "W", "*" =&gt; "D")))
	 * {
	 *     ShowNote("Права на файл успешно установлены.");
	 * 
	 *     // скопируем права файла "/ru/index.php" в права файла "/en/index.php"
	 *     if (<b>$APPLICATION-&gt;CopyFileAccessPermission</b>("/ru/index.php", "/en/index.php", true))
	 *          ShowNote("Права успешно скопированы.");
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getfileaccesspermission.php">CMain::GetFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setfileaccesspermission.php">CMain::SetFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/removefileaccesspermission.php">CMain::RemoveFileAccessPermission</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/copyfileaccesspermission.php
	 * @author Bitrix
	 */
	public static function CopyFileAccessPermission($path_from, $path_to, $bOverWrite=false)
	{
		CMain::InitPathVars($site_from, $path_from);
		$DOC_ROOT_FROM = CSite::GetSiteDocRoot($site_from);

		CMain::InitPathVars($site_to, $path_to);

		//upper .access.php
		if(($p = bxstrrpos($path_from, "/"))!==false)
		{
			$path_from_file = substr($path_from, $p+1);
			$path_from_dir = substr($path_from, 0, $p);
		}
		else
			return false;

		$PERM = array();

		$io = CBXVirtualIo::GetInstance();
		if (!$io->FileExists($DOC_ROOT_FROM.$path_from_dir."/.access.php"))
			return true;

		include($io->GetPhysicalName($DOC_ROOT_FROM.$path_from_dir."/.access.php"));

		$FILE_PERM = $PERM[$path_from_file];
		if(count($FILE_PERM)>0)
			return $this->SetFileAccessPermission(array($site_to, $path_to), $FILE_PERM, $bOverWrite);

		return true;
	}


	
	/**
	 * <p>Определяет <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >права доступа к файлу или каталогу</a>. Возвращает символ обозначающий то или иное право: </p> <ul> <li> <b>D</b> - доступ запрещён </li> <li> <b>R</b> - чтение (право просмотра содержимого файла) </li> <li> <b>U</b> - документооборот (право на редактирование файла в режиме документооборота) </li> <li> <b>W</b> - запись (право на прямое редактирование) </li> <li> <b>X</b> - полный доступ (право на прямое редактирование файла и право на изменение прав доступа на данных файл) </li> </ul>
	 *
	 *
	 *
	 *
	 * @param mixed $path  Путь к файлу или папке относительно корня. В случае многосайтовой
	 * версии, если корневой каталог у сайтов разный, то в данном
	 * параметре необходимо передавать массив вида:<pre>array("ID сайта", "Путь
	 * к файлу или папке относительно корня")</pre>
	 *
	 *
	 *
	 * @param array $groups  Массив ID групп пользователей, для которых необходимо определить
	 * права доступа. Если false, то определять группу прав для текущего
	 * пользователя.<br>Необязателен. По умолчанию - <i>false</i>.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (<b>$APPLICATION-&gt;GetFileAccessPermission</b>("/ru/index.php") &lt;= "D")
	 *    ShowError("Доступ к файлу запрещён.");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/setfileaccesspermission.php">CMain::SetFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/copyfileaccesspermission.php">CMain::CopyFileAccessPermission</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/removefileaccesspermission.php">CMain::RemoveFileAccessPermission</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getfileaccesspermission.php
	 * @author Bitrix
	 */
	public static function GetFileAccessPermission($path, $groups=false, $task_mode=false) // task_mode - new access mode
	{
		global $USER;

		if($groups === false)
		{
			if(!is_object($USER))
				$groups = array('G2');
			else
				$groups = $USER->GetAccessCodes();
		}
		elseif(is_array($groups) && !empty($groups))
		{
			//compatibility with user groups id
			$bNumbers = preg_match('/^[0-9]+$/', $groups[0]);
			if($bNumbers)
				foreach($groups as $key=>$val)
					$groups[$key] = "G".$val;
		}

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		//windows files are case-insensitive
		$bWin = (strncasecmp(PHP_OS, "WIN", 3) == 0);
		if($bWin)
			$path = strtolower($path);

		if(trim($path, "/") != "")
		{
			$path = Rel2Abs("/", $path);
			if($path == "")
				return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));
		}

		if(COption::GetOptionString("main", "controller_member", "N") == "Y" && COption::GetOptionString("main", "~controller_limited_admin", "N") == "Y")
			$bAdminM = (is_object($USER)? $USER->IsAdmin() : false);
		else
			$bAdminM = in_array("G1", $groups);

		if($bAdminM)
			return (!$task_mode? 'X' : array(CTask::GetIdByLetter('X', 'main', 'file')));

		if(substr($path, -12) == "/.access.php" && !$bAdminM)
			return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));

		if(substr($path, -10) == "/.htaccess" && !$bAdminM)
			return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));

		$max_perm = "D";
		$arGroupTask = array();

		$io = CBXVirtualIo::GetInstance();

		//in the group list * === "any group"
		$groups[] = "*";
		while(true)//till the root
		{
			$path = rtrim($path, "\0");
			$path = rtrim($path, "/");

			if($path == '')
			{
				$access_file_name="/.access.php";
				$Dir = "/";
			}
			else
			{
				//file or folder
				$pos = strrpos($path, "/");
				if($pos === false)
					break;
				$Dir = substr($path, $pos+1);

				//security fix: under Windows "my." == "my"
				$Dir = TrimUnsafe($Dir);

				//parent folder
				$path = substr($path, 0, $pos+1);

				$access_file_name=$path.".access.php";
			}

			if(array_key_exists($site."|".$access_file_name, $this->FILE_PERMISSION_CACHE))
			{
				$PERM = $this->FILE_PERMISSION_CACHE[$site."|".$access_file_name];
			}
			else
			{
				$PERM = array();

				//file with rights array
				if ($io->FileExists($DOC_ROOT.$access_file_name))
					include($io->GetPhysicalName($DOC_ROOT.$access_file_name));

				//windows files are case-insensitive
				if($bWin && !empty($PERM))
				{
					$PERM_TMP = array();
					foreach($PERM as $key => $val)
						$PERM_TMP[strtolower($key)] = $val;
					$PERM = $PERM_TMP;
				}

				$this->FILE_PERMISSION_CACHE[$site."|".$access_file_name] = $PERM;
			}

			//check wheather the rights are assigned to this file\folder for these groups
			if(isset($PERM[$Dir]) && is_array($PERM[$Dir]))
			{
				$dir_perm = $PERM[$Dir];
				foreach($groups as $key => $group_id)
				{
					if(isset($dir_perm[$group_id]))
						$perm = $dir_perm[$group_id];
					elseif(preg_match('/^G([0-9]+)$/', $group_id, $match)) //compatibility with group id
					{
						if(isset($dir_perm[$match[1]]))
							$perm = $dir_perm[$match[1]];
						else
							continue;
					}
					else
						continue;

					if ($task_mode)
					{
						if(substr($perm, 0, 2) == 'T_')
							$tid = intval(substr($perm, 2));
						elseif(($tid = CTask::GetIdByLetter($perm, 'main', 'file')) === false)
							continue;

						$arGroupTask[$group_id] = $tid;
					}
					else
					{
						if(substr($perm, 0, 2) == 'T_')
						{
							$tid = intval(substr($perm, 2));
							$perm = CTask::GetLetter($tid);
							if(strlen($perm) == 0)
								$perm = 'D';
						}

						if($max_perm == "" || $perm > $max_perm)
						{
							$max_perm = $perm;
							if($perm == "W")
								break 2;
						}
					}

					if($group_id == "*")
						break 2;

					//delete the groip from the list, we have rights alredy for it
					unset($groups[$key]);

					if(count($groups) == 1 && in_array("*", $groups))
						break 2;
				}

				if(count($groups)<=1)
					break;
			}

			if($path == '')
				break;
		}

		if($task_mode)
		{
			$arTasks = array_unique(array_values($arGroupTask));
			if(empty($arTasks))
				return array(CTask::GetIdByLetter('D', 'main', 'file'));
			sort($arTasks);
			return $arTasks;
		}
		else
			return $max_perm;
	}

	public static function GetFileAccessPermissionByUser($intUserID, $path, $groups=false, $task_mode=false) // task_mode - new access mode
	{
		$intUserIDTmp = intval($intUserID);
		if ($intUserIDTmp.'|' != $intUserID.'|')
			return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));
		$intUserID = $intUserIDTmp;

		if ($groups === false)
		{
			$groups = CUser::GetUserGroup($intUserID);
			foreach ($groups as $key=>$val)
				$groups[$key] = "G".$val;
		}
		elseif (is_array($groups) && !empty($groups))
		{
			$bNumbers = preg_match('/^[0-9]+$/', $groups[0]);
			if($bNumbers)
				foreach($groups as $key=>$val)
					$groups[$key] = "G".$val;
		}

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$bWin = (strncasecmp(PHP_OS, "WIN", 3) == 0);
		if ($bWin)
			$path = strtolower($path);

		if (trim($path, "/") != "")
		{
			$path = Rel2Abs("/", $path);
			if($path == "")
				return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));
		}

		$bAdminM = in_array("G1", $groups);

		if ($bAdminM)
			return (!$task_mode? 'X' : array(CTask::GetIdByLetter('X', 'main', 'file')));

		if (substr($path, -12) == "/.access.php" && !$bAdminM)
			return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));

		if (substr($path, -10) == "/.htaccess" && !$bAdminM)
			return (!$task_mode? 'D' : array(CTask::GetIdByLetter('D', 'main', 'file')));

		$max_perm = "D";
		$arGroupTask = array();

		$io = CBXVirtualIo::GetInstance();

		$groups[] = "*";
		while (true)
		{
			$path = rtrim($path, "\0");
			$path = rtrim($path, "/");

			if ($path == '')
			{
				$access_file_name="/.access.php";
				$Dir = "/";
			}
			else
			{
				$pos = strrpos($path, "/");
				if ($pos === false)
					break;
				$Dir = substr($path, $pos+1);

				$Dir = TrimUnsafe($Dir);

				$path = substr($path, 0, $pos+1);

				$access_file_name=$path.".access.php";
			}

			if (array_key_exists($site."|".$access_file_name, $this->FILE_PERMISSION_CACHE))
			{
				$PERM = $this->FILE_PERMISSION_CACHE[$site."|".$access_file_name];
			}
			else
			{
				$PERM = array();

				if ($io->FileExists($DOC_ROOT.$access_file_name))
					include($io->GetPhysicalName($DOC_ROOT.$access_file_name));

				if ($bWin && !empty($PERM))
				{
					$PERM_TMP = array();
					foreach($PERM as $key => $val)
						$PERM_TMP[strtolower($key)] = $val;
					$PERM = $PERM_TMP;
				}

				$this->FILE_PERMISSION_CACHE[$site."|".$access_file_name] = $PERM;
			}

			if ($PERM[$Dir] && is_array($PERM[$Dir]))
			{
				$dir_perm = $PERM[$Dir];
				foreach ($groups as $key => $group_id)
				{
					if(isset($dir_perm[$group_id]))
						$perm = $dir_perm[$group_id];
					elseif(preg_match('/^G[0-9]+$/', $group_id)) //compatibility with group id
						$perm = $dir_perm[substr($group_id, 1)];
					else
						continue;

					if ($task_mode)
					{
						if(substr($perm, 0, 2) == 'T_')
							$tid = intval(substr($perm, 2));
						elseif(($tid = CTask::GetIdByLetter($perm, 'main', 'file')) === false)
							continue;

						$arGroupTask[$group_id] = $tid;
					}
					else
					{
						if(substr($perm, 0, 2) == 'T_')
						{
							$tid = intval(substr($perm, 2));
							$perm = CTask::GetLetter($tid);
							if(strlen($perm) == 0)
								$perm = 'D';
						}

						if ($max_perm == "" || $perm > $max_perm)
						{
							$max_perm = $perm;
							if($perm == "W")
								break 2;
						}
					}

					if($group_id == "*")
						break 2;

					unset ($groups[$key]);

					if (count($groups) == 1 && in_array("*", $groups))
						break 2;
				}

				if (count($groups)<=1)
					break;
			}

			if($path == '')
				break;
		}

		if ($task_mode)
		{
			$arTasks = array_unique(array_values($arGroupTask));
			if(empty($arTasks))
				return array(CTask::GetIdByLetter('D', 'main', 'file'));
			sort($arTasks);
			return $arTasks;
		}
		else
			return $max_perm;
	}
	/***********************************************/

	
	/**
	 * <p>Сохраняет страницу на диске. Возвращает "true" - если сохранение произведено успешно и "false" - в случае ошибки. Данная функция инициализирует событие "OnChangeFile".</p>
	 *
	 *
	 *
	 *
	 * @param string $abs_path  Полный путь к файлу на диске.
	 *
	 *
	 *
	 * @param string $content  Содержимое файла.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $file_content = '
	 *     &lt;?
	 *     require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	 *     $APPLICATION-&gt;SetTitle("Title");
	 *     ?&gt;
	 *     Содержимое страницы...
	 *     &lt;?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?&gt;
	 *     ';
	 * 
	 * $abs_path = $_SERVER["DOCUMENT_ROOT"]."/ru/index.php";
	 * if(!<b>$APPLICATION-&gt;SaveFileContent</b>($abs_path, $file_content))
	 * 	ShowError("Ошибка при сохранениии файла");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getfilecontent.php">CMain::GetFileContent</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/events/onbeforechangefile.php">Событие
	 * "OnChangeFile"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/file/rewritefile.php">RewriteFile</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/savefilecontent.php
	 * @author Bitrix
	 */
	public static function SaveFileContent($abs_path, $strContent)
	{
		$strContent = str_replace("\r\n", "\n", $strContent);

		$file = array();
		$this->ResetException();

		foreach(GetModuleEvents("main", "OnBeforeChangeFile", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($abs_path, &$strContent)) == false)
			{
				if(!$this->GetException())
					$this->ThrowException(GetMessage("main_save_file_handler_error", array("#HANDLER#"=>$arEvent["TO_NAME"])));
				return false;
			}
		}

		$io = CBXVirtualIo::GetInstance();
		$fileIo = $io->GetFile($abs_path);

		$io->CreateDirectory($fileIo->GetPath());

		if($fileIo->IsExists())
		{
			$file["exists"] = true;
			if (!$fileIo->IsWritable())
				$fileIo->MarkWritable();
			$file["size"] = $fileIo->GetFileSize();
		}

		/****************************** QUOTA ******************************/
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$quota = new CDiskQuota();
			if (false === $quota->checkDiskQuota(array("FILE_SIZE" => intVal(strLen($strContent) - intVal($file["size"])))))
			{
				$this->ThrowException($quota->LAST_ERROR, "BAD_QUOTA");
				return false;
			}
		}
		/****************************** QUOTA ******************************/
		if ($fileIo->PutContents($strContent))
		{
			$fileIo->MarkWritable();
		}
		else
		{
			if ($file["exists"])
				$this->ThrowException(GetMessage("MAIN_FILE_NOT_CREATE"), "FILE_NOT_CREATE");
			else
				$this->ThrowException(GetMessage("MAIN_FILE_NOT_OPENED"), "FILE_NOT_OPEN");
			return false;
		}

		bx_accelerator_reset();

		$site = CSite::GetSiteByFullPath($abs_path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			//Fix for name case under Windows
			$abs_path = strtolower($abs_path);
			$DOC_ROOT = strtolower($DOC_ROOT);
		}

		if(strpos($abs_path, $DOC_ROOT)===0 && $site!==false)
		{
			$DOC_ROOT = rtrim($DOC_ROOT, "/\\");
			$path = "/".ltrim(substr($abs_path, strlen($DOC_ROOT)), "/\\");

			foreach(GetModuleEvents("main", "OnChangeFile", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($path, $site));
		}
		/****************************** QUOTA ******************************/
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$fs = $fileIo->GetFileSize();
			CDiskQuota::updateDiskQuota("files", intVal($fs - intVal($file["size"])), "update");
		}
		/****************************** QUOTA ******************************/
		return true;
	}

	
	/**
	 * <p>Возвращает содержимое файла. Если файл не существует - вернет "false".</p>
	 *
	 *
	 *
	 *
	 * @param string $abs_path  Абсолютный путь к файлу на диске.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$APPLICATION-&gt;GetFileContent</b>($_SERVER["DOCUMENT_ROOT"]."/ru/index.php");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/savefilecontent.php">CMain::SaveFileContent</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getfilecontent.php
	 * @author Bitrix
	 */
	public static function GetFileContent($path)
	{
		clearstatcache();

		$io = CBXVirtualIo::GetInstance();

		if(!$io->FileExists($path))
			return false;
		$f = $io->GetFile($path);
		if($f->GetFileSize()<=0)
			return "";
		$contents = $f->GetContents();
		return $contents;
	}

	public static function ProcessLPA($filesrc = false, $old_filesrc = false)
	{
		if ($filesrc === false)
			return '';

		// Find all php fragments in $filesrc and:
		// 	1. Kill all non-component 2.0 fragments
		// 	2. Get and check params of components
		$arPHP = PHPParser::ParseFile($filesrc);
		$l = count($arPHP);
		if ($l > 0)
		{
			$new_filesrc = '';
			$end = 0;
			for ($n = 0; $n<$l; $n++)
			{
				$start = $arPHP[$n][0];
				$new_filesrc .= CMain::EncodePHPTags(substr($filesrc,$end,$start-$end));
				$end = $arPHP[$n][1];

				//Trim php tags
				$src = $arPHP[$n][2];
				if (substr($src, 0, 5) == "<?php")
					$src = '<?'.substr($src, 5);

				//If it's Component 2 - we handle it's params, non components2 will be erased
				$comp2_begin = '<?$APPLICATION->INCLUDECOMPONENT(';
				if (strtoupper(substr($src, 0, strlen($comp2_begin))) == $comp2_begin)
				{
					$arRes = PHPParser::CheckForComponent2($src);

					if ($arRes)
					{
						$comp_name = CMain::_ReplaceNonLatin($arRes['COMPONENT_NAME']);
						$template_name = CMain::_ReplaceNonLatin($arRes['TEMPLATE_NAME']);
						$arParams = $arRes['PARAMS'];
						$arPHPparams = array();
						CMain::LPAComponentChecker($arParams, $arPHPparams);
						$len = count($arPHPparams);
						$br = "\r\n";
						$code = '$APPLICATION->IncludeComponent('.$br.
							"\t".'"'.$comp_name.'",'.$br.
							"\t".'"'.$template_name.'",'.$br;
						// If exist at least one parameter with php code inside
						if (count($arParams) > 0)
						{
							// Get array with description of component params
							$arCompParams = CComponentUtil::GetComponentProps($comp_name);
							$arTemplParams = CComponentUtil::GetTemplateProps($comp_name, $template_name);

							$arParameters = array();
							if (isset($arCompParams["PARAMETERS"]) && is_array($arCompParams["PARAMETERS"]))
								$arParameters = $arParameters + $arCompParams["PARAMETERS"];
							if (is_array($arTemplParams))
								$arParameters = $arParameters + $arTemplParams;

							// Replace values from 'DEFAULT'
							for ($e = 0; $e < $len; $e++)
							{
								$par_name = $arPHPparams[$e];
								$arParams[$par_name] = isset($arParameters[$par_name]['DEFAULT']) ? $arParameters[$par_name]['DEFAULT'] : '';
							}

							//ReturnPHPStr
							$params = PHPParser::ReturnPHPStr2($arParams, $arParameters);
							$code .= "\t".'array('.$br."\t".$params.$br."\t".')';
						}
						else
						{
							$code .=  "\t".'array()';
						}
						$parent_comp = CMain::_ReplaceNonLatin($arRes['PARENT_COMP']);
						$arExParams_ = $arRes['FUNCTION_PARAMS'];

						$bEx = isset($arExParams_) && is_array($arExParams_) && count($arExParams_) > 0;

						if (!$parent_comp || strtolower($parent_comp) == 'false')
							$parent_comp = false;
						if ($parent_comp)
						{
							if ($parent_comp == 'true' || intVal($parent_comp) == $parent_comp)
								$code .= ','.$br."\t".$parent_comp;
							else
								$code .= ','.$br."\t\"".$parent_comp.'"';
						}
						if ($bEx)
						{
							if (!$parent_comp)
								$code .= ','.$br."\tfalse";

							$arExParams = array();
							foreach ($arExParams_ as $k => $v)
							{
								$k = CMain::_ReplaceNonLatin($k);
								$v = CMain::_ReplaceNonLatin($v);
								if (strlen($k) > 0 && strlen($v) > 0)
									$arExParams[$k] = $v;
							}
							//CComponentUtil::PrepareVariables($arExParams);
							$exParams = PHPParser::ReturnPHPStr2($arExParams);
							$code .= ','.$br."\tarray(".$exParams.')';
						}
						$code .= $br.');';
						$code = '<?'.$code.'?>';
						$new_filesrc .= $code;
					}
				}
			}
			$new_filesrc .= CMain::EncodePHPTags(substr($filesrc,$end));
			$filesrc = $new_filesrc;
		}
		else
		{
			$filesrc = CMain::EncodePHPTags($filesrc);
		}

		if (strpos($filesrc, '#PHP') !== false && $old_filesrc !== false) // We have to handle php fragments
		{
			// Get array of PHP scripts from old saved file
			$arPHP = PHPParser::ParseFile($old_filesrc);
			$arPHPscripts = array();
			$l = count($arPHP);
			if ($l > 0)
			{
				$new_filesrc = '';
				$end = 0;
				for ($n = 0; $n < $l; $n++)
				{
					$start = $arPHP[$n][0];
					$new_filesrc .= substr($old_filesrc, $end, $start - $end);
					$end = $arPHP[$n][1];
					$src = $arPHP[$n][2];
					$src = SubStr($src, (SubStr($src, 0, 5) == "<?"."php") ? 5 : 2, -2); // Trim php tags
					$comp2_begin = '$APPLICATION->INCLUDECOMPONENT(';
					if (strtoupper(substr($src,0, strlen($comp2_begin))) != $comp2_begin)
						$arPHPscripts[] = $src;
				}
			}

			// Ok, so we already have array of php scripts lets check our new content
			// LPA-users CAN delete PHP fragments and swap them but CAN'T add new or modify existent:
			while (preg_match('/#PHP\d{4}#/i'.BX_UTF_PCRE_MODIFIER, $filesrc, $res))
			{
				$php_begin = strpos($filesrc, $res[0]);
				$php_fr_num = intval(substr($filesrc, $php_begin + 4, 4)) - 1; // Number of PHP fragment from #PHPXXXX# conctruction

				if (isset($arPHPscripts[$php_fr_num]))
					$filesrc = substr($filesrc, 0, $php_begin).'<?'.$arPHPscripts[$php_fr_num].'?>'.substr($filesrc, $php_begin + 9);
				else
					$filesrc = substr($filesrc, 0, $php_begin).substr($filesrc, $php_begin + 9);
			}
		}

		return $filesrc;
	}

	public static function EncodePHPTags($str)
	{
		$str = str_replace(array("<?","?>", "<%", "%>"),array("&lt;?","?&gt;","&lt;%","%&gt;"), $str);

		static $pattern = "/(<script[^>]*language\s*=\s*)('|\"|)php('|\"|)([^>]*>)/i";
		$str = preg_replace($pattern, "&lt;??&gt;", $str);

		return $str;
	}

	public static function LPAComponentChecker(&$arParams, &$arPHPparams, $parentParamName = false)
	{
		//all php fragments wraped by ={}
		foreach ($arParams as $param_name => $paramval)
		{
			if (substr($param_name, 0, 2) == '={' && substr($param_name, -1) == '}')
			{
				$key = substr($param_name, 2, -1);
				if (strval($key) !== strval(intval($key)))
				{
					unset($arParams[$param_name]);
					continue;
				}
			}
			if (is_array($paramval))
			{
				CMain::LPAComponentChecker($paramval, $arPHPparams, $param_name);
				$arParams[$param_name] = $paramval;
			}
			elseif (substr($paramval, 0, 2) == '={' && substr($paramval, -1) == '}')
			{
				$arPHPparams[] = $parentParamName ? $parentParamName : $param_name;
			}
		}
	}

	function _ReplaceNonLatin($str)
	{
		return preg_replace("/[^a-zA-Z0-9_:\\.!\$\\-;@\\^\\~]/is", "", $str);
	}

	public static function GetLangSwitcherArray()
	{
		return CMain::GetSiteSwitcherArray();
	}

	public static function GetSiteSwitcherArray()
	{
		$cur_dir = $this->GetCurDir();
		$cur_page = $this->GetCurPage();
		$bAdmin = (substr($cur_dir, 0, strlen(BX_ROOT."/admin/")) == BX_ROOT."/admin/");

		$path_without_lang = $path_without_lang_tmp = "";

		$db_res = CSite::GetList($by, $order, array("ACTIVE"=>"Y","ID"=>LANG));
		if(($ar = $db_res->Fetch()) && strpos($cur_page, $ar["DIR"])===0)
		{
			$path_without_lang = substr($cur_page, strlen($ar["DIR"])-1);
			$path_without_lang = LTrim($path_without_lang, "/");
			$path_without_lang_tmp = RTrim($path_without_lang, "/");
		}

		$result = array();
		$db_res = CSite::GetList($by="SORT", $order="ASC", array("ACTIVE"=>"Y"));
		while($ar = $db_res->Fetch())
		{
			$ar["NAME"] = htmlspecialcharsbx($ar["NAME"]);
			$ar["SELECTED"] = ($ar["LID"]==LANG);

			if($bAdmin)
			{
				global $QUERY_STRING;
				$p = rtrim(str_replace("&#", "#", preg_replace("/lang=[^&#]*&*/", "", $QUERY_STRING)), "&");
				$ar["PATH"] = $this->GetCurPage()."?lang=".$ar["LID"].($p <> ''? '&'.$p : '');
			}
			else
			{
				$ar["PATH"] = "";

				if(strlen($path_without_lang)>1 && file_exists($ar["ABS_DOC_ROOT"]."/".$ar["DIR"]."/".$path_without_lang_tmp))
					$ar["PATH"] = $ar["DIR"].$path_without_lang;

				if(strlen($ar["PATH"])<=0)
					$ar["PATH"] = $ar["DIR"];

				if($ar["ABS_DOC_ROOT"]!==$_SERVER["DOCUMENT_ROOT"])
					$ar["FULL_URL"] = (CMain::IsHTTPS() ? "https://" : "http://").$ar["SERVER_NAME"].$ar["PATH"];
				else
					$ar["FULL_URL"] = $ar["PATH"];
			}

			$result[] = $ar;
		}
		return $result;
	}

	/*
	Returns an array of roles for a module
	W - max rights (admin)
	D - min rights (access denied)

	$module_id - a module id
	$arGroups - array of groups ID, if not set then for current useer
	$use_default_role - "Y" - use default role
	$max_role_for_super_admin - "Y" - for group ID=1 return max rights
	*/
	
	/**
	 * <p>Возвращает массив ролей в рамках логики модуля для определённого набора групп (по умолчанию - это группы текущего пользователя). <br><br> Как правило в каждом модуле определены свои символы означающие ту или иную роль. Установка своего уникального набора ролей для каждого модуля осуществляется методом GetModuleRightList класса с именем равным ID модуля. Например, для модуля техподдержки, это будет метод <b>support::GetModuleRightList()</b>, описанный в файле <b>/bitrix/modules/support/install/index.php</b>. Администрирование ролей обычно осуществляется в настройках соответствующего модуля. </p> <p class="note">Для любого модуля роль с максимальными правами (администратор модуля) всегда обозначается символом "W", с минимальным правами - символом "D" (доступ к модулю закрыт).</p>
	 *
	 *
	 *
	 *
	 * @param string $module_id  ID модуля.
	 *
	 *
	 *
	 * @param mixed $groups = false Массив групп для которых необходимо определить их роли. Если
	 * значение - "false", то будет взят массив групп текущего пользователя.
	 * <br>Необязательный. По умолчанию - "false".
	 *
	 *
	 *
	 * @param string $use_default = "Y" Если значение - "Y", то для определения массива ролей будет
	 * учитываться роль установленная по умолчанию. <br>Необязательный.
	 * По умолчанию - "Y".
	 *
	 *
	 *
	 * @param string $max_for_admin = "Y" Если значение - "Y" и <i>groups</i> = false, то пользователю входящему в
	 * группу администраторов (группа #1) всегда будет возвращаться
	 * массив ролей с обязательно включенной туда ролью с максимальными
	 * правами - "W". <br>Необязательный. По умолчанию - "Y".
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // получим массив ролей текущего пользователя в модуле "Техподдержка"
	 * $arRoles = <b>$APPLICATION-&gt;GetUserRoles</b>("support");
	 * 
	 * if(in_array("R",$arRoles)) 
	 *     $strNote = "У вас есть право задавать вопросы техподдержке.";
	 * 
	 * if(in_array("T",$arRoles)) 
	 *     $strNote = "Вы являетесь сотрудником техподдержки.";
	 * 
	 * if(in_array("V",$arRoles)) 
	 *     $strNote = "Вы можете просматривать все обращения техподдержки без права модификации.";
	 * 
	 * if(in_array("W",$arRoles)) 
	 *     $strNote = "Вы являетесь администратором техподдержки.";
	 * 
	 * if ($arRoles==array("D"))
	 *     $APPLICATION-&gt;AuthForm("Доступ к модулю техподдержки запрещён.");
	 * 
	 * ShowNote($strNote);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getuserright.php">CMain::GetUserRight</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/getmodulerightlist.php">CModule::GetModuleRightList</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getuserroles.php
	 * @author Bitrix
	 */
	public static function GetUserRoles($module_id, $arGroups=false, $use_default_role="Y", $max_role_for_super_admin="Y", $site_id=false)
	{
		global $DB, $USER;
		static $MODULE_ROLES = array();

		$err_mess = (CAllMain::err_mess())."<br>Function: GetUserRoles<br>Line: ";
		$arRoles = array();
		$min_role = "D";
		$max_role = "W";
		if($arGroups===false)
		{
			if(is_object($USER))
				$arGroups = $USER->GetUserGroupArray();
			if(!is_array($arGroups))
				$arGroups[] = 2;
		}
		$key = $use_default_role."_".$max_role_for_super_admin;
		$groups = '';
		if(is_array($arGroups) && count($arGroups)>0)
		{
			foreach($arGroups as $grp)
				$groups .= ($groups<>''? ',':'').intval($grp);
			$key .= "_".$groups;
		}

		$cache_site_key = ($site_id ? $site_id : "COMMON");

		if(isset($MODULE_ROLES[$module_id][$cache_site_key][$key]))
		{
			$arRoles = $MODULE_ROLES[$module_id][$cache_site_key][$key];
		}
		else
		{
			if(is_array($arGroups) && count($arGroups)>0)
			{
				if(in_array(1,$arGroups) && $max_role_for_super_admin=="Y")
					$arRoles[] = $max_role;

				$strSql =
					"SELECT MG.G_ACCESS FROM b_group G ".
					"	LEFT JOIN b_module_group MG ON (G.ID = MG.GROUP_ID ".
					"		AND MG.MODULE_ID = '".$DB->ForSql($module_id,50)."') ".
					"		AND MG.SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL")." ".
					"WHERE G.ID in (".$groups.") AND G.ACTIVE = 'Y'";

				$t = $DB->Query($strSql, false, $err_mess.__LINE__);

				$default_role = $min_role;
				if($use_default_role=="Y")
					$default_role = COption::GetOptionString($module_id, "GROUP_DEFAULT_RIGHT", $min_role);

				while ($tr = $t->Fetch())
				{
					if ($tr["G_ACCESS"] !== null)
					{
						$arRoles[] = $tr["G_ACCESS"];
					}
					else
					{
						if($use_default_role=="Y")
							$arRoles[] = $default_role;
					}
				}

			}
			//if($use_default_role=="Y")
			//{
			//	$arRoles[] = COption::GetOptionString($module_id, "GROUP_DEFAULT_RIGHT", $min_role);
			//}
			$arRoles = array_unique($arRoles);
			$MODULE_ROLES[$module_id][$cache_site_key][$key] = $arRoles;
		}
		return $arRoles;
	}

	/*
	Returns an array of rights for a module
	W - max rights (admin)
	D - min rights (access denied)

	$module_id - a module id
	$arGroups - array of groups ID, if not set then for current useer
	$use_default_level - "Y" - use default role
	$max_right_for_super_admin - "Y" - for group ID=1 return max rights
	*/
	
	/**
	 * <p>Возвращает право в рамках логики модуля установленное для определённого набора групп (по умолчанию - это группы текущего пользователя). <br><br> Как правило в каждом модуле определены свои символы означающие то или иной право, в противном случае используются значения по умолчанию: </p> <ul> <li> <b>D</b> - доступ к модулю запрещён </li> <li> <b>R</b> - право на просмотр страниц модуля (без права модификации) </li> <li> <b>W</b> - право на модификацию данных модуля </li> </ul> Установка своего уникального набора прав для каждого модуля осуществляется методом GetModuleRightList класса с именем равным ID модуля. Например для модуля веб-форм, это будет метод <b>form::GetModuleRightList</b>() описаный в файле <b>/bitrix/modules/form/install/index.php</b>. Администрирование прав обычно осуществляется в настройках соответствующего модуля. <br><br>Для некоторых модулей (например, "информационные блоки") права устанавливаются индивидуально и к ним данная функция не применима, некоторые модули (например, "компрессия") вовсе не имеют прав доступа. <p class="note">Для любого модуля максимальное право (полный доступ к модулю) всегда обозначается символом "W", минимальное право - символом "D" (доступ к модулю закрыт).</p>
	 *
	 *
	 *
	 *
	 * @param string $module_id  ID модуля.
	 *
	 *
	 *
	 * @param mixed $groups = false Массив групп для которых необходимо определить максимальное
	 * право. Если значение - "false", то будет взят массив групп текущего
	 * пользователя. <br>Необязательный. По умолчанию - "false".
	 *
	 *
	 *
	 * @param string $use_default = "Y" Если значение - "Y", то для определения максимального уровня прав
	 * будет учитываться уровень прав установленный по умолчанию.
	 * <br>Необязательный. По умолчанию - "Y".
	 *
	 *
	 *
	 * @param string $max_for_admin = "Y" Если значение - "Y" и <i>groups</i> = false, то пользователю входящему в
	 * группу администраторов (группа #1) всегда будет возвращаться
	 * максимальное право - "W", независимо от того какие права
	 * установлены в настройках модуля. <br>Необязательный. По умолчанию -
	 * "Y".
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // получим максимальное право доступа к модулю "Веб-формы" для текущего пользователя
	 * if(<b>$APPLICATION-&gt;GetUserRight</b>("form") &lt;= "D") 
	 *     $APPLICATION-&gt;AuthForm("Доступ к модулю запрещён.");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" >Права
	 * доступа</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getuserroles.php">CMain::GetUserRoles</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmodule/getmodulerightlist.php">CModule::GetModuleRightList</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getuserright.php
	 * @author Bitrix
	 */
	public static function GetUserRight($module_id, $arGroups=false, $use_default_level="Y", $max_right_for_super_admin="Y", $site_id=false)
	{
		global $DB, $USER, $MODULE_PERMISSIONS;
		$err_mess = (CAllMain::err_mess())."<br>Function: GetUserRight<br>Line: ";
		$min_right = "D";
		$max_right = "W";
		if($arGroups===false)
		{
			if(is_object($USER))
			{
				if($USER->IsAdmin())
					return $max_right;
				$arGroups = $USER->GetUserGroupArray();
			}
			if(!is_array($arGroups))
				$arGroups = array(2);
		}

		$key = $use_default_level."_".$max_right_for_super_admin;
		$groups = '';
		if(is_array($arGroups) && count($arGroups)>0)
		{
			foreach($arGroups as $grp)
				$groups .= ($groups<>''? ',':'').intval($grp);
			$key .= "_".$groups;
		}


		if (!$site_id)
		{
			$cache_site_key = "COMMON";
		}
		elseif(is_array($site_id))
		{
			$cache_site_key = "";
			foreach($site_id as $i => $site_id_tmp)
			{
				if ($i > 0)
					$cache_site_key .= "_";

				$cache_site_key .= ($site_id_tmp ? $site_id_tmp : "COMMON");
			}
		}
		else
		{
			$cache_site_key = $site_id;
		}

		if(!is_array($MODULE_PERMISSIONS[$module_id][$cache_site_key]))
			$MODULE_PERMISSIONS[$module_id][$cache_site_key] = array();

		$right = "";
		if(is_set($MODULE_PERMISSIONS[$module_id][$cache_site_key], $key))
			$right = $MODULE_PERMISSIONS[$module_id][$cache_site_key][$key];
		else
		{
			if(is_array($arGroups) && count($arGroups)>0)
			{
				if(in_array(1, $arGroups) && $max_right_for_super_admin=="Y" && (COption::GetOptionString("main", "controller_member", "N") != "Y" || COption::GetOptionString("main", "~controller_limited_admin", "N") != "Y"))
					$right = $max_right;
				else
				{
					if (!$site_id)
						$strSqlSite = "and MG.SITE_ID IS NULL";
					elseif(is_array($site_id))
					{
						$strSqlSite = " and (";
						foreach($site_id as $i => $site_id_tmp)
						{
							if ($i > 0)
								$strSqlSite .= " OR ";

							$strSqlSite .= "MG.SITE_ID ".($site_id_tmp ? "= '".$DB->ForSql($site_id_tmp)."'" : "IS NULL");
						}
						$strSqlSite .= ")";
					}
					else
						$strSqlSite = "and MG.SITE_ID = '".$DB->ForSql($site_id)."'";

					$strSql = "
						SELECT
							max(MG.G_ACCESS) G_ACCESS
						FROM
							b_module_group MG
						INNER JOIN b_group G ON (MG.GROUP_ID = G.ID)
						WHERE
							MG.MODULE_ID = '".$DB->ForSql($module_id,50)."'
						and MG.GROUP_ID in (".$groups.")
						and G.ACTIVE = 'Y'
						".$strSqlSite;

					$t = $DB->Query($strSql, false, $err_mess.__LINE__);
					$tr = $t->Fetch();
					$right = $tr["G_ACCESS"];
				}
			}

			if($right == "" && $use_default_level=="Y")
				$right = COption::GetOptionString($module_id, "GROUP_DEFAULT_RIGHT", $min_right);

			if($right <> "")
			{
				if(!is_array($MODULE_PERMISSIONS[$module_id][$cache_site_key]))
					$MODULE_PERMISSIONS[$module_id][$cache_site_key] = array();
				$MODULE_PERMISSIONS[$module_id][$cache_site_key][$key] = $right;
			}
		}
		return $right;
	}

	public static function GetGroupRightList($arFilter, $site_id=false)
	{
		global $DB;

		$strSqlWhere = "";
		if (array_key_exists("MODULE_ID", $arFilter))
			$strSqlWhere .= " AND MODULE_ID = '".$DB->ForSql($arFilter["MODULE_ID"])."' ";
		if (array_key_exists("GROUP_ID", $arFilter))
			$strSqlWhere .= " AND GROUP_ID = ".IntVal($arFilter["GROUP_ID"])." ";
		if (array_key_exists("G_ACCESS", $arFilter))
			$strSqlWhere .= " AND G_ACCESS = '".$DB->ForSql($arFilter["G_ACCESS"])."' ";
		$strSqlWhere .= " AND SITE_ID ".($site_id? "= '".$DB->ForSql($site_id)."'" : "IS NULL");

		$dbRes = $DB->Query(
			"SELECT ID, MODULE_ID, GROUP_ID, G_ACCESS ".
			"FROM b_module_group ".
			"WHERE 1 = 1 ".
			$strSqlWhere
		);

		return $dbRes;
	}

	public static function GetGroupRight($module_id, $arGroups=false, $use_default_level="Y", $max_right_for_super_admin="Y", $site_id = false)
	{
		return CMain::GetUserRight($module_id, $arGroups, $use_default_level, $max_right_for_super_admin, $site_id);
	}

	public static function SetGroupRight($module_id, $group_id, $right, $site_id=false)
	{
		global $DB;
		$err_mess = (CAllMain::err_mess())."<br>Function: SetGroupRight<br>Line: ";
		$group_id = intval($group_id);

		if(COption::GetOptionString("main", "event_log_module_access", "N") === "Y")
		{
			//get old value
			$sOldRight = "";
			$rsRight = $DB->Query("SELECT G_ACCESS FROM b_module_group WHERE MODULE_ID='".$DB->ForSql($module_id,50)."' AND GROUP_ID=".$group_id." AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL"));
			if($arRight = $rsRight->Fetch())
				$sOldRight = $arRight["G_ACCESS"];
			if($sOldRight <> $right)
				CEventLog::Log("SECURITY", "MODULE_RIGHTS_CHANGED", "main", $group_id, $module_id.($site_id ? "/".$site_id : "").": (".$sOldRight.") => (".$right.")");
		}

		$arFields = array(
			"MODULE_ID"	=> "'".$DB->ForSql($module_id,50)."'",
			"GROUP_ID"	=> $group_id,
			"G_ACCESS"	=> "'".$DB->ForSql($right,255)."'"
			);

		$rows = $DB->Update("b_module_group", $arFields, "WHERE MODULE_ID='".$DB->ForSql($module_id,50)."' AND GROUP_ID='".$group_id."' AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL"), $err_mess.__LINE__);
		if(intval($rows)<=0)
		{
			if ($site_id)
				$arFields["SITE_ID"] = "'".$DB->ForSql($site_id,2)."'";

			$DB->Insert("b_module_group",$arFields, $err_mess.__LINE__);
		}
	}

	public static function DelGroupRight($module_id='', $arGroups=array(), $site_id=false)
	{
		global $DB;
		$err_mess = (CAllMain::err_mess())."<br>Function:  DelGroupRight<br>Line: ";
		$strSql = '';

		$sGroups = '';
		if(is_array($arGroups) && count($arGroups)>0)
			foreach($arGroups as $grp)
				$sGroups .= ($sGroups <> ''? ',':'').intval($grp);

		if($module_id <> '')
		{
			if($sGroups <> '')
			{
				if(COption::GetOptionString("main", "event_log_module_access", "N") === "Y")
				{
					//get old value
					$rsRight = $DB->Query("SELECT GROUP_ID, G_ACCESS FROM b_module_group WHERE MODULE_ID='".$DB->ForSql($module_id,50)."' AND GROUP_ID IN (".$sGroups.") AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL"));
					while($arRight = $rsRight->Fetch())
						CEventLog::Log("SECURITY", "MODULE_RIGHTS_CHANGED", "main", $arRight["GROUP_ID"], $module_id.($site_id ? "/".$site_id : "").": (".$arRight["G_ACCESS"].") => ()");
				}
				$strSql = "DELETE FROM b_module_group WHERE MODULE_ID='".$DB->ForSql($module_id,50)."' and GROUP_ID in (".$sGroups.") AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL");
			}
			else
			{
				//on delete module
				$strSql = "DELETE FROM b_module_group WHERE MODULE_ID='".$DB->ForSql($module_id,50)."' AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL");
			}
		}
		elseif($sGroups <> '')
		{
			//on delete user group
			$strSql = "DELETE FROM b_module_group WHERE GROUP_ID in (".$sGroups.") AND SITE_ID ".($site_id ? "= '".$DB->ForSql($site_id)."'" : "IS NULL");
		}

		if($strSql <> '')
			$DB->Query($strSql, false, $err_mess.__LINE__);
	}

	public static function GetMainRightList()
	{
		$arr = array(
			"reference_id" => array(
				"D",
				"P",
				"R",
				"T",
				"V",
				"W"),
			"reference" => array(
				"[D] ".GetMessage("OPTION_DENIED"),
				"[P] ".GetMessage("OPTION_PROFILE"),
				"[R] ".GetMessage("OPTION_READ"),
				"[T] ".GetMessage("OPTION_READ_PROFILE_WRITE"),
				"[V] ".GetMessage("OPTION_READ_OTHER_PROFILES_WRITE"),
				"[W] ".GetMessage("OPTION_WRITE"))
			);
		return $arr;
	}

	public static function GetDefaultRightList()
	{
		$arr = array(
			"reference_id" => array("D","R","W"),
			"reference" => array(
				"[D] ".GetMessage("OPTION_DENIED"),
				"[R] ".GetMessage("OPTION_READ"),
				"[W] ".GetMessage("OPTION_WRITE"))
			);
		return $arr;
	}

	public static function err_mess()
	{
		return "<br>Class: CAllMain<br>File: ".__FILE__;
	}

	/*
	Returns a cookie value by the name

	$name			: cookie name (without prefix)
	$name_prefix	: name prefix (if not set get from options)
	*/
	
	/**
	 * <p>Возвращает значение cookie.</p>
	 *
	 *
	 *
	 *
	 * @param string $name  Имя cookie переменной.
	 *
	 *
	 *
	 * @param mixed $name_prefix = false Префикс имени переменной cookie.<br>Необязательный. По умолчанию -
	 * значение параметра "Имя префикса для названия cookies" в настройках
	 * главного модуля (значение данного параметра можно получить с
	 * помощью функции: <pre>COption::GetOptionString("main", "cookie_name", "BITRIX_SM")</pre>
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * global $APPLICATION;
	 * $VISITOR_ID = <b>$APPLICATION-&gt;get_cookie</b>("VISITOR_ID");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=285"
	 * >Технология переноса посетителей</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/set_cookie.php">CMain::set_cookie</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showspreadcookiehtml.php">CMain::ShowSpreadCookieHTML</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/get_cookie.php
	 * @author Bitrix
	 */
	public static function get_cookie($name, $name_prefix=false)
	{
		if($name_prefix===false)
			$name = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".$name;
		else
			$name = $name_prefix."_".$name;
		return (isset($_COOKIE[$name])? $_COOKIE[$name] : "");
	}

	/*
	Sets a cookie and spreads it through domains

	$name			: cookie name (without prefix)
	$value			: value
	$time			: expire date
	$folder			: cookie dir
	$domain			: cookie domain
	$secure			: secure flag
	$spread			: to spread or not to spread
	$name_prefix	: name prefix (if not set get from options)
	*/
	
	/**
	 * <p>Устанавливает cookie и при необходимости запоминает параметры установленного cookie для дальнейшего распространения по сайтам с разными доменными именами.</p>
	 *
	 *
	 *
	 *
	 * @param string $name  Имя cookie переменной.
	 *
	 *
	 *
	 * @param string $value  Значение cookie переменной.
	 *
	 *
	 *
	 * @param mixed $expire = false Дата в Unix-формате после которой cookie будет считаться истекшим и
	 * его значение не будет передаваться от посетителя на
	 * сайт.<br>Необязательный. По умолчанию - cookie устанавливается сроком
	 * на 1 год.
	 *
	 *
	 *
	 * @param string $path = "/" Каталог веб-сайта для которого cookie будет
	 * действителен.<br>Необязательный. По умолчанию - весь сайт.
	 *
	 *
	 *
	 * @param mixed $domain = false Домен для которого cookie будет действительным.<br>Необязательный. По
	 * умолчанию - текущий сайт.
	 *
	 *
	 *
	 * @param bool $secure = false Флаг secure для устанавливаемого cookie. Если значение "true", то будет
	 * установлен как "защищенный", т.е. его значение будет возвращаться
	 * на сайт только если посетитель зашел на сайт по протоколу
	 * HTTPS.<br>Необязательный. По умолчанию - "false".
	 *
	 *
	 *
	 * @param bool $spread = true Если значение "true", то параметры установленного cookie будут
	 * запоминаться для дальнейшего его распространения по доменам
	 * (административное меню "Сайты", в настройках сайта - поле "Доменное
	 * имя").
	 *
	 *
	 *
	 * @param mixed $name_prefix = false Префикс имени переменной cookie.<br>Необязательный. По умолчанию -
	 * значение параметра "Имя префикса для названия cookies" в настройках
	 * главного модуля (значение данного параметра можно получить с
	 * помощью функции: <pre>COption::GetOptionString("main", "cookie_name", "BITRIX_SM")</pre>
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * global $APPLICATION;
	 * // устновим cookie на 2 года, действительного только для каталога /ru/
	 * <b>$APPLICATION-&gt;set_cookie</b>("RUSSIAN_VISITOR_ID", 156, time()+60*60*24*30*12*2, "/ru/");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=285"
	 * >Технология переноса посетителей</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/get_cookie.php">CMain::get_cookie</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showspreadcookiehtml.php">CMain::ShowSpreadCookieHTML</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/set_cookie.php
	 * @author Bitrix
	 */
	public static function set_cookie($name, $value, $time=false, $folder="/", $domain=false, $secure=false, $spread=true, $name_prefix=false)
	{
		if($time === false)
			$time = time()+60*60*24*30*12; // 30 days * 12 ~ 1 year
		if($name_prefix===false)
			$name = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".$name;
		else
			$name = $name_prefix."_".$name;

		if($domain === false)
			$domain = $this->GetCookieDomain();

		if($spread === "Y" || $spread === true)
			$spread_mode = BX_SPREAD_DOMAIN | BX_SPREAD_SITES;
		elseif($spread >= 1)
			$spread_mode = $spread;
		else
			$spread_mode = BX_SPREAD_DOMAIN;

		//current domain only
		if($spread_mode & BX_SPREAD_DOMAIN)
			setcookie($name, $value, $time, $folder, $domain, $secure);

		//spread over sites
		if($spread_mode & BX_SPREAD_SITES)
			$this->arrSPREAD_COOKIE[$name] = array("V" => $value, "T" => $time, "F" => $folder, "D" => $domain, "S" => $secure);
	}

	function GetCookieDomain()
	{
		static $bCache = false;
		static $cache  = false;
		if($bCache)
			return $cache;

		global $DB;
		if(CACHED_b_lang_domain===false)
		{
			$strSql = "
				SELECT
					DOMAIN
				FROM
					b_lang_domain
				WHERE
					'".$DB->ForSql('.'.$_SERVER["HTTP_HOST"])."' like ".$DB->Concat("'%.'", "DOMAIN")."
				ORDER BY
					".$DB->Length("DOMAIN")."
				";
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			if($ar = $res->Fetch())
			{
				$cache = $ar['DOMAIN'];
			}
		}
		else
		{
			global $CACHE_MANAGER;
			if($CACHE_MANAGER->Read(CACHED_b_lang_domain, "b_lang_domain", "b_lang_domain"))
			{
				$arLangDomain = $CACHE_MANAGER->Get("b_lang_domain");
			}
			else
			{
				$arLangDomain = array("DOMAIN"=>array(), "LID"=>array());
				$res = $DB->Query("SELECT * FROM b_lang_domain ORDER BY ".$DB->Length("DOMAIN"));
				while($ar = $res->Fetch())
				{
					$arLangDomain["DOMAIN"][]=$ar;
					$arLangDomain["LID"][$ar["LID"]][]=$ar;
				}
				$CACHE_MANAGER->Set("b_lang_domain", $arLangDomain);
			}
			//$strSql = "'".$DB->ForSql($_SERVER["HTTP_HOST"])."' like ".$DB->Concat("'%.'", "DOMAIN")."";
			foreach($arLangDomain["DOMAIN"] as $ar)
			{
				if(strcasecmp(substr('.'.$_SERVER["HTTP_HOST"], -(strlen($ar['DOMAIN'])+1)), ".".$ar['DOMAIN']) == 0)
				{
					$cache = $ar['DOMAIN'];
					break;
				}
			}
		}

		$bCache = true;
		return $cache;
	}

	public static function StoreCookies()
	{
		$_SESSION['SPREAD_COOKIE'] = $this->arrSPREAD_COOKIE;
	}

	// Returns string with images to spread cookies
	function GetSpreadCookieHTML()
	{
		static $showed_already;
		$res = "";
		if($showed_already!="Y" && COption::GetOptionString("main", "ALLOW_SPREAD_COOKIE", "Y")=="Y")
		{
			if(isset($_SESSION['SPREAD_COOKIE']) && is_array($_SESSION['SPREAD_COOKIE']) && !empty($_SESSION['SPREAD_COOKIE']))
			{
				$this->arrSPREAD_COOKIE += $_SESSION['SPREAD_COOKIE'];
				unset($_SESSION['SPREAD_COOKIE']);
			}

			if(!empty($this->arrSPREAD_COOKIE))
			{
				$params = "";
				reset($this->arrSPREAD_COOKIE);
				while (list($name,$ar)=each($this->arrSPREAD_COOKIE))
				{
					$ar["D"] = ""; // domain must be empty
					$params .= $name.chr(1).$ar["V"].chr(1).$ar["T"].chr(1).$ar["F"].chr(1).$ar["D"].chr(1).$ar["S"].chr(2);
				}
				$salt = $_SERVER["REMOTE_ADDR"]."|".@filemtime($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php")."|".LICENSE_KEY;
				$params = "s=".urlencode(base64_encode($params))."&k=".urlencode(md5($params.$salt));
				$arrDomain = array();
				$arrDomain[] = $_SERVER["HTTP_HOST"];
				$v1 = "sort";
				$v2 = "asc";
				$rs = CSite::GetList($v1, $v2, array("ACTIVE" => "Y"));
				while($ar = $rs->Fetch())
				{
					$arD = explode("\n", str_replace("\r", "\n", $ar["DOMAINS"]));
					if(is_array($arD) && count($arD)>0)
						foreach($arD as $d)
							if(strlen(trim($d))>0)
								$arrDomain[] = $d;
				}

				if(count($arrDomain)>0)
				{
					$arUniqDomains = array();
					$arrDomain = array_unique($arrDomain);
					$arrDomain2 = array_unique($arrDomain);
					foreach($arrDomain as $domain1)
					{
						$bGood = true;
						foreach($arrDomain2 as $domain2)
						{
							if(strlen($domain1)>strlen($domain2) && substr($domain1, -(strlen($domain2)+1)) == ".".$domain2)
							{
								$bGood = false;
								break;
							}
						}
						if($bGood)
							$arUniqDomains[] = $domain1;
					}

					$protocol = (CMain::IsHTTPS()) ? "https://" : "http://";
					$arrCurUrl = parse_url($protocol.$_SERVER["HTTP_HOST"]."/".$_SERVER["REQUEST_URI"]);
					foreach($arUniqDomains as $domain)
					{
						if(strlen(trim($domain))>0)
						{
							$url = $protocol.$domain."/bitrix/spread.php?".$params;
							$arrUrl = parse_url($url);
							if($arrUrl["host"] != $arrCurUrl["host"])
								$res .= '<img src="'.htmlspecialcharsbx($url).'" alt="" style="width:0px; height:0px; position:absolute; left:-1px; top:-1px;" />'."\n";
						}
					}
				}
				$showed_already = "Y";
			}
		}
		return $res;
	}

	
	/**
	 * <p>Отображает HTML представляющий из себя набор IFRAME'ов предназначенный для распространения cookie по доменам. Данная функция используется в <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=285" >Технология переноса посетителей</a> между разными сайтами. Она стандартно включена в визуальную часть эпилога.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=285"
	 * >Технология переноса посетителей</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/set_cookie.php">CMain::set_cookie</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/get_cookie.php">CMain::get_cookie</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showspreadcookiehtml.php
	 * @author Bitrix
	 */
	public static function ShowSpreadCookieHTML()
	{
		$this->AddBufferContent(array(&$this, "GetSpreadCookieHTML"));
	}

	
	/**
	 * <p>Добавляет в <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">панель управления</a> кнопку.</p>
	 *
	 *
	 *
	 *
	 * @param array $button  Массив описывающий добавляемую кнопку. Ключи массива:<br><ul> <li>
	 * <b>HREF</b> - ссылка на кнопке </li> <li> <b>SRC</b> - путь от корня сайта к
	 * картинке которая будет выведена на кнопке </li> <li> <b>ALT</b> - текст
	 * всплывающей подсказки на кнопке </li> <li> <b>MAIN_SORT</b> - индекс
	 * сортировки для группы кнопок, для стандартных групп иконок
	 * данный параметр имеет следующие значения: <ul> <li>100 - группа иконок
	 * модуля управления статикой </li> <li>200 - группа иконок модуля
	 * документооборота </li> <li>300 - группа иконок модуля информационных
	 * блоков </li> </ul> </li> <li> <b>SORT</b> - индекс сортировки внутри группы
	 * кнопок </li> <li> <b>TYPE</b> - (BIG/SMALL) размер иконки. (По умолчанию "SMALL".) </li>
	 * <li> <b>HINT</b> - Массив с ключами: <ul> <li> <b>TITLE</b> - Заголовок всплывающей
	 * подсказки;</li> <li> <b>TEXT</b> - Текст всплывающей подсказки.</li> </ul> </li> <li>
	 * <b>ICON</b> - CSS иконки. </li> <li> <b>TEXT</b> - Текст кнопки. </li> </ul> Если у
	 * пользователя не хватает прав на ту или иную операцию и вы хотите в
	 * любом случае вывести кнопку, то необходимо <b>HREF</b> оставлять
	 * пустым, при этом кнопка будет выведена черно-белой и без ссылки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // получим уровень доступа к модулю "Веб-формы"
	 * $FORM_RIGHT = $APPLICATION-&gt;GetUserRight("form");
	 * // если доступ есть то
	 * if($FORM_RIGHT&gt;"D"):
	 *     // добавим в панель кнопку ведущую на список веб-форм
	 *     <b>$APPLICATION-&gt;AddPanelButton</b>(array(
	 *         "HREF"      =&gt; "/bitrix/admin/form_list.php", 
	 *         "SRC"       =&gt; "/bitrix/images/fileman/panel/web_form.gif", 
	 *         "ALT"       =&gt; "Редактировать веб-форму", 
	 *         "MAIN_SORT" =&gt; 400, 
	 *         "SORT"      =&gt; 100
	 *     ));
	 * endif;
	 * ?&gt;Подменю кнопки (на примере кнопки стикеров):MENU =&gt; Array(
	 *  [0] =&gt; Array(
	 *   [TEXT] =&gt; &lt;div style="float: left; margin: 0 50px 0 0;"&gt;Наклеить стикер&lt;/div&gt;
	 *   [TITLE] =&gt; Наклеить новый стикер на страницу
	 *   [ICON] =&gt;
	 *   [ACTION] =&gt; if (wind ow .oBXSticker){window .oBXSticker.AddSticker();}
	 *   [DEFAULT] =&gt; 1
	 *   [HK_ID] =&gt; FMST_PANEL_STICKER_ADD
	 *  )
	 *  [1] =&gt; Array(
	 *   [SEPARATOR] =&gt; 1
	 *  )
	 * )
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">Панель управления</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showpanel.php">CMain::ShowPanel</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/events/onpanelcreate.php">Событие "OnPanelCreate"</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addpanelbutton.php
	 * @author Bitrix
	 */
	public static function AddPanelButton($arButton, $bReplace=false)
	{
		if(is_array($arButton) && count($arButton)>0)
		{
			if(isset($arButton["ID"]) && $arButton["ID"] <> "")
			{
				if(!isset($this->arPanelButtons[$arButton["ID"]]))
				{
					$this->arPanelButtons[$arButton["ID"]] = $arButton;
				}
				elseif($bReplace)
				{
					if(
						isset($this->arPanelButtons[$arButton["ID"]]["MENU"])
						&& is_array($this->arPanelButtons[$arButton["ID"]]["MENU"])
					)
					{
						if(!is_array($arButton["MENU"]))
							$arButton["MENU"] = array();
						$arButton["MENU"] = array_merge($this->arPanelButtons[$arButton["ID"]]["MENU"], $arButton["MENU"]);
					}
					$this->arPanelButtons[$arButton["ID"]] = $arButton;
				}

				if (isset($this->arPanelFutureButtons[$arButton['ID']]))
				{
					if (
						isset($this->arPanelButtons[$arButton["ID"]]["MENU"])
						&& is_array($this->arPanelButtons[$arButton["ID"]]["MENU"])
					)
					{
						$this->arPanelButtons[$arButton["ID"]]["MENU"] = array_merge(
							$this->arPanelButtons[$arButton["ID"]]["MENU"],
							$this->arPanelFutureButtons[$arButton["ID"]]
						);
					}
					else
					{
						$this->arPanelButtons[$arButton["ID"]]["MENU"] = $this->arPanelFutureButtons[$arButton["ID"]];
					}
					unset($this->arPanelFutureButtons[$arButton['ID']]);
				}
			}
			else
			{
				$this->arPanelButtons[] = $arButton;
			}
		}
	}

	public static function AddPanelButtonMenu($button_id, $arMenuItem)
	{
		if(isset($this->arPanelButtons[$button_id]))
		{
			if(!is_array($this->arPanelButtons[$button_id]['MENU']))
				$this->arPanelButtons[$button_id]['MENU'] = array();
			$this->arPanelButtons[$button_id]['MENU'][] = $arMenuItem;
		}
		else
		{
			if(!isset($this->arPanelFutureButtons[$button_id]))
				$this->arPanelFutureButtons[$button_id] = array();

			$this->arPanelFutureButtons[$button_id][] = $arMenuItem;
		}
	}

	
	/**
	 * <p>Возвращает HTML представляющий из себя панель управления публичной частью.</p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showpanel.php">CMain::ShowPanel</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addpanelbutton.php">CMain::AddPanelButton</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getpanel.php
	 * @author Bitrix
	 */
	public static function GetPanel()
	{
		global $USER;

		if(isset($GLOBALS["USER"]) && is_object($USER) && $USER->IsAuthorized() && !isset($_REQUEST["bx_hit_hash"]))
			echo CTopPanel::GetPanelHtml();
	}

	
	/**
	 * <p>Отображает <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">панель управления</a> в публичной части сайта. <br>Данная функция использует технологию <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >отложенных функций</a>, позволяющую, помимо всего прочего, добавлять кнопку в панель управления уже после того как будет выведен пролог сайта.<br><br> Если у пользователя не хватает прав ни на одну операцию задаваемую кнопками <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">панели управления</a>, то панель выведена не будет. Если вам необходимо вывести панель в обязательном порядке, необходимо задать в теле страницы: </p> <pre>$APPLICATION-&gt;ShowPanel = true;</pre>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"&gt;
	 * &lt;html&gt;
	 * &lt;head&gt;
	 * &lt;meta http-equiv="Content-Type" content="text/html; charset=&lt;?=LANG_CHARSET;?&gt;"&gt;
	 * &lt;META NAME="ROBOTS" content="ALL"&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("keywords")?&gt;
	 * &lt;?$APPLICATION-&gt;ShowMeta("description")?&gt;
	 * &lt;title&gt;&lt;?$APPLICATION-&gt;ShowTitle()?&gt;&lt;/title&gt;
	 * &lt;?$APPLICATION-&gt;ShowCSS();?&gt;
	 * &lt;/head&gt;
	 * &lt;body link="#525252" alink="#F1555A" vlink="#939393" text="#000000"&gt;
	 * &lt;?<b>$APPLICATION-&gt;ShowPanel</b>();?&gt;
	 * ...
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/general/panel.php">Панель управления</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >Отложенные
	 * функции</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/addpanelbutton.php">CMain::AddPanelButton</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/events/onpanelcreate.php">Событие "OnPanelCreate"</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showpanel.php
	 * @author Bitrix
	 */
	public static function ShowPanel()
	{
		global $USER;

		if(isset($GLOBALS["USER"]) && is_object($USER) && $USER->IsAuthorized() && !isset($_REQUEST["bx_hit_hash"]))
		{
			class_exists('CTopPanel'); //http://bugs.php.net/bug.php?id=47948
			AddEventHandler('main', 'OnBeforeEndBufferContent', array('CTopPanel', 'InitPanel'));
			$this->AddBufferContent(array('CTopPanel', 'GetPanelHtml'));

			//Prints global url classes and  variables for HotKeys
			$this->AddBufferContent(array('CAllMain',"PrintHKGlobalUrlVar"));

			//Prints global url classes and  variables for Stickers
			$this->AddBufferContent(array('CSticker',"InitJsAfter"));

			$this->AddBufferContent(array('CAdminInformer',"PrintHtmlPublic"));
		}
	}

	public static function PrintHKGlobalUrlVar()
	{
		return CHotKeys::GetInstance()->PrintGlobalUrlVar();
	}

	public static abstract function GetLang($cur_dir=false, $cur_host=false);

	
	/**
	 * <p>Возвращает массив описывающий сайт, определяемый по указанному пути и домену. Описание ключей данного массива вы можете найти на странице <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a>. Алгоритм работы функции следующий: </p> <ol> <li>Ищем сайты для которых удовлетворяют <i>path</i> и <i>host</i>, если нашли, то возвращаем, иначе </li> <li>Ищем сайты для которых удовлетворяет <i>path</i>, если нашли, то возвращаем, иначе </li> <li>Ищем сайты для которых удовлетворяет <i>host</i>, если нашли, то возвращаем, иначе </li> <li>Возвращаем сайт с установленным флагом "Сайт по умолчанию" </li> </ol>
	 *
	 *
	 *
	 *
	 * @param mixed $path = false Путь относительно корня.<br>Необязательный. По умолчанию - путь к
	 * текущей странице.
	 *
	 *
	 *
	 * @param mixed $host = false Имя домена.<br>Необязательный. По умолчанию - текущий домен.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // получим ссылающийся URL, либо последний URL в текущей сессии
	 * if (strlen($_SERVER["HTTP_REFERER"]) &lt;= 0)
	 *     $referer_url = $_SESSION["SESS_HTTP_REFERER"];
	 * else 
	 *     $referer_url = $_SERVER["HTTP_REFERER"];
	 * 
	 * // пропарсим URL чтобы отдельно получить домен и адрес страницы
	 * $arUrl = parse_url($referer_url);
	 * 
	 * // получим массив описывающий сайт
	 * $arSite = <b>$APPLICATION-&gt;GetSiteByDir</b>($arUrl["path"], $arUrl["host"]);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a> </li>
	 * <li>[link=89624]Сайты[/link] </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getsitebydir.php
	 * @author Bitrix
	 */
	public static function GetSiteByDir($cur_dir=false, $cur_host=false)
	{
		return $this->GetLang($cur_dir, $cur_host);
	}

	
	/**
	 * <p>Позволяет создавать <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489" >Отложенные функции</a>.</p>
	 *
	 *
	 *
	 *
	 * @param callback $function  Имя функции выполнение которой необходимо <i>отложить</i>. Если это
	 * обычная функция то в данном параметре просто указывается ее имя,
	 * если это метод класса, то указывается массив, первым элементом
	 * которого будет имя класса (либо объект класса), а вторым - имя
	 * метода.
	 *
	 *
	 *
	 * @param mixed $parameter_1  Неограниченное количество параметров которые будут
	 * впоследствии переданы функции <i>function</i>.
	 *
	 *
	 *
	 * @param mixed $parameter_2  
	 *
	 *
	 *
	 * @param  $mixed  
	 *
	 *
	 *
	 * @param parameter_ $N  
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;? <br>function myShowProperty($property_id, $default_value=false) <br>{ <br>    global $APPLICATION; <br>    $APPLICATION-&gt;AddBufferContent(Array(&amp;$APPLICATION, "GetProperty"), $property_id, $default_value); <br>} <br>?&gt;&lt;?<br>function myShowTitle($property_name="title", $strip_tags = true)<br>{<br>    global $APPLICATION;<br>    $property_name, $strip_tags);<br>    $APPLICATION-&gt;AddBufferContent(Array(&amp;$APPLICATION, "GetTitle"),<br>}<br>?&gt;&lt;?<br>function myShowPanel()<br>{<br>    global $APPLICATION;<br>    $APPLICATION-&gt;AddBufferContent(Array(&amp;$APPLICATION, "GetPanel"));<br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3489"
	 * >Отложенные функции</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showtitle.php">CMain::ShowTitle</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showcss.php">CMain::ShowCSS</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/shownavchain.php">CMain::ShowNavChain</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showproperty.php">CMain::ShowProperty</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showmeta.php">CMain::ShowMeta</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/showpanel.php">CMain::ShowPanel</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addbuffercontent.php
	 * @author Bitrix
	 */
	public static function AddBufferContent($callback)
	{
		//var_dump($callback);
		//var_dump("-----");
		$args = array();
		$args_num = func_num_args();
		if($args_num>1)
			for($i=1; $i<$args_num; $i++)
				$args[] = func_get_arg($i);

		if(!defined("BX_BUFFER_USED") || BX_BUFFER_USED!==true)
		{
			echo call_user_func_array($callback, $args);
			return;
		}
		//var_dump(ob_get_length());
		$this->buffer_content[] = ob_get_contents();
		$this->buffer_content[] = "";
		$this->buffer_content_type[] = array("F"=>$callback, "P"=>$args);
		$this->buffer_man = true;
		$this->auto_buffer_cleaned = false;
		ob_end_clean();
		$this->buffer_man = false;
		$this->buffered = true;
		if($this->auto_buffer_cleaned) // cross buffer fix
			ob_start(array(&$this, "EndBufferContent"));
		else
			ob_start();
	}

	public static function RestartBuffer()
	{
		$this->bShowHeadString = false;
		$this->bShowHeadScript = false;
		$this->buffer_man = true;
		ob_end_clean();
		$this->buffer_man = false;
		$this->buffer_content_type = array();
		$this->buffer_content = array();

		if(function_exists("getmoduleevents"))
		{
			foreach(GetModuleEvents("main", "OnBeforeRestartBuffer", true) as $arEvent)
				ExecuteModuleEventEx($arEvent);
		}

		ob_start(array(&$this, "EndBufferContent"));
	}

	function &EndBufferContentMan()
	{
		if(!$this->buffered)
			return null;
		$content = ob_get_contents();
		$this->buffer_man = true;
		ob_end_clean();
		$this->buffered = false;
		$this->buffer_man = false;

		$this->buffer_manual = true;
		$res = $this->EndBufferContent($content);
		$this->buffer_manual = false;

		$this->buffer_content_type = array();
		$this->buffer_content = array();
		return $res;
	}

	public static function EndBufferContent($content="")
	{
		if($this->buffer_man)
		{
			$this->auto_buffer_cleaned = true;
			return '';
		}

		if(function_exists("getmoduleevents"))
		{
			foreach(GetModuleEvents("main", "OnBeforeEndBufferContent", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array());
		}

		if(is_object($GLOBALS["APPLICATION"])) //php 5.1.6 fix: http://bugs.php.net/bug.php?id=40104
		{
			$cnt = count($this->buffer_content_type);
			for($i=0; $i<$cnt; $i++)
				$this->buffer_content[$i*2+1] = call_user_func_array($this->buffer_content_type[$i]["F"], $this->buffer_content_type[$i]["P"]);
		}

		$content = implode('', $this->buffer_content).$content;

		if(function_exists("getmoduleevents"))
		{
			foreach(GetModuleEvents("main", "OnEndBufferContent", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$content));
		}

		return $content;
	}

	
	/**
	 * <p>Метод очищает последнее исключение. </p>
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
	 * $events = GetModuleEvents("main", "OnAfterUserLogin");<br>while($arEvent = $events-&gt;Fetch())<br>{<br>   $APPLICATION-&gt;ResetException();<br>   ExecuteModuleEvent($arEvent, $login, $password, $remember, $USER_ID);<br>   if($err = $APPLICATION-&gt;GetException())<br>      $RESULT_MESSAGE = Array("MESSAGE"=&gt;$err-&gt;GetString()."&lt;br&gt;", "TYPE"=&gt;"ERROR");<br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/capplicationexception/index.php">Класс
	 * CApplicationException</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">CMain::GetException</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/resetexception.php
	 * @author Bitrix
	 */
	public static function ResetException()
	{
		if($this->LAST_ERROR)
			$this->ERROR_STACK[] = $this->LAST_ERROR;
		$this->LAST_ERROR = false;
	}

	
	/**
	 * <p>Метод фиксирует исключение <i>msg</i> c кодом <i>id</i>. Получить последнее исключение можно методом $APPLICATION-&gt;<a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getexception.php">GetException()</a></p>
	 *
	 *
	 *
	 *
	 * @param mixed $msg  Текст ошибки или объект класса, наследованного от <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/index.php">CApplicationException</a>.
	 *
	 *
	 *
	 * @param mixed $id = false Идентификатор ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if($login=='')
	 * {
	 *    global $APPLICATION;
	 *    $APPLICATION-&gt;ThrowException('Имя входа должно быть заполнено.'); 
	 *    return false;
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/resetexception.php">CMain::ResetException</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/getexception.php">CMain::GetException</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php
	 * @author Bitrix
	 */
	public static function ThrowException($msg, $id = false)
	{
		$this->ResetException();
		if(is_object($msg) && (is_subclass_of($msg, 'CApplicationException') || (strtolower(get_class($msg))=='capplicationexception')))
			$this->LAST_ERROR = $msg;
		else
			$this->LAST_ERROR = new CApplicationException($msg, $id);
	}

	
	/**
	 * <p>Метод возвращает объект класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/capplicationexception/index.php">CApplicationException</a>, содержащий последнее исключение. </p>
	 *
	 *
	 *
	 *
	 * @return CApplicationException 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if(!$langs-&gt;Delete($del_id))<br>{<br>   if($ex = $APPLICATION-&gt;GetException())<br>      $strError = $ex-&gt;GetString();
	 * }<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/capplicationexception/index.php">Класс
	 * CApplicationException</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getexception.php
	 * @author Bitrix
	 */
	public static function GetException()
	{
		return $this->LAST_ERROR;
	}

	public static function ConvertCharset($string, $charset_in, $charset_out)
	{
		$this->ResetException();

		$error = "";
		$result = CharsetConverter::ConvertCharset($string, $charset_in, $charset_out, $error);
		if (!$result && !empty($error))
			$this->ThrowException($error, "ERR_CHAR_BX_CONVERT");

		return $result;
	}

	public static function ConvertCharsetArray($arData, $charset_from, $charset_to)
	{
		if (!is_array($arData))
			return $this->ConvertCharset($arData, $charset_from, $charset_to);

		foreach ($arData as $key => $value)
		{
			$arData[$key] = $this->ConvertCharsetArray($value, $charset_from, $charset_to);
		}

		return $arData;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/captchagetcode.php
	 * @author Bitrix
	 */
	public static function CaptchaGetCode()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");

		$cpt = new CCaptcha();
		$cpt->SetCode();

		return $cpt->GetSID();
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmain/captchacheckcode.php
	 * @author Bitrix
	 */
	public static function CaptchaCheckCode($captcha_word, $captcha_sid)
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");

		$cpt = new CCaptcha();
		if ($cpt->CheckCode($captcha_word, $captcha_sid))
			return True;
		else
			return False;
	}

	public static function UnJSEscape($str)
	{
		if(strpos($str, "%u")!==false)
		{
			$str = preg_replace_callback("'%u([0-9A-F]{2})([0-9A-F]{2})'i", create_function('$ch', '$res = chr(hexdec($ch[2])).chr(hexdec($ch[1])); return $GLOBALS["APPLICATION"]->ConvertCharset($res, "UTF-16", LANG_CHARSET);'), $str);
		}
		return $str;
	}

	/**
	 * @deprecated Use CAdminFileDialog::ShowScript instead
	 */
	public static function ShowFileSelectDialog($event, $arResultDest, $arPath = array(), $fileFilter = "", $bAllowFolderSelect = False)
	{
		CAdminFileDialog::ShowScript(array(
				"event" => $event,
				"arResultDest" => $arResultDest,
				"arPath" => $arPath,
				"select" => $bAllowFolderSelect ? 'DF' : 'F',
				"fileFilter" => $fileFilter,
				"operation" => 'O',
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"allowAllFiles" => true,
				"SaveConfig" => true
		));
	}

	/*
	array(
		"URL"=> 'url to open'
		"PARAMS"=> array('param' => 'value') - additional params, 2nd argument of jsPopup.ShowDialog()
	),
	*/
	public static function GetPopupLink($arUrl)
	{
		CUtil::InitJSCore(array('window', 'ajax'));

		if (
			class_exists('CUserOptions')
			&& (
				!is_array($arUrl['PARAMS'])
				|| !isset($arUrl['PARAMS']['resizable'])
				|| $arUrl['PARAMS']['resizable'] !== false
			)
		)
		{
			$pos = strpos($arUrl['URL'], '?');
			if ($pos === false)
				$check_url = $arUrl['URL'];
			else
				$check_url = substr($arUrl['URL'], 0, $pos);

			$arPos = CUtil::GetPopupSize($check_url, $arUrl['PARAMS']);

			if ($arPos['width'])
			{
				if (!is_array($arUrl['PARAMS']))
					$arUrl['PARAMS'] = array();

				$arUrl['PARAMS']['width'] = $arPos['width'];
				$arUrl['PARAMS']['height'] = $arPos['height'];
			}
		}

		$dialog_class = 'CDialog';
		if (isset($arUrl['PARAMS']['dialog_type']) && $arUrl['PARAMS']['dialog_type'])
		{
			switch ($arUrl['PARAMS']['dialog_type'])
			{
				case 'EDITOR': $dialog_class = 'CEditorDialog'; break;
				case 'ADMIN': $dialog_class = 'CAdminDialog'; break;
				default: $dialog_class = 'CDialog';
			}
		}
		elseif (strpos($arUrl['URL'], 'bxpublic=') !== false)
		{
			$dialog_class = 'CAdminDialog';
		}

		$arDialogParams = array(
			'content_url' => $arUrl['URL'],
			'width' => null,
			'height' => null,
		);

		if (isset($arUrl['PARAMS']['width']))
			$arDialogParams['width'] = intval($arUrl['PARAMS']['width']);
		if (isset($arUrl['PARAMS']['height']))
			$arDialogParams['height'] = intval($arUrl['PARAMS']['height']);
		if (isset($arUrl['PARAMS']['min_width']))
			$arDialogParams['min_width'] = intval($arUrl['PARAMS']['min_width']);
		if (isset($arUrl['PARAMS']['min_height']))
			$arDialogParams['min_height'] = intval($arUrl['PARAMS']['min_height']);
		if (isset($arUrl['PARAMS']['resizable']) && $arUrl['PARAMS']['resizable'] === false)
			$arDialogParams['resizable'] = false;
		if (isset($arUrl['POST']) && $arUrl['POST'])
			$arDialogParams['content_post'] = $arUrl['POST'];

		return '(new BX.'.$dialog_class.'('.CUtil::PhpToJsObject($arDialogParams).')).Show()';
	}

	function GetServerUniqID()
	{
		static $uniq = null;
		if($uniq === null)
		{
			$uniq = COption::GetOptionString("main", "server_uniq_id", "");
		}
		if($uniq == '')
		{
			$uniq = md5(uniqid(rand(), true));
			COption::SetOptionString("main", "server_uniq_id", $uniq);
		}
		return $uniq;
	}

	public static function PrologActions()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;

		if (defined("BX_CHECK_SHORT_URI") && BX_CHECK_SHORT_URI)
		{
			if ($arUri = CBXShortUri::GetUri($_SERVER["REQUEST_URI"]))
			{
				CBXShortUri::SetLastUsed($arUri["ID"]);
				if (CModule::IncludeModule("statistic"))
					CStatEvent::AddCurrent("short_uri_redirect", "", "", "", "", $arUri["URI"], "N", SITE_ID);
				LocalRedirect($arUri["URI"], true, CBXShortUri::GetHttpStatusCodeText($arUri["STATUS"]));
				die();
			}
		}

		//session expander
		if(COption::GetOptionString("main", "session_expand", "Y") <> "N" && (!defined("BX_SKIP_SESSION_EXPAND") || BX_SKIP_SESSION_EXPAND == false))
		{
			$arPolicy = $USER->GetSecurityPolicy();

			$phpSessTimeout = ini_get("session.gc_maxlifetime");
			if($arPolicy["SESSION_TIMEOUT"] > 0)
				$sessTimeout = min($arPolicy["SESSION_TIMEOUT"]*60, $phpSessTimeout);
			else
				$sessTimeout = $phpSessTimeout;

			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
			$salt = $_COOKIE[$cookie_prefix.'_UIDH']."|".$_SERVER["REMOTE_ADDR"]."|".@filemtime($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php")."|".LICENSE_KEY."|".CMain::GetServerUniqID();
			$key = md5(bitrix_sessid().$salt);

			$bShowMess = ($USER->IsAuthorized() && COption::GetOptionString("main", "session_show_message", "Y") <> "N");

			$ext = array('ajax');
			if($bShowMess)
				$ext[] = "fx";

			CUtil::InitJSCore($ext);

			$jsMsg = '<script type="text/javascript">'."\n".
				($bShowMess? 'bxSession.mess.messSessExpired = \''.CUtil::JSEscape(GetMessage("MAIN_SESS_MESS", array("#TIMEOUT#"=>round($sessTimeout/60)))).'\';'."\n" : '').
				'bxSession.Expand('.$sessTimeout.', \''.bitrix_sessid().'\', '.($bShowMess? 'true':'false').', \''.$key.'\');'."\n".
				'</script>';

			if($APPLICATION->IsJSOptimized())
			{
				$APPLICATION->AddHeadScript('/bitrix/js/main/session.js');
				$APPLICATION->AddAdditionalJS($jsMsg);
			}
			else
			{
				$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL('/bitrix/js/main/session.js').'"></script>'."\n".
					$jsMsg
					, true
				);
			}

			$_SESSION["BX_SESSION_COUNTER"] = intval($_SESSION["BX_SESSION_COUNTER"]) + 1;
			if(!defined("BX_SKIP_SESSION_TERMINATE_TIME"))
				$_SESSION["BX_SESSION_TERMINATE_TIME"] = time()+$sessTimeout;
		}

		//user auto time zone via js cookies
		if(CTimeZone::Enabled())
			CTimeZone::SetAutoCookie();

		// check user options set via cookie
		if ($USER->IsAuthorized())
		{
			$cookieName = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_LAST_SETTINGS";
			if(!empty($_COOKIE[$cookieName]))
			{
				CUserOptions::SetCookieOptions($cookieName);
			}
		}

		if(COption::GetOptionString("main", "buffer_content", "Y")=="Y" && (!defined("BX_BUFFER_USED") || BX_BUFFER_USED!==true))
		{
			ob_start(array(&$APPLICATION, "EndBufferContent"));
			$APPLICATION->buffered = true;
			// define("BX_BUFFER_USED", true);
			register_shutdown_function(create_function('', 'while(@ob_end_flush());'));
		}

		foreach(GetModuleEvents("main", "OnProlog", true) as $arEvent)
			ExecuteModuleEventEx($arEvent);
	}

	public static function EpilogActions()
	{
		global $DB;
		//send email events
		if(COption::GetOptionString("main", "check_events", "Y") !== "N")
		{
			$DB->StartUsingMasterOnly();
			CEvent::CheckEvents();
			$DB->StopUsingMasterOnly();
		}
		//files cleanup
		CMain::FileAction();
	}

	public static function ForkActions($func = false, $args = array())
	{
		static $arFunctions = array();

		if(
			!defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION")
			|| !function_exists(BX_FORK_AGENTS_AND_EVENTS_FUNCTION)
			|| !function_exists("getmypid")
			|| !function_exists("posix_kill")
		)
			return false;

		//Avoid to recurse itself
		if(defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION_STARTED"))
			return false;

		//Register function to execute in forked process
		if($func !== false)
		{
			$arFunctions[] = array($func, $args);
			return true;
		}

		//There is nothing to do
		if(empty($arFunctions))
			return true;

		$func = BX_FORK_AGENTS_AND_EVENTS_FUNCTION;
		$pid = $func();

		//Parent just exits.
		if($pid > 0)
			return false;

		//Fork was successfull let's do seppuku on shutdown
		if($pid == 0)
			register_shutdown_function(create_function('', 'posix_kill(getmypid(), 9);'));

		//Mark start of execution
		// define("BX_FORK_AGENTS_AND_EVENTS_FUNCTION_STARTED", true);

		//Release session
		session_write_close();
		global $DB, $CACHE_MANAGER;
		$CACHE_MANAGER = new CCacheManager;

		$DB->DoConnect();
		$DB->StartUsingMasterOnly();
		foreach($arFunctions as $action)
			call_user_func_array($action[0], $action[1]);
		$DB->Disconnect();
		$CACHE_MANAGER->_Finalize();

		return null;
	}
}

global $MAIN_LANGS_CACHE;
$MAIN_LANGS_CACHE = array();

global $MAIN_LANGS_ADMIN_CACHE;
$MAIN_LANGS_ADMIN_CACHE = array();


class CAllSite
{
	var $LAST_ERROR;

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/indir.php
	 * @author Bitrix
	 */
	public static function InDir($strDir)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		return (substr($APPLICATION->GetCurPage(true), 0, strlen($strDir))==$strDir);
	}

	public static function InPeriod($iUnixTimestampFrom, $iUnixTimestampTo)
	{
		if($iUnixTimestampFrom>0 && time()<$iUnixTimestampFrom)
			return false;
		if($iUnixTimestampTo>0 && time()>$iUnixTimestampTo)
			return false;

		return true;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/ingroup.php
	 * @author Bitrix
	 */
	public static function InGroup($arGroups)
	{
		global $USER;
		$arUserGroups = $USER->GetUserGroupArray();
		if (count(array_intersect($arUserGroups,$arGroups))>0)
			return true;
		return false;
	}

	function GetWeekStart()
	{
		static $weekStart = -1;

		if ($weekStart < 0)
		{
			if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			{
				global $MAIN_LANGS_CACHE;
				if(!is_set($MAIN_LANGS_CACHE, SITE_ID))
				{
					$res = CLang::GetByID(SITE_ID);
					if ($res = $res->Fetch())
					{
						$MAIN_LANGS_CACHE[$res["LID"]] = $res;
					}
				}

				if (is_set($MAIN_LANGS_CACHE, SITE_ID))
				{
					$weekStart = $MAIN_LANGS_CACHE[SITE_ID]['WEEK_START'];
				}
			}
			else
			{
				global $MAIN_LANGS_ADMIN_CACHE;
				if(!is_set($MAIN_LANGS_ADMIN_CACHE, LANGUAGE_ID))
				{
					$res = CLanguage::GetByID(LANGUAGE_ID);
					if($res = $res->Fetch())
					{
						$MAIN_LANGS_ADMIN_CACHE[$res["LID"]] = $res;
					}
				}

				if (is_set($MAIN_LANGS_ADMIN_CACHE, LANGUAGE_ID))
				{
					$weekStart = $MAIN_LANGS_ADMIN_CACHE[LANGUAGE_ID]['WEEK_START'];
				}
			}

			if ($weekStart < 0 || $weekStart == null)
			{
				$weekStart = 1;
			}
		}

		return $weekStart;
	}

	
	/**
	 * <p>Возвращает формат даты (времени) сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $format_type = "FULL" Тип формата. Допустимы следующие значения: <ul> <li> <b>FULL</b> - для
	 * дата-время </li> <li> <b>SHORT</b> - для даты </li> </ul> Необязательный. По
	 * умолчанию "FULL".
	 *
	 *
	 *
	 * @param mixed $site_id = false ID сайта.<br>Необязательный. По умолчанию используется ID текущего
	 * сайта.
	 *
	 *
	 *
	 * @param bool $SearchInSitesOnly = false Необязательный. Если переменная определена в <i>true</i>, то в
	 * административной части будет использован не формат языка
	 * административной части, а сайта по умолчанию.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // выводит текущую дату в формате текущего сайта
	 * echo date($DB-&gt;DateFormatToPHP(<b>CSite::GetDateFormat</b>("SHORT")), time());
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdatabase/index.php#date">Методы класса
	 * CDataBase для работы с датой и временем</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/date/index.php">Функции для работы с датой и
	 * временем</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/getdateformat.php
	 * @author Bitrix
	 */
	public static function GetDateFormat($type="FULL", $lang=false, $bSearchInSitesOnly=false)
	{
		$bFullFormat = (strtoupper($type) == "FULL");

		if($lang === false)
			$lang = LANG;

		if(defined("SITE_ID") && $lang == SITE_ID)
		{
			if($bFullFormat && defined("FORMAT_DATETIME"))
				return FORMAT_DATETIME;
			if(!$bFullFormat && defined("FORMAT_DATE"))
				return FORMAT_DATE;
		}

		if(!$bSearchInSitesOnly && defined("ADMIN_SECTION") && ADMIN_SECTION===true)
		{
			global $MAIN_LANGS_ADMIN_CACHE;
			if(!is_set($MAIN_LANGS_ADMIN_CACHE, $lang))
			{
				$res = CLanguage::GetByID($lang);
				if($res = $res->Fetch())
					$MAIN_LANGS_ADMIN_CACHE[$res["LID"]] = $res;
			}

			if(is_set($MAIN_LANGS_ADMIN_CACHE, $lang))
			{
				if($bFullFormat)
					return strtoupper($MAIN_LANGS_ADMIN_CACHE[$lang]["FORMAT_DATETIME"]);
				return strtoupper($MAIN_LANGS_ADMIN_CACHE[$lang]["FORMAT_DATE"]);
			}
		}

		// if LANG is not found in LangAdmin:
		global $MAIN_LANGS_CACHE;
		if(!is_set($MAIN_LANGS_CACHE, $lang))
		{
			$res = CLang::GetByID($lang);
			$res = $res->Fetch();
			$MAIN_LANGS_CACHE[$res["LID"]] = $res;
			if(defined("ADMIN_SECTION") && ADMIN_SECTION === true)
				$MAIN_LANGS_ADMIN_CACHE[$res["LID"]] = $res;
		}

		if($bFullFormat)
		{
			$format = strtoupper($MAIN_LANGS_CACHE[$lang]["FORMAT_DATETIME"]);
			if($format == '')
				$format = "DD.MM.YYYY HH:MI:SS";
		}
		else
		{
			$format = strtoupper($MAIN_LANGS_CACHE[$lang]["FORMAT_DATE"]);
			if($format == '')
				$format = "DD.MM.YYYY";
		}
		return $format;
	}

	
	/**
	 * <p>Функция предназначена для работы с форматом даты.</p>
	 *
	 *
	 *
	 *
	 * @param lan $g = false Язык сайта.<br>Необязательный. По умолчанию используется язык
	 * текущего сайта.
	 *
	 *
	 *
	 * @param bool $SearchInSitesOnly = false Необязательный. Если переменная определена в <i>true</i>, то в
	 * административной части будет использован не формат языка
	 * административной части, а сайта по умолчанию.
	 *
	 *
	 *
	 * @return string <p>Возвращает формат времени, указанный в настройках сайта.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Вернет формат времени в формате php:echo $GLOBALS["DB"]-&gt;DateFormatToPHP( CSite::GetTimeFormat() );
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/gettimeformat.php
	 * @author Bitrix
	 */
	public static function GetTimeFormat($lang=false, $bSearchInSitesOnly = false)
	{
		$dateTimeFormat = self::GetDateFormat('FULL', $lang, $bSearchInSitesOnly);
		preg_match('~[HG]~', $dateTimeFormat, $chars, PREG_OFFSET_CAPTURE);
		return trim(substr($dateTimeFormat, $chars[0][1]));
	}

	public static function CheckFields($arFields, $ID=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$this->LAST_ERROR = "";
		$arMsg = array();

		if(is_set($arFields, "NAME") && strlen($arFields["NAME"])<2)
		{
			$this->LAST_ERROR .= GetMessage("BAD_SITE_NAME")." ";
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("BAD_SITE_NAME"));
		}
		if($ID===false && is_set($arFields, "LID") && strlen($arFields["LID"])!=2)
		{
			$this->LAST_ERROR .= GetMessage("BAD_SITE_LID")." ";
			$arMsg[] = array("id"=>"LID", "text"=> GetMessage("BAD_SITE_LID"));
		}
		if(is_set($arFields, "DIR") && strlen($arFields["DIR"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_DIR")." ";
			$arMsg[] = array("id"=>"DIR", "text"=> GetMessage("BAD_LANG_DIR"));
		}
		if($ID===false && !is_set($arFields, "LANGUAGE_ID"))
		{
			$this->LAST_ERROR .= GetMessage("MAIN_BAD_LANGUAGE_ID")." ";
			$arMsg[] = array("id"=>"LANGUAGE_ID", "text"=> GetMessage("MAIN_BAD_LANGUAGE_ID"));
		}
		elseif($ID!==false && is_set($arFields, "LANGUAGE_ID"))
		{
			$dbl_check = CLanguage::GetByID($arFields["LANGUAGE_ID"]);
			if(!$dbl_check->Fetch())
			{
				$this->LAST_ERROR .= GetMessage("MAIN_BAD_LANGUAGE_ID_BAD")." ";
				$arMsg[] = array("id"=>"LANGUAGE_ID", "text"=> GetMessage("MAIN_BAD_LANGUAGE_ID_BAD"));
			}
		}
		if(is_set($arFields, "SORT") && strlen($arFields["SORT"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_SORT")." ";
			$arMsg[] = array("id"=>"SORT", "text"=> GetMessage("BAD_SORT"));
		}
		if(is_set($arFields, "FORMAT_DATE") && strlen($arFields["FORMAT_DATE"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_FORMAT_DATE")." ";
			$arMsg[] = array("id"=>"FORMAT_DATE", "text"=> GetMessage("BAD_FORMAT_DATE"));
		}
		if(is_set($arFields, "FORMAT_DATETIME") && strlen($arFields["FORMAT_DATETIME"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_FORMAT_DATETIME")." ";
			$arMsg[] = array("id"=>"FORMAT_DATETIME", "text"=> GetMessage("BAD_FORMAT_DATETIME"));
		}
		if(is_set($arFields, "FORMAT_NAME") && strlen($arFields["FORMAT_NAME"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_FORMAT_NAME")." ";
			$arMsg[] = array("id"=>"FORMAT_NAME", "text"=> GetMessage("BAD_FORMAT_NAME"));
		}
		if(is_set($arFields, "CHARSET") && strlen($arFields["CHARSET"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_CHARSET")." ";
			$arMsg[] = array("id"=>"CHARSET", "text"=> GetMessage("BAD_CHARSET"));
		}
		if(is_set($arFields, "TEMPLATE"))
		{
			$isOK = false;
			$check_templ = array();
			foreach($arFields["TEMPLATE"] as $val)
			{
				if(strlen($val["TEMPLATE"])>0 && file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$val["TEMPLATE"]))
				{
					if(in_array($val["TEMPLATE"].", ".$val["CONDITION"], $check_templ))
						$this->LAST_ERROR = GetMessage("MAIN_BAD_TEMPLATE_DUP");
					$check_templ[] = $val["TEMPLATE"].", ".$val["CONDITION"];
					$isOK = true;
				}
			}
			if(!$isOK)
			{
				$this->LAST_ERROR .= GetMessage("MAIN_BAD_TEMPLATE");
				$arMsg[] = array("id"=>"SITE_TEMPLATE", "text"=> GetMessage("MAIN_BAD_TEMPLATE"));
			}
		}

		if($ID===false)
			$events = GetModuleEvents("main", "OnBeforeSiteAdd", true);
		else
			$events = GetModuleEvents("main", "OnBeforeSiteUpdate", true);
		foreach($events as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array(&$arFields));
			if($bEventRes===false)
			{
				if($err = $APPLICATION->GetException())
				{
					$this->LAST_ERROR .= $err->GetString()." ";
					$arMsg[] = array("id"=>"EVENT_ERROR", "text"=> $err->GetString());
				}
				else
				{
					$this->LAST_ERROR .= "Unknown error. ";
					$arMsg[] = array("id"=>"EVENT_ERROR", "text"=> "Unknown error. ");
				}
				break;
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		if($ID===false)
		{
			$r = $DB->Query("SELECT 'x' FROM b_lang WHERE LID='".$DB->ForSQL($arFields["LID"], 2)."'");
			if($r->Fetch())
			{
				$this->LAST_ERROR .= GetMessage("BAD_SITE_DUP")." ";
				$e = new CAdminException(array(array("id" => "LID", "text" => GetMessage("BAD_SITE_DUP"))));
				$APPLICATION->ThrowException($e);
				return false;
			}
		}

		return true;
	}

	public static function SaveDomains($LID, $domains)
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_lang_domain !== false)
			$CACHE_MANAGER->CleanDir("b_lang_domain");

		$DB->Query("DELETE FROM b_lang_domain WHERE LID='".$DB->ForSQL($LID)."'");

		$domains = str_replace("\r", "\n", $domains);
		$arDomains = explode("\n", $domains);
		for($i=0, $n=count($arDomains); $i<$n; $i++)
			$arDomains[$i] = preg_replace("#^(http://|https://)#", "", trim(strtolower($arDomains[$i])));
		$arDomains = array_unique($arDomains);

		$bIsDomain = false;
		foreach($arDomains as $domain)
		{
			if($domain <> '')
			{
				$arErrors = array();
				if ($domainTmp = CBXPunycode::ToASCII($domain, $arErrors))
					$domain = $domainTmp;
				$DB->Query("INSERT INTO b_lang_domain(LID, DOMAIN) VALUES('".$DB->ForSQL($LID, 2)."', '".$DB->ForSQL($domain, 255)."')");
				$bIsDomain = true;
			}
		}
		$DB->Query("UPDATE b_lang SET DOMAIN_LIMITED='".($bIsDomain? "Y":"N")."' WHERE LID='".$DB->ForSql($LID)."'");
	}

	
	/**
	 * <p>Функция добавляет новый сайт. Возвращает ID вставленного сайта. При возникновении ошибки функция вернет false, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. </p>
	 *
	 *
	 *
	 *
	 * @param array $fields  Массив значений <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">полей</a> вида
	 * array("поле"=&gt;"значение" [, ...]).
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = Array(
	 *   "LID"              =&gt; "ru",
	 *   "ACTIVE"           =&gt; "Y",
	 *   "SORT"             =&gt; 200,
	 *   "DEF"              =&gt; "N",
	 *   "NAME"             =&gt; "www.site.com",
	 *   "DIR"              =&gt; "/ru/",
	 *   "FORMAT_DATE"      =&gt; "DD.MM.YYYY",
	 *   "FORMAT_DATETIME"  =&gt; "DD.MM.YYYY HH:MI:SS",
	 *   "CHARSET"          =&gt; "windows-1251",
	 *   "SITE_NAME"        =&gt; "My site",
	 *   "SERVER_NAME"      =&gt; "www.site.com",
	 *   "EMAIL"            =&gt; "admin@site.com",
	 *   "LANGUAGE_ID"      =&gt; "ru",
	 *   "DOC_ROOT"         =&gt; "",
	 *   "DOMAINS"          =&gt; "www.site.com \n site.com"
	 *   );
	 * $obSite = new CSite;
	 * <b>$obSite-&gt;Add</b>($arFields);
	 * if (strlen($obSite-&gt;LAST_ERROR)&gt;0) $strError .= $obSite-&gt;LAST_ERROR;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/update.php">CSite::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/delete.php">CSite::Delete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB, $DOCUMENT_ROOT, $CACHE_MANAGER;

		if(!$this->CheckFields($arFields))
			return false;
		if(CACHED_b_lang!==false) $CACHE_MANAGER->CleanDir("b_lang");

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DEF"))
		{
			if($arFields["DEF"]=="Y")
				$DB->Query("UPDATE b_lang SET DEF='N' WHERE DEF='Y'");
			else
				$arFields["DEF"]="N";
		}

		$arInsert = $DB->PrepareInsert("b_lang", $arFields);

		$strSql =
			"INSERT INTO b_lang(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";

		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if(is_set($arFields, "DIR"))
			CheckDirPath($DOCUMENT_ROOT.$arFields["DIR"]);

		if(is_set($arFields, "DOMAINS"))
			self::SaveDomains($arFields["LID"], $arFields["DOMAINS"]);

		if(is_set($arFields, "TEMPLATE"))
		{
			global $CACHE_MANAGER;
			if(CACHED_b_site_template!==false) $CACHE_MANAGER->Clean("b_site_template");

			foreach($arFields["TEMPLATE"] as $arTemplate)
			{
				if(strlen(trim($arTemplate["TEMPLATE"]))>0)
				{
					$DB->Query(
						"INSERT INTO b_site_template(SITE_ID, ".CMain::__GetConditionFName().", SORT, TEMPLATE) ".
						"VALUES('".$DB->ForSQL($arFields["LID"])."', '".$DB->ForSQL(trim($arTemplate["CONDITION"]), 255)."', ".IntVal($arTemplate["SORT"]).", '".$DB->ForSQL(trim($arTemplate["TEMPLATE"]), 255)."')");
				}
			}
		}

		return $arFields["LID"];
	}


	
	/**
	 * <p>Функция изменяет параметры сайта. Возвращает "true", если изменение прошло успешно, при возникновении ошибки функция вернет "false", а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. </p>
	 *
	 *
	 *
	 *
	 * @param int $id  ID сайта.
	 *
	 *
	 *
	 * @param array $fields  Массив значений <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">полей</a> вида
	 * array("поле"=&gt;"значение" [, ...]).
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = Array(
	 *   "ACTIVE"           =&gt; "Y",
	 *   "SORT"             =&gt; 200,
	 *   "DEF"              =&gt; "N",
	 *   "NAME"             =&gt; "www.site.com",
	 *   "DIR"              =&gt; "/ru/",
	 *   "FORMAT_DATE"      =&gt; "DD.MM.YYYY",
	 *   "FORMAT_DATETIME"  =&gt; "DD.MM.YYYY HH:MI:SS",
	 *   "CHARSET"          =&gt; "windows-1251",
	 *   "SITE_NAME"        =&gt; "My site",
	 *   "SERVER_NAME"      =&gt; "www.site.com",
	 *   "EMAIL"            =&gt; "admin@site.com",
	 *   "LANGUAGE_ID"      =&gt; "ru",
	 *   "DOC_ROOT"         =&gt; "",
	 *   "DOMAINS"          =&gt; "www.site.com \n site.com"
	 *   );
	 * $obSite = new CSite;
	 * <b>$obSite-&gt;Update</b>("ru", $arFields);
	 * if (strlen($obSite-&gt;LAST_ERROR)&gt;0) $strError .= $obSite-&gt;LAST_ERROR;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/add.php">CSite::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/delete.php">CSite::Delete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB, $MAIN_LANGS_CACHE, $MAIN_LANGS_ADMIN_CACHE, $CACHE_MANAGER;
		UnSet($MAIN_LANGS_CACHE[$ID]);
		UnSet($MAIN_LANGS_ADMIN_CACHE[$ID]);

		if(!$this->CheckFields($arFields, $ID))
			return false;
		if(CACHED_b_lang!==false) $CACHE_MANAGER->CleanDir("b_lang");

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DEF"))
		{
			if($arFields["DEF"]=="Y")
				$DB->Query("UPDATE b_lang SET DEF='N' WHERE DEF='Y'");
			else
				$arFields["DEF"]="N";
		}

		$strUpdate = $DB->PrepareUpdate("b_lang", $arFields);
		$strSql = "UPDATE b_lang SET ".$strUpdate." WHERE LID='".$DB->ForSql($ID, 2)."'";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		global $BX_CACHE_DOCROOT;
		unset($BX_CACHE_DOCROOT[$ID]);

		if(is_set($arFields, "DIR"))
			CheckDirPath($_SERVER["DOCUMENT_ROOT"].$arFields["DIR"]);

		if(is_set($arFields, "DOMAINS"))
			self::SaveDomains($ID, $arFields["DOMAINS"]);

		if(is_set($arFields, "TEMPLATE"))
		{
			if(CACHED_b_site_template!==false) $CACHE_MANAGER->Clean("b_site_template");
			$DB->Query("DELETE FROM b_site_template WHERE SITE_ID='".$DB->ForSQL($ID)."'");
			foreach($arFields["TEMPLATE"] as $arTemplate)
			{
				if(strlen(trim($arTemplate["TEMPLATE"]))>0)
				{
					$DB->Query(
						"INSERT INTO b_site_template(SITE_ID, ".CMain::__GetConditionFName().", SORT, TEMPLATE) ".
						"VALUES('".$DB->ForSQL($ID)."', '".$DB->ForSQL(trim($arTemplate["CONDITION"]), 255)."', ".IntVal($arTemplate["SORT"]).", '".$DB->ForSQL(trim($arTemplate["TEMPLATE"]), 255)."')");
				}
			}
		}

		return true;
	}

	
	/**
	 * <p>Функция удаляет сайт. Если удаление успешно, то возвращает объект <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>, иначе "false".</p>
	 *
	 *
	 *
	 *
	 * @param string $id  ID удаляемого сайта.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (<b>CSite::Delete</b>("ru")===false) 
	 *   echo "Сайт удалить нельзя т.к. найдены связанные записи.";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/add.php">CSite::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/update.php">CSite::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/events/onbeforesitedelete.php">Событие "OnBeforeSiteDelete"</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/events/onsitedelete.php">Событие "OnSiteDelete"</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION, $CACHE_MANAGER;

		$APPLICATION->ResetException();

		foreach(GetModuleEvents("main", "OnBeforeLangDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		foreach(GetModuleEvents("main", "OnBeforeSiteDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		foreach(GetModuleEvents("main", "OnLangDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		foreach(GetModuleEvents("main", "OnSiteDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		if(!$DB->Query("DELETE FROM b_event_message_site WHERE SITE_ID='".$DB->ForSQL($ID, 2)."'"))
			return false;

		if(!$DB->Query("DELETE FROM b_lang_domain WHERE LID='".$DB->ForSQL($ID, 2)."'"))
			return false;
		if(CACHED_b_lang_domain!==false) $CACHE_MANAGER->CleanDir("b_lang_domain");

		if(!$DB->Query("UPDATE b_event_message SET LID=NULL WHERE LID='".$DB->ForSQL($ID, 2)."'"))
			return false;

		if(!$DB->Query("DELETE FROM b_site_template WHERE SITE_ID='".$DB->ForSQL($ID, 2)."'"))
			return false;
		if(CACHED_b_site_template!==false) $CACHE_MANAGER->Clean("b_site_template");

		if(CACHED_b_lang!==false) $CACHE_MANAGER->CleanDir("b_lang");
		return $DB->Query("DELETE FROM b_lang WHERE LID='".$DB->ForSQL($ID, 2)."'", true);
	}

	public static function GetTemplateList($site_id)
	{
		global $DB;
		$strSql =
				"SELECT * ".
				"FROM b_site_template ".
				"WHERE SITE_ID='".$DB->ForSQL($site_id, 2)."' ".
				"ORDER BY SORT";

		$dbr = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $dbr;
	}

	public static function GetDefList()
	{
		global $DB;
		$strSql = "SELECT L.*, L.LID as ID, L.LID as SITE_ID FROM b_lang L WHERE ACTIVE='Y' ORDER BY DEF desc, SORT";
		$sl = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $sl;
	}

	public static function GetSiteDocRoot($site)
	{
		if($site === false)
			$site = SITE_ID;

		global $BX_CACHE_DOCROOT;
		if(!array_key_exists($site, $BX_CACHE_DOCROOT))
		{
			$ar = CSite::getArrayByID($site);
			if($ar && strlen($ar["DOC_ROOT"])>0)
				$BX_CACHE_DOCROOT[$site] = Rel2Abs($_SERVER["DOCUMENT_ROOT"], $ar["DOC_ROOT"]);
			else
				$BX_CACHE_DOCROOT[$site] = RTrim($_SERVER["DOCUMENT_ROOT"], "/\\");
		}

		return $BX_CACHE_DOCROOT[$site];
	}

	public static function GetSiteByFullPath($path, $bOneResult = true)
	{
		$res = array();

		if(($p = realpath($path)))
			$path = $p;
		$path = str_replace("\\", "/", $path);
		$path = strtoupper($path);

		$db_res = CSite::GetList($by="lendir", $order="desc");
		while($ar_res = $db_res->Fetch())
		{
			$abspath = $ar_res["ABS_DOC_ROOT"].$ar_res["DIR"];
			if(($p = realpath($abspath)))
				$abspath = $p;
			$abspath = str_replace("\\", "/", $abspath);
			$abspath = strtoupper($abspath);
			if(substr($abspath, -1) <> "/")
				$abspath .= "/";
			if(strpos($path, $abspath)===0)
			{
				if($bOneResult)
					return $ar_res["ID"];
				$res[] = $ar_res["ID"];
			}
		}

		if(count($res)>0)
			return $res;

		return false;
	}

	
	/**
	 * <p>Возвращает список сайтов в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string &$by = "sort" По какому полю сортируем. Допустимы следующие значения: <ul> <li>
	 * <b>id</b> - по ID сайта </li> <li> <b>active</b> - по флагу активности </li> <li> <b>name</b> -
	 * по краткому названию сайта </li> <li> <b>dir</b> - по каталогу от которого
	 * начинается содержимое сайта </li> <li> <b>lendir</b> - по длине имени
	 * каталога от которого начинается содержимое сайта </li> <li> <b>def</b> - по
	 * флагу "Сайт по умолчанию" </li> <li> <b>sort</b> - по индексу сортировки </li>
	 * </ul>Параметр необязательный. По умолчанию - "sort".
	 *
	 *
	 *
	 * @param string &$order = "asc" Порядок сортировки. Допустимы следующие значения: <ul> <li> <b>asc</b> - по
	 * возрастанию </li> <li> <b>desc</b> - по убыванию </li> </ul> Параметр
	 * необязательный. По умолчанию - "asc".
	 *
	 *
	 *
	 * @param array $filter = array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]). "Фильтруемое
	 * поле" может принимать следующие значения: <ul> <li> <b>ID</b> - ID сайта </li>
	 * <li> <b>NAME</b> - поле "Название" из настроек сайта </li> <li> <b>DOMAIN</b> - поле
	 * "Доменное имя" из настроек сайта </li> <li> <b>IN_DIR</b> - префикс для поля
	 * "Папка сайта" из настроек сайта </li> <li> <b>LANGUAGE_ID</b> - двухсимвольный ID
	 * языка сайта </li> <li> <b>ACTIVE</b> - флаг активности (Y|N) </li> </ul>
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $rsSites = <b>CSite::GetList</b>($by="sort", $order="desc", Array("NAME" =&gt; "www.mysite.ru"));
	 * while ($arSite = $rsSites-&gt;Fetch())
	 * {
	 *   echo "&lt;pre&gt;"; print_r($arSite); echo "&lt;/pre&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/getbyid.php">CSite::GetByID</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/getlist.php
	 * @author Bitrix
	 */
	public static function GetList(&$by, &$order, $arFilter=array())
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_lang!==false)
		{
			$cacheId = "b_lang".md5($by.".".$order.".".serialize($arFilter));
			if($CACHE_MANAGER->Read(CACHED_b_lang, $cacheId, "b_lang"))
			{
				$arResult = $CACHE_MANAGER->Get($cacheId);

				$res = new CDBResult;
				$res->InitFromArray($arResult);
				$res = new _CLangDBResult($res);
				return $res;
			}
		}

		$strSqlSearch = " 1=1\n";
		$bIncDomain = false;
		if(is_array($arFilter))
		{
			foreach($arFilter as $key=>$val)
			{
				if(strlen($val)<=0) continue;
				$val = $DB->ForSql($val);
				switch(strtoupper($key))
				{
					case "ACTIVE":
						if($val=="Y" || $val=="N")
							$strSqlSearch .= " AND L.ACTIVE='".$val."'\n";
						break;
					case "DEFAULT":
						if($val=="Y" || $val=="N")
							$strSqlSearch .= " AND L.DEF='".$val."'\n";
						break;
					case "NAME":
						$strSqlSearch .= " AND UPPER(L.NAME) LIKE UPPER('".$val."')\n";
						break;
					case "DOMAIN":
						$bIncDomain = true;
						$strSqlSearch .= " AND UPPER(D.DOMAIN) LIKE UPPER('".$val."')\n";
						break;
					case "IN_DIR":
						$strSqlSearch .= " AND UPPER('".$val."') LIKE ".$DB->Concat("UPPER(L.DIR)", "'%'")."\n";
						break;
					case "ID":
					case "LID":
						$strSqlSearch .= " AND L.LID='".$val."'\n";
						break;
					case "LANGUAGE_ID":
						$strSqlSearch .= " AND L.LANGUAGE_ID='".$val."'\n";
						break;
				}
			}
		}

		$strSql = "
			SELECT ".($bIncDomain ? " DISTINCT " : "")."
				L.*,
				L.LID ID,
				".$DB->Length("L.DIR").",
				".$DB->IsNull($DB->Length("L.DOC_ROOT"), "0")."
			FROM
				b_lang L
				".($bIncDomain ? " LEFT JOIN b_lang_domain D ON D.LID=L.LID " : "")."
			WHERE
				".$strSqlSearch."
			";

		$by = strtolower($by);
		$order = strtolower($order);

		if($by == "lid" || $by=="id")	$strSqlOrder = " ORDER BY L.LID ";
		elseif($by == "active")			$strSqlOrder = " ORDER BY L.ACTIVE ";
		elseif($by == "name")			$strSqlOrder = " ORDER BY L.NAME ";
		elseif($by == "dir")			$strSqlOrder = " ORDER BY L.DIR ";
		elseif($by == "lendir")			$strSqlOrder = " ORDER BY ".$DB->IsNull($DB->Length("L.DOC_ROOT"), "0").($order=="desc"? " desc":"").", ".$DB->Length("L.DIR");
		elseif($by == "def")			$strSqlOrder = " ORDER BY L.DEF ";
		else
		{
			$strSqlOrder = " ORDER BY L.SORT ";
			$by = "sort";
		}

		if($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		if(CACHED_b_lang===false)
		{
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else
		{
			$arResult = array();
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			while($ar = $res->Fetch())
				$arResult[]=$ar;

			/** @noinspection PhpUndefinedVariableInspection */
			$CACHE_MANAGER->Set($cacheId, $arResult);

			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}
		$res = new _CLangDBResult($res);
		return $res;
	}

	
	/**
	 * <p>Возвращает информацию по сайту в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $id  ID сайта.
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $rsSites = <b>CSite::GetByID</b>("ru");
	 * $arSite = $rsSites-&gt;Fetch();
	 * echo "&lt;pre&gt;"; print_r($arSite); echo "&lt;/pre&gt;";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/index.php#flds">Поля CSite</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/csite/getlist.php">CSite::GetList</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/csite/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		return CSite::GetList($ord, $by, array("LID"=>$ID));
	}

	public static function GetArrayByID($ID)
	{
		global $DB, $CACHE_MANAGER;

		if (CACHED_b_lang !== false)
		{
			$cacheId = "b_lang|".$ID;
			if($CACHE_MANAGER->read(CACHED_b_lang, $cacheId, "b_lang"))
			{
				return $CACHE_MANAGER->get($cacheId);
			}
		}

		$strSql = "
			SELECT
				L.*,
				L.LID ID
			FROM
				b_lang L
			WHERE
				L.LID='".$DB->forSql($ID)."'\n
			";

		$res = $DB->query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arResult = $res->fetch();

		if (CACHED_b_lang !== false)
		{
			$CACHE_MANAGER->set($cacheId, $arResult);
		}

		return $arResult;
	}

	public static function GetDefSite($LID = false)
	{
		if(strlen($LID)>0)
		{
			$dbSite = CSite::GetByID($LID);
			if($dbSite->Fetch())
				return $LID;
		}

		$dbDefSites = CSite::GetDefList();
		if($arDefSite = $dbDefSites->Fetch())
			return $arDefSite["LID"];

		return false;
	}

	public static function IsDistinctDocRoots($arFilter=array())
	{
		$s = false;
		$res = CSite::GetList($by, $order, $arFilter);
		while($ar = $res->Fetch())
		{
			if($s!==false && $s!=$ar["ABS_DOC_ROOT"])
				return true;
			$s = $ar["ABS_DOC_ROOT"];
		}
		return false;
	}


	///////////////////////////////////////////////////////////////////
	// Returns drop down list with langs
	///////////////////////////////////////////////////////////////////
	public static function SelectBox($sFieldName, $sValue, $sDefaultValue="", $sFuncName="", $field="class=\"typeselect\"")
	{
		$by = "sort";
		$order = "asc";
		$l = CLang::GetList($by, $order);
		$s = '<select name="'.$sFieldName.'" '.$field;
		$s1 = '';
		if(strlen($sFuncName)>0) $s .= ' OnChange="'.$sFuncName.'"';
		$s .= '>'."\n";
		$found = false;
		while(($l_arr = $l->Fetch()))
		{
			$found = ($l_arr["LID"] == $sValue);
			$s1 .= '<option value="'.$l_arr["LID"].'"'.($found ? ' selected':'').'>['.htmlspecialcharsex($l_arr["LID"]).']&nbsp;'.htmlspecialcharsex($l_arr["NAME"]).'</option>'."\n";
		}
		if(strlen($sDefaultValue)>0)
			$s .= "<option value='NOT_REF' ".($found ? "" : "selected").">".htmlspecialcharsex($sDefaultValue)."</option>";
		return $s.$s1.'</select>';
	}

	public static function SelectBoxMulti($sFieldName, $Value)
	{
		$by = "sort";
		$order = "asc";
		$l = CLang::GetList($by, $order);
		if(is_array($Value))
			$arValue = $Value;
		else
			$arValue = array($Value);

		$s = '<div class="adm-list">';
		while($l_arr = $l->Fetch())
		{
			$s .=
				'<div class="adm-list-item">'.
				'<div class="adm-list-control"><input type="checkbox" name="'.$sFieldName.'[]" value="'.htmlspecialcharsex($l_arr["LID"]).'" id="'.htmlspecialcharsex($l_arr["LID"]).'" class="typecheckbox"'.(in_array($l_arr["LID"], $arValue)?' checked':'').'></div>'.
				'<div class="adm-list-label"><label for="'.htmlspecialcharsex($l_arr["LID"]).'">['.htmlspecialcharsex($l_arr["LID"]).']&nbsp;'.htmlspecialcharsex($l_arr["NAME"]).'</label></div>'.
				'</div>';
		}

		$s .= '</div>';

		return $s;
	}

	public static function GetNameTemplates()
	{
		return array(
			'#NAME# #LAST_NAME#' => GetMessage('MAIN_NAME_JOHN_SMITH'),
			'#LAST_NAME# #NAME#' => GetMessage('MAIN_NAME_SMITH_JOHN'),
			'#NAME# #SECOND_NAME_SHORT# #LAST_NAME#' => GetMessage('MAIN_NAME_JOHN_L_SMITH'),
			'#LAST_NAME# #NAME# #SECOND_NAME#' => GetMessage('MAIN_NAME_SMITH_JOHN_LLOYD'),
			'#LAST_NAME#, #NAME# #SECOND_NAME#' => GetMessage('MAIN_NAME_SMITH_COMMA_JOHN_LLOYD'),
			'#NAME# #SECOND_NAME# #LAST_NAME#' => GetMessage('MAIN_NAME_JOHN_LLOYD_SMITH'),
			'#NAME_SHORT# #SECOND_NAME_SHORT# #LAST_NAME#' => GetMessage('MAIN_NAME_J_L_SMITH'),
			'#NAME_SHORT# #LAST_NAME#' => GetMessage('MAIN_NAME_J_SMITH'),
			'#LAST_NAME# #NAME_SHORT#' => GetMessage('MAIN_NAME_SMITH_J'),
			'#LAST_NAME# #NAME_SHORT# #SECOND_NAME_SHORT#' => GetMessage('MAIN_NAME_SMITH_J_L'),
			'#LAST_NAME#, #NAME_SHORT#' => GetMessage('MAIN_NAME_SMITH_COMMA_J'),
			'#LAST_NAME#, #NAME_SHORT# #SECOND_NAME_SHORT#' => GetMessage('MAIN_NAME_SMITH_COMMA_J_L')
		);
	}

	public static function GetNameFormatByValue($sValue)
	{
		$arNameTemplates = self::GetNameTemplates();

		foreach ($arNameTemplates as $sFormat => $sName)
		{
			if ($sValue == $sName)
				return $sFormat;
		}

		if ($sValue == GetMessage("MAIN_FORMAT_NAME_NOT_SET"))
			return "";
		else
			return self::GetDefaultNameFormat();
	}

	/**
	* Returns current name template
	*
	* If site is not defined - will look for name template for current language.
	* If there is no value for language - returns pre-defined value @see CSite::GetDefaultNameFormat
	* FORMAT_NAME constant can be set in dbconn.php
	*
	* @param $dummy Unused
	* @param string $site_id - use to get value for the specific site
	* @return string ex: #LAST_NAME# #NAME#
	*/
	public static function GetNameFormat($dummy = null, $site_id = "")
	{
		if ($site_id == "")
			$site_id = SITE_ID;

		$format = "";

		//for current site
		if(defined("SITE_ID") && $site_id == SITE_ID)
		{
			if(defined("FORMAT_NAME"))
				$format = FORMAT_NAME;
		}

		//site value
		if ($format == "")
		{
			$db_res = CSite::GetByID($site_id);

			if ($res = $db_res->Fetch())
				$format = $res["FORMAT_NAME"];
		}

		//if not found - trying to get value for the language
		if ($format == "")
		{
			global $MAIN_LANGS_ADMIN_CACHE;
			if(!is_set($MAIN_LANGS_ADMIN_CACHE, $site_id))
			{
				$db_res = CLanguage::GetByID(LANGUAGE_ID);
				if ($res = $db_res->Fetch())
					$MAIN_LANGS_ADMIN_CACHE[$res["LID"]] = $res;
			}

			if(is_set($MAIN_LANGS_ADMIN_CACHE, LANGUAGE_ID))
				$format = strtoupper($MAIN_LANGS_ADMIN_CACHE[LANGUAGE_ID]["FORMAT_NAME"]);
		}

		//if not found - trying to get default values
		if ($format == "")
			$format = self::GetDefaultNameFormat(empty($res["LANGUAGE_ID"]) ? "" : $res["LANGUAGE_ID"]);

		$format = str_replace(array("#NOBR#","#/NOBR#"), "", $format);

		return $format;
	}

	/**
	* Returns default name template
	* By default: Russian #LAST_NAME# #NAME#, English #NAME# #LAST_NAME#
	*
	* @param string $sLangId - language id, if we need to get value for specific language
	* @return string - one of two possible default values
	*/
	public static function GetDefaultNameFormat($sLangId = "")
	{
		return '#NAME# #LAST_NAME#';
	}

	public static function SelectBoxName($sFieldName, $sValue, $sDefaultValue="", $sFuncName="", $field="class=\"typeselect\"")
	{
		$arNameTemplates = self::GetNameTemplates();

		if (empty($sValue))
			$arNameTemplates["0"] = GetMessage("MAIN_FORMAT_NAME_NOT_SET");

		$s = '<select name="'.$sFieldName.'" '.$field;
		$s1 = '';
		if(strlen($sFuncName)>0) $s .= ' OnChange="'.$sFuncName.'"';
		$s .= '>'."\n";
		$found = false;

		if (defined('FORMAT_NAME'))
		{
			$s1 .= '<option value="constant" selected>'.CUser::FormatName(FORMAT_NAME,
				array("NAME"		=>	GetMessage("MAIN_NAME_JOHN"),
					"LAST_NAME"		=>	GetMessage("MAIN_NAME_SMITH"),
					"SECOND_NAME"	=>	GetMessage("MAIN_NAME_LLOYD")), false).' '.GetMessage("MAIN_NAME_DEFINED_IN_DBCONN").'</option>';
		}
		else
		{
			foreach ($arNameTemplates as $template => $value) {
				$found = ($template == $sValue);
				$s1 .= '<option value="'.$value.'"'.($found ? ' selected':'').'>'.htmlspecialcharsex($value).'</option>'."\n";
			}

			if(strlen($sDefaultValue)>0)
				$s .= "<option value='NOT_REF' ".($found ? "" : "selected").">".htmlspecialcharsex($sDefaultValue)."</option>";
		}

		return $s.$s1.'</select>';
	}
}

class _CLangDBResult extends CDBResult
{

	function _CLangDBResult($res)
	{
		parent::CDBResult($res);
	}

	public static function Fetch()
	{
		if($res = parent::Fetch())
		{
			global $DB, $CACHE_MANAGER;
			static $arCache;
			if(!is_array($arCache))
				$arCache = array();
			if(is_set($arCache, $res["LID"]))
				$res["DOMAINS"] = $arCache[$res["LID"]];
			else
			{
				if(CACHED_b_lang_domain===false)
				{
					$res["DOMAINS"] = "";
					$db_res = $DB->Query("SELECT * FROM b_lang_domain WHERE LID='".$res["LID"]."'");
					while($ar_res = $db_res->Fetch())
					{
						$domain = $ar_res["DOMAIN"];
						$arErrorsTmp = array();
						if ($domainTmp = CBXPunycode::ToUnicode($ar_res["DOMAIN"], $arErrorsTmp))
							$domain = $domainTmp;
						$res["DOMAINS"] .= $domain."\r\n";
					}
				}
				else
				{
					if($CACHE_MANAGER->Read(CACHED_b_lang_domain, "b_lang_domain", "b_lang_domain"))
					{
						$arLangDomain = $CACHE_MANAGER->Get("b_lang_domain");
					}
					else
					{
						$arLangDomain = array("DOMAIN"=>array(), "LID"=>array());
						$rs = $DB->Query("SELECT * FROM b_lang_domain ORDER BY ".$DB->Length("DOMAIN"));
						while($ar = $rs->Fetch())
						{
							$arLangDomain["DOMAIN"][]=$ar;
							$arLangDomain["LID"][$ar["LID"]][]=$ar;
						}
						$CACHE_MANAGER->Set("b_lang_domain", $arLangDomain);
					}
					$res["DOMAINS"] = "";
					if(is_array($arLangDomain["LID"][$res["LID"]]))
						foreach($arLangDomain["LID"][$res["LID"]] as $ar_res)
						{
							$domain = $ar_res["DOMAIN"];
							$arErrorsTmp = array();
							if ($domainTmp = CBXPunycode::ToUnicode($ar_res["DOMAIN"], $arErrorsTmp))
								$domain = $domainTmp;
							$res["DOMAINS"] .= $domain."\r\n";

						}
				}
				$res["DOMAINS"] = Trim($res["DOMAINS"]);
				$arCache[$res["LID"]] = $res["DOMAINS"];
			}

			if(trim($res["DOC_ROOT"])=="")
				$res["ABS_DOC_ROOT"] = $_SERVER["DOCUMENT_ROOT"];
			else
				$res["ABS_DOC_ROOT"] = Rel2Abs($_SERVER["DOCUMENT_ROOT"], $res["DOC_ROOT"]);

			if($res["ABS_DOC_ROOT"]!==$_SERVER["DOCUMENT_ROOT"])
				$res["SITE_URL"] = (CMain::IsHTTPS() ? "https://" : "http://").$res["SERVER_NAME"];
		}
		return $res;
	}

}

class CAllLanguage
{
	var $LAST_ERROR;

	
	/**
	 * <p>Возвращает список языков в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string &$by = "lid" По какому полю сортируем. Допустимы следующие значения: <ul> <li>
	 * <b>lid</b> - по ID языка </li> <li> <b>active</b> - по флагу активности </li> <li> <b>name</b> -
	 * по названию </li> <li> <b>def</b> - по флагу "Язык по умолчанию" </li> </ul>
	 *
	 *
	 *
	 * @param string &$order = "asc" Порядок сортировки. Допустимы следующие значения: <ul> <li> <b>asc</b> - по
	 * возрастанию </li> <li> <b>desc</b> - по убыванию </li> </ul>
	 *
	 *
	 *
	 * @param array $filter = array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]). "Фильтруемое
	 * поле" может принимать следующие значения: <ul> <li> <b>LID</b> -
	 * двухсимвольный ID языка </li> <li> <b>NAME</b> - название языка </li> <li> <b>ACTIVE</b>
	 * - флаг активности (Y|N) </li> </ul>
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $rsLang = <b>CLanguage::GetList</b>($by="lid", $order="desc", Array("NAME" =&gt; "russian"));
	 * while ($arLang = $rsLang-&gt;Fetch())
	 * {
	 *   echo "&lt;pre&gt;"; print_r($arLang); echo "&lt;/pre&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">Поля CLanguage</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/getbyid.php">CLanguage::GetByID</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/getlist.php
	 * @author Bitrix
	 */
	public static function GetList(&$by, &$order, $arFilter=array())
	{
		global $DB;
		$arSqlSearch = array();

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val) > 0)
				{
					switch (strtoupper($key))
					{
					case "ACTIVE":
						if ($val == "Y" || $val == "N")
							$arSqlSearch[] = "L.ACTIVE='".$DB->ForSql($val)."'";
						break;

					case "NAME":
						$arSqlSearch[] = "UPPER(L.NAME) LIKE UPPER('".$DB->ForSql($val)."')";
						break;

					case "ID":
					case "LID":
						$arSqlSearch[] = "L.LID='".$DB->ForSql($val)."'";
						break;
					}
				}
			}
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $i => $condition)
		{
			if($i > 0)
				$strSqlSearch .= " AND ";
			else
				$strSqlSearch = " WHERE ";

			$strSqlSearch .= " (".$condition.") ";
		}

		$strSql =
			"SELECT L.*, L.LID as ID, L.LID as LANGUAGE_ID ".
			"FROM b_language L ".
				$strSqlSearch;

		if($by == "lid" || $by=="id") $strSqlOrder = " ORDER BY L.LID ";
		elseif($by == "active") $strSqlOrder = " ORDER BY L.ACTIVE ";
		elseif($by == "name") $strSqlOrder = " ORDER BY L.NAME ";
		elseif($by == "def") $strSqlOrder = " ORDER BY L.DEF ";
		else
		{
			$strSqlOrder = " ORDER BY L.SORT ";
			$by = "sort";
		}

		if($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $res;
	}

	
	/**
	 * <p>Возвращает язык по его коду <i>id</i> в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $id  Двухсимвольный ID языка.
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $rsLang = <b>CLanguage::GetByID</b>("en");
	 * $arLang = $rsLang-&gt;Fetch();
	 * echo "&lt;pre&gt;"; print_r($arLang); echo "&lt;/pre&gt;";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">Поля CLanguage</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/getlist.php">CLanguage::GetList</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/selectbox.php">CLanguage::SelectBox</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		return CLanguage::GetList($o, $b, array("LID"=>$ID));
	}

	public static function CheckFields($arFields, $ID=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$this->LAST_ERROR = "";
		$arMsg = array();

		if($ID===false && is_set($arFields, "LID") && strlen($arFields["LID"])!=2)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_LID")." ";
			$arMsg[] = array("id"=>"LID", "text"=> GetMessage("BAD_LANG_LID"));
		}
		if(is_set($arFields, "NAME") && strlen($arFields["NAME"])<2)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_NAME")." ";
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("BAD_LANG_NAME"));
		}
		if(is_set($arFields, "SORT") && intval($arFields["SORT"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_SORT")." ";
			$arMsg[] = array("id"=>"SORT", "text"=> GetMessage("BAD_LANG_SORT"));
		}
		if(is_set($arFields, "FORMAT_DATE") && strlen($arFields["FORMAT_DATE"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_FORMAT_DATE")." ";
			$arMsg[] = array("id"=>"FORMAT_DATE", "text"=> GetMessage("BAD_LANG_FORMAT_DATE"));
		}
		if(is_set($arFields, "FORMAT_DATETIME") && strlen($arFields["FORMAT_DATETIME"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_FORMAT_DATETIME")." ";
			$arMsg[] = array("id"=>"FORMAT_DATETIME", "text"=> GetMessage("BAD_LANG_FORMAT_DATETIME"));
		}
		if(is_set($arFields, "FORMAT_NAME") && strlen($arFields["FORMAT_NAME"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_FORMAT_NAME")." ";
			$arMsg[] = array("id"=>"FORMAT_NAME", "text"=> GetMessage("BAD_LANG_FORMAT_NAME"));
		}
		if(is_set($arFields, "CHARSET") && strlen($arFields["CHARSET"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("BAD_LANG_CHARSET")." ";
			$arMsg[] = array("id"=>"CHARSET", "text"=> GetMessage("BAD_LANG_CHARSET"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		if($ID===false)
		{
			$r = $DB->Query("SELECT 'x' FROM b_language WHERE LID='".$DB->ForSQL($arFields["LID"], 2)."'");
			if($r->Fetch())
			{
				$this->LAST_ERROR .= GetMessage("BAD_LANG_DUP")." ";
				$e = new CAdminException(array(array("id"=>"LID", "text" =>GetMessage("BAD_LANG_DUP"))));
				$APPLICATION->ThrowException($e);
				return false;
			}
		}

		return true;
	}

	
	/**
	 * <p>Функция добавляет новый язык. Возвращает ID вставленного языка. При возникновении ошибки функция вернет false, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки.</p>
	 *
	 *
	 *
	 *
	 * @param array $fields  Массив значений <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">полей</a> вида
	 * array("поле"=&gt;"значение" [, ...]).
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = Array(
	 *   "LID"              =&gt; "en",
	 *   "ACTIVE"           =&gt; "Y",
	 *   "SORT"             =&gt; 100,
	 *   "DEF"              =&gt; "N",
	 *   "NAME"             =&gt; "English",
	 *   "FORMAT_DATE"      =&gt; "MM/DD/YYYY",
	 *   "FORMAT_DATETIME"  =&gt; "MM/DD/YYYY HH:MI:SS",
	 *   "CHARSET"          =&gt; "iso-8859-1"
	 *   );
	 * $obLang = new CLanguage;
	 * <b>$obLang-&gt;Add</b>($arFields);
	 * if (strlen($obLang-&gt;LAST_ERROR)&gt;0) $strError .= $obLang-&gt;LAST_ERROR;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">Поля CLanguage</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/update.php">CLanguage::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/delete.php">CLanguage::Delete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields))
			return false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DIRECTION") && $arFields["DIRECTION"]!="Y")
			$arFields["DIRECTION"]="N";

		$arInsert = $DB->PrepareInsert("b_language", $arFields);

		if(is_set($arFields, "DEF"))
		{
			if($arFields["DEF"]=="Y")
				$DB->Query("UPDATE b_language SET DEF='N' WHERE DEF='Y'");
			else
				$arFields["DEF"]="N";
		}

		$strSql =
			"INSERT INTO b_language(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $arFields["LID"];
	}


	
	/**
	 * <p>Функция изменяет настройки языка. Возвращает "true" если изменение прошло успешно, при возникновении ошибки функция вернет "false", а в свойстве LAST_ERROR объекта будет содержаться текст ошибки.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Двухсимвольный ID языка.
	 *
	 *
	 *
	 * @param array $fields  Массив значений <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">полей</a> вида
	 * array("поле"=&gt;"значение" [, ...]).
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = Array(
	 *   "ACTIVE"           =&gt; "Y",
	 *   "SORT"             =&gt; 100,
	 *   "DEF"              =&gt; "N",
	 *   "NAME"             =&gt; "English",
	 *   "FORMAT_DATE"      =&gt; "MM/DD/YYYY",
	 *   "FORMAT_DATETIME"  =&gt; "MM/DD/YYYY HH:MI:SS",
	 *   "CHARSET"          =&gt; "iso-8859-1"
	 *   );
	 * $obLang = new CLanguage;
	 * <b>$obLang-&gt;Update</b>("en", $arFields);
	 * if (strlen($obLang-&gt;LAST_ERROR)&gt;0) $strError .= $obLang-&gt;LAST_ERROR;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/index.php#flds">Поля CLanguage</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/add.php">CLanguage::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/delete.php">CLanguage::Delete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB, $MAIN_LANGS_CACHE, $MAIN_LANGS_ADMIN_CACHE;
		UnSet($MAIN_LANGS_CACHE[$ID]);
		UnSet($MAIN_LANGS_ADMIN_CACHE[$ID]);

		if(!$this->CheckFields($arFields, $ID))
			return false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DIRECTION") && $arFields["DIRECTION"]!="Y")
			$arFields["DIRECTION"]="N";

		if(is_set($arFields, "DEF"))
		{
			if($arFields["DEF"]=="Y")
				$DB->Query("UPDATE b_language SET DEF='N' WHERE DEF='Y'");
			else
				$arFields["DEF"]="N";
		}

		$strUpdate = $DB->PrepareUpdate("b_language", $arFields);
		$strSql = "UPDATE b_language SET ".$strUpdate." WHERE LID='".$DB->ForSql($ID, 2)."'";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return true;
	}

	
	/**
	 * <p>Функция удаляет язык. Возвращается объект <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $id  Двухсимвольный ID языка.
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (<b>CLanguage::Delete</b>("en")) 
	 *   echo "Язык успешно удален.";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/add.php">CLanguage::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/clanguage/update.php">CLanguage::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/events/onbeforelanguagedelete.php">Событие
	 * "OnBeforeLanguageDelete"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/events/onlanguagedelete.php">Событие "OnLanguageDelete"</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$b = "";
		$o = "";
		$db_res = CLang::GetList($b, $o, array("LANGUAGE_ID" => $ID));
		if($db_res->Fetch())
			return false;

		foreach(GetModuleEvents("main", "OnBeforeLanguageDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		foreach(GetModuleEvents("main", "OnLanguageDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return $DB->Query("DELETE FROM b_language WHERE LID='".$DB->ForSQL($ID, 2)."'", true);
	}

	
	/**
	 * <p>Возвращает HTML, представляющий из себя выпадающий список языков (тэг "select").</p>
	 *
	 *
	 *
	 *
	 * @param string $name  Имя выпадающего списка:<br> &lt;select name="<i>name</i>" ...&gt;
	 *
	 *
	 *
	 * @param string $value  Текущее значение (для инициализации выбранного значения).
	 *
	 *
	 *
	 * @param string $default_value = "" Если задан, то будет выводиться первым пунктом списка (например,
	 * фраза "выберите язык"). <br>Не обязательный. По умолчанию "".
	 *
	 *
	 *
	 * @param string $js_function = "" Имя JS функции которая будет вызываться при выборе в выпадающем
	 * списке какого либо значения:<br> &lt;select OnChange="<i>js_function</i>" ...&gt; <br>Не
	 * обязательный. По умолчанию "".
	 *
	 *
	 *
	 * @param string $add_to_select = "class=typeselect" Произвольный HTML который будет добавлен в тэг "select":<br> &lt;select
	 * <i>add_to_select</i> ...&gt;<br> Не обязательный. По умолчанию задан
	 * стандартный CSS класс для выпадающих списков административной
	 * части.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;table&gt;
	 *   &lt;tr&gt;
	 *     &lt;td&gt;Язык:&lt;/td&gt;
	 *     &lt;td&gt;&lt;?=<b>CLanguage::SelectBox</b>("LANGUAGE", LANGUAGE_ID)?&gt;&lt;/td&gt;
	 *   &lt;/tr&gt;
	 * &lt;/table&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/selectbox.php
	 * @author Bitrix
	 */
	public static function SelectBox($sFieldName, $sValue, $sDefaultValue="", $sFuncName="", $field="class=\"typeselect\"")
	{
		$by = "sort";
		$order = "asc";
		$l = CLanguage::GetList($by, $order);
		$s = '<select name="'.$sFieldName.'" '.$field;
		$s1 = '';
		if(strlen($sFuncName)>0) $s .= ' OnChange="'.$sFuncName.'"';
		$s .= '>'."\n";
		$found = false;
		while(($l_arr = $l->Fetch()))
		{
			$found = ($l_arr["LID"] == $sValue);
			$s1 .= '<option value="'.$l_arr["LID"].'"'.($found ? ' selected':'').'>['.htmlspecialcharsex($l_arr["LID"]).']&nbsp;'.htmlspecialcharsex($l_arr["NAME"]).'</option>'."\n";
		}
		if(strlen($sDefaultValue)>0)
			$s .= "<option value='' ".($found ? "" : "selected").">".htmlspecialcharsex($sDefaultValue)."</option>";
		return $s.$s1.'</select>';
	}

	public static function GetLangSwitcherArray()
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$result = array();
		$db_res = $DB->Query("SELECT * FROM b_language WHERE ACTIVE='Y' ORDER BY SORT");
		while($ar = $db_res->Fetch())
		{
			$ar["NAME"] = htmlspecialcharsbx($ar["NAME"]);
			$ar["SELECTED"] = ($ar["LID"]==LANG);

			global $QUERY_STRING;
			$p = rtrim(str_replace("&#", "#", preg_replace("/lang=[^&#]*&*/", "", $QUERY_STRING)), "&");
			$ar["PATH"] = $APPLICATION->GetCurPage()."?lang=".$ar["LID"].($p <> ''? '&amp;'.htmlspecialcharsbx($p) : '');

			$result[] = $ar;
		}
		return $result;
	}
}

class CLanguage extends CAllLanguage
{
}

class CLangAdmin extends CLanguage
{
}

$SHOWIMAGEFIRST=false;

function ShowImage($PICTURE_ID, $iMaxW=0, $iMaxH=0, $sParams=false, $strImageUrl="", $bPopup=false, $strPopupTitle=false,$iSizeWHTTP=0, $iSizeHHTTP=0)
{
	return CFile::ShowImage($PICTURE_ID, $iMaxW, $iMaxH, $sParams, $strImageUrl, $bPopup, $strPopupTitle,$iSizeWHTTP, $iSizeHHTTP);
}


abstract class CAllFilterQuery
{
	var $cnt = 0;
	var $m_query;
	var $m_words;
	var $m_fields;
	var $m_kav;
	var $default_query_type;
	var $rus_bool_lang;
	var $error;
	var $procent;
	var $ex_sep;
	var $clob;
	var $div_fields;
	var $clob_upper;
	var $errorno;

	function __construct($default_query_type = "and", $rus_bool_lang = "yes", $procent="Y", $ex_sep = array(), $clob="N", $div_fields="Y", $clob_upper="N")
	{
		$this->CFilterQuery($default_query_type, $rus_bool_lang, $procent, $ex_sep, $clob, $div_fields, $clob_upper);
	}

	public static abstract function BuildWhereClause($word);

	/*
	$default_query_type - logic for spaces
	$rus_bool_lang - use russian logic words
	$ex_sep - array with exceptions for delimiters
	*/
	public static function CFilterQuery($default_query_type = "and", $rus_bool_lang = "yes", $procent="Y", $ex_sep = array(), $clob="N", $div_fields="Y", $clob_upper="N")
	{
		$this->m_query  = "";
		$this->m_fields = "";
		$this->default_query_type = $default_query_type;
		$this->rus_bool_lang = $rus_bool_lang;
		$this->m_kav = array();
		$this->error = "";
		$this->procent = $procent;
		$this->ex_sep = $ex_sep;
		$this->clob = $clob;
		$this->clob_upper = $clob_upper;
		$this->div_fields = $div_fields;
	}

	public static function GetQueryString($fields, $query)
	{
		$this->m_words = array();
		if($this->div_fields=="Y")
			$this->m_fields = explode(",", $fields);
		else
			$this->m_fields = $fields;
		if(!is_array($this->m_fields))
			$this->m_fields=array($this->m_fields);

		$query = $this->CutKav($query);
		$query = $this->ParseQ($query);
		if($query == "( )" || strlen($query)<=0)
		{
			$this->error=GetMessage("FILTER_ERROR3");
			$this->errorno=3;
			return false;
		}
		$query = $this->PrepareQuery($query);

		return $query;
	}

	public static function CutKav($query)
	{
		$bdcnt = 0;
		while (preg_match("/\"([^\"]*)\"/",$query,$pt))
		{
			$res = $pt[1];
			if(strlen(trim($pt[1]))>0)
			{
				$trimpt = $bdcnt."cut5";
				$this->m_kav[$trimpt] = $res;
				$query = str_replace("\"".$pt[1]."\"", " ".$trimpt." ", $query);
			}
			else
			{
				$query = str_replace("\"".$pt[1]."\"", " ", $query);
			}
			$bdcnt++;
			if($bdcnt>100) break;
		}

		$bdcnt = 0;
		while (preg_match("/'([^']*)'/",$query,$pt))
		{
			$res = $pt[1];
			if(strlen(trim($pt[1]))>0)
			{
				$trimpt = $bdcnt."cut6";
				$this->m_kav[$trimpt] = $res;
				$query = str_replace("'".$pt[1]."'", " ".$trimpt." ", $query);
			}
			else
			{
				$query = str_replace("'".$pt[1]."'", " ", $query);
			}
			$bdcnt++;
			if($bdcnt>100) break;
		}
		return $query;
	}

	public static function ParseQ($q)
	{
		$q = trim($q);
		if(strlen($q) <= 0)
			return '';

		$q=$this->ParseStr($q);

		$q = str_replace(
			array("&"   , "|"   , "~"  , "("  , ")"),
			array(" && ", " || ", " ! ", " ( ", " ) "),
			$q
		);
		$q="( $q )";
		$q = preg_replace("/\\s+/".BX_UTF_PCRE_MODIFIER, " ", $q);

		return $q;
	}

	public static function ParseStr($qwe)
	{
		$qwe=trim($qwe);

		$qwe=preg_replace("/ {0,}\\+ {0,}/", "&", $qwe);

		$qwe=preg_replace("/ {0,}([()|~]) {0,}/", "\\1", $qwe);

		// default query type is and
		if(strtolower($this->default_query_type) == 'or')
			$default_op = "|";
		else
			$default_op = "&";

		$qwe=preg_replace("/( {1,}|\\&\\|{1,}|\\|\\&{1,})/", $default_op, $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);
		$qwe=preg_replace("/\\~+/", "~", $qwe);
		$qwe=preg_replace("/\\|\\&\\|/", "&", $qwe);
		$qwe=preg_replace("/[|&~]+$/", "", $qwe);
		$qwe=preg_replace("/^[|&]+/", "", $qwe);

		// transform "w1 ~w2" -> "w1 default_op ~ w2"
		// ") ~w" -> ") default_op ~w"
		// "w ~ (" -> "w default_op ~("
		// ") w" -> ") default_op w"
		// "w (" -> "w default_op ("
		// ")(" -> ") default_op ("

		$qwe=preg_replace("/([^&~|()]+)~([^&~|()]+)/", "\\1".$default_op."~\\2", $qwe);
		$qwe=preg_replace("/\\)~{1,}/", ")".$default_op."~", $qwe);
		$qwe=preg_replace("/~{1,}\\(/", ($default_op=="|"? "~|(": "&~("), $qwe);
		$qwe=preg_replace("/\\)([^&~|()]+)/", ")".$default_op."\\1", $qwe);
		$qwe=preg_replace("/([^&~|()]+)\\(/", "\\1".$default_op."(", $qwe);
		$qwe=preg_replace("/\\) *\\(/", ")".$default_op."(", $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);

		// remove errornous format of query - ie: '(&', '&)', '(|', '|)', '~&', '~|', '~)'
		$qwe=preg_replace("/\\(\\&{1,}/", "(", $qwe);
		$qwe=preg_replace("/\\&{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\~{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\(\\|{1,}/", "(", $qwe);
		$qwe=preg_replace("/\\|{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\~{1,}\\&{1,}/", "&", $qwe);
		$qwe=preg_replace("/\\~{1,}\\|{1,}/", "|", $qwe);

		$qwe=preg_replace("/\\(\\)/", "", $qwe);
		$qwe=preg_replace("/^[|&]{1,}/", "", $qwe);
		$qwe=preg_replace("/[|&~]{1,}\$/", "", $qwe);
		$qwe=preg_replace("/\\|\\&/", "&", $qwe);
		$qwe=preg_replace("/\\&\\|/", "|", $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);

		return($qwe);
	}

	public static function PrepareQuery($q)
	{
		$state = 0;
		$qu = "";
		$n = 0;
		$this->error = "";

		$t=strtok($q," ");

		while (($t!="") && ($this->error==""))
		{
			switch ($state)
			{
			case 0:
				if(($t=="||") || ($t=="&&") || ($t==")"))
				{
					$this->error=GetMessage("FILTER_ERROR2")." ".$t;
					$this->errorno=2;
				}
				elseif($t=="!")
				{
					$state=0;
					$qu="$qu NOT ";
					break;
				}
				elseif($t=="(")
				{
					$n++;
					$state=0;
					$qu="$qu(";
				}
				else
				{
					$state=1;
					$qu="$qu ".$this->BuildWhereClause($t)." ";
				}
				break;

			case 1:
				if(($t=="||") || ($t=="&&"))
				{
					$state=0;
					if($t=='||') $qu="$qu OR ";
					else $qu="$qu AND ";
				}
				elseif($t==")")
				{
					$n--;
					$state=1;
					$qu="$qu)";
				}
				else
				{
					$this->error=GetMessage("FILTER_ERROR2")." ".$t;
					$this->errorno=2;
				}
				break;
			}
			$t=strtok(" ");
		}

		if(($this->error=="") && ($n != 0))
		{
			$this->error=GetMessage("FILTER_ERROR1");
			$this->errorno=1;
		}
		if($this->error!="") return 0;

		return $qu;
	}
}

class CAllLang extends CAllSite
{
}

class CSiteTemplate
{
	var $LAST_ERROR;

	public static function GetList($arOrder=array(), $arFilter=array(), $arSelect=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(isset($arFilter["ID"]) && !is_array($arFilter["ID"]))
			$arFilter["ID"] = array($arFilter["ID"]);

		$arRes = array();
		$path = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates";
		$handle  = opendir($path);
		if($handle)
		{
			while(($file = readdir($handle)) !== false)
			{
				if($file == "." || $file == ".." || !is_dir($path."/".$file))
					continue;

				if($file == ".default")
					continue;

				if(isset($arFilter["ID"]) && !in_array($file, $arFilter["ID"]))
					continue;

				$arTemplate = array("DESCRIPTION"=>"");

				if(file_exists(($fname = $path."/".$file."/lang/".LANGUAGE_ID."/description.php")))
					__IncludeLang($fname, false, true);
				elseif(file_exists(($fname = $path."/".$file."/lang/".LangSubst(LANGUAGE_ID)."/description.php")))
					__IncludeLang($fname, false, true);

				if(file_exists(($fname = $path."/".$file."/description.php")))
					include($fname);

				$arTemplate["ID"] = $file;
				if(!isset($arTemplate["NAME"]))
					$arTemplate["NAME"] = $file;

				if($arSelect === false || in_array("SCREENSHOT", $arSelect))
				{
					if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$file."/lang/".LANGUAGE_ID."/screen.gif"))
						$arTemplate["SCREENSHOT"] = BX_PERSONAL_ROOT."/templates/".$file."/lang/".LANGUAGE_ID."/screen.gif";
					elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$file."/screen.gif"))
						$arTemplate["SCREENSHOT"] = BX_PERSONAL_ROOT."/templates/".$file."/screen.gif";
					else
						$arTemplate["SCREENSHOT"] = false;

					if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$file."/lang/".LANGUAGE_ID."/preview.gif"))
						$arTemplate["PREVIEW"] = BX_PERSONAL_ROOT."/templates/".$file."/lang/".LANGUAGE_ID."/preview.gif";
					elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$file."/preview.gif"))
						$arTemplate["PREVIEW"] = BX_PERSONAL_ROOT."/templates/".$file."/preview.gif";
					else
						$arTemplate["PREVIEW"] = false;
				}

				if($arSelect === false || in_array("CONTENT", $arSelect))
					$arTemplate["CONTENT"] = $APPLICATION->GetFileContent($path."/".$file."/header.php")."#WORK_AREA#".$APPLICATION->GetFileContent($path."/".$file."/footer.php");

				if($arSelect === false || in_array("STYLES", $arSelect))
				{
					if(file_exists($path."/".$file."/styles.css"))
					{
						$arTemplate["STYLES"] = $APPLICATION->GetFileContent($path."/".$file."/styles.css");
						$arTemplate["STYLES_TITLE"] = CSiteTemplate::__GetByStylesTitle($path."/".$file."/.styles.php");
					}

					if(file_exists($path."/".$file."/template_styles.css"))
						$arTemplate["TEMPLATE_STYLES"] = $APPLICATION->GetFileContent($path."/".$file."/template_styles.css");
				}

				$arRes[] = $arTemplate;
			}
			closedir($handle);
		}
		$db_res = new CDBResult;
		$db_res->InitFromArray($arRes);

		return $db_res;
	}

	function __GetByStylesTitle($file)
	{
		if(file_exists($file))
			return include($file);
		return false;
	}

	public static function GetByID($ID)
	{
		return CSiteTemplate::GetList(array(), array("ID"=>$ID));
	}

	public static function CheckFields($arFields, $ID=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->LAST_ERROR = "";
		$arMsg = array();

		if($ID===false)
		{
			if(strlen($arFields["ID"])<=0)
				$this->LAST_ERROR .= GetMessage("MAIN_ENTER_TEMPLATE_ID")." ";
			elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]))
				$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_ID_EX")." ";

			if(!is_set($arFields, "CONTENT"))
				$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_CONTENT_NA")." ";
		}

		if(is_set($arFields, "CONTENT") && strlen($arFields["CONTENT"])<=0)
		{
			$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_CONTENT_NA")." ";
			$arMsg[] = array("id"=>"CONTENT", "text"=> GetMessage("MAIN_TEMPLATE_CONTENT_NA"));
		}
		elseif(is_set($arFields, "CONTENT") && strpos($arFields["CONTENT"], "#WORK_AREA#")===false)
		{
			$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_WORKAREA_NA")." ";
			$arMsg[] = array("id"=>"CONTENT", "text"=> GetMessage("MAIN_TEMPLATE_WORKAREA_NA"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}

	public static function Add($arFields)
	{
		if(!$this->CheckFields($arFields))
			return false;

		/** @global CMain $APPLICATION */
		global $APPLICATION;

		CheckDirPath($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]);
		if(is_set($arFields, "CONTENT"))
		{
			$p = strpos($arFields["CONTENT"], "#WORK_AREA#");
			$header = substr($arFields["CONTENT"], 0, $p);
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]."/header.php", $header);
			$footer = substr($arFields["CONTENT"], $p + strlen("#WORK_AREA#"));
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]."/footer.php", $footer);
		}
		if(is_set($arFields, "STYLES"))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]."/styles.css", $arFields["STYLES"]);
		}

		if(is_set($arFields, "TEMPLATE_STYLES"))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]."/template_styles.css", $arFields["TEMPLATE_STYLES"]);
		}

		if(is_set($arFields, "NAME") || is_set($arFields, "DESCRIPTION"))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]."/description.php",
				'<'.'?'.
				'$arTemplate = array("NAME"=>"'.EscapePHPString($arFields['NAME']).'", "DESCRIPTION"=>"'.EscapePHPString($arFields['DESCRIPTION']).'");'.
				'?'.'>'
				);
		}

		return $arFields["ID"];
	}


	public static function Update($ID, $arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		CheckDirPath($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID);
		if(is_set($arFields, "CONTENT"))
		{
			$p = strpos($arFields["CONTENT"], "#WORK_AREA#");
			$header = substr($arFields["CONTENT"], 0, $p);
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID."/header.php", $header);
			$footer = substr($arFields["CONTENT"], $p + strlen("#WORK_AREA#"));
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID."/footer.php", $footer);
		}
		if(is_set($arFields, "STYLES"))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID."/styles.css", $arFields["STYLES"]);
		}

		if(is_set($arFields, "TEMPLATE_STYLES"))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID."/template_styles.css", $arFields["TEMPLATE_STYLES"]);
		}

		if(is_set($arFields, "NAME") || is_set($arFields, "DESCRIPTION"))
		{
			$db_t = CSiteTemplate::GetList(array(), array("ID"=>$ID));
			$ar_t = $db_t->Fetch();
			if(!is_set($arFields, "NAME"))
				$arFields["NAME"] = $ar_t["NAME"];
			if(!is_set($arFields, "DESCRIPTION"))
				$arFields["DESCRIPTION"] = $ar_t["DESCRIPTION"];
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID."/description.php",
				'<'.'?'.
				'$arTemplate = array("NAME"=>"'.EscapePHPString($arFields['NAME']).'", "DESCRIPTION"=>"'.EscapePHPString($arFields['DESCRIPTION']).'");'.
				'?'.'>'
				);
		}

		return true;
	}

	public static function Delete($ID)
	{
		if($ID==".default")
			return false;
		DeleteDirFilesEx(BX_PERSONAL_ROOT."/templates/".$ID);
		return true;
	}



	public static function GetContent($ID)
	{
		if(strlen($ID)<=0)
			$arRes = array();
		else
			$arRes = CSiteTemplate::DirsRecursive($ID);
		$db_res = new CDBResult;
		$db_res->InitFromArray($arRes);
		return $db_res;
	}


	public static function DirsRecursive($ID, $path="", $depth=0, $maxDepth=1)
	{
		$arRes = array();
		$depth++;

		GetDirList(BX_PERSONAL_ROOT."/templates/".$ID."/".$path, $arDirsTmp, $arResTmp);
		foreach($arResTmp as $file)
		{
			switch($file["NAME"])
			{
			case "chain_template.php":
				$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_NAV");
				break;
			case "":
				$file["DESCRIPTION"] = "";
				break;
			default:
				if(($p=strpos($file["NAME"], ".menu_template.php"))!==false)
					$file["DESCRIPTION"] = str_replace("#MENU_TYPE#", substr($file["NAME"], 0, $p), GetMessage("MAIN_TEMPLATE_MENU"));
				elseif(($p=strpos($file["NAME"], "authorize_registration.php"))!==false)
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_AUTH_REG");
				elseif(($p=strpos($file["NAME"], "forgot_password.php"))!==false)
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_SEND_PWD");
				elseif(($p=strpos($file["NAME"], "change_password.php"))!==false)
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_CHN_PWD");
				elseif(($p=strpos($file["NAME"], "authorize.php"))!==false)
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_AUTH");
				elseif(($p=strpos($file["NAME"], "registration.php"))!==false)
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_REG");
			}
			$arRes[] = $file;
		}

		$nTemplateLen = strlen(BX_PERSONAL_ROOT."/templates/".$ID."/");
		foreach($arDirsTmp as $dir)
		{
			$arDir = $dir;
			$arDir["DEPTH_LEVEL"] = $depth;
			$arRes[] = $arDir;

			if($depth < $maxDepth)
			{
				$dirPath = substr($arDir["ABS_PATH"], $nTemplateLen);
				$arRes = array_merge($arRes, CSiteTemplate::DirsRecursive($ID, $dirPath, $depth, $maxDepth));
			}
		}
		return $arRes;
	}
}

class CApplicationException
{
	var $msg, $id;
	public static function CApplicationException($msg, $id = false)
	{
		$this->msg = $msg;
		$this->id = $id;
	}

	
	/**
	 * <p>Метод возвращает текст исключения</p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $DB-&gt;StartTransaction();<br>if(!$langs-&gt;Delete($del_id))<br>{
	 *   $DB-&gt;Rollback();
	 *   if($ex = $APPLICATION-&gt;GetException())
	 *     $strError = $ex-&gt;GetString();
	 * }
	 * else
	 *   $DB-&gt;Commit();<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/capplicationexception/index.php">CApplicationException</a></li>
	 * <li><a href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/capplicationexception/getstring.php
	 * @author Bitrix
	 */
	public static function GetString()
	{
		return $this->msg;
	}

	public static function GetID()
	{
		return $this->id;
	}
}

class CAdminException extends CApplicationException
{
	var $messages;
	public static function CAdminException($messages, $id = false)
	{
		//array("id"=>"", "text"=>""), array(...), ...
		$this->messages = $messages;
		$s = "";
		foreach($this->messages as $msg)
			$s .= $msg["text"]."<br>";
		parent::CApplicationException($s, $id);
	}

	public static function GetMessages()
	{
		return $this->messages;
	}

	public static function AddMessage($message)
	{
		$this->messages[]=$message;
		$this->msg.=$message["text"]."<br>";
	}
}

class CCaptchaAgent
{
	public static function DeleteOldCaptcha($sec = 3600)
	{
		global $DB;

		$sec = intval($sec);

		$time = $DB->CharToDateFunction(GetTime(time()-$sec,"FULL"));
		if (!$DB->Query("DELETE FROM b_captcha WHERE DATE_CREATE <= ".$time))
			return false;

		return "CCaptchaAgent::DeleteOldCaptcha(".$sec.");";
	}
}

class CDebugInfo
{
	var $start_time, $cnt_query, $query_time;
	var $cache_size;
	var $arQueryDebugSave;
	var $arResult;
	static $level = 0;
	var $is_comp = true;

	function __construct($is_comp = true)
	{
		$this->is_comp = $is_comp;
	}

	public static function Start()
	{
		global $DB;
		if($this->is_comp)
			self::$level++;

		$this->cache_size = $GLOBALS["CACHE_STAT_BYTES"];
		$GLOBALS["CACHE_STAT_BYTES"] = 0;

		$this->start_time = getmicrotime();
		if($DB->ShowSqlStat)
		{
			$this->cnt_query = $DB->cntQuery;
			$DB->cntQuery = 0;
			$this->query_time = $DB->timeQuery;
			$DB->timeQuery = 0;
			$this->arQueryDebugSave = $DB->arQueryDebug;
			$DB->arQueryDebug = array();
		}
	}

	public static function Stop($rel_path="", $path="", $cache_type="")
	{
		global $DB, $APPLICATION;
		if($this->is_comp)
			self::$level--;

		$this->arResult = array(
			"PATH" => $path,
			"REL_PATH" => $rel_path,
			"QUERY_COUNT" => 0,
			"QUERY_TIME" => 0,
			"QUERIES" => array(),
			"TIME" => (getmicrotime() - $this->start_time),
			"BX_STATE" => $GLOBALS["BX_STATE"],
			"CACHE_TYPE" => $cache_type,
			"CACHE_SIZE" => $GLOBALS["CACHE_STAT_BYTES"],
			"LEVEL" => self::$level,
		);
		$GLOBALS["CACHE_STAT_BYTES"] += $this->cache_size;

		if($DB->ShowSqlStat)
		{
			if($DB->cntQuery)
			{
				$this->arResult["QUERY_COUNT"] = $DB->cntQuery;
				$this->arResult["QUERY_TIME"] = $DB->timeQuery;
				$this->arResult["QUERIES"] = $DB->arQueryDebug;
			}
			$DB->arQueryDebug = $this->arQueryDebugSave;
			$DB->cntQuery = $this->cnt_query;
			$DB->timeQuery = $this->query_time;
		}

		$APPLICATION->arIncludeDebug[] = $this->arResult;
	}

	public static function Output($rel_path="", $path="", $cache_type="")
	{
		global $APPLICATION;

		$this->Stop($rel_path, $path, $cache_type);
		$result = "";

		$result .= '<div class="bx-component-debug">';
		$result .= ($rel_path<>""? $rel_path.": ":"")."<nobr>".round($this->arResult["TIME"], 4)." ".GetMessage("main_incl_file_sec")."</nobr>";
		if($this->arResult["QUERY_COUNT"])
		{
				$result .= '; <a title="'.GetMessage("main_incl_file_sql_stat").'" href="javascript:BX_DEBUG_INFO_'.(count($APPLICATION->arIncludeDebug)-1).'.Show(); BX_DEBUG_INFO_'.(count($APPLICATION->arIncludeDebug)-1).'.ShowDetails(\'BX_DEBUG_INFO_'.(count($APPLICATION->arIncludeDebug)-1).'_1\'); ">'.GetMessage("main_incl_file_sql").' '.($this->arResult["QUERY_COUNT"]).' ('.round($this->arResult["QUERY_TIME"], 4).' '.GetMessage("main_incl_file_sec").')</a>';
				//$result .= '; <a title="'.GetMessage("main_incl_file_sql_stat").'" href="javascript:jsDebugWindow.Show(\'BX_DEBUG_INFO_'.(count($APPLICATION->arIncludeDebug)-1).'\')">'.GetMessage("main_incl_file_sql").' '.($this->arResult["QUERY_COUNT"]).' ('.round($this->arResult["QUERY_TIME"], 4).' '.GetMessage("main_incl_file_sec").')</a>';
		}
		if($this->arResult["CACHE_SIZE"])
			$result .= "<nobr>; ".GetMessage("main_incl_cache_stat")." ".CFile::FormatSize($this->arResult["CACHE_SIZE"], 0)."</nobr>";
		$result .= "</div>";

		return $result;
	}
}
