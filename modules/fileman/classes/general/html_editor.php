<?
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

IncludeModuleLangFile(__FILE__);
class CHTMLEditor
{
	private static
		$thirdLevelId,
		$arComponents;

	private
		$siteId,
		$content,
		$id,
		$name,
		$jsConfig,
		$cssIframePath,
		$bAutorized,
		$bAllowPhp,
		$display,
		$inputName,
		$inputId,
		$cssPath;

	const CACHE_TIME = 31536000; // 365 days

	private function Init($arParams)
	{
		global $USER;
		?>
		<script>
			(function(window) {
				if (!window.BXHtmlEditor)
				{
					var BXHtmlEditor = {
						editors: {},
						configs: {},
						dialogs: {},
						Controls: {},
						SaveConfig: function(config)
						{
							BX.ready(function()
								{
									if (config && config.id)
									{
										BXHtmlEditor.configs[config.id] = config;
									}
								}
							);
						},
						Show: function(config, id)
						{
							BX.ready(function()
								{
									if ((!config || typeof config != 'object') && id && BXHtmlEditor.configs[id])
									{
										config = BXHtmlEditor.configs[id];
									}

									if (config && typeof config == 'object')
									{
										if (!BXHtmlEditor.editors[config.id] || !BXHtmlEditor.editors[config.id].Check())
										{
											BXHtmlEditor.editors[config.id] = new window.BXEditor(config);
										}
										else
										{
											BXHtmlEditor.editors[config.id].CheckAndReInit();
										}
									}
								}
							);
						},
						Hide: function(id)
						{
							if (BXHtmlEditor.editors[id])
							{
								BXHtmlEditor.editors[config.id].Hide();
							}
						},
						Get: function(id)
						{
							return BXHtmlEditor.editors[id] || false;
						},
						OnBeforeUnload: function(e)
						{
							for (var id in BXHtmlEditor.editors)
							{
								if (BXHtmlEditor.editors.hasOwnProperty(id) &&
									BXHtmlEditor.editors[id].config.askBeforeUnloadPage === true &&
									BXHtmlEditor.editors[id].IsShown() &&
									BXHtmlEditor.editors[id].IsContentChanged() &&
									!BXHtmlEditor.editors[id].IsSubmited() &&
									BXHtmlEditor.editors[id].beforeUnloadHandlerAllowed !== false)
								{
									if (typeof(BX.PULL) != 'undefined' && typeof(BX.PULL.tryConnectDelay) == 'function') // TODO change to right code in near future (e.shelenkov)
									{
										BX.PULL.tryConnectDelay();
									}
									if(typeof(BX.desktopUtils) != 'undefined' && typeof(BX.desktopUtils.isChangedLocationToBx) == 'function' && BX.desktopUtils.isChangedLocationToBx())
									{
										return;
									}
									return BXHtmlEditor.editors[id].config.beforeUnloadMessage || BX.message('BXEdExitConfirm');
								}
							}
						},

						ReplaceNewLines : function(content)
						{
							content = content.replace(/<[^<>]*br>\n/ig, '#BX_BR#');
							var contentTmp;
							while (true)
							{
								contentTmp = content.replace(/([\s|\S]+)\n([\s|\S]+)/gi, function (s, s1, s2)
									{
										if (s1.match(/>\s*$/) || s2.match(/^\s*</))
											return s;
										return s1 + '#BX_BR#' + s2;
									}
								);
								if (contentTmp == content)
								{
									break;
								}
								else
								{
									content = contentTmp;
								}
							}

							content = content.replace(/#BX_BR#/ig, "<br>\n");

							return content;
						},

						ReplaceNewLinesBack: function(content)
						{
							content = content.replace(/<[^<>]*br>\n/ig, '#BX_BR#');
							var contentTmp;
							while (true)
							{
								contentTmp = content.replace(/([\s|\S]+)#BX_BR#([\s|\S]+)/gi, function (s, s1, s2)
									{
										if (s1.match(/>\s*$/) || s2.match(/^\s*</))
											return s;
										return s1 + '\n' + s2;
									}
								);
								if (contentTmp == content)
								{
									break;
								}
								else
								{
									content = contentTmp;
								}
							}

							content = content.replace(/#BX_BR#/ig, "<br>\n");

							return content;
						}
					};

					window.BXHtmlEditor = BXHtmlEditor;
					window.onbeforeunload = BXHtmlEditor.OnBeforeUnload;
				}

				BX.onCustomEvent(window, "OnBXHtmlEditorInit");
				top.BXHtmlEditorAjaxResponse = {};
			})(window);
		</script><?

		$basePath = '/bitrix/js/fileman/html_editor/';
		$this->id = (isset($arParams['id']) && strlen($arParams['id']) > 0) ? $arParams['id'] : 'bxeditor'.substr(uniqid(mt_rand(), true), 0, 4);
		$this->id = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $this->id);
		if (isset($arParams['name']))
		{
			$this->name = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $arParams['name']);
		}
		else
		{
			$this->name = $this->id;
		}

		$this->cssIframePath = $this->GetActualPath($basePath.'iframe-style.css');

		CJSCore::RegisterExt('html_editor', array(
			'js' => array(
				$basePath.'range.js',
				$basePath.'html-actions.js',
				$basePath.'html-views.js',
				$basePath.'html-parser.js',
				$basePath.'html-base-controls.js',
				$basePath.'html-controls.js',
				$basePath.'html-components.js',
				$basePath.'html-snippets.js',
				$basePath.'html-editor.js',
				'/bitrix/js/main/dd.js'
			),
			'css' => $basePath.'html-editor.css',
			'rel' => array('date', 'timer')
		));
		CUtil::InitJSCore(array('html_editor'));

		foreach(GetModuleEvents("fileman", "OnBeforeHTMLEditorScriptRuns", true) as $arEvent)
			ExecuteModuleEventEx($arEvent);

		$this->bAutorized = is_object($USER) && $USER->IsAuthorized();
		if (isset($arParams['allowPhp']) && !isset($arParams['bAllowPhp']))
		{
			$arParams['bAllowPhp'] = $arParams['allowPhp'];
		}

		$this->bAllowPhp = $arParams['bAllowPhp'] !== false;
		$arParams['limitPhpAccess'] = $arParams['limitPhpAccess'] === true;
		$this->display = !isset($arParams['display']) || $arParams['display'];

		$arParams["bodyClass"] = COption::GetOptionString("fileman", "editor_body_class", "");
		$arParams["bodyId"] = COption::GetOptionString("fileman", "editor_body_id", "");

		$this->content = $arParams['content'];
		$this->inputName = isset($arParams['inputName']) ? $arParams['inputName'] : $this->name;
		$this->inputId = isset($arParams['inputId']) ? $arParams['inputId'] : 'html_editor_content_id';

		$arParams["bbCode"] = (isset($arParams["bbCode"]) && $arParams["bbCode"]) || (isset($arParams["BBCode"]) && $arParams["BBCode"]);

		// Site id
		if (!isset($arParams['siteId']))
		{
			$siteId = CSite::GetDefSite();
		}
		else
		{
			$siteId = $arParams['siteId'];
			$res = CSite::GetByID($siteId);
			if (!$res->Fetch())
			{
				$siteId = CSite::GetDefSite();
			}
		}
		if (!isset($siteId) && defined(SITE_ID))
		{
			$siteId = SITE_ID;
			$res = CSite::GetByID($siteId);
			if (!$res->Fetch())
			{
				$siteId = CSite::GetDefSite();
			}
		}

		$templateId = null;
		if (isset($arParams['templateId']))
		{
			$templateId = $arParams['templateId'];
		}
		elseif (defined('SITE_TEMPLATE_ID'))
		{
			$templateId = SITE_TEMPLATE_ID;
		}

		if (!isset($templateId) && isset($_GET['siteTemplateId']))
		{
			$templateId = $_GET['siteTemplateId'];
		}

		if ($arParams["bbCode"])
		{
			$arTemplates = array();
			$arSnippets = array();
			$templateParams = array();
		}
		else
		{
			if (isset($arParams['arTemplates']))
			{
				$arTemplates = $arParams['arTemplates'];
			}
			else
			{
				$arTemplates = self::GetSiteTemplates();
			}

			if (!isset($templateId) && isset($siteId))
			{
				$dbSiteRes = CSite::GetTemplateList($siteId);
				$first = false;
				while($arSiteRes = $dbSiteRes->Fetch())
				{
					if (!$first)
					{
						$first = $arSiteRes['TEMPLATE'];
					}
					if ($arSiteRes['CONDITION'] == "")
					{
						$templateId = $arSiteRes['TEMPLATE'];
						break;
					}
				}

				if (!isset($templateId))
				{
					$templateId = $first ? $first : '';
				}
			}

			$arSnippets = array($templateId => self::GetSnippets($templateId));
			$templateParams = self::GetSiteTemplateParams($templateId, $siteId);
		}

		$userSettings = array(
			'view' => isset($arParams["view"]) ? $arParams["view"] : 'wysiwyg',
			'split_vertical' => 0,
			'split_ratio' => 1,
			'taskbar_shown' => 0,
			'taskbar_width' => 250,
			'specialchars' => false,
			'clean_empty_spans' => 'Y',
			'paste_clear_colors' => 'Y',
			'paste_clear_borders' => 'Y',
			'paste_clear_decor' => 'Y',
			'paste_clear_table_dimen' => 'Y',
			'show_snippets' => 'Y',
			'link_dialog_type' => 'internal'
		);
		$settingsKey = "user_settings_".$arParams["bbCode"]."_".$this->id;

		$curSettings = CUserOptions::GetOption("html_editor", $settingsKey, false, $USER->GetId());
		if (is_array($curSettings))
		{
			foreach ($userSettings as $k => $val)
			{
				if (isset($curSettings[$k]))
				{
					$userSettings[$k] = $curSettings[$k];
				}
			}
		}

		if(!isset($arParams["usePspell"]))
		{
			$arParams["usePspell"] = COption::GetOptionString("fileman", "use_pspell", "N");
		}

		if(!isset($arParams["useCustomSpell"]))
		{
			$arParams["useCustomSpell"] = COption::GetOptionString("fileman", "use_custom_spell", "Y");
		}

		$arParams["showComponents"] = isset($arParams["showComponents"]) ? $arParams["showComponents"] : true;
		$arParams["showSnippets"] = isset($arParams["showSnippets"]) ? $arParams["showSnippets"] : true;
		$arParams["showSnippets"] = $arParams["showSnippets"] && $userSettings['show_snippets'] != 'N';

		if(!isset($arParams["initConponentParams"]))
			$arParams["initConponentParams"] = $arParams["showTaskbars"] !== false && $arParams["showComponents"] && ($arParams['limitPhpAccess'] || $arParams['bAllowPhp']);

		$arParams["actionUrl"] = $arParams["bbCode"] ? '/bitrix/tools/html_editor_action.php' : '/bitrix/admin/fileman_html_editor_action.php';

		$arParams["lazyLoad"] = isset($arParams["lazyLoad"]) ? $arParams["lazyLoad"] : false;

		$this->jsConfig = array(
			'id' => $this->id,
			'inputName' => $this->inputName,
			'content' => $this->content,
			'width' => $arParams['width'],
			'height' => $arParams['height'],
			'allowPhp' => $this->bAllowPhp,
			'limitPhpAccess' => $arParams['limitPhpAccess'],
			'templates' => $arTemplates,
			'templateId' => $templateId,
			'templateParams' => $templateParams,
			'componentFilter' => $arParams['componentFilter'],
			'snippets' => $arSnippets,
			'placeholder' => isset($arParams['placeholder']) ? $arParams['placeholder'] : 'Text here...',
			'actionUrl' => $arParams["actionUrl"],
			'cssIframePath' => $this->cssIframePath,
			'bodyClass' => $arParams["bodyClass"],
			'bodyId' => $arParams["bodyId"],
			'spellcheck_path' => $basePath.'html-spell.js?v='.filemtime($_SERVER['DOCUMENT_ROOT'].$basePath.'html-spell.js'),
			'usePspell' => $arParams["usePspell"],
			'useCustomSpell' => $arParams["useCustomSpell"],
			'bbCode' => $arParams["bbCode"],
			'askBeforeUnloadPage' => $arParams["askBeforeUnloadPage"] !== false,
			'settingsKey' => $settingsKey,
			'showComponents' => $arParams["showComponents"],
			'showSnippets' => $arParams["showSnippets"],
			// user settings
			'view' => $userSettings['view'],
			'splitVertical' => $userSettings['split_vertical'] ? true : false,
			'splitRatio' => $userSettings['split_ratio'],
			'taskbarShown' => $userSettings['taskbar_shown'] ? true : false,
			'taskbarWidth' => $userSettings['taskbar_width'],
			'lastSpecialchars' => $userSettings['specialchars'] ? explode('|', $userSettings['specialchars']) : false,
			'cleanEmptySpans' => $userSettings['clean_empty_spans'] != 'N',
			'pasteSetColors' => $userSettings['paste_clear_colors'] != 'N',
			'pasteSetBorders' => $userSettings['paste_clear_borders'] != 'N',
			'pasteSetDecor' => $userSettings['paste_clear_decor'] != 'N',
			'pasteClearTableDimen' => $userSettings['paste_clear_table_dimen'] != 'N',
			'linkDialogType' => $userSettings['link_dialog_type'],
			'lazyLoad' => $arParams["lazyLoad"]
		);

		if (($this->bAllowPhp || $arParams['limitPhpAccess']) && $arParams["showTaskbars"] !== false)
		{
			$this->jsConfig['components'] = self::GetComponents($templateId, false, $arParams['componentFilter']);
		}

		if (isset($arParams["initAutosave"]))
			$this->jsConfig["initAutosave"] = $arParams["initAutosave"];

		if (isset($arParams["useFileDialogs"]))
			$this->jsConfig["useFileDialogs"] = $arParams["useFileDialogs"];

		if (isset($arParams["showTaskbars"]))
			$this->jsConfig["showTaskbars"] = $arParams["showTaskbars"];

		if (isset($arParams["showNodeNavi"]))
			$this->jsConfig["showNodeNavi"] = $arParams["showNodeNavi"];

		if (isset($arParams["controlsMap"]))
			$this->jsConfig["controlsMap"] = $arParams["controlsMap"];

		if (isset($arParams["arSmiles"]))
			$this->jsConfig["smiles"] = $arParams["arSmiles"];

		if (isset($arParams["arSmilesSet"]))
			$this->jsConfig["smileSets"] = $arParams["arSmilesSet"];

		if (isset($arParams["iframeCss"]))
			$this->jsConfig["iframeCss"] = $arParams["iframeCss"];


		if (isset($arParams["beforeUnloadMessage"]))
			$this->jsConfig["beforeUnloadMessage"] = $arParams["beforeUnloadMessage"];

		if (isset($arParams["setFocusAfterShow"]))
			$this->jsConfig["setFocusAfterShow"] = $arParams["setFocusAfterShow"];

		if (isset($arParams["relPath"]))
			$this->jsConfig["relPath"] = $arParams["relPath"];

		// autoresize
		if (isset($arParams["autoResize"]))
		{
			$this->jsConfig["autoResize"] = $arParams["autoResize"];
			if (isset($arParams['autoResizeOffset']))
				$this->jsConfig['autoResizeOffset'] = $arParams['autoResizeOffset'];
			if (isset($arParams['autoResizeMaxHeight']))
				$this->jsConfig['autoResizeMaxHeight'] = $arParams['autoResizeMaxHeight'];
			if (isset($arParams['autoResizeSaveSize']))
				$this->jsConfig['autoResizeSaveSize'] = $arParams['autoResizeSaveSize'] !== false;
		}

		if (isset($arParams["minBodyWidth"]))
			$this->jsConfig["minBodyWidth"] = $arParams["minBodyWidth"];
		if (isset($arParams["minBodyHeight"]))
			$this->jsConfig["minBodyHeight"] = $arParams["minBodyHeight"];
		if (isset($arParams["normalBodyWidth"]))
			$this->jsConfig["normalBodyWidth"] = $arParams["normalBodyWidth"];

		return $arParams;
	}

