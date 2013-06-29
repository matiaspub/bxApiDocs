<?

class CWSDLCreator
{
	var $typensXSDType = array();
	var $typensVars = array();
	var $typensDefined = array();

	var $typens = array();
	var $typeTypens = array();

	var $WSDL;
	var $WSDLXML;

	var $messages = array();
	var $portTypes = array();
	var $bindings = array();
	var $services = array();
	var $paramsNames = array();

	var $XMLCreator;

	var $serviceName;
	var $serviceUrl;
	var $targetNamespace;
	var $classes = array();

	public function CWSDLCreator($serviceName, $serviceUrl = "", $targetNamespace = "")
	{
		global $APPLICATION;
		$serviceName = str_replace(" ", "_", $serviceName);
		if (!$serviceUrl) $serviceUrl = "http://".$_SERVER["HTTP_HOST"].$APPLICATION->GetCurPage();
		if (!$targetNamespace) $targetNamespace = "http://".$_SERVER["HTTP_HOST"]."/";

		$this->WSDLXML = new CXMLCreator("wsdl:definitions");
		$this->WSDLXML->setAttribute("name", $serviceName);
		$this->WSDLXML->setAttribute("targetNamespace", $targetNamespace);
		$this->WSDLXML->setAttribute("xmlns:tns",$targetNamespace);
		$this->WSDLXML->setAttribute("xmlns:http","http://schemas.xmlsoap.org/wsdl/http/");
		$this->WSDLXML->setAttribute("xmlns:mime","http://schemas.xmlsoap.org/wsdl/mime/");
		$this->WSDLXML->setAttribute("xmlns:tm","http://microsoft.com/wsdl/mime/textMatching/");
		$this->WSDLXML->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
		$this->WSDLXML->setAttribute("xmlns:soap", "http://schemas.xmlsoap.org/wsdl/soap/");
		$this->WSDLXML->setAttribute("xmlns:soap12", "http://schemas.xmlsoap.org/wsdl/soap12/");
		$this->WSDLXML->setAttribute("xmlns:soapenc", "http://schemas.xmlsoap.org/soap/encoding/");
		$this->WSDLXML->setAttribute("xmlns:wsdl", "http://schemas.xmlsoap.org/wsdl/");
		//$this->WSDLXML->setAttribute("xmlns", "http://schemas.xmlsoap.org/wsdl/");
		$this->serviceName = $serviceName;
		$this->serviceUrl = $serviceUrl;
		$this->targetNamespace = $targetNamespace;
	}

	public function setClasses($classes)
	{
		$this->classes = $classes;
	}

	public function AddComplexDataType($name, $vars)
	{
		global $xsd_simple_type;
		if (isset($this->typensVars[$name]))
			return true;

		if (!count($vars)) return false;

		$this->typensDefined[$name] = $name;
		$this->typensXSDType[$name] = "type";

		foreach ($vars as $pname => $param)
		{
			if (!is_array($param) or !isset($param["varType"])) continue;
			$this->typensVars[$name][$pname] = $param;
			if (!isset($xsd_simple_type[$param["varType"]]))
			{
				if (isset($param["arrType"]))
					$this->AddArrayType($pname, $param);
				else
					$this->AddComplexDataType($pname, $param);
			}
		}

		return true;
	}

	public function AddArrayType($pname, $param)
	{
		if (isset($param["varType"])
			and isset($this->typensVars[$param["varType"]]))
			return true;

		if (isset($param["arrType"]))
		{
			$arrType = $param["arrType"];

			$maxOccurs = "unbounded";
			if (isset($param["maxOccursA"])) $maxOccurs = $param["maxOccursA"];

			$this->typensXSDType[$param["varType"]] = "type";
			$this->typensDefined[$param["varType"]] = $param["varType"];
			$this->typensVars[$param["varType"]] = array(
				$param["varType"]."El" =>
					array(
						"varType" => $param["arrType"],
						"maxOccurs" => $maxOccurs)
			);

			if (isset($param["nillableA"]))
				$this->typensVars[$param["varType"]][$param["varType"]."El"]["nillable"] =
					$param["nillableA"];

			return true;
		}

		return false;
	}

