<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class SiteTable extends Entity\DataManager
{
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

	public static function getByDomainAndPath($domain, $path)
	{
		$connection = Application::getDbConnection();
		$helper = $connection->getSqlHelper();

		$domainForSql = $helper->forSql($domain, 255);
		$pathForSql = $helper->forSql($path);

		$sql = "
			SELECT L.*, L.LID as ID
			FROM b_lang L
				LEFT JOIN b_lang_domain LD ON L.LID = LD.LID AND '".$domainForSql."' LIKE CONCAT('%', LD.DOMAIN)
			WHERE ('".$pathForSql."' LIKE CONCAT(L.DIR, '%') OR LD.LID IS NOT NULL)
				AND L.ACTIVE = 'Y'
			ORDER BY
				IF((L.DOMAIN_LIMITED = 'Y' AND LD.LID IS NOT NULL) OR L.DOMAIN_LIMITED <> 'Y',
					IF('".$pathForSql."' LIKE CONCAT(L.DIR, '%'), 3, 1),
					IF('".$pathForSql."' LIKE CONCAT(L.DIR, '%'), 2, 0)
				) DESC,
				LENGTH(L.DIR) DESC,
				L.DOMAIN_LIMITED DESC,
				SORT,
				LENGTH(LD.DOMAIN) DESC
		";

		$siteList = $connection->query($sql);
		return $siteList->fetch();
	}
}
