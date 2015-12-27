<?php


/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * <buttononclick>
 * class CMSSOAPResearch extends CSOAPServerResponser
 * {
 *     var $provider_id;    
 *     var $service_id;
 *     var $add_tittle;
 *     var $query_path;
 *     var $registration_path;
 * 
 *     function OnBeforeRequest(&amp;$cserver) 
 *     {
 *         AddMessage2Log(mydump($cserver-&gt;GetRequestData()));    
 *     }
 * 
 *     function ProcessRequestBody(&amp;$cserver, $body) 
 *     {
 *         $functionName = $body-&gt;name();
 *         $namespaceURI = $body-&gt;namespaceURI();
 *         $requestNode = $body;
 *         
 *         if ($functionName == "Registration")
 *         {
 *             $root = new CXMLCreator("RegistrationResponse");
 *             $root-&gt;setAttribute("xmlns", "urn:Microsoft.Search");
 *             
 *             $regres = new CXMLCreator("RegistrationResult");
 *             $root-&gt;addChild($regres);
 *                         
 *             $prup = new CXMLCreator("ProviderUpdate");
 *             $prup-&gt;setAttribute("xmlns", "urn:Microsoft.Search.Registration.Response");
 *             $prup-&gt;setAttribute("revision", "1");             
 *             $prup-&gt;setAttribute("build", "1");
 *             $regres-&gt;addChild($prup);    
 *             
 *             $stat = new CXMLCreator("Status");
 *             $stat-&gt;setData("SUCCESS");
 *             $prup-&gt;addChild($stat);
 *                         
 *             $providers = array(
 *                 
 *                 "Provider" =&gt; array (
 *                     "Message" =&gt; "Тестовая служба.",
 *                     "Id" =&gt; "{$this-&gt;provider_id}",
 *                     "Name" =&gt; "Тестовая служба. {$this-&gt;add_tittle}",
 *                     "QueryPath" =&gt; $this-&gt;query_path,
 *                     "RegistrationPath" =&gt; $this-&gt;registration_path,
 *                     "AboutPath" =&gt; "http://www.bitrix.ru/",
 *                     "Type" =&gt; "SOAP",
 *                     "Revision" =&gt; "1",
 *                     "Services" =&gt; array(
 *                         "Service" =&gt; array(
 *                             "Id" =&gt; "{$this-&gt;service_id}",
 *                             "Name" =&gt; "Тестовая служба. {$this-&gt;add_tittle}",
 *                             "Description" =&gt; "Тестовая служба для тестирования soap сервера.",
 *                             "Copyright" =&gt; "(c) Bitrix.",
 *                             "Display" =&gt; "On",
 *                             "Category" =&gt; "ECOMMERCE_GENERAL",
 *                             "Parental" =&gt; "Unsupported",
 *                         )
 *                     )                        
 *                 )                        
 *             
 *             );
 * 
 *             $providersEncoded = CXMLCreator::encodeValueLight("Providers", $providers);
 *             $prup-&gt;addChild($providersEncoded);        
 *             
 *             $cserver-&gt;ShowRawResponse($root, true);
 *             
 *             AddMessage2Log($cserver-&gt;GetResponseData());
 *             
 *             return true;
 *         }
 *         
 *         return false;
 *     }
 * }</buttononclick>
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoapserverresponser/index.php
 * @author Bitrix
 */
class CSOAPServerResponser
{
	public static function OnBeforeRequest(&$cserver) {} /* $cserver->RawPostData */
	public static function OnAfterResponse(&$cserver) {}
	
	/*
	 * If function returns true, chain of ProcessRequest ends.
	 * If function returns true, this means function already passed It's value to ShowResponse handler
	 * If returns false, then next item in a chain will be called.
	 * Result Value must be set to $cserver->ResponseValue 
	 */
	public static function ProcessRequestHeader(&$cserver, $header) {}	/* stub, never used */
	public static function ProcessRequestBody(&$cserver, $body) {} 
}

class CWSSOAPResponser extends CSOAPServerResponser
{
	/*
	 * typename => name => array()
	 * funcname => parameters => array()
	 * Array can contain:
	 * 	serialize => class/assoc array.
	 * 	varType, arrType 	
	 * */
	var $TypensVars;
	
