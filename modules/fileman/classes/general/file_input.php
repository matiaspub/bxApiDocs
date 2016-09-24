<?
IncludeModuleLangFile(__FILE__);
class CFileInput
{
	private static
		$bMultiple = false,
		$bUseUpload = false,
		$bUseMedialib = false,
		$bUseFileDialog = false,
		$bUseCloud = false,
		$bShowDescInput = false,
		$bShowDelInput = true,
		$curFileIds = array(),
		$curFiles = array(),
		$jsId,
		$bFileExists,
		$menuNew,
		$menuExist,
		$delInputName,
		$descInputName,
		$maxPreviewWidth,
		$maxPreviewHeight,
		$minPreviewWidth,
		$minPreviewHeight,
		$maxCount,
		$inputSize = 50,
		$arInputs = array(),
		$inputNameTemplate,
		$showInfo,
		$bViewMode
	;

	private static function Init($showInfo, $inputName, $maxCount = false)
	{
		self::$bMultiple = false;
		self::$bUseUpload = false;
		self::$bUseMedialib = false;
		self::$bUseFileDialog = false;
		self::$bUseCloud = false;
		self::$bShowDelInput = false;
		self::$bShowDescInput = false;
		self::$bViewMode = false;
		self::$maxCount = $maxCount;
		self::$showInfo = $showInfo;

		self::$maxPreviewWidth = max((isset($showInfo['MAX_SIZE']['W']) ? $showInfo['MAX_SIZE']['W'] : 200), 40);
		self::$maxPreviewHeight = max((isset($showInfo['MAX_SIZE']['H']) ? $showInfo['MAX_SIZE']['H'] : 200), 40);

		self::$minPreviewWidth = min((isset($showInfo['MIN_SIZE']['W']) ? $showInfo['MIN_SIZE']['W'] : 120), 500);
		self::$minPreviewHeight = min((isset($showInfo['MIN_SIZE']['H']) ? $showInfo['MIN_SIZE']['H'] : 100), 500);

		self::$jsId = 'bx_file_'.strtolower(preg_replace("/[^a-z0-9]/i", "_", $inputName));
	}

/**
 * @param $strInputName
 * @param string $strFileId
 * @param bool|array $showInfo
 * @param array $inputs
 * @return string
 */
	public static function Show(
		$strInputName,
		$strFileId = "",
		$showInfo = false,
		$inputs = array()
	)
	{
		global $USER;

		CJSCore::Init('file_input');
		ob_start();

		$uploadInput = $inputs['upload'] === true ? array() : $inputs['upload'];
		$medialibInput = $inputs['medialib'] === true ? array() : $inputs['medialib'];
		$fileDialogInput = $inputs['file_dialog'] === true ? array() : $inputs['file_dialog'];
		$cloudInput = $inputs['cloud'] === true ? array() : $inputs['cloud'];

		self::Init($showInfo, $strInputName);

		//1. Upload from PC
		if(is_array($uploadInput))
		{
			self::$bUseUpload = true;
			if(!array_key_exists("NAME", $uploadInput))
				$uploadInput["NAME"] = $strInputName;
		}

		//2. Select file from medialib
		if(COption::GetOptionString('fileman', "use_medialib", "Y") != "N" && is_array($medialibInput))
		{
			self::$bUseMedialib = true;
			if(!array_key_exists("NAME", $medialibInput))
				$medialibInput["NAME"] = $strInputName;
		}

		//3. Select file from file dialog
		if(is_array($fileDialogInput))
		{
			self::$bUseFileDialog = true;
			if(!array_key_exists("NAME", $fileDialogInput))
				$fileDialogInput["NAME"] = $strInputName;
		}

		//4. Select file from cloud
		if(
			is_array($cloudInput)
			&& $USER->CanDoOperation("clouds_browse")
			&& CModule::IncludeModule("clouds")
			&& CCloudStorage::HasActiveBuckets()
		)
		{
			self::$bUseCloud = true;
			if(!array_key_exists("NAME", $cloudInput))
				$cloudInput["NAME"] = $strInputName;
		}

		if($inputs['description'] !== false)
		{
			self::$bShowDescInput = true;
			self::$descInputName = isset($inputs['description']["NAME"]) ? $inputs['description']["NAME"] : self::GetInputName($strInputName, "_descr");
		}

		if($inputs['del'] !== false)
		{
			self::$bShowDelInput = true;
			self::$delInputName = isset($inputs['del']["NAME"]) ? $inputs['del']["NAME"] : self::GetInputName($strInputName, "_del");
		}

		// $arFile - Array with current file or false if it's empty
		self::$curFileIds = is_array($strFileId) && !array_key_exists("tmp_name", $strFileId)? $strFileId : array($strFileId);
		self::$curFiles = array();
		self::$bFileExists = false;

		foreach(self::$curFileIds as $fileId)
		{
			if (is_array($fileId))
				continue;
			if (strlen($fileId) <= 1 && intVal($fileId) === 0)
				continue;

			self::$bFileExists = true;
			if($arFile = self::GetFile($fileId))
			{
				$arFile['FILE_NOT_FOUND'] = false;
				if (self::$bShowDescInput && isset($inputs['description']['VALUE']))
					$arFile['DESCRIPTION'] = $inputs['description']['VALUE'];
			}
			else
			{
				$arFile = array(
					'FILE_NOT_FOUND' => true,
					'DEL_NAME' => self::$delInputName
				);
			}
			self::$curFiles[] = $arFile;
		}

		self::$bViewMode = self::IsViewMode();
		if (self::$bViewMode)
			self::$bShowDelInput = false;

		if (!self::$bViewMode || self::$bFileExists)
		{
			$inputs = array(
				'upload' => self::$bUseUpload,
				'medialib' => self::$bUseMedialib,
				'file_dialog' => self::$bUseFileDialog,
				'cloud' => self::$bUseCloud,
				'del' => self::$bShowDelInput,
				'description' => self::$bShowDescInput
			);

			self::$arInputs = array(
				'upload' => $uploadInput,
				'medialib' => $medialibInput,
				'file_dialog' => $fileDialogInput,
				'cloud' => $cloudInput
			);

			self::DisplayControl($inputs);
		}

		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

/**
 * @param array $values
 * @param string $inputNameTemplate
 * @param bool|array $showInfo
 * @param bool $maxCount
 * @param array $inputs
 * @return string
 */
	public static function ShowMultiple(
		$values = array(),
		$inputNameTemplate = "", // #IND# will be replaced by autoincrement int (0, 1, 2,..)
		$showInfo = false,
		$maxCount = false,
		$inputs = array()
	)
	{
		CJSCore::Init('file_input');
		ob_start();

		global $USER;
		self::Init($showInfo, $inputNameTemplate, $maxCount);
		self::$bMultiple = true;

		$arDescInput = (is_array($inputs['description']) && isset($inputs['description']['VALUES']) && isset($inputs['description']['NAME_TEMPLATE'])) ? $inputs['description'] : false;

		$inputs = array(
			'upload' => $inputs['upload'] === true,
			'medialib' => $inputs['medialib'] === true && COption::GetOptionString('fileman', "use_medialib", "Y") != "N",
			'file_dialog' => $inputs['file_dialog'] === true,
			'cloud' => $inputs['cloud'] === true && $USER->CanDoOperation("clouds_browse") && CModule::IncludeModule("clouds") && CCloudStorage::HasActiveBuckets(),
			'del' => $inputs['del'] !== false,
			'description' => $inputs['description'] === true || $arDescInput
		);

		self::$bUseUpload = $inputs['upload'];
		self::$bUseMedialib = $inputs['medialib'];
		self::$bUseFileDialog = $inputs['file_dialog'];
		self::$bUseCloud = $inputs['cloud'];
		self::$bShowDelInput = $inputs['del'];
		self::$bShowDescInput = $inputs['description'];
		self::$inputNameTemplate = $inputNameTemplate;

		self::$bViewMode = self::IsViewMode();
		if (self::$bViewMode)
			self::$bShowDelInput = false;

		if (self::$bShowDelInput)
			self::$delInputName = self::GetInputName($inputNameTemplate, "_del");

		if (self::$bShowDescInput)
		{
			self::$descInputName = '';
			if ($arDescInput)
				self::$descInputName = $arDescInput['NAME_TEMPLATE'];

			if (empty(self::$descInputName))
				self::$descInputName = self::GetInputName($inputNameTemplate, "_descr");
		}

		// $arFile - Array with current file or false if it's empty
		self::$curFiles = array();
		self::$bFileExists = false;

		foreach($values as $inputName => $fileId)
		{
			if (strlen($fileId) <= 1 && intVal($fileId) === 0)
				continue;

			self::$bFileExists = true;
			if($arFile = self::GetFile($fileId))
			{
				$arFile['FILE_NOT_FOUND'] = false;
				$arFile['INPUT_NAME'] = $inputName;
				$arFile['DEL_NAME'] = self::GetInputName($inputName, '_del');
				$arFile['DESC_NAME'] = self::GetInputName($inputName, '_descr');

				if ($arDescInput)
				{
					list($descName, $descVal) = each($arDescInput['VALUES']);
					$arFile['DESC_NAME'] = $descName;
					$arFile['DESCRIPTION'] = $descVal;
				}
			}
			else
			{
				$arFile = array(
					'FILE_NOT_FOUND' => true,
					'INPUT_NAME' => $inputName,
					'DEL_NAME' => self::GetInputName($inputName, '_del'),
					'DESC_NAME' => self::GetInputName($inputName, '_descr')
				);
			}

			self::$curFiles[] = $arFile;
		}

		self::DisplayControl($inputs);

		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	private static function GetFile($fileId = "")
	{
		$arFile = CFile::GetFileArray($fileId);
		$io = CBXVirtualIo::GetInstance();
		//Check if not ID but file path was given
		if(!is_array($arFile) && $fileId != "")
		{
			$strFilePath = $_SERVER["DOCUMENT_ROOT"].$fileId;
			if($io->FileExists($strFilePath))
			{
				$flTmp = $io->GetFile($strFilePath);
				$arFile = array(
					"PATH" => $fileId,
					"FILE_SIZE" => $flTmp->GetFileSize(),
					"DESCRIPTION" => "",
				);

				$arImageSize = CFile::GetImageSize($strFilePath);
				if(is_array($arImageSize))
				{
					$arFile["WIDTH"] = intval($arImageSize[0]);
					$arFile["HEIGHT"] = intval($arImageSize[1]);
				}
			}
			elseif(self::$showInfo['IMAGE'] == 'N')
			{
				$arFile = array(
					"PATH" => $fileId,
					"FORMATED_SIZE" => '',
					"DESCRIPTION" => "",
					"IS_IMAGE" => false
				);
				return $arFile;
			}
		}


		$sImagePath = isset($arFile["PATH"]) ? $arFile["PATH"] : $arFile["SRC"];
		if(
			$arFile["HANDLER_ID"]
			|| (defined("BX_IMG_SERVER") && substr($sImagePath, 0, strlen(BX_IMG_SERVER)) === BX_IMG_SERVER)
			|| $io->FileExists($_SERVER["DOCUMENT_ROOT"].$sImagePath)
		)
		{
			$arFile["FORMATED_SIZE"] = CFile::FormatSize($arFile["FILE_SIZE"]);
			$arFile["IS_IMAGE"] = $arFile["WIDTH"] > 0 && $arFile["HEIGHT"] > 0 && self::$showInfo['IMAGE'] != 'N';

			//Mantis:#65168
			if ($arFile["CONTENT_TYPE"] && $arFile["IS_IMAGE"] && strpos($arFile["CONTENT_TYPE"], 'application') !== false)
			{
				$arFile["IS_IMAGE"] = false;
			}

			unset($arFile["MODULE_ID"], $arFile["CONTENT_TYPE"], $arFile["SUBDIR"], $arFile["~src"]);
			return $arFile;
		}

		return false;
	}

	private static function DisplayControl($inputs = array())
	{
		self::$menuNew = array();
		self::$menuExist = array();

		if ($inputs['upload'])
		{
			self::$menuNew[] = array("ID" => "upload", "GLOBAL_ICON" => "adm-menu-upload-pc", "TEXT" => GetMessage("ADM_FILE_UPLOAD"), "CLOSE_ON_CLICK" => false);
			self::$menuExist[] = array("ID" => "upload", "GLOBAL_ICON" => "adm-menu-upload-pc", "TEXT" => GetMessage("ADM_FILE_NEW_UPLOAD"), "CLOSE_ON_CLICK" => false);
		}
		if ($inputs['medialib'])
		{
			self::$menuNew[] = array("TEXT" => GetMessage("ADM_FILE_MEDIALIB"), "GLOBAL_ICON" => "adm-menu-upload-medialib", "ONCLICK" => "OpenMedialibDialog".self::$jsId."()");
			self::$menuExist[] = array("TEXT" => GetMessage("ADM_FILE_NEW_MEDIALIB"), "GLOBAL_ICON" => "adm-menu-upload-medialib", "ONCLICK" => "OpenMedialibDialog".self::$jsId."()");
		}
		if ($inputs['file_dialog'])
		{
			self::$menuNew[] = array("TEXT" => GetMessage("ADM_FILE_SITE"), "GLOBAL_ICON" => "adm-menu-upload-site", "ONCLICK" => "OpenFileDialog".self::$jsId."()");
			self::$menuExist[] = array("TEXT" => GetMessage("ADM_FILE_NEW_SITE"), "GLOBAL_ICON" => "adm-menu-upload-site", "ONCLICK" => "OpenFileDialog".self::$jsId."()");
		}

		if ($inputs['cloud'])
		{
			self::$menuNew[] = array("TEXT" => GetMessage("ADM_FILE_CLOUD"), "GLOBAL_ICON" => "adm-menu-upload-cloud", "ONCLICK" => "OpenCloudDialog".self::$jsId."()");
			self::$menuExist[] = array("TEXT" => GetMessage("ADM_FILE_NEW_CLOUD"), "GLOBAL_ICON" => "adm-menu-upload-cloud", "ONCLICK" => "OpenCloudDialog".self::$jsId."()");
		}

		$arConfig = array(
			'id' => self::$jsId,
			'fileExists' => self::$bFileExists,
			'files' => self::$curFiles,
			'menuNew' => self::$menuNew,
			'menuExist' => self::$menuExist,
			'multiple' => self::$bMultiple,
			'useUpload' => self::$bUseUpload,
			'useMedialib' => self::$bUseMedialib,
			'useFileDialog' => self::$bUseFileDialog,
			'useCloud' => self::$bUseCloud,
			'delName' => self::$delInputName,
			'descName' => self::$descInputName,
			'inputSize' => self::$inputSize,
			'minPreviewHeight' => self::$minPreviewHeight,
			'minPreviewWidth' => self::$minPreviewWidth,
			'showDesc' => self::$bShowDescInput,
			'showDel' => self::$bShowDelInput,
			'maxCount' => self::$maxCount,
			'viewMode' => self::$bViewMode
		);

		if (self::$bMultiple)
			$arConfig['inputNameTemplate'] = self::$inputNameTemplate;
		else
			$arConfig['inputs'] = self::$arInputs;

		if (self::$bUseCloud)
			$arConfig['cloudDialogPath'] = '/bitrix/admin/clouds_file_search.php?lang='.LANGUAGE_ID.'&n=';


		//Base container
		?><div class="adm-input-file-control" id="<?= self::$jsId.'_cont'?>"><?
			if (!self::$bViewMode)
				self::DisplayDialogs();

			if (self::$bFileExists)
				foreach(self::$curFiles as $ind => $arFile)
					self::DisplayFile($arFile, $ind);
		?>
		<script type="text/javascript">new top.BX.file_input(<?= CUtil::PHPToJSObject($arConfig)?>);</script>
		</div>
		<?/* Used to refresh form content - workaround for IE bug (mantis:37969) */?>
	<div id="<?= self::$jsId.'_ie_bogus_container'?>"><input type="hidden" value="" /></div>
	<?
	}

	private static function DisplayDialogs()
	{
		if(self::$bUseMedialib)
		{
			CMedialib::ShowDialogScript(array(
				"event" => "OpenMedialibDialog".self::$jsId,
				"arResultDest" => array(
					"FUNCTION_NAME" => "SetValueFromMedialib".self::$jsId,
				)
			));
		}

		if (self::$bUseFileDialog)
		{
			CAdminFileDialog::ShowScript
			(
				Array(
					"event" => "OpenFileDialog".self::$jsId,
					"arResultDest" => array("FUNCTION_NAME" => "SetValueFromFileDialog".self::$jsId),
					"arPath" => array("SITE" => SITE_ID, "PATH" =>"/upload"),
					"select" => 'F',// F - file only, D - folder only
					"operation" => 'O',
					"showUploadTab" => true,
					"allowAllFiles" => true,
					"SaveConfig" => true,
				)
			);
		}
	}

	private static function DisplayFile($arFile = array(), $ind = 0)
	{
		$hintId = self::$jsId.'_file_disp_'.$ind;
		$bNotFound = $arFile['FILE_NOT_FOUND'];

		// Hint
		$hint = '';

		if (!$bNotFound)
		{
			$sImagePath = isset($arFile["PATH"]) ? $arFile["PATH"] : $arFile["SRC"];
			$descName = isset($arFile['DESC_NAME']) ? $arFile['DESC_NAME'] : self::$descInputName;

			if ($arFile['FORMATED_SIZE'] != '')
				$hint .= '<span class="adm-input-file-hint-row">'.GetMessage('ADM_FILE_INFO_SIZE').':&nbsp;&nbsp;'.$arFile['FORMATED_SIZE'].'</span>';

			if ($arFile['IS_IMAGE'])
				$hint .= '<span class="adm-input-file-hint-row">'.GetMessage('ADM_FILE_INFO_DIM').':&nbsp;&nbsp;'.$arFile['WIDTH'].'x'.$arFile['HEIGHT'].'</span>';
			if ($sImagePath != '')
				$hint .= '<span class="adm-input-file-hint-row">'.GetMessage('ADM_FILE_INFO_LINK').':&nbsp;&nbsp;<a href="'.CHTTP::urnEncode($sImagePath, "UTF-8").'">'.$sImagePath.'</a></span>';

			if (!self::$bShowDescInput && $arFile['DESCRIPTION'] != "")
				$hint .= '<span class="adm-input-file-hint-row">'.GetMessage('ADM_FILE_DESCRIPTION').':&nbsp;&nbsp;'.htmlspecialcharsbx($arFile['DESCRIPTION']).'</span>';
		}
		?><span class="adm-input-file-exist-cont" id="<?= self::$jsId?>_file_cont_<?= $ind?>">
		<div class="adm-input-file-ex-wrap<?if(self::$bMultiple){echo ' adm-input-cont-bordered';}?>">
		<?
		if ($bNotFound)
		{
			?>
			<span id="<?= self::$jsId.'_file_404_'.$ind?>" class="adm-input-file-not-found">
			<?= GetMessage('ADM_FILE_NOT_FOUND')?>
			</span>
			<?
		}
		elseif ($arFile['IS_IMAGE'])
		{
			$file = CFile::ResizeImageGet($arFile['ID'], array('width' => self::$maxPreviewWidth, 'height' => self::$maxPreviewHeight), BX_RESIZE_IMAGE_PROPORTIONAL, true);
			?>
			<span id="<?= $hintId?>" class="adm-input-file-preview" style="<?if(self::$minPreviewWidth > 0){echo 'min-width: '.self::$minPreviewWidth.'px;';}?> <?if(self::$minPreviewHeight > 0){echo 'min-height:'.self::$minPreviewHeight.'px;';}?>">
				<?= CFile::Show2Images($file['src'], $arFile['SRC'], self::$maxPreviewWidth, self::$maxPreviewHeight);?>
				<div id="<?= self::$jsId.'_file_del_lbl_'.$ind?>" class="adm-input-file-del-lbl"><?= GetMessage
			('ADM_FILE_DELETED_TITLE')?></div>
			</span>
			<?
		}
		else
		{
			$val = !empty($arFile['FILE_NAME']) ? $arFile['FILE_NAME'] : $sImagePath;
			?>
			<a id="<?= $hintId?>" href="<?= htmlspecialcharsbx($arFile['SRC'])?>" class="adm-input-file-name"><?= htmlspecialcharsbx($val)?></a>
			<?
		}

		if ($hint != '')
		{
		?>
		<script type="text/javascript">
			new top.BX.CHint({
				parent: top.BX("<?= $hintId?>"),
				show_timeout: 10,
				hide_timeout: 200,
				dx: 2,
				preventHide: true,
				min_width: 250,
				hint: '<?= CUtil::JSEscape($hint)?>'
			});
		</script>
			<?
		}

		if (!self::$bViewMode)
			self::ShowOpenerMenuHtml(self::$jsId.'_menu_'.$ind, $ind);

		if (!$bNotFound && self::$bShowDescInput)
		{
			?>
			<div id="<?= self::$jsId.'_file_desc_'.$ind?>" class="adm-input-file-desc-inp-cont" <?if($arFile['DESCRIPTION'] == ""){echo 'style="display: none;"';}?>>
				<input name="<?= $descName?>" class="adm-input" type="text" value="<?= htmlspecialcharsbx($arFile['DESCRIPTION'])?>" size="<?= self::$inputSize?>" placeholder="<?= GetMessage("ADM_FILE_DESC")?>" <?if(self::$bViewMode){echo ' disabled="disabled"';}?>>
			</div>
			<?
		}
		?>
		</div>
		</span>
		<?
	}

	private static function ShowOpenerMenuHtml($id, $data=false)
	{
		?><span <?if($data !== false){echo 'data-bx-meta="'.$data.'"';}?> id="<?= $id?>" class="adm-btn add-file-popup-btn"></span><?
	}

	private static function GetInputName($inputName, $type = "")
	{
		if ($type == "")
			return $inputName;
		$p = strpos($inputName, "[");
		return  ($p > 0) ? substr($inputName, 0, $p).$type.substr($inputName, $p) : $inputName.$type;
	}

	private static function IsViewMode()
	{
		return !self::$bUseUpload && !self::$bUseMedialib && !self::$bUseFileDialog && !self::$bUseCloud;
	}
}
?>