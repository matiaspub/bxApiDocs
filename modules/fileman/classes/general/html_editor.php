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
		global $USER, $APPLICATION;

		?>
		<script>
			(function() {
				if (!window.BXHtmlEditor)
				{
					var BXHtmlEditor = {
						ajaxResponse: {},
						editors: {},
						configs: {},
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
										BXHtmlEditor.editors[config.id] = new window.BXEditor(config);
										BXHtmlEditor.editors[config.id].Show();
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
									BXHtmlEditor.editors[id].IsShown() &&
									BXHtmlEditor.editors[id].IsContentChanged() &&
									!BXHtmlEditor.editors[id].IsSubmited())
								{
									return BX.message('BXEdExitConfirm');
								}
							}
						}
					};
					top.BXHtmlEditor = window.BXHtmlEditor = BXHtmlEditor;
					window.onbeforeunload = BXHtmlEditor.OnBeforeUnload;
				}
				BX.onCustomEvent(window, "OnBXHtmlEditorInit");
			})();
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

		$arJSPath = array(
			$basePath.'range.js',
			$basePath.'html-actions.js',
			$basePath.'html-views.js',
			$basePath.'html-parser.js',
			$basePath.'html-controls.js',
			$basePath.'html-components.js',
			$basePath.'html-snippets.js',
			$basePath.'html-editor.js',
			'/bitrix/js/main/dd.js'
		);

		$this->cssPath = $this->GetActualPath($basePath.'html-editor.css');
		$this->cssIframePath = $this->GetActualPath($basePath.'iframe-style.css');
		$APPLICATION->SetAdditionalCss($this->cssPath);

		foreach ($arJSPath as $path)
		{
			$APPLICATION->AddHeadScript($path);
		}

		$this->bAutorized = is_object($USER) && $USER->IsAuthorized();
		$this->bAllowPhp = $arParams['bAllowPhp'] !== false;
		$arParams['limitPhpAccess'] = $arParams['limitPhpAccess'] === true;
		$this->display = !isset($arParams['display']) || $arParams['display'];

		$arParams["bodyClass"] = COption::GetOptionString("fileman", "editor_body_class", "");
		$arParams["bodyId"] = COption::GetOptionString("fileman", "editor_body_id", "");

		$this->content = $arParams['content'];
		$this->inputName = isset($arParams['inputName']) ? $arParams['inputName'] : 'html_editor_content';
		$this->inputId = isset($arParams['inputId']) ? $arParams['inputId'] : 'html_editor_content_id';

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

		$arTemplates = self::GetSiteTemplates();
		if (isset($arParams['templateId']))
		{
			$templateId = $arParams['templateId'];
		}
		elseif (defined(SITE_TEMPLATE_ID))
		{
			$templateId = SITE_TEMPLATE_ID;
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
				$templateId = $first;
			}
		}

		$arSnippets = array($templateId => self::GetSnippets($templateId));
		$arComponents = self::GetComponents($templateId);
		$templateParams = self::GetSiteTemplateParams($templateId, $siteId);

		$userSettings = array(
			'view' => 'wysiwyg',
			'split_vertical' => 0,
			'split_ratio' => 1,
			'taskbar_shown' => 0,
			'taskbar_width' => 250,
			'specialchars' => false
		);

		$curSettings = CUserOptions::GetOption("html_editor", "user_settings", false, $USER->GetId());
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

		$this->jsConfig = array(
			'id' => $this->id,
			'inputName' => $this->name,
			'content' => $this->content,
			'width' => $arParams['width'],
			'height' => $arParams['height'],
			'allowPhp' => $this->bAllowPhp,
			'limitPhpAccess' => $arParams['limitPhpAccess'],
			'templates' => $arTemplates,
			'templateId' => $templateId,
			'templateParams' => $templateParams,
			'snippets' => $arSnippets,
			'components' => $arComponents,
			'placeholder' => isset($arParams['placeholder']) ? $arParams['placeholder'] : 'Text here...',
			'actionUrl' => '/bitrix/admin/fileman_html_editor_action.php',
			'cssIframePath' => $this->cssIframePath,
			'bodyClass' => $arParams["bodyClass"],
			'bodyId' => $arParams["bodyId"],
			// user settings
			'view' => $userSettings['view'],
			'splitVertical' => $userSettings['split_vertical'] ? true : false,
			'splitRatio' => $userSettings['split_ratio'],
			'taskbarShown' => $userSettings['taskbar_shown'] ? true : false,
			'taskbarWidth' => $userSettings['taskbar_width'],
			'lastSpecialchars' => $userSettings['specialchars'] ? explode('|', $userSettings['specialchars']) : false
		);
	}

	public static function GetActualPath($path)
	{
		return $path.'?'.@filemtime($_SERVER['DOCUMENT_ROOT'].$path);
	}

	public function Show($arParams)
	{
		CUtil::InitJSCore(array('window', 'ajax', 'fx'));
		$this->InitLangMess();
		$this->Init($arParams);

		// Display all DOM elements, dialogs
		$this->BuildSceleton($this->display);
		$this->Run($this->display);

		CComponentParamsManager::Init(array(
			'requestUrl' => '/bitrix/admin/fileman_component_params.php'
		));
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
</div>

		<div style="display: none;">
		<?
		CAdminFileDialog::ShowScript(Array
			(
				"event" => "BxOpenFileBrowserWindFile".$this->id,
				"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogSelect".$this->id),
				"arPath" => Array("SITE" => SITE_ID),
				"select" => 'F',
				"operation" => 'O',
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => '',
				"allowAllFiles" => true,
				"SaveConfig" => true
			)
		);

		CAdminFileDialog::ShowScript(Array
			(
				"event" => "BxOpenFileBrowserImgFile".$this->id,
				"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogImgSelect".$this->id),
				//"arPath" => Array("SITE" => $_GET["site"], "PATH" =>(strlen($str_FILENAME) > 0 ? GetDirPath($str_FILENAME) : '')),
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
				'event' => "BxOpenFileBrowserImgFile".$this->id,
				'button_id' => "bx-open-file-medialib-but-".$this->id,
				'id' => "bx_open_file_medialib_button_".$this->id,
				'MedialibConfig' => array(
					"arResultDest" => Array("FUNCTION_NAME" => "OnFileDialogImgSelect".$this->id),
					"types" => array('image')
				)
			)
		);
		?>
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

	public static function GetComponents($Params, $bClearCache = false)
	{
		global $CACHE_MANAGER;

		$allowed = trim(COption::GetOptionString('fileman', "~allowed_components", ''));
		$mask = $allowed === '' ? 0 : substr(md5($allowed), 0, 10);

		$lang = isset($Params['lang']) ? $Params['lang'] : LANGUAGE_ID;
		$cache_name = 'component_tree_array_'.$lang.'_'.$mask;
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

			$arTree = CComponentUtil::GetComponentsTree($namespace, $arAllowed);
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
							"title" => $comp['TITLE'],
							//"icon" => $comp['ICON'],
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
						"title" => $comp['TITLE'],
						//"icon" => $comp['ICON'],
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
		$result = null;

		$result = array();
		//if (!$USER->CanDoOperation('fileman_view_file_structure') || !$USER->CanDoOperation('fileman_edit_existent_files'))
		//	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

		switch($action)
		{
			case "load_site_template":
				$siteTemplate = $_REQUEST['site_template'];
				$siteId = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : SITE_ID;
				$result = self::GetSiteTemplateParams($siteTemplate, $siteId);
				break;
//			case "load_comp_params_list":
//				$name = $_REQUEST['comp_name'];
//				$siteTemplate = $_REQUEST['site_template'];
//				$template = isset($_REQUEST['comp_template']) ? $_REQUEST['comp_template'] : '';
//				if (isset($_REQUEST['comp_values']))
//					$curValues = CEditorUtils::UnJSEscapeArray($_REQUEST['comp_values']);
//				else
//					$curValues = array();
//				$result = self::GetComponentParams($name, $siteTemplate, $template, $curValues);
//				break;
			case "load_components_list":
				$siteTemplate = $_REQUEST['site_template'];
				$result = self::GetComponents($siteTemplate, true);
				break;

			// Snippets actions
			case "load_snippets_list":
				$template = $_REQUEST['site_template'];
				$result = array(
					'result' => true,
					'snippets' => array($template => self::GetSnippets($template, $_REQUEST['clear_cache'] == 'Y'))
				);
				break;
			case "edit_snippet":
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
				<script>top.BXHtmlEditor.ajaxResponse['<?= $reqId?>'] = <?= CUtil::PhpToJSObject($Res)?>;</script>
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
		return CFileman::GetAllTemplateParams($templateId, $siteId);
	}
}
?>