	/// message => function
	var $MessageTags;
	
    /// Contains a list over registered functions
    var $FunctionList;
    
    public function RegisterFunction( $name, $params=array() )
    {
        $this->FunctionList[] = $name;
        $this->TypensVars[$name] = $params;
        
        if ($params["request"]) $this->MessageTags[$params["request"]] = $name;
        if ($params["response"]) $this->MessageTags[$params["response"]] = $name;
    }

	/*
	 * $complex = array( "typename" => array( paraname => array(type desc, valType)))
	 */
	public function RegisterComplexType($complex)
	{
		foreach ($complex as $complexTypeName => $declaration)
			$this->TypensVars[$complexTypeName] = $declaration;
	}
	
	public function ProcessRequestBody(&$cserver, $body) 
	{
		$functionName = $body->name();
		$namespaceURI = $body->namespaceURI();
		$requestNode = $body;
		
		// If this is request name in functionName, get functionName.
		if (!in_array( $functionName, $this->FunctionList )
			and isset($this->MessageTags[$functionName]))
		{
			$functionName = $this->MessageTags[$functionName];
		}
                        
		if (!in_array( $functionName, $this->FunctionList ))
		{
			CSOAPServer::ShowSOAPFault("Trying to access unregistered function: ".$functionName);
			return true;
		}
        
		$objectName = "";
		$params = array();
			
		$paramsDecoder = new CSOAPResponse($functionName, $namespaceURI);
		$paramsDecoder->setTypensVars($this->TypensVars); 
            
		if (!isset($this->TypensVars[$functionName]) or
          	!isset($this->TypensVars[$functionName]["myclassname"]) or
            !isset($this->TypensVars[$functionName]["input"]))
		{          
			CSOAPServer::ShowSOAPFault("Requested function has no type specified: ".$functionName);
			return true;
		}
            
		$objectName = $this->TypensVars[$functionName]["myclassname"];
		$inputParams = $this->TypensVars[$functionName]["input"];
            
		$httpAuth = "N"; 
		if (isset($this->TypensVars[$functionName]["httpauth"])) 
			$httpAuth = $this->TypensVars[$functionName]["httpauth"];
		if ($httpAuth == "Y"
			and !CWebService::MethodRequireHTTPAuth($objectName, $functionName))
		{
			CSOAPServer::ShowSOAPFault("Requested function requires HTTP Basic Auth to be done before.");
			return true;
		}
                        
		$requestParams = array(); // reorganize params
		foreach ( $requestNode->children() as $parameterNode )
		{          
			if (!$parameterNode->name()) continue;
				$requestParams[$parameterNode->name()] =
					$parameterNode;
		}
		
		// check parameters/decode // check strict params
		foreach ($inputParams as $pname => $param)
		{
			$decoded = null;
			
			if (isset($requestParams[$pname]))  
				$decoded = $paramsDecoder->decodeDataTypes( $requestParams[$pname] );
			if (is_object($decoded) and (get_class($decoded) == "CSOAPFault" or get_class($decoded) == "csoapfault"))
			{
				CSOAPServer::ShowSOAPFault($decoded);
				return true;
			}
			if (!isset($decoded) and (!isset($param["strict"]) or
				(isset($param["strict"]) and $param["strict"] == "strict") ))
			{
				CSOAPServer::ShowSOAPFault("Request has no enought params of strict type to be decoded. ");
            	return true;
			}
			$params[] = $decoded;
		}
            
		//AddMessage2Log(mydump($params));            
		
		unset($paramsDecoder);
            
		$object = null;
		
		if (class_exists($objectName)) 
			$object = new $objectName;
		
		if (is_object($object) && method_exists($object, $functionName))
		{
			$this->ShowResponse( 
				$cserver,
				$functionName, 
				$namespaceURI,
				call_user_func_array( 
					array($object, $functionName), 
					$params
				) 
			);
		}
		else if ( !class_exists( $objectName ) )
		{
			$this->ShowResponse( $cserver, $functionName, $namespaceURI,
													new CSOAPFault( 'Server Error',
															'Object not found' ) );
		}
		else
		{            	
			$this->ShowResponse( $cserver, $functionName, $namespaceURI,
													new CSOAPFault( 'Server Error',
															'Method not found' ) );
		}
		
		return true;
	}
	
