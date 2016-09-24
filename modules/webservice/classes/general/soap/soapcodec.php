<?php

class CSOAPCodec
{
	var $outputVars = array();
	var $typensVars = array();

    static function CSOAPCodec()
    {
    }

    public function setTypensVars($vars)
    {
    	$this->typensVars = $vars;
    }

    public function setOutputVars($functionName)
    {
    	if (!isset($this->typensVars[$functionName]["output"]))
    	{
    		ShowError("encodeValue() cant find output declaration.");
    		exit();
    	}

    	$this->outputVars = $this->typensVars[$functionName]["output"];
    }

	public static function _validateSimpleType($dataType, $value)
	{
		global $xsd_simple_type;
		if (!isset($xsd_simple_type[$dataType]))
			CSOAPCodec::_errorTypeValidation("[Is not a simple type.{$dataType}]", $value);

		if ($dataType != gettype( $value ))
		{
			// all numbers are same as string
			if (is_numeric($value) and (
				$dataType == "integer" or
				$dataType == "double" or
				$dataType == "float"))
				return;
			//elseif ($dataType == 'base64Binary' && preg_match('/^([A-Za-z0-9]|\+|\/|\-|\=)+$/', $value))
			elseif ($dataType == 'base64Binary' || $dataType == 'any')
				return;

			CSOAPCodec::_errorTypeValidation($dataType, $value);
		}
	}

	public static function _validateClassType($classType, $value)
	{
		$phpClassType = strtolower($classType);
		$phpValue = strtolower(get_class($value));
		if ($phpClassType != $phpValue)
		{
			CSOAPServer::ShowSOAPFault("_errorTypeValidation(): Type validation for func. failed: {$classType} != ".get_class($value));
    		exit();
		}
	}

	public static function _validateType($dataType, $value)
	{
		global $xsd_simple_type;
		if (isset($xsd_simple_type[$dataType]))
		{
			/*
			if (is_array($value))
			{
				echo $dataType;
				die();

			}
			else*/if ($dataType != gettype( $value ))
			{
				// all numbers are same as string
				if (is_numeric($value) and (
					$dataType == "integer" or
					$dataType == "double" or
					$dataType == "float"))
					return;
				//elseif ($dataType == 'base64Binary' && preg_match('/^([A-Za-z0-9]|\+|\/|\-|\=)+$/', $value))
				elseif ($dataType == 'base64Binary' || $dataType == 'any')
					return;

				CSOAPCodec::_errorTypeValidation($dataType, $value);
			}
		}
		else
		{
			if (!is_object($value) and !is_array($value))
				CSOAPCodec::_errorTypeValidation($dataType, $value);
		}
	}

	public static function _errorTypeValidation($dataType, $value)
	{
		CSOAPServer::ShowSOAPFault("_errorTypeValidation(): Type validation for func. failed: {$dataType} != ".gettype($value));
    	exit();
	}