	public static function GetActualPath($path)
	{
		return $path.'?'.@filemtime($_SERVER['DOCUMENT_ROOT'].$path);
	}

	public function Show($arParams)
	{
		CUtil::InitJSCore(array('window', 'ajax', 'fx'));
		$this->InitLangMess();
		$arParams = $this->Init($arParams);

		// Display all DOM elements, dialogs
		$this->BuildSceleton($this->display);
		$this->Run($this->display);

		if ($arParams["initConponentParams"])
		{
			CComponentParamsManager::Init(array(
				'requestUrl' => '/bitrix/admin/fileman_component_params.php'
			));
		}
	}

	public function BuildSceleton($display = true)
	{
		$width = isset($this->jsConfig['width']) && intval($this->jsConfig['width']) > 0 ? $this->jsConfig['width'] : "100%";
		$height = isset($this->jsConfig['height']) && intval($this->jsConfig['height']) > 0 ? $this->jsConfig['height'] : "100%";

		$widthUnit = strpos($width, "%") === false ? "px" : "%";
		$heightUnit = strpos($height, "%") === false ? "px" : "%";
		$width = intval($width);
		$height = intval($height);

		?>
		<div class="bx-html-editor" id="bx-html-editor-<?=$this->id?>" style="width:<?= $width.$widthUnit?>; height:<?= $height.$heightUnit?>; <?= $display ? '' : 'display: none;'?>">
			<div class="bxhtmled-toolbar-cnt" id="bx-html-editor-tlbr-cnt-<?=$this->id?>">
				<div class="bxhtmled-toolbar" id="bx-html-editor-tlbr-<?=$this->id?>"></div>
			</div>
			<div class="bxhtmled-search-cnt" id="bx-html-editor-search-cnt-<?=$this->id?>" style="display: none;"></div>
			<div class="bxhtmled-area-cnt" id="bx-html-editor-area-cnt-<?=$this->id?>">
				<div class="bxhtmled-iframe-cnt" id="bx-html-editor-iframe-cnt-<?=$this->id?>"></div>
				<div class="bxhtmled-textarea-cnt" id="bx-html-editor-ta-cnt-<?=$this->id?>"></div>
				<div class="bxhtmled-resizer-overlay" id="bx-html-editor-res-over-<?=$this->id?>"></div>
				<div id="bx-html-editor-split-resizer-<?=$this->id?>"></div>
			</div>
			<div class="bxhtmled-nav-cnt" id="bx-html-editor-nav-cnt-<?=$this->id?>" style="display: none;"></div>
			<div class="bxhtmled-taskbar-cnt bxhtmled-taskbar-hidden" id="bx-html-editor-tskbr-cnt-<?=$this->id?>">
				<div class="bxhtmled-taskbar-top-cnt" id="bx-html-editor-tskbr-top-<?=$this->id?>"></div>
				<div class="bxhtmled-taskbar-resizer" id="bx-html-editor-tskbr-res-<?=$this->id?>">
					<div class="bxhtmled-right-side-split-border">
						<div data-bx-tsk-split-but="Y" class="bxhtmled-right-side-split-btn"></div>
					</div>
				</div>
				<div class="bxhtmled-taskbar-search-nothing" id="bxhed-tskbr-search-nothing-<?=$this->id?>"><?= GetMessage('HTMLED_SEARCH_NOTHING')?></div>
				<div class="bxhtmled-taskbar-search-cont" id="bxhed-tskbr-search-cnt-<?=$this->id?>" data-bx-type="taskbar_search">
					<div class="bxhtmled-search-alignment" id="bxhed-tskbr-search-ali-<?=$this->id?>">
						<input type="text" class="bxhtmled-search-inp" id="bxhed-tskbr-search-inp-<?=$this->id?>" placeholder="<?= GetMessage('HTMLED_SEARCH_PLACEHOLDER')?>"/>
					</div>
					<div class="bxhtmled-search-cancel" data-bx-type="taskbar_search_cancel" title="<?= GetMessage('HTMLED_SEARCH_CANCEL')?>"></div>
				</div>
			</div>
			<div id="bx-html-editor-file-dialogs-<?=$this->id?>" style="display: none;"></div>
		</div>
	<?
	}

