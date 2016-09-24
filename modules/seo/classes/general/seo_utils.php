<?
class CSeoUtils
{
	public static function CleanURL($URL)
	{
		if (false !== ($pos = strpos($URL, '?')))
		{
			$query = substr($URL, $pos+1);
			$URL = substr($URL, 0, $pos);

			$arQuery = explode('&', $query);

			$arExcludedParams = array('clear_cache', 'clear_cache_session', 'back_url_admin', 'back_url', 'backurl', 'login', 'logout', 'compress');
			foreach ($arQuery as $key => $param)
			{
				if (false !== ($pos = strpos($param, '=')))
				{
					$param_name = ToLower(substr($param, 0, $pos));
					if (
						substr($param_name, 0, 7) == 'bitrix_'
						|| substr($param_name, 0, 5) == 'show_'
						|| in_array($param_name, $arExcludedParams)
					)
					{
						unset($arQuery[$key]);
					}
				}
			}

			if (count($arQuery) > 0)
			{
				$URL .= '?'.implode('&', $arQuery);
			}
		}

		return $URL;
	}

	public static function getDomainsList()
	{
		static $arDomains = null;

		if($arDomains === null)
		{
			$arDomains = array();

			$dbSites = Bitrix\Main\SiteDomainTable::getList(
				array(
					'select'=>array(
						'DOMAIN', 'LID',
						'SITE_NAME'=>'SITE.NAME', 'SITE_ACTIVE' => 'SITE.ACTIVE',
						'SITE_DIR' => 'SITE.DIR', 'SITE_DOC_ROOT' => 'SITE.DOC_ROOT'
					)
				)
			);

			$defaultDomain = CBXPunycode::ToASCII(Bitrix\Main\Config\Option::getRealValue('main', 'server_name'), $e = null);

			$bCurrentHostFound = false;
			while($arSite = $dbSites->fetch())
			{
				$arDomains[] = $arSite;
				if($arSite['DOMAIN'] == $defaultDomain)
					$bCurrentHostFound = true;
			}

			if(!$bCurrentHostFound)
			{
				$dbDefSite = Bitrix\Main\SiteTable::getList(array(
					'filter' => array('DEF' => 'Y'),
					'select' => array('LID', 'NAME', 'ACTIVE'),
				));
				$arDefSite = $dbDefSite->fetch();
				if($arDefSite)
				{
					array_unshift($arDomains, array(
						'DOMAIN' => $defaultDomain,
						'LID' => $arDefSite['LID'],
						'SITE_NAME' => $arDefSite['NAME'],
						'SITE_ACTIVE' => $arDefSite['ACTIVE'],
						'SITE_DIR' => $arDefSite['DIR'],
						'SITE_DOC_ROOT' => $arDefSite['DOC_ROOT'],
					));
				}
			}
		}

		return $arDomains;
	}

	public static function getDirStructure($bLogical, $site, $path)
	{
		global $USER;

		$arDirContent = array();
		if($USER->CanDoFileOperation('fm_view_listing', array($site, $path)))
		{
			\Bitrix\Main\Loader::includeModule('fileman');

			$arDirs = array();
			$arFiles = array();

			\CFileMan::GetDirList(array($site, $path), $arDirs, $arFiles, array(), array("NAME" => "asc"), "DF", $bLogical, true);

			$arDirContent_t = array_merge($arDirs, $arFiles);
			for($i=0,$l = count($arDirContent_t);$i<$l;$i++)
			{
				$file = $arDirContent_t[$i];
				$arPath = array($site, $file['ABS_PATH']);
				if(
					($file["TYPE"]=="F" && !$USER->CanDoFileOperation('fm_view_file',$arPath))
					|| ($file["TYPE"]=="D" && !$USER->CanDoFileOperation('fm_view_listing',$arPath))
					|| ($file["TYPE"]=="F" && $file["NAME"]==".section.php")
				)
				{
					continue;
				}

				$f = $file['TYPE'] == 'F'
					? new \Bitrix\Main\IO\File($file['PATH'], $site)
					: new \Bitrix\Main\IO\Directory($file['PATH'], $site);

				$p = $f->getName();

				if($f->isSystem()
					|| $file['TYPE'] == 'F' && in_array($p, array("urlrewrite.php"))
					|| $file['TYPE'] == 'D' && preg_match("/\/(bitrix|".\COption::getOptionString("main", "upload_dir", "upload").")\//", "/".$p."/")
				)
				{
					continue;
				}

				$arFileData = array(
					'NAME' => $bLogical ? $file['LOGIC_NAME'] : $p,
					'FILE' => $p,
					'TYPE' => $file['TYPE'],
					'DATA' => $file,
				);

				if(strlen($arFileData['NAME']) <= 0)
					$arFileData['NAME'] = GetMessage('SEO_DIR_LOGICAL_NO_NAME');

				$arDirContent[] = $arFileData;
			}
			unset($arDirContent_t);
		}

		return $arDirContent;
	}
}
?>