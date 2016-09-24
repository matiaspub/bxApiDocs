<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/start.php");
error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE));

/**
 * <b>CMainPage</b> - класс для использования на индексной странице портала.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmainpage/index.php
 * @author Bitrix
 */
class CMainPage
{
	// определяет сайт по HTTP_HOST в таблице сайтов
	
	/**
	* <p>Возвращает идентификатор сайта, определяя его по текущему хосту. Если идентификатор сайта неверный, то вернет - "false".  Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
	* 
	* if($page = CMainPage::GetIncludeSitePage(<b>CMainPage::GetSiteByHost</b>()))
	*     require_once($page);
	* 
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/header.php");?&gt;
	* &lt;?require($_SERVER['DOCUMENT_ROOT']."/bitrix/footer.php");?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2833" >Список
	* терминов</a> </li> <li> <a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=03987"
	* >Конфигурирование многосайтовости</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmainpage/getsitebyhost.php
	* @author Bitrix
	*/
	public static function GetSiteByHost()
	{
		$cur_host = $_SERVER["HTTP_HOST"];
		$arURL = parse_url("http://".$cur_host);
		if($arURL["scheme"]=="" && strlen($arURL["host"])>0)
			$CURR_DOMAIN = $arURL["host"];
		else
			$CURR_DOMAIN = $cur_host;

		if(strpos($CURR_DOMAIN, ':')>0)
			$CURR_DOMAIN = substr($CURR_DOMAIN, 0, strpos($CURR_DOMAIN, ':'));
		$CURR_DOMAIN = Trim($CURR_DOMAIN, "\t\r\n\0 .");

		global $DB;
		$strSql =
			"SELECT L.LID as SITE_ID ".
			"FROM b_lang L, b_lang_domain LD ".
			"WHERE L.ACTIVE='Y' ".
			"	AND L.LID=LD.LID ".
			"	AND '".$DB->ForSql($CURR_DOMAIN, 255)."' LIKE ".$DB->Concat("'%'", "LD.DOMAIN")." ".
			"ORDER BY ".$DB->Length("LD.DOMAIN")." DESC, L.SORT";

		$res = $DB->Query($strSql);
		if($ar_res = $res->Fetch())
			return $ar_res["SITE_ID"];

		$sl = CSite::GetDefList();
		while ($slang = $sl->Fetch())
			if($slang["DEF"]=="Y")
				return $slang["SITE_ID"];

		return false;
	}

	// определяет сайт по HTTP_ACCEPT_LANGUAGE
	
	/**
	* <p>Возвращает идентификатор сайта, определяя его по переменной Accept-Language в настройках браузера посетителя. Приоритетным для данной функции является порядок языков установленный в настройках браузера посетителя. Если ни один из этих языков не подойдет, то будет выбран сайт с установленным флагом "Сайт по умолчанию". Нестатический метод.</p>
	*
	*
	* @param bool $compare_site_id = false Если значение "true", то поиск сайта будет осуществляться через
	* сравнение идентификатора языка из Accept-Language и идентификатора
	* сайта, если значение "false" - то сравнение будет между
	* идентификатором языка из Accept-Languageи идентификатором языка
	* выбранного в настройках сайта.<br>Необязательный. По умолчанию -
	* "false".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Для использования данного примера в качестве индексной страницы портала необходимо убедиться что:
	* <br>1. Многосайтовость организована по способу <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=286" target="_blank">Многосайтовость на одном домене</a>.
	* <br>2. Ни у одного из сайтов в поле "Папка сайта" не указано значение - "/". 
	* 
	* &lt;?
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
	* 
	* if ($sid = <b>CMainPage::GetSiteByAcceptLanguage</b>())
	*     CMainPage::RedirectToSite($sid);
	* 
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/header.php");?&gt;
	* &lt;?require($_SERVER['DOCUMENT_ROOT']."/bitrix/footer.php");?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2833" >Список
	* терминов</a> </li> <li> <a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=03987"
	* >Конфигурирование многосайтовости</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmainpage/getsitebyacceptlanguage.php
	* @author Bitrix
	*/
	public static function GetSiteByAcceptLanguage($compare_site_id=false)
	{
		$site_id = false;
		$arUserLang = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		$rsSites = CSite::GetDefList();
		while($arSite = $rsSites->Fetch())
		{
			$last_site_id = $arSite["ID"];
			if($arSite["DEF"]=="Y")
				$site_id = $arSite["ID"];
			$arSites[] = $arSite;
		}
		if(is_array($arUserLang))
		{
			foreach($arUserLang as $user_lid)
			{
				$user_lid = strtolower(substr($user_lid, 0, 2));
				foreach($arSites as $arSite)
				{
					$sid = ($compare_site_id) ? strtolower($arSite["ID"]) : strtolower($arSite["LANGUAGE_ID"]);
					if($user_lid==$sid)
						return $arSite["ID"];
				}
			}
		}
		if(strlen($site_id)<=0)
			return $last_site_id;
		return $site_id;
	}