	public function ShowResponse( &$cserver, $functionName, $namespaceURI, &$value )
    {
    	global $APPLICATION;
        // Convert input data to XML
        
        $response = new CSOAPResponse( $functionName, $namespaceURI );
        $response->setTypensVars($this->TypensVars);
		
        $response->setValue($value);

        $payload = $response->payload();

        header("SOAPServer: BITRIX SOAP");
        header("Content-Type: text/xml; charset=\"UTF-8\"");
        Header("Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload)));

        $APPLICATION->RestartBuffer();        
        $cserver->RawPayloadData = $payload;
        echo $payload;
    }
}


/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * В этом примере в <b>$arParams["SOAPSERVER_RESPONSER"]</b> содержаться объекты 
 * обработчики SOAP. 
 * 
 * Обычно SOAP-обработчик создаётся в компоненте, сохраняется в массив в 
 * <b>$arParams["SOAPSERVER_RESPONSER"]</b> и далее, когда включается компонент 
 * webservice.server, выполняется следующий код. 
 * 
 * 
 * // В компоненте обработчика
 * 
 * // Создаем экземпляр обработчика
 * $research =&amp; new CMSSOAPResearch();
 * 
 * // Конфигурируем обработчик
 * $research-&gt;provider_id = '{XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}';
 * $research-&gt;service_id = '{XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}';
 * $research-&gt;add_tittle = "";
 * $research-&gt;query_path = "http://{$_SERVER[HTTP_HOST]}/ws/wscauth.php";
 * $research-&gt;registration_path = "http://{$_SERVER[HTTP_HOST]}/ws/wscauth.php";
 * 
 * // Сохраняем для передачи в webservice.server
 * $arParams["SOAPSERVER_RESPONSER"] = array( &amp;$research );
 * 
 * .........
 * 
 * // В компоненте webservice.server
 * $server = new CSOAPServer();
 * 
 * for ($i = 0; $i&lt;count($arParams["SOAPSERVER_RESPONSER"]); $i++)
 * {
 *     $server-&gt;AddServerResponser($arParams["SOAPSERVER_RESPONSER"][$i]);
 * }
 * 
 * $result = $server-&gt;ProcessRequest();
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoapserver/index.php
 * @author Bitrix
 */
class CSOAPServer
{
	/// Contains the RAW HTTP post data information
    var $RawPostData;
    var $RawPayloadData;
    
    /// Consists of instances of CSOAPServerResponser
    var $OnRequestEvent = array();
    
    public function CSOAPServer()
    {
        global $HTTP_RAW_POST_DATA;
        $this->RawPostData = $HTTP_RAW_POST_DATA;
    }
    
    public function GetRequestData()
    {
    	return $this->RawPostData;
    }
    
    public function GetResponseData()
    {
    	return $this->RawPayloadData;
    }
    
    public function AddServerResponser(&$respobject)
    {
    	if (is_subclass_of($respobject, "CSOAPServerResponser"))
    	{
    		$this->OnRequestEvent[count($this->OnRequestEvent)] =& $respobject;
    		return true;  
    	}
    	
    	return false;
    }