	public function Run($display = true)
	{
		?><script>
		<?if($display):?>
		window.BXHtmlEditor.Show(<?=CUtil::PhpToJSObject($this->jsConfig)?>);
		<?else:?>
		window.BXHtmlEditor.SaveConfig(<?=CUtil::PhpToJSObject($this->jsConfig)?>);
		<?endif;?>
	</script><?
	}

	public static function InitLangMess()
	{
		$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/classes/general/html_editor_js.php';
		if(!file_exists($langPath))
			$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/en/classes/general/html_editor_js.php';
		$mess_lang = __IncludeLang($langPath, true, true);

		?><script>BX.message(<?=CUtil::PhpToJSObject($mess_lang, false);?>);</script><?
	}

	public static function GetSnippets($templateId, $bClearCache = false)
	{
		return array(
			'items' => CSnippets::LoadList(
					array(
						'template' => $templateId,
						'bClearCache' => $bClearCache,
						'returnArray' => true
					)
				),
			'groups' => CSnippets::GetGroupList(
					array(
						'template' => $templateId,
						'bClearCache' => $bClearCache
					)
				),
			'rootDefaultFilename' => CSnippets::GetDefaultFileName($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$templateId."/snippets")
		);
	}

	public static function GetComponents($Params, $bClearCache = false, $arFilter = array())
	{
		global $CACHE_MANAGER;

		$allowed = trim(COption::GetOptionString('fileman', "~allowed_components", ''));
		$mask = $allowed === '' ? 0 : substr(md5($allowed), 0, 10);

		$lang = isset($Params['lang']) ? $Params['lang'] : LANGUAGE_ID;
		$component_type = '';
		if(isset($arFilter['TYPE']))
			$component_type = '_'.$arFilter['TYPE'];

		$cache_name = 'component_tree_array_'.$lang.'_'.$mask.$component_type;
		$table_id = "fileman_component_tree";

		if ($bClearCache)
		{
			$CACHE_MANAGER->CleanDir($table_id);
		}

		if($CACHE_MANAGER->Read(self::CACHE_TIME, $cache_name, $table_id))
		{
			self::$arComponents = $CACHE_MANAGER->Get($cache_name);
		}

		if (empty(self::$arComponents))
		{
			// Name filter exists
			if ($allowed !== '')
			{
				$arAC = explode("\n", $allowed);
				$arAC = array_unique($arAC);
				$arAllowed = Array();
				foreach ($arAC as $f)
				{
					$f = preg_replace("/\s/is", "", $f);
					$f = preg_replace("/\./is", "\\.", $f);
					$f = preg_replace("/\*/is", ".*", $f);
					$arAllowed[] = '/^'.$f.'$/';
				}
				$namespace = 'bitrix';
			}
			else
			{
				$arAllowed = false;
				$namespace = false;
			}

			$arTree = CComponentUtil::GetComponentsTree($namespace, $arAllowed, $arFilter);
			self::$arComponents = array(
				'items' => array(),
				'groups' => array()
			);
			self::$thirdLevelId = 0;

			if (isset($arTree['#']))
			{
				self::_HandleComponentElement($arTree['#'], '');
			}

			$CACHE_MANAGER->Set($cache_name, self::$arComponents);
		}

		return self::$arComponents;
	}

