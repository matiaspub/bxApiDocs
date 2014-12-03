<?
// ***** CImageUploader, CFlashUploader *****
IncludeModuleLangFile(__FILE__);

class CImageUploader
{
	public static function ShowScript($Params = array())
	{
		global $APPLICATION;
		self::SetId($Params['id']);

		self::CheckDirPath($Params["pathToTmp"]);

		if (!isset($Params['height']))
			$Params['height'] = '500px';
		if (!isset($Params['width']))
			$Params['width'] = '100%';
		if (!isset($Params['layout']) || ($Params['layout'] != 'ThreePanes' && $Params['layout'] != 'OnePane'))
			$Params['layout'] = 'ThreePanes';
		if (!isset($Params['folderPaneHeight']))
			$Params['folderPaneHeight'] = 300;
		if (!isset($Params['treePaneWidth']))
			$Params['treePaneWidth'] = 200;
		if (!isset($Params['folderViewMode']) || !in_array($Params['folderViewMode'], array('Thumbnails', 'Tiles', 'Details', ' List')))
			$Params['folderViewMode'] = 'Thumbnails';
		if (!isset($Params['uploadViewMode']) || !in_array($Params['uploadViewMode'], array('Thumbnails', 'Tiles', 'Details', ' List')))
			$Params['uploadViewMode'] = 'Thumbnails';

		if (!isset($Params['thumbnailJpegQuality']) || $Params['thumbnailJpegQuality'] > 100 || $Params['thumbnailJpegQuality'] <= 0)
			$Params['thumbnailJpegQuality'] = 90;

		$Params['showAddFileButton'] = $Params['showAddFileButton'] === true;
		$Params['showAddFolderButton'] = $Params['showAddFolderButton'] === true;

		$Params['enableCrop'] = $Params['enableCrop'] !== false;

		// Uploading config
		if (!isset($Params['filesPerPackage']) || $Params['filesPerPackage'] <= 0)
			$Params['filesPerPackage'] = 1;

		if (!isset($Params['chunkSize']) || $Params['chunkSize'] <= 0)
			$Params['chunkSize'] = self::GetChunkSize();

		if (!isset($Params['fileMask']))
			$Params['fileMask'] = "*.*";

		if (!isset($Params['converters']) || count($Params['converters']) == 0)
			$Params['converters'] = array('code' => 'real', 'width' => false, 'height' => false);

		$APPLICATION->AddHeadScript('/bitrix/image_uploader/bximageuploader.js');
		$APPLICATION->SetAdditionalCSS('/bitrix/image_uploader/bximageuploader.css');

		$APPLICATION->AddHeadScript('/bitrix/image_uploader/aurigma.uploader.js');
		$APPLICATION->AddHeadScript('/bitrix/image_uploader/aurigma.uploader.installationprogress.js');
		$id = self::GetId();

		$cookie = '';
		foreach($_COOKIE as $key => $val)
		{
			$cookie .= $key.'='.$val.';';
		}

		if ($Params['showAddFileButton'] || $Params['showAddFolderButton']): ?>
		<div class="bxiu-buttons">
			<?if($Params['showAddFileButton']):?>
			<button type="button" onclick="addFiles();"><?= GetMessage("UPLOADER_ADD_FILES")?></button>
			<?endif;?>
			<?if($Params['showAddFolderButton']):?>
			<button type="button" onclick="addFolder();"><?= GetMessage("UPLOADER_ADD_FOLDERS")?></button>
			<?endif;?>
		</div>
		<?endif;?>

		<script>
		var oBXUploaderHandler_<?= CUtil::JSEscape($id)?> = new window.BXUploader({id: '<?= CUtil::JSEscape($id)?>'});
		var BXIU_<?= CUtil::JSEscape($id)?> = $au.uploader({id: '<?= CUtil::JSEscape($id)?>'});

		// Params
		var bxp = {
			id: '<?= CUtil::JSEscape($id)?>',
			width: '<?= CUtil::JSEscape($Params['width'])?>',
			height: '<?= CUtil::JSEscape($Params['height'])?>',
			paneLayout: '<?= CUtil::JSEscape($Params['layout'])?>',
			enableAutoRotation: true,
			enableDescriptionEditor: true,
			enableRotation: true,
			activeXControl:
			{
				classId: '776D11E8-CD62-4105-B4F2-ABFDE7B4BFC5',
				codeBase: '/bitrix/image_uploader/ImageUploader7.cab',
				codeBase64: '/bitrix/image_uploader/ImageUploader7_x64.cab',
				progId: 'Bitrix.ImageUploader.7',
				version: '7.0.38.0'
			},
			javaControl:
			{
				className: 'com.bitrixsoft.imageuploader.ImageUploader',
				codeBase: '/bitrix/image_uploader/ImageUploader7.jar',
				version: '7.0.38.0'
			},
			metadata: {
				cookie: '<?= CUtil::JSEscape($cookie)?>'
			},
			events:{
				afterPackageUpload: [],
				afterSendRequest: [],
				afterUpload: [],
				beforePackageUpload: [],
				beforeSendRequest: [],
				beforeUpload: [],
				error: [],
				folderChange: [],
				imageEditorClose: [],
				imageRotated: [],
				initComplete: [],
				preRender: [],
				progress: [],
				restrictionFailed: [],
				selectionChange: [],
				uploadFileCountChange: [],
				viewChange: []
			},
			restrictions: {
				fileMask: '<?= CUtil::JSEscape($Params['fileMask'])?>'
			},
			uploadSettings: {
				actionUrl: '<?= CUtil::JSEscape(self::StrangeUrlEncode($Params['actionUrl']))?>',
				enableInstantUpload: false, // Immediate upload after file selecting
				filesPerPackage: <?= intval($Params['filesPerPackage'])?>,
				autoRecoveryTimeout: 5000,
				autoRecoveryMaxAttemptCount: 1,
				chunkSize: <?= intval($Params['chunkSize'])?>
			},
			paneItem: {
				descriptionAddedIconImageFormat: '',
				descriptionEditorIconImageFormat: '',
				imageCroppedIconImageFormat: '',
				imageEditorIconImageFormat: '',
				qualityMeter: {},
				removalIconImageFormat: '',
				rotationIconImageFormat: ''
			},
			converters: [],
			imageEditor: {
				enableCrop: <?= $Params['enableCrop'] ? 'true' : 'false'?>
			}
		};

		<?if (isset($Params['appendFormName']) && $Params['appendFormName'] != ''):?>
			bxp.metadata.additionalFormName = '<?= CUtil::JSEscape($Params['appendFormName'])?>';
		<?endif;?>

		<?foreach ($Params['converters'] as $converter):?>
			<?$bSource = (!$converter['width'] || !$converter['height']);?>
			bxp.converters.push({
					mode: '*.*=Thumbnail',
					thumbnailApplyCrop: true,
					thumbnailKeepColorSpace: true,
					thumbnailJpegQuality: <?= $Params['thumbnailJpegQuality']?>,
					thumbnailResizeQuality: "High", // High | Medium | Low,
					thumbnailCopyIptc: true,
					thumbnailCopyExif: true,
				<?if ($bSource):?>
					thumbnailFitMode: "ActualSize", // Fit | OrientationalFit | Width | Height | ActualSize
				<?else:?>
					thumbnailFitMode: "Fit", // Fit | OrientationalFit | Width | Height | ActualSize
					thumbnailHeight: <?= intval($converter['height'])?>,
					thumbnailWidth: <?= intval($converter['width'])?>,
				<?endif;?>
					thumbnailCompressOversizedOnly: true
			});
		<?endforeach;?>

		<?if ($Params['layout'] == 'ThreePanes'):?>
			bxp.folderPane = {
				height: <?= intval($Params['folderPaneHeight'])?>,
				viewMode: '<?= CUtil::JSEscape($Params['folderViewMode'])?>'
			};
			bxp.treePane = {width: <?= intval($Params['treePaneWidth'])?>};
		<?endif;?>

		<?if ($Params['uploadViewMode'] != 'Thumbnails'):?>
			bxp.uploadPane = {
				viewMode: '<?= CUtil::JSEscape($Params['uploadViewMode'])?>'
			};
		<?endif;?>

		<?if (isset($Params['redirectUrl'])):?>
			bxp.uploadSettings.redirectUrl = '<?= CUtil::JSEscape($Params['redirectUrl'])?>';
		<?endif;?>

		<? if (isset($Params['minFileCount'])):?>
			bxp.restrictions.minFileCount= '<?= CUtil::JSEscape($Params['minFileCount'])?>';
		<?endif;?>
		<? if (isset($Params['maxFileCount'])):?>
			bxp.restrictions.maxFileCount= '<?= CUtil::JSEscape($Params['maxFileCount'])?>';
		<?endif;?>
		<? if (isset($Params['maxTotalFileSize'])):?>
			bxp.restrictions.maxTotalFileSize = '<?= CUtil::JSEscape($Params['maxTotalFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['maxFileSize'])):?>
			bxp.restrictions.maxFileSize = '<?= CUtil::JSEscape($Params['maxFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['minFileSize'])):?>
			bxp.restrictions.minFileSize = '<?= CUtil::JSEscape($Params['minFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['minImageWidth'])):?>
			bxp.restrictions.minImageWidth = '<?= CUtil::JSEscape($Params['minImageWidth'])?>';
		<?endif;?>
		<? if (isset($Params['minImageHeight'])):?>
			bxp.restrictions.minImageHeight = '<?= CUtil::JSEscape($Params['minImageHeight'])?>';
		<?endif;?>
		<? if (isset($Params['maxImageWidth'])):?>
			bxp.restrictions.maxImageWidth = '<?= CUtil::JSEscape($Params['maxImageWidth'])?>';
		<?endif;?>
		<? if (isset($Params['maxImageHeight'])):?>
			bxp.restrictions.maxImageHeight = '<?= CUtil::JSEscape($Params['maxImageHeight'])?>';
		<?endif;?>


		<?if (isset($Params['cropRatio'])):?>
			bxp.imageEditor.cropRatio = '<?= CUtil::JSEscape($Params['cropRatio'])?>';
		<?endif;?>
		<?if (isset($Params['cropMinSize'])):?>
			bxp.imageEditor.cropMinSize = '<?= CUtil::JSEscape($Params['cropMinSize'])?>';
		<?endif;?>

		<?if ($Params['useWatermark']):?>
			bxp = oBXUploaderHandler_<?= $id?>.enableWatermark(bxp, {
				rules: '<?= CUtil::JSEscape($Params['watermarkConfig']['rules'])?>', // ALL | USER
				type: '<?= CUtil::JSEscape($Params['watermarkConfig']['type'])?>', //
				text: '<?= CUtil::JSEscape($Params['watermarkConfig']['text'])?>', //
				color: '<?= CUtil::JSEscape($Params['watermarkConfig']['color'])?>',
				position: '<?= CUtil::JSEscape($Params['watermarkConfig']['position'])?>',
				size: '<?= CUtil::JSEscape($Params['watermarkConfig']['size'])?>',
				opacity: '<?= CUtil::JSEscape($Params['watermarkConfig']['opacity'])?>',
				file: '<?= CUtil::JSEscape($Params['watermarkConfig']['file'])?>',
				fileWidth: '<?= intVal($Params['watermarkConfig']['fileWidth'])?>',
				fileHeight: '<?= intVal($Params['watermarkConfig']['fileHeight'])?>',

				values: {
					use: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['use'])?>',
					type: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['type'])?>',
					text: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['text'])?>',
					color: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['color'])?>',
					position: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['position'])?>',
					size: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['size'])?>',
					opacity: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['opacity'])?>',
					file: '<?= CUtil::JSEscape($Params['watermarkConfig']['values']['file'])?>'
				}
			});
		<?endif;?>

		BXIU_<?= CUtil::JSEscape($id)?>.set(bxp);

		// Set theme
		// Todo: add view customization params
		var theme = {
			borderStyle: 'FixedSingle',
			headerColor: '#E4EEFB',
			headerTextColor: '#000000',
			panelBorderColor: '#C9C9C9',
			statusPane: {
				color: '#EEEEEE'
			}
		};
		BXIU_<?= CUtil::JSEscape($id)?>.set(theme);

		// Apply localization
		BXIU_<?= CUtil::JSEscape($id)?>.set(<?= self::GetLocalization()?>);

		<?if($Params['showAddFileButton']):?>
		function addFiles()	{$au.uploader('<?= CUtil::JSEscape($id)?>').uploadPane().addFiles();}
		<?endif;?>
		<?if($Params['showAddFolderButton']):?>
		function addFolder(){$au.uploader('<?= CUtil::JSEscape($id)?>').uploadPane().addFolders();}
		<?endif;?>
		BX.ready(function(){BX('bxiu_<?= CUtil::JSEscape($id)?>').innerHTML = BXIU_<?= CUtil::JSEscape($id)?>.getHtml();})
		</script>
		<div id="bxiu_<?= htmlspecialcharsbx($id)?>" class="bx-image-uploader"></div>
		<?
	}

	public static function UploadCallback($uploadedFiles)
	{

	}

	public static function InitUploaderHandler()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/image_uploader/ImageUploaderPHP/UploadHandler.class.php");
		//include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/image_uploader/ImageUploaderPHP/UploadedFile.class.php");
	}

	private static $id;
	public static function GetId()
	{
		return self::$id;
	}

	public static function SetId($id = "")
	{
		if ($id == "")
			$id = 'BXImageUploader_'.uniqid();
		self::$id = $id;
	}

	public static
		$uploadCallbackFunc,
		$uploadCallbackParams,
		$convCount,
		$sTmpPath,
		$PackageGuid,
		$savedData = false;

	public static function SetTmpPath($PackageGuid, $pathToTmp)
	{
		CheckDirPath($pathToTmp);

		self::$sTmpPath = $pathToTmp.md5(serialize(array("PackageGuid " => $PackageGuid, "sessid" => bitrix_sessid())));
		return self::$sTmpPath;
	}

	public static function CheckDirPath($path = "")
	{
		if ($path)
		{
			if (!CheckDirPath($path))
				echo "<b>Warning! Check file permissions for uploading dir: ".htmlspecialcharsbx($path)."</b><br>";
		}
	}

	public static function UploadFileHandler($callback, $Params)
	{
		self::InitUploaderHandler();

		self::$convCount = $Params['convCount'];
		self::$uploadCallbackFunc = $callback;
		self::$uploadCallbackParams = $Params;
		self::SetTmpPath($_REQUEST["PackageGuid"], $Params["pathToTmp"]);

		$uh = new UploadHandler();
		$uh->setUploadCacheDirectory($Params['pathToTmp']);
		$uh->setAllFilesUploadedCallback(array("CImageUploader", "SaveAllUploadedFiles"));
		$uh->processRequest();
	}

	public static function SaveAllUploadedFiles($uploadedFiles)
	{
		try
		{
			$packageFields = $uploadedFiles[0]->getPackage()->getPackageFields();
			// We call 'onBeforeUpload' only once and if exists handler
			if (isset(self::$uploadCallbackParams['onBeforeUpload']) && self::$PackageGuid != $packageFields['PackageGuid'])
			{
				self::$PackageGuid = $packageFields['PackageGuid'];
				self::$uploadCallbackParams['packageFields'] = $packageFields;
				if (!call_user_func(self::$uploadCallbackParams['onBeforeUpload'], self::$uploadCallbackParams))
					return;
			}
			foreach ($uploadedFiles as $uploadedFile)
			{
				try
				{
					$convertedFiles = $uploadedFile->getConvertedFiles();

					$arFiles = array();
					foreach ($convertedFiles as $j => $convertedFile)
					{
						$path = self::$sTmpPath."_".$j.".tmp";
						$convertedFile->moveTo($path);
						$arFiles[] = array(
							'name' => $convertedFile->getName(),
							'tmp_name' => $path,
							'errors' => 0,
							'type' => self::GetMimeType($convertedFile->getName()),
							'size' => $convertedFile->getSize(),
							'mode' => $convertedFile->getMode(),
							'height' => $convertedFile->getHeight(),
							'width' => $convertedFile->getWidth(),
							'path' => $path
						);
					}

					$name = $packageFields['Title_'.$uploadedFile->getIndex()];
					$fileName = $uploadedFile->getSourceName();
					if ($name == "")
						$name = $fileName;

					$Info = array(
						'name' => $name,
						'filename' => $fileName,
						'description' => $uploadedFile->getDescription(),
						'tags' => $uploadedFile->getTag()
					);
					call_user_func(self::$uploadCallbackFunc, $Info, $arFiles, self::$uploadCallbackParams);
				}
				catch (Exception $e)
				{
					CImageUploader::SaveError(array(array("id" => "BXUPL_APPLET_SAVE_1", "text" => $e->getMessage)));
				}
			}
			if (isset(self::$uploadCallbackParams['onAfterUpload']))
				call_user_func(self::$uploadCallbackParams['onAfterUpload'], self::$uploadCallbackParams);
		}
		catch (Exception $e)
		{
			CImageUploader::SaveError(array(array("id" => "BXUPL_APPLET_SAVE_2", "text" => $e->getMessage)));
		}
	}

	public static function SetSavedData($savedData = array())
	{
		//$savedPath = self::$sTmpPath."_sd.tmp";
		self::$savedData = $savedData;
		$_SESSION['BX_PHOTO_TMP_SAVED_DATA'] = $savedData;
		return true;
	}

	public static function GetSavedData()
	{
		if (is_array(self::$savedData))
			return self::$savedData;

		$savedData = $_SESSION['BX_PHOTO_TMP_SAVED_DATA'];

		if (!is_array($savedData))
			$savedData = array();

		return $savedData;
	}

	public static function CleanSavedData()
	{
		unset($_SESSION['BX_PHOTO_TMP_SAVED_DATA']);
	}

	public static function CheckErrors()
	{
		$arData = self::GetSavedData();
		$arErrors = $arData['arError'];
		if ($arData && is_array($arErrors) && count($arErrors) > 0)
			return $arErrors;
		return false;
	}

	public static function SaveError($arError)
	{
		$savedData = self::GetSavedData();
		if (is_array($savedData['arError']))
			$savedData['arError'] = array_merge($savedData['arError'], $arError);
		else
			$savedData['arError'] = $arError;
		CImageUploader::SetSavedData($savedData);
	}

	public static function GetMimeType($fileName)
	{
		$ext = strtolower(substr($fileName, strrpos($fileName, ".") + 1));

		if ($ext == 'jpeg' || $ext == 'jpg')
			return "image/jpg";
		if ($ext == 'bmp')
			return "image/bmp";
		if ($ext == 'png')
			return "image/png";
		if ($ext == 'gif')
			return "image/gif";
		if ($ext == 'psd')
			return "image/psd";
		if ($ext == 'wbmp')
			return "image/wbmp";

		return "";
	}

	public static function GetLocalization()
	{
		static $arLoc = false;
		if($arLoc === false)
		{
			$arLoc = array(
				'addFilesProgressDialog' => array(
					'cancelButtonText' => GetMessage("BXIU_CANCELBUTTONTEXT"),
					'currentFileText' => GetMessage("BXIU_CURRENTFILETEXT"),
					'titleText' => GetMessage("BXIU_TITLETEXT1"),
					'totalFilesText' => GetMessage("BXIU_TOTALFILESTEXT"),
					'waitText' => GetMessage("BXIU_WAITTEXT"),
				),
				'authenticationDialog' => array(
					'cancelButtonText' => GetMessage("BXIU_CANCELBUTTONTEXT"),
					'loginText' => GetMessage("BXIU_LOGINTEXT"),
					'okButtonText' => GetMessage("BXIU_OKBUTTONTEXT"),
					'passwordText' => GetMessage("BXIU_PASSWORDTEXT"),
					'realmText' => GetMessage("BXIU_REALMTEXT"),
					'text' => GetMessage("BXIU_TEXT"),
				),
				'contextMenu' => array(
					'arrangeByDimensionsText' => GetMessage("BXIU_ARRANGEBYDIMENSIONSTEXT"),
					'arrangeByModifiedText' => GetMessage("BXIU_ARRANGEBYMODIFIEDTEXT"),
					'arrangeByNameText' => GetMessage("BXIU_ARRANGEBYNAMETEXT"),
					'arrangeByPathText' => GetMessage("BXIU_ARRANGEBYPATHTEXT"),
					'arrangeBySizeText' => GetMessage("BXIU_ARRANGEBYSIZETEXT"),
					'arrangeByText' => GetMessage("BXIU_ARRANGEBYTEXT"),
					'arrangeByTypeText' => GetMessage("BXIU_ARRANGEBYTYPETEXT"),
					'checkAllText' => GetMessage("BXIU_CHECKALLTEXT"),
					'checkText' => GetMessage("BXIU_CHECKTEXT"),
					'detailsViewText' => GetMessage("BXIU_DETAILSVIEWTEXT"),
					'editDescriptionText' => GetMessage("BXIU_EDITDESCRIPTIONTEXT"),
					'editText' => GetMessage("BXIU_EDITTEXT"),
					'listViewText' => GetMessage("BXIU_LISTVIEWTEXT"),
					'openText' => GetMessage("BXIU_OPENTEXT"),
					'pasteText' => GetMessage("BXIU_PASTETEXT"),
					'removeAllText' => GetMessage("BXIU_REMOVEALLTEXT"),
					'removeText' => GetMessage("BXIU_REMOVETEXT"),
					'thumbnailsViewText' => GetMessage("BXIU_THUMBNAILSVIEWTEXT"),
					'tilesViewText' => GetMessage("BXIU_TILESVIEWTEXT"),
					'uncheckAllText' => GetMessage("BXIU_UNCHECKALLTEXT"),
					'uncheckText' => GetMessage("BXIU_UNCHECKTEXT"),
				),
				'deleteFilesDialog' => array(
					'message' => GetMessage("BXIU_MESSAGE"),
					'titleText' => GetMessage("BXIU_TITLETEXT2"),
				),
				'descriptionEditor' => array(
					'cancelHyperlinkText' => GetMessage("BXIU_CANCELHYPERLINKTEXT"),
					'orEscLabelText' => GetMessage("BXIU_ORESCLABELTEXT"),
					'saveButtonText' => GetMessage("BXIU_SAVEBUTTONTEXT1"),
				),
				'detailsViewColumns' => array(
					'dimensionsText' => GetMessage("BXIU_DIMENSIONSTEXT"),
					'fileNameText' => GetMessage("BXIU_FILENAMETEXT"),
					'fileSizeText' => GetMessage("BXIU_FILESIZETEXT"),
					'fileTypeText' => GetMessage("BXIU_FILETYPETEXT"),
					'infoText' => GetMessage("BXIU_INFOTEXT1"),
					'lastModifiedText' => GetMessage("BXIU_LASTMODIFIEDTEXT"),
				),
				'folderPane' => array(
					'filterHintText' => GetMessage("BXIU_FILTERHINTTEXT"),
					'headerText' => GetMessage("BXIU_HEADERTEXT"),
				),
				'imageEditor' => array(
					'cancelButtonText' => GetMessage("BXIU_CANCELBUTTONTEXT"),
					'cancelCropButtonText' => GetMessage("BXIU_CANCELCROPBUTTONTEXT"),
					'cropButtonText' => GetMessage("BXIU_CROPBUTTONTEXT"),
					'descriptionHintText' => GetMessage("BXIU_DESCRIPTIONHINTTEXT"),
					'rotateButtonText' => GetMessage("BXIU_ROTATEBUTTONTEXT"),
					'saveButtonText' => GetMessage("BXIU_SAVEBUTTONTEXT2"),
				),
				'messages' => array(
					'cmykImagesNotAllowed' => GetMessage("BXIU_CMYKIMAGESNOTALLOWED"),
					'deletingFilesError' => GetMessage("BXIU_DELETINGFILESERROR"),
					'dimensionsTooLarge' => GetMessage("BXIU_DIMENSIONSTOOLARGE"),
					'dimensionsTooSmall' => GetMessage("BXIU_DIMENSIONSTOOSMALL"),
					'fileNameNotAllowed' => GetMessage("BXIU_FILENAMENOTALLOWED"),
					'fileSizeTooSmall' => GetMessage("BXIU_FILESIZETOOSMALL"),
					'filesNotAdded' => GetMessage("BXIU_FILESNOTADDED"),
					'maxFileCountExceeded' => GetMessage("BXIU_MAXFILECOUNTEXCEEDED"),
					'maxFileSizeExceeded' => GetMessage("BXIU_MAXFILESIZEEXCEEDED"),
					'maxTotalFileSizeExceeded' => GetMessage("BXIU_MAXTOTALFILESIZEEXCEEDED"),
					'noResponseFromServer' => GetMessage("BXIU_NORESPONSEFROMSERVER"),
					'serverNotFound' => GetMessage("BXIU_SERVERNOTFOUND"),
					'unexpectedError' => GetMessage("BXIU_UNEXPECTEDERROR"),
					'uploadCancelled' => GetMessage("BXIU_UPLOADCANCELLED"),
					'uploadCompleted' => GetMessage("BXIU_UPLOADCOMPLETED"),
					'uploadFailed' => GetMessage("BXIU_UPLOADFAILED"),
				),
				'paneItem' => array(
					'descriptionEditorIconTooltip' => GetMessage("BXIU_DESCRIPTIONEDITORICONTOOLTIP"),
					'imageCroppedIconTooltip' => GetMessage("BXIU_IMAGECROPPEDICONTOOLTIP"),
					'imageEditorIconTooltip' => GetMessage("BXIU_IMAGEEDITORICONTOOLTIP"),
					'removalIconTooltip' => GetMessage("BXIU_REMOVALICONTOOLTIP"),
					'rotationIconTooltip' => GetMessage("BXIU_ROTATIONICONTOOLTIP"),
				),
				'statusPane' => array(
					'clearAllHyperlinkText' => GetMessage("BXIU_CLEARALLHYPERLINKTEXT"),
					'filesToUploadText' => GetMessage("BXIU_FILESTOUPLOADTEXT"),
					'noFilesToUploadText' => GetMessage("BXIU_NOFILESTOUPLOADTEXT"),
					'progressBarText' => GetMessage("BXIU_PROGRESSBARTEXT"),
				),
				'treePane' => array(
					'titleText' => GetMessage("BXIU_TITLETEXT3"),
					'unixFileSystemRootText' => GetMessage("BXIU_UNIXFILESYSTEMROOTTEXT"),
					'unixHomeDirectoryText' => GetMessage("BXIU_UNIXHOMEDIRECTORYTEXT"),
				),
				'uploadPane' => array(
					'dropFilesHereText' => GetMessage("BXIU_DROPFILESHERETEXT"),
				),
				'uploadProgressDialog' => array(
					'cancelUploadButtonText' => GetMessage("BXIU_CANCELUPLOADBUTTONTEXT"),
					'estimationText' => GetMessage("BXIU_ESTIMATIONTEXT"),
					'hideButtonText' => GetMessage("BXIU_HIDEBUTTONTEXT"),
					'hoursText' => GetMessage("BXIU_HOURSTEXT"),
					'infoText' => GetMessage("BXIU_INFOTEXT2"),
					'kilobytesText' => GetMessage("BXIU_KILOBYTESTEXT"),
					'megabytesText' => GetMessage("BXIU_MEGABYTESTEXT"),
					'minutesText' => GetMessage("BXIU_MINUTESTEXT"),
					'preparingText' => GetMessage("BXIU_PREPARINGTEXT"),
					'secondsText' => GetMessage("BXIU_SECONDSTEXT"),
					'titleText' => GetMessage("BXIU_TITLETEXT4"),
				),
				'cancelUploadButtonText' => GetMessage("BXIU_CANCELUPLOADBUTTONTEXT"),
				'loadingFolderContentText' => GetMessage("BXIU_LOADINGFOLDERCONTENTTEXT"),
				'uploadButtonText' => GetMessage("BXIU_UPLOADBUTTONTEXT"),
			);
		}
		return CUtil::PhpToJSObject($arLoc);
	}

	public static function GetChunkSize()
	{
		$max_upload_size = min(self::GetSize(ini_get('post_max_size')), self::GetSize(ini_get('upload_max_filesize')));
		$max_upload_size -= 1024 * 200;
		return $max_upload_size;
	}

	private function GetSize($v)
	{
		$l = substr($v, -1);
		$ret = substr($v, 0, -1);
		switch(strtoupper($l))
		{
			case 'P':
				$ret *= 1024;
			case 'T':
				$ret *= 1024;
			case 'G':
				$ret *= 1024;
			case 'M':
				$ret *= 1024;
			case 'K':
				$ret *= 1024;
			break;
		}
		return $ret;
	}

	public static function StrangeUrlEncode($url)
	{
		if (!defined('BX_UTF'))
			$url = CharsetConverter::ConvertCharset($url, SITE_CHARSET, "UTF-8");

		$ind = strpos($url, "?");
		$url = str_replace("%2F", "/", rawurlencode(substr($url, 0, $ind))).substr($url, $ind);
		return $url;
	}
}

