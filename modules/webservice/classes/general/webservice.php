<?php

$wsdescs = array();
$wswraps = array();

$componentContext = array();


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/cwebservicedesc/index.php
 * @author Bitrix
 */
class CWebServiceDesc
{
	var $wsname;		// webservice name
	var $wsclassname;	// webservice class wrapper (i-face implementor) name
	var $wsdlauto;		// boolean, automatic generating wsdl

	var $wstargetns;	// target namespace
	var $wsendpoint;

	/*
	 * Return info about $reflect method, class, para,
	 * For method:
	 * 	classname=>method
	 *
	 * 	must contain array(
	 * 		"name"	=> string,
	 * 		"documentation" => "Description"
	 * 		"input" => array(..),
	 * 		"output" => array(..)
	 * 		)
	 * 	Input in method: list of para's.
	 * 		array(
	 * 			"name" => "type",
	 * 			...
	 * 		)
	 * 	Output in method:
	 * 		1) "simpleType" => "type"
	 * 		2) "complexType" => array[] of structModuleSoapRetData declared by output array.
	 * */
	var $classes;		// class methods for soap

	/*
	 * On next two, syntax same as for $classes, but:
	 *
	 */
	var $structTypes;	// complex assoc array data types.
	var $classTypes;	// complex class data struct. when soaped. must be unserialized to class.

	////////////////////// registrating
	var $_wsdlci;		// wsdlcreator instance class
	var $_soapsi;		// soap server instance class
}


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/iwebservice/index.php
 * @author Bitrix
 */
class IWebService
{
	// May be called by Event to collect CWebServiceDesc on configuring WS.Server
	
	/**
	* <p>Метод возвращает экземпляр класса <a href="http://dev.1c-bitrix.ru/api_help/webservice/classes/cwebservicedesc/index.php">CWebServiceDesc</a> - описателя веб-сервиса. Метод динамичный.</p> <br><br>
	*
	*
	* @return CWebServiceDesc 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/webservice/classes/iwebservice/getwebservicedesc.php
	* @author Bitrix
	*/
	public static function GetWebServiceDesc() {}

	//function TestComponent() {}

	/*
	 * Web Service methods must have ws prefix in there names and
	 * they have to be serviced by ReflectService function to generate
	 * valid wsdl code, binding and other.
	 * Example:
	 * 		wsGetUserInfo();
	 * */
}


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/cwebservice/index.php
 * @author Bitrix
 */
class CWebService
{
	public static function SetComponentContext($arParams)
	{
		if (is_array($arParams))
			$GLOBALS["componentContext"] = $arParams;
	}

	public static function GetComponentContext($arParams)
	{
		if (is_array($GLOBALS["componentContext"]))
			return $GLOBALS["componentContext"];

		return false;
	}

