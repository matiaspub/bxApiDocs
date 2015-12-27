<?
IncludeModuleLangFile(__FILE__);

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllHistoryService
	extends CBPRuntimeService
{
	protected $useGZipCompression = false;

	public function __construct()
	{
		$useGZipCompressionOption = \Bitrix\Main\Config\Option::get("bizproc", "use_gzip_compression", "");
		if ($useGZipCompressionOption === "Y")
			$this->useGZipCompression = true;
		elseif ($useGZipCompressionOption === "N")
			$this->useGZipCompression = false;
		else
			$this->useGZipCompression = (function_exists("gzcompress") && ($GLOBALS["DB"]->type != "ORACLE" || !defined('BX_UTF')));
	}

	protected function ParseFields(&$arFields, $id = 0)
	{
		global $DB;

		$id = intval($id);
		$updateMode = ($id > 0 ? true : false);
		$addMode = !$updateMode;

		if ($addMode && !is_set($arFields, "DOCUMENT_ID"))
			throw new CBPArgumentNullException("DOCUMENT_ID");

		if (is_set($arFields, "DOCUMENT_ID") || $addMode)
		{
			$arDocumentId = CBPHelper::ParseDocumentId($arFields["DOCUMENT_ID"]);
			$arFields["MODULE_ID"] = $arDocumentId[0];
			if (strlen($arFields["MODULE_ID"]) <= 0)
				$arFields["MODULE_ID"] = false;
			$arFields["ENTITY"] = $arDocumentId[1];
			$arFields["DOCUMENT_ID"] = $arDocumentId[2];
		}

		if (is_set($arFields, "NAME") || $addMode)
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if (strlen($arFields["NAME"]) <= 0)
				throw new CBPArgumentNullException("NAME");
		}

		if (is_set($arFields, "DOCUMENT"))
		{
			if ($arFields["DOCUMENT"] == null)
			{
				$arFields["DOCUMENT"] = false;
			}
			elseif (is_array($arFields["DOCUMENT"]))
			{
				if (count($arFields["DOCUMENT"]) > 0)
					$arFields["DOCUMENT"] = $this->GetSerializedForm($arFields["DOCUMENT"]);
				else
					$arFields["DOCUMENT"] = false;
			}
			else
			{
				throw new CBPArgumentTypeException("DOCUMENT");
			}
		}

		unset($arFields["MODIFIED"]);
	}

	private function GetSerializedForm($arTemplate)
	{
		$buffer = serialize($arTemplate);
		if ($this->useGZipCompression)
			$buffer = gzcompress($buffer, 9);
		return $buffer;
	}

	public static function Add($arFields)
	{
		$h = new CBPHistoryService();
		return $h->AddHistory($arFields);
	}

	public static function Update($id, $arFields)
	{
		$h = new CBPHistoryService();
		return $h->UpdateHistory($id, $arFields);
	}

	private static function GenerateFilePath($documentId)
	{
		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$dest = "/bizproc/";
		if (strlen($arDocumentId[0]) > 0)
			$dest .= preg_replace("/[^a-zA-Z0-9._]/i", "_", $arDocumentId[0]);
		else
			$dest .= "NA";
		$documentIdMD5 = md5($arDocumentId[2]);
		$dest .= "/".preg_replace("/[^a-zA-Z0-9_]/i", "_", $arDocumentId[1])."/".substr($documentIdMD5, 0, 3)."/".$documentIdMD5;

		return $dest;
	}

	public function DeleteHistory($id, $documentId = null)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");

		$arFilter = array("ID" => $id);
		if ($documentId != null)
			$arFilter["DOCUMENT_ID"] = $documentId;

		$db = $this->GetHistoryList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "MODULE_ID", "ENTITY", "DOCUMENT_ID")
		);
		if ($ar = $db->Fetch())
		{
			$deleteFile = true;
			foreach(GetModuleEvents("bizproc", "OnBeforeDeleteFileFromHistory", true) as $event)
			{
				if(ExecuteModuleEventEx($event, array($id, $documentId)) !== true)
				{
					$deleteFile = false;
					break;
				}
			}

			if ($deleteFile)
			{
				$dest = self::GenerateFilePath($ar["DOCUMENT_ID"]);
				DeleteDirFilesEx("/".(COption::GetOptionString("main", "upload_dir", "upload")).$dest."/".$ar["ID"]);
				if(CModule::IncludeModule('clouds'))
					CCloudStorage::DeleteDirFilesEx($dest."/".$ar["ID"]);
			}

			$DB->Query("DELETE FROM b_bp_history WHERE ID = ".intval($id)." ", true);
		}
	}

	public static function Delete($id, $documentId = null)
	{
		$h = new CBPHistoryService();
		$h->DeleteHistory($id, $documentId);
	}

	static public function DeleteHistoryByDocument($documentId)
	{
		global $DB;

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$dest = self::GenerateFilePath($documentId);
		DeleteDirFilesEx("/".(COption::GetOptionString("main", "upload_dir", "upload")).$dest);
		if(CModule::IncludeModule('clouds'))
			CCloudStorage::DeleteDirFilesEx($dest);

		$DB->Query(
			"DELETE FROM b_bp_history ".
			"WHERE DOCUMENT_ID = '".$DB->ForSql($arDocumentId[2])."' ".
			"	AND ENTITY = '".$DB->ForSql($arDocumentId[1])."' ".
			"	AND MODULE_ID ".((strlen($arDocumentId[0]) > 0) ? "= '".$DB->ForSql($arDocumentId[0])."'" : "IS NULL")." ",
			true
		);
	}

	public static function DeleteByDocument($documentId)
	{
		$h = new CBPHistoryService();
		$h->DeleteHistoryByDocument($documentId);
	}

	public static function GetById($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new CBPArgumentNullException("id");

		$h = new CBPHistoryService();
		$db = $h->GetHistoryList(array(), array("ID" => $id));
		return $db->GetNext();
	}

	public static function GetList($arOrder = array("ID" => "DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		$h = new CBPHistoryService();
		return $h->GetHistoryList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}

	public static function RecoverDocumentFromHistory($id)
	{
		$arHistory = self::GetById($id);
		if (!$arHistory)
			throw new Exception(str_replace("#ID#", intval($id), GetMessage("BPCGHIST_INVALID_ID")));

		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($arHistory["DOCUMENT_ID"]);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "RecoverDocumentFromHistory"), array($documentId, $arHistory["DOCUMENT"]));

		return false;
	}

	public static function PrepareFileForHistory($documentId, $arFileId, $historyIndex)
	{
		$dest = self::GenerateFilePath($documentId);

		$fileParameterIsArray = true;
		if (!is_array($arFileId))
		{
			$arFileId = array($arFileId);
			$fileParameterIsArray = false;
		}

		$result = array();

		foreach ($arFileId as $fileId)
		{
			if($ar = CFile::GetFileArray($fileId))
			{
				$newFilePath = CFile::CopyFile($fileId, false, $dest."/".$historyIndex."/".$ar["FILE_NAME"]);
				if ($newFilePath)
					$result[] = $newFilePath;
			}
		}

		if (!$fileParameterIsArray)
		{
			if (count($result) > 0)
				$result = $result[0];
			else
				$result = "";
		}

		return $result;
	}

	public static function MergeHistory($firstDocumentId, $secondDocumentId)
	{
		global $DB;

		$arFirstDocumentId = CBPHelper::ParseDocumentId($firstDocumentId);
		$arSecondDocumentId = CBPHelper::ParseDocumentId($secondDocumentId);

		$DB->Query(
			"UPDATE b_bp_history SET ".
			"	DOCUMENT_ID = '".$DB->ForSql($arFirstDocumentId[2])."', ".
			"	ENTITY = '".$DB->ForSql($arFirstDocumentId[1])."', ".
			"	MODULE_ID = '".$DB->ForSql($arFirstDocumentId[0])."' ".
			"WHERE DOCUMENT_ID = '".$DB->ForSql($arSecondDocumentId[2])."' ".
			"	AND ENTITY = '".$DB->ForSql($arSecondDocumentId[1])."' ".
			"	AND MODULE_ID = '".$DB->ForSql($arSecondDocumentId[0])."' "
		);
	}

	public static function MigrateDocumentType($oldType, $newType, $workflowTemplateIds)
	{
		global $DB;

		$arOldType = CBPHelper::ParseDocumentId($oldType);
		$arNewType = CBPHelper::ParseDocumentId($newType);

		$DB->Query(
			"UPDATE b_bp_history SET ".
			"	ENTITY = '".$DB->ForSql($arNewType[1])."', ".
			"	MODULE_ID = '".$DB->ForSql($arNewType[0])."' ".
			"WHERE ENTITY = '".$DB->ForSql($arOldType[1])."' ".
			"	AND MODULE_ID = '".$DB->ForSql($arOldType[0])."' ".
			"	AND DOCUMENT_ID IN (SELECT t.DOCUMENT_ID FROM b_bp_workflow_state t WHERE t.WORKFLOW_TEMPLATE_ID in (".implode(",", $workflowTemplateIds).") and t.MODULE_ID='".$DB->ForSql($arOldType[0])."' and t.ENTITY='".$DB->ForSql($arOldType[1])."') "
		);
	}
}

class CBPHistoryResult extends CDBResult
{
	private $useGZipCompression = false;

	public function __construct($res, $useGZipCompression)
	{
		$this->useGZipCompression = $useGZipCompression;
		parent::CDBResult($res);
	}

	private function GetFromSerializedForm($value)
	{
		if (strlen($value) > 0)
		{
			if ($this->useGZipCompression)
				$value = gzuncompress($value);

			$value = unserialize($value);
			if (!is_array($value))
				$value = array();
		}
		else
		{
			$value = array();
		}
		return $value;
	}

	public function Fetch()
	{
		$res = parent::Fetch();

		if ($res)
		{
			if (array_key_exists("DOCUMENT_ID", $res))
				$res["DOCUMENT_ID"] = array($res["MODULE_ID"], $res["ENTITY"], $res["DOCUMENT_ID"]);
			if (array_key_exists("DOCUMENT", $res))
				$res["DOCUMENT"] = $this->GetFromSerializedForm($res["DOCUMENT"]);
		}

		return $res;
	}
}
?>
