<?php
namespace Bitrix\Main\Page;

use Bitrix\Main;
use Bitrix\Main\IO;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\BinaryString;

class AssetMode
{
	const STANDARD = 1;
	const COMPOSITE = 2;
	const SPECIAL = 4;
	const ALL = 7;
}

class AssetLocation
{
	const BEFORE_CSS = 'BEFORE_CSS';
	const AFTER_CSS = 'AFTER_CSS';
	const AFTER_JS_KERNEL = 'AFTER_JS_KERNEL';
	const AFTER_JS = 'AFTER_JS';
}

class AssetShowTargetType
{
    const ALL = 0;
    const KERNEL = 1;
    const TEMPLATE_PAGE = 2;
    const BODY = 3;
}

class Asset
{
	private static $instance;

	/** @var array Contains target list */
	private $targetList;

	/** @var array pointer to current target */
	private $target;

	/** @var array of css files */
	private $css = array();

	/** @var array of js files */
	private $js = array();

	/** @var array of inline string */
	private $strings = array(
		AssetLocation::BEFORE_CSS => array(),
		AssetLocation::AFTER_CSS => array(),
		AssetLocation::AFTER_JS_KERNEL => array(),
		AssetLocation::AFTER_JS => array(),
	);

	/** @var array Information about kernel modules */
	private $moduleInfo = array('CSS' => array(), 'JS' => array());
	private $kernelAsset = array('CSS' => array(), 'JS' => array());
	private $assetList = array('CSS' => array(), 'JS' => array());
	private $fileList = array('CSS' => array(), 'JS' => array());
	private $mode = AssetMode::STANDARD;

	/** @var string Domain name for css files */
	private $cssDomain = '';

	/** @var string Domain name for js files */
	private $jsDomain = '';

	private $ajax;
	private $isIE;

	private $maxStylesCnt = 20;
	private $xhtmlStyle = true;

	private $headString = false;
	private $headScript = false;
	private $bodyScript = false;

	private $moveJsToBody = null;

	private $siteTemplateID = '';
	private $templatePath = '';
	private $documentRoot = '';

	const MAX_ADD_CSS_SELECTOR = 3950;
	const MAX_CSS_SELECTOR = 4000;

	const SOURCE_MAP_TAG = "\n//# sourceMappingURL=";
	const HEADER_START_TAG = "; /* Start:\"";
	const HEADER_END_TAG = "\"*/";

	private function __construct()
	{
		//use self::getInstance()
		$this->targetList['KERNEL'] = array(
			'NAME' => 'KERNEL',
			'START' => true,
			'CSS_RES' => array(),
			'JS_RES' => array(),
			'CSS_LIST' => array(),
			'JS_LIST' => array(),
			'STRING_LIST' => array(),
			'UNIQUE' => true,
			'PREFIX' => 'kernel',
			'BODY' => false,
			'MODE' => AssetMode::ALL
		);

		$this->targetList['BODY'] = $this->targetList['TEMPLATE'] = $this->targetList['PAGE'] = $this->targetList['KERNEL'];
		$this->targetList['PAGE']['NAME'] = 'PAGE';
		$this->targetList['PAGE']['UNIQUE'] = false;
		$this->targetList['PAGE']['PREFIX'] = 'page';
		$this->targetList['TEMPLATE']['NAME'] = 'TEMPLATE';
		$this->targetList['TEMPLATE']['UNIQUE'] = false;
		$this->targetList['TEMPLATE']['PREFIX'] = 'template';
		$this->targetList['BODY']['NAME'] = 'BODY';
		$this->targetList['BODY']['UNIQUE'] = false;
		$this->targetList['BODY']['PREFIX'] = 'body';

		/** fix current order of kernel modules */
		$this->targetList['KERNEL']['CSS_LIST']['KERNEL_main'] = array();
		$this->targetList['KERNEL']['JS_LIST']['KERNEL_main'] = array();

		$this->target = &$this->targetList['TEMPLATE'];

		$ieVersion = IsIE();
		$this->isIE = ($ieVersion !== false && $ieVersion < 10);
		$this->documentRoot = Main\Loader::getDocumentRoot();
	}

	private function __clone()
	{
		//you can't clone it
	}

	/**
	 * Singleton instance.
	 *
	 * @return Asset
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new Asset();
		}

		return self::$instance;
	}

	/**
	 * Set mode for current target
	 * @param int $mode
	 */
	public function setMode($mode = AssetMode::STANDARD)
	{
		$this->mode = $mode;
	}

	/**
	 * Returns gzip enabled
	 *
	 * @return bool
	 */
	public static function gzipEnabled()
	{
		static $bGzip = null;
		if ($bGzip === null)
		{
			$bGzip = (
				Option::get('main','compres_css_js_files', 'N') == 'Y'
				&& extension_loaded('zlib')
				&& function_exists('gzcompress')
			);
		}
		return $bGzip;
	}

	/**
	 * @param $value bool - use xhtml html style
	 */
	public function setXhtml($value)
	{
		$this->xhtmlStyle = ($value === true);
	}

	/**
	 * @param $value int count of css files showed inline fore ie
	 */
	public function setMaxCss($value)
	{
		$value = intval($value);
		if($value > 0)
		{
			$this->maxStylesCnt = $value;
		}
	}

	/**
	 * Set ShowHeadString in page or not
	 * @param bool $value
	 */
	public function setShowHeadString($value = true)
	{
		$this->headString = $value;
	}

	/**
	 * Return true if ShowHeadString exist in page
	 * @return bool
	 */
	public function getShowHeadString()
	{
		return $this->headString;
	}

	/**
	 *  Set ShowHeadScript in page or not
	 * @param bool $value
	 */
	public function setShowHeadScript($value = true)
	{
		$this->headScript = $value;
	}

	/**
	 * Return true if ShowHeadScript exist in page
	 * @param bool $value
	 */
	public function setShowBodyScript($value = true)
	{
		$this->bodyScript = $value;
	}

	/**
	 * Set Ajax mode and restart instance
	 * @return Asset
	 */
	static public function setAjax()
	{
		$newInstance = self::$instance = new Asset();
		$newInstance->ajax = true;
		return $newInstance;
	}

	/**
	 * @param $domain string Domain name
	 */
	public function setCssDomain($domain)
	{
		$this->cssDomain = $domain;
	}


	/**
	 * @param $domain string Domain name
	 */
	public function setJsDomain($domain)
	{
		$this->jsDomain = $domain;
	}

	/**
	 * @return string - Return current set name
	 */
	public function getTargetName()
	{
		return $this->target['NAME'];
	}

	/**
	 * @return mixed Return current set
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * Temporary fix for update system. Need to delete later
	 * @param string $id
	 * @param int $mode
	 * @return bool
	 */
	public function startSet($id = '', $mode = AssetMode::ALL)
	{
		return $this->startTarget($id, $mode);
	}

	/**
	 * Start new target for asset
	 * @param string $id
	 * @param int $mode
	 * @return bool
	 */
	public function startTarget($id = '', $mode = AssetMode::ALL)
	{
		$id = ToUpper(trim($id));
		if(strlen($id) <= 0)
		{
			return false;
		}

		if(
			($this->target['NAME'] == 'TEMPLATE' || $this->target['NAME'] == 'PAGE')
			&& ($id == 'TEMPLATE' || $id == 'PAGE')
		)
		{
			$this->target['START'] = false;
			$this->targetList[$id]['START'] = true;
			$this->target = &$this->targetList[$id];
		}
		elseif(!($id == 'TEMPLATE' || $id == 'PAGE'))
		{
			if(isset($this->targetList[$id]))
			{
				return false;
			}

			$this->stopTarget();
			$this->targetList[$id] = array(
				'NAME' => $id,
				'START' => true,
				'JS_RES' => array(),
				'CSS_RES' => array(),
				'JS_LIST' => array(),
				'CSS_LIST' => array(),
				'STRING_LIST' => array(),
				'BODY' => false,
				'UNIQUE' => false,
				'MODE' => $mode
			);
			$this->target = &$this->targetList[$id];
		}
		return true;
	}

	/**
	 * Stop current target
	 * @param string $id
	 * @return bool
	 */
	public function stopTarget($id = '')
	{
		$id = ToUpper(trim($id));
		if($id == 'TEMPLATE')
		{
			if($this->target['NAME'] == 'TEMPLATE')
			{
				$this->target['START'] = false;
				$this->target = &$this->targetList['PAGE'];
			}
			else
			{
				$this->targetList['TEMPLATE']['START'] = false;
			}
		}
		else
		{
			if($this->target['NAME'] == 'TEMPLATE')
			{
				return false;
			}
			elseif($this->targetList['TEMPLATE']['START'])
			{
				$this->target['START'] = false;
				$this->target = &$this->targetList['TEMPLATE'];
			}
			else
			{
				$this->target['START'] = false;
				$this->target = &$this->targetList['PAGE'];
			}
		}

		return true;
	}

