<?php
namespace Bitrix\Scale;

/**
 * Class SitesData
 * @package Bitrix\Scale *
 */
class SitesData
{
	/**
	 * @param $siteName
	 * @return array site's param
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getSite($siteName, $dbName = false)
	{
		if(strlen($siteName) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("siteName");

		$result = array();
		$sites = self::getList($dbName);

		if(isset($sites[$siteName]))
			$result = $sites[$siteName];

		return $result;
	}

	/**
	 * @param string $dbName
	 * @return array List of all sites & their params
	 */
	public static function getList($dbName = false)
	{
		if(!$dbName)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$dbName = $connection->getDbName();
		}

		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-sites -o json -a list -d ".$dbName);
		$sitesData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($sitesData, true);

			if(isset($arData["params"]))
				$result = $arData["params"];

			$rsSite = \Bitrix\Main\SiteTable::getList();

			while ($site = $rsSite->fetch())
			{
				foreach($result as $siteId => $siteInfo)
				{
					$docRoot = strlen($site["DOC_ROOT"]) > 0 ? $site["DOC_ROOT"] : \Bitrix\Main\Application::getDocumentRoot();

					if($siteInfo["DocumentRoot"] == $docRoot)
					{
						$result[$siteId]["NAME"] = $site["NAME"]." (".$site["LID"].") ";
					}
					else
					{
						$result[$siteId]["NAME"] = $siteId;
					}
				}
			}
		}

		return $result;
	}
}