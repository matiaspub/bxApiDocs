<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CDataXMLNode
{
	var $name;
	var $content;
	/** @var CDataXMLNode[] */
	var $children;
	/** @var CDataXMLNode[] */
	var $attributes;
	var $_parent;

	static public function __construct()
	{
	}

	public function name()
	{
		return $this->name;
	}

	public function children()
	{
		return $this->children;
	}

	public function textContent()
	{
		return $this->content;
	}

	public function getAttribute($attribute)
	{
		if(is_array($this->attributes))
		{
			foreach ($this->attributes as $anode)
				if ($anode->name == $attribute)
					return $anode->content;
		}
		return "";
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function namespaceURI()
	{
		return $this->getAttribute("xmlns");
	}

	/**
	 * @param $tagname
	 * @return CDataXMLNode[]
	 */
	public function elementsByName($tagname)
	{
		$result = array();

		if ($this->name == $tagname)
			array_push($result, $this);

		if(is_array($this->children))
		{
			foreach ($this->children as $node)
			{
				$more = $node->elementsByName($tagname);
				if (is_array($more))
				{
					foreach($more as $mnode)
						array_push($result, $mnode);
				}
			}
		}
		return $result;
	}

	public static function _SaveDataType_OnDecode(&$result, $name, $value)
	{
		if (isset($result[$name]))
		{
			$i = 1;
			while (isset($result[$i.":".$name]))
				$i++;
			$result[$i.":".$name] = $value;
			return "indexed";
		}
		else
		{
			$result[$name] = $value;
			return "common";
		}
	}

	public function decodeDataTypes($attrAsNodeDecode = false)
	{
		$result = array();

		if (!$this->children)
		{
			$this->_SaveDataType_OnDecode($result, $this->name(), $this->textContent());
		}
		else
		{
			foreach ($this->children() as $child)
			{
				$cheese = $child->children();
				if (!$cheese or !count($cheese))
				{
					$this->_SaveDataType_OnDecode($result, $child->name(), $child->textContent());
				}
				else
				{
					$cheresult = $child->decodeDataTypes();
					if (is_array($cheresult))
						$this->_SaveDataType_OnDecode($result, $child->name(), $cheresult);
				}
			}
		}

		if ($attrAsNodeDecode)
		{
			foreach ($this->getAttributes() as $child)
			{
				$this->_SaveDataType_OnDecode($result, $child->name(), $child->textContent());
			}
		}

		return $result;
	}

	function &__toString()
	{
		switch ($this->name)
		{
			case "cdata-section":
				$ret = "<![CDATA[";
				$ret .= $this->content;
				$ret .= "]]>";
				break;

			default:
				$isOneLiner = false;

				if (count($this->children) == 0 && $this->content == '')
					$isOneLiner = true;

				$attrStr = "";

				if(is_array($this->attributes))
				{
					foreach ($this->attributes as $attr)
					{
						$attrStr .= " ".$attr->name."=\"".CDataXML::xmlspecialchars($attr->content)."\" ";
					}
				}

				if ($isOneLiner)
					$oneLinerEnd = " /";
				else
					$oneLinerEnd = "";

				$ret = "<".$this->name.$attrStr.$oneLinerEnd.">";

				if(is_array($this->children))
				{
					foreach ($this->children as $child)
					{
						$ret .= $child->__toString();
					}
				}

				if (!$isOneLiner)
				{
					if ($this->content <> '')
						$ret .= CDataXML::xmlspecialchars($this->content);

					$ret .= "</".$this->name.">";
				}

				break;
		}

		return $ret;
	}

	public function __toArray()
	{
		$retHash = array(
			"@" => array(),
		);

		if (is_array($this->attributes))
		{
			foreach ($this->attributes as $attr)
				$retHash["@"][$attr->name] = $attr->content;
		}

		if ($this->content != "")
		{
			$retHash["#"] = $this->content;
		}
		elseif (!empty($this->children))
		{
			$ar = array();
			foreach ($this->children as $child)
				$ar[$child->name][] = $child->__toArray();
			$retHash["#"] = $ar;
		}
		else
		{
			$retHash["#"] = "";
		}

		return $retHash;
	}
}

class CDataXMLDocument
{
	var $version = '';
	var $encoding = '';

	/** @var CDataXMLNode[] */
	var $children;
	var $root;

	static public function __construct()
	{
	}

	public function elementsByName($tagname)
	{
		$result = array();
		if(is_array($this->children))
		{
			foreach ($this->children as $node)
			{
				$more = $node->elementsByName($tagname);
				if (is_array($more))
				{
					foreach($more as $mnode)
						array_push($result, $mnode);
				}
			}
		}
		return $result;
	}

	public function encodeDataTypes( $name, $value)
	{
		static $Xsd = array(
			"string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
			"int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
			"array"=>"anyType", "resource"=>"anyType",
			"mixed"=>"anyType", "unknown_type"=>"anyType", "anyType"=>"anyType"
		);

		$node = new CDataXMLNode();
		$node->name = $name;

		if (is_object($value))
		{
			$ovars = get_object_vars($value);
			foreach ($ovars as $pn => $pv)
			{
				$decode = CDataXMLDocument::encodeDataTypes( $pn, $pv);
				if ($decode)
					array_push($node->children, $decode);
			}
		}
		else if (is_array($value))
		{
			foreach ($value as $pn => $pv)
			{
				$decode = CDataXMLDocument::encodeDataTypes( $pn, $pv);
				if ($decode)
					array_push($node->children, $decode);
			}
		}
		else
		{
			if (isset($Xsd[gettype($value)]))
			{
				$node->content = $value;
			}
		}
		return $node;
	}

	/* Returns a XML string of the DOM document */
	function &__toString()
	{
		$ret = "<"."?xml";
		if ($this->version <> '')
			$ret .= " version=\"".$this->version."\"";
		if ($this->encoding <> '')
			$ret .= " encoding=\"".$this->encoding."\"";
		$ret .= "?".">";

		if(is_array($this->children))
		{
			foreach ($this->children as $child)
			{
				$ret .= $child->__toString();
			}
		}

		return $ret;
	}

	/* Returns an array of the DOM document */
	function &__toArray()
	{
		$arRetArray = array();

		if (is_array($this->children))
		{
			foreach ($this->children as $child)
			{
				$arRetArray[$child->name] = $child->__toArray();
			}
		}

		return $arRetArray;
	}
}

class CDataXML
{
	/** @var CDataXMLDocument */
	var $tree;
	var $TrimWhiteSpace;

	var $delete_ns = true;

	public function __construct($TrimWhiteSpace = True)
	{
		$this->TrimWhiteSpace = ($TrimWhiteSpace ? True : False);
		$this->tree = False;
	}

	public function Load($file)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		unset($this->tree);
		$this->tree = False;

		if (file_exists($file))
		{
			$content = file_get_contents($file);
			$charset = (defined("BX_DEFAULT_CHARSET")? BX_DEFAULT_CHARSET : "windows-1251");
			if (preg_match("/<"."\\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\\?".">/i", $content, $matches))
			{
				$charset = trim($matches[1]);
			}
			$content = $APPLICATION->ConvertCharset($content, $charset, SITE_CHARSET);
			$this->tree = &$this->__parse($content);
			return $this->tree !== false;
		}

		return false;
	}

	public function LoadString($text)
	{
		unset($this->tree);
		$this->tree = false;

		if ($text <> '')
		{
			$this->tree = &$this->__parse($text);
			return ($this->tree !== false);
		}

		return false;
	}

	function &GetTree()
	{
		return $this->tree;
	}

	function &GetArray()
	{
		if (!is_object($this->tree))
			return false;
		else
			return $this->tree->__toArray();
	}

	function &GetString()
	{
		if (!is_object($this->tree))
			return false;
		else
			return $this->tree->__toString();
	}

	function &SelectNodes($strNode)
	{
		if (!is_object($this->tree))
			return false;

		$result = &$this->tree;

		$tmp = explode("/", $strNode);
		$tmpCount = count($tmp);
		for ($i = 1; $i < $tmpCount; $i++)
		{
			if ($tmp[$i] != "")
			{
				if (!is_array($result->children))
					return false;

				$bFound = false;
				for ($j = 0, $c = count($result->children); $j < $c; $j++)
				{
					if ($result->children[$j]->name==$tmp[$i])
					{
						$result = &$result->children[$j];
						$bFound = true;
						break;
					}
				}

				if (!$bFound)
					return false;
			}
		}

		return $result;
	}

	public static function xmlspecialchars($str)
	{
		static $search = array("&","<",">","\"","'");
		static $replace = array("&amp;","&lt;","&gt;","&quot;","&apos;");
		return str_replace($search, $replace, $str);
	}

	public static function xmlspecialcharsback($str)
	{
		static $search = array("&lt;","&gt;","&quot;","&apos;","&amp;");
		static $replace = array("<",">","\"","'","&");
		return str_replace($search, $replace, $str);
	}

	/**
	 * Will return an DOM object tree from the well formed XML.
	 *
	 * @param string $strXMLText
	 * @return CDataXMLDocument
	 */
	function __parse(&$strXMLText)
	{
		static $search = array("&gt;","&lt;","&apos;","&quot;","&amp;");
		static $replace = array(">","<","'",'"',"&");

		$oXMLDocument = new CDataXMLDocument();

		// strip comments
		$strXMLText = &CDataXML::__stripComments($strXMLText);

		// stip the !doctype
		// The DOCTYPE declaration can consists of an internal DTD in square brackets
		$cnt = 0;
		$strXMLText = preg_replace("%<\\!DOCTYPE[^\\[>]*\\[.*?\\]>%is", "", $strXMLText, -1, $cnt);
		if($cnt == 0)
			$strXMLText = preg_replace("%<\\!DOCTYPE[^>]*>%is", "", $strXMLText);

		// get document version and encoding from header
		preg_match_all("#<\\?(.*?)\\?>#i", $strXMLText, $arXMLHeader_tmp);
		foreach ($arXMLHeader_tmp[0] as $strXMLHeader_tmp)
		{
			preg_match_all("/([a-zA-Z:]+=\".*?\")/i", $strXMLHeader_tmp, $arXMLParam_tmp);
			foreach ($arXMLParam_tmp[0] as $strXMLParam_tmp)
			{
				if ($strXMLParam_tmp <> '')
				{
					$arXMLAttribute_tmp = explode("=\"", $strXMLParam_tmp);
					if ($arXMLAttribute_tmp[0] == "version")
						$oXMLDocument->version = substr($arXMLAttribute_tmp[1], 0, strlen($arXMLAttribute_tmp[1]) - 1);
					elseif ($arXMLAttribute_tmp[0] == "encoding")
						$oXMLDocument->encoding = substr($arXMLAttribute_tmp[1], 0, strlen($arXMLAttribute_tmp[1]) - 1);
				}
			}
		}

		// strip header
		$strXMLText = &preg_replace("#<\\?.*?\\?>#", "", $strXMLText);

		$oXMLDocument->root = &$oXMLDocument->children;

		/** @var CDataXMLNode $currentNode */
		$currentNode = &$oXMLDocument;

		$tok = strtok($strXMLText, "<");
		$arTag = explode(">", $tok);
		if(count($arTag) < 2)
		{
			//There was whitespace before <, so make another try
			$tok = strtok("<");
			$arTag = explode(">", $tok);
			if(count($arTag) < 2)
			{
				//It's a broken XML
				return false;
			}
		}

		while($tok !== false)
		{
			$tagName = $arTag[0];
			$tagContent = $arTag[1];

			// find tag name with attributes
			// check if it's an endtag </tagname>
			if($tagName[0] == "/")
			{
				$tagName = substr($tagName, 1);
				// strip out namespace; nameSpace:Name
				if($this->delete_ns)
				{
					$colonPos = strpos($tagName, ":");

					if ($colonPos > 0)
						$tagName = substr($tagName, $colonPos + 1);
				}

				if($currentNode->name != $tagName)
				{
					// Error parsing XML, unmatched tags $tagName
					return false;
				}

				$currentNode = $currentNode->_parent;

				// convert special chars
				if ((!$this->TrimWhiteSpace) || (trim($tagContent) != ""))
					$currentNode->content = str_replace($search, $replace, $tagContent);
			}
			elseif(strncmp($tagName, "![CDATA[", 8) === 0)
			{
				//because cdata may contain > and < chars
				//it is special processing needed
				$cdata = "";
				for($i = 0, $c = count($arTag); $i < $c; $i++)
				{
					$cdata .= $arTag[$i].">";
					if(substr($cdata, -3) == "]]>")
					{
						$tagContent = $arTag[$i+1];
						break;
					}
				}

				if(substr($cdata, -3) != "]]>")
				{
					$cdata = substr($cdata, 0, -1)."<";
					do
					{
						$tok = strtok(">");//unfortunatly strtok eats > followed by >
						$cdata .= $tok.">";
						//util end of string or end of cdata found
					}
					while ($tok !== false && substr($tok, -2) != "]]");
					//$tagName = substr($tagName, 0, -1);
				}

				$cdataSection = substr($cdata, 8, -3);

				// new CDATA node
				$subNode = new CDataXMLNode();
				$subNode->name = "cdata-section";
				$subNode->content = $cdataSection;

				$currentNode->children[] = $subNode;
				$currentNode->content .= $subNode->content;

				// convert special chars
				if ((!$this->TrimWhiteSpace) || (trim($tagContent) != ""))
					$currentNode->content = str_replace($search, $replace, $tagContent);
			}
			else
			{
				// normal start tag
				$firstSpaceEnd = strpos($tagName, " ");
				$firstNewlineEnd = strpos($tagName, "\n");

				if ($firstNewlineEnd != false)
				{
					if ($firstSpaceEnd != false)
					{
						$tagNameEnd = min($firstSpaceEnd, $firstNewlineEnd);
					}
					else
					{
						$tagNameEnd = $firstNewlineEnd;
					}
				}
				else
				{
					if ($firstSpaceEnd != false)
					{
						$tagNameEnd = $firstSpaceEnd;
					}
					else
					{
						$tagNameEnd = 0;
					}
				}

				if ($tagNameEnd > 0)
					$justName = substr($tagName, 0, $tagNameEnd);
				else
					$justName = $tagName;

				// strip out namespace; nameSpace:Name
				if ($this->delete_ns)
				{
					$colonPos = strpos($justName, ":");

					if ($colonPos > 0)
						$justName = substr($justName, $colonPos + 1);
				}

				// remove trailing / from the name if exists
				$justName = rtrim($justName, "/");

				$subNode = new CDataXMLNode();
				$subNode->_parent = $currentNode;
				$subNode->name = $justName;

				// find attributes
				if ($tagNameEnd > 0)
				{
					$attributePart = substr($tagName, $tagNameEnd);

					// attributes
					unset($attr);
					$attr = CDataXML::__parseAttributes($attributePart);

					if ($attr != false)
						$subNode->attributes = $attr;
				}

				// convert special chars
				if ((!$this->TrimWhiteSpace) || (trim($tagContent) != ""))
					$subNode->content = str_replace($search, $replace, $tagContent);

				$currentNode->children[] = $subNode;

				if (substr($tagName, -1) != "/")
					$currentNode = $subNode;
			}

			//Next iteration
			$tok = strtok("<");
			$arTag = explode(">", $tok);
			//There was whitespace before < just after CDATA section, so make another try
			if(count($arTag) < 2 && (strncmp($tagName, "![CDATA[", 8) === 0))
			{
				$currentNode->content .= $arTag[0];

				// convert special chars
				if ((!$this->TrimWhiteSpace) || (trim($tagContent) != ""))
					$currentNode->content = str_replace($search, $replace, $tagContent);

				$tok = strtok("<");
				$arTag = explode(">", $tok);
			}
		}
		return $oXMLDocument;
	}

	public function __stripComments(&$str)
	{
		$str = &preg_replace("#<\\!--.*?-->#s", "", $str);
		return $str;
	}

	/* Parses the attributes. Returns false if no attributes in the supplied string is found */
	function &__parseAttributes($attributeString)
	{
		$ret = false;

		preg_match_all("/(\\S+)\\s*=\\s*([\"'])(.*?)\\2/s".BX_UTF_PCRE_MODIFIER, $attributeString, $attributeArray);

		foreach ($attributeArray[0] as $i => $attributePart)
		{
			$attributePart = trim($attributePart);
			if ($attributePart != "" && $attributePart != "/")
			{
				$attributeName = $attributeArray[1][$i];

				// strip out namespace; nameSpace:Name
				if ($this->delete_ns)
				{
					$colonPos = strpos($attributeName, ":");

					if ($colonPos > 0)
					{
						// exclusion: xmlns attribute is xmlns:nameSpace
						if ($colonPos == 5 && (substr($attributeName, 0, $colonPos) == 'xmlns'))
							$attributeName = 'xmlns';
						else
							$attributeName = substr($attributeName, $colonPos + 1);
					}
				}
				$attributeValue = $attributeArray[3][$i];

				unset($attrNode);
				$attrNode = new CDataXMLNode();
				$attrNode->name = $attributeName;
				$attrNode->content = CDataXML::xmlspecialcharsback($attributeValue);

				$ret[] = &$attrNode;
			}
		}
		return $ret;
	}
}
/*
Usage:

class OrderLoader
{
	var $errors = array();

	public static function elementHandler($path, $attr)
	{
		AddMessage2Log(print_r(array($path, $attr), true));
	}

	public static function nodeHandler(CDataXML $xmlObject)
	{
		AddMessage2Log(print_r($xmlObject, true));
	}
}

$position = false;
$loader = new OrderLoader;

while(true) //this while is cross hit emulation
{
	$o = new CXMLFileStream;
	$o->registerElementHandler("/КоммерческаяИнформация", array($loader, "elementHandler"));
	$o->registerNodeHandler("/КоммерческаяИнформация/Каталог/Товары/Товар", array($loader, "nodeHandler"));
	$o->setPosition($position);

	if ($o->openFile($_SERVER["DOCUMENT_ROOT"]."/upload/081_books_books-books_ru.xml"))
	{
		while($o->findNext())
		{
			//if (time() > $endTime)
			break;
		}

		if ($o->endOfFile())
		{
			break;
		}
		else
		{
			$position = $o->getPosition();
		}
	}
}
*/
class CXMLFileStream
{
	private $fileCharset = false;
	private $filePosition = 0;
	private $xmlPosition = "";
	private $nodeHandlers = array();
	private $elementHandlers = array();
	private $endNodes = array();
	private $fileHandler = null;

