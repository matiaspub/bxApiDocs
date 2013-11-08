<?
require_once(dirname(__FILE__)."/../bx_root.php");

// define("START_EXEC_PROLOG_BEFORE_1", microtime());
$GLOBALS["BX_STATE"] = "PB";
unset($_REQUEST["BX_STATE"]);
unset($_GET["BX_STATE"]);
unset($_POST["BX_STATE"]);
unset($_COOKIE["BX_STATE"]);
unset($_FILES["BX_STATE"]);

// define("NEED_AUTH", true);
// define("ADMIN_SECTION", true);

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