<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Text\Converter;

/**
 * Generates index file from sitemap files list
 * Class SitemapIndex
 * @package Bitrix\Seo
 */
class SitemapIndex
	extends SitemapFile
{
	const FILE_HEADER = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	const FILE_FOOTER = '</sitemapindex>';

	const ENTRY_TPL = '<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>';

	public function createIndex($arIndex)
	{
		$str = self::XML_HEADER.self::FILE_HEADER;

		foreach ($arIndex as $file)
		{
			if(!$file->isSystem() && $file->isExists())
			{
				$str .= sprintf(
					self::ENTRY_TPL,
					Converter::getXmlConverter()->encode($this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$this->getFileUrl($file)),
					date(c, $file->getModificationTime())
				);
			}
		}

		$str .= self::FILE_FOOTER;

		$this->putContents($str);
	}

	public function appendIndexEntry($file)
	{
		if($this->isExists() && $file->isExists())
		{
			$fileUrlEnc = Converter::getXmlConverter()->encode($this->settings['PROTOCOL'].'://'.\CBXPunycode::toASCII($this->settings['DOMAIN'], $e = null).$this->getFileUrl($file));

			$contents = $this->getContents();

			$reg = "/".sprintf(preg_quote(self::ENTRY_TPL, "/"), preg_quote($fileUrlEnc, "/"), "[^<]*")."/";

			$newEntry = sprintf(
				self::ENTRY_TPL,
				$fileUrlEnc,
				date(c, $file->getModificationTime($file))
			);

			$count = 0;
			$contents = preg_replace($reg, $newEntry, $contents, 1, $count);

			if($count <= 0)
			{
				$contents = substr($contents, 0, -strlen(self::FILE_FOOTER))
					.$newEntry.self::FILE_FOOTER;
			}

			$this->putContents($contents);
		}
		else
		{
			$this->createIndex(array($file));
		}
	}
}