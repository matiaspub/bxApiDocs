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

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_sitemap_iblock';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
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

	/**
	 * Clears all iblock links on sitemap settings deletion.
	 *
	 * @param int $sitemapId Sitemap settings ID.
	 *
	 * @return void
	 */
	public static function clearBySitemap($sitemapId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$query = $connection->query("
DELETE
FROM ".self::getTableName()."
WHERE SITEMAP_ID='".intval($sitemapId)."'
");
	}

	/**
	 * Returns array of data for sitemap update due to some iblock action.
	 *
	 * @param array $fields Iblock element or section fields array.
	 * @param string $itemType SitemapIblockTable::TYPE_ELEMENT || SitemapIblockTable::TYPE_SECTION.
	 *
	 * @return array Array of sitemap settings
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getByIblock($fields, $itemType)
	{
		$sitemaps = array();

		if(!isset(self::$iblockCache[$fields['IBLOCK_ID']]))
		{
			self::$iblockCache[$fields['IBLOCK_ID']] = array();

			$dbRes = self::getList(array(
				'filter' => array(
					'IBLOCK_ID' => $fields['IBLOCK_ID']
				),
				'select' => array('SITEMAP_ID',
					'SITE_ID' => 'SITEMAP.SITE_ID', 'SITEMAP_SETTINGS' => 'SITEMAP.SETTINGS',
					'IBLOCK_CODE' => 'IBLOCK.CODE', 'IBLOCK_XML_ID' => 'IBLOCK.XML_ID',
					'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
					'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
				)
			));

			while($res = $dbRes->fetch())
			{
				self::$iblockCache[$fields['IBLOCK_ID']][] = $res;
			}
		}

		foreach(self::$iblockCache[$fields['IBLOCK_ID']] as $res)
		{
			$sitemapSettings = unserialize($res['SITEMAP_SETTINGS']);

			$add = false;

			if($itemType == self::TYPE_SECTION)
			{
				$add = self::checkSection(
					$fields['ID'],
					$sitemapSettings['IBLOCK_SECTION_SECTION'][$fields['IBLOCK_ID']],
					$sitemapSettings['IBLOCK_SECTION'][$fields['IBLOCK_ID']]
				);
			}
			else
			{
				if(is_array($fields['IBLOCK_SECTION']) && count($fields['IBLOCK_SECTION']) > 0)
				{
					foreach($fields['IBLOCK_SECTION'] as $sectionId)
					{
						$add = self::checkSection(
							$sectionId,
							$sitemapSettings['IBLOCK_SECTION_ELEMENT'][$fields['IBLOCK_ID']],
							$sitemapSettings['IBLOCK_ELEMENT'][$fields['IBLOCK_ID']]
						);

						if($add)
						{
							break;
						}
					}
				}
				else
				{
					$add = $sitemapSettings['IBLOCK_ELEMENT'][$fields['IBLOCK_ID']] == 'Y';
				}
			}

			if($add)
			{
				$sitemaps[] = array(
					'IBLOCK_CODE' => $res['IBLOCK_CODE'],
					'IBLOCK_XML_ID' => $res['IBLOCK_XML_ID'],
					'DETAIL_PAGE_URL' => $res['DETAIL_PAGE_URL'],
					'SECTION_PAGE_URL' => $res['SECTION_PAGE_URL'],
					'SITE_ID' => $res['SITE_ID'],
					'PROTOCOL' => $sitemapSettings['PROTO'] == 1 ? 'https' : 'http',
					'DOMAIN' => $sitemapSettings['DOMAIN'],
					'ROBOTS' => $sitemapSettings['ROBOTS'],
					'SITEMAP_DIR' => $sitemapSettings['DIR'],
					'SITEMAP_FILE' => $sitemapSettings['FILENAME_INDEX'],
					'SITEMAP_FILE_IBLOCK' => $sitemapSettings['FILENAME_IBLOCK'],
				);
			}
		}

		return $sitemaps;
	}

	/**
	 * Checks if section $sectionId should be added to sitemap.
	 *
	 * @param int $sectionId Section ID.
	 * @param array $sectionSettings Sitemap section settings array.
	 * @param bool $defaultValue Default value for situation of settings absence.
	 *
	 * @return bool
	 */
	public static function checkSection($sectionId, $sectionSettings, $defaultValue)
	{
		$value = $defaultValue;

		if(is_array($sectionSettings) && count($sectionSettings) > 0)
		{
			while ($sectionId > 0)
			{
				if(isset($sectionSettings[$sectionId]))
				{
					$value = $sectionSettings[$sectionId];
					break;
				}

				$dbRes = \CIBlockSection::getList(array(), array('ID' => $sectionId), false, array('ID', 'IBLOCK_SECTION_ID'));
				$section = $dbRes->fetch();

				$sectionId = $section["IBLOCK_SECTION_ID"];
			}
		}

		return $value === 'Y';
	}
}

