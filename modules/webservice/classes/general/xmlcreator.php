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
 *     ...
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
 *             $providersEncoded = CSOAPRequest::encodeValueLight("Providers", $providers);
 *             $prup-&gt;addChild($providersEncoded);        
 *             
 *             ...
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
 * @link http://dev.1c-bitrix.ru/api_help/webservice/classes/cxmlcreator/index.php
 * @author Bitrix
 */
class CXMLCreator {

	var $tag;
	var $data;
	var $startCDATA = "";
	var $endCDATA = "";

	var $attributs = array();
	var $children = array();

	public function CXMLCreator($tag, $cdata = false)
	{
		$cdata ? $this->setCDATA() : null;
		$this->tag = $tag;
	}

	// format of $heavyTag = '[Index:]TagName [asd:qwe="asd"] [zxc:dfg="111"]'
	// returns created CXMLCreator node with setted TagName and Attributes
	
	/**
	* <p>Статический метод возвращает созданный тэг <b>CXMLCreator</b> из названия <i>heavyTag</i>,<i> </i>записанного в особенном формате. Если формат <i>heavyTag</i> неверен, возвращается <i>true</i>.</p>
	*
	*
	* @param string $heavyTag  Строка названия тэга в формате:<br><br><i>[Индекс:]НазваниеТэга
	* [Атрибут="Значение атрибута" ...]</i><br><br><i>Индекс </i>- число,
	* помогающее поместить в ассоциативном массиве сразу несколько
	* тегов с одинаковым названием.<br><br><i>Атрибут</i> может быть записан
	* в виде: <i>симв:симв = "Значение" </i>
	*
	* @return static 
	*
	* <h4>Example</h4> 
	* <pre>
	* <buttononclick>
	* CXMLCreator::createTagAttributed( "LicenseInfo xmlns=\"http://ws.strikeiron.com\"");
	* 
	* // Или
	* CXMLCreator::encodeValueLight( "LicenseInfo xmlns=\"http://ws.strikeiron.com\"",
	* array(
	* 	"1:ArrayOfStringEl" =&gt; "Строка1", 
	* 	"2:ArrayOfStringEl" =&gt; "Строка2"
	* 	)
	* );</buttononclick>
	* </h
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/webservice/classes/cxmlcreator/createtagattributed.php
	* @author Bitrix
	*/
	public static function createTagAttributed($heavyTag, $value = null)
	{
		$heavyTag = trim($heavyTag);
		$name = $heavyTag;

		$attrs = 0;
		$attrsPos = strpos($heavyTag, " ");

		if ($attrsPos)
		{
			$name = substr($heavyTag, 0, $attrsPos);
			$attrs = strstr(trim($heavyTag), " ");
		}

		if (!trim($name)) return false;

		$nameSplited = explode(":", $name);
		if ($nameSplited)
			$name = $nameSplited[count($nameSplited) - 1];
		$name = CDataXML::xmlspecialcharsback($name);

		$node = new CXMLCreator( $name );

		if ($attrs and strlen($attrs))
		{
			$attrsSplit = explode("\"", $attrs);
			$i = 0;
			while ($validi = strpos(trim($attrsSplit[$i]), "="))
			{
				$attrsSplit[$i] = trim($attrsSplit[$i]);
				// attr:ns=
				$attrName = CDataXML::xmlspecialcharsback(substr($attrsSplit[$i], 0, $validi));
				// attrs:ns
				$attrValue = CDataXML::xmlspecialcharsback($attrsSplit[$i+1]);

				$node->setAttribute($attrName, $attrValue);
				$i = $i + 2;
			}
		}

		if (null !== $value)
			$node->setData($value);

		return $node;
	}