	// $valueEncoded type of CXMLCreator
	public function ShowRawResponse( $valueEncoded, $wrapEnvelope = false )
    {
    	global $APPLICATION;

		if ($wrapEnvelope)
		{
			// $valueEncoded class of CXMLCreator
			$root = new CXMLCreator("soap:Envelope");
			$root->setAttribute("xmlns:soap", BX_SOAP_ENV);
	
	        // add the body
	        $body = new CXMLCreator( "soap:Body" );
	        
	        $body->addChild( $valueEncoded );
	        
	        $root->addChild( $body );
	        
	        $valueEncoded = $root;
		}
		
		$payload = CXMLCreator::getXMLHeader().$valueEncoded->getXML();

        header( "SOAPServer: BITRIX SOAP" );
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
        Header( "Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload))  );

        $APPLICATION->RestartBuffer();
        $this->RawPayloadData = $payload;
        
        echo $payload;
    }

    public function ShowResponse( $functionName, $namespaceURI, $valueName, &$value )
    {
    	global $APPLICATION;
        // Convert input data to XML
        
        $response = new CSOAPResponse( $functionName, $namespaceURI );
        $response->setValueName( $valueName );
        $response->setValue( $value );

        $payload = $response->payload();

        header( "SOAPServer: BITRIX SOAP" );
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
        Header( "Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload)) );

        $APPLICATION->RestartBuffer();
        
        $this->RawPayloadData = $payload;
        echo $payload;
    }
    
    /* static */ function ShowSOAPFault($errorString)
    {
    	global $APPLICATION;
    	$response = new CSOAPResponse( 'unknown_function_name', 'unknown_namespace_uri' );
    	if (is_object($errorString) and (get_class($errorString) == "CSOAPFault" or get_class($errorString) == "csoapfault"))
    		$response->setValue( $errorString /*CSOAPFault*/ );
    	else
    		$response->setValue( new CSOAPFault( 'Server Error', $errorString ) );
    	
    	$payload = $response->payload();
    	
    	header( "SOAPServer: BITRIX SOAP" );
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
        Header( "Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload))  );

        $APPLICATION->RestartBuffer();
        echo $payload;
    }

    /*!
      Processes the SOAP request and prints out the
      propper response.
    */
    public function ProcessRequest()
    {
        global $HTTP_SERVER_VARS, $APPLICATION;

        if ( $HTTP_SERVER_VARS["REQUEST_METHOD"] != "POST" or
        	!class_exists("CDataXML"))
        {
            $this->ShowSOAPFault( "Error: this web page does only understand POST methods. BitrixXMLParser. " );
        }

		for ($i = 0; $i < count($this->OnRequestEvent); $i++)
			$this->OnRequestEvent[$i]->OnBeforeRequest($this);
        
		//AddMessage2Log($this->RawPostData);
        $xmlData = $this->stripHTTPHeader( $this->RawPostData );
		$xmlData = $APPLICATION->ConvertCharset($xmlData, "UTF-8", SITE_CHARSET);
		
        $xml = new CDataXML();

		//AddMessage2Log($xmlData);
		if (!$xml->LoadString( $xmlData ))
		{
			$this->ShowSOAPFault( "Error: Can't parse request xml data. " );
		}
		
		$dom = $xml->GetTree();
		 
        // Check for non-parsing XML, to avoid call to non-object error.
        if ( !is_object( $dom ) )
        {
            $this->ShowSOAPFault("Bad XML");
        }

        // add namespace fetching on body
        // get the SOAP body
        $body = $dom->elementsByName( "Body" );

        $children = $body[0]->children();
	
        if ( count( $children ) == 1 )
        {
            $requestNode = $children[0];
            $requestParsed = false;
            
            // get target namespace for request
            // it often function request message. in wsdl gen. = function+"request"
            $functionName = $requestNode->name();
            $namespaceURI = $requestNode->namespaceURI();
            
			for ($i = 0; $i < count($this->OnRequestEvent); $i++)
            {
				if ($this->OnRequestEvent[$i]->ProcessRequestBody($this, $requestNode))
				{	
					$requestParsed = true;
					break;
				}
            }
            
            for ($i = 0; $i < count($this->OnRequestEvent); $i++)
				$this->OnRequestEvent[$i]->OnAfterResponse($this);
				
			if (!$requestParsed)
				$this->ShowSOAPFault('Unknown operation requested.' );
			
			return $requestParsed;
        }
        else
        {
            $this->ShowSOAPFault('"Body" element in the request has wrong number of children' );
        }
        
        return false;
    }
    
    public static function stripHTTPHeader( $data )
    {
        //$start = strpos( $data, "<"."?xml" );
        $start = strpos( $data, "\r\n\r\n" );
        return substr( $data, $start, strlen( $data ) - $start );
    }
}

?>
