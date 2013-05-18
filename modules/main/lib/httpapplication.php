<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use \Bitrix\Main\IO;
use \Bitrix\Main\Security;
use Bitrix\Main\Web\Uri;

/**
 * Http application extends application. Contains http specific methods.
 */
class HttpApplication extends Application
{
	/**
	 * Page of current request.
	 *
	 * @var \Bitrix\Main\Page
	 */
	protected $page;

	/**
	 * @var array
	 */
	protected $arInputParameters = array();

	/**
	 * Creates new instance of http application.
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->arInputParameters = array(
			"get" => array(),
			"post" => array(),
			"files" => array(),
			"cookie" => array(),
			"server" => array(),
			"env" => array(),
		);
	}

	static public function setInputParameters($get, $post, $files, $cookie, $server, $env)
	{
		if (!$this->canSetInputParameters())
			return;

		$this->arInputParameters = array(
			"get" => $get,
			"post" => $post,
			"files" => $files,
			"cookie" => $cookie,
			"server" => $server,
			"env" => $env,
		);
	}

	/**
	 * Initializes context of the current request.
	 */
	protected function initializeContext()
	{
		$context = new HttpContext($this);

		$server = new Server($this->arInputParameters["server"]);

		$request = new HttpRequest(
			$server,
			$this->arInputParameters["get"],
			$this->arInputParameters["post"],
			$this->arInputParameters["files"],
			$this->arInputParameters["cookie"]
		);

		$response = new HttpResponse($context);

		$env = new Environment($this->arInputParameters["env"]);

		$context->initialize($request, $response, $server, $env);

		$this->setContext($context);
	}

	/**
	 * Initializes default culture of the current request.
	 */
	protected function initializeCulture()
	{
		$this->context->setCulture(new Context\Culture());
	}

	/**
	 * Initializes basic part of kernel. It is called before update system call.
	 */
	protected function initializeBasicKernel()
	{
	}

	private function isRequestedUriExists()
	{
		/** @var $request HttpRequest */
		$request = $this->getContext()->getRequest();
		$absUrl = IO\Path::convertRelativeToAbsolute($request->getRequestedPage());

		return IO\File::isFileExists($absUrl);
	}

	private function fixUpRequestUriAndQueryString()
	{
		/** @var $context HttpContext */
		$context = $this->context;

		$queryString = $context->getServer()->get("QUERY_STRING");
		$requestUri = $context->getServer()->get("REQUEST_URI");
		$redirectStatus = $context->getServer()->get("REDIRECT_STATUS");

		//try to fix REQUEST_URI under IIS
		$arProtocols = array('http', 'https');
		foreach ($arProtocols as $protocol)
		{
			$marker = "404;".$protocol."://";
			if (($p = strpos($queryString, $marker)) !== false)
			{
				$uri = $queryString;
				if (($p = strpos($uri, "/", $p + strlen($marker))) !== false)
				{
					if ($requestUri == '' || $requestUri == '/404.php' || strpos($requestUri, $marker) !== false)
					{
						$requestUriTmp = substr($uri, $p);
						if (!Uri::isPathTraversalUri($requestUriTmp))
							$requestUri = $requestUriTmp;
					}
					$redirectStatus = '404';
					$queryString = '';
					break;
				}
			}
		}

		$requestUri = urldecode($requestUri);
		$requestUri = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($requestUri);

		$sefApplicationCurPageUrl = $context->getRequest()->get("SEF_APPLICATION_CUR_PAGE_URL");
		if ($redirectStatus == '404' || $sefApplicationCurPageUrl != null)
		{
			if ($redirectStatus != '404')
			{
				if (!Uri::isPathTraversalUri($sefApplicationCurPageUrl))
					$requestUri = $sefApplicationCurPageUrl;
			}

			if (($pos = strpos($requestUri, "?")) !== false)
				$queryString = substr($requestUri, $pos + 1);
		}

		if ($queryString != $context->getServer()->get("QUERY_STRING")
			|| $requestUri != $context->getServer()->get("REQUEST_URI")
			|| $redirectStatus != $context->getServer()->get("REDIRECT_STATUS"))
		{
			$context->rewriteUri($requestUri, $queryString, $redirectStatus);
		}
	}

