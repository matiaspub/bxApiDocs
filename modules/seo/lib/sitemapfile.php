<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\IO\Path;
use Bitrix\Main\IO\File;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Text\Converter;

/**
 * Base class for sitemapfile
 * Class SitemapFile
 * @package Bitrix\Seo
 */
class SitemapFile
	extends File
{
	const XML_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';

	const FILE_HEADER = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	const FILE_FOOTER = '</urlset>';

	const ENTRY_TPL = '<url><loc>%s</loc><lastmod>%s</lastmod></url>';
	const ENTRY_TPL_SEARCH = '<url><loc>%s</loc>';

	const XPATH_URL = '/urlset/url';

	const MAX_SIZE = 5000000;

	const FILE_EXT = '.xml';
	const FILE_PART_SUFFIX = '.part';

	protected $settings = array();
	protected $parser = false;

	protected $partFile = '';
	protected $partList = array();
	protected $part = 0;
	protected $partChanged = false;

	protected $urlToSearch = '';
	protected $urlFound = false;

	public function __construct($fileName, $arSettings)
	{
		$this->settings = array(
			'SITE_ID' => $arSettings['SITE_ID'],
			'PROTOCOL' => $arSettings['PROTOCOL'] == 'https' ? 'https' : 'http',
			'DOMAIN' => $arSettings['DOMAIN'],
		);

		$arSite = SiteTable::getRow(array("filter" => array("LID" => $this->settings['SITE_ID'])));

		$this->siteRoot = Path::combine(
			SiteTable::getDocumentRoot($this->settings['SITE_ID']),
			$arSite['DIR']
		);

		if(substr($fileName, -strlen(self::FILE_EXT)) != self::FILE_EXT)
		{
			$fileName .= self::FILE_EXT;
		}

		if($this->partFile == '')
		{
			$this->partFile = $fileName;
		}

		$this->pathPhysical = null; // hack for object reconstuct during file splitting

		parent::__construct($this->siteRoot.'/'.$fileName, $this->settings['SITE_ID']);

		$this->partChanged = $this->isExists();
	}

	protected function reInit($fileName)
	{
		$this->__construct($fileName, $this->settings);
	}

	public function addHeader()
	{
		$this->partChanged = true;
		$this->putContents(self::XML_HEADER.self::FILE_HEADER);
	}

	protected function isSplitNeeded()
	{
		return $this->isExists() && $this->getFileSize() >= self::MAX_SIZE;
	}

	public function addEntry($entry)
	{
		if($this->isSplitNeeded())
		{
			$this->split();
			$this->addEntry($entry);
		}
		else
		{
			if(!$this->partChanged)
			{
				$this->addHeader();
			}

			$this->putContents(
				sprintf(
					self::ENTRY_TPL,
					Converter::getXmlConverter()->encode($entry['XML_LOC']),
					Converter::getXmlConverter()->encode($entry['XML_LASTMOD'])
				), self::APPEND
			);
		}
	}

	public function split()
	{
		if($this->partChanged)
		{
			$this->addFooter();
		}

		$this->partList[] = $this->getName();
		$this->part++;

		$fileName = $this->partFile;
		$fileName = substr($fileName, 0, -strlen(self::FILE_EXT)).self::FILE_PART_SUFFIX.$this->part.substr($fileName, -strlen(self::FILE_EXT));

		$this->reInit($fileName);

		$this->partChanged = $this->isExists() && !$this->isSplitNeeded();

		return $fileName;
	}

	public function getNameList()
	{
		return $this->isCurrentPartNotEmpty() ? array_merge($this->partList, array($this->getName())) : $this->partList;
	}

	public function isNotEmpty()
	{
		return (count($this->partList) > 0) || $this->isCurrentPartNotEmpty();
	}

	public function isCurrentPartNotEmpty()
	{
		if($this->isExists())
		{
			$c = $this->getContents();
			return strlen($c) > 0 && $c != self::XML_HEADER.self::FILE_HEADER;
		}

		return false;
	}

	public function appendEntry($entry)
	{
		if($this->isSplitNeeded())
		{
			$this->split();
			$this->appendEntry($entry);
		}
		else
		{
			$fd = $this->open('r+');
			fseek($fd, $this->getFileSize()-strlen(self::FILE_FOOTER));
			fwrite($fd, sprintf(
				self::ENTRY_TPL,
				Converter::getXmlConverter()->encode($entry['XML_LOC']),
				Converter::getXmlConverter()->encode($entry['XML_LASTMOD'])
			).self::FILE_FOOTER);
			fclose($fd);
		}
	}

	public function removeEntry($url)
	{
		$url = $this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$url;
		$pattern = sprintf(self::ENTRY_TPL_SEARCH, $url);

		while($this->isExists())
		{
			$c = $this->getContents();
			$p = strpos($c, $pattern);
			if($p !== false)
			{
				$fd = $this->open('r+');
				fseek($fd, intval($p));
				fwrite($fd, str_repeat(" ", strlen(sprintf(
					self::ENTRY_TPL,
					Converter::getXmlConverter()->encode($url),
					Converter::getXmlConverter()->encode(date('c'))
				))));
				fclose($fd);
				break;
			}

			if(!$this->isSplitNeeded())
			{
				break;
			}
			else
			{
				$this->part++;

				$fileName = $this->partFile;
				$fileName = substr($fileName, 0, -strlen(self::FILE_EXT)).self::FILE_PART_SUFFIX.$this->part.substr($fileName, -strlen(self::FILE_EXT));

				$this->reInit($fileName);
			}
		}
	}

	public function addFileEntry(File $f)
	{
		if($f->isExists() && !$f->isSystem())
		{
			$this->addEntry(array(
				'XML_LOC' => $this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$this->getFileUrl($f),
				'XML_LASTMOD' => date('c', $f->getModificationTime()),
			));
		}
	}

	public function addIBlockEntry($url, $modifiedDate)
	{
		$this->addEntry(array(
			'XML_LOC' => $this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$url,
			'XML_LASTMOD' => date('c', $modifiedDate - \CTimeZone::getOffset()),
		));
	}

	public function appendIBlockEntry($url, $modifiedDate)
	{
		if($this->isExists())
		{
			$this->appendEntry(array(
				'XML_LOC' => $this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$url,
				'XML_LASTMOD' => date('c', $modifiedDate - \CTimeZone::getOffset()),
			));
		}
		else
		{
			$this->addHeader();
			$this->addIBlockEntry($url, $modifiedDate);
			$this->addFooter();
		}
	}

	public function addFooter()
	{
		$this->putContents(self::FILE_FOOTER, self::APPEND);
	}

	public function getSiteRoot()
	{
		return $this->siteRoot;
	}

	public function getUrl()
	{
		return $this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$this->getFileUrl($this);
	}

	public function parse()
	{
		if(!$this->parser)
		{
			if($this->isExists())
			{
				$this->parser = new \CDataXML();
				$this->parser->loadString($this->getContents());
			}
		}

		return $this->parser;
	}

	protected function getFileUrl(File $f)
	{
		static $arIndexNames;
		if(!is_array($arIndexNames))
		{
			$arIndexNames = GetDirIndexArray();
		}

		if (substr($this->path, 0, strlen($this->documentRoot)) === $this->documentRoot)
		{
			$path = '/'.substr($f->getPath(), strlen($this->documentRoot));
		}

		$path = Path::convertLogicalToUri($path);

		$path = in_array($f->getName(), $arIndexNames)
			? str_replace('/'.$f->getName(), '/', $path)
			: $path;

		return '/'.ltrim($path, '/');
	}
}