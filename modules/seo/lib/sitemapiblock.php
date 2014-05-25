<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Entity;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Text\Converter;

class SitemapIblockTable extends Entity\DataManager
{
	const ACTIVE = 'Y';
	const INACTIVE = 'N';

	const TYPE_ELEMENT = 'E';
	const TYPE_SECTION = 'S';

	protected static $iblockCache = array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_seo_sitemap_iblock';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'SITEMAP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SITEMAP' => array(
				'data_type' => 'Bitrix\Seo\SitemapTable',
				'reference' => array('=this.SITEMAP_ID' => 'ref.ID'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\IblockTable',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID'),
			),
		);

		return $fieldsMap;
	}

	public static function clearBySitemap($sitemapId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$query = $connection->query("
DELETE
FROM ".self::getTableName()."
WHERE SITEMAP_ID='".intval($sitemapId)."'
");
	}

	public static function getByIblock($arFields, $itemType)
	{
		$arSitemaps = array();

		if(!isset(self::$iblockCache[$arFields['IBLOCK_ID']]))
		{
			self::$iblockCache[$arFields['IBLOCK_ID']] = array();

			$dbRes = self::getList(array(
				'filter' => array(
					'IBLOCK_ID' => $arFields['IBLOCK_ID']
				),
				'select' => array('SITEMAP_ID',
					'SITE_ID' => 'SITEMAP.SITE_ID', 'SITEMAP_SETTINGS' => 'SITEMAP.SETTINGS',
					'IBLOCK_CODE' => 'IBLOCK.CODE', 'IBLOCK_XML_ID' => 'IBLOCK.XML_ID',
					'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
					'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
				)
			));

			while($arRes = $dbRes->fetch())
			{
				self::$iblockCache[$arFields['IBLOCK_ID']][] = $arRes;
			}
		}

		foreach(self::$iblockCache[$arFields['IBLOCK_ID']] as $arRes)
		{
			$arSitemapSettings = unserialize($arRes['SITEMAP_SETTINGS']);

			if($itemType == self::TYPE_SECTION)
			{
				$bAdd = self::checkSection(
					$arFields['ID'],
					$arSitemapSettings['IBLOCK_SECTION_SECTION'][$arFields['IBLOCK_ID']],
					$arSitemapSettings['IBLOCK_SECTION'][$arFields['IBLOCK_ID']]
				);
			}
			else
			{
				if(is_array($arFields['IBLOCK_SECTION']) && count($arFields['IBLOCK_SECTION']) > 0)
				{
					foreach($arFields['IBLOCK_SECTION'] as $sectionId)
					{
						$bAdd = self::checkSection(
							$sectionId,
							$arSitemapSettings['IBLOCK_SECTION_ELEMENT'][$arFields['IBLOCK_ID']],
							$arSitemapSettings['IBLOCK_ELEMENT'][$arFields['IBLOCK_ID']]
						);

						if($bAdd)
						{
							break;
						}
					}
				}
				else
				{
					$bAdd = $arSitemapSettings['IBLOCK_ELEMENT'][$arFields['IBLOCK_ID']] == 'Y';
				}
			}

			if($bAdd)
			{
				$arSitemaps[] = array(
					'IBLOCK_CODE' => $arRes['IBLOCK_CODE'],
					'IBLOCK_XML_ID' => $arRes['IBLOCK_XML_ID'],
					'DETAIL_PAGE_URL' => $arRes['DETAIL_PAGE_URL'],
					'SECTION_PAGE_URL' => $arRes['SECTION_PAGE_URL'],
					'SITE_ID' => $arRes['SITE_ID'],
					'PROTOCOL' => $arSitemapSettings['PROTO'] == 1 ? 'https' : 'http',
					'DOMAIN' => $arSitemapSettings['DOMAIN'],
					'ROBOTS' => $arSitemapSettings['ROBOTS'],
					'SITEMAP_DIR' => $arSitemapSettings['DIR'],
					'SITEMAP_FILE' => $arSitemapSettings['FILENAME_INDEX'],
					'SITEMAP_FILE_IBLOCK' => $arSitemapSettings['FILENAME_IBLOCK'],
				);
			}
		}

		return $arSitemaps;
	}

	public static function checkSection($SECTION_ID, $arSectionSettings, $defaultValue)
	{
		$value = $defaultValue;

		if(is_array($arSectionSettings) && count($arSectionSettings) > 0)
		{
			while ($SECTION_ID > 0)
			{
				if(isset($arSectionSettings[$SECTION_ID]))
				{
					$value = $arSectionSettings[$SECTION_ID];
					break;
				}

				$dbRes = \CIBlockSection::getList(array(), array('ID' => $SECTION_ID), false, array('ID', 'IBLOCK_SECTION_ID'));
				$arSection = $dbRes->fetch();

				$SECTION_ID = $arSection["IBLOCK_SECTION_ID"];
			}
		}

		return $value === 'Y';
	}
}

