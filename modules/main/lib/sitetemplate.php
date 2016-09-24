<?php
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class SiteTemplate
{
	protected $id;

	public function __construct($id)
	{
		$this->id = $id;
	}
}

class SiteTemplateTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_site_template';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'SITE_ID' => array(
				'data_type' => 'string',
			),
			'CONDITION' => array(
				'data_type' => 'string'
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'TEMPLATE' => array(
				'data_type' => 'string'
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID'),
			),
		);
	}

	public static function getCurrentTemplateId($siteId)
	{
		$cacheFlags = Config\Configuration::getValue("cache_flags");
		$ttl = isset($cacheFlags["site_template"]) ? $cacheFlags["site_template"] : 0;

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$field = ($connection->getType() === "mysql") ? "`CONDITION`" : "CONDITION";

		$path2templates = IO\Path::combine(
			Application::getDocumentRoot(),
			Application::getPersonalRoot(),
			"templates"
		);

		if ($ttl === false)
		{
			$sql = "
				SELECT ".$field.", TEMPLATE
				FROM b_site_template
				WHERE SITE_ID = '".$sqlHelper->forSql($siteId)."'
				ORDER BY IF(LENGTH(".$field.") > 0, 1, 2), SORT
				";
			$recordset = $connection->query($sql);
			while ($record = $recordset->fetch())
			{
				$condition = trim($record["CONDITION"]);
				if (($condition != '') && (!@eval("return ".$condition.";")))
					continue;

				if (IO\Directory::isDirectoryExists($path2templates."/".$record["TEMPLATE"]))
					return $record["TEMPLATE"];
			}
		}
		else
		{
			$managedCache = Application::getInstance()->getManagedCache();
			if ($managedCache->read($ttl, "b_site_template"))
			{
				$arSiteTemplateBySite = $managedCache->get("b_site_template");
			}
			else
			{
				$arSiteTemplateBySite = array();
				$sql = "
					SELECT ".$field.", TEMPLATE, SITE_ID
					FROM b_site_template
					WHERE SITE_ID = '".$sqlHelper->forSql($siteId)."'
					ORDER BY SITE_ID, IF(LENGTH(".$field.") > 0, 1, 2), SORT
					";
				$recordset = $connection->query($sql);
				while ($record = $recordset->fetch())
					$arSiteTemplateBySite[$record['SITE_ID']][] = $record;
				$managedCache->set("b_site_template", $arSiteTemplateBySite);
			}

			if (is_array($arSiteTemplateBySite[$siteId]))
			{
				foreach ($arSiteTemplateBySite[$siteId] as $record)
				{
					$condition = trim($record["CONDITION"]);
					if (($condition != '') && (!@eval("return ".$condition.";")))
						continue;

					if (IO\Directory::isDirectoryExists($path2templates."/".$record["TEMPLATE"]))
						return $record["TEMPLATE"];
				}
			}
		}

		return ".default";
	}
}