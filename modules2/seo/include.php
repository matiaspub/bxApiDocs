<?
global $DB, $APPLICATION, $MESS, $DBType;

CModule::AddAutoloadClasses(
	"seo",
	array(
		'CSeoUtils' => 'classes/general/seo_utils.php',
		'CSeoKeywords' => 'classes/general/seo_keywords.php',
		'CSeoPageChecker' => 'classes/general/seo_page_checker.php',
	)
);

if (!defined('SEO_COUNTERS_DEFAULT'))
{
	if (COption::GetOptionString('main', 'vendor', '') == '1c_bitrix')
	{
		define(
			'SEO_COUNTERS_DEFAULT',
			"<img src=\"http://yandex.ru/cycounter?#DOMAIN#\" width=\"88\" height=\"31\" border=\"0\" />"
		);
	}
	else
	{
		define(
			'SEO_COUNTERS_DEFAULT',
			'<a href="http://www.whats-my-pagerank.com" target="_blank"><img src = "http://www.whats-my-pagerank.com/pagerank2.php" alt="PR Checker" border="0" /></a>'
		);
	}
}

IncludeModuleLangFile(__FILE__);

class CSeoEventHandlers
{
	public static function SeoOnPanelCreate()
	{
		global $APPLICATION, $USER;

		if (!$USER->CanDoOperation('seo_tools'))
			return false;

		if (isset($_SERVER["REAL_FILE_PATH"]) && $_SERVER["REAL_FILE_PATH"] != "")
		{
			$currentDirPath = dirname($_SERVER["REAL_FILE_PATH"]);
			$currentFilePath = $_SERVER["REAL_FILE_PATH"];
		}
		else
		{
			$currentDirPath = $APPLICATION->GetCurDir();
			$currentFilePath = $APPLICATION->GetCurPage(true);
		}

		$encCurrentDirPath = urlencode($currentDirPath);
		$encCurrentFilePath = urlencode($currentFilePath);
		$encRequestUri = urlencode($_SERVER["REQUEST_URI"]);

		$encTitleChangerLink = '';
		$encWinTitleChangerLink = '';
		$encTitleChangerName = '';
		$encWinTitleChangerName = '';
		if (is_array($APPLICATION->sDocTitleChanger))
		{
			if (isset($APPLICATION->sDocTitleChanger['PUBLIC_EDIT_LINK']))
				$encTitleChangerLink = urlencode(base64_encode($APPLICATION->sDocTitleChanger['PUBLIC_EDIT_LINK']));
			if (isset($APPLICATION->sDocTitleChanger['COMPONENT_NAME']))
				$encTitleChangerName = urlencode($APPLICATION->sDocTitleChanger['COMPONENT_NAME']);
		}

		$prop_code = ToUpper(COption::GetOptionString('seo', 'property_window_title', 'title'));

		if (is_array($APPLICATION->arPagePropertiesChanger[$prop_code]))
		{
			if (isset($APPLICATION->arPagePropertiesChanger[$prop_code]['PUBLIC_EDIT_LINK']))
				$encWinTitleChangerLink = urlencode(base64_encode($APPLICATION->arPagePropertiesChanger[$prop_code]['PUBLIC_EDIT_LINK']));
			if (isset($APPLICATION->arPagePropertiesChanger[$prop_code]['COMPONENT_NAME']))
				$encWinTitleChangerName = urlencode($APPLICATION->arPagePropertiesChanger[$prop_code]['COMPONENT_NAME']);
		}

		$encTitle = urlencode(base64_encode($APPLICATION->sDocTitle));
		$encWinTitle = urlencode(base64_encode($APPLICATION->arPageProperties[$prop_code]));

		$APPLICATION->AddPanelButton(array(
			"HREF"=> 'javascript:'.$APPLICATION->GetPopupLink(
				array(
					"URL"=>"/bitrix/admin/public_seo_tools.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=seo&site=".SITE_ID
						."&path=".$encCurrentFilePath
						."&title_final=".$encTitle."&title_changer_name=".$encTitleChangerName.'&title_changer_link='.$encTitleChangerLink
						."&title_win_final=".$encWinTitle."&title_win_changer_name=".$encWinTitleChangerName.'&title_win_changer_link='.$encWinTitleChangerLink
						."&".bitrix_sessid_get()
						."&back_url=".$encRequestUri,
					"PARAMS"=> Array("width"=>920, "height" => 400, 'resize' => false)
				)),
			"ID"=>"seo",
			"ICON" => "bx-panel-seo-icon",
			"ALT"=>GetMessage('SEO_ICON_ALT'),
			"TEXT"=>GetMessage('SEO_ICON_TEXT'),
			"MAIN_SORT"=>"300",
			"SORT"=> 50,
			"HINT" => array(
				"TITLE" => GetMessage('SEO_ICON_TEXT'),
				"TEXT" => GetMessage('SEO_ICON_HINT')
			),
/*			"MENU" => array(
				array(
					"TEXT"=> 'SEO (page)',
					"TITLE"=> 'SEO (page)',
					"ICON"=>"panel-new-file",
					"ACTION" => $APPLICATION->GetPopupLink(
						array(
							"URL"=>"/bitrix/admin/public_seo_tools.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=seo&site=".SITE_ID
								."&path=".$encCurrentFilePath
								."&title_final=".$encTitle."&title_changer_name=".$encTitleChangerName.'&title_changer_link='.$encTitleChangerLink
								."&back_url=".$encRequestUri,
							"PARAMS"=> Array("width"=>700, "height" => 400, 'resize' => false)
						)),
					"DEFAULT" => 'Y',
				),
				array(
				"TEXT"=> 'SEO (site)',
				"TITLE"=> 'SEO (site)',
				"ICON"=>"panel-new-folder",
				"ACTION" => $APPLICATION->GetPopupLink(
					Array(
						"URL"=>"/bitrix/admin/public_seo_tools_site.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=seo&site=".SITE_ID.
							"&path=".$encCurrentDirPath."&back_url=".$encRequestUri,
						"PARAMS"=> Array("width"=>700, "height" => 400, 'resize' => false)
					))
				),
			), */
		));
	}
}
?>