class SitemapIblock
{
	private static $arBeforeActions = array(
		'BEFOREDELETEELEMENT' => array(array(),array()),
		'BEFOREDELETESECTION' => array(array(),array()),
		'BEFOREUPDATEELEMENT' => array(array(),array()),
		'BEFOREUPDATESECTION' => array(array(),array()),
	);

	public static function __callStatic($name, $arguments)
	{
		$name = ToUpper($name);

		switch($name)
		{
			case 'ADDELEMENT':
			case 'ADDSECTION':
				if(
					$arguments[0]["ID"] > 0
					&& $arguments[0]['IBLOCK_ID'] > 0
					&& $arguments[0]['ACTIVE'] == 'Y'
				)
				{
					// we recieve array reference here
					$arFields = array();
					foreach($arguments[0] as $key => $value)
					{
						$arFields[$key] = $value;
					}

					self::actionAdd($name, $arFields);
				}
			break;

			case 'BEFOREDELETEELEMENT':
			case 'BEFOREDELETESECTION':
			case 'BEFOREUPDATEELEMENT':
			case 'BEFOREUPDATESECTION':
				$ID = $arguments[0];
				if(is_array($ID))
					$ID = $ID['ID'];

				if($ID > 0)
				{
					$bElement = $name == 'BEFOREDELETEELEMENT' || $name == 'BEFOREUPDATEELEMENT';

					$dbFields = $bElement
						? \CIBlockElement::getByID($ID)
						: \CIBlockSection::getByID($ID);

					$arFields = $dbFields->getNext();
					if($arFields)
					{
						if($bElement && !self::checkElement($arFields))
						{
							return;
						}

						$arSitemaps = SitemapIblockTable::getByIblock(
							$arFields,
							$bElement ? SitemapIblockTable::TYPE_ELEMENT : SitemapIblockTable::TYPE_SECTION
						);

						if(count($arSitemaps) > 0)
						{
							self::$arBeforeActions[$name][intval($bElement)][$ID] = array(
								'URL' => $bElement
									? $arFields['~DETAIL_PAGE_URL']
									: $arFields['~SECTION_PAGE_URL'],
								'FIELDS' => $arFields,
								'SITEMAPS' => $arSitemaps,
							);
						}
					}
				}
			break;

			case 'DELETEELEMENT':
			case 'DELETESECTION':
			case 'UPDATEELEMENT':
			case 'UPDATESECTION':

				$arFields = $arguments[0];
				$bElement = $name == 'DELETEELEMENT' || $name == 'UPDATEELEMENT';

				if(
					is_array($arFields)
					&& $arFields['ID'] > 0
					&& isset(self::$arBeforeActions['BEFORE'.$name][intval($bElement)][$arFields['ID']])
				)
				{
					if($name == 'DELETEELEMENT' || $name == 'DELETESECTION')
					{
						self::actionDelete(self::$arBeforeActions['BEFORE'.$name][intval($bElement)][$arFields['ID']]);
					}
					else
					{
						self::actionUpdate(self::$arBeforeActions['BEFORE'.$name][intval($bElement)][$arFields['ID']], $bElement);
					}
				}

			break;

		}
	}

