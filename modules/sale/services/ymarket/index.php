<?
// define("NO_AGENT_CHECK", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
// define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!CModule::IncludeModule('sale'))
{
	CHTTP::SetStatus("500 Internal Server Error");
	die('{"error":"Module \"sale\" not installed"}');
}

$pattern = "#^\/bitrix\/services\/ymarket\/(([\w\d\-]{2})\/)?([\w\d\-]+)?(\/)?(([\w\d\-]+)(\/)?)?#";

preg_match ($pattern, $_SERVER["REQUEST_URI"], $matches);

$siteId = isset($matches[2]) ? $matches[2] : '';
$requestObject = isset($matches[3]) ? $matches[3] : '';
$method = isset($matches[6]) ? $matches[6] : '';

$postData = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && count($_POST) <= 0)
	$postData = file_get_contents("php://input");

$YMHandler = new CSaleYMHandler(array(
	"SITE_ID" => $siteId
));

$result = $YMHandler->processRequest($requestObject, $method, $postData);
$APPLICATION->RestartBuffer();
echo $result;

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>