<?
IncludeModuleLangFile(__FILE__);
class CCodeEditor // CE
{
	public static function Show($params)
	{
		global $APPLICATION, $USER;
		CUtil::InitJSCore(array('window', 'ajax'));

		$APPLICATION->AddHeadScript('/bitrix/js/fileman/code_editor/code-editor.js');
		$APPLICATION->SetAdditionalCSS('/bitrix/js/fileman/code_editor/code-editor.css');

		$id = (isset($params['id']) && strlen($params['id']) > 0) ? $params['id'] : 'bxce-'.substr(uniqid(mt_rand(), true), 0, 4);
		$theme = isset($params['defaultTheme']) ? $params['defaultTheme'] : 'light';
		$highlight = isset($params['defaultHighlight']) ? $params['defaultHighlight'] : true;
		$saveSettings = $params['saveSettings'] !== false && $USER && $USER->IsAuthorized();

		if ($saveSettings)
		{
			$Settings = CUserOptions::GetOption("fileman", "code_editor");
			$theme = $Settings['theme'] == 'dark' ? 'dark' : 'light';
			$highlight = !isset($Settings['highlight']) || $Settings['highlight'];
		}

		if (!in_array($theme, array('dark', 'light')))
			$theme = 'dark';
		$highlight = $highlight === false ? false : true;

		$JSConfig = array(
			'id' => $id,
			'textareaId' => $params['textareaId'],
			'theme' => $theme,
			'highlightMode' => $highlight,
			'saveSettings' => $saveSettings
		);

		if (isset($params['width']) && intVal($params['width']) > 0)
			$JSConfig['width'] = $params['width'];
		if (isset($params['height']) && intVal($params['height']) > 0)
			$JSConfig['height'] = $params['height'];

		if (isset($params['forceSyntax']) && in_array($params['forceSyntax'], array('php', 'js', 'sql', 'css')))
			$JSConfig['forceSyntax'] = $params['forceSyntax'];
		else
			$JSConfig['forceSyntax'] = false;
		?>
		<script>

			BX.ready(function()
			{
				if (!top.BXCodeEditors)
					top.BXCodeEditors = window.BXCodeEditors = {};

				function codeEditorLoaded()
				{
					var CE = new window.JCCodeEditor(<?= CUtil::PhpToJSObject($JSConfig)?>, <?= self::GetLangMessage()?>);
					top.BXCodeEditors['<?= $id?>'] = window.BXCodeEditors['<?= $id?>'] = CE;
					BX.onCustomEvent(window, "OnCodeEditorReady", ['<?= $id?>']);
				}

				if (!window.JCCodeEditor)
				{
					BX.loadScript('/bitrix/js/fileman/code_editor/code-editor.js?<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/fileman/code_editor/code-editor.js')?>', codeEditorLoaded);
					BX.loadCSS('/bitrix/js/fileman/code_editor/code-editor.css');
				}
				else
				{
					codeEditorLoaded();
				}

			});
		</script>
		<?
		return $id;
	}

	public static function GetLangMessage()
	{
		$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/classes/general/code_editor_js.php';
		if(file_exists($langPath))
			include($langPath);
		else
			$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/en/classes/general/code_editor_js.php';

		echo CUtil::PhpToJSObject($MESS);
	}
}
?>