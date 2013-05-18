<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/d7.php"))
{
	require_once(dirname(__FILE__)."/../bx_root.php");

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

error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/charset_converter.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");

$bSkipRewriteChecking = false;

//try to fix REQUEST_URI under IIS
$aProtocols = array('http', 'https');
foreach($aProtocols as $prot)
{
	$marker = "404;".$prot."://";
	if(($p = strpos($_SERVER["QUERY_STRING"], $marker)) !== false)
	{
		$uri = $_SERVER["QUERY_STRING"];
		if(($p = strpos($uri, "/", $p+strlen($marker))) !== false)
		{
			if($_SERVER["REQUEST_URI"] == '' || $_SERVER["REQUEST_URI"] == '/404.php' || strpos($_SERVER["REQUEST_URI"], $marker) !== false)
			{
				$_SERVER["REQUEST_URI"] = $REQUEST_URI = substr($uri, $p);
			}
			$_SERVER["REDIRECT_STATUS"] = '404';
			$_SERVER["QUERY_STRING"] = $QUERY_STRING = "";
			$_GET = array();
			break;
		}
	}
}

if (!defined("AUTH_404"))
	// define("AUTH_404", "Y");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php");

// define("BX_URLREWRITE", true);

$requestUri = urldecode($_SERVER["REQUEST_URI"]);

$bUTF = (!defined("BX_UTF") && CUtil::DetectUTF8($_SERVER["REQUEST_URI"]));
if($bUTF)
	$requestUri = CharsetConverter::ConvertCharset($requestUri, "utf-8", (defined("BX_DEFAULT_CHARSET")? BX_DEFAULT_CHARSET : "windows-1251"));

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io.php");
$io = CBXVirtualIo::GetInstance();

$arUrlRewrite = array();
if(file_exists($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php"))
	include($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php");

if(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404' || isset($_GET["SEF_APPLICATION_CUR_PAGE_URL"]))
{
	if(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404')
	{
		$url = $requestUri;
	}
	else
	{
		$url = $requestUri = $_SERVER["REQUEST_URI"] = $REQUEST_URI = ((is_array($_GET["SEF_APPLICATION_CUR_PAGE_URL"])) ? '' : $_GET["SEF_APPLICATION_CUR_PAGE_URL"]);
	}

	if(($pos=strpos($url, "?"))!==false)
	{
		$params = substr($url, $pos+1);
		parse_str($params, $vars);

		$_GET += $vars;
		$_REQUEST += $vars;
		$GLOBALS += $vars;
		$_SERVER["QUERY_STRING"] = $QUERY_STRING = $params;
	}

	if (isset($_GET["SEF_APPLICATION_CUR_PAGE_URL"]) && isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404')
	{
		$url = $requestUri = $_SERVER["REQUEST_URI"] = $REQUEST_URI = "";
		$_GET = array();
		$_REQUEST = array();
		$_SERVER["QUERY_STRING"] = $QUERY_STRING = "";
	}

	$HTTP_GET_VARS = $_GET;
	$sUrlPath = GetPagePath();
	$strNavQueryString = DeleteParam(array("SEF_APPLICATION_CUR_PAGE_URL"));
	if($strNavQueryString != "")
		$sUrlPath = $sUrlPath."?".$strNavQueryString;
	// define("POST_FORM_ACTION_URI", htmlspecialcharsbx("/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=".urlencode($sUrlPath)));
}

if (!CHTTP::isPathTraversalUri($_SERVER["REQUEST_URI"]))
{
	foreach($arUrlRewrite as $val)
	{
		if(preg_match($val["CONDITION"], $requestUri))
		{
			if (strlen($val["RULE"]) > 0)
				$url = preg_replace($val["CONDITION"], (strlen($val["PATH"]) > 0 ? $val["PATH"]."?" : "").$val["RULE"], $requestUri);
			else
				$url = $val["PATH"];

			if(($pos=strpos($url, "?"))!==false)
			{
				$params = substr($url, $pos+1);
				parse_str($params, $vars);

				$_GET += $vars;
				$_REQUEST += $vars;
				$GLOBALS += $vars;
				$_SERVER["QUERY_STRING"] = $QUERY_STRING = $params;
				$url = substr($url, 0, $pos);
			}

			$url = _normalizePath($url);

			if(!$io->FileExists($_SERVER['DOCUMENT_ROOT'].$url))
				continue;

			if (!$io->ValidatePathString($io->GetPhysicalName($url)))
				continue;

			$urlTmp = strtolower(ltrim($url, "/\\"));
			$urlTmp = str_replace(".", "", $urlTmp);
			$urlTmp = substr($urlTmp, 0, 7);
			if (($urlTmp == "bitrix/") || ($urlTmp == "upload/"))
				continue;

			$ext = strtolower(GetFileExtension($url));
			if ($ext != "php")
				continue;

			CHTTP::SetStatus("200 OK");

			$_SERVER["REAL_FILE_PATH"] = $url;

			include_once($io->GetPhysicalName($_SERVER['DOCUMENT_ROOT'].$url));

			die();
		}
	}
}

//admin section 404
if(strpos($requestUri, "/bitrix/admin/") === 0)
{
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/404.php");
	die();
}

// define("BX_CHECK_SHORT_URI", true);
?>