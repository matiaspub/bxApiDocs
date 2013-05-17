<?
require_once(dirname(__FILE__)."/../bx_root.php");

if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/d7.php"))
{

	/**
	 * <p>Возвращает текущее время в Unix-формате.</p>
	 *
	 *
	 *
	 *
	 * @return float 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Алгоритм организации временных засечек
	 * $ptime = <b>getmicrotime</b>();
	 * 
	 * for ($i=0; $i&lt;10000; $i++);
	 * 
	 * echo "Цикл выполнялся ".round(<b>getmicrotime</b>()-$ptime, 3)." секунд";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/functions/date/getmicrotime.php
	 * @author Bitrix
	 */
	function getmicrotime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lib/loader.php");

	/** @var $application \Bitrix\Main\HttpApplication */
	$application = \Bitrix\Main\HttpApplication::getInstance();

	$application->turnOnCompatibleMode();
	$application->setInputParameters(
		$_GET, $_POST, $_FILES, $_COOKIE, $_SERVER, $_ENV
	);

	$application->initialize();

	$page = new \Bitrix\Main\PublicPage();
	$application->setPage($page);

	$application->start();

	CMain::PrologActions();
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");

	return;
}

if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled"))
{
	// define("BITRIX_STATIC_PAGES", true);
	require_once(dirname(__FILE__)."/../classes/general/cache_html.php");
	CHTMLPagesCache::startCaching();
}

require_once(dirname(__FILE__)."/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
?>