	public static function _HandleComponentElement($arEls, $path)
	{
		foreach ($arEls as $elName => $arEl)
		{
			if (strpos($path, ",") !== false)
			{
				if (isset($arEl['*']))
				{
					$thirdLevelName = '__bx_thirdLevel_'.self::$thirdLevelId;
					self::$thirdLevelId++;
					foreach ($arEl['*'] as $name => $comp)
					{
						self::$arComponents['items'][] = array(
							"path" => $path,
							"name" => $name,
							"type" => $comp['TYPE'],
							"title" => $comp['TITLE'],
							"complex" => $comp['COMPLEX'],
							"params" => array("DESCRIPTION" => $comp['DESCRIPTION']),
							"thirdlevel" => $thirdLevelName
						);
					}
				}
				continue;
			}

			$realPath = (($path == '') ? $elName : $path.','.$elName);
			// Group
			self::$arComponents['groups'][] = array(
				"path" => $path,
				"name" => $elName,
				"title" => (isset($arEl['@']['NAME']) && $arEl['@']['NAME'] !== '') ? $arEl['@']['NAME'] : $elName
			);

			if (isset($arEl['#']))
			{
				self::_HandleComponentElement($arEl['#'], $realPath);
			}

			if (is_array($arEl['*']) && !empty($arEl['*']))
			{
				foreach ($arEl['*'] as $name => $comp)
				{
					self::$arComponents['items'][] = array(
						"path" => $realPath,
						"name" => $name,
						"type" => $comp['TYPE'],
						"title" => $comp['TITLE'],
						"complex" => $comp['COMPLEX'],
						"params" => array("DESCRIPTION" => $comp['DESCRIPTION']),
						"thirdlevel" => false
					);
				}
			}
		}
	}

