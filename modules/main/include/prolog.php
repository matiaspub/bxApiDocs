<?
require_once(dirname(__FILE__)."/../bx_root.php");

if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled"))
{
	// define("BITRIX_STATIC_PAGES", true);
	require_once(dirname(__FILE__)."/../classes/general/cache_html.php");
	CHTMLPagesCache::startCaching();
}

require_once(dirname(__FILE__)."/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
?>