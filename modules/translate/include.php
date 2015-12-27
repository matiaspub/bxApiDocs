<?
use Bitrix\Main\Localization\LanguageTable;

global $DBType, $MESS, $APPLICATION;
IncludeModuleLangFile(__FILE__);

$arrTransEncoding = array(
	'windows-1250' => 'windows-1250 (ISO 8859-2)',
	'windows-1251' => 'windows-1251',
	'windows-1252' => 'windows-1252 (ISO 8859-1)',
	'windows-1253' => 'windows-1253',
	'windows-1254' => 'windows-1254',
	'windows-1255' => 'windows-1255',
	'windows-1256' => 'windows-1256',
	'windows-1257' => 'windows-1257',
	'windows-1258' => 'windows-1258'
);

class CTranslateEventHandlers
{
	public static function TranslatOnPanelCreate()
	{
		global $APPLICATION, $USER;

		if(!(IsModuleInstalled('translate') && $APPLICATION->GetGroupRight("translate")>"D"))
			return ;

		if (!$USER->IsAuthorized())
			return ;

		$show_button = COption::GetOptionString('translate', 'BUTTON_LANG_FILES', 'N');

		if ($show_button == 'Y')
		{
			$cmd = 'Y';
			$checked = 'N';
			if (isset($_SESSION['SHOW_LANG_FILES']))
			{
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

class CTranslateUtils
{
	const LANGUAGES_DEFAULT = 0;
	const LANGUAGES_EXIST = 1;
	const LANGUAGES_ACTIVE = 2;
	const LANGUAGES_CUSTOM = 3;

	protected static $languageList = array("ru", "en", "de", "ua");

	public static function setLanguageList($languages = self::LANGUAGES_DEFAULT, $customList = array())
	{
		if ($languages == self::LANGUAGES_ACTIVE || $languages == self::LANGUAGES_EXIST)
		{
			self::$languageList = array();
			if ($languages == self::LANGUAGES_ACTIVE)
			{
				$languageIterator = LanguageTable::getList(array(
					'select' => array('ID'),
					'filter' => array('ACTIVE' => 'Y')
				));
			}
			else
			{
				$languageIterator = LanguageTable::getList(array(
					'select' => array('ID')
				));
			}
			while ($lang = $languageIterator->fetch())
			{
				self::$languageList[] = $lang['ID'];
			}
			unset($lang, $languageIterator);
		}
		elseif ($languages == self::LANGUAGES_CUSTOM)
		{
			if (!is_array($customList))
				$customList = array($customList);
			self::$languageList = $customList;
		}
		else
		{
			self::$languageList = array("ru", "en", "de", "ua");
		}

	}

	
	/**
	* <p>Метод копирует фразу по четырём языкам: de, en, ru, ua.</p>
	*
	*
	* @param mixed $code  Код фразы, которую нужно копировать.
	*
	* @param mixed $fileFrom  Путь к исходному файлу, где расположена фраза.
	*
	* @param mixed $fileTo  Путь к файлу куда копируется фраза.
	*
	* @param mixed $newCode = '' Новый код фразы.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* CTranslateUtils::CopyMessage("AD_INSTALL_MODULE_NAME", "C:/Projects/local/bitrix/modules/advertising/install/install.php", "C:/Projects/local/bitrix/modules/advertising/install/index.php");
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/translate/copymessage.php
	* @author Bitrix
	*/
	public static function CopyMessage($code, $fileFrom, $fileTo, $newCode = '')
	{
		$newCode = (string)$newCode;
		if ($newCode === '')
			$newCode = $code;
		$langDir = $fileName = "";
		$filePath = $fileFrom;
		while(($slashPos = strrpos($filePath, "/")) !== false)
		{
			$filePath = substr($filePath, 0, $slashPos);
			if(is_dir($filePath."/lang"))
			{
				$langDir = $filePath."/lang";
				$fileName = substr($fileFrom, $slashPos);
				break;
			}
		}
		if($langDir <> '')
		{
			$langDirTo = $fileNameTo = "";
			$filePath = $fileTo;
			while(($slashPos = strrpos($filePath, "/")) !== false)
			{
				$filePath = substr($filePath, 0, $slashPos);
				if(is_dir($filePath."/lang"))
				{
					$langDirTo = $filePath."/lang";
					$fileNameTo = substr($fileTo, $slashPos);
					break;
				}
			}

			if($langDirTo <> '')
			{
				$langs = self::$languageList;
				foreach($langs as $lang)
				{
					$MESS = array();
					if (file_exists($langDir."/".$lang.$fileName))
					{
						include($langDir."/".$lang.$fileName);
						if(isset($MESS[$code]))
						{
							$message = $MESS[$code];
							$MESS = array();
							if (file_exists($langDirTo."/".$lang.$fileNameTo))
							{
								include($langDirTo."/".$lang.$fileNameTo);
							}
							$MESS[$newCode] = $message;
							$s = "<?\n";
							foreach($MESS as $c => $m)
							{
								$s .= "\$MESS[\"".EscapePHPString($c)."\"] = \"".EscapePHPString($m)."\";\n";
							}
							$s .= "?>";
							file_put_contents($langDirTo."/".$lang.$fileNameTo, $s);
						}
					}
				}
			}
		}
	}
}