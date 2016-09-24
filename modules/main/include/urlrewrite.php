<?
error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lib/loader.php");
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

require_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php");

if (defined("BX_URLREWRITE"))
	return;
// define("BX_URLREWRITE", true);

$foundQMark = strpos($_SERVER["REQUEST_URI"], "?");
$requestUriWithoutParams = ($foundQMark !== false? substr($_SERVER["REQUEST_URI"], 0, $foundQMark) : $_SERVER["REQUEST_URI"]);
$requestParams = ($foundQMark !== false? substr($_SERVER["REQUEST_URI"], $foundQMark) : "");

//decode only filename, not parameters
$requestPage = urldecode($requestUriWithoutParams);

if(!defined("BX_UTF") && CUtil::DetectUTF8($_SERVER["REQUEST_URI"]))
{
	$requestPage = \Bitrix\Main\Text\Encoding::convertEncoding($requestPage, "utf-8", (defined("BX_DEFAULT_CHARSET")? BX_DEFAULT_CHARSET : "windows-1251"));
}

$requestUri = $requestPage.$requestParams;

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io.php");
$io = CBXVirtualIo::GetInstance();

$arUrlRewrite = array();
if(file_exists($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php"))
	include($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php");

if((isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404') || isset($_GET["SEF_APPLICATION_CUR_PAGE_URL"]))
{
	if(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404' && !isset($_GET["SEF_APPLICATION_CUR_PAGE_URL"]))
	{
		$url = $requestUri;
	}
	else
	{
		if(!is_string($_GET["SEF_APPLICATION_CUR_PAGE_URL"]))
		{
			$_GET["SEF_APPLICATION_CUR_PAGE_URL"] = '';
		}
		$url = $requestUri = $_GET["SEF_APPLICATION_CUR_PAGE_URL"];
		$_SERVER["REQUEST_URI"] = $REQUEST_URI = CHTTP::urnEncode($_GET["SEF_APPLICATION_CUR_PAGE_URL"]);
		unset($_GET["SEF_APPLICATION_CUR_PAGE_URL"]);
	}

	if(($pos = strpos($url, "?")) !== false)
	{
		$params = substr($url, $pos+1);
		if ($params !== false && $params !== "")
		{
			parse_str($params, $vars);
			unset($vars["SEF_APPLICATION_CUR_PAGE_URL"]);

			$_GET += $vars;
			$_REQUEST += $vars;
			if (ini_get_bool("register_globals"))
				$GLOBALS += $vars;

			$_SERVER["QUERY_STRING"] = $QUERY_STRING = CHTTP::urnEncode($params);
		}
	}

	if (isset($_GET["SEF_APPLICATION_CUR_PAGE_URL"])
		&& (
			isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404'
			|| ($requestUriWithoutParams != "/bitrix/urlrewrite.php")
		)
	)
	{
		$url = $requestUri = $_SERVER["REQUEST_URI"] = $REQUEST_URI = "";
		$_GET = array();
		$_REQUEST = array();
		$_SERVER["QUERY_STRING"] = $QUERY_STRING = "";
	}

	$HTTP_GET_VARS = $_GET;

	$uriPath = GetRequestUri();
	// define("POST_FORM_ACTION_URI", htmlspecialcharsbx("/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=".urlencode($uriPath)));
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
				unset($vars["SEF_APPLICATION_CUR_PAGE_URL"]);

				$_GET += $vars;
				$_REQUEST += $vars;
				$_SERVER["QUERY_STRING"] = $QUERY_STRING = CHTTP::urnEncode($params);
				$url = substr($url, 0, $pos);
			}

			$url = _normalizePath($url);

			if(!$io->FileExists($_SERVER['DOCUMENT_ROOT'].$url))
				continue;

			if (!$io->ValidatePathString($url))
				continue;

			$urlTmp = strtolower(ltrim($url, "/\\"));
			$urlTmp = str_replace(".", "", $urlTmp);
			$urlTmp7 = substr($urlTmp, 0, 7);

			if (($urlTmp7 == "upload/" || ($urlTmp7 == "bitrix/" && substr($urlTmp, 0, 16) != "bitrix/services/" && substr($urlTmp, 0, 18) != "bitrix/groupdavphp")))
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
	$_SERVER["REAL_FILE_PATH"] = "/bitrix/admin/404.php";
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/404.php");
	die();
}

// define("BX_CHECK_SHORT_URI", true);
