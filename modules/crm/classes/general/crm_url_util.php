<?php
class CCrmUrlUtil
{
	public static function GetUrlScheme($url)
	{
		$url = trim(strval($url));
		$colonOffset = strpos($url, ':');
		if($colonOffset === false)
		{
			$colonOffset = -1;
		}

		$slashOffset = strpos($url, '/');
		if($slashOffset === false)
		{
			$slashOffset = -1;
		}

		return $colonOffset > 0 && ($slashOffset < 0 || $colonOffset < $slashOffset)
			? strtolower(substr($url, 0, $colonOffset)) : '';
	}
	public static function HasScheme($url)
	{
		return self::GetUrlScheme($url) !== '';
	}
	public static function IsSecureUrl($url)
	{
		$scheme = self::GetUrlScheme($url);
		return $scheme === '' || preg_match('/^(?:(?:ht|f)tp(?:s)?){1}/i', $scheme) === 1;
	}
	public static function IsAbsoluteUrl($url)
	{
		return self::GetUrlScheme($url) !== '';
	}
	public static function ToAbsoluteUrl($url)
	{
		$url = trim(strval($url));

		if($url === '')
		{
			return '';
		}
		elseif(self::GetUrlScheme($url) !== '')
		{
			return $url;
		}

		$scheme = (CMain::IsHTTPS() ? 'https' : 'http');

		$host = '';
		if(defined('SITE_SERVER_NAME') && is_string(SITE_SERVER_NAME))
		{
			$host = SITE_SERVER_NAME;
		}

		if($host === '')
		{
			$host = COption::GetOptionString('main', 'server_name', '');
		}

		if($host === '')
		{
			$host = $_SERVER['SERVER_NAME'];
		}

		$port = intval($_SERVER['SERVER_PORT']);

		if(preg_match('/^\//', $url))
		{
			$url = substr($url, 1);
		}

		return $scheme.'://'.$host.(($port !== 80 && $port !== 443) ? ':'.$port : '').'/'.$url;
	}
	public static function UrnEncode($str, $charset = false)
	{
		global $APPLICATION;

		$result = '';
		$arParts = preg_split("#(://|:\\d+/|/|\\?|=|&)#", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

		if($charset === false)
		{
			$charset = LANG_CHARSET;
		}

		foreach($arParts as $i => $part)
		{
			$result .= ($i % 2)
				? $part
				: rawurlencode($APPLICATION->ConvertCharset($part, $charset, 'UTF-8'));
			//$result .= ($i % 2) ? $part : urlencode(iconv('windows-1251', 'UTF-8', $part));
		}

		return $result;
	}
	public static function AddUrlParams($url, $params)
	{
		if(empty($params))
		{
			return $url;
		}

		$query = array();
		foreach($params as $k => &$v)
		{
			$query[] = $k.'='.$v;
		}
		unset($v);

		return $url.(strpos($url, '?') === false ? '?' : '&').implode('&', $query);
	}
	public static function PrepareCallToUrl($value)
	{
		return CCrmCallToUrl::Format($value);
	}
}

class CCrmCallToUrl
{
	const Undefined = 0;
	const Standard = 1;
	const Slashless = 2;
	const Custom = 3;
	const Bitrix = 4;

	private static $CUSTOM_SETTINGS = null;
	private static $ALL_DESCRIPTIONS = null;
	private static $URL_TEMPLATE = null;
	private static $CLICK_HANDLER = null;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Bitrix;
	}

	public static function GetFormat($default)
	{
		$value = intval(COption::GetOptionString('crm', 'callto_frmt', '0'));
		return self::IsDefined($value) ? $value : $default;
	}

	public static function SetFormat($format)
	{
		$format = intval($format);
		return self::IsDefined($format) ? COption::SetOptionString('crm', 'callto_frmt', $format) : false;
	}

	public static function GetCustomSettings()
	{
		if(self::$CUSTOM_SETTINGS !== null)
		{
			return self::$CUSTOM_SETTINGS;
		}

		$s = COption::GetOptionString('crm', 'callto_custom_settings', '');
		return (self::$CUSTOM_SETTINGS = $s !== '' ? unserialize($s) : array());
	}