	// Encodes a PHP variable into a SOAP datatype.
    public function encodeValue($name, $value, $complexDataTypeName = "")
    {
    	global $xsd_simple_type;
    	if (!is_array($this->outputVars) or !count($this->outputVars))
    	{
    		CSOAPServer::ShowSOAPFault("encodeValue() has no Output Data Type Declaration for validation.");
    		exit();
    	}

 		$dataType = "";
 		$typeDeclaration = "";
 		if (isset($this->outputVars[$name]))
 			$typeDeclaration = $this->outputVars[$name];
 		else if (isset($this->typensVars[$name]))
 			$typeDeclaration = $this->typensVars[$name];
 		else if (isset($this->typensVars[$complexDataTypeName][$name]))
 			$typeDeclaration = $this->typensVars[$complexDataTypeName][$name];

		if (isset($typeDeclaration["varType"])) // if not, name = complex data type
			$dataType = $typeDeclaration["varType"];
		else
			$dataType = $name;

		if (isset($xsd_simple_type[$dataType]))
			$dataType = $xsd_simple_type[$dataType];

		// Type validation
		$this->_validateType($dataType, $value);

        switch ($dataType)
        {
            case "string" :
            {
                $node = new CXMLCreator( $name );
                //$node->setAttribute( "type", BX_SOAP_XSD_PREFIX . ":string" );
                $node->setData($value);
                return $node;
            } break;

            case "boolean" :
            {
                $node = new CXMLCreator( $name );
                //$node->setAttribute( "type", BX_SOAP_XSD_PREFIX . ":boolean" );
                if ( $value === true )
                    $node->setData( "true" );
                else
                    $node->setData( "false" );
                return $node;
            } break;

            case "integer" :
            {
				$node = new CXMLCreator( $name );
                //$node->setAttribute( "type", BX_SOAP_XSD_PREFIX . ":int" );
                $node->setData( intval( $value ) );
                return $node;
            } break;

			case "float":
            case "double" :
            {
            	$node = new CXMLCreator( $name );
                //$node->setAttribute( "type", BX_SOAP_XSD_PREFIX . ":float" );
                $node->setData($value);
                return $node;
            } break;

            // added by Sigurd
            case "base64":
            case "base64Binary":
            	$node = new CXMLCreator($name);
                //$node->setAttribute("type", BX_SOAP_XSD_PREFIX . ":base64Binary" );
                $node->setData(base64_encode($value));
                return $node;

            break;

			case 'any':
				$node = new CXMLCreator($name);
				
				if (is_object($value))
				{
// $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/ws.log', 'a');
// fwrite($fp, $value->GetTree()."\n");
// fwrite($fp, '===================================='."\r\n");
// fclose($fp);

				
					if (get_class($value) == 'CDataXML')
						$node->addChild(CXMLCreator::CreateFromDOM($value->GetTree()));
					elseif (get_class($value) == 'CDataXMLDocument')
						$node->addChild(CXMLCreator::CreateFromDOM($value));
					elseif(get_class($value) == 'CXMLCreator')
						$node->addChild($value);
				}
				else
				{
					$data = new CDataXML();
					if ($data->LoadString($value))
						$node->addChild(CXMLCreator::CreateFromDOM($data->GetTree()));
					else
						$node->setData($value);
				}
				
				return $node;
			break;
			
            default :
            {
            	$node = new CXMLCreator( $name );

            	if (isset($typeDeclaration["arrType"]))
            	{
            		if (!isset($typeDeclaration["varType"]))
            			$this->_errorTypeValidation("varType [undef]", $value);

            		$varType = $typeDeclaration["varType"];

            		// Decode array
            		$maxOccurs = 0;

            		$arrayType = $typeDeclaration["arrType"];
            		if (isset($typeDeclaration["maxOccursA"]))
            			$maxOccurs = $typeDeclaration["maxOccursA"];

            		if (isset($xsd_simple_type[$arrayType]))
            		{
            			$i = 0;
            			$arrayType = $xsd_simple_type[$arrayType];
            			$arrayTypeEl = $varType."El"; // TODO: non fixed. get El name from wsdl. or decl.
            			if (!is_array($value))
            				CSOAPCodec::_errorTypeValidation("Array", $value);

            			foreach ($value as $valnode)
            			{
            				$i++;
            				$this->_validateType($arrayType, $valnode);
            				$cndata = new CXMLCreator ( $arrayTypeEl );
            				$cndata->setData($valnode);
            				$node->addChild($cndata);

            				if (intval($maxOccurs)>0 and $i>$maxOccurs)
            					break;
            			}
            		}
            		else
            		{
            			// Complex data type arrays // $arrayType as is.
            			// TODO: non fixed. get $arrayTypeEl name from wsdl. or decl.
            			$i = 0;
            			$arrayTypeEl = $varType."El";
            			if (!is_array($value))
            				CSOAPCodec::_errorTypeValidation("Array", $value);

            			foreach ($value as $valnode)
            			{
            				$decoded = null;
            				$i++;

            				$this->_validateType($arrayType, $valnode);
            				$decoded = $this->encodeValue( $arrayType, $valnode );

            				$cndata = new CXMLCreator ( $arrayTypeEl );

            				if ($decoded)
            				{
            					$this->_validateClassType("CXMLCreator", $decoded);
            					$decoded->setName($arrayTypeEl);
            					$node->addChild($decoded);
            				}

            				if (intval($maxOccurs)>0 and $i>$maxOccurs)
            					break;
            			}
            		}
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

            		if (!$objectDecl)
            		{
            			CSOAPServer::ShowSOAPFault("encodeValue() cant find complex type declaration for {$dataType}.");
            			exit();
            		}

            		// Type of serialization: class/assoc array
            		$objectClass = null;
            		$serialize = "assoc";
            		if (isset($objectDecl["serialize"]))
            		{
            			$serialize = $objectDecl["serialize"];
            			unset($objectDecl["serialize"]);
            		}

					// Validate hard complex data types
            		if ($serialize == "assoc")
            			$this->_validateType("array", $value);
            		if ($serialize != "assoc")
            			$this->_validateClassType($dataType, $value);

            		foreach($objectDecl as $pname => $param)
            		{
            			$decoded = null;
            			$strict = true;
            			if (isset($param["strict"])) $strict = ($param["strict"]=="strict")?true:false;

            			if ($serialize == "assoc")
            			{
							//var_dump($pname); var_dump($value[$pname]); die();
            				if (isset($value[$pname]))
            					$decoded = $this->encodeValue( $pname, $value[$pname], $dataType );
            			}
            			else
            			if ($serialize != "assoc")
            			{
            				if (isset($value->$pname))
            					$decoded = $this->encodeValue( $pname, $value->$pname, $dataType );
            			}


            			if ($decoded)
            				$this->_validateClassType("CXMLCreator", $decoded);

            			if (!$decoded and !$strict)
						{
							CSOAPServer::ShowSOAPFault("Request has no enought params of strict type to be decoded. ");
			            	exit();
						}

						$node->addChild($decoded);
		            }
            	}
            	return $node;
            } break;
        }

        return false;
    }
}

?>