	public function __createMessage ($name, $returnType = false, $params = array())
	{
		global $xsd_simple_type;
		$insoap = array();
		$outsoap = array();
		$message = new CXMLCreator("wsdl:message");
		$message->setAttribute("name", $name."SoapIn");
		$part = new CXMLCreator("wsdl:part");
		$part->setAttribute("name", "parameters");
		$part->setAttribute("element", "tns:".$name/*."Request"*/);
		$message->addChild($part);

		if (is_array($params)) {
			foreach ($params as $pname=>$param) {
				$type = isset($param["varType"]) ? $param["varType"]:"anyType";
				if (isset($xsd_simple_type[$type])) {
					$insoap[$pname] = $xsd_simple_type[$type];
					$type = "xsd:".$xsd_simple_type[$type];
				} else {
					$this->AddArrayType($pname, $param);
					$insoap[$pname] = $param["varType"];
					$type = "tns".":".$param["varType"];
				}
			}
		}
		$this->messages[] = $message;
		if ($returnType) {
			//foreach ($returnType as $pname=>$param) break;
			$message = new CXMLCreator("wsdl:message");
			$message->setAttribute("name", $name."SoapOut");
			$part = new CXMLCreator("wsdl:part");
			$part->setAttribute("name", "parameters");
			$part->setAttribute("element", "tns:".$name."Response");
			$message->addChild($part);

			//changed by Sigurd
			if (is_array($params))
			{
				foreach ($returnType as $pname=>$param)
				{
					$type = isset($param["varType"]) ? $param["varType"]:"anyType";
					if (isset($xsd_simple_type[$type])) {
						$outsoap[$pname] = $xsd_simple_type[$type];
						$type = "xsd:".$xsd_simple_type[$type];
					} else {
						if (isset($this->typeTypens[$type])) {
							$type = $this->typeTypens[$type].":".$type;
						} else {
							$this->AddArrayType($pname, $param);
							$outsoap[$pname] = $param["varType"];
							$type = "tns".":".$param["varType"];
						}
					}
				}
			}

			$this->messages[] = $message;
		} else {
			$message = new CXMLCreator("message");
			$message->setAttribute("name", $name."Response");
			$this->messages[] = $message;
		}

		$this->typensDefined[$name/*."Request"*/] = $name/*."Request"*/;
		$this->typensDefined[$name."Response"] = $name."Response";
		$this->typensVars[$name/*."Request"*/] = $insoap;
		$this->typensVars[$name."Response"] = $outsoap;
	}

	public function __createPortType ($portTypes)
	{
		if (is_array($portTypes)) {
			foreach ($portTypes as $class=>$methods) {
				$pt = new CXMLCreator("wsdl:portType");
				$pt->setAttribute("name", $class."Interface");
				foreach ($methods as $method=>$components) {
					$op = new CXMLCreator("wsdl:operation");
					$op->setAttribute("name", $method);

					$input = new CXMLCreator("wsdl:input");
					$input->setAttribute("message", "tns:".$method."SoapIn");
					$op->addChild($input);

					$output = new CXMLCreator("wsdl:output");
					$output->setAttribute("message", "tns:".$method."SoapOut");
					$op->addChild($output);

					if ($components["documentation"]) {
						$doc = new CXMLCreator("wsdl:documentation");
						$doc->setData($components["documentation"]);
						$op->addChild($doc);
					}

					$pt->addChild($op);
				}
				$this->portTypes[] = $pt;
			}
		}
	}

	public function __createBinding ($bindings)
	{
		if (is_array($bindings)) {
			$b = new CXMLCreator("wsdl:binding");
			foreach ($bindings as $class=>$methods) {
				$b->setAttribute("name", $class."Binding");
				$b->setAttribute("type", "tns:".$class."Interface");
				$s = new CXMLCreator("soap:binding");
				$s->setAttribute("transport", "http://schemas.xmlsoap.org/soap/http");
				$b->addChild($s);
				foreach ($methods as $method=>$components) {
					$op = new CXMLCreator("wsdl:operation");
					$op->setAttribute("name", $method);
					$s = new CXMLCreator("soap:operation");
					$s->setAttribute("soapAction", $this->targetNamespace.$method);
					$s->setAttribute("style", "document");
					$op->addChild($s);

					$input = new CXMLCreator("wsdl:input");
					$s = new CXMLCreator("soap:body");
					$s->setAttribute("use", "literal");

					$input->addChild($s);
					$op->addChild($input);

					$output = new CXMLCreator("wsdl:output");
					$output->addChild($s);
					$op->addChild($output);
					$b->addChild($op);
				}
				$this->bindings[] = $b;
			}
		}
	}

	public function __createService ($services)
	{
		if (is_array($services)) {
			foreach ($services as $class=>$methods) {
				$port = new CXMLCreator("wsdl:port");
				$port->setAttribute("name", $class."Soap");
				$port->setAttribute("binding", "tns:".$class."Binding");
				$soap = new CXMLCreator("soap:address");
				$soap->setAttribute("location", $this->serviceUrl);
				$port->addChild($soap);
				$this->services[] = $port;
			}
		}
	}