class CFlashUploader extends CImageUploader
{
	public static function ShowScript($Params = array())
	{
		global $APPLICATION;
		self::SetId($Params['id']);

		if (!isset($Params['height']))
			$Params['height'] = '500px';

		if (!isset($Params['width']))
			$Params['width'] = '100%';

		if (!isset($Params['fileMask']))
			$Params['fileMask'] = "*.*";

		if (!isset($Params['chunkSize']) || $Params['chunkSize'] <= 0)
			$Params['chunkSize'] = self::GetChunkSize();

		if (!isset($Params['thumbnailJpegQuality']) || $Params['thumbnailJpegQuality'] > 100 || $Params['thumbnailJpegQuality'] <= 0)
			$Params['thumbnailJpegQuality'] = 90;

		// Check and create tmp dir
		self::CheckDirPath($Params["pathToTmp"]);

		$APPLICATION->AddHeadScript('/bitrix/image_uploader/bximageuploader.js');
		$APPLICATION->SetAdditionalCSS('/bitrix/image_uploader/bximageuploader.css');
		$APPLICATION->AddHeadScript('/bitrix/image_uploader/flash/aurigma.imageuploaderflash.js');
		$id = self::GetId();
		?>
		<script>
		var oBXUploaderHandler_<?= CUtil::JSEscape($id)?> = new window.BXUploader({id: '<?= CUtil::JSEscape($id)?>',type: 'flash'});

		window.BXFIU_<?= CUtil::JSEscape($id)?> = $au.imageUploaderFlash({id: '<?= CUtil::JSEscape($id)?>'});
		var bxp = {
			id: '<?= CUtil::JSEscape($id)?>',
			width: '<?= CUtil::JSEscape($Params['width'])?>',
			height: '<?= CUtil::JSEscape($Params['height'])?>',
			converters: [],
			flashControl: {
				codeBase: '/bitrix/image_uploader/flash/aurigma.imageuploaderflash.swf',
				wmode: "opaque"
			},
			uploadSettings: {
				actionUrl: '<?= CUtil::JSEscape(self::StrangeUrlEncode($Params['actionUrl']))?>',
				chunkSize: <?= intval($Params['chunkSize'])?>
			},
			metadata: {},
			restrictions : {fileMask: '<?= CUtil::JSEscape($Params['fileMask'])?>'}
		};

		<? if (isset($Params['appendFormName']) && $Params['appendFormName'] != ''): ?>
			bxp.metadata.additionalFormName = '<?= CUtil::JSEscape($Params['appendFormName'])?>';
		<?endif;?>

		<? if (isset($Params['redirectUrl'])): ?>
			bxp.uploadSettings.redirectUrl = '<?= CUtil::JSEscape($Params['redirectUrl'])?>';
		<?endif;?>

		<? if (isset($Params['minFileCount'])):?>
			bxp.restrictions.minFileCount= '<?= CUtil::JSEscape($Params['minFileCount'])?>';
		<?endif;?>
		<? if (isset($Params['maxFileCount'])):?>
			bxp.restrictions.maxFileCount= '<?= CUtil::JSEscape($Params['maxFileCount'])?>';
		<?endif;?>
		<? if (isset($Params['maxTotalFileSize'])):?>
			bxp.restrictions.maxTotalFileSize = '<?= CUtil::JSEscape($Params['maxTotalFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['maxFileSize'])):?>
			bxp.restrictions.maxFileSize = '<?= CUtil::JSEscape($Params['maxFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['minFileSize'])):?>
			bxp.restrictions.minFileSize = '<?= CUtil::JSEscape($Params['minFileSize'])?>';
		<?endif;?>
		<? if (isset($Params['minImageWidth'])):?>
			bxp.restrictions.minImageWidth = '<?= CUtil::JSEscape($Params['minImageWidth'])?>';
		<?endif;?>
		<? if (isset($Params['minImageHeight'])):?>
			bxp.restrictions.minImageHeight = '<?= CUtil::JSEscape($Params['minImageHeight'])?>';
		<?endif;?>
		<? if (isset($Params['maxImageWidth'])):?>
			bxp.restrictions.maxImageWidth = '<?= CUtil::JSEscape($Params['maxImageWidth'])?>';
		<?endif;?>
		<? if (isset($Params['maxImageHeight'])):?>
			bxp.restrictions.maxImageHeight = '<?= CUtil::JSEscape($Params['maxImageHeight'])?>';
		<?endif;?>

		<?foreach ($Params['converters'] as $converter):?>
			<?$bSource = (!$converter['width'] || !$converter['height']);?>
			bxp.converters.push({
				<?if ($bSource):?>
					mode: '*.*=Thumbnail',
					thumbnailFitMode: "ActualSize", // Fit | OrientationalFit | Width | Height | ActualSize
					thumbnailCopyIptc: true,
					thumbnailCopyExif: true,
					thumbnailJpegQuality: <?= $Params['thumbnailJpegQuality']?>,
				<?else:?>
					mode: '*.*=Thumbnail',
					thumbnailFitMode: "Fit", // Fit | OrientationalFit | Width | Height | ActualSize
					thumbnailCopyIptc: true,
					thumbnailCopyExif: true,

					thumbnailHeight: <?= intval($converter['height'])?>,
					thumbnailWidth: <?= intval($converter['width'])?>,
					thumbnailJpegQuality: <?= $Params['thumbnailJpegQuality']?>
				<?endif;?>
			});
		<?endforeach;?>

		BXFIU_<?= CUtil::JSEscape($id)?>.set(bxp);
		// Apply localization
		BXFIU_<?= CUtil::JSEscape($id)?>.set(<?= self::GetLocalization()?>);

		// $au.debug().level(3);
		// $au.debug().mode(['popup', 'console']);

		BX.ready(function(){BX('bxiu_<?= CUtil::JSEscape($id)?>').innerHTML = BXFIU_<?= CUtil::JSEscape($id)?>.getHtml();});

		</script>
		<div id="bxiu_<?= htmlspecialcharsbx($id)?>" class="bx-image-uploader"></div>
		<?
	}

	public static function InitUploaderHandler()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/image_uploader/ImageUploaderFlashPHP/UploadHandler.class.php");
	}

	public static function UploadFileHandler($callback, $Params)
	{
		self::HealFilesVars();
		self::InitUploaderHandler();
		self::$convCount = $Params['convCount'];
		self::$uploadCallbackFunc = $callback;
		self::$uploadCallbackParams = $Params;
		self::SetTmpPath($_REQUEST["PackageGuid"], $Params["pathToTmp"]);

		$uh = new UploadHandler();
		$uh->setUploadCacheDirectory($Params['pathToTmp']);
		$uh->setAllFilesUploadedCallback(array("CFlashUploader", "SaveAllUploadedFiles"));
		$uh->processRequest();

		// Kill unsecure vars from $_POST
		self::HealFilesVars(true);
	}

	public static function HealFilesVars($bKill = false)
	{
		global $_UNSECURE;
		if (!$bKill)
			UnQuoteArr($_UNSECURE["_POST"], false, true);

		foreach($_POST as $key => $var)
		{
			if (preg_match("/file\d_\d/i", $key) && isset($_UNSECURE['_POST'][$key]))
			{
				if ($bKill)
					$_POST[$key] = null;
				else
					$_POST[$key] = $_UNSECURE['_POST'][$key];
			}
		}
	}

	public static function SaveAllUploadedFiles($uploadedFiles)
	{
		try
		{
			$packageFields = $uploadedFiles[0]->getPackage()->getPackageFields();
			// We call 'onBeforeUpload' only once and if exists handler
			if (isset(self::$uploadCallbackParams['onBeforeUpload']) && self::$PackageGuid != $packageFields['PackageGuid'])
			{
				self::$PackageGuid = $packageFields['PackageGuid'];
				self::$uploadCallbackParams['packageFields'] = $packageFields;
				if (!call_user_func(self::$uploadCallbackParams['onBeforeUpload'], self::$uploadCallbackParams))
					return;
			}
			foreach ($uploadedFiles as $uploadedFile)
			{
				try
				{
					$convertedFiles = $uploadedFile->getConvertedFiles();
					if (count($convertedFiles) <= 0)
					{
						CImageUploader::SaveError(array(array("id" => "BXUPL_FLASH_TYPE_1", "text" => GetMessage('P_BXUPL_FLASH_TYPE_1'))));
						continue;
					}

					$arFiles = array();
					foreach ($convertedFiles as $j => $convertedFile)
					{
						$path = self::$sTmpPath."_".$j.".tmp";
						$convertedFile->moveTo($path);
						$arFiles[] = array(
							'name' => $convertedFile->getName(),
							'tmp_name' => $path,
							'errors' => 0,
							'type' => self::GetMimeType($convertedFile->getName()),
							'size' => $convertedFile->getSize(),
							'mode' => $convertedFile->getMode(),
							'height' => $convertedFile->getHeight(),
							'width' => $convertedFile->getWidth(),
							'path' => $path
						);
					}
					$name = $packageFields['Title_'.$uploadedFile->getIndex()];
					$fileName = $uploadedFile->getSourceName();
					if ($name == "")
						$name = $fileName;
					$Info = array(
						'name' => $name,
						'filename' => $fileName,
						'description' => $uploadedFile->getDescription(),
						'tags' => $uploadedFile->getTag()
					);
					call_user_func(self::$uploadCallbackFunc, $Info, $arFiles, self::$uploadCallbackParams);
				}
				catch (Exception $e)
				{
					CImageUploader::SaveError(array(array("id" => "BXUPL_FLASH_SAVE_1", "text" => $e->getMessage)));
				}
			}
			if (isset(self::$uploadCallbackParams['onAfterUpload']))
				call_user_func(self::$uploadCallbackParams['onAfterUpload'], self::$uploadCallbackParams);
		}
		catch (Exception $e)
		{
			CImageUploader::SaveError(array(array("id" => "BXUPL_FLASH_SAVE_2", "text" => $e->getMessage)));
		}
	}

	public static function GetLocalization()
	{
		static $arLoc = false;
		if($arLoc === false)
		{
			$arLoc = array(
				'addFilesProgressDialog' => array(
					'text' => GetMessage("BXFIU_text")
				),
				'commonDialog' => array(
					'cancelButtonText' => GetMessage("BXFIU_cancelButtonText"),
					'okButtonText' => GetMessage("BXFIU_okButtonText")
				),
				'descriptionEditor' => array(
					'cancelButtonText' => GetMessage("BXFIU_cancelButtonText"),
					'saveButtonText' => GetMessage("BXFIU_saveButtonText")
				),
				'imagePreviewWindow' => array(
					'closePreviewTooltip' => GetMessage("BXFIU_closePreviewTooltip")
				),
				'messages' => array(
					'cannotReadFile' => GetMessage("BXFIU_cannotReadFile"),
					'dimensionsTooLarge' => GetMessage("BXFIU_dimensionsTooLarge"),
					'dimensionsTooSmall' => GetMessage("BXFIU_dimensionsTooSmall"),
					'fileSizeTooSmall' => GetMessage("BXFIU_fileSizeTooSmall"),
					'filesNotAdded' => GetMessage("BXFIU_filesNotAdded"),
					'maxFileCountExceeded' => GetMessage("BXFIU_maxFileCountExceeded"),
					'maxFileSizeExceeded' => GetMessage("BXFIU_maxFileSizeExceeded"),
					'maxTotalFileSizeExceeded' => GetMessage("BXFIU_maxTotalFileSizeExceeded"),
					'previewNotAvailable' => GetMessage("BXFIU_previewNotAvailable"),
					'tooFewFiles' => GetMessage("BXFIU_tooFewFiles")
				),
				'paneItem' => array(
					'descriptionEditorIconTooltip' => GetMessage("BXFIU_descriptionEditorIconTooltip"),
					'imageTooltip' => GetMessage("BXFIU_imageTooltip"),
					'itemTooltip' => GetMessage("BXFIU_itemTooltip"),
					'removalIconTooltip' => GetMessage("BXFIU_removalIconTooltip"),
					'rotationIconTooltip' => GetMessage("BXFIU_rotationIconTooltip")
				),
				'statusPane' => array(
					'dataUploadedText' => GetMessage("BXFIU_dataUploadedText"),
					'filesPreparedText' => GetMessage("BXFIU_filesPreparedText"),
					'filesToUploadText' => GetMessage("BXFIU_filesToUploadText"),
					'filesUploadedText' => GetMessage("BXFIU_filesUploadedText"),
					'noFilesToUploadText' => GetMessage("BXFIU_noFilesToUploadText"),
					'preparingText' => GetMessage("BXFIU_preparingText"),
					'sendingText' => GetMessage("BXFIU_sendingText")
				),
				'topPane' => array(
					'addFilesHyperlinkText' => GetMessage("BXFIU_addFilesHyperlinkText"),
					'clearAllHyperlinkText' => GetMessage("BXFIU_clearAllHyperlinkText"),
					'orText' => GetMessage("BXFIU_orText"),
					'titleText' => GetMessage("BXFIU_titleText"),
					'viewComboBox' => array(GetMessage("BXFIU_viewComboBox1"), GetMessage("BXFIU_viewComboBox2"), GetMessage("BXFIU_viewComboBox3")),
					'viewComboBoxText' => GetMessage("BXFIU_viewComboBoxText")
				),
				'uploadErrorDialog' => array(
					'hideDetailsButtonText' => GetMessage("BXFIU_hideDetailsButtonText"),
					'message' => GetMessage("BXFIU_message"),
					'showDetailsButtonText' => GetMessage("BXFIU_showDetailsButtonText"),
					'title' => GetMessage("BXFIU_title")
				),
				'uploadPane' => array(
					'addFilesButtonText' => GetMessage("BXFIU_addFilesButtonText")
				),
				'cancelUploadButtonText' => GetMessage("BXFIU_cancelUploadButtonText"),
				'uploadButtonText' => GetMessage("BXFIU_uploadButtonText")
			);
		}
		return CUtil::PhpToJSObject($arLoc);
	}
}
use \Bitrix\Main\UI\FileInputUtility;
class CFileUploader
{
	public $files = array();
	public $error = "";
	public $status = "ready";
	public $controlId = "fileUploader";
	public $params = array(
		"allowUpload" => "A",
/*		"allowUploadExt" => "",
		"copies" => array(
			"copyName" => array(
				"width" => 100,
				"height" => 100
			)
		)*/
	);

