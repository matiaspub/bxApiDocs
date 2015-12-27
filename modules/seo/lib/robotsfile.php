<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;

class RobotsFile
	extends IO\File
{
	const ROBOTS_FILE_NAME = 'robots.txt';
	const SECTION_RULE = 'User-Agent';
	const SITEMAP_RULE = 'Sitemap';

	/**
	 * Standard robots.txt rules tell us that at least one Disallow instruction should be put in robots.txt for its correct interpretation. But Yandex interprets empty Disallow rule as total allowance and skips all further Disallow rules.
	 *
	 * @deprecated
	 */
	const EMPTY_DISALLOW_RULE = 'Disallow: # empty Disallow instruction SHOULD be there';

	protected $siteId = '';
	protected $documentRoot;

	protected $robotsFile = null;

	protected $contents = array();
	protected $bLoaded = false;

	public function __construct($siteId)
	{
		$this->siteId = $siteId;
		$this->documentRoot = SiteTable::getDocumentRoot($this->siteId);

		parent::__construct(IO\Path::combine($this->documentRoot, self::ROBOTS_FILE_NAME));
	}

	public function addRule($rule, $section = '*', $bCheckUnique = true)
	{
		$this->load();
		if($bCheckUnique)
		{
			$strRule = ToUpper($this->getRuleText($rule));
			$arRules = $this->getSection($section);
			foreach($arRules as $existingRule)
			{
				$strExistingRule = ToUpper($this->getRuleText($existingRule));
				if($strRule == $strExistingRule)
				{
					return true;
				}
			}
		}

		$this->addSectionRule($section, $rule);

		$this->save();
	}

	static public function getRuleText($rule)
	{
		return implode(': ', $rule);
	}

	static public function parseRule($strRule)
	{
		if(substr($strRule, 0, 1) == '#')
		{
			return array($strRule);
		}
		else
		{
			return preg_split("/:\s*/", $strRule, 2);
		}
	}

	public function getRules($rule, $section = '*')
	{
		$this->load();
		$arRules = array();
		if(isset($this->contents[$section]))
		{
			$rule = ToUpper($rule);
			foreach ($this->contents[$section] as $arRule)
			{
				if(ToUpper($arRule[0]) == $rule)
				{
					$arRules[] = $arRule;
				}
			}
		}
		return $arRules;
	}

	protected function getSection($section)
	{
		$section = ToUpper($section);
		foreach($this->contents as $currentAgent => $arRules)
		{
			if(ToUpper($currentAgent) == $section)
			{
				return $arRules;
			}
		}

		return array();
	}

	protected function addSectionRule($section, $rule)
	{
		$section = ToUpper($section);
		foreach($this->contents as $currentAgent => $arRules)
		{
			if(ToUpper($currentAgent) == $section)
			{
				$this->contents[$section][] = $rule;
				return;
			}
		}

		$this->contents[$section] = array($rule);
	}

	protected function load()
	{
		if($this->isExists() && !$this->bLoaded)
		{
			$contents = $this->getContents();
			$arLines = preg_split("/\\n+/", $contents);
			$currentAgent = '';
			if(count($arLines) > 0)
			{
				$strSectionCompare = ToUpper(self::SECTION_RULE);
				foreach($arLines as $line)
				{
					$line = trim($line);

					if(strlen($line) > 0)
					{
						$rule = $this->parseRule($line);
						if(ToUpper($rule[0]) == $strSectionCompare)
						{
							$currentAgent = $rule[1];
						}
						elseif ($currentAgent != '')
						{

							if(!is_array($this->contents[$currentAgent]))
							{
								$this->contents[$currentAgent] = array();
							}

							$this->contents[$currentAgent][] = $rule;
						}
					}
				}
			}
		}
		$this->bLoaded = true;
	}

	protected function save()
	{
		if(count($this->contents) > 0)
		{
			$strContent = '';
			$nn = "\r\n";

			foreach($this->contents as $currentAgent => $arRules)
			{
				if(is_array($arRules) && count($arRules) > 0)
				{
					$strContent .=
						($strContent == '' ? '' : $nn)
						.$this->getRuleText(array(self::SECTION_RULE, $currentAgent)).$nn;

					foreach ($arRules as $rule)
					{
						$strContent .= $this->getRuleText($rule).$nn;
					}
				}
			}

			$this->putContents($strContent);
		}
	}
}