	public function createWSDL ()
	{
		global $xsd_simple_type;
		if (!$this->classes or !count($this->classes)) return 0;

		foreach ($this->classes as $class=>$methods) {
			$pbs = array();
			ksort($methods);
			foreach ($methods as $method=>$components)
			{
				if ($components["type"] == "public") {
					$this->__createMessage($method, $components["output"], $components["input"]);

					$pbs[$class][$method]["documentation"] = $components["description"];
					$pbs[$class][$method]["input"] = $components["input"];
					$pbs[$class][$method]["output"] = $components["output"];
				}
			}
			$this->__createPortType($pbs);
			$this->__createBinding($pbs);
			$this->__createService($pbs);
			//AddMessage2Log(mydump($this->portTypes));
		}

		//echo '<pre>'; print_r($this); echo '</pre>';


		// add types
		if (is_array($this->typensDefined) && count($this->typensDefined) > 0) {
			$types = new CXMLCreator("wsdl:types");
			$xsdSchema = new CXMLCreator("xsd:schema");
			$xsdSchema->setAttribute("elementFormDefault", "qualified");
			$xsdSchema->setAttribute("targetNamespace", $this->targetNamespace);
			foreach ($this->typensDefined as $typensDefined) {
				$xsdtype = "element";
				if (isset($this->typensXSDType[$typensDefined])) $xsdtype = "type";

				if ($xsdtype == "element") {
					$elroot = new CXMLCreator("xsd:element");
					$elroot->setAttribute("name", $typensDefined);
				}
				$complexType = new CXMLCreator("xsd:complexType");
				if ($xsdtype == "type")
					$complexType->setAttribute("name", $typensDefined);

				$all = new CXMLCreator("xsd:sequence");
				if (isset($this->typensVars[$typensDefined])
					and is_array($this->typensVars[$typensDefined])) {

					//commented by Sigurd;

					//ksort($this->typensVars[$typensDefined]);
					foreach ($this->typensVars[$typensDefined] as $varName=>$varType) {

						// check minOccurs|maxOccurs here!
						
						$element = new CXMLCreator("xsd:element");
						$element->setAttribute("minOccurs", 0);

						if (is_array($varType) and isset($varType["maxOccurs"]))
							$element->setAttribute("maxOccurs", $varType["maxOccurs"]);
						else
							$element->setAttribute("maxOccurs", 1);

						if (is_array($varType) and isset($varType["nillable"]))
							$element->setAttribute("nillable", $varType["nillable"]);

						$element->setAttribute("name", $varName);

						if (is_array($varType)) $varType = $varType["varType"];

						if ($varType == 'any')
						{
							$any = new CXMLCreator('xsd:any');
							$sequence = new CXMLCreator('xsd:sequence');
							$sequence->addChild($any);
							$element->addChild($sequence);
							$complexType->setAttribute('mixed', "true");
						}
						else
						{
							$varType = isset($xsd_simple_type[$varType]) ? "xsd:".$xsd_simple_type[$varType] : "tns:".$varType;
							$element->setAttribute("type", $varType);
						}
						
						$all->addChild($element);
					}
				}

				$complexType->addChild($all);

				if ($xsdtype == "element") {
					$elroot->addChild($complexType);
					$xsdSchema->addChild($elroot);
				} else {
					$xsdSchema->addChild($complexType);
				}
			}
			$types->addChild($xsdSchema);
			$this->WSDLXML->addChild($types);
		}

		// adding messages
		foreach ($this->messages as $message) {
			$this->WSDLXML->addChild($message);
		}

		// adding port types
		foreach ($this->portTypes as $portType) {
			$this->WSDLXML->addChild($portType);
		}

		// adding bindings
		foreach ($this->bindings as $binding) {
			$this->WSDLXML->addChild($binding);
		}

		// adding services
		$s = new CXMLCreator("wsdl:service");
		$s->setAttribute("name", $this->serviceName);
		foreach ($this->services as $service) {
			$s->addChild($service);
		}
		$this->WSDLXML->addChild($s);

		$this->WSDL  = "<?xml version='1.0' encoding='UTF-8'?>\n";
		//$this->WSDL .= "<!-- WSDL file generated by BITRIX WSDLCreator (http://www.bitrix.ru) -->\n";
		$this->WSDL .= $this->WSDLXML->getXML();

	}

	public function getWSDL()
	{
		return $this->WSDL;
	}

	public function printWSDL()
	{
		print $this->WSDL;
	}

	public function saveWSDL ($targetFile, $overwrite = true)
	{
		if (file_exists($targetFile) && $overwrite == false) {
			$this->downloadWSDL();
		} elseif ($targetFile) {
			$fh = fopen($targetFile, "w+");
			fwrite($fh, $this->getWSDL());
			fclose($fh);
		}
	}

	public function downloadWSDL ()
	{
		session_cache_limiter();
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=".$this->name.".wsdl");
		header("Accept-Ranges: bytes");
		header("Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($this->WSDL, 'latin1') : strlen($this->WSDL)) );
		$this->printWSDL();
		die();
	}
}


?>