	public static function GetSiteTemplates()
	{
		$arTemplates = Array(Array('value' => '.default', 'name' => GetMessage("FILEMAN_DEFTEMPL")));
		$db_site_templates = CSiteTemplate::GetList(array(), array(), array());
		while($ar = $db_site_templates->Fetch())
		{
			$arTemplates[] = Array('value'=>$ar['ID'], 'name'=> $ar['NAME']);
		}

		return $arTemplates;
	}

	public static function RequestAction($action = '')
	{
		global $USER, $APPLICATION;
		$result = array();

		switch($action)
		{
			case "load_site_template":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				$siteTemplate = $_REQUEST['site_template'];
				$siteId = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : SITE_ID;
				$result = self::GetSiteTemplateParams($siteTemplate, $siteId);
				break;
			case "load_components_list":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				$siteTemplate = $_REQUEST['site_template'];
				$componentFilter = isset($_REQUEST['componentFilter']) ? $_REQUEST['componentFilter'] : false;
				$result = self::GetComponents($siteTemplate, true, $componentFilter);
				break;

			case "video_oembed":
				$result = self::GetVideoOembed($_REQUEST['video_source']);
				break;

			// Snippets actions
			case "load_snippets_list":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				$template = $_REQUEST['site_template'];
				$result = array(
					'result' => true,
					'snippets' => array($template => self::GetSnippets($template, $_REQUEST['clear_cache'] == 'Y'))
				);
				break;
			case "edit_snippet":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				CUtil::JSPostUnEscape();
				$template = $_REQUEST['site_template'];

				// Update
				if ($_REQUEST['current_path'])
				{
					$result = CSnippets::Update(array(
						'template' => $template,
						'path' => $_REQUEST['path'],
						'code' => $_REQUEST['code'],
						'title' => $_REQUEST['name'],
						'current_path' => $_REQUEST['current_path'],
						'description' => $_REQUEST['description']
					));
				}
				// Add new
				else
				{
					$result = CSnippets::Add(array(
						'template' => $template,
						'path' => $_REQUEST['path'],
						'code' => $_REQUEST['code'],
						'title' => $_REQUEST['name'],
						'description' => $_REQUEST['description']
					));
				}

				if ($result && $result['result'])
				{
					$result['snippets'] = array($template => self::GetSnippets($template));
				}

				break;
			case "remove_snippet":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				CUtil::JSPostUnEscape();
				$template = $_REQUEST['site_template'];

				$res = CSnippets::Remove(array(
					'template' => $template,
					'path' => $_REQUEST['path']
				));

				if ($res)
				{
					$result = array(
						'result' => true,
						'snippets' => array($template => self::GetSnippets($template))
					);
				}
				else
				{
					$result = array('result' => false);
				}

				break;
			case "snippet_add_category":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				CUtil::JSPostUnEscape();
				$template = $_REQUEST['site_template'];
				$res = CSnippets::CreateCategory(array(
					'template' => $template,
					'name' => $_REQUEST['category_name'],
					'parent' => $_REQUEST['category_parent']
				));

				if ($res)
				{
					$result = array(
						'result' => true,
						'snippets' => array($template => self::GetSnippets($template))
					);
				}
				else
				{
					$result = array('result' => false);
				}
				break;
			case "snippet_remove_category":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				CUtil::JSPostUnEscape();
				$template = $_REQUEST['site_template'];
				$res = CSnippets::RemoveCategory(array(
					'template' => $template,
					'path' => $_REQUEST['category_path']
				));

				if ($res)
				{
					$result = array(
						'result' => true,
						'snippets' => array($template => self::GetSnippets($template))
					);
				}
				else
				{
					$result = array('result' => false);
				}
				break;
			case "snippet_rename_category":
				if (!$USER->CanDoOperation('fileman_view_file_structure'))
					break;
				CUtil::JSPostUnEscape();
				$template = $_REQUEST['site_template'];
				$res = CSnippets::RenameCategory(array(
					'template' => $template,
					'path' => $_REQUEST['category_path'],
					'new_name' => $_REQUEST['category_new_name']
				));

				if ($res)
				{
					$result = array(
						'result' => true,
						'snippets' => array($template => self::GetSnippets($template))
					);
				}
				else
				{
					$result = array('result' => false);
				}
				break;
			// END *** Snippets actions

			// spellcheck
			case "spellcheck_words":
			case "spellcheck_add_word":
				CUtil::JSPostUnEscape();
				$spellChecker = new CSpellchecker(array(
					"lang" => $_REQUEST['lang'],
					"skip_length" => 2,
					"use_pspell" => $_REQUEST['use_pspell'] !== "N",
					"use_custom_spell" => $_REQUEST['use_custom_spell'] !== "N",
					"mode" => PSPELL_FAST
				));

				if ($action == "spellcheck_words")
				{
					$words = (isset($_REQUEST['words']) && is_array($_REQUEST['words'])) ? $_REQUEST['words'] : array();
					$result = array(
						'words' => $spellChecker->checkWords($words)
					);
				}
				else // Add word
				{
					$word = CFileMan::SecurePathVar($_REQUEST['word']);
					$spellChecker->addWord($word);
				}
				break;
			// END *** spellcheck
			case "load_file_dialogs":
				$editorId = $_REQUEST['editor_id'];
				$editorId = preg_replace("/[^a-zA-Z0-9_-]/is", "_", $editorId);

				CAdminFileDialog::ShowScript(Array
					(
						"event" => "BxOpenFileBrowserWindFile".$editorId,
						"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogSelect".$editorId),
						"arPath" => Array("SITE" => SITE_ID),
						"select" => 'F',
						"operation" => 'O',
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'image',
						"allowAllFiles" => true,
						"SaveConfig" => true
					)
				);
				CMedialib::ShowBrowseButton(
					array(
						'value' => '...',
						'event' => "BxOpenFileBrowserWindFile".$editorId,
						'button_id' => "bx-open-file-link-medialib-but-".$editorId,
						'id' => "bx_open_file_link_medialib_button_".$editorId,
						'MedialibConfig' => array(
							"event" => "BxOpenFileBrowserFileMl".$editorId,
							"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogSelect".$editorId)
						),
						'useMLDefault' => false
					)
				);