	private $path = "";
	private $logData = array();
	private $CID = "";
	private $mode = "view";
	private $package = array();
	private $errorCode;

	const FILE_NAME = "bxu_files";
	const INFO_NAME = "bxu_info";
	const EVENT_NAME = "main_bxu";
	const SESSION_LIST = "MFI_SESSIONS";
	const SESSION_TTL = 86400;

	private $script;

	/**
	 * @param $script - url for detail
	 */
	public function __construct($params = array(), $doCheckPost = true)
	{
		global $APPLICATION;
		$this->errorCode = array(
			"BXU344" => GetMessage("UP_CID_IS_REQUIRED"),
			"BXU344.1" => GetMessage("UP_PACKAGE_INDEX_IS_REQUIRED"),
			"BXU345" => GetMessage("UP_BAD_SESSID"),
			"BXU346" => GetMessage("UP_NOT_ENOUGH_PERMISSION"),
			"BXU347" => GetMessage("UP_FILE_IS_LOST"),
			"BXU348" => GetMessage("UP_FILE_IS_NOT_UPLOADED"));
		$params = (is_array($params) ? $params : array());
		$this->script = (array_key_exists("urlToUpload", $params) ? $params["urlToUpload"] : $APPLICATION->GetCurPageParam());
		if (array_key_exists("copies", $params) && is_array($params["copies"]))
		{
			$copies = array();
			foreach($params["copies"] as $key => $val)
			{
				if (is_array($val) && (array_key_exists("width", $val) || array_key_exists("height", $val)))
					$copies[$key] = array("width" => $val["width"], "height" => $val["height"]);
			}
			if (!empty($copies))
				$this->params["copies"] = $copies;
		}
		if (array_key_exists("uploadFileWidth", $params))
			$this->params["uploadFileWidth"] = $params["uploadFileWidth"];
		if (array_key_exists("uploadFileHeight", $params))
			$this->params["uploadFileHeight"] = $params["uploadFileHeight"];
		if (array_key_exists("uploadMaxFilesize", $params))
			$this->params["uploadMaxFilesize"] = $params["uploadMaxFilesize"];

		if (array_key_exists("events", $params) && is_array($params["events"]))
		{
			foreach($params["events"] as $key => $val)
			{
				AddEventHandler(self::EVENT_NAME, $key, $val);
			}
		}
		if (array_key_exists("allowUpload", $params))
		{
			// ALLOW_UPLOAD = 'A'll files | 'I'mages | 'F'iles with selected extensions
			// ALLOW_UPLOAD_EXT = comma-separated list of allowed file extensions (ALLOW_UPLOAD='F')
			$this->params["allowUpload"] = (in_array($params["allowUpload"], array("A", "I", "F")) ? $params["allowUpload"] : "A");
			if ($params["allowUpload"] == "F" && empty($params["allowUploadExt"]))
				$this->params["allowUpload"] = "A";
			$this->params["allowUploadExt"] = $params["allowUploadExt"];
		}
		if (array_key_exists("controlId", $params))
			$this->controlId = $params["controlId"];
		$this->params["controlId"] = $this->controlId;
		$this->path = CTempFile::GetDirectoryName(
			12,
			array(
				"bxu",
				md5(serialize(array(
					$this->controlId,
					bitrix_sessid(),
					CMain::GetServerUniqID()
					))
				)
			)
		);
		if ($doCheckPost !== false && !$this->checkPost(($doCheckPost === true || $doCheckPost == "post")))
		{
			$this->showError();
		}
		return $this;
	}
	public function showError($text = NULL)
	{
		$text = ($text === NULL ? $this->error : $text);
		$patt = array_keys($this->errorCode);
		$repl = array_values($this->errorCode);
		echo str_replace($patt, $repl, $text);
	}


