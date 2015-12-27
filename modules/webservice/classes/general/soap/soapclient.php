<?php


/**
 * 
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * <buttononclick>
 * function TestComponent() 
 * {
 * 	global $APPLICATION;
 * 	$client = new CSOAPClient( "bitrix.soap", $APPLICATION-&gt;GetCurPage() );
 * 	$request = new CSOAPRequest( "wsTestStartOut1", "http://bitrix.soap/" );
 * 	$request-&gt;addParameter("str1", "qwe");
 * 	$request-&gt;addParameter("str2", "fjdfhgfdh");
 * 	$request-&gt;addParameter("int3", "123");
 * 	$response = $client-&gt;send( $request );
 * 	echo "Call wsTestStartOut1";
 * 	if ( $response-&gt;isFault() )
 * 	{
 * 	    print( "SOAP fault: " . $response-&gt;faultCode(). " - " . $response-&gt;faultString() . "" );
 * 	}
 * 	else
 * 	    echo "[OK]: ".mydump($response-&gt;Value);		
 * }</buttononclick><buttononclick>
 * function TestComponent() 
 * {
 * 	$client = new CSOAPClient("ws.strikeiron.com", "/relauto/iplookup/DNS");
 * 	$request = new CSOAPRequest( "DNSLookup", "http://tempuri.org/");
 * 	$request-&gt;addSOAPHeader( "LicenseInfo xmlns=\"http://ws.strikeiron.com\"",
 * 			array("UnregisteredUser" =&gt; array( "EmailAddress" =&gt; "qwerty@mail.ru" ))
 * 		);
 * 	$request-&gt;addParameter("server", "www.yandex.ru");
 * 	$response = $client-&gt;send( $request );
 * 		
 * 	echo "SOAPRequest: ".htmlspecialchars($client-&gt;getRawRequest());
 * 	echo "SOAPResponse: ".htmlspecialchars($client-&gt;getRawResponse());
 * }</buttononclick>
 * CModule::IncludeModule('webservice');
 * 
 * $client = new CSOAPClient("192.168.1.1", '/path_to_webservice/');
 * $request = new CSOAPRequest("myMethod", "http://some-namespace/");
 * 
 * $client-&gt;setLogin('my_login');
 * $client-&gt;setPassword('my_password');
 * 
 * $request-&gt;addParameter("myMethodParam", "xxxxx");
 * 
 * $response = $client-&gt;send($request);
 *  
 * if ( $response-&gt;isFault() ) 
 * { 
 *    print( "SOAP fault: " . $response-&gt;faultCode(). " - " . $response-&gt;faultString() . "" ); 
 * } 
 * else 
 * { 
 *      echo "[OK]: ".print_r($response-&gt;Value, 1);    
 * }
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoapclient/index.php
 * @author Bitrix
 */
class CSOAPClient
{
    /// The name or IP of the server to communicate with
    var $Server;
    /// The path to the SOAP server
    var $Path;
    /// The port of the server to communicate with.
    var $Port;
    /// How long to wait for the call.
    var $Timeout = 0;
    /// HTTP login for HTTP authentification
    var $Login;
    /// HTTP password for HTTP authentification
    var $Password;
    
    var $SOAPRawRequest;
    var $SOAPRawResponse;
    
    public function CSOAPClient( $server, $path = '/', $port = 80 )
    {
        $this->Login = "";
        $this->Password = "";
        $this->Server = $server;
        $this->Path = $path;
        $this->Port = $port;
        if ( is_numeric( $port ) )
            $this->Port = $port;
        elseif( strtolower( $port ) == 'ssl' )
            $this->Port = 443;
        else
            $this->Port = 80;
    }

    /*!
      Sends a SOAP message and returns the response object.
    */
    public function send( $request )
    {
    	if ( $this->Port == 443)
    	{
			$this->ErrorString = "<b>Error:</b> CSOAPClient::send() : SSL port on request server no supported by current impl. of SOAPClient.";
			return 0;
    	}
    	
        if ( $this->Port != 443 )
        {
            if ( $this->Timeout != 0 )
            {
                $fp = fsockopen( $this->Server,
                                 $this->Port,
                                 $this->errorNumber,
                                 $this->errorString,
                                 $this->Timeout );
            }
            else
            {
                $fp = fsockopen( $this->Server,
                                 $this->Port,
                                 $this->errorNumber,
                                 $this->errorString );
            }

            if ( $fp == 0 )
            {
                $this->ErrorString = '<b>Error:</b> CSOAPClient::send() : Unable to open connection to ' . $this->Server . '.';
                return 0;
            }

            $payload = $request->payload();

            $authentification = "";
            if ( ( $this->login() != "" ) )
            {
                $authentification = "Authorization: Basic " . base64_encode( $this->login() . ":" . $this->password() ) . "\r\n" ;
            }
            
            $name = $request->name();
            $namespace = $request->get_namespace();
            if ($namespace[strlen($namespace)-1] != "/")
            	$namespace .= "/";            

            $HTTPRequest = "POST " . $this->Path . " HTTP/1.0\r\n" .
                "User-Agent: BITRIX SOAP Client\r\n" .
                "Host: " . $this->Server . "\r\n" .
                $authentification .
                "Content-Type: text/xml; charset=utf-8\r\n" .
                "SOAPAction: \"" . $request->get_namespace() . $request->name() . "\"\r\n" .
                "Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload))  . "\r\n\r\n" .
                $payload;
			
			$this->SOAPRawRequest = $HTTPRequest;
            if ( !fwrite( $fp, $HTTPRequest /*, strlen( $HTTPRequest )*/ ) )
            {
                $this->ErrorString = "<b>Error:</b> could not send the SOAP request. Could not write to the socket.";
                $response = 0;
                return $response;
            }

            $rawResponse = "";
            // fetch the SOAP response
            while ( $data = fread( $fp, 32768 ) )
            {
                $rawResponse .= $data;
            }

            // close the socket
            fclose( $fp );
        }
        
		$this->SOAPRawResponse = $rawResponse;
        $response = new CSOAPResponse();
        $response->decodeStream( $request, $rawResponse );
        return $response;
    }

    public function setTimeout( $timeout )
    {
        $this->Timeout = $timeout;
    }

    public function setLogin( $login  )
    {
        $this->Login = $login;
    }
    
    public function getRawRequest()
    {
    	return $this->SOAPRawRequest;
    }
    
    public function getRawResponse()
    {
    	return $this->SOAPRawResponse;
    }

    public function login()
    {
        return $this->Login;
    }

    public function setPassword( $password  )
    {
        $this->Password = $password;
    }

    public function password()
    {
        return $this->Password;
    }
}

?>