	public static function SOAPServerProcessRequest($wsname)
	{
		if (!isset($GLOBALS["wsdescs"][$wsname]) or
			!$GLOBALS["wsdescs"][$wsname] or
			!$GLOBALS["wsdescs"][$wsname]->_soapsi)
			return false;

		return $GLOBALS["wsdescs"][$wsname]->_soapsi->ProcessRequest();
	}

	
	/**
	* <p>Метод регистрирует веб-сервис. Если операция проведена успешно, возвращается <i>true</i>, иначе <i>false</i>. Метод динамичный.</p> <p>Если веб-сервис реализован через систему компонентов, то <b>RegisterWebService </b>вызывается автоматически в компоненте <b>webservice.server</b>. В этом случае <i>className = $arParams["WEBSERVICE_NAME"]</i>.</p>
	*
	*
	* @param string $className  Название класса веб-сервиса. реализующего интерфейс <b>IWebService</b>.
	*
	* @return boolean 
	*
	* <h4>Example</h4> 
	* <pre>
	* <buttononclick>
	* // В компоненте webservice.server
	* CWebService::RegisterWebService($arParams["WEBSERVICE_CLASS"]);
	* 
	* // В компоненте веб-сервиса
	* $arParams["WEBSERVICE_NAME"] = "bitrix.webservice.checkauth";
	* // Следующий параметр прямо передается в SOAPServerProcessRequest
	* $arParams["WEBSERVICE_CLASS"] = "CCheckAuthWS";
	* $arParams["WEBSERVICE_MODULE"] = "";
	* $APPLICATION-&gt;IncludeComponent(
	*     "bitrix:webservice.server",
	*     "",
	*     $arParams
	*     );</buttononclick>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/webservice/classes/cwebservice/registerwebservice.php
	* @author Bitrix
	*/
	public static function RegisterWebService($className /*IWebService implementor*/)
	{
		$ifce =& CWebService::GetInterface($className);
		if (!is_object($ifce)) return false;

		$wsHandler = $ifce->GetWebServiceDesc();
		if (!$wsHandler or
			isset($GLOBALS["wsdescs"][$wsHandler->wsname])
			or !$wsHandler->wsname
			or !$wsHandler->wsclassname
			or !$wsHandler->wstargetns
			or !$wsHandler->wsendpoint
			or !is_array($wsHandler->classes)
			or !is_array($wsHandler->structTypes)
			or !is_array($wsHandler->classTypes))
			return false;

		if (isset($GLOBALS["wsdescs"][$wsHandler->wsname]))
			return false;

		$wsHandler->_wsdlci = new CWSDLCreator(
			$wsHandler->wsname,
			$wsHandler->wsendpoint,
			$wsHandler->wstargetns);

		$wsHandler->_wsdlci->setClasses($wsHandler->classes);
		if (count($wsHandler->structTypes))
			foreach ($wsHandler->structTypes as $pname => $vars)
				$wsHandler->_wsdlci->AddComplexDataType($pname, $vars);
		if (count($wsHandler->classTypes))
			foreach ($wsHandler->classTypes as $pname => $vars)
				$wsHandler->_wsdlci->AddComplexDataType($pname, $vars);
		$wsHandler->_wsdlci->createWSDL();

		$wsHandler->_soapsi = new CSOAPServer();

		$soapr = new CWSSOAPResponser();

		foreach ($wsHandler->structTypes as $cTypeN => $desc)
		{
			$tdesc = $desc;
			$tdesc["serialize"] = "assoc";
			$soapr->RegisterComplexType(
				array($cTypeN => $tdesc)
			);
		}

		foreach ($wsHandler->classTypes as $cTypeN => $desc)
		{
			$tdesc = $desc;
			$tdesc["serialize"] = "class";
			$soapr->RegisterComplexType(
				array($cTypeN => $tdesc)
			);
		}

		foreach ($wsHandler->classes as $classws => $methods)
		foreach ($methods as $method => $param)
		{
			if (isset($param["httpauth"]))
				$httprequired = $param["httpauth"];
			if ($httprequired!="Y")
				$httprequired = "N";

			$input = array(); if (is_array($param["input"]))
				$input = $param["input"];
			$output = array(); if (is_array($param["output"]))
				$output = $param["output"];

			$soapr->RegisterFunction(
					$method,
					array(
						"input" => $input,
						"output" => $output,
						"myclassname" => $classws,
						"request" => $method,
						"response" => $method."Response",
						"httpauth" => $httprequired
					)
				);


		}

		$wsHandler->_soapsi->AddServerResponser($soapr);

		$GLOBALS["wsdescs"][$wsHandler->wsname] = &$wsHandler;
		$GLOBALS["wswraps"][$wsHandler->wsname] = &$ifce;

		return true;
	}

	public static function GetSOAPServerRequest($wsname)
	{
		if (isset($GLOBALS["wsdescs"][$wsname]) and
			$GLOBALS["wsdescs"][$wsname]->_soapsi)
		{
			return $GLOBALS["wsdescs"][$wsname]->_soapsi->GetRequestData();
		}
		return false;
	}

	public static function GetSOAPServerResponse($wsname)
	{
		if (isset($GLOBALS["wsdescs"][$wsname]))
		{
			return $GLOBALS["wsdescs"][$wsname]->_soapsi->GetResponseData();
		}
		return false;
	}

	public static function MethodRequireHTTPAuth($class, $method)
	{
		global $USER;

		if (!$USER->IsAuthorized())
			return $USER->RequiredHTTPAuthBasic("Bitrix.{$class}.{$method}");

		return true;
	}

	public static function TestComponent($wsname)
	{
		if (isset($GLOBALS["wsdescs"][$wsname]))
		{
			$ifce =& CWebService::GetInterface($GLOBALS["wsdescs"][$wsname]->wsclassname);
			if (!is_object($ifce)) return false;
			$ifce->TestComponent();
		}
		return false;
	}

	public static function GetWSDL($wsname)
	{
		if (!isset($GLOBALS["wsdescs"][$wsname]) or
			!$GLOBALS["wsdescs"][$wsname] or
			!$GLOBALS["wsdescs"][$wsname]->_wsdlci)
			return false;
		return $GLOBALS["wsdescs"][$wsname]->_wsdlci->getWSDL();
	}

	public static function GetDefaultEndpoint()
	{
		global $APPLICATION;
		return "http://".$_SERVER["HTTP_HOST"].
				$APPLICATION->GetCurPage();
	}

	public static function GetDefaultTargetNS()
	{
		return "http://".$_SERVER["HTTP_HOST"]."/";
	}

	function &GetWebServiceDeclaration($className)
	{
		if (isset($GLOBALS["wsdescs"][$className])) return $GLOBALS["wsdescs"][$className];
		$ifce =& CWebService::GetInterface($className);
		if (!is_object($ifce)) return false;
		return $ifce->GetWebServiceDesc();
	}

	function &GetInterface($className)
	{
		if (isset($GLOBALS["wswraps"][$className])) return $GLOBALS["wswraps"][$className];

		if (!class_exists($className)) return 0;
		//AddMessage2Log(mydump(class_exists($className, true)));
		$ifce = new $className;
		if (!is_subclass_of($ifce, "IWebService")) return 0;
		return $ifce;
	}

}

?>
