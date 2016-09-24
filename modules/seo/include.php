<?
global $DB, $APPLICATION, $MESS, $DBType;

\Bitrix\Main\Loader::registerAutoLoadClasses(
	"seo",
	array(
		'CSeoUtils' => 'classes/general/seo_utils.php',
		'CSeoKeywords' => 'classes/general/seo_keywords.php',
		'CSeoPageChecker' => 'classes/general/seo_page_checker.php'
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
		));
	}

	public static function OnIncludeHTMLEditorScript()
	{
		if (COption::GetOptionString('main', 'vendor', '') == '1c_bitrix' && defined('ADMIN_SECTION'))
		{
?>
<script>
;(function(){
	var originalTextWnd = null;
	var originalTextBtn = new BX.CWindowButton({
			title: '<?=GetMessageJS('SEO_UNIQUE_TEXT_YANDEX_SUBMIT')?>',
			className: 'adm-btn-save',
			action: function()
			{
				var content = document.forms.seo_original_text_form.original_text.value;
				var domain = document.forms.seo_original_text_form.domain.options[document.forms.seo_original_text_form.domain.selectedIndex].value;
				if(content.length < <?=\Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MIN_LENGTH?>)
				{
					alert('<?=GetMessageJS('SEO_YANDEX_ORIGINAL_TEXT_TOO_SHORT', array('#NUM#' => \Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MIN_LENGTH))?>');
				}
				else if(content.length > <?=\Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MAX_LENGTH?>)
				{
					alert('<?=GetMessageJS('SEO_YANDEX_ORIGINAL_TEXT_TOO_LONG', array('#NUM#' => \Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MAX_LENGTH))?>');
				}
				else
				{
					originalTextBtn.disable();
					BX.ajax({
						method: 'POST',
						dataType: 'json',
						url: '/bitrix/tools/seo_yandex.php',
						data: {
							action: 'original_text',
							domain: domain,
							dir: '/',
							original_text: content,
							sessid: BX.bitrix_sessid()
						},
						onsuccess: function(res)
						{
							originalTextBtn.enable();
							if(!!res.error)
							{
								alert(BX.util.strip_tags(res.error));
							}
							else
							{
								BX('seo_original_text_form_form').style.display = 'none';
								BX('seo_original_text_form_ok').style.display = 'block';

								originalTextBtn.btn.disabled = true;

								originalTextWnd.adjustSizeEx();
								BX.defer(originalTextWnd.adjustPos, originalTextWnd)();
							}
						}
					});
				}
			}
		});

	var originalTextLoader = function(res)
	{
		BX.closeWait();
		originalTextWnd = new BX.CDialog({
			content: res,
			resizable: false,
			width: 750,
			height: 550,
			title: '<?=GetMessageJS('SEO_UNIQUE_TEXT_YANDEX')?>',
			buttons: [
				originalTextBtn
			]
		});
		originalTextHandler.apply(this, arguments);
	};

	var originalTextHandler = function()
	{
		if(!originalTextWnd)
		{
			BX.showWait();
			BX.ajax.get('/bitrix/tools/seo_yandex.php?get=original_text_form&sessid=' + BX.bitrix_sessid(), BX.proxy(originalTextLoader, this));
		}
		else if(!!document.forms.seo_original_text_form)
		{
			this.pMainObj.SaveContent();

			var content = BX.util.strip_tags(
				this.pMainObj.GetContent()
					.replace(/<\?(.|[\r\n])*?\?>/g, '')
					.replace(/#PHP[^#]*#/ig, '')
			);

			originalTextWnd.Show();
			originalTextWnd.Get().style.zIndex = 3010;

			document.forms.seo_original_text_form.original_text.value = content;
			BX('seo_original_text_form_form').style.display = 'block';
			BX('seo_original_text_form_ok').style.display = 'none';

			originalTextWnd.adjustSizeEx();
			originalTextBtn.enable();
			originalTextBtn.btn.disabled = false;
			BX.defer(originalTextWnd.adjustPos, originalTextWnd)();
		}
		else
		{
			originalTextWnd.Show();
			originalTextWnd.Get().style.zIndex = 3010;
			originalTextBtn.btn.disabled = true;
		}
	};

	var seoEditorButton = ['BXButton',
	{
		id : 'SeoUniqText',
		src : '/bitrix/panel/seo/images/icon_editor_toolbar.png',
		name : '<?=CUtil::JSEscape(GetMessage('SEO_UNIQUE_TEXT_YANDEX'))?>',
		codeEditorMode : true,
		handler : originalTextHandler
	}];

	if(typeof window.arToolbars != 'undefined' && !window.bSeoToolbarButtonAdded)
	{
		if(typeof window.arToolbars['manage'] != 'undefined')
		{
			window.arToolbars['manage'][1].push(seoEditorButton);
		}
		else
		{
			window.arToolbars['standart'][1].push(seoEditorButton);
		}

		window.bSeoToolbarButtonAdded = true;
	}

	if(typeof window.arGlobalToolbar != 'undefined' && !window.bSeoGlobalToolbarButtonAdded)
	{
		window.arGlobalToolbar.push(seoEditorButton);
		window.bSeoGlobalToolbarButtonAdded = true;
	}
})();
</script>
<?
		}
	}

	public static function OnBeforeHTMLEditorScriptRuns()
	{
		if (COption::GetOptionString('main', 'vendor', '') == '1c_bitrix' && defined('ADMIN_SECTION'))
		{
?>
<script>
	;(function(){
		var originalTextWnd = null;
		var originalTextBtn = new BX.CWindowButton({
			title: '<?=GetMessageJS('SEO_UNIQUE_TEXT_YANDEX_SUBMIT')?>',
			className: 'adm-btn-save',
			action: function()
			{
				var content = document.forms.seo_original_text_form.original_text.value;
				var domain = document.forms.seo_original_text_form.domain.options[document.forms.seo_original_text_form.domain.selectedIndex].value;
				if(content.length < <?=\Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MIN_LENGTH?>)
				{
					alert('<?=GetMessageJS('SEO_YANDEX_ORIGINAL_TEXT_TOO_SHORT', array('#NUM#' => \Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MIN_LENGTH))?>');
				}
				else if(content.length > <?=\Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MAX_LENGTH?>)
				{
					alert('<?=GetMessageJS('SEO_YANDEX_ORIGINAL_TEXT_TOO_LONG', array('#NUM#' => \Bitrix\Seo\Engine\Yandex::ORIGINAL_TEXT_MAX_LENGTH))?>');
				}
				else
				{
					originalTextBtn.disable();
					BX.ajax({
						method: 'POST',
						dataType: 'json',
						url: '/bitrix/tools/seo_yandex.php',
						data: {
							action: 'original_text',
							domain: domain,
							dir: '/',
							original_text: content,
							sessid: BX.bitrix_sessid()
						},
						onsuccess: function(res)
						{
							originalTextBtn.enable();
							if(!!res.error)
							{
								alert(BX.util.strip_tags(res.error));
							}
							else
							{
								BX('seo_original_text_form_form').style.display = 'none';
								BX('seo_original_text_form_ok').style.display = 'block';

								originalTextBtn.btn.disabled = true;

								originalTextWnd.adjustSizeEx();
								BX.defer(originalTextWnd.adjustPos, originalTextWnd)();
							}
						}
					});
				}
			}
		});

		var originalTextHandler = function(editor)
		{
			if(!originalTextWnd)
			{
				BX.showWait();
				BX.ajax.get(
					'/bitrix/tools/seo_yandex.php?get=original_text_form&sessid=' + BX.bitrix_sessid(),
					BX.delegate(function(res)
					{
						BX.closeWait();
						originalTextWnd = new BX.CDialog({
							content: res,
							resizable: false,
							width: 750,
							height: 550,
							title: '<?=GetMessageJS('SEO_UNIQUE_TEXT_YANDEX')?>',
							buttons: [
								originalTextBtn
							]
						});
						originalTextHandler.apply(this, [editor]);
					}, this)
				);
			}
			else if(!!document.forms.seo_original_text_form)
			{
				var content = BX.util.strip_tags(
					editor.GetContent()
						.replace(/<\?(.|[\r\n])*?\?>/g, '')
						.replace(/#PHP[^#]*#/ig, '')
				);

				originalTextWnd.Show();
				originalTextWnd.Get().style.zIndex = 3010;

				document.forms.seo_original_text_form.original_text.value = content;
				BX('seo_original_text_form_form').style.display = 'block';
				BX('seo_original_text_form_ok').style.display = 'none';

				originalTextWnd.adjustSizeEx();
				originalTextBtn.enable();
				originalTextBtn.btn.disabled = false;
				BX.defer(originalTextWnd.adjustPos, originalTextWnd)();
			}
			else
			{
				originalTextWnd.Show();
				originalTextWnd.Get().style.zIndex = 3010;
				originalTextBtn.btn.disabled = true;
			}
		};

		function applyForEditor(editor)
		{
			editor.AddButton(
				{
					id : 'SeoUniqText',
					src : '/bitrix/panel/seo/images/icon_editor_toolbar_2.png',
					name : '<?=CUtil::JSEscape(GetMessage('SEO_UNIQUE_TEXT_YANDEX'))?>',
					codeEditorMode : true,
					handler : function(){originalTextHandler(editor);},
					toolbarSort: 305
				}
			);
		}

		if (window.BXHtmlEditor && window.BXHtmlEditor.editors)
		{
			for (var id in window.BXHtmlEditor.editors)
			{
				if (window.BXHtmlEditor.editors.hasOwnProperty(id))
				{
					applyForEditor(window.BXHtmlEditor.Get(id))
				}
			}
		}

		BX.addCustomEvent("OnEditorInitedBefore", applyForEditor);
	})();
</script>
		<?
		}
	}
}
?>