	/**
	 * Copies file from really tmp dir to repo
	 * @param $file
	 * @param $canvas
	 * @param $res
	 * @return string
	 */
	private function copyFile($file, $canvas, &$res)
	{
		$hash = $this->getHash($file);
		$io = CBXVirtualIo::GetInstance();
		$directory = $io->GetDirectory($this->path.$hash);
		$path = $this->path.$hash."/".$canvas;
		$error = "";
		if (!$directory->Create())
			$error = "BXU001";
		elseif ($res["error"] > 0 || !array_key_exists('chunks', $res) && !file_exists($res['tmp_name']))
		{
			$error = "BXU347";
			if ($canvas != "default" && !empty($file["files"]["default"]) &&
				$res["width"] <= $file["files"]["default"]["width"] && $res["height"] <= $file["files"]["default"]["height"])
			{
				@copy($file["files"]["default"]["tmp_path"], $path);
				if (is_file($path))
				{
					@chmod($path, BX_FILE_PERMISSIONS);
					$res["tmp_name"] = $path;
				}
				else
				{
					$error = "BXU348";
					$this->log($file["id"], $res["~name"], array("status" => "error", "note" => $error));
				}
			}
			else
			{
				$error = "BXU347";
				$this->log($file["id"], $res["~name"], array("status" => "error", "note" => $error));
			}
		}
		elseif (!empty($res['chunks']))
		{
			if ($res["packages"] <= count($res["chunks"])) // TODO glue pieces
			{
				ksort($res["chunks"]);
				$buff = 4096;
				$fdst = fopen($path, 'a');
				foreach($res["chunks"] as $chunk)
				{
					$fsrc = fopen($chunk['tmp_name'], 'r');
					while(!feof($fsrc) && ($data = fread($fsrc, $buff)) !== '')
					{
						fwrite($fdst, $data);
					}
					fclose($fsrc);
					$this->log($file["id"], $chunk["~name"], array("status" => "uploaded"));
					unlink($chunk['tmp_name']);
				}
				fclose($fdst);
				@chmod($path, BX_FILE_PERMISSIONS);
				unset($res["chunks"]);
				$res["tmp_name"] = $path;
				$res["type"] = (array_key_exists("type", $file) ? $file["type"] : CFile::GetContentType($path));
				$res["size"] = filesize($path);
			}
			else
			{
				foreach($res['chunks'] as $package => $chunk)
				{
					$tmp_name = $path.".".$package;
					if (file_exists($tmp_name) && filesize($tmp_name) == filesize($chunk["tmp_name"]) || move_uploaded_file($chunk["tmp_name"], $tmp_name))
					{
						$res['chunks'][$package]["tmp_name"] = $tmp_name;
						$this->log($file["id"], $chunk["~name"], array("status" => "uploaded"));
					}
					else
					{
						$error = "BXU348";
						$this->log($file["id"], $chunk["~name"], array("status" => "error", "note" => $error));
					}
				}
			}
		}
		elseif (file_exists($res['tmp_name']) && file_exists($path) && filesize($res['tmp_name']) == filesize($path) ||
			move_uploaded_file($res['tmp_name'], $path))
		{
			$res["tmp_name"] = $path;
			$res["size"] = filesize($path);
			if (empty($res["type"]))
				$res["type"] = (array_key_exists("type", $file) ? $file["type"] : CFile::GetContentType($path));
			$this->log($file["id"], $res["~name"], array("status" => "uploaded"));
		}
		else
		{
			$error = "BXU348";
			$this->log($file["id"], $res["~name"], array("status" => "error", "note" => $error));
		}
		$res["name"] = $file["name"];

		if ($canvas != "default" && array_key_exists("type", $file))
			$res["type"] = $file["type"];
		return $error;
	}

