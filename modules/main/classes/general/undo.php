<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CUndo
{
	public static function Add($params = array())
	{
		global $USER;

		$ID = '1'.md5(uniqid(rand(), true));

		$strContent = serialize($params['arContent']);

		$arFields = array(
			'ID' => $ID,
			'MODULE_ID' => $params['module'],
			'UNDO_TYPE'  => $params['undoType'],
			'UNDO_HANDLER'  => $params['undoHandler'],
			'CONTENT' => $strContent,
			'USER_ID' => $USER->GetId(),
			'TIMESTAMP_X' => time()
		);

		CDatabase::Add("b_undo", $arFields, Array("CONTENT"));
		return $ID;
	}

	public static function Escape($ID)
	{
		global $USER;
		if(!isset($USER) || !is_object($USER) || !$USER->IsAuthorized())
			return false;

		$arUndos = CUndo::GetList(array('arFilter' => array('ID' => $ID, 'USER_ID' => $USER->GetId())));
		if (count($arUndos) <= 0)
			return false;

		$arUndo = $arUndos[0];

		// Include module
		if ($arUndo['MODULE_ID'] && strlen($arUndo['MODULE_ID']) > 0)
			CModule::IncludeModule($arUndo['MODULE_ID']);

		// Get params for Escaping
		$arParams = unserialize($arUndo['CONTENT']);

		// Check and call Undo handler
		if (function_exists($arUndo['UNDO_HANDLER'])) // function
		{
			call_user_func($arUndo['UNDO_HANDLER'], array($arParams, $arUndo['UNDO_TYPE']));
		}
		elseif(strpos($arUndo['UNDO_HANDLER'], "::") !== false) // Static method
		{
			$p = strpos($arUndo['UNDO_HANDLER'], "::");
			$className = substr($arUndo['UNDO_HANDLER'], 0, $p);
			$methodName = substr($arUndo['UNDO_HANDLER'], $p + 2);

			if (class_exists($className))
				call_user_func_array(array($className, $methodName), array($arParams, $arUndo['UNDO_TYPE']));
		}

		// Del entry
		CUndo::Delete($ID);
		return true;
	}

	public static function GetList($Params = array())
	{
		global $DB;

		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('ID' => 'asc');

		$arFields = array(
			"ID" => Array("FIELD_NAME" => "U.ID", "FIELD_TYPE" => "string"),
			"MODULE_ID" => Array("FIELD_NAME" => "U.MODULE_ID", "FIELD_TYPE" => "string"),
			"UNDO_TYPE" => Array("FIELD_NAME" => "U.UNDO_TYPE", "FIELD_TYPE" => "string"),
			"UNDO_HANDLER" => Array("FIELD_NAME" => "U.UNDO_HANDLER", "FIELD_TYPE" => "string"),
			"CONTENT" => Array("FIELD_NAME" => "U.CONTENT", "FIELD_TYPE" => "string"),
			"USER_ID" => Array("FIELD_NAME" => "U.USER_ID", "FIELD_TYPE" => "int"),
			"TIMESTAMP_X" => Array("FIELD_NAME" => "U.TIMESTAMP_X", "FIELD_TYPE" => "int")
		);

		$err_mess = "CUndo::GetList<br>Line: ";
		$arSqlSearch = array();

		if(is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for($i=0, $l = count($filter_keys); $i<$l; $i++)
			{
				$n = strtoupper($filter_keys[$i]);
				$val = $arFilter[$filter_keys[$i]];
				if ($n == 'ID')
					$arSqlSearch[] = GetFilterQuery("U.ID", $val, 'N');
				elseif(isset($arFields[$n]))
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
			}
		}

		$strOrderBy = '';
		foreach($arOrder as $by=>$order)
			if(isset($arFields[strtoupper($by)]))
				$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

		if(strlen($strOrderBy)>0)
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				U.*
			FROM
				b_undo U
			WHERE
				$strSqlSearch
			$strOrderBy";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$arResult = Array();
		while($arRes = $res->Fetch())
			$arResult[]=$arRes;

		return $arResult;
	}

	public static function Delete($ID)
	{
		global $DB;
		$strSql = "DELETE FROM b_undo WHERE ID='".$DB->ForSql($ID)."'";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

	public static function CleanUpOld()
	{
		global $DB;
		// All entries older than one day
		$timestamp = mktime(date("H"), date("i"), 0, date("m"),   date("d") - 1,   date("Y"));
		$strSql = "delete from b_undo where TIMESTAMP_X <= ".$timestamp." ";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return "CUndo::CleanUpOld();";
	}

	public static function ShowUndoMessage($ID)
	{
		$_SESSION['BX_UNDO_ID'] = $ID;
	}

	public static function CheckNotifyMessage()
	{
		global $USER, $APPLICATION;
		if(!is_array($_SESSION) || !array_key_exists("BX_UNDO_ID", $_SESSION))
			return;

		$ID = $_SESSION['BX_UNDO_ID'];
		unset($_SESSION['BX_UNDO_ID']);

		$arUndos = CUndo::GetList(array('arFilter' => array('ID' => $ID, 'USER_ID' => $USER->GetId())));
		if (count($arUndos) <= 0)
			return;
		$arUndo = $arUndos[0];
		$detail = GetMessage('MAIN_UNDO_TYPE_'.strtoupper($arUndo['UNDO_TYPE']));

		$s = "
<script>
window.BXUndoLastChanges = function()
{
	if (!confirm(\"".GetMessage("MAIN_UNDO_ESCAPE_CHANGES_CONFIRM")."\"))
		return;

	BX.ajax.get(\"/bitrix/admin/public_undo.php?undo=".$ID."&".bitrix_sessid_get()."\", null, function(result)
	{
		if (result && result.toUpperCase().indexOf(\"ERROR\") != -1)
			BX.admin.panel.Notify(\"".GetMessage("MAIN_UNDO_ESCAPE_ERROR")."\");
		else
			window.location = window.location;
	});
}
BX.ready(function()
{
	setTimeout(function()
	{
		BX.admin.panel.Notify('".$detail." <a href=\"javascript: void(0);\" onclick=\"window.BXUndoLastChanges(); return false;\" title=\"".GetMessage("MAIN_UNDO_ESCAPE_CHANGES_TITLE")."\">".GetMessage("MAIN_UNDO_ESCAPE_CHANGES")."</a>');
	}, 100);
});
</script>";

		$APPLICATION->AddHeadString($s);
	}
}

class CAutoSave
{
	/*'ID', 'COPY_ID', 'ENTITY_ID', 'mid', 'WEB_FORM_ID', 'CONTRACT_ID', 'COURSE_ID', 'IBLOCK_SECTION_ID', 'IBLOCK_ID', 'CHANNEL_ID', 'VOTE_ID', 'DICTIONARY_ID', 'CHAPTER_ID', 'LESSON_ID', */

	private $formId = '';
	private $autosaveId = '';

	private $bInited = false;

	private $bSkipRestore = false;

	private static $bAllowed = null;
	private static $arImportantParams = array(
		'LANG'=>1, 'SITE'=>1, 'PATH'=>1, 'TYPE'=>1, 'EVENT_NAME'=>1, 'SHOW_ERROR'=>1, 'NAME'=>1, 'FULL_SRC'=>1, 'ACTION'=>1, 'LOGICAL'=>1, 'ADMIN'=>1, 'ADDITIONAL'=>1, 'NEW'=>1, 'MODE'=>1, 'CONDITION'=>1, 'QUESTION_TYPE'=>1,
	);

	public function __construct()
	{
		global $USER;

		if ($USER->IsAuthorized())
		{
			if (isset($_REQUEST['autosave_id']) && strlen($_REQUEST['autosave_id']) == 33)
			{
				$this->bSkipRestore = true;
				$this->autosaveId = preg_replace("/[^a-z0-9_]/i", "", $_REQUEST['autosave_id']);
			}
			else
				$this->formId = self::_GetFormID();

			addEventHandler('main', 'OnBeforeLocalRedirect', array($this, 'Reset'));

			if (!defined('BX_PUBLIC_MODE'))
				CJSCore::Init(array('autosave'));

			if (!$this->bSkipRestore)
			{
				addEventHandler('main', 'onEpilog', array($this, 'checkRestore'));
			}
		}
	}

	public function Init($admin = true)
	{
		global $USER;

		if (!$USER->IsAuthorized())
			return false;

		if (!$this->bInited)
		{
			$DISABLE_STANDARD_NOTIFY = ($admin ? 'false' : 'true');

			if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
				echo CJSCore::GetHTML(array('autosave'));
?>
<input type="hidden" name="autosave_id" id="autosave_marker_<?=$this->GetID()?>" value="<?=$this->GetID()?>" />
<script type="text/javascript">window.autosave_<?=$this->GetID()?> = new top.BX.CAutoSave({
	form_marker: 'autosave_marker_<?=$this->GetID()?>',
	form_id: '<?=$this->GetID()?>',
	DISABLE_STANDARD_NOTIFY: <?=$DISABLE_STANDARD_NOTIFY?>
});
</script>
<?
			$this->bInited = true;
		}
		return true;
	}

	public function checkRestore()
	{
		$key = addEventHandler('main', 'OnAutoSaveRestore', array($this, 'Restore'));
		CUndo::Escape($this->GetID());
		removeEventHandler('main', 'OnAutoSaveRestore', $key);
	}

	public function Reset()
	{
		global $USER, $DB;

		if (!$USER->IsAuthorized())
			return false;

		$DB->Query("DELETE FROM b_undo WHERE ID='".$DB->ForSQL($this->GetID())."' AND USER_ID='".$USER->GetID()."'");

		return true;
	}

	public function Set($data)
	{
		global $USER;

		if ($this->Reset() !== false)
		{
			if (is_array($data) && count($data) > 0)
			{
				$arFields = array(
					'ID' => $this->GetID(),
					'MODULE_ID' => 'main',
					'UNDO_TYPE'  => 'autosave',
					'UNDO_HANDLER'  => 'CAutoSave::_Restore',
					'CONTENT' => serialize($data),
					'USER_ID' => $USER->GetID(),
					'TIMESTAMP_X' => time()
				);

				CDatabase::Add("b_undo", $arFields, Array("CONTENT"));
			}
			return true;
		}
		return false;
	}

	public function Restore($arFields)
	{
		if (is_array($arFields))
		{
?>
<script type="text/javascript">if (window.autosave_<?=$this->GetID();?>) window.autosave_<?=$this->GetID();?>.Restore(<?=CUtil::PhpToJSObject($arFields);?>);</script>
<?
		}
	}

	public function GetID()
	{
		global $USER;

		if (!$this->autosaveId)
		{
			$this->autosaveId = '2'.md5($this->formId.'|'.$USER->GetID());
		}

		return $this->autosaveId;
	}

	private static function _GetFormID()
	{
		global $APPLICATION;

		$arParams = array();
		foreach ($_GET as $param => $value)
		{
			$param = ToUpper($param);

			if (substr($param, -2) == 'ID' || array_key_exists($param, self::$arImportantParams))
				$arParams[$param] = $value;
		}

		ksort($arParams);

		$url = ToLower($APPLICATION->GetCurPage()).'?';
		foreach ($arParams as $param => $value)
		{
			if (is_array($value))
				$value = implode('|', $value);

			$url .= urlencode($param).'='.urlencode($value).'&';
		}

		return $url;
	}

	public static function _Restore($arFields)
	{
		$rsEvents = GetModuleEvents("main", "OnAutoSaveRestore");
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($arFields));
		}
	}

	public static function Allowed()
	{
		global $USER, $APPLICATION;

		if (!$USER->IsAuthorized())
			return false;

		if (self::$bAllowed == null)
		{
			$arOpt = CUserOptions::GetOption('global', 'settings');
			self::$bAllowed = $arOpt['autosave'] != 'N' && $APPLICATION->GetCurPage() != '/bitrix/admin/update_system.php';
		}

		return self::$bAllowed;
	}
}