	/**
	 * Return information about target assets
	 * @param $id string
	 * @param $mode mixed
	 * @return array
	 */
	public function getAssetInfo($id, $mode)
	{
		$res = array(
			'JS' => array(),
			'CSS' => array(),
			'STRINGS' => array()
		);

		$id = ToUpper(trim($id));
		if(!isset($this->targetList[$id]))
		{
			return $res;
		}

		static $cacheInfo = array(
			AssetMode::STANDARD => null,
			AssetMode::COMPOSITE => null,
			AssetMode::ALL => null,
			AssetMode::SPECIAL => null
		);

		if($cacheInfo[$mode] === null)
		{
			$cacheInfo[$mode] = array('JS' => array(), 'CSS' => array(), 'STRINGS' => array());

			foreach($this->strings as $locationID => $location)
			{
				foreach($location as $key => $item)
				{
					if($mode == $item['MODE'])
					{
						$cacheInfo[$mode]['STRINGS'][$item['TARGET'][0]][] = $item['CONTENT'];
					}
				}
			}

			$jsList = $this->getTargetList('JS');
			foreach($jsList as $set)
			{
				if($mode === $set['MODE'])
				{
					if(isset($this->fileList['JS'][$set['NAME']]['FILES']))
					{
						foreach($this->fileList['JS'][$set['NAME']]['FILES'] as $item)
						{
							$cacheInfo[$mode]['JS'][$set['NAME']][] = $item;
							if($set['PARENT_NAME'] == 'KERNEL')
							{
								foreach($this->targetList['KERNEL']['JS_LIST'][$set['NAME']]['WHERE_USED'] as $target => $tmp)
								{
									$cacheInfo[$mode]['JS'][$target][] = $item;
								}
							}
						}
					}
				}
				elseif(isset($this->fileList['JS'][$set['NAME']]['UP_NEW_FILES']))
				{
					foreach($this->fileList['JS'][$set['NAME']]['UP_NEW_FILES'] as $item)
					{
						$cacheInfo[$mode]['JS'][$set['NAME']][] = $this->jsDomain.$item['FULL_PATH'];
						if($set['PARENT_NAME'] == 'KERNEL')
						{
							foreach($this->targetList['KERNEL']['JS_LIST'][$set['NAME']]['WHERE_USED'] as $target => $tmp)
							{
								$cacheInfo[$mode]['JS'][$target][] = $this->jsDomain.$item['FULL_PATH'];
							}
						}
					}
				}
			}

			$cssList = $this->getTargetList('CSS');
			foreach($cssList as $set)
			{
				if($mode === $set['MODE'])
				{
					if(isset($this->fileList['CSS'][$set['NAME']]['FILES']))
					{
						foreach($this->fileList['CSS'][$set['NAME']]['FILES'] as $item)
						{
							$cacheInfo[$mode]['CSS'][$set['NAME']][] = $item;
							if($set['PARENT_NAME'] == 'KERNEL')
							{
								foreach($this->targetList['KERNEL']['CSS_LIST'][$set['NAME']]['WHERE_USED'] as $target => $tmp)
								{
									$cacheInfo[$mode]['CSS'][$target][] = $item;
								}
							}
						}
					}
				}
				elseif(isset($this->fileList['CSS'][$set['NAME']]['UP_NEW_FILES']))
				{
					foreach($this->fileList['CSS'][$set['NAME']]['UP_NEW_FILES'] as $item)
					{
						$cacheInfo[$mode]['CSS'][$set['NAME']][] = $this->cssDomain.$item['FULL_PATH'];
						if($set['PARENT_NAME'] == 'KERNEL')
						{
							foreach($this->targetList['KERNEL']['CSS_LIST'][$set['NAME']]['WHERE_USED'] as $target => $tmp)
							{
								$cacheInfo[$mode]['CSS'][$target][] = $this->cssDomain.$item['FULL_PATH'];
							}
						}
					}
				}
			}
		}

		$res['STRINGS'] = $cacheInfo[$mode]['STRINGS'][$id];
		$res['JS'] = $cacheInfo[$mode]['JS'][$id];
		$res['CSS'] = $cacheInfo[$mode]['CSS'][$id];
		return $res;
	}

	/**
	 * Set composite mode for set
	 * @param string $id
	 * @return bool
	 */
	public function compositeTarget($id = '')
	{
		$id = ToUpper(trim($id));
		if(strlen($id) <= 0 || !isset($this->targetList[$id]))
		{
			return false;
		}
		else
		{
			$this->targetList[$id]['MODE'] = AssetMode::COMPOSITE;
		}
		return true;
	}

	/**
	 * @param string $type
	 * @return array Return set list with subsets
	 */
	public function getTargetList($type = 'CSS')
	{
		static $res = array('CSS_LIST' => null, 'JS_LIST' => null);
		$key = ($type == 'CSS' ? 'CSS_LIST' : 'JS_LIST');

		if($res[$key] === null)
		{
			foreach($this->targetList as $targetName => $targetInfo)
			{
				$res[$key][] = array(
					'NAME' => $targetName,
					'PARENT_NAME' => $targetName,
					'UNIQUE' => $targetInfo['UNIQUE'],
					'PREFIX' => $targetInfo['PREFIX'],
					'MODE' => $targetInfo['MODE']
				);

				if(!empty($targetInfo[$key]))
				{
					foreach($targetInfo[$key] as $subSetName => $val)
					{
						$res[$key][] = array(
							'NAME' => $subSetName,
							'PARENT_NAME' => $targetName,
							'UNIQUE' => $val['UNIQUE'],
							'PREFIX' => $val['PREFIX'],
							'MODE' => $val['MODE']
						);
					}
				}
			}
		}
		return $res[$key];
	}

	/**
	 * Add string asset
	 * @param $str string
	 * @param bool $unique
	 * @param string $location
	 * @param null $mode
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод добавляет строку в секцию <code>&lt;head&gt;…&lt;/head&gt;</code> сайта.</p> <p>Аналог метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addheadstring.php" >CMain::AddHeadString</a> в старом ядре.</p>
	*
	*
	* @param mixed $str  Строка, которая будет добавлена
	*
	* @param boolean $unique = false 
	*
	* @param string $location = \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL 
	*
	* @param null $mode = null 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/asset/addstring.php
	* @author Bitrix
	*/
	public function addString($str, $unique = false, $location = AssetLocation::AFTER_JS_KERNEL, $mode = null)
	{
		if($str == '')
		{
			return false;
		}

		if($unique)
		{
			$chkSum = md5($str);
			$this->strings[$location][$chkSum]['CONTENT'] = $str;
			$this->strings[$location][$chkSum]['TARGET'][] = $this->getTargetName();
			$this->strings[$location][$chkSum]['MODE'] = $mode;
		}
		else
		{
			$this->strings[$location][] = array('CONTENT' => $str, 'MODE' => $mode, 'TARGET' => array($this->getTargetName()));
		}
		return true;
	}

	/**
	 * Return strings assets
	 * @param string $location
	 * @return string
	 */
	public function getStrings($location = AssetLocation::AFTER_JS_KERNEL)
	{
		static $firstExec = true;
		if($firstExec)
		{
			$this->prepareString();
			$firstExec = false;
		}

		$res = '';
		if($location == AssetLocation::AFTER_CSS && \CJSCore::IsCoreLoaded())
		{
			$res = "<script type=\"text/javascript\">if(!window.BX)window.BX={message:function(mess){if(typeof mess=='object') for(var i in mess) BX.message[i]=mess[i]; return true;}};</script>\n";
		}

		if(isset($this->strings[$location]))
		{
			foreach($this->strings[$location] as $item)
			{
				if($this->mode & $item['MODE'])
				{
					$res .= $item['CONTENT']."\n";
				}
			}
		}

		return ($res == '') ? '' : $res."\n";
	}

	/**
	 * Add some css to asset
	 * @param $path
	 * @param bool $additional
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод добавляет <b>css</b> в секцию <code>&lt;head&gt;…&lt;/head&gt;</code> сайта.</p> <p>Аналог метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setadditionalcss.php" >CMain::SetAdditionalCSS</a> в старом ядре.</p>
	*
	*
	* @param mixed $path  
	*
	* @param boolean $additional = false 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/asset/addcss.php
	* @author Bitrix
	*/
	public function addCss($path, $additional = false)
	{
		if(strlen($path) <= 0)
		{
			return false;
		}

		$css = $this->getAssetPath($path);
		$this->css[$css]['TARGET'][] = $this->getTargetName();
		$this->css[$css]['ADDITIONAL'] = (isset($this->css[$css]['ADDITIONAL']) && $this->css[$css]['ADDITIONAL'] ? true : $additional);
		return true;
	}

	/**
	 * Add some js to asset
	 * @param $path
	 * @param bool $additional
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод добавляет <b>js</b> в секцию <code>&lt;head&gt;…&lt;/head&gt;</code> сайта.</p> <p>Аналог <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addheadscript.php" >CMain::AddHeadScript</a> в старом ядре.</p>
	*
	*
	* @param mixed $path  
	*
	* @param boolean $additional = false 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/asset/addjs.php
	* @author Bitrix
	*/
	public function addJs($path, $additional = false)
	{
		if(strlen($path) <= 0)
		{
			return false;
		}

		$js = $this->getAssetPath($path);
		$this->js[$js]['TARGET'][] = $this->getTargetName();
		$this->js[$js]['ADDITIONAL'] = (isset($this->js[$js]['ADDITIONAL']) && $this->js[$js]['ADDITIONAL'] ? true : $additional);
		return true;
	}

	/**
	 * Replace path to includes in css
	 * @param $content
	 * @param $path
	 * @return mixed
	 */
	public static function fixCssIncludes($content, $path)
	{
		$path = IO\Path::getDirectory($path);
		$content = preg_replace_callback(
			'#([;\s:]*(?:url|@import)\s*\(\s*)(\'|"|)(.+?)(\2)\s*\)#si',
			create_function('$matches', 'return $matches[1].Bitrix\Main\Page\Asset::replaceUrlCSS($matches[3], $matches[2], "'.addslashes($path).'").")";'),
			$content
		);

		$content = preg_replace_callback(
			'#(\s*@import\s*)([\'"])([^\'"]+)(\2)#si',
			create_function('$matches', 'return $matches[1].Bitrix\Main\Page\Asset::replaceUrlCSS($matches[3], $matches[2],"'.addslashes($path).'");'),
			$content
		);

		return $content;
	}