	public static function SetCustomSettings($settings)
	{
		if(!is_array($settings))
		{
			return false;
		}

		COption::SetOptionString('crm', 'callto_custom_settings', serialize($settings));
		self::$CUSTOM_SETTINGS = null;
		self::$URL_TEMPLATE = null;
		self::$CLICK_HANDLER = null;
		return true;
	}
	public static function NormalizeNumberIfRequired($number)
	{
		$settings =  self::GetCustomSettings();
		if(!(isset($settings['NORMALIZE_NUMBER']) && $settings['NORMALIZE_NUMBER'] === 'Y'))
		{
			return strval($number);
		}

		return preg_replace('/[^0-9\|\+\,]/', '', strval($number));
	}
	public static function Format($value)
	{
		$value = self::NormalizeNumberIfRequired($value);

		$format = self::GetFormat(self::Slashless);
		if($format !== self::Custom )
		{
			if($format === self::Bitrix)
			{
				return "bx://callto/phone/{$value}";
			}
			if($format === self::Slashless)
			{
				return "callto:{$value}";
			}
			return "callto://{$value}";
		}

		if(!self::$URL_TEMPLATE)
		{
			self::$URL_TEMPLATE = new CCrmUrlTemplate();
			$settings =  self::GetCustomSettings();
			self::$URL_TEMPLATE->SetTemplate(isset($settings['URL_TEMPLATE']) ? $settings['URL_TEMPLATE'] : 'callto:[phone]');
		}
		return self::$URL_TEMPLATE->Build(array('PHONE' => $value));
	}
	public static function PrepareLinkAttributes($value)
	{
		$value = self::NormalizeNumberIfRequired($value);
		$format = self::GetFormat(self::Slashless);

		if($format === self::Bitrix)
		{
			return array(
				'HREF' => "callto://{$value}",
				'ONCLICK' => "if(typeof(BXIM) !== 'undefined') { BXIM.phoneTo('{$value}'); return BX.PreventDefault(); }"
			);
		}

		if($format !== self::Custom )
		{
			return array(
				'HREF' => $format === self::Slashless ? "callto:{$value}" : "callto://{$value}",
				'ONCLICK' => ''
			);
		}

		if(!self::$URL_TEMPLATE || !self::$CLICK_HANDLER)
		{
			$settings =  self::GetCustomSettings();

			self::$URL_TEMPLATE = new CCrmUrlTemplate();
			self::$URL_TEMPLATE->SetTemplate(isset($settings['URL_TEMPLATE']) ? $settings['URL_TEMPLATE'] : 'callto:[phone]');

			self::$CLICK_HANDLER = new CCrmUrlTemplate();
			self::$CLICK_HANDLER->SetTemplate(isset($settings['CLICK_HANDLER']) ? $settings['CLICK_HANDLER'] : '');
		}

		$templateParams = array('PHONE' => $value);
		return array(
			'HREF' => self::$URL_TEMPLATE->Build($templateParams),
			'ONCLICK' => self::$CLICK_HANDLER->Build($templateParams)
		);
	}
	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			IncludeModuleLangFile(__FILE__);

			self::$ALL_DESCRIPTIONS = array(
				self::Standard => GetMessage('CRM_CALLTO_URL_STANDARD'),
				self::Slashless => GetMessage('CRM_CALLTO_URL_SLASHLESS'),
				self::Bitrix => GetMessage('CRM_CALLTO_URL_BITRIX'),
				self::Custom => GetMessage('CRM_CALLTO_URL_CUSTOM')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}
}

class CCrmUrlTemplate
{
	private static $CONTAINER_TAGS = array('URLENCODE', 'HTMLENCODE', 'JSENCODE', 'SHA1', 'MD4', 'MD5');
	private $isReady = false;
	private $template = '';
	private $nodes = array();

	public function GetTemplate()
	{
		return $this->template;
	}
	public function SetTemplate($template)
	{
		$this->template = strval($template);
	}

	public function Build($params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		if(!$this->isReady)
		{
			$this->Prepare();
		}

		if(empty($this->nodes))
		{
			return $this->template;
		}

		if(!isset($params['PHONE']))
		{
			$params['PHONE'] = '';
		}

		$output = array();
		self::BuildNodes($this->nodes, $params, $output);
		return !empty($output) ? implode('', $output) : '';
	}

	private static function IsChildNodesSupported($nodeName)
	{
		$nodeName = strtoupper(strval($nodeName));
		return in_array($nodeName, self::$CONTAINER_TAGS, true);
	}

