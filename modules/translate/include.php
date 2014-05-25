<?
global $DBType, $MESS, $APPLICATION;
IncludeModuleLangFile(__FILE__);

$arrTransEncoding = array();
$arrTransEncoding['windows-1250'] = 'windows-1250 (ISO 8859-2)';
$arrTransEncoding['windows-1251'] = 'windows-1251';
$arrTransEncoding['windows-1252'] = 'windows-1252 (ISO 8859-1)';
$arrTransEncoding['windows-1253'] = 'windows-1253';
$arrTransEncoding['windows-1254'] = 'windows-1254';
$arrTransEncoding['windows-1255'] = 'windows-1255';
$arrTransEncoding['windows-1256'] = 'windows-1256';
$arrTransEncoding['windows-1257'] = 'windows-1257';

class CTranslateEventHandlers
{
	public static function TranslatOnPanelCreate()
	{
		global $APPLICATION, $USER;

		if(!(IsModuleInstalled('translate') && $APPLICATION->GetGroupRight("translate")>"D")) {
			return ;
		}


		if (!$USER->IsAuthorized()) {
			return ;
		}

		$show_button = COption::GetOptionString('translate', 'BUTTON_LANG_FILES', 'N');

		if ($show_button == 'Y') {

			$cmd = 'Y';
			$checked = 'N';
			if (isset($_SESSION['SHOW_LANG_FILES'])) {
				$cmd = $_SESSION['SHOW_LANG_FILES'] == 'Y' ? 'N' : 'Y';
				$checked = $_SESSION['SHOW_LANG_FILES'] == 'Y' ? 'Y' : 'N';
			}

			$url = $APPLICATION->GetCurPageParam("show_lang_files=".$cmd, array('show_lang_files'));
			$arMenu = array(
				array(
					"TEXT"=> GetMessage("TRANSLATE_SHOW_LANG_FILES_TEXT"),
					"TITLE"=> GetMessage("TRANSLATE_SHOW_LANG_FILES_TITLE"),
					"CHECKED"=>($checked == "Y"),
					"LINK"=>$url,
					"DEFAULT"=>false,
				));

			$APPLICATION->AddPanelButton(array(
				"HREF"=> '',
				"ID"=>"translate",
				"ICON" => "bx-panel-translate-icon",
				"ALT"=> GetMessage('TRANSLATE_ICON_ALT'),
				"TEXT"=> GetMessage('TRANSLATE_ICON_TEXT'),
				"MAIN_SORT"=>"1000",
				"SORT"=> 50,
				"MODE"=>array("configure"),
				"MENU" => $arMenu,
				"HINT" => array(
					'TITLE' => GetMessage('TRANSLATE_ICON_TEXT'),
					'TEXT' => GetMessage('TRANSLATE_ICON_HINT')
				)
			));
		}
	}
}

?>
