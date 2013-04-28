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

	define("ADMIN_SECTION", true);

	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lib/loader.php");

	/** @var $application \Bitrix\Main\HttpApplication */
	$application = \Bitrix\Main\HttpApplication::getInstance();

	$application->turnOnCompatibleMode();
	$application->setInputParameters($_GET, $_POST, $_FILES, $_COOKIE, $_SERVER, $_ENV);

	$application->initialize();

	$page = new \Bitrix\Main\AdminPage();
	$application->setPage($page);

	$application->start();

	if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_jspopup.php");
	}

	if (isset($_SESSION['BX_ADMIN_LOAD_AUTH']))
	{
		// define('ADMIN_SECTION_LOAD_AUTH', 1);
		unset($_SESSION['BX_ADMIN_LOAD_AUTH']);
	}

	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/admin_tools.php");
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");

	CMain::PrologActions();

	return;
}

// define("START_EXEC_PROLOG_BEFORE_1", microtime());
$GLOBALS["BX_STATE"] = "PB";
unset($_REQUEST["BX_STATE"]);
unset($_GET["BX_STATE"]);
unset($_POST["BX_STATE"]);
unset($_COOKIE["BX_STATE"]);
unset($_FILES["BX_STATE"]);

// define("NEED_AUTH"// , true);
define("ADMIN_SECTION", true);

if (isset($_REQUEST['bxpublic']) && $_REQUEST['bxpublic'] == 'Y' && !defined('BX_PUBLIC_MODE'))
	// define('BX_PUBLIC_MODE', 1);

require_once(dirname(__FILE__)."/../include.php");
if(!headers_sent())
	header("Content-type: text/html; charset=".LANG_CHARSET);

if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST')
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_jspopup.php");
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/admin_tools.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");

CMain::PrologActions();
?>