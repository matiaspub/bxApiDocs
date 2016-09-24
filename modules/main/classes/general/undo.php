<?php
IncludeModuleLangFile(__FILE__);

class CUndo
{
	public static function Add($params = array())
	{
		global $DB, $USER, $CACHE_MANAGER;

		$ID = '1'.md5(uniqid(rand(), true));
		$strContent = serialize($params['arContent']);
		$userID = $USER->GetId();

		$arFields = array(
			'ID' => $ID,
			'MODULE_ID' => $params['module'],
			'UNDO_TYPE' => $params['undoType'],
			'UNDO_HANDLER' => $params['undoHandler'],
			'CONTENT' => $strContent,
			'USER_ID' => $userID,
			'TIMESTAMP_X' => time(),
		);

		$DB->Add("b_undo", $arFields, Array("CONTENT"));

		$CACHE_MANAGER->Clean(substr($ID, 0, 3), "b_undo");

		return $ID;
	}

	public static function Escape($ID)
	{
		global $USER, $CACHE_MANAGER;
		if (!isset($USER) || !is_object($USER) || !$USER->IsAuthorized())
			return false;

		$arUndo = null;
		$cacheId = substr($ID, 0, 3);
		if ($CACHE_MANAGER->Read(48 * 3600, $cacheId, "b_undo"))
		{
			$arUndoCache = $CACHE_MANAGER->Get($cacheId);
		}
		else
		{
			$arUndoCache = array();
			$arUndoList = CUndo::GetList(array('arFilter' => array('%ID' => $cacheId."%")));
			foreach ($arUndoList as $ar)
			{
				if (!isset($arUndoCache[$ar["ID"]]) && !isset($arUndoCache[$ar["ID"]][$ar["USER_ID"]]))
					$arUndoCache[$ar["ID"]][$ar["USER_ID"]] = $ar;
			}
			$CACHE_MANAGER->Set($cacheId, $arUndoCache);
		}
		$arUndo = $arUndoCache[$ID][$USER->GetId()];

		if (!$arUndo)
			return false;

		// Include module
		if ($arUndo['MODULE_ID'] && strlen($arUndo['MODULE_ID']) > 0)
		{
			if (!CModule::IncludeModule($arUndo['MODULE_ID']))
				return false;
		}

		// Get params for Escaping
		$arParams = unserialize($arUndo['CONTENT']);

		// Check and call Undo handler
		$p = strpos($arUndo['UNDO_HANDLER'], "::");
		if ($p === false)
		{
			if (function_exists($arUndo['UNDO_HANDLER'])) // function
			{
				call_user_func($arUndo['UNDO_HANDLER'], array($arParams, $arUndo['UNDO_TYPE']));
			}
		}
		else
		{
			$className = substr($arUndo['UNDO_HANDLER'], 0, $p);
			if (class_exists($className)) //class
			{
				$methodName = substr($arUndo['UNDO_HANDLER'], $p + 2);
				if (method_exists($className, $methodName)) //static method
				{
					call_user_func_array(array($className, $methodName), array($arParams, $arUndo['UNDO_TYPE']));
				}
			}
		}

		// Del entry
		CUndo::Delete($ID);
		return true;
	}