	/**
	 * @param array $file
	 * @return string
	 */
	public function getHash($file = array())
	{
		return $this->controlId.md5($file["id"]);
	}

	/**
	 * this function just merge 2 arrays with a lot of deep keys
	 * array_merge replaces keys in second level and deeper
	 * array_merge_recursive multiplies similar keys
	 * @param $res
	 * @param $res2
	 * @return array
	 */
	static function merge($res, $res2)
	{
		$res = is_array($res) ? $res : array();
		$res2 = is_array($res2) ? $res2 : array();
		foreach ($res2 as $key => $val)
		{
			if (array_key_exists($key, $res) && is_array($val))
				$res[$key] = self::merge($res[$key], $val);
			else
				$res[$key] = $val;
		}
		return $res;
	}
	/**
	 * Decodes and converts keys(!) and values
	 * @param $data
	 * @return array
	 */
	private static function __UnEscape($data)
	{
		global $APPLICATION;

		if(is_array($data))
		{
			$res = array();
			foreach($data as $k => $v)
			{
				$k = $APPLICATION->ConvertCharset(CHTTP::urnDecode($k), "UTF-8", LANG_CHARSET);
				$res[$k] = self::__UnEscape($v);
			}
		}
		else
		{
			$res = $APPLICATION->ConvertCharset(CHTTP::urnDecode($data), "UTF-8", LANG_CHARSET);
		}

		return $res;
	}
	/**
	 * Generates hash from info about file
	 * @param $packages
	 * @param $package
	 * @return string
	 */
	private static function getChunkKey($packages, $package)
	{
		return "p".str_pad($package, 4, "0", STR_PAD_LEFT);
	}
	/**
	 * excludes real paths from array
	 * @param $item - array
	 * @return array
	 */
	private static function removeTmpPath($item)
	{
		if (is_array($item))
		{
			if (array_key_exists("tmp_name", $item))
			{
				unset($item["tmp_name"]);
			}
			foreach ($item as $key => $val)
			{
				if (is_array($val))
				{
					$item[$key] = self::removeTmpPath($val);
				}
			}
		}
		return $item;
	}

