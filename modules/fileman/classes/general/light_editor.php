<?
IncludeModuleLangFile(__FILE__);
class CLightHTMLEditor // LHE
{
	public function Init(&$arParams)
	{
		global $USER, $APPLICATION;
		$basePath = '/bitrix/js/fileman/light_editor/';
		$this->Id = (isset($arParams['id']) && strlen($arParams['id']) > 0) ? $arParams['id'] : 'bxlhe'.substr(uniqid(mt_rand(), true), 0, 4);

		$this->cssPath = $basePath."light_editor.css";
		$APPLICATION->SetAdditionalCSS($this->cssPath);

		$this->arJSPath = array(
			$basePath.'le_dialogs.js',
			$basePath.'le_controls.js',
			$basePath.'le_toolbarbuttons.js',
			$basePath.'le_core.js'
		);

		$this->bBBCode = $arParams['BBCode'] === true;
		$this->bRecreate = $arParams['bRecreate'] === true;
		$arJS = Array();
		$arCSS = Array();

		foreach(GetModuleEvents("fileman", "OnBeforeLightEditorScriptsGet", true) as $arEvent)
		{
			$tmp = ExecuteModuleEventEx($arEvent, array($this->Id, $arParams));
			if (!is_array($tmp))
				continue;

			if (is_array($tmp['JS']))
			{
				for($i = 0, $c = count($tmp['JS']); $i < $c; $i++)
				{
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$tmp['JS'][$i]))
						$this->arJSPath[] = $tmp['JS'][$i];
				}
			}
		}

		foreach($this->arJSPath as $path)
		{
			$APPLICATION->AddHeadScript($path);
		}

		//Messages
		$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/classes/general/light_editor_js.php';
		if(!file_exists($langPath))
			$langPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/en/classes/general/light_editor_js.php';
		$this->mess = __IncludeLang($langPath, true, true);
		$this->messOld = array();

		if (!empty($this->mess))
		{
			$this->messOld = array('Image' => $this->mess['Image'], 'Video' => $this->mess['Video'],'ImageSizing' => $this->mess['ImageSizing']);

			$jsMsg = "<script bxrunfirst>LHE_MESS = window.LHE_MESS = ".CUtil::PhpToJSObject($this->messOld)."; (window.BX||top.BX).message(".CUtil::PhpToJSObject($this->mess, false).");</script>";

			$APPLICATION->AddLangJS($jsMsg);
		}

		$this->bAutorized = is_object($USER) && $USER->IsAuthorized();
		$this->bUseFileDialogs = $arParams['bUseFileDialogs'] !== false && $this->bAutorized;
		$this->bUseMedialib = $arParams['bUseMedialib'] !== false && COption::GetOptionString('fileman', "use_medialib", "Y") == "Y" && CMedialib::CanDoOperation('medialib_view_collection', 0);

		$this->bResizable = $arParams['bResizable'] === true;
		$this->bManualResize = $this->bResizable && $arParams['bManualResize'] !== false;
		$this->bAutoResize = $arParams['bAutoResize'] !== false;
		$this->bInitByJS = $arParams['bInitByJS'] === true;
		$this->bSaveOnBlur = $arParams['bSaveOnBlur'] !== false;
		$this->content = $arParams['content'];
		$this->inputName = isset($arParams['inputName']) ? $arParams['inputName'] : 'lha_content';
		$this->inputId = isset($arParams['inputId']) ? $arParams['inputId'] : 'lha_content_id';
		$this->videoSettings = is_array($arParams['videoSettings']) ? $arParams['videoSettings'] : array(
				'maxWidth' => 640,
				'maxHeight' => 480,
				'WMode' => 'transparent',
				'windowless' => true,
				'bufferLength' => 20,
				'skin' => '/bitrix/components/bitrix/player/mediaplayer/skins/bitrix.swf',
				'logo' => ''
			);

		if (!is_array($arParams['arFonts']) || count($arParams['arFonts']) <= 0)
			$arParams['arFonts'] = array('Arial', 'Verdana', 'Times New Roman', 'Courier', 'Tahoma', 'Georgia', 'Optima', 'Impact', 'Geneva', 'Helvetica');

		if (!is_array($arParams['arFontSizes']) || count($arParams['arFontSizes']) <= 0)
			$arParams['arFontSizes'] = array('1' => 'xx-small', '2' => 'x-small', '3' => 'small', '4' => 'medium', '5' => 'large', '6' => 'x-large', '7' => 'xx-large');

		// Tables
		//$this->arJSPath[] = $this->GetActualPath($basePath.'le_table.js');
		$this->jsObjName = (isset($arParams['jsObjName']) && strlen($arParams['jsObjName']) > 0) ? $arParams['jsObjName'] : 'LightHTMLEditor'.$this->Id;

		if ($this->bResizable)
		{
			// Get height user settings
			$userOpt = CUserOptions::GetOption(
				'fileman',
				'LHESize_'.$this->Id,
				array('height' => $arParams['height'])
			);
			$arParams['height'] = intval($userOpt['height']) > 0 ? $userOpt['height'] : $arParams['height'];
		}

		$this->JSConfig = array(
			'id' => $this->Id,
			'content' => $this->content,
			'bBBCode' => $this->bBBCode,
			'bUseFileDialogs' => $this->bUseFileDialogs,
			'bUseMedialib' => $this->bUseMedialib,
			'arSmiles' => $arParams['arSmiles'],
			'arFonts' => $arParams['arFonts'],
			'arFontSizes' => $arParams['arFontSizes'],
			'inputName' => $this->inputName,
			'inputId' => $this->inputId,
			'videoSettings' => $this->videoSettings,
			'bSaveOnBlur' => $this->bSaveOnBlur,
			'bResizable' => $this->bResizable,
			'autoResizeSaveSize' => $arParams['autoResizeSaveSize'] !== false,
			'bManualResize' => $this->bManualResize,
			'bAutoResize' => $this->bAutoResize,
			'bReplaceTabToNbsp' => true,
			'bSetDefaultCodeView' => isset($arParams['bSetDefaultCodeView']) && $arParams['bSetDefaultCodeView'],
			'bBBParseImageSize' => isset($arParams['bBBParseImageSize']) && $arParams['bBBParseImageSize'],
			'smileCountInToolbar' => intVal($arParams['smileCountInToolbar']),
			'bQuoteFromSelection' => isset($arParams['bQuoteFromSelection']) && $arParams['bQuoteFromSelection'],
			'bConvertContentFromBBCodes' => isset($arParams['bConvertContentFromBBCodes']) && $arParams['bConvertContentFromBBCodes'],
			'oneGif' => '/bitrix/images/1.gif',
			'imagePath' => '/bitrix/images/fileman/light_htmledit/'
		);

		// Set editor from visual mode to textarea for mobile devices
		if (!isset($this->JSConfig['bSetDefaultCodeView']) && CLightHTMLEditor::IsMobileDevice())
			$this->JSConfig['bSetDefaultCodeView'] = true;

		if (isset($arParams['width']) && intVal($arParams['width']) > 0)
			$this->JSConfig['width'] = $arParams['width'];
		if (isset($arParams['height']) && intVal($arParams['height']) > 0)
			$this->JSConfig['height'] = $arParams['height'];
		if (isset($arParams['toolbarConfig']))
			$this->JSConfig['toolbarConfig'] = $arParams['toolbarConfig'];
		if (isset($arParams['documentCSS']))
			$this->JSConfig['documentCSS'] = $arParams['documentCSS'];
		if (isset($arParams['fontFamily']))
			$this->JSConfig['fontFamily'] = $arParams['fontFamily'];
		if (isset($arParams['fontSize']))
			$this->JSConfig['fontSize'] = $arParams['fontSize'];
		if (isset($arParams['lineHeight']))
			$this->JSConfig['lineHeight'] = $arParams['lineHeight'];
		if (isset($arParams['bHandleOnPaste']))
			$this->JSConfig['bHandleOnPaste'] = $arParams['bHandleOnPaste'];
		if (isset($arParams['autoResizeOffset']))
			$this->JSConfig['autoResizeOffset'] = $arParams['autoResizeOffset'];
		if (isset($arParams['autoResizeMaxHeight']))
			$this->JSConfig['autoResizeMaxHeight'] = $arParams['autoResizeMaxHeight'];
		if (isset($arParams['controlButtonsHeight']))
			$this->JSConfig['controlButtonsHeight'] = $arParams['controlButtonsHeight'];

		if ($this->bBBCode)
		{
			$this->JSConfig['bParceBBImageSize'] = true;
		}

		if (isset($arParams['ctrlEnterHandler']))
			$this->JSConfig['ctrlEnterHandler'] = $arParams['ctrlEnterHandler'];
	}

	public static function GetActualPath($path)
	{
		return $path.'?'.@filemtime($_SERVER['DOCUMENT_ROOT'].$path);
	}

	public function Show($arParams)
	{
		CUtil::InitJSCore(array('window', 'ajax'));
		$this->Init($arParams);
		$this->BuildSceleton();
		$this->InitScripts();

		if ($this->bUseFileDialogs)
			$this->InitFileDialogs();

		if ($this->bUseMedialib)
			$this->InitMedialibDialogs();
	}

	public function BuildSceleton()
	{
		$width = isset($this->JSConfig['width']) && intval($this->JSConfig['width']) > 0 ? $this->JSConfig['width'] : "100%";
		$height = isset($this->JSConfig['height']) && intval($this->JSConfig['height']) > 0 ? $this->JSConfig['height'] : "100%";

		$widthUnit = strpos($width, "%") === false ? "px" : "%";
		$heightUnit = strpos($height, "%") === false ? "px" : "%";
		$width = intval($width);
		$height = intval($height);

		$editorCellHeight = ($heightUnit == "px" && $height > 50 ? "height:".($height - 27 - ($this->bResizable ? 3 : 0))."px" : "");
		?>
		<?/* <img src="/bitrix/images/1.gif" width="300" height="1" id="bxlhe_ww_<?=$this->Id?>" />*/?>
<div class="bxlhe-frame" id="bxlhe_frame_<?=$this->Id?>" style="width:<?=$width.$widthUnit?>; height:<?=$height.$heightUnit?>;"><table class="bxlhe-frame-table" cellspacing="0" style="height:<?=$height.$heightUnit?>; width: 100%;">
		<tr class="bxlhe-editor-toolbar-row"><td class="bxlhe-editor-buttons" style="height:27px;"><div class="lhe-stat-toolbar-cont lhe-stat-toolbar-cont-preload"></div></td></tr>
		<tr><td class="bxlhe-editor-cell" style="<?=$editorCellHeight?>"></td></tr>
		<?if ($this->bResizable):?>
		<tr><td class="lhe-resize-row" style="height: 3px;"><img id="bxlhe_resize_<?=$this->Id?>" src="/bitrix/images/1.gif"/></td></tr>
		<?endif;?>
</table></div>
		<?
	}

	public function InitScripts()
	{
		ob_start();
		foreach(GetModuleEvents("fileman", "OnIncludeLightEditorScript", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($this->Id));
		$scripts = trim(ob_get_contents());
		ob_end_clean();

		$scripts = str_replace("<script>", "", $scripts);
		$scripts = str_replace("</script>", "", $scripts);

		$loadScript = "";
		foreach ($this->arJSPath as $path)
		{
			if ($loadScript != "")
			{
				$loadScript .= ", ";
			}
			$loadScript .= "\"".$this->GetActualPath($path)."\"";
		}
		?>
		<script>
		function LoadLHE_<?=$this->Id?>()
		{
			function _lheScriptloaded()
			{
				if (!window.JCLightHTMLEditor)
					return setTimeout(_lheScriptloaded, 10);

				<?if (!empty($scripts)):?>
				// User's customization scripts here
				try{<?= $scripts?>}
				catch(e){alert('Errors in customization scripts! ' + e);}
				<?endif;?>

				if (
					<?= ($this->bRecreate ? 'true' : 'false')?> || 
					JCLightHTMLEditor.items['<?= $this->Id?>'] == undefined ||
					!document.body.contains(JCLightHTMLEditor.items['<?= $this->Id?>'].pFrame)
				)
				{
					top.<?=$this->jsObjName?> = window.<?=$this->jsObjName?> = new window.JCLightHTMLEditor(<?=CUtil::PhpToJSObject($this->JSConfig)?>);
					BX.onCustomEvent(window, 'LHE_ConstructorInited', [window.<?=$this->jsObjName?>]);
				}
			}

			if (!window.JCLightHTMLEditor)
			{
				BX.loadCSS("<?=$this->GetActualPath($this->cssPath)?>");
				<?if (!empty($this->mess)):?>
				LHE_MESS = window.LHE_MESS = "<?=CUtil::PhpToJSObject($this->messOld)?>"; (window.BX||top.BX).message(<?=CUtil::PhpToJSObject($this->mess, false)?>);
				<?endif?>
				BX.loadScript([<?=$loadScript?>], _lheScriptloaded);
			}
			else
			{
				_lheScriptloaded();
			}
		}

		<?if(!$this->bInitByJS):?>
			BX.ready(function(){LoadLHE_<?=$this->Id?>();});
		<?endif;?>

		</script><?
	}

	public static function InitFileDialogs()
	{
		// Link
		CAdminFileDialog::ShowScript(Array(
			"event" => "LHED_Link_FDOpen",
			"arResultDest" => Array("ELEMENT_ID" => "lhed_link_href"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'php, html',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));

		// Image
		CAdminFileDialog::ShowScript(Array
		(
			"event" => "LHED_Img_FDOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Img_SetUrl"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'image',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));

		// video path
		CAdminFileDialog::ShowScript(Array
		(
			"event" => "LHED_VideoPath_FDOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Video_SetPath"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'wmv,wma,flv,vp6,mp3,mp4,aac,jpg,jpeg,gif,png',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));

		// video preview
		CAdminFileDialog::ShowScript(Array
		(
			"event" => "LHED_VideoPreview_FDOpen",
			"arResultDest" => Array("ELEMENT_ID" => "lhed_video_prev_path"),
			"arPath" => Array("SITE" => SITE_ID),
			"select" => 'F',
			"operation" => 'O',
			"showUploadTab" => true,
			"showAddToMenuTab" => false,
			"fileFilter" => 'image',
			"allowAllFiles" => true,
			"SaveConfig" => true
		));
	}

	public static function InitMedialibDialogs()
	{
		CMedialib::ShowDialogScript(array(
			"event" => "LHED_Img_MLOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Img_SetUrl")
		));
		CMedialib::ShowDialogScript(array(
			"event" => "LHED_Video_MLOpen",
			"arResultDest" => Array("FUNCTION_NAME" => "LHED_Video_SetPath")
		));
	}

	public static function IsMobileDevice()
	{
		return preg_match('/ipad|iphone|android|mobile|touch/i',$_SERVER['HTTP_USER_AGENT']);
	}
}
?>