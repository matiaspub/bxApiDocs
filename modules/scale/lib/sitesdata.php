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
	 * @return string
	 */
	public static function getKernelSite()
	{
		foreach(self::getList() as $siteId => $siteParams)
			if($siteParams['SiteInstall'] == 'kernel')
				return $siteId;

		return '';
	}

	/**
	 * @return string
	 */
	public static function getKernelRoot()
	{
		foreach(self::getList() as $siteId => $siteParams)
			if($siteParams['SiteInstall'] == 'kernel')
				return $siteParams['DocumentRoot'];

		return '';
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
			$dbName = $connection->getDatabase();
		}

		static $result = array();

		if(!isset($result[$dbName]))
		{
			$resSite = array();
			$shellAdapter = new ShellAdapter();
			$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-sites -o json -a list -d ".$dbName);
			$sitesData = $shellAdapter->getLastOutput();

			if($execRes)
			{
				$arData = json_decode($sitesData, true);

				if(isset($arData["params"]))
					$resSite = $arData["params"];

				$rsSite = \Bitrix\Main\SiteTable::getList();

				while ($site = $rsSite->fetch())
				{
					foreach($resSite as $siteId => $siteInfo)
					{
						$docRoot = strlen($site["DOC_ROOT"]) > 0 ? $site["DOC_ROOT"] : \Bitrix\Main\Application::getDocumentRoot();

						if($siteInfo["DocumentRoot"] == $docRoot)
						{
							$resSite[$siteId]["NAME"] = $site["NAME"]." (".$site["LID"].") ";
						}
						else
						{
							$resSite[$siteId]["NAME"] = $siteId;
						}
					}
				}
			}

			$result[$dbName] = $resSite;
		}

		return $result[$dbName];
	}
}