				CMedialib::ShowBrowseButton(
					array(
						'value' => '...',
						'event' => "BxOpenFileBrowserWindFile".$editorId,
						'button_id' => "bx-open-file-medialib-but-".$editorId,
						'id' => "bx_open_file_medialib_button_".$editorId,
						'MedialibConfig' => array(
							"event" => "BxOpenFileBrowserImgFileMl".$editorId,
							"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogImgSelect".$editorId),
							"types" => array('image')
						)
					)
				);


				$result = array('result' => true);
				break;
		}

		self::ShowResponse(intVal($_REQUEST['reqId']), $result);
	}

	public static function ShowResponse($reqId = false, $Res = false)
	{
		if ($Res !== false)
		{
			if ($reqId === false)
			{
				$reqId = intVal($_REQUEST['reqId']);
			}

			if ($reqId)
			{
				?>
				<script>top.BXHtmlEditorAjaxResponse['<?= $reqId?>'] = <?= CUtil::PhpToJSObject($Res)?>;</script>
			<?
			}
		}
	}

	public static function GetComponentParams($name, $siteTemplate = '', $template = '', $curValues = array(), $loadHelp = true)
	{
		$template = (!$template || $template == '.default') ? '' : CUtil::JSEscape($template);
		$arTemplates = CComponentUtil::GetTemplatesList($name, $siteTemplate);

		$result = array(
			'groups' => array(),
			'templates' => array(),
			'props' => array(),
			'template_props' => array()
		);

		$arProps = CComponentUtil::GetComponentProps($name, $curValues);

		if (is_array($arTemplates))
		{
			foreach ($arTemplates as $k => $arTemplate)
			{
				$result['templates'][] = array(
					'name' => $arTemplate['NAME'],
					'template' => $arTemplate['TEMPLATE'],
					'title' => $arTemplate['TITLE'],
					'description' => $arTemplate['DESCRIPTION'],
				);

				$tName = (!$arTemplate['NAME'] || $arTemplate['NAME'] == '.default') ? '' : $arTemplate['NAME'];
				if ($tName == $template)
				{
					$arTemplateProps = CComponentUtil::GetTemplateProps($name, $arTemplate['NAME'], $siteTemplate, $curValues);

					if (is_array($arTemplateProps))
					{
						foreach ($arTemplateProps as $k => $arTemplateProp)
						{
							$result['templ_props'][] = self::_HandleComponentParam($k, $arTemplateProp, $arProps['GROUPS']);
						}
					}
				}
			}
		}

		//if ($loadHelp && is_array($arProps['PARAMETERS']))
		//	fetchPropsHelp($name);

		if (is_array($arProps['GROUPS']))
		{
			foreach ($arProps['GROUPS'] as $k => $arGroup)
			{
				$result['templ_props'][] = array(
					'name' => $k,
					'title' => $arGroup['NAME']
				);
			}
		}

		if (is_array($arProps['PARAMETERS']))
		{
			foreach ($arProps['PARAMETERS'] as $k => $arParam)
			{
				$result['properties'][] = self::_HandleComponentParam($k, $arParam, $arProps['GROUPS']);
			}
		}

		return $result;
	}

	private static function _HandleComponentParam($name = '', $arParam = array(), $arGroup = array())
	{
		$name = preg_replace("/[^a-zA-Z0-9_-]/is", "_", $name);

		$result = array(
			'name' => $name,
			'parent' => (isset($arParam['PARENT']) && isset($arGroup[$arParam['PARENT']])) ? $arParam['PARENT'] : false
		);

		if (!empty($arParam))
		{
			foreach ($arParam as $k => $prop)
			{
				if ($k == 'TYPE' && $prop == 'FILE')
				{
					$GLOBALS['arFD'][] = Array(
						'NAME' => CUtil::JSEscape($name),
						'TARGET' => isset($arParam['FD_TARGET']) ? $arParam['FD_TARGET'] : 'F',
						'EXT' => isset($arParam['FD_EXT']) ? $arParam['FD_EXT'] : '',
						'UPLOAD' => isset($arParam['FD_UPLOAD']) && $arParam['FD_UPLOAD'] && $arParam['FD_TARGET'] == 'F',
						'USE_ML' => isset($arParam['FD_USE_MEDIALIB']) && $arParam['FD_USE_MEDIALIB'],
						'ONLY_ML' => isset($arParam['FD_USE_ONLY_MEDIALIB']) && $arParam['FD_USE_ONLY_MEDIALIB'],
						'ML_TYPES' => isset($arParam['FD_MEDIALIB_TYPES']) ? $arParam['FD_MEDIALIB_TYPES'] : false
					);
				}
				elseif (in_array($k, Array('FD_TARGET', 'FD_EXT','FD_UPLOAD', 'FD_MEDIALIB_TYPES', 'FD_USE_ONLY_MEDIALIB')))
				{
					continue;
				}

				$result[$k] = $prop;
			}
		}

		return $result;
	}

	public static function GetSiteTemplateParams($templateId, $siteId)
	{
		$params = CFileman::GetAllTemplateParams($templateId, $siteId);

		$params["STYLES"] = preg_replace("/(url\(\"?)images\//is", "\\1".$params['SITE_TEMPLATE_PATH'].'/images/', $params["STYLES"]);

		if (is_array($params['EDITOR_STYLES']))
		{
			for ($i = 0, $l = count($params['EDITOR_STYLES']); $i < $l; $i++)
			{
				$params['EDITOR_STYLES'][$i] = $params['EDITOR_STYLES'][$i].'?'.@filemtime($_SERVER['DOCUMENT_ROOT'].$params['EDITOR_STYLES'][$i]);
			}
		}

		return $params;
	}

	public static function GetVideoOembed($url = '')
	{
		// Get oembed url
		$oembed = self::GetOembedUrlInfo($url);
		$output = array('result' => false, 'error' => "");

		$http = new \Bitrix\Main\Web\HttpClient();
		$resp = $http->get($oembed['url']);
		if ($resp === false)
		{
			$io = CBXVirtualIo::GetInstance();
			$path = $url;
			$serverPath = self::GetServerPath();

			if (strpos($path, $serverPath) !== false)
			{
				$path = str_replace($serverPath, '', $path);
			}

			if ($io->FileExists($io->RelativeToAbsolutePath($path)))
			{
				$output['data'] = array(
					'local' => true,
					'path' => $path
				);
				$output['result'] = true;
			}
			else
			{
				$path = $url;
				$http = new \Bitrix\Main\Web\HttpClient();
				$resp1 = $http->get($path);
				if ($resp1 !== false)
				{
					$output['data'] = array(
						'local' => true,
						'path' => $url
					);
				}
				$output['result'] = true;
			}

			if (!$output['result'])
			{
				$error = $http->getError();
				foreach($error as $errorCode => $errorMessage)
				{
					$output['error'] .=  '['.$errorCode.'] '.$errorMessage.";\n";
				}
			}
		}
		else
		{
			$resParams = json_decode($resp, true);
			if ($resParams && is_array($resParams))
			{
				if (!defined('BX_UTF') || BX_UTF !== true)
				{
					$resParams['title'] = CharsetConverter::ConvertCharset($resParams['title'], 'UTF-8', SITE_CHARSET);
					$resParams['html'] = CharsetConverter::ConvertCharset($resParams['html'], 'UTF-8', SITE_CHARSET);
					$resParams['provider_name'] = CharsetConverter::ConvertCharset($resParams['provider_name'], 'UTF-8', SITE_CHARSET);

				}

				$resParams['html'] = preg_replace("/https?:\/\//is", '//', $resParams['html']);
				$output['result'] = true;
				$output['data'] = array(
					'html' => $resParams['html'],
					'title' => $resParams['title'],
					'width' => intval($resParams['width']),
					'height' => intval($resParams['height']),
					'provider' => $resParams['provider_name']
				);
			}
			else
			{
				$output['error'] .=  '[FVID404] '.GetMessage('HTMLED_VIDEO_NOT_FOUND').";\n";
			}
		}

		return $output;
	}

	public static function GetServerPath()
	{
		if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
			$server_name = SITE_SERVER_NAME;
		if (!$server_name)
			$server_name = COption::GetOptionString("main", "server_name", "");
		if (!$server_name)
			$server_name = $_SERVER['HTTP_HOST'];
		$server_name = rtrim($server_name, '/');
		if (!preg_match('/^[a-z0-9\.\-]+$/i', $server_name)) // cyrillic domain hack
		{
			$converter = new CBXPunycode(defined('BX_UTF') && BX_UTF === true ? 'UTF-8' : 'windows-1251');
			$host = $converter->Encode($server_name);
			if (!preg_match('#--p1ai$#', $host)) // trying to guess
				$host = $converter->Encode(CharsetConverter::ConvertCharset($server_name, 'utf-8', 'windows-1251'));
			$server_name = $host;
		}

		$serverPath = (CMain::IsHTTPS() ? "https://" : "http://").$server_name;

		return $serverPath;
	}

	public static function GetOembedUrlInfo($url = '')
	{
		$res = array(
			'url' => '',
			'provider' => ''
		);
		if (preg_match('/(youtube.com)|(youtu.be)/i', $url))
		{
			$res['url'] = 'http://www.youtube.com/oembed?url='.urlencode($url).'&format=json';
			$res['provider'] = 'youtube';
		}
		elseif (preg_match('/vimeo.com/i', $url))
		{
			$res['url'] = 'http://vimeo.com/api/oembed.json?url='.urlencode($url);
			$res['provider'] = 'vimeo';
		}
		elseif (preg_match('/rutube.ru/i', $url))
		{
			$res['url'] = 'http://rutube.ru/api/oembed/?url='.urlencode($url).'&format=json';
			$res['provider'] = 'rutube';
		}

		return $res;
	}
}
?>