	// делает перенаправление на сайт
	
	/**
	* <p>Перенаправляет на индексную страницу <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04773" >сайта</a>. Нестатический метод.</p>
	*
	*
	* @param string $site  Идентификатор [<a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04773" >сайта</a>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Для использования данного примера в качестве индексной страницы портала  необходимо убедиться что:
	* <br>1. Многосайтовость организована по способу <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=286" target="_blank">многосайтовости на одном домене</a>.
	* <br>2. Ни у одного из сайтов в поле "Папка сайта" не указано значение - "/". Т.е. корень каждого сайта - отдельный подкаталог.
	* 
	* &lt;?
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
	* 
	* if ($sid = CMainPage::GetSiteByAcceptLanguage())
	*     <b>CMainPage::RedirectToSite</b>($sid);
	* 
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/header.php");?&gt;
	* &lt;?require($_SERVER['DOCUMENT_ROOT']."/bitrix/footer.php");?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2833" >Список
	* терминов</a> </li> <li> <a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=03987"
	* >Конфигурирование многосайтовости</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmainpage/redirecttosite.php
	* @author Bitrix
	*/
	public static function RedirectToSite($site)
	{
		if(strlen($site)<=0) return false;
		$db_site = CSite::GetByID($site);
		if($arSite = $db_site->Fetch())
		{
			$arSite["DIR"] = RTrim($arSite["DIR"], ' \/');
			if(strlen($arSite["DIR"])>0)
				LocalRedirect((strlen($arSite["SERVER_NAME"])>0?"http://".$arSite["SERVER_NAME"]:"").$arSite["DIR"].$_SERVER["REQUEST_URI"], true);
		}
	}

	// подключает страницу с папки другого сайта
	
	/**
	* <p>Возвращает абсолютный путь на индексную страницу заданного сайта, для дальнейшего ее подключения при помощи require() или include(). Если ни один из сайтов не найден по хосту, то функция вернет - "false". Нестатический метод.</p>
	*
	*
	* @param string $site  Идентификатор <a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04773" >сайта</a>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
	* 
	* if($page = <b>CMainPage::GetIncludeSitePage</b>(CMainPage::GetSiteByHost()))
	*     require_once($page);
	* 
	* require($_SERVER['DOCUMENT_ROOT']."/bitrix/header.php");?&gt;
	* &lt;?require($_SERVER['DOCUMENT_ROOT']."/bitrix/footer.php");?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2833" >Список
	* терминов</a> </li> <li> <a
	* href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=03987"
	* >Конфигурирование многосайтовости</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmainpage/getincludesitepage.php
	* @author Bitrix
	*/
	public static function GetIncludeSitePage($site)
	{
		if(strlen($site)<=0) return false;
		$db_site = CSite::GetByID($site);
		if($arSite = $db_site->Fetch())
		{
			$arSite["DIR"] = RTrim($arSite["DIR"], ' \/');
			$cur_page = GetPagePath();
			if(strlen($arSite["DIR"])>0)
			{
				global $REQUEST_URI;
				$REQUEST_URI = $arSite["DIR"].$cur_page;
				$_SERVER["REQUEST_URI"] = $REQUEST_URI;
				return $_SERVER["DOCUMENT_ROOT"].$REQUEST_URI;
			}
		}
		return false;
	}
}
?>