	/* static */
	public static function encodeValueLight( $name, $value)
	{
		global $xsd_simple_type;

		//AddMessage2Log($name."|".mydump($value));
		if (!$name)
		{
			ShowError("Tag name undefined (== 0) in encodeValueLight.");
			return false;
		}

		$node = CXMLCreator::createTagAttributed($name);
		$name = $node->tag;

		if (!$node)
		{
			ShowError("Can't create NODE object. Unable to parse tag name: ".$name);
			return false;
		}

		if (is_object($value) && strtolower(get_class($value)) == "cxmlcreator")
		{
			$node->addChild($value);
		}
		else if (is_object($value))
		{
			$ovars = get_object_vars($value);
			foreach ($ovars as $pn => $pv)
			{
				$decode = CXMLCreator::encodeValueLight( $pn, $pv);
				if ($decode) $node->addChild($decode);
			}
		}
		else if (is_array($value))
		{
			foreach ($value as $pn => $pv)
			{
				$decode = CXMLCreator::encodeValueLight( $pn, $pv);
				if ($decode)
				{
					$node->addChild($decode);
				}
			}
		}
		else
		{
			if (!$value) $node->setData("");
			else if (!isset($xsd_simple_type[gettype($value)]))
			{
				ShowError("Unknown param type.");
				return false;
			}

			$node->setData($value);
		}

		return $node;
	}

	public function setCDATA()
	{
		$this->startCDATA = "<![CDATA[";
		$this->endCDATA = "]]>";
	}

	public function setAttribute($attrName, $attrValue)
	{
		global $APPLICATION;

		//$attrName = CDataXML::xmlspecialchars($attrName);
		$attrValue = $APPLICATION->ConvertCharset($attrValue /*CDataXML::xmlspecialchars($attrValue)*/, LANG_CHARSET, 'utf-8');

		$newAttribute = array($attrName => $attrValue);
		$this->attributs = array_merge($this->attributs, $newAttribute);
	}

	public function setData($data)
	{
		global $APPLICATION;

		//$data = CDataXML::xmlspecialchars($data);
		$this->data = $APPLICATION->ConvertCharset($data, SITE_CHARSET, "utf-8");
	}

	public function setName($tag)
	{
		//$tag = CDataXML::xmlspecialchars($tag);
		$this->tag = $tag;
	}

	public function addChild($element)
	{
		//AddMessage2Log(mydump(get_class($element)));
		if($element && (get_class($element) == "CXMLCreator" || get_class($element) == "cxmlcreator"))
		{
			array_push($this->children, $element);
		}
	}

	public function getChildrenCount()
	{
		return count($this->children);
	}

	public function _getAttributs()
	{
		$attributs = "";
		if (is_array($this->attributs)){
			foreach($this->attributs as $key=>$val)
			{
				$attributs .= " " . CDataXML::xmlspecialchars($key). "=\"" . CDataXML::xmlspecialchars($val) . "\"";
			}
		}
		return $attributs;
	}

	public function _getChildren()
	{
		$children = "";
		foreach($this->children as $key=>$val)
		{
			$children .= $val->getXML();
		}
		return $children;

	}

	public function getXML()
	{
		if (!$this->tag) return "";
		$xml  = "<" . CDataXML::xmlspecialchars($this->tag) . $this->_getAttributs() . ">";
		$xml .= $this->startCDATA;
		$xml .= $this->data;
		$xml .= $this->endCDATA;
		$xml .= $this->_getChildren();
		$xml .= "</" . CDataXML::xmlspecialchars($this->tag) . ">";
		return $xml;
	}

	public static function getXMLHeader()
	{
		return "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
	}

	public function __destruct()
	{
		unset($this->tag);
	}

	/* static */
	public static function CreateFromDOM($dom)
	{
		return CXMLCreator::__createFromDOM($dom->root[0]);
	}

	/* static */
	public static function __createFromDOM($domNode)
	{
		$result = new CXMLCreator($domNode->name);

		$result->setData($domNode->content);

		if (is_array($domNode->attributes))
		{
			foreach ($domNode->attributes as $attrDomNode)
			{
				$result->setAttribute($attrDomNode->name, $attrDomNode->content);
			}
		}

		if (is_array($domNode->children))
		{
			foreach ($domNode->children as $domChild)
			{
				$result->addChild(CXMLCreator::__createFromDOM($domChild));
			}
		}

		return $result;
	}
}

?>