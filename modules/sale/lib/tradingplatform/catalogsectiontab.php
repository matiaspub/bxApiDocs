<?

namespace Bitrix\Sale\TradingPlatform;

use \Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CatalogSectionTab
 * Work with iblock section / catalog category.
 * @package Bitrix\Sale\TradingPlatform
 */
class CatalogSectionTab
{
	protected static $tabHandlers = array();

	public static function OnInit($args)
	{
		$result = array();

		$res = \Bitrix\Sale\TradingPlatformTable::getList(array(
			'select' => array("ID", "CODE", "CATALOG_SECTION_TAB_CLASS_NAME"),
			'filter' => array('=ACTIVE' => 'Y'),
		));

		while($arRes = $res->fetch())
		{
			if(strlen($arRes["CATALOG_SECTION_TAB_CLASS_NAME"]) > 0 && class_exists($arRes["CATALOG_SECTION_TAB_CLASS_NAME"]))
			{
				$tabHandler = new $arRes["CATALOG_SECTION_TAB_CLASS_NAME"];

				if(!($tabHandler instanceof TabHandler))
					throw new SystemException("TabHandler (".$arRes["CODE"].") has wrong instance. (".__CLASS__."::".__METHOD__.")");

				self::$tabHandlers[$arRes["CODE"]] = $tabHandler;
			}
		}

		if(!empty(self::$tabHandlers))
		{
			//todo: iblock filter
			$result =  array(
				"TABSET" => "SALE_TRADING_PLATFORM",
				"GetTabs" => array("\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "GetTabs"),
				"ShowTab" => array("\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "ShowTab"),
				"Action" => array("\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "Action"),
				"Check" => array("\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "Check"),
			);
		}

		return $result;
	}

	public static function Action($arArgs)
	{
		/** @var \CMain $APPLICATION*/
		global $APPLICATION;
		$result = true;

		foreach(self::$tabHandlers as $handler)
		{
			/** @var  TabHandler $handler*/
			try
			{
				$result = $handler->action($arArgs);
			}
			catch(SystemException $e)
			{
				$APPLICATION->ThrowException($e->getMessage());
				$result = false;
				break;
			}
		}

		return $result;
	}

	public static function Check($arArgs)
	{
		/** @var \CMain $APPLICATION*/
		global $APPLICATION;
		$result = true;

		foreach(self::$tabHandlers as $handler)
		{
			/** @var  TabHandler $handler*/
			try
			{
				$result = $handler->check($arArgs);
			}
			catch(SystemException $e)
			{
				$APPLICATION->ThrowException($e->getMessage());
				$result = false;
				break;
			}
		}

		return $result;
	}

	public static function GetTabs($arArgs)
	{
		$arTabs = array(
			array(
				"DIV" => "edit_trading_platforms",
				"TAB" => Loc::getMessage('SALE_TRADING_PLATFORMS_TAB'),
				"ICON" => "sale",
				"TITLE" => Loc::getMessage('SALE_TRADING_PLATFORMS_TAB_TITLE'),
			),
		);
		return $arTabs;
	}

	// arArgs = array("ID" => $ID, "IBLOCK"=>$arIBlock, "IBLOCK_TYPE"=>$arIBTYPE)
	public static function ShowTab($divName, $arArgs, $bVarsFromForm)
	{
		if ($divName == "edit_trading_platforms")
		{
			$result = "";

			foreach(self::$tabHandlers as $tradingPlatformCode => $handler)
			{
				/** @var  TabHandler $handler*/
				$header = '<tr class="heading" id="tr_'.$tradingPlatformCode.'"><td colspan="2">'.$handler->name.'</td></tr>';
				$body = $handler->showTabSection($divName, $arArgs, $bVarsFromForm);
				$result .= $header.$body;
			}

			echo $result;
		}
	}
}