	protected static function checkElement(&$arFields)
	{
		if($arFields['WF'] === 'Y')
		{
			if(
				$arFields['WF_PARENT_ELEMENT_ID'] > 0
				&& $arFields['WF_PARENT_ELEMENT_ID'] != $arFields['ID']
				&& $arFields['WF_STATUS_ID'] == 1
			)
			{
				$arFields['ID'] = $arFields['WF_PARENT_ELEMENT_ID'];
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	protected static function actionUpdate($arData, $bElement)
	{
		$arFields = $arData['FIELDS'];
		foreach($arData['SITEMAPS'] as $arSitemap)
		{
			$fileName = str_replace(
				array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
				array($arFields['IBLOCK_ID'], $arSitemap['IBLOCK_CODE'], $arSitemap['IBLOCK_XML_ID']),
				$arSitemap['SITEMAP_FILE_IBLOCK']
			);

			$rule = array(
				'url' => $bElement
					? $arFields['DETAIL_PAGE_URL']
					: $arFields['SECTION_PAGE_URL'],
				'lastmod' => MakeTimeStamp($arFields['TIMESTAMP_X'])
			);

			$sitemapFile = new SitemapFile($fileName, $arSitemap);
			$sitemapFile->removeEntry($arData['URL']);
			$sitemapFile->appendIblockEntry($rule['url'], $rule['lastmod']);

			$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
			$sitemapIndex->appendIndexEntry($sitemapFile);

			if($arSitemap['ROBOTS'] == 'Y')
			{
				$robotsFile = new RobotsFile($arSitemap['SITE_ID']);
				$robotsFile->addRule(
					array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
				);
			}
		}
	}

	protected static function actionDelete($arData)
	{
		$arFields = $arData['FIELDS'];
		foreach($arData['SITEMAPS'] as $arSitemap)
		{
			$fileName = str_replace(
				array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
				array($arFields['IBLOCK_ID'], $arSitemap['IBLOCK_CODE'], $arSitemap['IBLOCK_XML_ID']),
				$arSitemap['SITEMAP_FILE_IBLOCK']
			);

			$sitemapFile = new SitemapFile($fileName, $arSitemap);
			$sitemapFile->removeEntry($arData['URL']);

			$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
			$sitemapIndex->appendIndexEntry($sitemapFile);
		}
	}

	protected static function actionAdd($name, $arFields)
	{
		if($name == 'ADDELEMENT')
		{
			if(!self::checkElement($arFields))
			{
				return;
			}

			// we don't have the GLOBAL_ACTIVE flag in $arFields so we should check it manually
			if(is_array($arFields['IBLOCK_SECTION']) && count($arFields['IBLOCK_SECTION']) > 0)
			{
				$arNewSections = array();
				$arFilter = array('ID' => $arFields['IBLOCK_SECTION'], 'IBLOCK_ID' => $arFields['IBLOCK_ID'], 'GLOBAL_ACTIVE' => 'Y');

				$dbRes = \CIBlockSection::getList(array(), $arFilter, false, array('ID'));
				while($ar = $dbRes->fetch())
				{
					$arNewSections[] = $ar['ID'];
				}

				if(count($arNewSections) <= 0)
				{
					// element is added to inactive sections
					return;
				}

				$arFields['IBLOCK_SECTION'] = $arNewSections;
			}
		}
		elseif($name == 'ADDSECTION')
		{
			$dbRes = \CIBlockSection::getList(array(), array('ID' => $arFields['ID'], 'GLOBAL_ACTIVE' => 'Y'), false, array('ID'));
			if(!$dbRes->fetch())
			{
				// section is added to inactive branch
				return;
			}
		}

		$arSitemaps = SitemapIblockTable::getByIblock(
			$arFields,
			$name == 'ADDSECTION' ? SitemapIblockTable::TYPE_SECTION : SitemapIblockTable::TYPE_ELEMENT
		);

		$arFields['TIMESTAMP_X'] = ConvertTimeStamp(false, "FULL");

		if(isset($arFields['IBLOCK_SECTION']) && is_array($arFields['IBLOCK_SECTION']) && count($arFields['IBLOCK_SECTION']) > 0)
		{
			$arFields['IBLOCK_SECTION_ID'] = min($arFields['IBLOCK_SECTION']);
		}

		if(count($arSitemaps) > 0)
		{
			$arSiteDirs = array();
			$dbSite = SiteTable::getList(array('select' => array('LID', 'DIR')));
			while($arSite = $dbSite->fetch())
			{
				$arSiteDirs[$arSite['LID']] = $arSite['DIR'];
			}

			foreach($arSitemaps as $arSitemap)
			{
				$arFields['LANG_DIR'] = $arSiteDirs[$arSitemap['SITE_ID']];

				$rule = array(
					'url' => $name == 'ADDSECTION'
						? \CIBlock::replaceDetailUrl($arSitemaps[0]['SECTION_PAGE_URL'], $arFields, false, "S")
						: \CIBlock::replaceDetailUrl($arSitemaps[0]['DETAIL_PAGE_URL'], $arFields, false, "E"),
					'lastmod' => MakeTimeStamp($arFields['TIMESTAMP_X'])
				);

				$fileName = str_replace(
					array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
					array($arFields['IBLOCK_ID'], $arSitemap['IBLOCK_CODE'], $arSitemap['IBLOCK_XML_ID']),
					$arSitemap['SITEMAP_FILE_IBLOCK']
				);

				$sitemapFile = new SitemapFile($fileName, $arSitemap);
				$sitemapFile->appendIblockEntry($rule['url'], $rule['lastmod']);

				$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
				$sitemapIndex->appendIndexEntry($sitemapFile);

				if($arSitemap['ROBOTS'] == 'Y')
				{
					$robotsFile = new RobotsFile($arSitemap['SITE_ID']);
					$robotsFile->addRule(
						array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
					);
				}
			}
		}
	}
}