	private $eof = false;
	private $readSize = 1024;
	private $buf = "";
	private $bufPosition = 0;
	private $bufLen = 0;
	private $positionStack = array();
	private $elementStack = array();
	/**
	 * Registers an handler function which will be called on xml parsed path with CDataXML object as a parameter
	 *
	 * @param string $nodePath
	 * @param mixed $callableHandler
	 * @return void
	 *
	 */
	public function registerNodeHandler($nodePath, $callableHandler)
	{
		if (is_callable($callableHandler))
		{
			if (!isset($this->nodeHandlers[$nodePath]))
				$this->nodeHandlers[$nodePath] = array();
			$this->nodeHandlers[$nodePath][] = $callableHandler;

			$pathComponents = explode("/", $nodePath);
			$this->endNodes[end($pathComponents)] = true;
		}
	}
	/**
	 * Registers an handler function which will be called on xml parsed path with path and attributes
	 *
	 * @param string $nodePath
	 * @param mixed $callableHandler
	 * @return void
	 *
	 */
	public function registerElementHandler($nodePath, $callableHandler)
	{
		if (is_callable($callableHandler))
		{
			if (!isset($this->elementHandlers[$nodePath]))
				$this->elementHandlers[$nodePath] = array();
			$this->elementHandlers[$nodePath][] = $callableHandler;

			$pathComponents = explode("/", $nodePath);
			$this->endNodes[end($pathComponents)] = true;
		}
	}
	/**
	 * Opens file by it's absolute path. Returns true on success.
	 *
	 * @param string $filePath
	 * @return bool
	 *
	 */
	public function openFile($filePath)
	{
		$this->fileHandler = null;

		$io = CBXVirtualIo::getInstance();
		$file = $io->getFile($filePath);
		$this->fileHandler = $file->open("rb");
		if (is_resource($this->fileHandler))
		{
			if ($this->filePosition > 0)
				fseek($this->fileHandler, $this->filePosition);

			$this->elementStack = array();
			$this->positionStack = array();
			foreach(explode("/", $this->xmlPosition) as $pathPart)
			{
				@list($elementPosition, $elementName) = explode("@", $pathPart, 2);
				$this->elementStack[] = $elementName;
				$this->positionStack[] = $elementPosition;
			}

			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * Returns true when end of the file is reached.
	 *
	 * @return bool
	 *
	 */
	public function endOfFile()
	{
		if ($this->fileHandler === null)
			return true;
		else
			return $this->eof;
	}
	/**
	 * Returns current position state needed to continue file parsing process on the next hit.
	 *
	 * @return array[int]string
	 *
	 */
	public function getPosition()
	{
		$this->xmlPosition = array();
		foreach($this->elementStack as $i => $elementName)
			$this->xmlPosition[] = $this->positionStack[$i]."@".$elementName;
		$this->xmlPosition = implode("/", $this->xmlPosition);

		return array(
			$this->fileCharset,
			$this->filePosition,
			$this->xmlPosition,
		);
	}
	/**
	 * Sets the position state returned by getPosition method.
	 *
	 * @param array[int]string $position
	 * @return void
	 *
	 */
	public function setPosition($position)
	{
		if(is_array($position))
		{
			if (isset($position[0]))
				$this->fileCharset = $position[0];
			if (isset($position[1]))
				$this->filePosition = $position[1];
			if (isset($position[2]))
				$this->xmlPosition = $position[2];
		}
	}
	/**
	 * Processes file futher. Returns true when there is more work to do. False on the end of file.
	 *
	 * @return bool
	 *
	 */
	public function findNext()
	{
		$bMB = defined("BX_UTF");
		$cs = $this->fileCharset;

		if ($this->fileHandler === null)
			return false;

		$this->eof = false;
		while(($xmlChunk = $this->getXmlChunk($bMB)) !== false)
		{
			$origChunk = $xmlChunk;
			if($cs)
			{
				$error = "";
				$xmlChunk = \Bitrix\Main\Text\Encoding::convertEncoding($origChunk, $cs, LANG_CHARSET, $error);
			}

			if($xmlChunk[0] == "/")
			{
				$this->endElement($xmlChunk);
				return true;
			}
			elseif($xmlChunk[0] == "!" || $xmlChunk[0] == "?")
			{
				if(substr($xmlChunk, 0, 4) === "?xml")
				{
					if(preg_match('#encoding[\s]*=[\s]*"(.*?)"#i', $xmlChunk, $arMatch))
					{
						$this->fileCharset = $arMatch[1];
						if(strtoupper($this->fileCharset) === strtoupper(LANG_CHARSET))
							$this->fileCharset = false;
						$cs = $this->fileCharset;
					}
				}
			}
			else
			{
				$this->startElement($bMB, $xmlChunk, $origChunk);
			}
		}
		$this->eof = true;

		return false;
	}
	/**
	 * Used to read an xml by chunks started with "<" and endex with "<"
	 *
	 * @param bool $bMB
	 * @return bool
	 *
	 */
	private function getXmlChunk($bMB = false)
	{
		if($this->bufPosition >= $this->bufLen)
		{
			if(!feof($this->fileHandler))
			{
				$this->buf = fread($this->fileHandler, $this->readSize);
				$this->bufPosition = 0;
				$this->bufLen = $bMB? mb_strlen($this->buf, 'latin1'): strlen($this->buf);
			}
			else
			{
				return false;
			}
		}

		//Skip line delimiters (ltrim)
		$xml_position = $bMB? mb_strpos($this->buf, "<", $this->bufPosition, 'latin1'): strpos($this->buf, "<", $this->bufPosition);
		while($xml_position === $this->bufPosition)
		{
			$this->bufPosition++;
			$this->filePosition++;
			//Buffer ended with white space so we can refill it
			if($this->bufPosition >= $this->bufLen)
			{
				if(!feof($this->fileHandler))
				{
					$this->buf = fread($this->fileHandler, $this->readSize);
					$this->bufPosition = 0;
					$this->bufLen = $bMB? mb_strlen($this->buf, 'latin1'): strlen($this->buf);
				}
				else
					return false;
			}
			$xml_position = $bMB? mb_strpos($this->buf, "<", $this->bufPosition, 'latin1'): strpos($this->buf, "<", $this->bufPosition);
		}

		//Let's find next line delimiter
		while($xml_position===false)
		{
			$next_search = $this->bufLen;
			//Delimiter not in buffer so try to add more data to it
			if(!feof($this->fileHandler))
			{
				$this->buf .= fread($this->fileHandler, $this->readSize);
				$this->bufLen = $bMB? mb_strlen($this->buf, 'latin1'): strlen($this->buf);
			}
			else
				break;

			//Let's find xml tag start
			$xml_position = $bMB? mb_strpos($this->buf, "<", $next_search, 'latin1'): strpos($this->buf, "<", $next_search);
		}
		if($xml_position===false)
			$xml_position = $this->bufLen+1;

		$len = $xml_position-$this->bufPosition;
		$this->filePosition += $len;
		$result = $bMB? mb_substr($this->buf, $this->bufPosition, $len, 'latin1'): substr($this->buf, $this->bufPosition, $len);
		$this->bufPosition = $xml_position;

		return $result;
	}
	/**
	 * Stores an element into xml path stack.
	 *
	 * @param bool $bMB
	 * @param string $xmlChunk
	 * @param string $origChunk
	 * @return void
	 *
	 */
	private function startElement($bMB, $xmlChunk, $origChunk)
	{
		static $search = array(
				"'&(quot|#34);'i",
				"'&(lt|#60);'i",
				"'&(gt|#62);'i",
				"'&(amp|#38);'i",
			);

		static $replace = array(
				"\"",
				"<",
				">",
				"&",
			);

		$p = strpos($xmlChunk, ">");
		if($p !== false)
		{
			if(substr($xmlChunk, $p - 1, 1)=="/")
				$elementName = substr($xmlChunk, 0, $p-1);
			else
				$elementName = substr($xmlChunk, 0, $p);

			if(($ps = strpos($elementName, " "))!==false)
			{
				$elementAttrs = substr($elementName, $ps+1);
				$elementName = substr($elementName, 0, $ps);
			}
			else
			{
				$elementAttrs = "";
			}

			if(substr($xmlChunk, $p - 1, 1) != "/")
			{
				$this->elementStack[] = $elementName;
				$this->positionStack[] = $this->filePosition - ($bMB? mb_strlen($origChunk, 'latin1'): strlen($origChunk)) - 1;

				if (isset($this->endNodes[$elementName]))
				{
					$xmlPath = implode("/", $this->elementStack);
					if (isset($this->elementHandlers[$xmlPath]))
					{
						$attributes = array();
						if ($elementAttrs !== "")
						{
							preg_match_all("/(\\S+)\\s*=\\s*[\"](.*?)[\"]/s", $elementAttrs, $attrs_tmp);
							if(strpos($elementAttrs, "&")===false)
							{
								foreach($attrs_tmp[1] as $i=>$attrs_tmp_1)
									$attributes[$attrs_tmp_1] = $attrs_tmp[2][$i];
							}
							else
							{
								foreach($attrs_tmp[1] as $i=>$attrs_tmp_1)
									$attributes[$attrs_tmp_1] = preg_replace($search, $replace, $attrs_tmp[2][$i]);
							}
						}

						foreach ($this->elementHandlers[$xmlPath] as $callableHandler)
						{
							call_user_func_array($callableHandler, array(
								$xmlPath,
								$attributes,
							));
						}
					}
				}
			}
		}
	}
	/**
	 * Winds tree stack back. Calls (if neccessary) node handlers.
	 *
	 * @param string $xmlChunk
	 * @return void
	 *
	 */
	private function endElement($xmlChunk)
	{
		$elementName = array_pop($this->elementStack);
		$elementPosition  = array_pop($this->positionStack);

		if (isset($this->endNodes[$elementName]))
		{
			$xmlPath = implode("/", $this->elementStack)."/".$elementName;
			if (isset($this->nodeHandlers[$xmlPath]))
			{
				$xmlObject = $this->readXml($elementPosition, $this->filePosition);
				if (is_object($xmlObject))
				{
					foreach ($this->nodeHandlers[$xmlPath] as $callableHandler)
					{
						call_user_func_array($callableHandler, array(
							$xmlObject,
						));
					}
				}
			}
		}
	}
	/**
	 * Reads xml chunk from the file preserving it's position
	 *
	 * @param int $startPosition
	 * @param int $endPosition
	 * @return CDataXML|false
	 */
	private function readXml($startPosition, $endPosition)
	{
		$xmlChunk = $this->readFilePart($startPosition, $endPosition);
		if ($xmlChunk && $this->fileCharset)
		{
			$error = "";
			$xmlChunk = \Bitrix\Main\Text\Encoding::convertEncoding($xmlChunk, $this->fileCharset, LANG_CHARSET, $error);
		}

		$xmlObject = new CDataXML;
		if ($xmlObject->loadString($xmlChunk))
			return $xmlObject;
		else
			return false;
	}

	/**
	 * Reads part of the file preserving it's position
	 *
	 * @param int $startPosition
	 * @param int $endPosition
	 * @return CDataXML|false
	 */
	public function readFilePart($startPosition, $endPosition)
	{
		$savedPosition = ftell($this->fileHandler);
		fseek($this->fileHandler, $startPosition);
		$xmlChunk = fread($this->fileHandler, $endPosition - $startPosition);
		fseek($this->fileHandler, $savedPosition);
		return $xmlChunk;
	}
}
