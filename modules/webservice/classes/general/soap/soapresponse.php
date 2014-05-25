<?php

class CSOAPResponse extends CSOAPEnvelope
{
	/// Contains the response value
    var $Value = false;
    var $ValueName = 0;
    /// Contains the response type
    var $Type = false;
    /// Contains fault string
    var $FaultString = false;
    /// Contains the fault code
    var $FaultCode = false;
    /// Contains true if the response was an fault
    var $IsFault = false;
    /// Contains the name of the response, i.e. function call name
    var $Name;
    /// Contains the target namespace for the response
    var $Namespace;

    var $typensVars = array();

    /// Contains the DOM document for the current SOAP response
    var $DOMDocument = false;

    public function CSOAPResponse( $name="", $namespace="" )
    {
        $this->Name = $name;
        $this->Namespace = $namespace;

        // call the parents constructor
        $this->CSOAPEnvelope();
    }

	//	Decodes the SOAP response stream
    public function decodeStream( $request, $stream )
    {
		global $APPLICATION;

    	$stream_cutted = $this->stripHTTPHeader( $stream );
        if ( !$stream_cutted or !class_exists("CDataXML"))
        {
            $APPLICATION->ThrowException("Error: BitrixXMLParser. "
				."Downloaded page: <br>". htmlspecialcharsEx($stream));
			return;
        }

        $stream = $stream_cutted;

        $xml = new CDataXML();

		$stream = $APPLICATION->ConvertCharset($stream, "UTF-8", SITE_CHARSET);
        if (!$xml->LoadString( $stream ))
		{
			$APPLICATION->ThrowException( "Error: Can't parse request xml data. ");
            return;
		}

		$dom = $xml->GetTree();
		$this->DOMDocument = $dom;
        if ( get_class( $dom ) == "CDataXMLDocument" || get_class( $dom ) == "cdataxmldocument")
        {
            // check for fault
            $response = $dom->elementsByName( 'Fault' );

            if ( count( $response ) == 1 )
            {
                $this->IsFault = 1;
                $faultStringArray = $dom->elementsByName( "faultstring" );
                $this->FaultString = $faultStringArray[0]->textContent();

                $faultCodeArray = $dom->elementsByName( "faultcode" );
                $this->FaultCode = $faultCodeArray[0]->textContent();
                return;
            }

            // get the response
            $body = $dom->elementsByName( "Body" );
            $body = $body[0];

            $response = $body->children();
			$response = $response[0];

			if ( get_class( $response ) == "CDataXMLNode" || get_class( $response ) == "cdataxmlnode")
			{
				/*Cut from the SOAP spec:
				The method response is viewed as a single struct containing an accessor
				for the return value and each [out] or [in/out] parameter.
				The first accessor is the return value followed by the parameters
				in the same order as in the method signature.

				Each parameter accessor has a name corresponding to the name
				of the parameter and type corresponding to the type of the parameter.
				The name of the return value accessor is not significant.
				Likewise, the name of the struct is not significant.
				However, a convention is to name it after the method name
				with the string "Response" appended.
				*/

				$responseAccessors = $response->children();
				//echo '<pre>'; print_r($responseAccessors); echo '</pre>';
				if ( count($responseAccessors) > 0 )
				{
					$this->Value = array();
					foreach ($responseAccessors as $arChild)
					{
						$value = $arChild->decodeDataTypes();
						$this->Value = array_merge($this->Value, $value);
					}
				}
			}
			else
			{
				$APPLICATION->ThrowException( "Could not understand type of class decoded" );
			}
        }
        else
        {
            $APPLICATION->ThrowException( "Could not process XML in response" );
        }
    }