	private static function BuildNode(&$node, &$params, &$output)
	{
		$nodeType = $node['nodeType'];
		if($nodeType === 2)
		{
			$output[] = $node['content'];
		}
		elseif($nodeType === 1)
		{
			$nodeName = $node['name'];

			if($nodeName === 'PHONE')
			{
				$output[] = $params['PHONE'];
			}
			if(in_array($nodeName, self::$CONTAINER_TAGS, true))
			{
				$childrenOutput = array();
				if(isset($node['nodes']) && is_array($node['nodes']))
				{
					self::BuildNodes($node['nodes'], $params, $childrenOutput);
				}

				if(!empty($childrenOutput))
				{
					$childrenText = implode('', $childrenOutput);
					if($nodeName === 'URLENCODE')
					{
						$output[] = urlencode($childrenText);
					}
					elseif($nodeName === 'HTMLENCODE')
					{
						$output[] = htmlspecialcharsbx($childrenText);
					}
					elseif($nodeName === 'JSENCODE')
					{
						$output[] = CUtil::JSEscape($childrenText);
					}
					elseif($nodeName === 'SHA1')
					{
						$output[] = hash('sha1', $childrenText);
					}
					elseif($nodeName === 'MD4')
					{
						$output[] = hash('md4', $childrenText);
					}
					elseif($nodeName === 'MD5')
					{
						$output[] = hash('md5', $childrenText);
					}
				}
				unset($childrenOutput);
			}
		}
	}

	private static function BuildNodes(&$nodes, &$params, &$output)
	{
		foreach($nodes as &$node)
		{
			self::BuildNode($node, $params, $output);
		}
		unset($node);
	}

	private function Prepare()
	{
		if($this->isReady)
		{
			return;
		}

		$this->nodes = array();

		$result = preg_match_all('/\[\s*\/?\s*[a-z0-9_]+\s*\/?\s*\]/i',
			$this->template,
			$matches,
			PREG_SET_ORDER|PREG_OFFSET_CAPTURE
		);

		if(!(is_int($result) && $result > 0))
		{
			return;
		}

		$curNode = null;
		$lastNode = null;
		$offset = 0;
		foreach($matches as &$match)
		{
			$m = &$match[0];
			if(!(is_array($m) && count($m) === 2))
			{
				continue;
			}

			$tag = $m[0];
			$tagLength = strlen($tag);
			$tagName = trim(substr($tag, 1, $tagLength - 2));
			$slashPos = strpos($tagName, '/');
			$isEnd = $slashPos === 0;
			$isSelfClosing = $slashPos === strlen($tagName) - 1;

			if($isEnd)
			{
				$tagName = trim(substr($tagName, 1));
			}
			elseif($isSelfClosing)
			{
				$tagName = trim(substr($tagName, 0, strlen($tagName) - 1));
			}

			if(!$isSelfClosing && !self::IsChildNodesSupported($tagName))
			{
				$isSelfClosing = true;
			}

			$node = array(
				'nodeType' => 1, //object
				'name' => strtoupper($tagName),
				'offset' => intval($m[1]),
				'length' => $tagLength,
				'isEnd' => $isEnd,
				'isSelfClosing' => $isSelfClosing,
				'parent' => null,
				'nodes' => array(),
				'end' => null
			);

			$lastNode = &$node;

			if($node['offset'] > $offset)
			{
				$textNode = array(
					'nodeType' => 2, //text
					'content' => substr($this->template, $offset, $node['offset'] - $offset)
				);

				if($curNode)
				{
					$curNode['nodes'][] = &$textNode;
					$textNode['parent'] = &$curNode;
				}
				else
				{
					$this->nodes[] = &$textNode;
				}
			}
			$offset = $node['offset'] + $node['length'];

			if(!$isEnd)
			{
				if($curNode)
				{
					$curNode['nodes'][] = &$node;
					$node['parent'] = &$curNode;
				}
				else
				{
					$this->nodes[] = &$node;
				}

				if(!$isSelfClosing)
				{
					unset($curNode);
					$curNode = &$node;
				}
			}
			else
			{
				//End tags without opened tag will be ignored.
				$parent = &$curNode;
				while($parent)
				{
					if($parent['name'] === $node['name'])
					{
						$parent['end'] = &$node;
						$node['parent'] = &$parent;
						break;
					}

					$parent = &$parent['parent'];
				}
				unset($parent);
			}
			unset($node, $textNode);
		}
		unset($match, $m, $curNode);

		if($lastNode)
		{
			$endPos = $lastNode['offset'] + $lastNode['length'];
			if($endPos < (strlen($this->template) - 1))
			{
				$this->nodes[] = array(
					'nodeType' => 2, //text
					'content' => substr($this->template, $endPos)
				);
			}
		}
		unset($lastNode);

		$this->isReady = true;
	}
}
