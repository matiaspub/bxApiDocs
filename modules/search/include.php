<?
if(!defined("CACHED_b_search_tags")) // define("CACHED_b_search_tags", 3600);
if(!defined("CACHED_b_search_tags_len")) // define("CACHED_b_search_tags_len", 2);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/tools/stemming.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/tools/tags.php");

global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"search",
	array(
		"CSearchCallback" => "classes/general/search.php",
		"CSearch" => "classes/".$db_type."/search.php",
		"CSearchItem" => "classes/general/item.php",
		"CSearchQuery" => "classes/".$db_type."/search.php",
		"CAllSiteMap" => "classes/general/sitemap.php",
		"CSiteMap" => "classes/".$db_type."/sitemap.php",
		"CSearchCustomRank" => "classes/general/customrank.php",
		"CSearchTags" => "classes/general/tags.php",
		"CSearchSuggest" => "classes/".$db_type."/suggest.php",
		"CSearchStatistic" => "classes/general/statistic.php",
		"CSearchTitle" => "classes/".$db_type."/title.php",
		"CSearchLanguage" => "tools/language.php",
		"CSearchUser" => "classes/general/user.php",
		"CSearchParameters" => "classes/general/comp_parameters.php",
		"search" => "install/index.php",
		"CSearchFullText" => "classes/general/full_text.php",
		"CSearchSphinx" => "tools/sphinx.php",
		"CSearchStemTable" => "tools/stemtable.php",
	)
);

CJSCore::RegisterExt('search_tags', array(
	'js' => '/bitrix/js/search/tags.js',
));
/**
 * Returns filtered sName concatenated with random number.
 *
 * @param string $sName
 * @return string
 * @deprecated
 */
function GenerateUniqId($sName)
{
	static $arPostfix = array();

	$sPostfix = rand();
	while(isset($arPostfix[$sPostfix]))
		$sPostfix = rand();

	$arPostfix[$sPostfix] = 1;

	return preg_replace("/\\W/", "_", $sName).$sPostfix;
}

$DB_test = CDatabase::GetModuleConnection('search', true);
if(!is_object($DB_test))
	return false;

if(COption::GetOptionString("search", "version", "") === "v2.0")
	define("BX_SEARCH_VERSION", 2);
elseif($DB->TableExists("b_search_stem"))
{
	define("BX_SEARCH_VERSION", 2);
	COption::SetOptionString("search", "version", "v2.0");
}
else
	// define("BX_SEARCH_VERSION", 1);
?>