	/**
	 * Returns all saved data for this file hash
	 * @param $hash
	 * @param bool $copies
	 * @param bool $watermark
	 * @return array
	 */
	public function getFile($hash, $copies = false, $watermark = false)
	{
		if ($copies === false && array_key_exists("copies", $this->params))
		{
			$copies = $this->params["copies"];
			$default = array();
			if (array_key_exists("uploadFileWidth", $this->params))
				$default["width"] = $this->params["uploadFileWidth"];
			if (array_key_exists("uploadFileHeight", $this->params))
				$default["height"] = $this->params["uploadFileHeight"];
			if (!empty($default))
				$copies["default"] = $default;
		}
		$files = array();
		$hashes = FileInputUtility::instance()->checkFiles($this->controlId, (is_array($hash) ? $hash : array($hash)));
		if (!empty($hashes))
		{
			foreach ($hashes as $h)
			{
				$file = $this->getFromCache($h);
				if (!!$file && (!empty($copies) || !empty($watermark)))
				{
					$this->checkCanvases($hash, $file, $copies, $watermark);
				}
				$files[$h] = $file;
			}
		}
		return (is_array($hash) ? $files : $files[$hash]);
	}

	private function getFromCache($hash, $data = array())
	{
		$file = CBXVirtualIo::GetInstance()->GetFile($this->path.$hash."/.log");
		$text = $file->GetContents();
		$res = self::merge(unserialize($file->GetContents()), $data);
		return $res;
	}
	public function setIntoCache($hash, $data)
	{
		$io = CBXVirtualIo::GetInstance();
		$directory = $io->GetDirectory($this->path.$hash);
		if ($directory->Create())
		{
			$file = $io->GetFile($this->path.$hash."/.log");
			$file->PutContents(serialize($data));
		}
	}

