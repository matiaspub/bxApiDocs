<?
global $DB, $MESS, $APPLICATION;

// define('WS_SP_SERVICE_PATH', '/_vti_bin/lists.asmx');
// define('WS_SP_SERVICE_NS', 'http://schemas.microsoft.com/sharepoint/soap/');

$GLOBALS["xsd_simple_type"] = array(
	"string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
	"int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
	"base64"=>"base64Binary", "base64Binary"=>"base64Binary",
	"any"=>"any",
);

CModule::AddAutoloadClasses(
	"webservice",
	array(
		"CXMLCreator" => "classes/general/xmlcreator.php",
		
		"CSOAPHeader" => "classes/general/soap/soapbase.php",
		"CSOAPBody" => "classes/general/soap/soapbase.php",
		"CSOAPEnvelope" => "classes/general/soap/soapbase.php",
		"CSOAPParameter" => "classes/general/soap/soapbase.php",
		"CSOAPFault" => "classes/general/soap/soapbase.php",
		
		"CSOAPCodec" => "classes/general/soap/soapcodec.php",
		"CSOAPRequest" => "classes/general/soap/soaprequest.php",
		"CSOAPResponse" => "classes/general/soap/soapresponse.php",
		"CSOAPClient" => "classes/general/soap/soapclient.php",
		
		"CSOAPServerResponser" => "classes/general/soap/soapserver.php",
		"CWSSOAPResponser" => "classes/general/soap/soapserver.php",
		"CSOAPServer" => "classes/general/soap/soapserver.php",
		
		"CWSDLCreator" => "classes/general/wsdl/wsdlcreator.php",
		
		"CWebServiceDesc" => "classes/general/webservice.php",
		"IWebService" => "classes/general/webservice.php",
		"CWebService" => "classes/general/webservice.php",
		
		"CSPListsClient" => "classes/general/sharepoint/client.php",
	)
);

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");

//require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/classes/general/webservice.wsdl.phpt");
//require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webservice/classes/general/msoffice.research.phpt");
IncludeModuleLangFile(__FILE__);

?>