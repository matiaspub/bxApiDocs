<?php

namespace Bitrix\Main\UI;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FileInput
{
	protected $elementSetts = array(
		"name" => "FILE[n#IND#]",
		"description" => true,
		"delete" => true,
		"edit" => true,
		"thumbSize" => 640
	);
	protected $uploadSetts = array(
		"upload" => false,
		"uploadType" => "path",
		"medialib" => false,
		"fileDialog" => false,
		"cloud" => false,
		"maxCount" => 0,
		"maxSize" => 0
	);
	protected $id = "bx_iblockfileprop";
	protected $files = array();
	protected static $instance = null;
	protected $templates = array();

	public static $templatePatterns = array(
		'description' => <<<HTML
		<input type="text" id="#id#Description" name="#description_name#" value="#description#" class="adm-fileinput-item-description" />
HTML
		,
		'new' => <<<HTML
	<div class="adm-fileinput-item">
		<div class="adm-fileinput-item-preview">
			<span class="adm-fileinput-item-loading">
				<span class="container-loading-title">#MESS_LOADING#</span>
				<span class="container-loading-bg"><span class="container-loading-bg-progress" style="width: 5%;" id="#id#Progress"></span></span>
			</span>
			<div class="adm-fileinput-item-preview-icon">
				<div class="bx-file-icon-container-medium icon-#ext#">
					<div class="bx-file-icon-cover">
						<div class="bx-file-icon-corner">
							<div class="bx-file-icon-corner-fix"></div>
						</div>
						<div class="bx-file-icon-images"></div>
					</div>
					<div class="bx-file-icon-label"></div>
				</div>
				<span class="container-doc-title" id="#id#Name">#name#</span>
			</div>
			<div class="adm-fileinput-item-preview-img">#preview#</div>
			<input class="bx-bxu-fileinput-value" type="hidden" id="#id#Value" name="#input_name#" value="#input_value#" />
		</div>
		#description#
		<div class="adm-fileinput-item-panel">
			<span class="adm-fileinput-item-panel-btn adm-btn-setting" id="#id#Edit">&nbsp;</span>
			<span class="adm-fileinput-item-panel-btn adm-btn-del" id="#id#Del">&nbsp;</span>
		</div>
		<div id="#id#Properties" class="adm-fileinput-item-properties">#properties#</div>
	</div>
HTML
		,
		/**
		 * adm-fileinput-item-saved - saved
		 * adm-fileinput-item-error - error
		 * adm-fileinput-item-image - file is image
		 *
		 */
		'uploaded' => <<<HTML
<div class="adm-fileinput-item-wrapper" id="#id#Block">
	<div class="adm-fileinput-item adm-fileinput-item-saved">
		<div class="adm-fileinput-item-preview">
			<span class="adm-fileinput-item-loading">
				<span class="container-loading-title">#MESS_LOADING#</span>
				<span class="container-loading-bg"><span class="container-loading-bg-progress" style="width: 60%;"></span></span>
			</span>
			<div class="adm-fileinput-item-preview-icon">
				<div class="bx-file-icon-container-medium icon-#ext#">
					<div class="bx-file-icon-cover">
						<div class="bx-file-icon-corner">
							<div class="bx-file-icon-corner-fix"></div>
						</div>
						<div class="bx-file-icon-images"></div>
					</div>
					<div class="bx-file-icon-label"></div>
				</div>
				<span class="container-doc-title" id="#id#Name">#name#</span>
			</div>
			<div class="adm-fileinput-item-preview-img" id="#id#Canvas"></div>
			<input style="display: none;" type="hidden" id="#id#Value" readonly="readonly" name="#input_name#" value="#id#" />
		</div>
		#description#
		<div class="adm-fileinput-item-panel">
			<span class="adm-fileinput-item-panel-btn adm-btn-setting" id="#id#Edit">&nbsp;</span>
			<span class="adm-fileinput-item-panel-btn adm-btn-del" id="#id#Del">&nbsp;</span>
		</div>
		<div id="#id#Properties" class="adm-fileinput-item-properties">#properties#</div>
	</div>
</div>
HTML
		,
		'unexisted' => <<<HTML
<div class="adm-fileinput-item-wrapper" id="#id#Block">
	<div class="adm-fileinput-item adm-fileinput-item-saved">
		<div class="adm-fileinput-item-preview">
			<span class="adm-fileinput-item-loading">
				<span class="container-loading-title">#MESS_LOADING#</span>
				<span class="container-loading-bg"><span class="container-loading-bg-progress" style="width: 60%;"></span></span>
			</span>
			<div class="adm-fileinput-item-preview-icon">
				<div class="bx-file-icon-container-medium icon-#ext#">
					<div class="bx-file-icon-cover">
						<div class="bx-file-icon-corner">
							<div class="bx-file-icon-corner-fix"></div>
						</div>
						<div class="bx-file-icon-images"></div>
					</div>
					<div class="bx-file-icon-label"></div>
				</div>
				<span class="container-doc-title" id="#id#Name">#name#</span>
			</div>
			<div class="adm-fileinput-item-preview-img" id="#id#Canvas"></div>
			<input style="display: none;" data-fileinput="Y" type="file" id="#id#Value" readonly="readonly" name="#input_name#" value="" />
		</div>
		#description#
		<div class="adm-fileinput-item-panel">
			<span class="adm-fileinput-item-panel-btn adm-btn-del" id="#id#Del">&nbsp;</span>
		</div>
		<div id="#id#Properties" class="adm-fileinput-item-properties">#properties#</div>
	</div>
</div>
HTML
);
	/**
	 * @param array $params
	 */
	public function __construct($params = array())
	{
		global $USER;
		$inputs = array_merge($this->elementSetts, $params);
		$this->elementSetts = array(
			"name" => $inputs["name"],
			"description" => !empty($inputs["description"]),
			"delete" => $inputs['delete'] !== false,
			"edit" => $inputs['edit'] !== false,
			"thumbSize" => 640,
			//"properties" => (is_array($inputs) ? $inputs : array()) //TODO It is needed to deal with additional properties
		);
		if (isset($params['id']))
			$this->elementSetts['id'] = $params['id'];
		$replace = array(
			"/\\#MESS_LOADING\\#/" => Loc::getMessage("BXU_LoadingProcess"),
			"/\\#description\\#/" => ($this->elementSetts["edit"] == true && $this->elementSetts["description"] == true ? self::$templatePatterns["description"] : ""),
			"/\\#properties\\#/" => "",
			"/[\n\t]+/" => ""
		);
		$this->templates["uploaded"] = preg_replace(array_keys($replace), array_values($replace), self::$templatePatterns["uploaded"]);
		$this->templates["unexisted"] = preg_replace(array_keys($replace), array_values($replace), self::$templatePatterns["unexisted"]);

		$this->templates["new"] = preg_replace(array_keys($replace), array_values($replace), self::$templatePatterns["new"]);
		$replace = array(
			"#input_name#" => $inputs["name"],
			"#input_value#" => "",
			"#description_name#" => self::getInputName($inputs["name"], "_descr")
		);
		$this->templates["new"] = str_replace(array_keys($replace), array_values($replace), $this->templates["new"]);
		$inputs = array_merge($this->uploadSetts, $params);

		$this->uploadSetts = array(
			"upload" => '',
			"uploadType" => "path",
			"medialib" => ($inputs['medialib'] === true && \COption::GetOptionString('fileman', "use_medialib", "Y") != "N"),
			"fileDialog" => ($inputs['file_dialog'] === true || $inputs['fileDialog'] === true),
			"cloud" => ($inputs['cloud'] === true && $USER->CanDoOperation("clouds_browse") && \CModule::IncludeModule("clouds") && \CCloudStorage::HasActiveBuckets()),
			"maxCount" => ($params["maxCount"] > 0 ? $params["maxCount"] : 0),
			"maxSize" => ($params["maxSize"] > 0 ? $params["maxSize"] : 0),
			"allowUpload" => (in_array($params["allowUpload"], array("A", "I", "F")) ? $params["allowUpload"] : "A"),
			"allowUploadExt" => trim($params["allowUploadExt"]),
			"allowSort" => ($params["allowSort"] == "N" ? "N" : "Y")
		);

		if (empty($this->uploadSetts["allowUploadExt"]) && $this->uploadSetts["allowUpload"] == "F")
			$this->uploadSetts["allowUpload"] = "A";
		if (isset($this->elementSetts["id"]))
			$this->id = 'bx_file_'.strtolower(preg_replace("/[^a-z0-9]/i", "_", $this->elementSetts["id"]));
		else
			$this->id = 'bx_file_'.strtolower(preg_replace("/[^a-z0-9]/i", "_", $this->elementSetts["name"]));

		if ($inputs['upload'] === true)
		{
			$this->uploadSetts['upload'] = FileInputReceiver::sign(array(
				"id" => ($inputs['uploadType'] === "hash" ? "hash" : "path"),
				"allowUpload" => $this->uploadSetts["allowUpload"],
				"allowUploadExt" => $this->uploadSetts["allowUploadExt"]
			));
			$this->uploadSetts['uploadType'] = (in_array($inputs["uploadType"], array(/*"file",*/ "hash", "path")) ? $inputs["uploadType"] : "path");
		}
		self::$instance = $this;
	}