	/**
	 * @param string $hash
	 * @param string $act
	 * @return string
	 */
	public function getUrl($hash, $act = "view")
	{
		return CHTTP::URN2URI($this->script.(strpos($this->script, "?") === false ? "?" : "&").
			CHTTP::PrepareData(
				array(
					self::INFO_NAME => array(
						"CID" => $this->CID,
						"mode" => $act,
						"hash" => $hash
					)
				)
			)
		);
	}

	public function saveFile($file)
	{
		$hash = $this->getHash($file);
		$error = "";
		if (FileInputUtility::instance()->checkFile($this->CID, $hash))
			$file = $this->getFromCache($hash, $file);
		$status = "inprogress";
		if (!empty($file["files"]))
		{
			$canvases = array_merge((array_key_exists("canvases", $file) ? $file["canvases"] : array()), array("default" => "nothing"));
			foreach ($file["files"] as $canvas => $res)
			{
				$error = $this->copyFile($file, $canvas, $res);
				if (strlen($error) <= 0)
				{
					$res["url"] = $this->getUrl($hash."_".$canvas);
					$file["files"][$canvas] = $res;
					if (!empty($res["tmp_name"]))
					{
						$res["sizeFormatted"] = CFile::FormatSize($res['size']);
						unset($canvases[$canvas]);
					}
				}
			}

			$status = (!empty($canvases) ? "inprogress" : "uploaded");
			if ($status == "uploaded" && $this->params["allowUpload"] == "I" && is_array($file["copies"]))
			{
				foreach($file["copies"] as $res)
				{
					$error = $this->checkFile($res, $file);
					if (strlen($error) > 0)
					{
						break;
					}
				}

				if (strlen($error) > 0)
					$status = "error";
			}
			if ($status == "uploaded")
			{

				if ($this->getPost("type") != "brief")
				{
					foreach(GetModuleEvents(self::EVENT_NAME, "onFileIsUploaded", true) as $arEvent)
					{
						if (!ExecuteModuleEventEx($arEvent, array($hash, &$file, &$this->package["data"], &$this->uploading["data"], &$error)))
							$status = "error";
					}
				}
			}
			if (strlen($error) <= 0)
			{
				$this->setIntoCache($hash, $file);
				FileInputUtility::instance()->registerFile($this->CID, $hash);
			}
		}
		else
		{
			$error = "Empty data.";
			$status = "error";
		}
		return array("error" => $error, "hash" => $hash, "file" => $file, "status" => $status);
	}

	/**
	 * Checks file params
	 * @param $file
	 * @param $arFile
	 * @return mixed|null|string
	 */
	private function checkFile($file, &$arFile)
	{

		$error = "";

		if ($file["error"] > 0)
			$error = "BXU348: " . $file["error"];
		else if (!is_uploaded_file($file['tmp_name']))
			$error = "BXU348";
		else if (!file_exists($file['tmp_name']))
			$error = "BXU347";
		elseif ($this->params["allowUpload"] == "F")
			$error = CFile::CheckFile($file, $this->params["uploadMaxFilesize"], false, $this->params["allowUploadExt"]);
		else
			$error = CFile::CheckFile($file, $this->params["uploadMaxFilesize"]);

		if (strlen($error) <= 0)
		{
			$key = (preg_match("/\\\\(.+?)\\\\/", $file["~name"], $matches) ? $matches[1] : "default");
			$res = (array_key_exists($key, $arFile["files"]) ? $arFile["files"][$key] : array("copy" => $key));
			if (preg_match("/\/(\d+)\/(\d+)\/$/", $file["~name"], $matches))
			{
				$file["package"] = $matches[2];
				$file["packages"] = $matches[1];
				$res["packages"] = $matches[1];
				$res["chunks"] = (is_array($res["chunks"]) ? $res["chunks"] : array());
				$res["chunks"][self::getChunkKey($file["packages"], $file["package"])] = $file;
				$arFile["files"][$key] = $res;
			}
			else
			{
				if ($this->params["allowUpload"] == "I")
					$error = CFile::CheckImageFile($file, $this->params["uploadMaxFilesize"], 0, 0);
				if (strlen($error) <= 0)
				{
					$res = array_merge($res, $file);
					$arFile["files"][$key] = $res;
				}
			}
		}
		if (strlen($error) > 0)
		{
			$arFile["error"] = $error;
		}

		return $error;
	}

	/**
	 * Main function for uploading data
	 */
	public function uploadData()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$post = array_merge($request->getQueryList()->toArray(), $request->getPostList()->toArray());
		$error = "";
		$post = self::__UnEscape($post);
		$files = self::__UnEscape($_FILES);