	// Decodes a DOM node and returns the PHP datatype instance of it.
    public function decodeDataTypes( $node, $complexDataTypeName = "" )
    {
    	global $xsd_simple_type;
        $returnValue = false;

        $attr = $node->getAttribute("type");
		if ($attr and strlen($attr))
		{
			return new CSOAPFault("Server Error", "Server supports only document/literal binding.");
		}

		$rootDataName = $this->Name;
		if (strlen(trim($complexDataTypeName)))
			$rootDataName = trim($complexDataTypeName);

		if (!$rootDataName or !isset($this->typensVars[$rootDataName]))
		{
			return new CSOAPFault("Server Error", "decodeDataTypes() can't find function type declaration." );
		}

		$name = $node->name();
		$typeDeclaration = array();
		$dataType = "";

		/*
		 * Typen can be:
		 * 	1) Whole Complex Data Type
		 * 	2) Complex Data Type Part
		 * 	3) Input decl
		 * 	3) Output decl
		 */

		if (isset($this->typensVars[$name]))
			$typeDeclaration = $this->typensVars[$name];
		if (isset($this->typensVars[$rootDataName][$name]))
			$typeDeclaration = $this->typensVars[$rootDataName][$name];
		else if (isset($this->typensVars[$rootDataName]["input"][$name]))
			$typeDeclaration = $this->typensVars[$rootDataName]["input"][$name];
		else if (isset($this->typensVars[$rootDataName]["output"][$name]))
			$typeDeclaration = $this->typensVars[$rootDataName]["output"][$name];

		if (!count($typeDeclaration))
		{
			return new CSOAPFault("Server Error", "decodeDataTypes() can't find type declaration for {$name} param." );
		}
		else
		{
			if (isset($typeDeclaration["varType"]))
				$dataType = $typeDeclaration["varType"];
			else
				$dataType = $name; // case 1 of typens choose.
		}

		if (isset($xsd_simple_type[$dataType]))
			$dataType = $xsd_simple_type[$dataType];

        switch ( $dataType )
        {
            case "string" :
				$returnValue = strval($node->textContent());
			break;

            case "integer" :
            	$returnValue = intval($node->textContent());
            break;

            case "float" :
            case 'double' :
				$returnValue = ($node->textContent());
            break;

            case "boolean" :
            {
                if ( $node->textContent() == "true" )
                    $returnValue = true;
                else
                    $returnValue = false;
            } break;

            case "base64" :
            case "base64Binary" :
                $returnValue = base64_decode($node->textContent());

            break;

			case "any":
				$returnValue = $node;
			break;
			
            default:
            {
            	if (isset($typeDeclaration["arrType"]))
            	{
            		// Decode array

            		$maxOccurs = 0;
            		$returnValue = array();

            		$arrayType = $typeDeclaration["arrType"];
            		if (isset($typeDeclaration["maxOccursA"]))
            			$maxOccurs = $typeDeclaration["maxOccursA"];

            		if (isset($xsd_simple_type[$arrayType]))
            		{
            			$i = 0;
            			$childs = $node->children();
            			foreach ($childs as $child)
            			{
            				$i++;
            				$returnValue[] = $child->textContent();
            				if (intval($maxOccurs) and $i>intval($maxOccurs))
            					break;
            			}
            		}
            		else
            		{
            			foreach ($node->children() as $child)
            			{
            				/*
            				 * Mega hack. Usually as name for this used
            				 * ArrayOf{STRUCT|CLASS}El. So decoder must have
            				 * a chance to find true data type = arrayType;
            				 */
            				if (!isset($this->typensVars[$child->name]))
            					$child->name = $arrayType;
            				// Decode complex data type for an array
            				$decoded = $this->decodeDataTypes( $child, $arrayType );
            				if (is_object($decoded) and (get_class($decoded) == "CSOAPFault" or get_class($decoded) == "csoapfault"))
							{
								CSOAPServer::ShowSOAPFault($decoded);
								return;
							}
            				$returnValue[] = $decoded;
            			}
            		}

            		break;
            	}
            	else
            	{
            		// Here we goes with struct, or with class
            		// First, try to find declaration
            		$objectDecl = 0;
            		$returnValue = array();
            		$params = array();

            		if (!isset($this->typensVars[$dataType])) break;
            		$objectDecl = $this->typensVars[$dataType];

            		// Type of serialization: class/assoc array
            		$objectClass = null;
            		$serialize = "assoc";
            		if (isset($objectDecl["serialize"]))
            		{
            			$serialize = $objectDecl["serialize"];
            			unset($objectDecl["serialize"]);
            		}

            		$requestParams = array(); // reorganize params
		            foreach ( $node->children() as $parameterNode )
		            {
		                if (!$parameterNode->name()) continue;
		                $requestParams[$parameterNode->name()] =
		                	$parameterNode;
		            }

            		foreach($objectDecl as $pname => $param)
            		{
            			$decoded = null;

						if (isset($requestParams[$pname]))
							$decoded = $this->decodeDataTypes( $requestParams[$pname], $dataType );
						if (is_object($decoded) and (get_class($decoded) == "CSOAPFault" or get_class($decoded) == "csoapfault"))
						{
							CSOAPServer::ShowSOAPFault($decoded);
							return;
						}
						if (!$decoded and (!isset($param["strict"]) or
							(isset($param["strict"]) and $param["strict"] == "strict") ))
						{
							return new CSOAPFault("Server Error", "Request has no enought params of strict type to be decoded. " );
						}

						$params[$pname] = $decoded;
		            }

		            if ($serialize == "class")
            		{
            			$stillValid = true;
            			$classRequest = $params;
            			$params = null;

            			if (class_exists($dataType)) {
            				$objectClass = new $dataType;
            				if ($objectClass)
            				{
            					$existedVars = get_object_vars($objectClass);
	            				foreach ($classRequest as $pname => $value)
	            				{
	            					if (!is_set($existedVars, $pname))
	            						$stillValid = false;
	            					$objectClass->$pname = $value;
	            				}
            				}
	            			else
	            			{
	            				$stillValid = false;
	            			}
            			}

            			if ($stillValid) $params = $objectClass;
            		}

            		$returnValue = $params;
            	}


            } break;
        }

        return $returnValue;
    }