	protected function rewriteUrlIfNeeded()
	{
		if ($this->isRequestedUriExists())
			return;

		$this->fixUpRequestUriAndQueryString();

		/** @var $context HttpContext */
		$context = $this->context;

		$queryString = $context->getServer()->get("QUERY_STRING");
		$requestUri = $context->getServer()->get("REQUEST_URI");

		$arUrlRewriteRules = $this->loadUrlRewriteRules();
		foreach ($arUrlRewriteRules as $rule)
		{
			if (preg_match($rule["CONDITION"], $requestUri))
			{
				if (strlen($rule["RULE"]) > 0)
					$url = preg_replace($rule["CONDITION"], (strlen($rule["PATH"]) > 0 ? $rule["PATH"]."?" : "").$rule["RULE"], $requestUri);
				else
					$url = $rule["PATH"];

				$params = "";
				if (($pos = strpos($url, "?")) !== false)
				{
					$params = substr($url, $pos + 1);
					$url = substr($url, 0, $pos);
				}

				/** @var $response HttpResponse */
				$response = $context->getResponse();
				$response->setStatus("200 OK");

				$context->transferUri($url, $params);
				$this->setTransferUri($url);

				break;
			}
		}
	}

	private function loadUrlRewriteRules()
	{
		$arUrlRewrite = array();
		if (file_exists(Application::getDocumentRoot()."/urlrewrite.php"))
			include(Application::getDocumentRoot()."/urlrewrite.php");
		return $arUrlRewrite;
	}

	protected function createExceptionHandlerOutput()
	{
		return new Diag\HttpExceptionHandlerOutput();
	}

	/**
	 * Initializes extended part of kernel. It is called after update system call.
	 */
	protected function initializeExtendedKernel()
	{
		//<start.php>

		$this->checkAgents();

		$this->initSession();

		$event = new Event("main", "OnPageStart");
		$event->send();
	}

	/**
	 * Initializes application shell. Called after initializeKernel.
	 *
	 * @param System\IApplicationStrategy $initStrategy
	 */
	protected function initializeShell(\Bitrix\Main\System\IApplicationStrategy $initStrategy = null)
	{
		$this->authenticateUser();
		if ($initStrategy != null)
			$initStrategy->authenticateUser();

		// define("BX_STARTED", true); // required for iblock to define site

		//magic parameters: show page creation time
		if($_GET["show_page_exec_time"]=="Y" || $_GET["show_page_exec_time"]=="N")
			$_SESSION["SESS_SHOW_TIME_EXEC"] = $_GET["show_page_exec_time"];

		//magic parameters: show included file processing time
		if($_GET["show_include_exec_time"]=="Y" || $_GET["show_include_exec_time"]=="N")
			$_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"] = $_GET["show_include_exec_time"];

		//magic parameters: show include areas
		if(isset($_GET["bitrix_include_areas"]) && $_GET["bitrix_include_areas"] <> "")
			$GLOBALS["APPLICATION"]->setShowIncludeAreas($_GET["bitrix_include_areas"]=="Y");

		//magic sound
		/** @var $context HttpContext */
		$context = $this->context;
		$user = $context->getUser();
		/** @var $request HttpRequest */
		$request = $context->getRequest();
		if ($user->isAuthenticated() && ($request->getCookie("SOUND_LOGIN_PLAYED") == null))
		{
			/** @var $response HttpResponse */
			$response = $context->getResponse();
			$response->addCookie(new \Bitrix\Main\Web\Cookie('SOUND_LOGIN_PLAYED', 'Y', 0));
		}

		$event = new Event("main", "OnBeforeProlog");
		$event->send();

		$this->authorizeUser();
		if ($initStrategy != null)
			$initStrategy->authorizeUser();
	}