		if ($this->getPost("type") != "brief")
		{
			foreach(GetModuleEvents(
				self::EVENT_NAME,
				($this->uploading["handler"]->IsExists() ?
					"onUploadIsContinued" : "onUploadIsStarted"),
				true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$this->package["data"], &$this->uploading["data"], &$post, &$files, &$error)) === false)
				{
					die($error);
				}
			}
			foreach(GetModuleEvents(
				self::EVENT_NAME,
				($this->package["handler"]->IsExists() ?
					"onPackageIsContinued" : "onPackageIsStarted"),
				true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$this->package["data"], &$this->uploading["data"], &$post, &$files, &$error)) === false)
				{
					die($error);
				}
			}
		}

		$data = array();
		if (!empty($post[self::FILE_NAME]))
		{
			foreach($post[self::FILE_NAME] as $fileID => $props)
			{
				$data[$fileID] = array_merge($props, array(
					"id" => $fileID,
					"files" => array()));
			}
		}
		if (!empty($files[self::FILE_NAME]) && !empty($files[self::FILE_NAME]["name"]))
		{
			foreach($files[self::FILE_NAME]["name"] as $fileID => $fileNames)
			{
				$arFile = $this->getFromCache($this->getHash(array("id" => $fileID, "name" => $fileNames)));
				$arFile = self::merge(
					(array_key_exists($fileID, $data) ? $data[$fileID] : array( "id" => $fileID, "files" => array())),
					(is_array($arFile) ? $arFile : array())
				);
				if (is_array($fileNames))
				{
					foreach ($fileNames as $fileName => $val)
					{
						$file = array(
							"name" => $arFile["name"],
							"~name" => $fileName,
							"tmp_name" => $files[self::FILE_NAME]["tmp_name"][$fileID][$fileName],
							"type" => $files[self::FILE_NAME]["type"][$fileID][$fileName],
							"size" => $files[self::FILE_NAME]["size"][$fileID][$fileName],
							"error" => $files[self::FILE_NAME]["error"][$fileID][$fileName],
							"packageID" => $this->package["id"]
						);
						if ($file["type"] == "application/octet-stream" && array_key_exists("type", $arFile))
							$file["type"] = $arFile["type"];
						$error = $this->checkFile($file, $arFile);
						$this->log($fileID, $fileName, (!empty($error) ? array("status" => "error", "error" => $error) : array("status" => "prepared")));
					}
				}
				else
				{
					$fileName = $fileNames;
					$file = array(
						"name" => $arFile["name"],
						"~name" => $fileName,
						"tmp_name" => $files[self::FILE_NAME]["tmp_name"][$fileID],
						"type" => $files[self::FILE_NAME]["type"][$fileID],
						"size" => $files[self::FILE_NAME]["size"][$fileID],
						"error" => $files[self::FILE_NAME]["error"][$fileID],
						"packageID" => $this->package["id"]
					);
					$error = $this->checkFile($file, $arFile);
					$this->log($fileID, $fileName, (!empty($error) ? array("status" => "error", "error" => $error) : array("status" => "prepared")));
				}
				$data[$fileID] = $arFile;
			}
		}
		foreach ($data as $fileID => $arFile)
		{
			$res = (!array_key_exists("error", $arFile) ? $this->saveFile($arFile) : array("status" => "error", "error" => $arFile["error"]));
			$this->files[$fileID] = $res;
			if ($res["status"] == "uploaded")
				$this->package["data"]["files"][] = $res["hash"];
			else if ($res["status"] == "error")
				$this->package["data"]["files"][] = "error";
		}

		if ($this->package["data"]["filesCount"] > 0 && $this->package["data"]["filesCount"] == count($this->package["data"]["files"]))
		{
			if ($this->package["handler"]->IsExists())
				$this->package["handler"]->unlink();

			$this->status = "done";
			if ($this->getPost("type") != "brief")
			{
				foreach(GetModuleEvents(self::EVENT_NAME, "onPackageIsFinished", true) as $arEvent)
				{
					if (ExecuteModuleEventEx($arEvent, array($this->package["data"], $this->uploading["data"], $post, $this->files)) === false)
						return false;
				}
			}
		}
		else
		{
			$this->status = "inprogress";
			$this->package["handler"]->PutContents(serialize($this->package["data"]));
			$this->uploading["handler"]->PutContents(serialize($this->uploading["data"]));
		}
		$this->log("package", $this->package["id"], $this->package["data"]);
		$this->log("uploading", $this->CID, $this->uploading["data"]);
	}

	public function deleteFile($hash)
	{
		if (FileInputUtility::instance()->unRegisterFile($this->CID, $hash))
		{
			$io = CBXVirtualIo::GetInstance();
			$directory = $io->GetDirectory($this->path.$hash);
			$res = $directory->GetChildren();
			foreach($res as $file)
				$file->unlink();
			$directory->rmdir();
			return true;
		}
		return false;
	}
	public function viewFile($hash)
	{
		$file = false;
		$copy = "";
		if (strpos($hash, "_") > 0)
		{
			$copy = explode("_", $hash);
			$hash = $copy[0]; $copy = $copy[1];
		}
		$copy = (!!$copy ? $copy : "default");
		if (FileInputUtility::instance()->checkFile($this->CID, $hash))
		{
			$file = $this->getFromCache($hash);
			$file = $file["files"][$copy];
		}
		if ($file)
			CFile::ViewByUser($file, array("content_type" => $file["type"]));
	}

	static public function getData($data)
	{
		array_walk_recursive($data, create_function('&$v,$k',
					'if($k=="error"){$v=preg_replace("/<(.+?)>/is".BX_UTF_PCRE_MODIFIER, "", $v);}'));
		return self::removeTmpPath($data);
	}
	private function log($fileId, $fileName, $data)
	{
		if (!array_key_exists($fileId, $this->logData))
			$this->logData[$fileId] = array();
		$this->logData[$fileId][$fileName] = $data;
	}
	public function getLog()
	{
		array_walk_recursive($this->logData, create_function('&$v,$k',
			'if($k=="error"){$v=preg_replace("/<(.+?)>/is".BX_UTF_PCRE_MODIFIER, "", $v);}'));
		return $this->logData;
	}
	private function fillRequireData($requestType)
	{
		$this->mode = $this->getPost("mode", $requestType);
		$this->CID = FileInputUtility::instance()->registerControl($this->getPost("CID", $requestType), $this->controlId);

		if (in_array($this->mode, array("upload", "delete", "view")))
		{
			if ($this->mode != "view" && !check_bitrix_sessid())
				$this->error = "BXU345";
			else if (!CheckDirPath($this->path))
				$this->error .= "BXU346";
			else if ($this->getPost("packageIndex", $requestType))
			{
				$this->package = array(
					"handler" => CBXVirtualIo::GetInstance()->GetFile($this->path.$this->getPost("packageIndex").".package"),
					"id" => $this->getPost("packageIndex"),
					"data" => array("filesCount" => intval($this->getPost("filesCount")), "files" => array())
				);
				if ($this->package["handler"]->IsExists())
					$this->package["data"] = unserialize($this->package["handler"]->GetContents());
			}
			else if ($this->mode == "upload")
				$this->error = "BXU344.1";

			$this->uploading = array(
				"handler" => CBXVirtualIo::GetInstance()->GetFile($this->path.$this->CID.".log"),
				"data" => array());
			if ($this->uploading["handler"]->IsExists())
				$this->uploading["data"] = unserialize($this->uploading["handler"]->GetContents());

			return true;
		}
		return false;
	}

	private function getPost($key = "", $checkPost = true)
	{
		static $request = false;
		if ($key === true || $key === false)
		{
			$checkPost = $key;
			$key = "";
		}

		$checkPost = ($checkPost === true ? "postAndGet" : "onlyGet");
		if ($request === false)
		{
			$req = \Bitrix\Main\Context::getCurrent()->getRequest();
			$request = array(
				"onlyGet" => $req->getQueryList()->toArray(),
				"postAndGet" => array_merge($req->getQueryList()->toArray(), $req->getPostList()->toArray())
			);
		}
		$post = $request[$checkPost];
		if ($key == "")
			return array_key_exists(self::INFO_NAME, $post) ? $post[self::INFO_NAME] : false;
		else if (array_key_exists(self::INFO_NAME, $post) && array_key_exists($key, $post[self::INFO_NAME]))
			return $post[self::INFO_NAME][$key];
		return false;
	}

	/**
	 * @return bool
	 */
	public function checkPost($checkPost = true)
	{
		if (!$this->getPost("", $checkPost) || !$this->fillRequireData($checkPost))
		{
			return false;
		}

		if (!defined("PUBLIC_AJAX_MODE"))
			;// define("PUBLIC_AJAX_MODE", true);
		if (!defined("NO_KEEP_STATISTIC"))
			;// define("NO_KEEP_STATISTIC", "Y");
		if (!defined("NO_AGENT_STATISTIC"))
			;// define("NO_AGENT_STATISTIC", "Y");
		if (!defined("NO_AGENT_CHECK"))
			;// define("NO_AGENT_CHECK", true);
		if (!defined("DisableEventsCheck"))
			;// define("DisableEventsCheck", true);
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
		$GLOBALS["APPLICATION"]->RestartBuffer();
		if (strlen($this->error) > 0)
		{
			header('Content-Type: text/html; charset='.LANG_CHARSET);
			?><?=$this->showError();?><?
			die();
		}

		header('Content-Type: text/html; charset='.LANG_CHARSET); // because of IE11

		if ($this->mode == "upload")
		{
			$this->uploadData();
			$result = array(
				"status" => $this->status,
				"report" => $this->getLog(),
				"files" => $this->getData($this->files)
			);
			?><?=CUtil::PhpToJSObject($result);?><?
		}
		else if ($this->mode == "delete")
		{
			$result = array("result" => $this->deleteFile($this->getPost("hash")));
			?><?=CUtil::PhpToJSObject($result);?><?
		}
		else
			$this->viewFile($this->getPost("hash"));
		die();
	}
	private static function createCanvas($source, $dest, $canvasParams = array(), $watermarkParams = array())
	{
		$watermark = (array_key_exists("watermark", $source) ? array() : $watermarkParams);
		if (CFile::ResizeImageFile(
			$source["tmp_name"],
			$dest["tmp_name"],
			$canvasParams,
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$watermark,
			$canvasParams["quality"],
			array()
		))
		{
			$dest = array_merge($source, $dest);
			if (array_key_exists("watermark", $source) || !empty($watermarkParams))
				$dest["watermark"] = true;
		}
		else
			$dest["error"] = 348;
		return $dest;

	}
	public function checkCanvases($hash, &$file, $canvases = array(), $watermark = array())
	{
		if (!empty($watermark))
		{
			$file["files"]["default"] = self::createCanvas(
				$file["files"]["default"],
				$file["files"]["default"],
				array(),
				$watermark
			);
		}
		if (is_array($canvases))
		{
			foreach ($canvases as $canvas => $canvasParams)
			{
				if (!array_key_exists($canvas, $file["files"]))
				{
					$sourceKey = "default"; $source = $file["files"][$sourceKey]; // TODO pick up more appropriate copy by params
					$res = array(
						"copy" => $canvas,
						"tmp_name" => $this->path.$hash."/".$canvas,
						"url" => $this->getUrl($hash."_".$canvas)
					);
					$file["files"][$canvas] = $res + self::createCanvas($source, $res, $canvasParams, $watermark);

				}
			}
		}
		return $file;
	}
}
?>