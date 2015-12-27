<?php

// define("BX_SOAP_ENV", "http://schemas.xmlsoap.org/soap/envelope/");
// define("BX_SOAP_ENC", "http://schemas.xmlsoap.org/soap/encoding/");
// define("BX_SOAP_SCHEMA_INSTANCE", "http://www.w3.org/2001/XMLSchema-instance");
// define("BX_SOAP_SCHEMA_DATA", "http://www.w3.org/2001/XMLSchema");

// define("BX_SOAP_ENV_PREFIX", "SOAP-ENV");
// define("BX_SOAP_ENC_PREFIX", "SOAP-ENC");
// define("BX_SOAP_XSI_PREFIX", "xsi");
// define("BX_SOAP_XSD_PREFIX", "xsd");

// define("BX_SOAP_INT", 1);
// define("BX_SOAP_STRING", 2);

class CSOAPHeader 
{
	var $Headers = array ();

	function CSOAPHeader() 
	{

	}

	public static function addHeader() 
	{

	}
}

class CSOAPBody 
{
	public static function CSOAPBody() 
	{

	}
}

class CSOAPEnvelope 
{
	var $Header;
	var $Body;

	public function CSOAPEnvelope() 
	{
		$this->Header = new CSOAPHeader();
		$this->Body = new CSOAPBody();
	}
}

class CSOAPParameter
{
    var $Name;
    var $Value;
    
    public function CSOAPParameter( $name, $value)
    {
        $this->Name = $name;
        $this->Value = $value;
    }

    public function setName( $name )
    {
        $this->Name = $name;
    }

    public function name()
    {
        return $this->Name;
    }

    public static function setValue( $value )
    {

    }
    public function value()
    {
        return $this->Value;
    }
}


/**
 * 
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * <buttononclick><code>// Часть кода обработчика веб-сервисов SOAP сервера
 * $this-&gt;ShowResponse( $cserver, $functionName, $namespaceURI,
 * 	new CSOAPFault( 
 * 		'Server Error',
 * 		'Method not found' 
 * 	) );</code></buttononclick>
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/csoapfault/index.php
 * @author Bitrix
 */
class CSOAPFault 
{
	var $FaultCode;
	var $FaultString;
	var $detail;
	
	public function CSOAPFault($faultCode = "", $faultString = "", $detail = '') {
		$this->FaultCode = $faultCode;
		$this->FaultString = $faultString;
		$this->detail = $detail;
	}

	public function faultCode() {
		return $this->FaultCode;
	}

	public function faultString() {
		return $this->FaultString;
	}

	public function detail() {
		return $this->detail;
	}
}

?>