	public static function GetList($Params = array())
	{
		global $DB;

		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder'])? $Params['arOrder']: array('ID' => 'asc');

		$arFields = array(
			"ID" => array("FIELD_NAME" => "U.ID", "FIELD_TYPE" => "string"),
			"MODULE_ID" => array("FIELD_NAME" => "U.MODULE_ID", "FIELD_TYPE" => "string"),
			"UNDO_TYPE" => array("FIELD_NAME" => "U.UNDO_TYPE", "FIELD_TYPE" => "string"),
			"UNDO_HANDLER" => array("FIELD_NAME" => "U.UNDO_HANDLER", "FIELD_TYPE" => "string"),
			"CONTENT" => array("FIELD_NAME" => "U.CONTENT", "FIELD_TYPE" => "string"),
			"USER_ID" => array("FIELD_NAME" => "U.USER_ID", "FIELD_TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD_NAME" => "U.TIMESTAMP_X", "FIELD_TYPE" => "int"),
		);

		$arSqlSearch = array();

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				$n = strtoupper($key);
				if ($n == '%ID')
					$arSqlSearch[] = "(U.ID like '".$DB->ForSql($val)."')";
				elseif ($n == 'ID' || $n == 'USER_ID')
					$arSqlSearch[] = GetFilterQuery("U.".$n, $val, 'N');
				elseif (isset($arFields[$n]))
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
			}
		}

		$strOrderBy = '';
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			if (isset($arFields[$by]))
			{
				$strOrderBy .= $arFields[$by]["FIELD_NAME"].' '.(strtolower($order) == 'desc'? 'desc'.(strtoupper($DB->type) == "ORACLE"? " NULLS LAST": ""): 'asc'.(strtoupper($DB->type) == "ORACLE"? " NULLS FIRST": "")).',';
			}
		}

		if ($strOrderBy)
		{
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				U.*
			FROM
				b_undo U
			WHERE
				$strSqlSearch
			$strOrderBy";

		$res = $DB->Query($strSql);
		$arResult = array();
		while ($arRes = $res->Fetch())
			$arResult[] = $arRes;

		return $arResult;
	}

	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;

		$DB->Query("DELETE FROM b_undo WHERE ID='".$DB->ForSql($ID)."'");

		$CACHE_MANAGER->Clean(substr($ID, 0, 3), "b_undo");
	}

	public static function CleanUpOld()
	{
		global $DB, $CACHE_MANAGER;

		// All entries older than one day
		$timestamp = mktime(date("H"), date("i"), 0, date("m"), date("d") - 1, date("Y"));
		$DB->Query("delete from b_undo where TIMESTAMP_X <= ".$timestamp);

		$CACHE_MANAGER->CleanDir("b_undo");

		return "CUndo::CleanUpOld();";
	}

	public static function ShowUndoMessage($ID)
	{
		$_SESSION['BX_UNDO_ID'] = $ID;
	}

	public static function CheckNotifyMessage()
	{
		global $USER, $APPLICATION;
		if (!is_array($_SESSION) || !array_key_exists("BX_UNDO_ID", $_SESSION))
			return;

		$ID = $_SESSION['BX_UNDO_ID'];
		unset($_SESSION['BX_UNDO_ID']);

		$arUndoList = CUndo::GetList(array('arFilter' => array('ID' => $ID, 'USER_ID' => $USER->GetId())));
		if (!$arUndoList)
			return;

		$arUndo = $arUndoList[0];
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
};
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
		'LANG' => 1,
		'SITE' => 1,
		'PATH' => 1,
		'TYPE' => 1,
		'EVENT_NAME' => 1,
		'SHOW_ERROR' => 1,
		'NAME' => 1,
		'FULL_SRC' => 1,
		'ACTION' => 1,
		'LOGICAL' => 1,
		'ADMIN' => 1,
		'ADDITIONAL' => 1,
		'NEW' => 1,
		'MODE' => 1,
		'CONDITION' => 1,
		'QUESTION_TYPE' => 1,
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
			{
				$this->formId = self::_GetFormID();
			}

			addEventHandler('main', 'OnBeforeLocalRedirect', array($this, 'Reset'));

			if (!defined('BX_PUBLIC_MODE'))
			{
				CJSCore::Init(array('autosave'));
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
			$DISABLE_STANDARD_NOTIFY = ($admin? 'false': 'true');

			if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
				echo CJSCore::GetHTML(array('autosave'));
			?>
			<input type="hidden" name="autosave_id" id="autosave_marker_<?=$this->GetID()?>" value="<?=$this->GetID()?>"/>
			<script type="text/javascript">window.autosave_<?=$this->GetID()?> = new top.BX.CAutoSave({
					form_marker: 'autosave_marker_<?=$this->GetID()?>',
					form_id: '<?=$this->GetID()?>',
					DISABLE_STANDARD_NOTIFY: <?=$DISABLE_STANDARD_NOTIFY?>
				});
			</script>
			<?
			$this->checkRestore();

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
		global $USER, $DB, $CACHE_MANAGER;

		if (!$USER->IsAuthorized())
			return false;

		$ID = $this->GetID();
		$DB->Query("DELETE FROM b_undo WHERE ID='".$DB->ForSQL($ID)."' AND USER_ID='".$USER->GetID()."'");

		$CACHE_MANAGER->Clean(substr($ID, 0, 3), "b_undo");

		return true;
	}

	public function Set($data)
	{
		global $DB, $USER, $CACHE_MANAGER;

		if (!$USER->IsAuthorized())
			return false;

		if (!is_array($data) || empty($data))
			return false;

		$ID = $this->GetID();
		$arFields = array(
			'MODULE_ID' => 'main',
			'UNDO_TYPE' => 'autosave',
			'UNDO_HANDLER' => 'CAutoSave::_Restore',
			'CONTENT' => serialize($data),
			'USER_ID' => $USER->GetID(),
			'TIMESTAMP_X' => time(),
		);
		$arBinds = array(
			"CONTENT" => $arFields["CONTENT"],
		);

		$strUpdate = $DB->PrepareUpdate("b_undo", $arFields);
		$rs = $DB->QueryBind("UPDATE b_undo SET ".$strUpdate." WHERE ID = '".$DB->ForSQL($ID)."'", $arBinds);
		if ($rs->AffectedRowsCount() == 0)
		{
			$arFields['ID'] = $ID;
			$DB->Add("b_undo", $arFields, array("CONTENT"), "", true);
		}

		$CACHE_MANAGER->Clean(substr($ID, 0, 3), "b_undo");
		return true;
	}

	public function Restore($arFields)
	{
		if (is_array($arFields))
		{
?>
<script type="text/javascript">BX.ready(function(){
	if (window.autosave_<?=$this->GetID();?>)
	{
		window.autosave_<?=$this->GetID();?>.Restore(<?=CUtil::PhpToJSObject($arFields);?>);
	}
});</script>
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
		foreach (GetModuleEvents("main", "OnAutoSaveRestore", true) as $arEvent)
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