	/**
	 * Group some js modules
	 * @param string $from
	 * @param string $to
	 */
	public function groupJs($from = '', $to = '')
	{
		if(empty($from) || empty($to))
		{
			return;
		}

		$to = $this->movedJsTo($to);
		if(array_key_exists($from, $this->moduleInfo['JS']))
		{
			$this->moduleInfo['JS'][$from]['MODULE_ID'] = $to;
		}
		else
		{
			$this->moduleInfo['JS'][$from] = array('MODULE_ID' => $to, 'FILES_INFO' => false, 'BODY' => false);
		}

		foreach($this->moduleInfo['JS'] as $moduleID => $moduleInfo)
		{
			if($moduleInfo['MODULE_ID'] == $from)
			{
				$this->moduleInfo['JS'][$moduleID]["MODULE_ID"] = $to;
			}
		}
	}

	/**
	 * Group some css modules
	 * @param string $from
	 * @param string $to
	 */
	public function groupCss($from = '', $to = '')
	{
		if(empty($from) || empty($to))
		{
			return;
		}

		$to = $this->movedCssTo($to);
		if(array_key_exists($from, $this->moduleInfo['CSS']))
		{
			$this->moduleInfo['CSS'][$from]['MODULE_ID'] = $to;
		}
		else
		{
			$this->moduleInfo['CSS'][$from] = array('MODULE_ID' => $to, 'FILES_INFO' => false);
		}

		foreach($this->moduleInfo['CSS'] as $moduleID => $moduleInfo)
		{
			if($moduleInfo['MODULE_ID'] == $from)
			{
				$this->moduleInfo['CSS'][$moduleID]["MODULE_ID"] = $to;
			}
		}
	}

	/**
	 * @param $to string Module name
	 * @return string Return module name
	 */
	private function movedJsTo($to)
	{
		if(isset($this->moduleInfo['JS'][$to]['MODULE_ID']) && $this->moduleInfo['JS'][$to]['MODULE_ID'] != $to)
		{
			$to = $this->movedJsTo($this->moduleInfo['JS'][$to]['MODULE_ID']);
		}
		return $to;
	}

	/**
	 * @param $to string Module name
	 * @return string Return module name
	 */
	private function movedCssTo($to)
	{
		if(isset($this->moduleInfo['CSS'][$to]['MODULE_ID']) && $this->moduleInfo['CSS'][$to]['MODULE_ID'] != $to				)
		{
			$to = $this->movedCssTo($this->moduleInfo['JS'][$to]['MODULE_ID']);
		}
		return $to;
	}

	/**
	 * Move js kernel module to BODY
	 * @param string $module
	 */
	public function moveJs($module = '')
	{
		if (empty($module) || $module === "main")
		{
			return;
		}

		if (array_key_exists($module, $this->moduleInfo['JS']))
		{
			$this->moduleInfo['JS'][$module]['BODY'] = true;
		}
		else
		{
			$this->moduleInfo['JS'][$module] = array('MODULE_ID' => $module, 'FILES_INFO' => false, 'BODY' => true);
		}
	}

	/**
	 *
	 * Enables or disables the moving all of scripts to the body.
	 * @param bool $flag
	 */
	public function setJsToBody($flag)
	{
		$this->moveJsToBody = (bool)$flag;
	}