	/**
	 * @param array $params
	 * @param bool $hashIsID
	 * @return FileInput
	 */
	public static function createInstance($params = array(), $hashIsID = true)
	{
		$c = __CLASS__;
		return new $c($params, $hashIsID);
	}

	/**
	 * @param array $values
	 * @return string
	 */
	public function show($values = array())
	{
		\CJSCore::Init(array('fileinput'));

		$files = '';

		if (is_array($values))
		{
			foreach($values as $inputName => $fileId)
			{
				if ($fileId > 0)
				{
					$res = $this->getFile($fileId, $inputName);
					$t = $this->templates["uploaded"];
					if (!is_array($res))
					{
						$res = $this->formFile($fileId, $inputName);
						$t = $this->templates["unexisted"];
					}
					$patt = array();
					foreach ($res as $pat => $rep)
						$patt[] = "#".$pat."#";
					$files .= str_ireplace($patt, array_values($res), $t);
					$this->files[] = $res;
				}
			}
		}
		else if (($fileId = intval($values)) > 0)
		{
			$res = $this->getFile($fileId, $this->elementSetts["name"]);
			$t = $this->templates["uploaded"];
			if (!is_array($res))
			{
				$res = $this->formFile($fileId, $this->elementSetts["name"]);
				$t = $this->templates["unexisted"];
			}
			$patt = array();
			foreach ($res as $pat => $rep)
				$patt[] = "#".$pat."#";
			$files .= str_ireplace($patt, array_values($res), $t);
			$this->files[] = $res;
		}

		$canDelete = true ? '' : 'adm-fileinput-non-delete'; // In case we can not delete files
		$canEdit = ($this->elementSetts["edit"] ? '' : 'adm-fileinput-non-edit');

		$settings = \CUserOptions::GetOption('main', 'fileinput');
		$settings = (is_array($settings) ? $settings : array(
			"frameFiles" => "Y",
			"pinDescription" => "N",
			"mode" => "mode-pict",
			"presets" => array(
				array("width" => 200, "height" => 200, "title" => "200x200")
			),
			"presetActive" => 0
		));

		if ($this->uploadSetts["maxCount"] == 1)
		{
			if ($this->uploadSetts["allowUpload"] == "I")
				$hintMessage = Loc::getMessage("BXU_DNDMessage01");
			else if ($this->uploadSetts["allowUpload"] == "F")
				$hintMessage = Loc::getMessage("BXU_DNDMessage02", array("#ext#" => $this->uploadSetts["allowUploadExt"]));
			else
				$hintMessage = Loc::getMessage("BXU_DNDMessage03");

			if ($this->uploadSetts["maxSize"] > 0)
				$hintMessage .= Loc::getMessage("BXU_DNDMessage04", array("#size#" => \CFile::FormatSize($this->uploadSetts["maxSize"])));
		}
		else
		{
			$maxCount = ($this->uploadSetts["maxCount"] > 0 ? GetMessage("BXU_DNDMessage5", array("#maxCount#" => $this->uploadSetts["maxCount"])) : "");
			if ($this->uploadSetts["allowUpload"] == "I")
				$hintMessage = Loc::getMessage("BXU_DNDMessage1", array("#maxCount#" => $maxCount));
			else if ($this->uploadSetts["allowUpload"] == "F")
				$hintMessage = Loc::getMessage("BXU_DNDMessage2", array("#ext#" => $this->uploadSetts["allowUploadExt"], "#maxCount#" => $maxCount));
			else
				$hintMessage = Loc::getMessage("BXU_DNDMessage3", array("#maxCount#" => $maxCount));
			if ($this->uploadSetts["maxSize"] > 0)
				$hintMessage .= Loc::getMessage("BXU_DNDMessage4", array("#size#" => \CFile::FormatSize($this->uploadSetts["maxSize"])));
		}

		$this->getExtDialogs();

		$uploadSetts = $this->uploadSetts + $settings;
		if (array_key_exists("presets", $settings))
		{
			$uploadSetts["presets"] = $settings["presets"];
			$uploadSetts["presetActive"] = $settings["presetActive"];
		}

		$template = \CUtil::JSEscape($this->templates["new"]);
		$classSingle = (array_key_exists("maxCount", $uploadSetts) && intval($uploadSetts["maxCount"]) == 1 ? "adm-fileinput-wrapper-single" : "");
		$uploadSetts = \CUtil::PhpToJSObject($uploadSetts);
		$elementSetts = \CUtil::PhpToJSObject($this->elementSetts);
		$values = \CUtil::PhpToJSObject($this->files);
		$mes = array(
			"preview" => GetMessage("BXU_Preview"),
			"nonPreview" => GetMessage("BXU_NonPreview")
		);

		$settings["modePin"] = ($settings["pinDescription"] == "Y" && $this->elementSetts["description"] ? "mode-with-description" : "");
		$t = <<<HTML
<div class="adm-fileinput-wrapper {$classSingle}">
<div class="adm-fileinput-btn-panel">
	<span class="adm-btn add-file-popup-btn" id="{$this->id}_add"></span>
	<div class="adm-fileinput-mode {$settings["mode"]}" id="{$this->id}_mode">
		<a href="#" class="mode-pict" id="{$this->id}ThumbModePreview" title="{$mes["preview"]}"></a>
		<a href="#" class="mode-file" id="{$this->id}ThumbModeNonPreview" title="{$mes["nonPreview"]}"></a>
	</div>
</div>
<div id="{$this->id}_block" class="adm-fileinput-area {$canDelete} {$canEdit} {$settings['mode']} {$settings["modePin"]}">
	<div class="adm-fileinput-area-container" id="{$this->id}_container">{$files}</div>
	<span class="adm-fileinput-drag-area-hint" id="{$this->id}Notice">{$hintMessage}</span>
<script>
(function(BX)
{
	if (BX)
	{
		BX.ready(function(){
			new BX.UI.FileInput('{$this->id}', {$uploadSetts}, {$elementSetts}, {$values}, '{$template}');
		});
	}
})(window["BX"] || top["BX"]);
</script>
</div>
</div>
HTML;
		return $t;
	}
	private function getExtDialogs()
	{
		if ($this->uploadSetts["medialib"] && Loader::includeModule("fileman"))
		{
			$this->uploadSetts["medialib"] = array(
				"click" => "OpenMedialibDialog".$this->id,
				"handler" => "SetValueFromMedialib".$this->id
			);
			\CMedialib::ShowDialogScript(array(
				"event" => $this->uploadSetts["medialib"]["click"],
				"arResultDest" => array(
					"FUNCTION_NAME" => $this->uploadSetts["medialib"]["handler"]
				)
			));
		}
		if ($this->uploadSetts["fileDialog"])
		{
			$this->uploadSetts["fileDialog"] = array(
				"click" => "OpenFileDialog".$this->id,
				"handler" => "SetValueFromFileDialog".$this->id
			);
			\CAdminFileDialog::ShowScript
			(
				Array(
					"event" => $this->uploadSetts["fileDialog"]["click"],
					"arResultDest" => array("FUNCTION_NAME" => $this->uploadSetts["fileDialog"]["handler"]),
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
	private function formFile($fileId = "", $inputName = "file")
	{
		$result = array(
			'id' => $fileId,
			'name' => 'Unknown',
			'description_name' => self::getInputName($inputName, "_descr"),
			'description' => '',
			'size' => 0,
			'type' => 'unknown',
			'input_name' => $inputName,
			'input_value' => $fileId,
			'entity' => "file",
			'ext' => ''
		);
		if (!empty($this->elementSetts["properties"]))
		{
			foreach ($this->elementSetts["properties"] as $key)
			{
				$result[$key."_name"] = self::getInputName($inputName, "_".$key);
				$result[$key] = "";
			}
		}
		return $result;
	}
	private function getFile($fileId = "", $inputName = "file")
	{
		$result = NULL;
		$properties = array();
		if (is_array($fileId))
		{
			$properties = $fileId;
			unset($properties["ID"]);
			$fileId = $fileId["ID"];
		}

		if (($ar = \CFile::GetFileArray($fileId)) && is_array($ar))
		{
			$name = (strlen($ar['ORIGINAL_NAME'])>0?$ar['ORIGINAL_NAME']:$ar['FILE_NAME']);
			$result = array(
				'id' => $fileId,
				'name' => $name,
				'description_name' => self::getInputName($inputName, "_descr"),
				'description' => str_replace('"', "&quot;", $ar['DESCRIPTION']),
				'size' => $ar['FILE_SIZE'],
				'type' => $ar['CONTENT_TYPE'],
				'input_name' => $inputName,
				'input_value' => $fileId,
				'entity' => (($ar["WIDTH"] > 0 && $ar["HEIGHT"] > 0) ? "image" : "file"),
				'ext' => GetFileExtension($name),
				'real_url' => $ar['SRC']
			);
			if ($result['entity'] == "image")
			{
				$result['tmp_url'] = FileInputUnclouder::getSrc($ar);
				$result['preview_url'] = FileInputUnclouder::getSrcWithResize($ar, array('width' => 200, 'height' => 200));
				$result['width'] = $ar["WIDTH"];
				$result['height'] = $ar["HEIGHT"];
			}
		}
		else
		{
			$strFilePath = $_SERVER["DOCUMENT_ROOT"].$fileId;
			$io = \CBXVirtualIo::GetInstance();
			if($io->FileExists($strFilePath))
			{
				$flTmp = $io->GetFile($strFilePath);
				if ($flTmp->IsExists())
				{
					$ar = \CFile::GetImageSize($strFilePath);
					$result = array(
						'id' => md5($fileId),
						'name' => $flTmp->getName(),
						'description_name' => self::getInputName($inputName, "_descr"),
						'description' => "",
						'size' => $flTmp->GetFileSize(),
						'type' => $flTmp->getType(),
						'input_name' => $inputName,
						'input_value' => $fileId,
						'entity' => ((is_array($ar) && $ar["WIDTH"] > 0 && $ar["HEIGHT"] > 0) ? "image" : "file"),
						'ext' => GetFileExtension($flTmp->getName()),
						'real_url' => $fileId
					);
					if ($result['entity'] == "image")
						$result['tmp_url'] = $fileId;
				}
			}
		}
		if (!empty($this->elementSetts["properties"]))
		{
			foreach ($this->elementSetts["properties"] as $key)
			{
				$result[$key."_name"] = self::getInputName($inputName, "_".$key);
				$result[$key] = $properties[$key];
			}
		}
		return $result;
	}

	private static function getInputName($inputName, $type = "")
	{
		if ($type == "")
			return $inputName;
		$p = strpos($inputName, "[");
		return  ($p > 0) ? substr($inputName, 0, $p).$type.substr($inputName, $p) : $inputName.$type;
	}
}
?>