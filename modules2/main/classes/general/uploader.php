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
?>