	protected function getJsToBody()
	{
		if($this->moveJsToBody === null)
		{
			$this->moveJsToBody = Option::get("main", "move_js_to_body") === "Y" && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true);
		}
		return $this->moveJsToBody;
	}

	/**
	 *
	 * Moves all of scripts in front of </body>
	 * @param string $content
	 *
	 * @internal
	 */
	public function moveJsToBody(&$content)
	{
		if (!$this->getJsToBody())
		{
			return;
		}

		$js = "";
		$offset = 0;
		$newContent = "";
		$areas = $this->getScriptAreas($content);
		foreach ($areas as $area)
		{
			if (BinaryString::getPosition($area->attrs, "data-skip-moving") !== false || !self::isValidScriptType($area->attrs))
			{
				continue;
			}

			$js .= BinaryString::getSubstring($content, $area->openTagStart, $area->closingTagEnd - $area->openTagStart);
			$newContent .= BinaryString::getSubstring($content, $offset, $area->openTagStart - $offset);
			$offset = $area->closingTagEnd;
		}

		if ($js === "")
		{
			return;
		}

		$newContent .= BinaryString::getSubstring($content, $offset);
		$bodyEnd = BinaryString::getLastPositionIgnoreCase($newContent, "</body>");
		if ($bodyEnd === false)
		{
			$content = $newContent.$js;
		}
		else
		{
			$content = substr_replace($newContent, $js, $bodyEnd, 0);
		}
	}

	/**
	 *
	 * Returns positions of <script>...</script> elements
	 * @param $content
	 * @return array
	 */
	private function getScriptAreas($content)
	{
		$openTag = "<script";
		$closingTag = "</script";
		$ending = ">";

		$offset = 0;
		$areas = array();
		$content = BinaryString::changeCaseToLower($content);
		while (($openTagStart = BinaryString::getPosition($content, $openTag, $offset)) !== false)
		{
			$endingPos = BinaryString::getPosition($content, $ending, $openTagStart);
			if ($endingPos === false)
			{
				break;
			}

			$attrsStart = $openTagStart + strlen($openTag);
			$attrs = BinaryString::getSubstring($content, $attrsStart, $endingPos - $attrsStart);
			$openTagEnd = $endingPos + strlen($ending);

			$realClosingTag = $closingTag.$ending;
			$closingTagStart = BinaryString::getPosition($content, $realClosingTag, $openTagEnd);
			if ($closingTagStart === false)
			{
				$offset = $openTagEnd;
				continue;
			}

			$closingTagEnd = $closingTagStart + strlen($realClosingTag);
			while (isset($content[$closingTagEnd]) && $content[$closingTagEnd] === "\n")
			{
				$closingTagEnd++;
			}

			$area = new \stdClass();
			$area->attrs = $attrs;
			$area->openTagStart = $openTagStart;
			$area->openTagEnd = $openTagEnd;
			$area->closingTagStart = $closingTagStart;
			$area->closingTagEnd = $closingTagEnd;
			$areas[] = $area;

			$offset = $closingTagEnd;
		}

		return $areas;
	}

	public function canMoveJsToBody()
	{
		return
			$this->getJsToBody() &&
			!Main\Application::getInstance()->getContext()->getRequest()->isAjaxRequest() &&
			!defined("BX_BUFFER_SHUTDOWN");
	}

	/**
	 *
	 * Returns true if <script> has valid mime type
	 * @param $attrs
	 * @return bool
	 */
	private static function isValidScriptType($attrs)
	{
		if ($attrs === "" || !preg_match("/type\\s*=\\s*(['\"]?)(.*?)\\1/i", $attrs, $match))
		{
			return true;
		}

		$type = strtolower($match[2]);
		return $type === "" || $type === "text/javascript" || $type === "application/javascript";
	}


	/**
	 * Replace path to includes in line
	 * @param string $url of css files
	 * @param string $quote
	 * @param string $path to css
	 * @return string replaced
	 */
	public static function replaceUrlCss($url, $quote, $path)
	{
		if(strpos($url, "://") !== false || strpos($url, "data:") !== false)
		{
			return $quote.$url.$quote;
		}

		$url = trim(stripslashes($url), "'\" \r\n\t");
		if(substr($url, 0, 1) == "/")
		{
			return $quote.$url.$quote;
		}

		return $quote.$path.'/'.$url.$quote;
	}

	/**
	 * Return count of css selectors
	 *
	 * @param bool|string $css - Css content
	 * @return int - Selectors count
	 */
	public static function getCssSelectCnt($css)
	{
		$matches = array();
		$cnt = (int) preg_match_all("#[^,{]+\\s*(?:\\{[^}]*\\}\\s*;?|,)#is", $css, $matches);
		return $cnt;
	}

	/**
	 * Remove from file path any parametrs
	 * @param string $src path to asset file
	 * @return string path whithout ?xxx
	 */
	public static function getAssetPath($src)
	{
		if(($p = strpos($src, "?")) > 0 && !\CMain::IsExternalLink($src))
		{
			$src = substr($src, 0, $p);
		}
		return $src;
	}

	/**
	 * @return bool Optimization off or on for css
	 */
	public function optimizeCss()
	{
		static $optimize = null;
		if($optimize === null)
		{
			$optimize = (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			&& Option::get('main', 'optimize_css_files', 'N') == 'Y'
			&& !$this->ajax;
		}
		return $optimize;
	}

	/**
	 * @return bool Optimization off or on for js
	 */
	public function optimizeJs()
	{
		static $optimize = null;
		if($optimize === null)
		{
			$optimize =
				(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
				&& Option::get('main', 'optimize_js_files', 'N') == 'Y'
				&& !$this->ajax;
		}
		return $optimize;
	}

	public static function canUseMinifiedAssets()
	{
		static $canLoad = null;
		if ($canLoad === null)
		{
			$canLoad = Option::get("main","use_minified_assets", "Y") == "Y";
		}

		return $canLoad;
	}
	/**
	 * @return bool
	 */
	public function sliceKernel()
	{
		return (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true);
	}

	/**
	 * insert inline css
	 * @param $css
	 * @param bool $setLabel
	 * @param bool $bInline
	 * @return string
	 */
	public function insertCss($css, $setLabel = false,  $bInline = false)
	{
		$label = $setLabel ? ' data-template-style="true" ' : '';
		if($bInline)
		{
			return '<style type="text/css"'.$label.'>'."\n".$css."\n</style>\n";
		}
		else
		{
			return '<link href="'.$css.'" type="text/css" '.$label.' rel="stylesheet"'.($this->xhtmlStyle ? ' /':'').'>'."\n";
		}
	}

	/**
	 * Set templateID and template path
	 */
	private function setTemplateID()
	{
		global $USER;
		static $firstExec = true;
		if($firstExec && !$this->ajax && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
		{
			if(defined("SITE_TEMPLATE_PREVIEW_MODE"))
			{
				$this->templatePath = BX_PERSONAL_ROOT.'/tmp/templates/__bx_preview';
			}
			elseif(defined('SITE_TEMPLATE_ID'))
			{
				$this->siteTemplateID = SITE_TEMPLATE_ID;
				$this->templatePath = SITE_TEMPLATE_PATH;
			}
			else
			{
				$this->siteTemplateID = '.default';
				$this->templatePath = BX_PERSONAL_ROOT."/templates/.default";
			}
			$firstExec = false;
		}
	}

	/**
	 * Add template css to asset
	 */
	private function addTemplateCss()
	{
		if(!$this->ajax && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
		{
			$this->css[$this->templatePath.'/styles.css']['TARGET'][] = 'TEMPLATE';
			$this->css[$this->templatePath.'/styles.css']['ADDITIONAL'] = false;

			$this->css[$this->templatePath.'/template_styles.css']['TARGET'][] = 'TEMPLATE';
			$this->css[$this->templatePath.'/template_styles.css']['ADDITIONAL'] = false;
		}
	}

	/**
	 * Show css inline for IE
	 *
	 * @param string $file - Full path for a source css file
	 * @param string $path - Path to css without document root, Include timestamp
	 * @param int $count - Current css selector count
	 * @param bool $check - Skip file check
	 * @return array - Return array(cnt - current css selector count, content - css content)
	 */
	public static function showInlineCssIE($file, $path, $count, $check = false)
	{
		$result = '';
		if(!$check || (file_exists($file) && filesize($file) > 0))
		{
			$content = file_get_contents($file);
			if($content != '')
			{
				$countOld = $count;
				$content = self::fixCssIncludes($content, $path);
				$cnt = self::getCssSelectCnt($content);
				$count += $cnt;
				if($count > 4000)
				{
					$count = $cnt;
					if($countOld > 0)
					{
						$result .= '</style>'."\n".'<style type="text/css">';
					}
				}
				$result .= "\n".$content."\n";
			}
		}

		return array('CNT' => $count, 'CONTENT' => $result);
	}

	/** Prepare string assets */
	private function prepareString()
	{
		foreach($this->strings as $location => $stringLocation)
		{
			foreach($stringLocation as $key => $item)
			{
				/** @var  $assetTID - get first target where added asset */
				$this->strings[$location][$key]['MODE'] = ($item['MODE'] === null ? $this->targetList[$item['TARGET'][0]]['MODE'] : $item['MODE']);
			}
		}
	}

	/***
	 * Returns asset's paths
	 * @param $assetPath
	 * @return null|array
	 */
	private function getAssetPaths($assetPath)
	{
		$paths = array($assetPath);
		if (self::canUseMinifiedAssets() && preg_match("/(.+)\\.(js|css)$/i", $assetPath, $matches))
		{
			array_unshift($paths, $matches[1].".min.".$matches[2]);
		}

		$result = null;
		$maxMtime = 0;
		foreach ($paths as $path)
		{
			$filePath = $this->documentRoot.$path;
			if (file_exists($filePath) && ($mtime = filemtime($filePath)) > $maxMtime && filesize($filePath) > 0)
			{
				$maxMtime = $mtime;
				$result = array(
					"PATH" => $path,
					"FILE_PATH" => $filePath,
					"FULL_PATH" => \CUtil::GetAdditionalFileURL($path, true),
				);
			}
		}

		return $result;
	}

	/** Prepare css asset to optimize */
	private function prepareCss()
	{
		$cnt = 0;
		$arAdditional = array();

		foreach($this->css as $css => $set)
		{
			/** @var  $assetTID - get first target where added asset */
			$assetTID = $set['ADDITIONAL'] ? 'TEMPLATE' : $set['TARGET'][0];
			$cssInfo = array(
				'PATH' => $css,
				'FULL_PATH' => false,
				'FILE_PATH' => false,
				'SKIP' => false,
				'TARGET' => $assetTID,
				'EXTERNAL' => \CMain::IsExternalLink($css),
				'ADDITIONAL' => $set['ADDITIONAL']
			);

			if($cssInfo['EXTERNAL'])
			{
				if($set['ADDITIONAL'])
				{
					$tmpKey = 'TEMPLATE';
					$tmpPrefix = 'template';
				}
				else
				{
					$tmpKey = 'KERNEL';
					$tmpPrefix = 'kernel';
				}

				$cssInfo['MODULE_ID'] = $cnt;
				$cssInfo['TARGET'] = $tmpKey.'_'.$cnt;
				$cssInfo['PREFIX'] = $tmpPrefix.'_'.$cnt;
				$cssInfo['FULL_PATH'] = $cssInfo['PATH'];
				$cssInfo['SKIP'] = true;
				$cnt++;

				$this->targetList[$tmpKey]['CSS_LIST'][$cssInfo['TARGET']] = array(
					'TARGET' => $cssInfo['TARGET'],
					'PREFIX' => $cssInfo['PREFIX'],
					'MODE' => $this->targetList[$assetTID]['MODE'],
					'UNIQUE' => false,
					'WHERE_USED' => array()
				);
			}
			else
			{
				if (($paths = $this->getAssetPaths($css)) !== null)
				{
					$cssInfo["PATH"] = $css;
					$cssInfo["FILE_PATH"] = $paths["FILE_PATH"];
					$cssInfo["FULL_PATH"] = $paths["FULL_PATH"];
				}
				else
				{
					unset($this->css[$css]);
					continue;
				}

				if(strncmp($cssInfo['PATH'], '/bitrix/js/', 11) != 0)
				{
					$cssInfo['SKIP'] = !(
						strncmp($cssInfo['PATH'], '/bitrix/panel/', 14) != 0
						&& strncmp($cssInfo['PATH'], '/bitrix/themes/', 15) != 0
						&& strncmp($cssInfo['PATH'], '/bitrix/modules/', 16) != 0
					);
				}
				else
				{
					$cssInfo['TARGET'] = 'KERNEL';

					if($this->sliceKernel() && $this->optimizeCss())
					{
						$moduleInfo = $this->isKernelCSS($cssInfo['PATH']);
					}
					else
					{
						$moduleInfo = false;
					}

					if($moduleInfo)
					{
						$cssInfo['MODULE_ID'] = $moduleInfo['MODULE_ID'];
						$cssInfo['TARGET'] = 'KERNEL_'.$moduleInfo['MODULE_ID'];
						$cssInfo['PREFIX'] = 'kernel_'.$moduleInfo['MODULE_ID'];
					}
					else
					{
						$cssInfo['MODULE_ID'] = $cnt;
						$cssInfo['TARGET'] = 'KERNEL_'.$cnt;
						$cssInfo['PREFIX'] = 'kernel_'.$cnt;
						$cssInfo['SKIP'] = true;
						$cnt++;
					}

					if(isset($this->targetList['KERNEL']['CSS_LIST'][$cssInfo['TARGET']]['MODE']))
					{
						$this->targetList['KERNEL']['CSS_LIST'][$cssInfo['TARGET']]['MODE'] |= $this->targetList[$assetTID]['MODE'];
					}
					else
					{
						$this->targetList['KERNEL']['CSS_LIST'][$cssInfo['TARGET']] = array(
							'TARGET' => $cssInfo['TARGET'],
							'PREFIX' => $cssInfo['PREFIX'],
							'MODE' => $set['ADDITIONAL'] ? $this->targetList[$set['TARGET'][0]]['MODE'] : $this->targetList[$assetTID]['MODE'],
							'UNIQUE' => true,
							'WHERE_USED' => array()
						);
					}

					// Add information about sets where used
					foreach($set['TARGET'] as $setID)
					{
						$this->targetList['KERNEL']['CSS_LIST'][$cssInfo['TARGET']]['WHERE_USED'][$setID] = true;
					}
				}
			}

			if($cssInfo['ADDITIONAL'])
			{
				$arAdditional[] = $cssInfo;
			}
			else
			{
				$this->css[$cssInfo['TARGET']][] = $cssInfo;
			}

			unset($this->css[$css]);
		}

		foreach($arAdditional as $cssInfo)
		{
			$this->css[$cssInfo['TARGET']][] = $cssInfo;
		}
	}

	/** Prepare js asset to optimize */
	private function prepareJs()
	{
		$cnt = 0;
		$arAdditional = array();
		foreach($this->js as $js => $set)
		{
			/** @var  $assetTID - get first target where added asset */
			$assetTID = $set['ADDITIONAL'] ? 'TEMPLATE' : $set['TARGET'][0];
			$jsInfo = array(
				'PATH' => $js,
				'FULL_PATH' => false,
				'FILE_PATH' => false,
				'SKIP' => false,
				'TARGET' => $assetTID,
				'EXTERNAL' => \CMain::IsExternalLink($js),
				'BODY' => false,
				'ADDITIONAL' => $set['ADDITIONAL']
			);

			if($jsInfo['EXTERNAL'])
			{
				if($set['ADDITIONAL'])
				{
					$tmpKey = 'TEMPLATE';
					$tmpPrefix = 'template';
				}
				else
				{
					$tmpKey = 'KERNEL';
					$tmpPrefix = 'kernel';
				}

				$jsInfo['MODULE_ID'] = $cnt;
				$jsInfo['TARGET'] = $tmpKey.'_'.$cnt;
				$jsInfo['PREFIX'] = $tmpPrefix.'_'.$cnt;
				$jsInfo['FULL_PATH'] = $jsInfo['PATH'];
				$jsInfo['SKIP'] = true;
				$cnt++;

				$this->targetList[$tmpKey]['JS_LIST'][$jsInfo['TARGET']] = array(
					'TARGET' => $jsInfo['TARGET'],
					'PREFIX' => $jsInfo['PREFIX'],
					'MODE' => $this->targetList[$assetTID]['MODE'],
					'UNIQUE' => false,
					'WHERE_USED' => array()
				);
			}
			else
			{
				if (($paths = $this->getAssetPaths($js)) !== null)
				{
					$jsInfo["PATH"] = $js;
					$jsInfo["FILE_PATH"] = $paths["FILE_PATH"];
					$jsInfo["FULL_PATH"] = $paths["FULL_PATH"];
				}
				else
				{
					unset($this->js[$js]);
					continue;
				}

				if(strncmp($jsInfo['PATH'], '/bitrix/js/', 11) != 0)
				{
					$jsInfo['SKIP'] = !(
						strncmp($jsInfo['PATH'], '/bitrix/panel/', 14) != 0
						&& strncmp($jsInfo['PATH'], '/bitrix/themes/', 15) != 0
						&& strncmp($jsInfo['PATH'], '/bitrix/modules/', 16) != 0
					);
				}
				else
				{
					$jsInfo['TARGET'] = 'KERNEL';
					if($this->sliceKernel() && $this->optimizeJs())
					{
						$moduleInfo = $this->isKernelJS($jsInfo['PATH']);
					}
					else
					{
						$moduleInfo = false;
					}

					if($moduleInfo)
					{
						$jsInfo['MODULE_ID'] = $moduleInfo['MODULE_ID'];
						$jsInfo['TARGET'] = 'KERNEL_'.$moduleInfo['MODULE_ID'];
						$jsInfo['PREFIX'] = 'kernel_'.$moduleInfo['MODULE_ID'];
						$jsInfo['BODY'] = $moduleInfo['BODY'];
					}
					else
					{
						$jsInfo['MODULE_ID'] = $cnt;
						$jsInfo['TARGET'] = 'KERNEL_'.$cnt;
						$jsInfo['PREFIX'] = 'kernel_'.$cnt;
						$jsInfo['SKIP'] = true;
						$cnt++;
					}

					if($jsInfo['BODY'])
					{
						$this->targetList['BODY']['JS_LIST'][$jsInfo['TARGET']] = array(
							'TARGET' => $jsInfo['TARGET'],
							'PREFIX' => $jsInfo['PREFIX'],
							'MODE' => $this->targetList[$assetTID]['MODE'],
							'UNIQUE' => true,
							'WHERE_USED' => array()
						);
					}
					else
					{
						if(isset($this->targetList['KERNEL']['JS_LIST'][$jsInfo['TARGET']]['MODE']))
						{
							$this->targetList['KERNEL']['JS_LIST'][$jsInfo['TARGET']]['MODE'] |= $this->targetList[$assetTID]['MODE'];
						}
						else
						{
							$this->targetList['KERNEL']['JS_LIST'][$jsInfo['TARGET']] = array(
								'TARGET' => $jsInfo['TARGET'],
								'PREFIX' => $jsInfo['PREFIX'],
								'MODE' => $set['ADDITIONAL'] ? $this->targetList[$set['TARGET'][0]]['MODE'] : $this->targetList[$assetTID]['MODE'],
								'UNIQUE' => true,
								'WHERE_USED' => array()
							);
						}
					}

					// Add information about sets where used
					foreach($set['TARGET'] as $setID)
					{
						$this->targetList['KERNEL']['JS_LIST'][$jsInfo['TARGET']]['WHERE_USED'][$setID] = true;
					}
				}
			}

			if($jsInfo['ADDITIONAL'])
			{
				$arAdditional[] = $jsInfo;
			}
			else
			{
				$this->js[$jsInfo['TARGET']][] = $jsInfo;
			}
			unset($this->js[$js]);
		}

		// Clean body scripts
		foreach($this->targetList['BODY']['JS_LIST'] as $item)
		{
			unset($this->targetList['KERNEL']['JS_LIST'][$item['TARGET']]);
		}

		foreach($arAdditional as $jsInfo)
		{
			$this->js[$jsInfo['TARGET']][] = $jsInfo;
		}
	}

	/**
	 * Return css page assets
	 * @return string
	 */
    public function getCss($type = AssetShowTargetType::ALL)
	{
		$res = $res_content = '';
		$cnt = $ruleCount = 0;
		static $firstExec = true;
		static $setList = array();
		static $arAjaxList = array();

		if($firstExec)
		{
			$this->setTemplateID();
			$this->addTemplateCss();
			$this->prepareCss();
			$setList = $this->getTargetList();
			$optimizeCss = $this->optimizeCss();
			if($optimizeCss)
			{
				$this->maxStylesCnt -= 3;
			}

			foreach($setList as $setInfo)
			{
				if(!isset($this->css[$setInfo['NAME']]))
				{
					continue;
				}

				$resCss = '';
				$listAsset = array();
				$showLabel = ($setInfo['NAME'] == 'TEMPLATE');

				foreach($this->css[$setInfo['NAME']] as $cssFile)
				{
					$css = ($cssFile['EXTERNAL'] ? '' : $this->cssDomain).$cssFile['FULL_PATH'];
					if($this->ajax)
					{
						$this->assetList['CSS'][] = $cssFile['PATH'];
						$arAjaxList[] = $css;
					}
					elseif($cssFile['EXTERNAL'])
					{
						$resCss .= $this->insertCss($css, $showLabel);
						$this->fileList['CSS'][$setInfo['NAME']]['FILES'][] = $css;
						$cnt++;
					}
					elseif($optimizeCss)
					{
						if($cssFile['SKIP'])
						{
							$resCss .= $this->insertCss($css, $showLabel);
							$this->fileList['CSS'][$setInfo['NAME']]['FILES'][] = $css;
							$cnt++;
						}
						else
						{
							$listAsset[] = $cssFile;
						}
					}
					else
					{
						if($this->isIE)
						{
							if($cnt < $this->maxStylesCnt)
							{
								$resCss .= $this->insertCss($css, $showLabel);
								$this->fileList['CSS'][$setInfo['NAME']]['FILES'][] = $css;
								$cnt++;
							}
							else
							{
								$arTmp = $this->showInlineCssIE($cssFile['FILE_PATH'], $cssFile['PATH'], $ruleCount, $showLabel, true);
								$ruleCount = $arTmp['CNT'];
								$res_content .= $arTmp['CONTENT'];
							}
						}
						else
						{
							$resCss .= $this->insertCss($css, $showLabel);
							$this->fileList['CSS'][$setInfo['NAME']]['FILES'][] = $css;
							$cnt++;
						}
					}
				}

				$resCss .= ($res_content == '' ? '' : $this->insertCss($res_content, $showLabel, true));
				$arTmp = $this->optimizeAsset($listAsset, $setInfo['UNIQUE'], $setInfo['PREFIX'], $setInfo['NAME'], 'css');
				$resCss = $arTmp['RESULT'].$resCss;
				$this->assetList['CSS'][$setInfo['PARENT_NAME']][$setInfo['NAME']] = $arTmp['FILES'];
				$this->targetList[$setInfo['PARENT_NAME']]['CSS_RES'][$setInfo['NAME']][] = $resCss;
			}
			$firstExec = false;
		}

		if($this->ajax && !empty($arAjaxList))
		{
			$res .= '<script type="text/javascript">'."BX.loadCSS(['".implode("','", $arAjaxList)."']);".'</script>';
		}

        if($type == AssetShowTargetType::KERNEL)
        {
            $res .= $this->showAsset($setList, 'css', 'KERNEL');
        }
        elseif($type == AssetShowTargetType::TEMPLATE_PAGE)
        {
            foreach($this->targetList as $setName => $set)
            {
                if($setName != 'TEMPLATE' && $setName != 'KERNEL')
                {
                    $res .= $this->showAsset($setList, 'css', $setName);
                }
            }

            $res .= $this->showAsset($setList, 'css', 'TEMPLATE');
        }
        else
        {
            foreach($this->targetList as $setName => $set)
            {
                if($setName != 'TEMPLATE')
                {
                    $res .= $this->showAsset($setList, 'css', $setName);
                }
            }

            $res .= $this->showAsset($setList, 'css', 'TEMPLATE');
        }

		return $res;
	}

	/**
	 * Return JS page assets
	 * @param int $type
	 * @return string
	 */
	function getJs($type = AssetShowTargetType::ALL)
	{
		static $firstExec = true;
		static $setList = array();

		$res = '';
		$type = (int) $type;
		$type = (($type == AssetShowTargetType::KERNEL && $this->headString && !$this->headScript) ? AssetShowTargetType::ALL : $type);
		$optimize = $this->optimizeJs();
		if($firstExec)
		{
			$this->prepareJs();
			$setList = $this->getTargetList('JS');

			foreach($setList as $setInfo)
			{
				if(!isset($this->js[$setInfo['NAME']]))
				{
					continue;
				}

				$resJs = '';
				$listAsset = array();
				foreach($this->js[$setInfo['NAME']] as $jsFile)
				{
					$js = ($jsFile['EXTERNAL'] ? '' : $this->jsDomain).$jsFile['FULL_PATH'];
					if($optimize)
					{
						if($jsFile['SKIP'])
						{
							$this->fileList['JS'][$setInfo['NAME']]['FILES'][] = $js;
							$resJs .= '<script type="text/javascript" src="'.$js.'"></script>'."\n";
						}
						else
						{
							$listAsset[] = $jsFile;
						}
					}
					else
					{
						$this->fileList['JS'][$setInfo['NAME']]['FILES'][] = $js;
						$resJs .= '<script type="text/javascript" src="'.$js.'"></script>'."\n";
					}
				}
				$arTmp = $this->optimizeAsset($listAsset, $setInfo['UNIQUE'], $setInfo['PREFIX'], $setInfo['NAME'], 'js');
				$resJs = $arTmp['RESULT'].$resJs;
				$this->assetList['JS'][$setInfo['PARENT_NAME']][$setInfo['NAME']] = $arTmp['FILES'];
				$this->targetList[$setInfo['PARENT_NAME']]['JS_RES'][$setInfo['NAME']][] = $resJs;
			}
			$firstExec = false;
		}

		if($type == AssetShowTargetType::KERNEL && ($this->mode & $this->targetList['KERNEL']['MODE']))
		{
			$setName = 'KERNEL';
			$res .= $this->getStrings(AssetLocation::AFTER_CSS);
			$res .= $this->showAsset($setList,'js', $setName);
			$res .= $this->showFilesList();
			$res .= $this->getStrings(AssetLocation::AFTER_JS_KERNEL);
		}
		elseif($type == AssetShowTargetType::TEMPLATE_PAGE)
		{
			foreach($this->targetList as $setName => $set)
			{
				if($setName != 'KERNEL' && $setName != 'BODY')
				{
					$setName = $this->fixJsSetOrder($setName);
					$res .= $this->showAsset($setList,'js', $setName);
				}
			}
			$res .= $this->getStrings(AssetLocation::AFTER_JS);
		}
		elseif($type == AssetShowTargetType::BODY && ($this->mode & $this->targetList['BODY']['MODE']))
		{
			$setName = 'BODY';
			$res .= $this->showAsset($setList,'js', $setName);
		}
		else
		{
			foreach($this->targetList as $setName => $set)
			{
				if ($this->mode & $set['MODE'])
				{
					$setName = $this->fixJsSetOrder($setName);
					if ($setName == 'KERNEL')
					{
						$res .= $this->getStrings(AssetLocation::AFTER_CSS);
						$res .= $this->showAsset($setList, 'js', $setName);
						$res .= $this->showFilesList();
						$res .= $this->getStrings(AssetLocation::AFTER_JS_KERNEL);
					}
					elseif ($setName != 'BODY')
					{
						$res .= $this->showAsset($setList, 'js', $setName);
					}
				}
			}

			$res .= $this->getStrings(AssetLocation::AFTER_JS);
		}

		return (trim($res) == '' ? $res : $res."\n");
	}

	/**
	 * Convert location for new format
	 * @param $location
	 * @return AssetLocation
	 */
	public static function getLocationByName($location)
	{
		if($location === false || $location === 'DEFAULT')
		{
			$location = AssetLocation::AFTER_JS_KERNEL;
		}
		elseif($location === true)
		{
			$location = AssetLocation::AFTER_CSS;
		}

		return $location;
	}

	/**
	 * Insert JS code to set assets included in page
	 * @return string
	 */
	private function showFilesList()
	{
		$res = '';
		if (!\CJSCore::IsCoreLoaded())
		{
			return $res;
		}

		if(!empty($this->assetList['JS']))
		{
			$assetList = array();
			$setList = $this->getTargetList('JS');
			foreach($setList as $set)
			{
				if($this->mode & $set['MODE']
					&& isset($this->assetList['JS'][$set['PARENT_NAME']][$set['NAME']])
					&& is_array($this->assetList['JS'][$set['PARENT_NAME']][$set['NAME']]))
				{
					$assetList = array_merge($assetList, $this->assetList['JS'][$set['PARENT_NAME']][$set['NAME']]);
				}
			}

			if(!empty($assetList))
			{
				$res .= '<script type="text/javascript">'."BX.setJSList(['".implode("','", $assetList)."']); </script>\n";
			}
		}

		if(!empty($this->assetList['CSS']))
		{
			$assetList = array();
			$setList = $this->getTargetList('CSS');
			foreach($setList as $set)
			{
				if($this->mode & $set['MODE']
					&& isset($this->assetList['CSS'][$set['PARENT_NAME']][$set['NAME']])
					&& is_array($this->assetList['CSS'][$set['PARENT_NAME']][$set['NAME']])
				)
				{
					$assetList = array_merge($assetList, $this->assetList['CSS'][$set['PARENT_NAME']][$set['NAME']]);
				}
			}

			if(!empty($assetList))
			{
				$res .= '<script type="text/javascript">'."BX.setCSSList(['".implode("','", $assetList)."']); </script>\n";
			}
		}
		return $res;
	}

	/**
	 * Add information about kernel module css
	 * @param string $module
	 * @param array $css
	 */
	function addCssKernelInfo($module = '', $css = array())
	{
		if(empty($module) || empty($css))
		{
			return;
		}

		if(!array_key_exists($module, $this->moduleInfo['CSS']))
		{
			$this->moduleInfo['CSS'][$module] = array('MODULE_ID' => $module, 'FILES_INFO' => true);
		}

		foreach($css as $key)
		{
			$this->kernelAsset['CSS'][$key] = $module;
		}

		$this->moduleInfo['CSS'][$module]['FILES_INFO'] = true;
	}

	/**
	 * Add information about kernel js modules
	 * @param string $module
	 * @param array $js
	 */
	function addJsKernelInfo($module = '', $js = array())
	{
		if(empty($module) || empty($js))
		{
			return;
		}

		if(!array_key_exists($module, $this->moduleInfo['JS']))
		{
			$this->moduleInfo['JS'][$module] = array('MODULE_ID' => $module, 'FILES_INFO' => true, 'BODY' => false);
		}

		foreach($js as $key)
		{
			$this->kernelAsset['JS'][$key] = $module;
		}

		$this->moduleInfo['JS'][$module]['FILES_INFO'] = true;
	}

	/**
	 * Return information about file and check is it in kernel pack
	 * @param $css
	 * @return array|bool
	 */
	function isKernelCSS($css)
	{
		if(array_key_exists($css, $this->kernelAsset['CSS']))
		{
			return $this->moduleInfo['CSS'][$this->kernelAsset['CSS'][$css]];
		}
		else
		{
			$tmp = explode('/', $css);
			$moduleID = $tmp['3'];
			unset($tmp);

			if(empty($moduleID))
			{
				return false;
			}
			elseif(array_key_exists($moduleID, $this->moduleInfo['CSS']))
			{
				if($this->moduleInfo['CSS'][$moduleID]['FILES_INFO'])
				{
					return false;
				}
				else
				{
					return $this->moduleInfo['CSS'][$moduleID];
				}
			}

			return array('MODULE_ID' => $moduleID, 'BODY' => false, 'FILES_INFO' => false);
		}
	}

	/**
	 * Return information about file and check is it in kernel pack
	 * @param $js
	 * @return array|bool
	 */
	function isKernelJS($js)
	{
		if(array_key_exists($js, $this->kernelAsset['JS']))
		{
			return $this->moduleInfo['JS'][$this->kernelAsset['JS'][$js]];
		}
		else
		{
			$tmp = explode('/', $js);
			$moduleID = $tmp['3'];
			unset($tmp);

			if(empty($moduleID))
			{
				return false;
			}
			elseif(array_key_exists($moduleID, $this->moduleInfo['JS']))
			{
				if($this->moduleInfo['JS'][$moduleID]['FILES_INFO'])
				{
					return false;
				}
				else
				{
					return $this->moduleInfo['JS'][$moduleID];
				}
			}

			return array('MODULE_ID' => $moduleID, 'BODY' => false, 'FILES_INFO' => false, 'IS_KERNEL' => true );
		}
	}

	/**
	 * Set unique mode for set
	 * @param string $setID
	 * @param string $uniqueID
	 * @return bool
	 */
	public function setUnique($setID = '', $uniqueID = '')
	{
		$setID = preg_replace('#[^a-z0-9_]#i', '', $setID);
		$uniqueID = preg_replace('#[^a-z0-9_]#i', '', $uniqueID);
		if(!(empty($setID) || empty($uniqueID)) && isset($this->targetList[$setID]))
		{
			$this->targetList[$setID]['UNIQUE'] = true;
			$this->targetList[$setID]['PREFIX'] .= ''.($uniqueID == '' ? '' : '_'.$uniqueID);
			return true;
		}
		return false;
	}

	/**
	 * Show asset resource
	 * @param array $arSetList
	 * @param string $setName
	 * @param string $type
	 * @return string
	 */
	private function showAsset($arSetList = array(), $type = 'css', $setName = '')
	{
		$res = '';
		$type = ($type == 'css' ? 'CSS_RES' : 'JS_RES');
		$skipCheck = ($setName == '');

		foreach($arSetList as $setInfo)
		{
			if(
				($skipCheck || $setName == $setInfo['PARENT_NAME'])
				&& $this->mode & $setInfo['MODE']
				&& isset($this->targetList[$setInfo['PARENT_NAME']][$type][$setInfo['NAME']]))
			{
				$res .= implode("\n", $this->targetList[$setInfo['PARENT_NAME']][$type][$setInfo['NAME']]);
			}
		}

		return $res;
	}

	/**
	 * Fix current set order for js
	 * @param string $setName
	 * @return string
	 */
	private function fixJsSetOrder($setName = '')
	{
		if($setName == 'PAGE')
		{
			$setName = 'TEMPLATE';
		}
		elseif($setName == 'TEMPLATE')
		{
			$setName = 'PAGE';
		}
		return $setName;
	}

	/**
	 * Get time for current asset
	 * @param string $file
	 * @return bool|string
	 */
	public static function getAssetTime($file = '')
	{
		$qpos = strpos($file, '?');
		if($qpos === false)
		{
			return false;
		}
		$qpos++;
		return substr($file, $qpos);
	}

	/**
	 * Return md5 for asset
	 * @param array $assetList
	 * @return string
	 */
	private function getAssetChecksum($assetList = array())
	{
		$arList = array();
		foreach($assetList as $arAsset)
		{
			$arList[$arAsset['PATH']] = $arAsset['FULL_PATH'];
		}
		ksort($arList);
		return md5(implode('_', $arList));
	}

	/**
	 * Check assets and return action and files
	 * @param array $arAssetList
	 * @param string $infoFile
	 * @param string $optimFile
	 * @param bool $unique
	 * @return array
	 */
	private function isAssetChanged($arAssetList = array(), $infoFile = '', $optimFile = '', $unique = false)
	{
		$arRes = array(
			'FILE' => array(),
			'ACTION' => 'NO',
			'FILE_EXIST' => false,
			'INFO' => array(
				'CUR_SEL_CNT' => 0,
				'CUR_IE_CNT' => 0,
				'FILES' => array()
			)
		);

		if(file_exists($infoFile) && file_exists($optimFile))
		{
			include($infoFile);
			/** @var $arFilesInfo - information about files in set */
			$arRes['INFO'] = $arFilesInfo;
			$arRes['FILE_EXIST'] = true;
			if($unique)
			{
				if(is_array($arFilesInfo['FILES']))
				{
					foreach($arAssetList as $arAsset)
					{
						if(isset($arFilesInfo['FILES'][$arAsset['PATH']]))
						{
							if($this->getAssetTime($arAsset['FULL_PATH']) != $arFilesInfo['FILES'][$arAsset['PATH']])
							{
								$arRes = array(
									'FILE' => $arAssetList,
									'ACTION' => 'NEW',
									'INFO' => array(
										'CUR_SEL_CNT' => 0,
										'CUR_IE_CNT' => 0,
										'FILES' => array()
									)
								);

								break;
							}
						}
						else
						{
							$arRes['FILE'][] = $arAsset;
							$arRes['ACTION'] = 'UP';
						}
					}
				}
				else
				{
					$arRes = array(
						'FILE' => $arAssetList,
						'ACTION' => 'NEW',
						'INFO' => array(
							'CUR_SEL_CNT' => 0,
							'CUR_IE_CNT' => 0,
							'FILES' => array()
						)
					);
				}

			}
		}
		else
		{
			$arRes['FILE'] = $arAssetList;
			$arRes['ACTION'] = 'NEW';
		}

		return $arRes;
	}

	/**
	 * @param array $arFile
	 * @param bool $unique
	 * @param string $prefix
	 * @param string $setName
	 * @param string $type
	 * @return array
	 */
	private function optimizeAsset($arFile = array(), $unique = false, $prefix = 'default', $setName = '', $type = 'css')
	{
		if((!is_array($arFile) || empty($arFile)))
		{
			return array('RESULT' => '', 'FILES' => array());
		}

		$this->setTemplateID();
		$res = $assetMD5 = $comments = $contents = '';
		$prefix = trim($prefix);
		$prefix = strlen($prefix) < 1 ? 'default' : $prefix;
		$add2End = (strncmp($prefix, 'kernel', 6) == 0);
		$type = ($type == 'js' ? 'js' : 'css');
		$arIEContent = array();
		/** @var bool $noCheckOnly when we cant frite files */
		$noCheckOnly = !defined('BX_HEADFILES_CACHE_CHECK_ONLY');
		$prefix = ($unique ? $prefix : $prefix.'_'.$this->getAssetChecksum($arFile));
		$dbType = ToUpper(\Bitrix\Main\Application::getInstance()->getConnection()->getType());
		$documentRoot = Main\Loader::getDocumentRoot();
		$optimPath = BX_PERSONAL_ROOT.'/cache/'.$type.'/'.SITE_ID.'/'.$this->siteTemplateID.'/'.$prefix.'/';
		$infoFile = $documentRoot.BX_PERSONAL_ROOT.'/managed_cache/'.$dbType.'/'.$type.'/'.SITE_ID.'/'.$this->siteTemplateID.'/'.$prefix.'/info.php';
		$optimFile = $optimPath.$prefix.($type == 'css' ? '.css' : '.js');
		$optimFName = $documentRoot.$optimFile;
		$cssFNameIE = $optimPath.$prefix.'#CNT#.css';
		$cssFPathIE = $documentRoot.$cssFNameIE;

		$tmpInfo = $this->isAssetChanged($arFile, $infoFile, $optimFName, $unique);
		$arFilesInfo = $tmpInfo['INFO'];
		$action = $tmpInfo['ACTION'];
		$arFile = $tmpInfo['FILE'];
		$optimFileExist = $tmpInfo['FILE_EXIST'];
		$writeResult = ($action == 'NEW' ? false : true);

		if($action != 'NO')
		{
			if($type == 'css')
			{
				$this->fileList['CSS'][$setName]['UP_NEW_FILES'] = $tmpInfo['FILE'];
			}
			else
			{
				$this->fileList['JS'][$setName]['UP_NEW_FILES'] = $tmpInfo['FILE'];
			}

			$arFilesInfo['CUR_IE_CNT'] = intval($arFilesInfo['CUR_IE_CNT']);
			$arFilesInfo['CUR_SEL_CNT'] = intval($arFilesInfo['CUR_SEL_CNT']);

			if($action == 'UP')
			{
				if($noCheckOnly)
				{
					$contents .= file_get_contents($optimFName);
					if($type == 'css')
					{
						if($arFilesInfo['CUR_SEL_CNT'] < self::MAX_ADD_CSS_SELECTOR)
						{
							$css = str_replace('#CNT#', $arFilesInfo['CUR_IE_CNT'], $cssFPathIE);
							if(file_exists($css))
							{
								$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= file_get_contents($css);
								$arFilesInfo['CUR_SEL_CNT'] = $this->getCssSelectCnt($arIEContent[$arFilesInfo['CUR_IE_CNT']]);
							}
						}
						else
						{
							$arFilesInfo['CUR_IE_CNT']++;
							$arFilesInfo['CUR_SEL_CNT'] = 0;
						}
					}
				}
				else
				{
					$writeResult = false;
				}
			}

			$needWrite = false;
			if($noCheckOnly)
			{
				$newContent = '';
				$mapNeeded = false;
				foreach($arFile as $file)
				{
					$assetContent = file_get_contents($file['FILE_PATH']);
					if($type == 'css')
					{
						$f_cnt = $this->getCssSelectCnt($assetContent);
						$new_cnt = $f_cnt + $arFilesInfo['CUR_SEL_CNT'];

						$comments .= "/* ".$file['FULL_PATH']." */\n";
						$assetContent = $this->fixCSSIncludes($assetContent, $file['PATH']);
						$assetContent = "\n/* Start:".$file['FULL_PATH']."*/\n".$assetContent."\n/* End */\n";

						if($new_cnt < self::MAX_CSS_SELECTOR)
						{
							$arFilesInfo['CUR_SEL_CNT'] = $new_cnt;
							$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= $assetContent;
						}
						else
						{
							$arFilesInfo['CUR_SEL_CNT'] = $f_cnt;
							$arFilesInfo['CUR_IE_CNT']++;
							$arIEContent[$arFilesInfo['CUR_IE_CNT']] .= $assetContent;
						}
						$newContent .= "\n".$assetContent;
					}
					else
					{
						$info = array(
							"full" => $file['FULL_PATH'],
							"source" => $file['PATH'],
							"min" => "",
							"map" => "",
						);

						if (preg_match("/\\.min\\.js$/i", $file['FILE_PATH']))
						{
							$sourceMap = self::cutSourceMap($assetContent);
							if (strlen($sourceMap) > 0)
							{
								$dirPath = IO\Path::getDirectory($file['PATH']);
								$info["map"] = $dirPath."/".$sourceMap;
								$info["min"] = self::getAssetPath($file['FULL_PATH']);
								$mapNeeded = true;
							}
						}

						$comments .= "; /* ".$file['FULL_PATH']."*/\n";
						$newContent .= "\n".self::HEADER_START_TAG.serialize($info).self::HEADER_END_TAG."\n".$assetContent."\n/* End */\n;";
					}

					$arFilesInfo['FILES'][$file['PATH']] = $this->getAssetTime($file['FULL_PATH']);
					$needWrite = true;
				}

				if($needWrite)
				{
					$sourceMap = self::cutSourceMap($contents);
					$mapNeeded = $mapNeeded || strlen($sourceMap) > 0;

					// Write packed files and meta information
					$contents = ($add2End ? $comments.$contents.$newContent : $newContent.$contents.$comments);
					if ($mapNeeded)
					{
						$contents .= self::SOURCE_MAP_TAG.$prefix.".map.js";
					}

					if($writeResult = $this->write($optimFName, $contents))
					{
						$cacheInfo = '<? $arFilesInfo = array( \'FILES\' => array(';

						foreach($arFilesInfo['FILES'] as $key => $time)
						{
							$cacheInfo .= '"'.EscapePHPString($key).'" => "'.$time.'",';
						}

						$cacheInfo .= "), 'CUR_SEL_CNT' => '".$arFilesInfo['CUR_SEL_CNT']."', 'CUR_IE_CNT' => '".$arFilesInfo['CUR_IE_CNT']."'); ?>";
						$this->write($infoFile, $cacheInfo, false);

						if($type == 'css')
						{
							foreach($arIEContent as $key => $ieContent)
							{
								$css = str_replace('#CNT#', $key, $cssFPathIE);
								$this->write($css, $ieContent);
							}
						}

						if ($mapNeeded)
						{
							$this->write($documentRoot.$optimPath.$prefix.".map.js", self::generateSourceMap($prefix.".js", $contents), false);
						}
					}
				}
				elseif($optimFileExist)
				{
					$writeResult = true;
				}
				unset($contents, $arIEContent);
			}
		}

		$label = (($prefix == 'template' || substr($prefix, 0, 9)  == 'template_') ? ' data-template-style="true" ' : '');
		if($type == 'css' && $this->isIE && $writeResult)
		{
			for($i = 0; $i <= $arFilesInfo['CUR_IE_CNT']; $i++)
			{
				$css = \CUtil::GetAdditionalFileURL(str_replace('#CNT#', $i, $cssFNameIE));
				$res .= '<link href="'.$this->cssDomain.$css.'" type="text/css" '.($i == 0 ? $label : '').' rel="stylesheet"'.($this->xhtmlStyle ? ' /':'').'>'."\n";
				$this->fileList['CSS'][$setName]['FILES'][] = $this->cssDomain.$css;
			}
		}
		else
		{
			if($type == 'css')
			{
				if($writeResult || !$writeResult && $unique && $action == 'UP')
				{
					$css = \CUtil::GetAdditionalFileURL($optimFile);
					$res .= '<link href="'.$this->cssDomain.$css.'" type="text/css" '.$label.' rel="stylesheet"'.($this->xhtmlStyle? ' /':'').'>'."\n";
					$this->fileList['CSS'][$setName]['FILES'][] = $this->cssDomain.$css;
				}

				if(!$writeResult)
				{
					if($this->isIE)
					{
						$cnt = 0;
						$resContent = '';
						$ruleCount = 0;

						foreach($arFile as $file)
						{
							if($cnt < $this->maxStylesCnt)
							{
								$res .= '<link href="'.$this->cssDomain.$file['FULL_PATH'].'" '.($cnt == 0 ? $label : '').' type="text/css" rel="stylesheet"'.($this->xhtmlStyle ? ' /':'').'>'."\n";
								$this->fileList['CSS'][$setName]['FILES'][] = $this->cssDomain.$file['FULL_PATH'];
							}
							else
							{
								$tmpInfo = $this->showInlineCssIE($file['FILE_PATH'], $file['PATH'], $ruleCount, true);
								$ruleCount = $tmpInfo['CNT'];
								$resContent .= $tmpInfo['CONTENT'];
							}
							$cnt++;
						}

						if($resContent != '')
						{
							$res .= '<style type="text/css">'."\n".$resContent."\n</style>\n";
						}
					}
					else
					{
						foreach($arFile as $file)
						{
							$res .= '<link href="'.$this->cssDomain.$file['FULL_PATH'].'" type="text/css" '.$label.' rel="stylesheet"'.($this->xhtmlStyle? ' /':'').'>'."\n";
							$this->fileList['CSS'][$setName]['FILES'][] = $this->jsDomain.$file['FULL_PATH'];
						}
					}
				}
			}
			else
			{
				if($writeResult || (!$writeResult && $unique && $action == 'UP'))
				{
					$js = \CUtil::GetAdditionalFileURL($optimFile);
					$res .= '<script type="text/javascript" src="'.$this->jsDomain.$js.'"></script>'."\n";
					$this->fileList['JS'][$setName]['FILES'][] = $this->jsDomain.$js;
				}

				if(!$writeResult)
				{
					foreach ($arFile as $file)
					{
						$res .= '<script type="text/javascript" src="'.$this->jsDomain.$file['FULL_PATH'].'"></script>'."\n";
						$this->fileList['JS'][$setName]['FILES'][] = $this->jsDomain.$file['FULL_PATH'];
					}
				}
			}
		}

		$arF = array();
		if(is_array($arFilesInfo['FILES']))
		{
			foreach ($arFilesInfo['FILES'] as $key => $time)
			{
				$arF[] = str_replace($documentRoot, '', $key).'?'.$time;
			}
		}
		unset($arFile, $arFilesInfo);
		return array('RESULT' => $res, 'FILES' => $arF);
	}

	/**
	 * Cuts and returns source map comment
	 * @param $content
	 * @return string
	 */
	private static function cutSourceMap(&$content)
	{
		$sourceMapName = "";

		$length = BinaryString::getLength($content);
		$position = $length > 512 ? $length - 512 : 0;
		$lastLine = BinaryString::getPosition($content, self::SOURCE_MAP_TAG, $position);
		if ($lastLine !== false)
		{
			$nameStart = $lastLine + strlen(self::SOURCE_MAP_TAG);
			if (($newLinePos = BinaryString::getPosition($content, "\n", $nameStart)) !== false)
			{
				$sourceMapName = BinaryString::getSubstring($content, $nameStart, $newLinePos - $nameStart);
			}
			else
			{
				$sourceMapName = BinaryString::getSubstring($content, $nameStart);
			}

			$sourceMapName = trim($sourceMapName);
			$content = BinaryString::getSubstring($content, 0, $lastLine);
		}

		return $sourceMapName;
	}

	/**
	 * Returns array of file data
	 * @param $content
	 * @return array
	 */
	private static function getFilesInfo($content)
	{
		$offset = 0;
		$line = 0;

		$arResult = array();
		while (($newLinePos = BinaryString::getPosition($content, "\n", $offset)) !== false)
		{
			$line++;
			$offset = $newLinePos + 1;
			if (BinaryString::getSubstring($content, $offset, strlen(self::HEADER_START_TAG)) === self::HEADER_START_TAG)
			{
				$endingPos = BinaryString::getPosition($content, self::HEADER_END_TAG, $offset);
				if ($endingPos === false)
				{
					break;
				}

				$startData = $offset + strlen(self::HEADER_START_TAG);
				$data = unserialize(BinaryString::getSubstring($content, $startData, $endingPos - $startData));

				if (is_array($data))
				{
					$data["line"] = $line + 1;
					$arResult[] = $data;
				}

				$offset = $endingPos;
			}
		}

		return $arResult;
	}

	/**
	 * Generates source map content
	 * @param $fileName
	 * @param $content
	 * @return string
	 */
	private static function generateSourceMap($fileName, $content)
	{
		$files = self::getFilesInfo($content);
		$sections = "";
		foreach ($files as $file)
		{
			if (!isset($file["map"]) || strlen($file["map"]) < 1)
			{
				continue;
			}

			$filePath = Main\Loader::getDocumentRoot().$file["map"];
			if (file_exists($filePath) && ($content = file_get_contents($filePath)) !== false)
			{
				if ($sections !== "")
				{
					$sections .= ",";
				}

				$dirPath = IO\Path::getDirectory($file["source"]);
				$sourceName = IO\Path::getName($file["source"]);
				$minName = IO\Path::getName($file["min"]);

				$sourceMap = str_replace(
					array($sourceName, $minName),
					array($dirPath."/".$sourceName, $dirPath."/".$minName),
					$content
				);
				$sections .= '{"offset": { "line": '.$file["line"].', "column": 0 }, "map": '.$sourceMap.'}';
			}
		}

		return '{"version":3, "file":"'.$fileName.'", "sections": ['.$sections.']}';
	}

	/**
	 * Write optimized css, js files or info file
	 *
	 * @param string $filePath - Path for optimized css, js or info file
	 * @param string $content - File contents
	 * @param bool $gzip - For disabled gzip
	 * @return bool - TRUE or FALSE result
	 */
	function write($filePath, $content, $gzip = true)
	{
		$result = false;
		$fnTmp = $filePath.'.tmp';

		if(!CheckDirPath($filePath) || !$fh = fopen($fnTmp, "wb"))
		{
			return $result;
		}

		$written = fwrite($fh, $content);
		$len = Main\Text\BinaryString::getLength($content);
		fclose($fh);

		self::unlink($filePath);
		if($written === $len)
		{
			$result = true;
			rename($fnTmp, $filePath);
			if($gzip && self::gzipEnabled())
			{
				$fnTmpGz = $filePath.'.tmp.gz';
				$fnGz = $filePath.'.gz';

				if($gz = gzopen($fnTmpGz, 'wb9f'))
				{
					$writtenGz = @gzwrite ($gz, $content);
					gzclose($gz);

					self::unlink($fnGz);
					if($writtenGz === $len)
					{
						rename($fnTmpGz, $fnGz);
					}
					self::unlink($fnTmpGz);
				}
			}
		}
		self::unlink($fnTmp);
		return $result;
	}

	/**
	 * Delete cache files
	 * @param string $fileName - Name of file to remove
	 * @return bool
	 */
	private static function unlink($fileName)
	{
		//This checks for Zend Server CE in order to suppress warnings
		if (function_exists('accelerator_reset'))
		{
			@chmod($fileName, BX_FILE_PERMISSIONS);
			if (@unlink($fileName))
				return true;
		}
		else
		{
			if (file_exists($fileName))
			{
				@chmod($fileName, BX_FILE_PERMISSIONS);
				if (unlink($fileName))
					return true;
			}
		}
		return false;
	}
}