	protected function authorizeUser()
	{
		if ((!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS !== true) && (!defined(
			"NOT_CHECK_FILE_PERMISSIONS"
		) || NOT_CHECK_FILE_PERMISSIONS !== true)
		)
		{
////////////////////////////////////
//  $this->page->authorize();
			$arAuthResult = $GLOBALS["APPLICATION"]->arAuthResult;

			$real_path = $GLOBALS["APPLICATION"]->getCurPage(true);
			if (isset($_SERVER["REAL_FILE_PATH"]) && $_SERVER["REAL_FILE_PATH"] != "")
				$real_path = $_SERVER["REAL_FILE_PATH"];

			if (!$GLOBALS["USER"]->canDoFileOperation('fm_view_file', array(SITE_ID, $real_path)) || (defined(
				"NEED_AUTH"
			) && NEED_AUTH && !$GLOBALS["USER"]->isAuthorized())
			)
			{
				if ($GLOBALS["USER"]->isAuthorized() && strlen($arAuthResult["MESSAGE"]) <= 0)
					$arAuthResult = array(
						"MESSAGE" => GetMessage("ACCESS_DENIED") . ' ' . GetMessage(
							"ACCESS_DENIED_FILE", array("#FILE#" => $real_path)
						), "TYPE" => "ERROR"
					);

				if (defined("ADMIN_SECTION") && ADMIN_SECTION == true)
				{
					if ($_REQUEST["mode"] == "list" || $_REQUEST["mode"] == "settings")
					{
						echo "<script>top.location='" . $GLOBALS["APPLICATION"]->getCurPage() . "?" . deleteParam(
							array("mode")
						) . "';</script>";
						die();
					}
					elseif ($_REQUEST["mode"] == "frame")
					{
						echo "<script type=\"text/javascript\">
					var w = (opener? opener.window:parent.window);
					w.location.href='" . $GLOBALS["APPLICATION"]->getCurPage() . "?" . deleteParam(array("mode")) . "';
				</script>";
						die();
					}
				}

				/** @var $request HttpRequest */
				$request = $this->context->getRequest();
				//LocalRedirect("/auth_new.php?back_url=".urlencode($request->getRequestedPage()));
				$GLOBALS["APPLICATION"]->authForm($arAuthResult);
			}
		}
	}

	protected function checkAgents()
	{
		if(\COption::getOptionString("main", "check_agents", "Y")=="Y")
		{
			// define("START_EXEC_AGENTS_1", microtime());
			$GLOBALS["BX_STATE"] = "AG";
			$GLOBALS["DB"]->startUsingMasterOnly();
			\CAgent::checkAgents();
			$GLOBALS["DB"]->stopUsingMasterOnly();
			// define("START_EXEC_AGENTS_2", microtime());
			$GLOBALS["BX_STATE"] = "PB";
		}
	}

	protected function initSession()
	{
		if($domain = $GLOBALS["APPLICATION"]->getCookieDomain())
			ini_set("session.cookie_domain", $domain);

		if(\COption::getOptionString("security", "session", "N") === "Y" && \CModule::includeModule("security"))
			\CSecuritySession::init();

		//diagnostic for spaces in init.php etc.
		//message is shown in the admin section
		$GLOBALS["aHeadersInfo"] = array();
		if(headers_sent($hs_file, $hs_line))
			$GLOBALS["aHeadersInfo"] = array("file"=>$hs_file, "line"=>$hs_line);

		session_start();
	}

	protected function authenticateUser()
	{
		$user = Security\Authentication::getUserBySession();

		if (!is_null($user) && !Security\Authentication::checkSessionSecurity($user))
			$user = null;

		if (is_null($user))
			$user = Security\Authentication::getUserByCookie();

		if (is_null($user))
			$user = new Security\CurrentUser();

		/** @var $context HttpContext */
		$context = $this->context;
		$context->setUser($user);

		Security\Authentication::copyToSession($user);
	}

	/**
	 * Starts request execution. Should be called after initialize.
	 */
	static public function start()
	{
		if (!$this->page)
			$this->page = $this->getPageByFactory();

		$this->exceptionHandler->setHandlerOutput($this->page);

		register_shutdown_function(array($this, "finish"));

		$this->page->startRequest();
	}

	/**
	 * Finishes request execution.
	 * It is registered in start() and called automatically on script shutdown.
	 */
	static public function finish()
	{
		$buffer = $this->page->render();

		$response = $this->context->getResponse();
		$response->flush($buffer);

		$this->managedCache->finalize();
	}

	/**
	 * Page factory. Should return object of appropriate Page subclass.
	 *
	 * @return Page
	 */
	protected function getPageByFactory()
	{
		//$request = $this->getRequest();

		//if ($request->isAdminSection())
		//	return new CAdminPage();
		//elseif ($request->isMobile())
		//	return new CMobilePage();
		//elseif ($request["light"] == "Y")
		//	return new LightPage();

		return new PublicPage($this);
	}

	/**
	 * Sets page of current request.
	 *
	 * @param Page $page
	 */
	static public function setPage(Page $page)
	{
		$page->setApplication($this);
		$this->page = $page;
	}

	/**
	 * Returns page of current request.
	 *
	 * @return Page
	 */
	static public function getPage()
	{
		return $this->page;
	}
}