	//      Returns the XML payload for the response.
    public function payload( )
    {
        $root = new CXMLCreator("soap:Envelope");
		$root->setAttribute("xmlns:soap", BX_SOAP_ENV);

        // add the body
        $body = new CXMLCreator( "soap:Body" );

        // Check if it's a fault
        if (is_object($this->Value) && ToUpper(get_class($this->Value)) == 'CSOAPFAULT')
        {
            $fault = new CXMLCreator( "soap:Fault" );

            $faultCodeNode = new CXMLCreator( "faultcode" );
            $faultCodeNode->setData($this->Value->faultCode());

            $fault->addChild( $faultCodeNode );

            $faultStringNode = new CXMLCreator( "faultstring" );
            $faultStringNode->setData( $this->Value->faultString() );

            $fault->addChild( $faultStringNode );
			
			if ($this->Value->detail)
				$fault->addChild($this->Value->detail());

            $body->addChild( $fault );
        }
        else
        {
            // add the request
            $responseName = $this->Name . "Response";
            $response = new CXMLCreator( $responseName );
            $response->setAttribute("xmlns", $this->Namespace);
            if (!isset($this->typensVars[$this->Name]["output"]) or !count($this->typensVars[$this->Name]["output"]))
            {
            	if (count($this->typensVars))
            	{
	            	$GLOBALS['APPLICATION']->ThrowException("payload() can't find output type declaration.", "SoapRespnose::payload()");
					return;
            	}
            	else
            	{
					//print_r($this->Value);
					//die();
            		// EncodeLight
            		$value = CXMLCreator::encodeValueLight( $this->ValueName, $this->Value );
            	}

            	$response->addChild( $value );
            }
            else
            {
				//$return = new CXMLCreator($returnType);
				$valueEncoder = new CSOAPCodec();
				$valueEncoder->setTypensVars($this->typensVars);

	            foreach ($this->typensVars[$this->Name]["output"] as $returnType => $returnParam)
	            {
		            if (!$returnType)
		            {
		            	$GLOBALS['APPLICATION']->ThrowException("payload() can't find output type declaration for {$this->Name}.", "SoapRespnose::payload()");
						return;
		            }

		            $valueEncoder->setOutputVars($this->Name);

		            $value = $valueEncoder->encodeValue($returnType, isset($this->Value[$returnType]) ? $this->Value[$returnType] : $this->Value);

		            $response->addChild($value);
	            }

				//AddM
            }


            $body->addChild( $response );
        }

		$root->addChild( $body );

		//AddMessage2Log($root->getXML());
        return CXMLCreator::getXMLHeader().$root->getXML();
    }

	//     Strips the header information from the HTTP raw response.
    public static function stripHTTPHeader( $data )
    {
        $missingxml = false;
        //$start = strpos( $data, "<"."?xml" );
        $start = strpos( $data, "\r\n\r\n" );
        if ($start === false) return null;
        $data = substr( $data, $start, strlen( $data ) - $start );
        return $data;
    }

    public function value()
    {
        return $this->Value;
    }

    public function setValue( $value )
    {
        $this->Value = $value;
    }

    public function setValueName ( $valname )
    {
    	$this->ValueName = $valname;
    }

    public function isFault()
    {
        return $this->IsFault;
    }

    public function faultCode()
    {
        return $this->FaultCode;
    }

    public function faultString()
    {
        return $this->FaultString;
    }

    public function setTypensVars($vars)
    {
    	$this->typensVars = $vars;
    }
}

?>
