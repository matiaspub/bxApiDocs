<?php
namespace Bitrix\Seo;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Bitrix\Seo\Engine;

Loc::loadMessages(__FILE__);

/**
 * Class AdvTabEngine
 *
 * Events handler for integration with IBlock element edit form
 *
 * @package Bitrix\Seo
 **/

class AdvTabEngine
{
	public static function eventHandler()
	{
		if(
			Option::get('main', 'vendor', '') == '1c_bitrix'
			&& Loc::getDefaultLang(LANGUAGE_ID) == 'ru'
			&& IsModuleInstalled('socialservices')
		)
		{
			return array(
				"TABSET" => "seo_adv",
				"Check" => array(__CLASS__, 'checkFields'),
				"Action" => array(__CLASS__, 'saveData'),
				"GetTabs" => array(__CLASS__, 'getTabs'),
				"ShowTab" => array(__CLASS__, 'showTab'),
			);
		}
	}

	public static function getTabs($iblockElementInfo)
	{
		$showTab = false;

		$request = Context::getCurrent()->getRequest();

		if($iblockElementInfo["ID"] > 0 && (!isset($request['action']) || $request['action'] != 'copy'))
		{
			$showTab = true;
			if(Loader::includeModule('catalog'))
			{
/*
				$dbRes = CatalogIblockTable::getList(array(
					'filter' => array(
						'=IBLOCK_ID' => $iblockElementInfo["IBLOCK"]["ID"],
						'!PRODUCT_IBLOCK_ID' => 0
					),
					'select' => array('IBLOCK_ID'),
				));
				if($dbRes->fetch())
				{
					$showTab = false;
				}
*/
				if(\CCatalogSku::getInfoByOfferIBlock($iblockElementInfo["IBLOCK"]["ID"]) !== false)
				{
					$showTab = false;
				}
			}
		}

		return $showTab ? array(
			array(
				"DIV" => "seo_adv",
				"SORT" => 4,
				"TAB" => Loc::getMessage("SEO_ADV_TAB"),
				"TITLE" => Loc::getMessage("SEO_ADV_TAB_TITLE"),
			),
		) : null;
	}

	public static function showTab($div,$iblockElementInfo)
	{
		$engineList = array();

		if(Option::get('main', 'vendor', '') == '1c_bitrix')
		{
			$engineList[] = array(
				"DIV" => "yandex_direct",
				"TAB" => Loc::getMessage("SEO_ADV_YANDEX_DIRECT"),
				"TITLE" => Loc::getMessage("SEO_ADV_YANDEX_DIRECT_TITLE"),
				"HANDLER" => IO\Path::combine(
					Application::getDocumentRoot(),
					BX_ROOT,
					"/modules/seo/admin/tab/seo_search_yandex_direct.php"
				),
			);
		}

		if(count($engineList) > 0)
		{
			$engineTabControl = new \CAdminViewTabControl("engineTabControl", $engineList);
?>
<tr>
	<td colspan="2">
<?php
			$engineTabControl->begin();
			foreach($engineList as $engineTab)
			{
				$engineTabControl->beginNextTab();

				$file = new IO\File($engineTab["HANDLER"]);
				if($file->isExists())
				{
					require($file->getPath());
				}
			}

			$engineTabControl->end();
?>
	</td>
</tr>
<?php
		}
	}

	public static function checkFields()
	{
		return true;
	}

	public static function saveData()
	{
		return true;
	}
}