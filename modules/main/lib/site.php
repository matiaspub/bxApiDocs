<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;
use Bitrix\Main\IO;

class SiteTable extends Entity\DataManager
{
	private static $documentRootCache = array();

	public static function getDocumentRoot($siteId = null)
	{
		if ($siteId === null)
		{
			$context = Application::getInstance()->getContext();
			$siteId = $context->getSite();
		}

		if (!isset(self::$documentRootCache[$siteId]))
		{
			$ar = SiteTable::getRow(array("filter" => array("LID" => $siteId)));
			if ($ar && ($docRoot = $ar["DOC_ROOT"]) && (strlen($docRoot) > 0))
			{
				if (!IO\Path::isAbsolute($docRoot))
					$docRoot = IO\Path::combine(Application::getDocumentRoot(), $docRoot);

				self::$documentRootCache[$siteId] = $docRoot;
			}
			else
			{
				self::$documentRootCache[$siteId] = Application::getDocumentRoot();
			}
		}

		return self::$documentRootCache[$siteId];
	}


	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_lang';
	}

	public static function getMap()
	{
		return array(
			'LID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'DEF' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'DIR' => array(
				'data_type' => 'string'
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
			),
			'DOC_ROOT' => array(
				'data_type' => 'string',
			),
			'DOMAIN_LIMITED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SERVER_NAME' => array(
				'data_type' => 'string'
			),
			'SITE_NAME' => array(
				'data_type' => 'string'
			),
			'EMAIL' => array(
				'data_type' => 'string'
			),
			'CULTURE_ID' => array(
				'data_type' => 'integer',
			),
			'CULTURE' => array(
				'data_type' => 'Bitrix\Main\Localization\Culture',
				'reference' => array('=this.CULTURE_ID' => 'ref.ID'),
			),
		);
	}
}