class SitemapIblock
{
	private static $beforeActions = array(
		'BEFOREDELETEELEMENT' => array(array(),array()),
		'BEFOREDELETESECTION' => array(array(),array()),
		'BEFOREUPDATEELEMENT' => array(array(),array()),
		'BEFOREUPDATESECTION' => array(array(),array()),
	);

	/**
	 * Event handler for multiple IBlock events
	 */
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
					&& (
						!isset($arguments[0]['ACTIVE'])
						|| $arguments[0]['ACTIVE'] == 'Y'
					)
				)
				{
					// we recieve array reference here
					$fields = array();
					foreach($arguments[0] as $key => $value)
					{
						$fields[$key] = $value;
					}

					if(!isset($fields['EXTERNAL_ID']) && isset($fields['XML_ID']))
					{
						$fields['EXTERNAL_ID'] = $fields['XML_ID'];
					}

					self::actionAdd($name, $fields);
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
					$element = $name == 'BEFOREDELETEELEMENT' || $name == 'BEFOREUPDATEELEMENT';

					$dbFields = $element
						? \CIBlockElement::getByID($ID)
						: \CIBlockSection::getByID($ID);

					$fields = $dbFields->getNext();
					if($fields)
					{
						if($element && !self::checkElement($fields))
						{
							return;
						}

						$sitemaps = SitemapIblockTable::getByIblock(
							$fields,
							$element ? SitemapIblockTable::TYPE_ELEMENT : SitemapIblockTable::TYPE_SECTION
						);

						if(count($sitemaps) > 0)
						{
							self::$beforeActions[$name][intval($element)][$ID] = array(
								'URL' => $element
									? $fields['~DETAIL_PAGE_URL']
									: $fields['~SECTION_PAGE_URL'],
								'FIELDS' => $fields,
								'SITEMAPS' => $sitemaps,
							);
						}
					}
				}
			break;

			case 'DELETEELEMENT':
			case 'DELETESECTION':
			case 'UPDATEELEMENT':
			case 'UPDATESECTION':

				$fields = $arguments[0];
				$element = $name == 'DELETEELEMENT' || $name == 'UPDATEELEMENT';

				if(
					is_array($fields)
					&& $fields['ID'] > 0
					&& isset(self::$beforeActions['BEFORE'.$name][intval($element)][$fields['ID']])
				)
				{
					if($fields['RESULT'] !== false)
					{
						if($name == 'DELETEELEMENT' || $name == 'DELETESECTION')
						{
							self::actionDelete(self::$beforeActions['BEFORE'.$name][intval($element)][$fields['ID']]);
						}
						else
						{
							self::actionUpdate(self::$beforeActions['BEFORE'.$name][intval($element)][$fields['ID']], $element);
						}
					}

					unset(self::$beforeActions['BEFORE'.$name][intval($element)][$fields['ID']]);
				}

			break;

		}
	}

	/**
	 * Checks if element is a real element, not a workflow item
	 *
	 * @param array $fields Element fields.
	 *
	 * @return bool
	 */
	protected static function checkElement(&$fields)
	{
		if($fields['WF'] === 'Y')
		{
			if(
				$fields['WF_PARENT_ELEMENT_ID'] > 0
				&& $fields['WF_PARENT_ELEMENT_ID'] != $fields['ID']
				&& $fields['WF_STATUS_ID'] == 1
			)
			{
				$fields['ID'] = $fields['WF_PARENT_ELEMENT_ID'];
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Processes actions on IBlock element or section update
	 *
	 * @param array $data Data got from SitemapIblockTable::getByIblock() + element/section data + prev link data got from event handler.
	 * @param bool $element Element or section.
	 */
	protected static function actionUpdate($data, $element)
	{
		$fields = $data['FIELDS'];
		foreach($data['SITEMAPS'] as $sitemap)
		{
			$fileName = str_replace(
				array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
				array($fields['IBLOCK_ID'], $sitemap['IBLOCK_CODE'], $sitemap['IBLOCK_XML_ID']),
				$sitemap['SITEMAP_FILE_IBLOCK']
			);

			if($element)
			{
				$dbRes = \CIBlockElement::getByID($fields["ID"]);
			}
			else
			{
				$dbRes = \CIBlockSection::getByID($fields["ID"]);
			}

			$newFields = $dbRes->fetch();

			$rule = array(
				'url' => $element
					? \CIBlock::replaceDetailUrl($sitemap['DETAIL_PAGE_URL'], $newFields, false, "E")
					: \CIBlock::replaceDetailUrl($sitemap['SECTION_PAGE_URL'], $newFields, false, "S"),
				'lastmod' => MakeTimeStamp($fields['TIMESTAMP_X'])
			);

			$sitemapFile = new SitemapFile($fileName, $sitemap);
			$sitemapFile->removeEntry($data['URL']);
			$sitemapFile->appendIblockEntry($rule['url'], $rule['lastmod']);

			$sitemapIndex = new SitemapIndex($sitemap['SITEMAP_FILE'], $sitemap);
			$sitemapIndex->appendIndexEntry($sitemapFile);

			if($sitemap['ROBOTS'] == 'Y')
			{
				$robotsFile = new RobotsFile($sitemap['SITE_ID']);
				$robotsFile->addRule(
					array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
				);
			}

			unset($sitemapFile);
			unset($sitemapIndex);
			unset($robotsFile);
		}
	}

	/**
	 * Processes actions on IBlock element or section delete.
	 *
	 * @param array $data Data got from SitemapIblockTable::getByIblock() + element/section data + prev link data got from event handler.
	 */
	protected static function actionDelete($data)
	{
		$fields = $data['FIELDS'];
		foreach($data['SITEMAPS'] as $sitemap)
		{
			$fileName = str_replace(
				array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
				array($fields['IBLOCK_ID'], $sitemap['IBLOCK_CODE'], $sitemap['IBLOCK_XML_ID']),
				$sitemap['SITEMAP_FILE_IBLOCK']
			);

			$sitemapFile = new SitemapFile($fileName, $sitemap);
			$sitemapFile->removeEntry($data['URL']);

			$sitemapIndex = new SitemapIndex($sitemap['SITEMAP_FILE'], $sitemap);
			$sitemapIndex->appendIndexEntry($sitemapFile);
		}
	}

	/**
	 * Processes actions on IBlock element or section add.
	 *
	 * @param string $name Event handler name.
	 * @param array $fields Element/section fields.
	 */
	protected static function actionAdd($name, $fields)
	{
		if($name == 'ADDELEMENT')
		{
			if(!self::checkElement($fields))
			{
				return;
			}

			// we don't have the GLOBAL_ACTIVE flag in fields so we should check it manually
			if(is_array($fields['IBLOCK_SECTION']) && count($fields['IBLOCK_SECTION']) > 0)
			{
				$newSections = array();
				$filter = array(
					'ID' => $fields['IBLOCK_SECTION'],
					'IBLOCK_ID' => $fields['IBLOCK_ID'],
					'GLOBAL_ACTIVE' => 'Y'
				);

				$dbRes = \CIBlockSection::getList(array(), $filter, false, array('ID'));
				while($ar = $dbRes->fetch())
				{
					$newSections[] = $ar['ID'];
				}

				if(count($newSections) <= 0)
				{
					// element is added to inactive sections
					return;
				}

				$fields['IBLOCK_SECTION'] = $newSections;
			}
		}
		elseif($name == 'ADDSECTION')
		{
			$dbRes = \CIBlockSection::getList(array(), array('ID' => $fields['ID'], 'GLOBAL_ACTIVE' => 'Y'), false, array('ID'));
			if(!$dbRes->fetch())
			{
				// section is added to inactive branch
				return;
			}
		}

		$sitemaps = SitemapIblockTable::getByIblock(
			$fields,
			$name == 'ADDSECTION' ? SitemapIblockTable::TYPE_SECTION : SitemapIblockTable::TYPE_ELEMENT
		);

		$fields['TIMESTAMP_X'] = ConvertTimeStamp(false, "FULL");

		if(isset($fields['IBLOCK_SECTION']) && is_array($fields['IBLOCK_SECTION']) && count($fields['IBLOCK_SECTION']) > 0)
		{
			$fields['IBLOCK_SECTION_ID'] = min($fields['IBLOCK_SECTION']);
		}

		if(count($sitemaps) > 0)
		{
			$siteDirs = array();
			$dbSite = SiteTable::getList(array('select' => array('LID', 'DIR')));
			while($site = $dbSite->fetch())
			{
				$siteDirs[$site['LID']] = $site['DIR'];
			}

			foreach($sitemaps as $sitemap)
			{
				$fields['LANG_DIR'] = $siteDirs[$sitemap['SITE_ID']];

				$rule = array(
					'url' => $name == 'ADDSECTION'
						? \CIBlock::replaceDetailUrl($sitemaps[0]['SECTION_PAGE_URL'], $fields, false, "S")
						: \CIBlock::replaceDetailUrl($sitemaps[0]['DETAIL_PAGE_URL'], $fields, false, "E"),
					'lastmod' => MakeTimeStamp($fields['TIMESTAMP_X'])
				);

				$fileName = str_replace(
					array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
					array($fields['IBLOCK_ID'], $sitemap['IBLOCK_CODE'], $sitemap['IBLOCK_XML_ID']),
					$sitemap['SITEMAP_FILE_IBLOCK']
				);

				$sitemapFile = new SitemapFile($fileName, $sitemap);
				$sitemapFile->appendIblockEntry($rule['url'], $rule['lastmod']);

				$sitemapIndex = new SitemapIndex($sitemap['SITEMAP_FILE'], $sitemap);
				$sitemapIndex->appendIndexEntry($sitemapFile);

				if($sitemap['ROBOTS'] == 'Y')
				{
					$robotsFile = new RobotsFile($sitemap['SITE_ID']);
					$robotsFile->addRule(
						array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
					);
				}
			}
		}
	}
}
