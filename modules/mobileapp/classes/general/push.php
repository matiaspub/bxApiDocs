<?
IncludeModuleLangFile(__FILE__);

class CAdminMobilePush
{
	private static $arData = array();

	public static function addData($branchName, $arData)
	{
		$result = true;

		if(strlen($branchName) > 0)
			self::$arData[$branchName] = $arData;
		else
			$result = false;

		return $result;
	}

	public static function getData($path = "")
	{
		$arResult = self::$arData;

		$arResult = array(
			"TYPE" => "SECTIONS_SECTION",
			"TITLE" => GetMessage("MOBILEAPP_PUSH_SECTIONS"),
			"SECTIONS" => $arResult
		);

		$arPath = explode("/", $path);

		if(is_array($arPath))
		{
			foreach ($arPath as $idx)
			{
				if(isset($arResult["SECTIONS"][$idx]))
					$arResult = $arResult["SECTIONS"][$idx];
				else
					break;
			}
		}

		return $arResult;
	}

	static public function getOptions($path = "")
	{
		global $USER;
		$arOptions = array();

		foreach (GetModuleEvents("mobileapp", "OnBeforeAdminMobilePushOptsLoad", true) as $arHandler)
			ExecuteModuleEventEx($arHandler, array(
				$USER->GetID(),
				$path,
				&$arOptions
			));

		if(empty($arOptions))
		{
			$arResult = CUserOptions::GetOption('mobileapp', 'push_options', array());
			$arPath = explode("/", $path);

			if(is_array($arPath))
			{
				foreach ($arPath as $idx)
				{
					if(isset($arResult[$idx]))
						$arResult = $arResult[$idx];
					else
						break;
				}
			}
		}
		else
		{
			$arResult = $arOptions;
		}

		return $arResult;
	}

	static public function saveOptions($path = "", $arOpts)
	{
		$result = true;
		$opts = self::getOptions();
		$arTmp = &$opts;
		$arPath = explode("/", $path);

		if(is_array($arPath))
		{
			foreach ($arPath as $pathItem)
			{
				if(!isset($arTmp[$pathItem]) || !is_array($arTmp[$pathItem]))
					$arTmp[$pathItem] = array();

				$arTmp = &$arTmp[$pathItem];
			}

			$arTmp = $arOpts;
		}

		return CUserOptions::SetOption('mobileapp', 'push_options', $opts);
	}

	static public function OnAdminMobileGetPushSettings()
	{
		foreach (GetModuleEvents("mobileapp", "OnAdminMobileGetPushSettings", true) as $arHandler)
			ExecuteModuleEventEx($arHandler);

		if(!empty(self::$arData))
		{
			$arItems = array();

			foreach (self::$arData as $optBranch => $arOptions)
			{
				$arItems[] = array(
					"text" => $arOptions["TITLE"],
					"data-url" => "/bitrix/admin/mobile/push.php?path=".urlencode($optBranch),
					"data-pageid" => "push_settings_".$optBranch
				);
			}

			if(!empty($arItems))
			{
				$arMenuData = array(
					"type" => "section",
					"sort" => "990",
					"text" => GetMessage("MOBILEAPP_PUSH_TITLE"),
					"items" =>	$arItems
				);

				CAdminMobileMenu::addItem($arMenuData);
			}
		}
	}
}
?>