<?
IncludeModuleLangFile(__FILE__);
class CSticker
{
	static $oParser = null;
	static $Params = null;

	public static function GetOperations()
	{
		global $USER;
		static $arOp;
		static $arUsers;

		$userGroups = $USER->GetUserGroupArray();
		$key = implode('-', $userGroups);

		if (!is_array($arOp))
			$arOp = array();

		if (!is_array($arOp[$key]))
		{
			$res = CSticker::GetAccessPermissions();
			$arOp[$key]  = array();
			$bDefaultTask = false;

			$count = 0;
			foreach ($res as $group_id => $task_id)
				if (in_array($group_id, $userGroups))
				{
					$arOp[$key] = array_merge($arOp[$key], CTask::GetOperations($task_id, true));
					$count++;
				}

			if ($count < count($userGroups))
			{
				$defaultAccess = COption::GetOptionString('fileman', 'stickers_default_access', false);
				if ($defaultAccess !== false)
					$arOp[$key] = array_merge($arOp[$key], CTask::GetOperations($defaultAccess, true));
			}
		}
		return $arOp[$key];
	}

	public static function CanDoOperation($operation)
	{
		if ($GLOBALS["USER"]->IsAdmin())
			return true;

		$arOp = CSticker::GetOperations();
		return in_array($operation, $arOp);
	}


	public static function GetAccessPermissions()
	{
		global $DB;

		$strSql = 'SELECT * FROM b_sticker_group_task SGT';
		$res = $DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arResult = array();
		while($arRes = $res->Fetch())
			$arResult[intVal($arRes['GROUP_ID'])] = intVal($arRes['TASK_ID']);

		return $arResult;
	}

	public static function SaveAccessPermissions($arTaskPerm)
	{
		global $DB;
		$DB->Query("DELETE FROM b_sticker_group_task WHERE 1=1", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		foreach($arTaskPerm as $group_id => $task_id)
		{
			$arInsert = $DB->PrepareInsert("b_sticker_group_task", array("GROUP_ID" => $group_id, "TASK_ID" => $task_id));
			$strSql = "INSERT INTO b_sticker_group_task(".$arInsert[0].") VALUES(".$arInsert[1].")";
			$DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function GetTasks()
	{
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/fileman/admin/task_description.php");

		$arTasks = Array();
		$res = CTask::GetList(Array('LETTER' => 'asc'), Array('MODULE_ID' => 'fileman', 'BINDING' => 'stickers'));
		while($arRes = $res->Fetch())
		{
			$name = '';
			if ($arRes['SYS'])
				$name = GetMessage('TASK_NAME_'.strtoupper($arRes['NAME']));
			if (strlen($name) == 0)
				$name = $arRes['NAME'];
			$arTasks[$arRes['ID']] = Array('title' => $name, 'letter' => $arRes['LETTER']);
		}
		return $arTasks;
	}

	public static function GetList($Params = array())
	{
		if (!CSticker::CanDoOperation('sticker_view'))
			return false;

		global $DB, $USER;
		$bDBResult = isset($Params['bDBResult'])? $Params['bDBResult']: false;
		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('ID' => 'asc');

		// Cache
		$cachePath = "stickers/";
		$cacheTime = 36000000;
		$bCache = true;

		static $arFields = array(
			"ID" => Array("FIELD_NAME" => "ST.ID", "FIELD_TYPE" => "int"),
			"SITE_ID" => Array("FIELD_NAME" => "ST.SITE_ID", "FIELD_TYPE" => "string"),
			"PAGE_URL" => Array("FIELD_NAME" => "ST.PAGE_URL", "FIELD_TYPE" => "string"),
			"PAGE_TITLE" => Array("FIELD_NAME" => "ST.PAGE_TITLE", "FIELD_TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD_NAME" => "ST.DATE_CREATE", "FIELD_TYPE" => "date"),
			"DATE_UPDATE" => Array("FIELD_NAME" => "ST.DATE_UPDATE", "FIELD_TYPE" => "date"),
			"MODIFIED_BY" => Array("FIELD_NAME" => "ST.MODIFIED_BY", "FIELD_TYPE" => "int"),
			"CREATED_BY" => Array("FIELD_NAME" => "ST.CREATED_BY", "FIELD_TYPE" => "int"),
			"PERSONAL" => Array("FIELD_NAME" => "ST.PERSONAL", "FIELD_TYPE" => "string"),
			"CONTENT" => Array("FIELD_NAME" => "ST.CONTENT ", "FIELD_TYPE" => "string"),
			"POS_TOP" => Array("FIELD_NAME" => "ST.POS_TOP", "FIELD_TYPE" => "int"),
			"POS_LEFT" => Array("FIELD_NAME" => "ST.POS_LEFT", "FIELD_TYPE" => "int"),
			"WIDTH" => Array("FIELD_NAME" => "ST.WIDTH", "FIELD_TYPE" => "int"),
			"HEIGHT" => Array("FIELD_NAME" => "ST.HEIGHT", "FIELD_TYPE" => "int"),
			"COLOR" => Array("FIELD_NAME" => "ST.COLOR", "FIELD_TYPE" => "int"),

			"COLLAPSED" => Array("FIELD_NAME" => "ST.COLLAPSED ", "FIELD_TYPE" => "string"),
			"CLOSED" => Array("FIELD_NAME" => "ST.CLOSED ", "FIELD_TYPE" => "string"),
			"DELETED" => Array("FIELD_NAME" => "ST.DELETED ", "FIELD_TYPE" => "string"),

			"MARKER_TOP" => Array("FIELD_NAME" => "ST.MARKER_TOP", "FIELD_TYPE" => "int"),
			"MARKER_LEFT" => Array("FIELD_NAME" => "ST.MARKER_LEFT", "FIELD_TYPE" => "int"),
			"MARKER_WIDTH" => Array("FIELD_NAME" => "ST.MARKER_WIDTH", "FIELD_TYPE" => "int"),
			"MARKER_HEIGHT" => Array("FIELD_NAME" => "ST.MARKER_HEIGHT", "FIELD_TYPE" => "int"),
			"MARKER_ADJUST" => Array("FIELD_NAME" => "ST.MARKER_ADJUST", "FIELD_TYPE" => "string")
		);

		$err_mess = (CSticker::GetErrorMess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = array();
		$strSqlSearch = "";

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array($arFilter, $bDBResult));
			if(($tzOffset = CTimeZone::GetOffset()) <> 0)
				$cacheId .= "_".$tzOffset;

			if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
			{
				$cachedRes = $cache->GetVars();
				if (!empty($cachedRes['stickers']))
					return $cachedRes['stickers'];
			}
		}

		if(is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for($i=0, $l = count($filter_keys); $i<$l; $i++)
			{
				$n = strtoupper($filter_keys[$i]);
				$val = $arFilter[$filter_keys[$i]];
				if(is_string($val)  && strlen($val) <=0)
					continue;

				if ($n == 'ID')
					$arSqlSearch[] = GetFilterQuery("ST.ID", $val, 'N');
				if ($n == 'PAGE_URL')
					$arSqlSearch[] = GetFilterQuery("ST.PAGE_URL", $val, 'N');
				if ($n == 'SITE_ID')
					$arSqlSearch[] = GetFilterQuery("ST.SITE_ID", $val, 'N');
				elseif(isset($arFields[$n]))
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
			}
		}

		$strOrderBy = '';
		foreach($arOrder as $by=>$order)
			if(isset($arFields[strtoupper($by)]))
				$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

		if(strlen($strOrderBy) > 0)
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if (is_array($arFilter['COLORS']))
		{
			$strColors = "";
			for($i=0; $i < count($arFilter['COLORS']); $i++)
				$strColors .= ",".IntVal($arFilter['COLORS'][$i]);
			$strSqlSearch .= "\n AND COLOR in (".trim($strColors, ", ").")";
		}

		$strSql = "
			SELECT
				ST.*, ".$DB->DateToCharFunction("ST.DATE_UPDATE")." as DATE_UPDATE2,
				".$DB->DateToCharFunction("ST.DATE_CREATE")."  as DATE_CREATE2
			FROM
				b_sticker ST
			WHERE
				$strSqlSearch
			$strOrderBy";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		if ($arFilter['USER_ID'] > 0 || !$bDBResult)
		{
			$arResult = Array();
			while($arRes = $res->Fetch())
			{
				if ($arFilter['USER_ID'] > 0 && $arRes['CREATED_BY'] != $arFilter['USER_ID'] &&
				($arRes['PERSONAL'] == 'Y'/* It's another user's personal sticker*/
				|| $arFilter['ONLY_OWNER'] == 'Y'/* display only owner's stickers*/))
					continue;

				if (!$bDBResult)
				{
					$arRes['AUTHOR'] = CSticker::GetUserName($arRes['CREATED_BY']);
					$arRes['INFO'] = CSticker::GetStickerInfo($arRes['CREATED_BY'], $arRes['DATE_CREATE2'], $arRes['MODIFIED_BY'], $arRes['DATE_UPDATE2']);
					$arRes['HTML_CONTENT'] = CSticker::BBParseToHTML($arRes['CONTENT']);
					$arRes['MARKER_ADJUST'] = unserialize($arRes['MARKER_ADJUST']);
				}

				$arResult[] = $arRes;
			}

			if ($bDBResult)
				$res->InitFromArray($arResult);
		}

		if ($bDBResult)
			$arResult = $res;

		if ($bCache)
		{
			$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
			$cache->EndDataCache(array("stickers" => $arResult));
		}

		return $arResult;
	}

	public static function ClearCache()
	{
		global $CACHE_MANAGER;
		$cache = new CPHPCache;
		$cache->CleanDir("stickers/");
		$CACHE_MANAGER->CleanDir("fileman_stickers_count");
	}

	public static function GetById($id)
	{
		global $USER;
		$res = CSticker::GetList(
			array(
				'arFilter' => array(
					'USER_ID' => $USER->GetId(),
					'ID' => intVal($id),
				)
			));
		if ($res && is_array($res) && count($res) > 0)
			return $res[0];
		return false;
	}

	public static function GetPagesList($site)
	{
		if (!CSticker::CanDoOperation('sticker_view'))
			return false;

		global $USER, $DB;
		$userId = $USER->GetId();

		$cachePath = "stickers/";
		$cacheTime = 36000000;
		$bCache = true;

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = 'page_list_'.$userId;

			if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
			{
				$cachedRes = $cache->GetVars();
				if (!empty($cachedRes['page_list']))
					return $cachedRes['page_list'];
			}
		}
		$err_mess = (CSticker::GetErrorMess())."<br>Function: GetPagesList<br>Line: ";
		$strSql = "
			select PAGE_URL, PAGE_TITLE, max(DATE_UPDATE) as MAX_DATE_UPDATE
			from b_sticker
			where
				DELETED='N'
				AND SITE_ID='".$DB->ForSql($site)."'
				AND ((PERSONAL='Y' AND CREATED_BY=".intVal($userId).") OR PERSONAL='N')
			group by PAGE_URL, PAGE_TITLE
			order by MAX_DATE_UPDATE desc";

		$strSql = $DB->TopSQL($strSql, 10);

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$arResult = array();
		while($arRes = $res->Fetch())
			$arResult[] = $arRes;

		if ($bCache)
		{
			$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
			$cache->EndDataCache(array("page_list" => $arResult));
		}

		return $arResult;
	}

	public static function GetCurPageCount()
	{
		global $APPLICATION;
		return CSticker::GetCount(array(
			"PAGE_URL" => $APPLICATION->GetCurPage(),
			"SITE_ID" => SITE_ID
		));
	}

	public static function GetCount($Params)
	{
		global $DB, $USER, $CACHE_MANAGER;
		$userId = $USER->GetId();

		$cacheId = 'stickers_count_'.$userId."_".$Params["PAGE_URL"];
		$bCache = CACHED_stickers_count !== false;

		if($bCache && $CACHE_MANAGER->Read(CACHED_stickers_count, $cacheId, "fileman_stickers_count"))
			return $CACHE_MANAGER->Get($cacheId);

		$strSqlSearch = "((ST.PERSONAL='Y' AND ST.CREATED_BY=".intVal($userId).") OR ST.PERSONAL='N')";
		$strSqlSearch .= "\n AND ST.CLOSED='N' AND ST.DELETED='N' AND ST.SITE_ID='".$DB->ForSql($Params['SITE_ID'])."'";

		if ($Params["PAGE_URL"])
			$strSqlSearch .= "\n AND ST.PAGE_URL='".$DB->ForSql($Params["PAGE_URL"])."'";

		$strSql = "
			SELECT
				COUNT(ST.ID) as CNT
			FROM
				b_sticker ST
			WHERE
				$strSqlSearch";

		$err_mess = (CSticker::GetErrorMess())."<br>Function: GetCount<br>Line: ";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$count = 0;
		if($arRes = $res->Fetch())
			$count = $arRes['CNT'];

		if ($bCache)
			$CACHE_MANAGER->Set($cacheId, $count);

		return $count;
	}

	public static function Edit($Params)
	{
		if (!CSticker::CanDoOperation('sticker_edit'))
			return;

		global $DB, $USER;
		$arFields = $Params['arFields'];

		if(!CSticker::CheckFields($arFields))
			return false;

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;

		if (!isset($arFields['~DATE_UPDATE']))
			$arFields['~DATE_UPDATE'] = $DB->CurrentTimeFunction();

		if (!isset($arFields['MODIFIED_BY']))
			$arFields['MODIFIED_BY'] = $USER->GetId();

		if (!isset($arFields['SITE_ID']))
			$arFields['SITE_ID'] = $_REQUEST['site_id'];

		if ($bNew) // Add
		{
			if (!isset($arFields['CREATED_BY']))
				$arFields['CREATED_BY'] = $arFields['MODIFIED_BY'];

			if (!isset($arFields['~DATE_CREATE']))
				$arFields['~DATE_CREATE'] = $arFields['~DATE_UPDATE'];

			unset($arFields['ID']);

			$ID = $DB->Add("b_sticker", $arFields, Array("CONTENT","MARKER_ADJUST"));
		}
		else // Update
		{
			$ID = $arFields['ID'];
			unset($arFields['ID']);

			$strUpdate = $DB->PrepareUpdate("b_sticker", $arFields);
			$strSql =
				"UPDATE b_sticker SET ".
					$strUpdate.
				" WHERE ID=".IntVal($ID);

			$DB->QueryBind($strSql, Array("CONTENT" => $arFields["CONTENT"], "MARKER_ADJUST" => $arFields["MARKER_ADJUST"]), false,  "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		CSticker::ClearCache();
		return $ID;
	}

	public static function DeleteAll()
	{
		if (!CSticker::CanDoOperation('sticker_del'))
			return GetMessage('FMST_DEL_ACCESS_ERROR');

		global $DB;
		if (!$DB->Query("DELETE FROM b_sticker WHERE 1=1", false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return GetMessage('FMST_REQ_ERROR');

		CSticker::ClearCache();
		return true;
	}

	public static function Delete($ids = array())
	{
		if (!is_array($ids))
			$ids = array($ids);

		if (!CSticker::CanDoOperation('sticker_del'))
			return GetMessage('FMST_DEL_ACCESS_ERROR');

		if (count($ids) == 0)
			return GetMessage('FMST_NO_ITEMS_WARN');

		global $DB;
		$strIds = "";
		for($i=0; $i < count($ids); $i++)
			$strIds .= ",".IntVal($ids[$i]);
		$strSql = "DELETE FROM b_sticker WHERE ID in (".trim($strIds, ", ").")";

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return GetMessage('FMST_REQ_ERROR');

		CSticker::ClearCache();
		return true;
	}

	public static function CheckFields()
	{
		return true;
	}

	public static function SetHiden($ids = array(), $bHide)
	{
		if (!is_array($ids))
			$ids = array($ids);

		if (!CSticker::CanDoOperation('sticker_edit'))
			return GetMessage('FMST_EDIT_ACCESS_ERROR');

		if (count($ids) == 0)
			return GetMessage('FMST_NO_ITEMS_WARN');

		global $DB;
		$strIds = "";
		for($i=0; $i < count($ids); $i++)
			$strIds .= ",".IntVal($ids[$i]);

		$arFields = array("CLOSED" => $bHide ? "Y" : "N");
		$strUpdate = $DB->PrepareUpdate("b_sticker", $arFields);
		$strSql =
			"UPDATE b_sticker SET ".
				$strUpdate.
			" WHERE ID in (".trim($strIds, ", ").")";

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return GetMessage('FMST_REQ_ERROR');

		CSticker::ClearCache();
		return true;
	}

	public static function InitJS($Params)
	{
		global $APPLICATION, $USER;
		CUtil::InitJSCore(array('window', 'ajax'));
		$APPLICATION->AddHeadScript('/bitrix/js/fileman/sticker.js');
		$APPLICATION->SetAdditionalCSS('/bitrix/js/fileman/sticker.css');

		$pageUrl = $APPLICATION->GetCurPage();
		$pageTitle = $APPLICATION->GetTitle();
		if ($pageTitle == '')
			$pageTitle = $pageUrl;

		$listSize = CUtil::GetPopupSize("bx_sticker_list_resize_id", array("width" => 800, "height" => 450));
		$size = explode("_", COption::GetOptionString("fileman", "stickers_start_sizes", "350_200"));
		$min_width = 280;
		$min_height = 160;
		$JSConfig = array(
			"access" => CSticker::CanDoOperation('sticker_edit') ? "W" : "R",
			"sessid_get" => bitrix_sessid_get(),
			"start_width" => $size[0] > $min_width ? $size[0] : $min_width,
			"start_height" => $size[1] > $min_height ? $size[1] : $min_height,
			"min_width" => $min_width,
			"min_height" => $min_height,
			"start_color" => CUserOptions::GetOption('fileman', "stickers_last_color", 0),
			"zIndex" => 800,
			"curUserName" => CSticker::GetUserName(),
			"curUserId" => $USER->GetId(),
			"pageUrl" => $pageUrl,
			"pageTitle" => $pageTitle,
			"bShowStickers" => $Params['bInit'],
			"listWidth" => $listSize['width'],
			"listHeight" => $listSize['height'],
			"listNaviSize" => CUserOptions::GetOption('fileman', "stickers_navi_size", 5),
			"useHotkeys" => COption::GetOptionString('fileman', "stickers_use_hotkeys", "Y") == "Y",
			"filterParams" => CSticker::GetFilterParams(),
			"bHideBottom" => COption::GetOptionString("fileman", "stickers_hide_bottom", "Y") == "Y",
			"focusOnSticker" => isset($_GET['show_sticker'])? intVal($_GET['show_sticker']): 0,
			"strDate" => FormatDate("j F", time()+CTimeZone::GetOffset()),
			"curPageCount" => $Params['curPageCount'],
			"site_id" => SITE_ID
		);

		if (!is_array($Params['stickers']))
			$Params['stickers'] = array();

		self::$Params = array("JSCONFIG" => $JSConfig, "STICKERS" => $Params['stickers']);
	}

	public static function InitJsAfter()
	{
		if(is_array(self::$Params))
		{
			return '<script type="text/javascript">BX.ready(function(){'.CSticker::AppendLangMessages()." window.oBXSticker = new BXSticker(".CUtil::PhpToJSObject(self::$Params['JSCONFIG']).", ".CUtil::PhpToJSObject(self::$Params['STICKERS']).", BXST_MESS);});</script>";
		}
	}

	public static function GetUserName($id = false)
	{
		global $USER;
		static $arUsersCache = array();

		if ($id !== false)
		{
			if (isset($arUsersCache[$id]))
				return $arUsersCache[$id];

			$rsu = CUser::GetByID($id);
			if($arUser = $rsu->Fetch())
				$arUsersCache[$id] = htmlspecialcharsback(CUser::FormatName(CSite::GetNameFormat(), $arUser));
			else
				$arUsersCache[$id] = '- Unknown -';
		}
		else
		{
			$id = $USER->GetId();
			if (isset($arUsersCache[$id]))
				return $arUsersCache[$id];

			$arUsersCache[$id] = htmlspecialcharsback($USER->GetFormattedName());
		}

		return $arUsersCache[$id];
	}

	public static function AppendLangMessages()
	{
		return 'var BXST_MESS =
{
	Public : "'.GetMessage('FMST_TYPE_PUBLIC').'",
	Personal : "'.GetMessage('FMST_TYPE_PERSONAL').'",
	Close : "'.GetMessage('FMST_CLOSE').'",
	Collapse : "'.GetMessage('FMST_COLLAPSE').'",
	UnCollapse : "'.GetMessage('FMST_UNCOLLAPSE').'",
	UnCollapseTitle : "'.GetMessage('FMST_UNCOLLAPSE_TITLE').'",
	SetMarkerArea : "'.GetMessage('FMST_SET_MARKER_AREA').'",
	SetMarkerEl : "'.GetMessage('FMST_SET_MARKER_ELEMENT').'",
	Color : "'.GetMessage('FMST_COLOR').'",
	Add : "'.GetMessage('FMST_ADD').'",
	PersonalTitle : "'.GetMessage('FMST_TYPE_PERSONAL_TITLE').'",
	PublicTitle : "'.GetMessage('FMST_TYPE_PUBLIC_TITLE').'",
	CursorHint : "'.GetMessage('FMST_CURSOR_HINT').'",
	Yellow : "'.GetMessage('FMST_COL_YELLOW').'",
	Green : "'.GetMessage('FMST_COL_GREEN').'",
	Blue : "'.GetMessage('FMST_COL_BLUE').'",
	Red : "'.GetMessage('FMST_COL_RED').'",
	Purple : "'.GetMessage('FMST_COL_PURPLE').'",
	Gray : "'.GetMessage('FMST_COL_GREY').'",
	StickerListTitle : "'.GetMessage('FMST_PANEL_STICKER_LIST').'",
	CompleteLabel : "'.GetMessage('FMST_COMPLETE_LABEL').'",
	DelConfirm : "'.GetMessage('FMST_LIST_DEL_CONFIRM').'",
	CloseConfirm : "'.GetMessage('FMST_CLOSE_CONFIRM').'",
	Complete : "'.GetMessage('FMST_COMPLETE').'",
	CloseNotify : "'.GetMessage('FMST_CLOSE_MESSAGE').'"
};';
	}

	public static function Init($Params = array())
	{
		global $APPLICATION, $USER;

		if (!CSticker::CanDoOperation('sticker_view'))
			return;
		// Dectect - show stickers or No
		$bGetStickers = CSticker::GetBShowStickers();

		$Stickers = array();
		if ($bGetStickers)
		{
			$Stickers = CSticker::GetList(array(
				'arFilter' => array(
					'USER_ID' => $USER->GetId(),
					'PAGE_URL' => $APPLICATION->GetCurPage(),
					'CLOSED' => 'N',
					'DELETED' => 'N',
					'SITE_ID' => SITE_ID
				)
			));
		}
		else
		{
			$Stickers = array();
		}

		$curPageCount = isset($Params['curPageCount']) ? $Params['curPageCount'] : CSticker::GetCurPageCount();

		CSticker::InitJS(array(
			'bInit' => $bGetStickers,
			'stickers' => $Stickers,
			'curPageCount' => $curPageCount
		));
	}

	public static function GetErrorMess()
	{
		return "Class: CSticker<br>File: ".__FILE__;
	}

	public static function GetScriptStr($mode)
	{
		if ($mode == 'add')
			return "if (window.oBXSticker){window.oBXSticker.AddSticker();}";
		elseif($mode == 'list_cur')
			return "if (window.oBXSticker){window.oBXSticker.ShowList('current');}";
		elseif($mode == 'list_all')
			return "if (window.oBXSticker){window.oBXSticker.ShowList('all');}";
		elseif($mode == 'show')
			return "if (window.oBXSticker){window.oBXSticker.ShowAll();}";
		return '';
	}

	public static function GetBShowStickers()
	{
		if (isset($_SESSION["SESS_SHOW_STICKERS"]) && $_SESSION["SESS_SHOW_STICKERS"] == "Y")
			return true;
		if (isset($_GET['show_sticker']) && intVal($_GET['show_sticker']) > 0)
			return true;
		return false;
	}

	public static function SetBShowStickers($bShow = false)
	{
		$_SESSION["SESS_SHOW_STICKERS"] = $bShow ? "Y" : "N";
		return $bShow;
	}

	public static function BBParseToHTML($str, $bForList = false)
	{
		if (!$oParser)
			$oParser = new blogTextParser1();

		$html = $oParser->convert($str, false, array(), array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "Y", "LIST" => "Y", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N", "TABLE" => "N"));

		if ($bForList)
		{
			$html = preg_replace(array(
					"/\[st_title\](.+?)\[\/st_title\]/is".BX_UTF_PCRE_MODIFIER,
					"/<br(.+?)>/is".BX_UTF_PCRE_MODIFIER,
					"/<\/??ol(.+?)>/is".BX_UTF_PCRE_MODIFIER,
					"/<\/??ul(.+?)>/is".BX_UTF_PCRE_MODIFIER,
					"/<\/??li(.+?)>/is".BX_UTF_PCRE_MODIFIER,
					"/<\/??w+(.+?)>/is".BX_UTF_PCRE_MODIFIER
			), " ", $html);

			$html = preg_replace(
				array(
					"/\[st_title\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/st_title\]/is".BX_UTF_PCRE_MODIFIER,
				),
				"",
				$html
			);

			if (strlen($html) > 40)
				$html = substr($html, 0, 40)."...";
		}
		else
		{
			$html = preg_replace(
				"/\[st_title\](.*?)\[\/st_title\]/is".BX_UTF_PCRE_MODIFIER,
				"<span class=\"bxst-title\">\\1</span> ",
				$html
			);

			// ?
			$html = preg_replace(
				array(
					"/\[st_title\]/is".BX_UTF_PCRE_MODIFIER,
					"/\[\/st_title\]/is".BX_UTF_PCRE_MODIFIER,
				),
				"",
				$html
			);
		}
		return $html;
	}

	public static function GetStickerInfo($createdBy, $dateCreate, $modBy, $dateMod)
	{
		$str = GetMessage("FMST_CREATED").": <b>".htmlspecialcharsEx(CSticker::GetUserName($createdBy))."</b> ".CSticker::GetUsableDate($dateCreate).
			"<br/>".
			GetMessage("FMST_UPDATED").": <b>".htmlspecialcharsEx(CSticker::GetUserName($modBy))."</b> ".CSticker::GetUsableDate($dateMod);

		return $str;
	}

	public static function GetUsableDate($d)
	{
		$ts = MakeTimeStamp(ConvertDateTime($d, "DD.MM.YYYY HH:MI"), "DD.MM.YYYY HH:MI");
		if (date("Y") == date("Y", $ts)) // Same year
			$date = FormatDate("j F G:i", $ts);
		else
			$date = FormatDate("j.m.Y", $ts);
		return $date;
	}

	public static function SetFilterParams($Filter)
	{
		CUserOptions::SetOption('fileman', "stickers_list_filter", serialize($Filter));
	}

	public static function GetFilterParams()
	{
		$result = array(
			'type' => 'all',
			'colors' => 'all',
			'status' => 'opened',
			'page' => 'all'
		);

		$res = CUserOptions::GetOption('fileman', "stickers_list_filter", false);
		if ($res !== false)
		{
			$Filter = unserialize($res);
			if (is_array($Filter))
			{
				if ($Filter['type'])
					$result['type'] = $Filter['type'] == 'my' ? 'my' : 'all';
				if ($Filter['status'] && in_array($Filter['status'], array('all', 'opened', 'closed')))
					$result['status'] = $Filter['status'];
				if ($Filter['page'])
					$result['page'] = $Filter['page'];
				if ($Filter['colors'])
					$result['colors'] = $Filter['colors'];
			}
		}

		return $result;
	}
}

// Class from /bitrix/modules/blog/general/functions.php
// TODO: Remove this class after including BB-parser class to the core
class blogTextParser1
{
	var $smiles = array();
	var $arFontSize = array(
		1 => 40, //"xx-small"
		2 => 60, //"x-small"
		3 => 80, //"small"
		4 => 100, //"medium"
		5 => 120, //"large"
		6 => 140, //"x-large"
		7 => 160); //"xx-large"
	var $word_separator = "\s.,;:!?\#\-\*\|\[\]\(\)\{\}";

	public static function blogTextParser1($strLang = False, $pathToSmile = false)
	{
		global $DB, $CACHE_MANAGER;
		if ($strLang===False)
			$strLang = LANGUAGE_ID;
		$this->path_to_smile = $pathToSmile;

		$this->imageWidth = COption::GetOptionString("blog", "image_max_width", 600);
		$this->imageHeight = COption::GetOptionString("blog", "image_max_height", 600);

		$this->smiles = array();

		// if($CACHE_MANAGER->Read(10, "b_blog_smile"))
		// {
			// $arSmiles = $CACHE_MANAGER->Get("b_blog_smile");
		// }
		// else
		// {
			// $db_res = CBlogSmile::GetList(array("SORT" => "ASC"), array("SMILE_TYPE" => "S"/*, "LANG_LID" => $strLang*/), false, false, Array("LANG_LID", "ID", "IMAGE", "DESCRIPTION", "TYPING", "SMILE_TYPE", "SORT"));
			// while ($res = $db_res->Fetch())
			// {
				// $tok = strtok($res["TYPING"], " ");
				// while ($tok)
				// {
					// $arSmiles[$res["LANG_LID"]][] = array(
										// "TYPING" => $tok,
										// "IMAGE"  => stripslashes($res["IMAGE"]),
										// "DESCRIPTION" => stripslashes($res["NAME"]));

					// $tok = strtok(" ");
				// }
			// }

			// function sortlen($a, $b) {
				// if (strlen($a["TYPING"]) == strlen($b["TYPING"])) {
					// return 0;
				// }
				// return (strlen($a["TYPING"]) > strlen($b["TYPING"])) ? -1 : 1;
			// }

			// foreach ($arSmiles as $LID => $arSmilesLID)
			// {
				// uasort($arSmilesLID, 'sortlen');
				// $arSmiles[$LID] = $arSmilesLID;
			// }

			// $CACHE_MANAGER->Set("b_blog_smile", $arSmiles);

		// }
		// $this->smiles = $arSmiles[$strLang];
	}

	public static function convert($text, $bPreview = True, $arImages = array(), $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N"), $arParams = Array())
	{
		global $DB;

		$text = preg_replace("#([?&;])PHPSESSID=([0-9a-zA-Z]{32})#is", "\\1PHPSESSID1=", $text);
		if(!is_array($arParams) && strlen($arParams) > 0)
			$type = $arParams;
		elseif(is_array($arParams))
			$type = $arParams["type"];

		if(IntVal($arParams["imageWidth"]) > 0)
			$this->imageWidth = IntVal($arParams["imageWidth"]);
		if(IntVal($arParams["imageHeight"]) > 0)
			$this->imageHeight = IntVal($arParams["imageHeight"]);

		$type = ($type == "rss" ? "rss" : "html");
		$serverName = "";
		if($type == "rss")
		{
			$dbSite = CSite::GetByID(SITE_ID);
			$arSite = $dbSite->Fetch();
			$serverName = $arSite["SERVER_NAME"];
			if (strLen($serverName) <=0)
			{
				if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
					$serverName = SITE_SERVER_NAME;
				else
					$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
			}
			$serverName = "http://".$serverName;
		}

		$this->quote_error = 0;
		$this->quote_open = 0;
		$this->quote_closed = 0;
		$this->code_error = 0;
		$this->code_open = 0;
		$this->code_closed = 0;
		$this->MaxStringLen = 60;
		$this->preg = array("counter" => 0, "pattern" => array(), "replace" => array());
		$this->allow_img_ext = "gif|jpg|jpeg|png";

		$allow = array(
			"HTML" => ($allow["HTML"] == "Y" ? "Y" : "N"),
			"NL2BR" => ($allow["NL2BR"] == "Y" ? "Y" : "N"),
			"CODE" => ($allow["CODE"] == "N" ? "N" : "Y"),
			"VIDEO" => ($allow["VIDEO"] == "N" ? "N" : "Y"),
			"ANCHOR" => ($allow["ANCHOR"] == "N" ? "N" : "Y"),
			"BIU" => ($allow["BIU"] == "N" ? "N" : "Y"),
			"IMG" => ($allow["IMG"] == "N" ? "N" : "Y"),
			"QUOTE" => ($allow["QUOTE"] == "N" ? "N" : "Y"),
			"FONT" => ($allow["FONT"] == "N" ? "N" : "Y"),
			"LIST" => ($allow["LIST"] == "N" ? "N" : "Y"),
			"SMILES" => ($allow["SMILES"] == "N" ? "N" : "Y"),
			"TABLE" => ($allow["TABLE"] == "N" ? "N" : "Y"),
			"ALIGN" => ($allow["ALIGN"] == "N" ? "N" : "Y"),
			"CUT_ANCHOR" => ($allow["CUT_ANCHOR"] == "Y" ? "Y" : "N"),
			);

		$this->arImages = $arImages;

		$text = str_replace(array("\001", "\002", chr(11), chr(12), chr(34), chr(39)), array("", "", "", "", chr(11), chr(12)), $text);

		if ($bPreview)
		{
			$text = preg_replace("#^(.*?)<cut[\s]*(/>|>).*?$#is", "\\1", $text);
			$text = preg_replace("#^(.*?)\[cut[\s]*(/\]|\]).*?$#is", "\\1", $text);
		}
		else
		{
			$text = preg_replace("#<cut[\s]*(/>|>)#is", "[cut]", $text);
		}

		if ($allow["CODE"]=="Y")
		{
			$text = preg_replace(
				array(
				"#<code(\s+[^>]*>|>)(.+?)</code(\s+[^>]*>|>)#is".BX_UTF_PCRE_MODIFIER,
				"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
				"/(?<=[\001])(([^\002]+))(?=([\002]))/ise".BX_UTF_PCRE_MODIFIER,
				"/\001/",
				"/\002/"),
				array(
				"[code]\\2[/code]",
				"\001",
				"\002",
				"\$this->pre_convert_code_tag('\\2')",
				"[code]",
				"[/code]"), $text);
		}

		if ($allow["HTML"] != "Y")
		{
			if ($allow["ANCHOR"]=="Y")
			{
				$text = preg_replace(
					array(
						"#<a[^>]+href\s*=\s*[\011]+(([^\011])+)[\011]+[^>]*>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*[\012]+(([^\012])+)[\012]+[^>]*>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href\s*=\s*(([^\012\011\>])+)>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
					"[url=\\1]\\3[/url]", $text);
			}
			if ($allow["BIU"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<b([^>]*)\>(.+?)\<\/b([^>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<u([^>]*)\>(.+?)\<\/u([^>]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<s([^>a-z]*)\>(.+?)\<\/s([^>a-z]*)>/is".BX_UTF_PCRE_MODIFIER,
						"/\<i([^>]*)\>(.+?)\<\/i([^>]*)>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[b]\\2[/b]",
						"[u]\\2[/u]",
						"[s]\\2[/s]",
						"[i]\\2[/i]"),
					$text);
			}
			if ($allow["IMG"]=="Y")
			{
				$text = preg_replace(
					"#<img[^>]+src\s*=[\s\011\012]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\/[-_/=:.a-z0-9@{}&?]+)+)[\s\011\012]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
					"[img]\\1[/img]", $text);
			}
			if ($allow["FONT"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<font[^>]+size\s*=[\s\011\012]*([0-9]+)[\s\011\012]*[^>]*\>(.+?)\<\/font[^>]*\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+color\s*=[\s\011\012]*(\#[a-f0-9]{6})[^>]*\>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
						"/\<font[^>]+face\s*=[\s\011\012]*([a-z\s\-]+)[\s\011\012]*[^>]*>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER),
					array(
						"[size=\\1]\\2[/size]",
						"[color=\\1]\\2[/color]",
						"[font=\\1]\\2[/font]"),
					$text);
			}
			if ($allow["LIST"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<ul((\s[^>]*)|(\s*))\>(.+?)<\/ul([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<ol((\s[^>]*)|(\s*))\>(.+?)<\/ol([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<li((\s[^>]*)|(\s*))\>/is".BX_UTF_PCRE_MODIFIER,
						),
					array(
						"[list]\\4[/list]",
						"[list=1]\\4[/list]",
						"[*]",
						),
					$text);
			}

			if ($allow["TABLE"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\<table((\s[^>]*)|(\s*))\>(.+?)<\/table([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<tr((\s[^>]*)|(\s*))\>(.*?)<\/tr([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						"/\<td((\s[^>]*)|(\s*))\>(.*?)<\/td([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
						),
					array(
						"[table]\\4[/table]",
						"[tr]\\4[/tr]",
						"[td]\\4[/td]",
						),
					$text);
			}

			if ($allow["QUOTE"]=="Y")
			{
				$text = preg_replace("#<(/?)quote(.*?)>#is", "[\\1quote]", $text);
			}

			if (strLen($text)>0)
			{
				$text = str_replace(
					array("<", ">", chr(34)),
					array("&lt;", "&gt;", "&quot;"),
					$text);
			}
		}

		if ($allow["ANCHOR"]=="Y")
		{
			$word_separator = str_replace("\]", "", $this->word_separator);
			$text = preg_replace("'(?<=^|[".$word_separator."]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\011\s\'\012\[\]\{\}])*)'is",
				"[url]\\1[/url]", $text);
		}

		foreach ($allow as $tag => $val)
		{
			if ($val != "Y")
				continue;

			if (strpos($text, "<nomodify>") !== false):
				$text = preg_replace(
					array(
						"/\001/", "/\002/",
						"/\<nomodify\>/is".BX_UTF_PCRE_MODIFIER, "/\<\/nomodify\>/is".BX_UTF_PCRE_MODIFIER,
						"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
						"/\001/", "/\002/"
						),
					array(
						"", "",
						"\001", "\002",
						"\$this->defended_tags('\\2', 'replace')",
						"<nomodify>", "</nomodify>"),
					$text);
			endif;

			switch ($tag)
			{
				case "CODE":
					$bHTML = false;
					if($allow["HTML"] == "Y")
						$bHTML = true;
					$text = preg_replace(
								array(	"/\[code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
										"/\[\/code([^\]])*\]/is".BX_UTF_PCRE_MODIFIER,
										"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
										"/\001/",
										"/\002/"
										),
								array(	"\001",
										"\002",
										"\$this->convert_code_tag('\\2', \$type, \$bHTML)",
										"[code]",
										"[/code]"),
								$text);
					break;
				case "VIDEO":
					$text = preg_replace("/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_video('\\1', '\\2')", $text);
					break;
				case "IMG":
						$text = preg_replace("/\[img([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_blog_image('\\1', '\\2', '\\3', \$type, \$serverName)", $text);

						$text = preg_replace("/\[img([^\]]*)\](.+?)\[\/img\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_image_tag('\\2', \$type, \$serverName, '\\1')", $text);
					break;
				case "ANCHOR":
					if($allow["CUT_ANCHOR"] != "Y")
					{
						$text = preg_replace(
									array(	"/\[url\]([^\]]*?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER,
											"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER),
									array(	"\$this->convert_anchor_tag('\\1', '\\1', '')",
											"\$this->convert_anchor_tag('\\1', '\\2', '')"
											),
									$text);
						break;
					}
					else
					{
						$text = preg_replace(
									array(	"/\[url\]([^\]]*?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER,
											"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER),
									"",
									$text);
						break;
					}
				case "BIU":
					$text = preg_replace(
								array(
									"/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER),
								array(
									"<b>\\1</b>",
									"<i>\\1</i>",
									"<s>\\1</s>",
									"<u>\\1</u>"),
								$text);
					break;
				case "LIST":
					while (preg_match("/\[list\s*=\s*([^\]]+?)\s*\](.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER, $text))
					$text = preg_replace(
								array(
									"/\[list\s*=\s*1\](\s*\\n*)(.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[list\s*=\s*a\](\s*\\n*)(.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[\*\]/".BX_UTF_PCRE_MODIFIER,
									),
								array(
									"<ol>\\2</ol>",
									"<ol type=\"a\">\\2</ol>",
									"<li>",
									),
								$text);
					while (preg_match("/\[list\](.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER, $text))
					$text = preg_replace(
								array(
									"/\[list\](\s*\\n*)(.+?)\[\/list\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[\*\]/".BX_UTF_PCRE_MODIFIER,
									),
								array(
									"<ul>\\2</ul>",
									"<li>",
									),
								$text);
					break;
				case "FONT":
					while (preg_match("/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[size\s*=\s*([^\]]+)\](.+?)\[\/size\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('size', '\\1', '\\2')", $text);
					while (preg_match("/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[font\s*=\s*([^\]]+)\](.*?)\[\/font\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('font', '\\1', '\\2')", $text);
					while (preg_match("/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/is".BX_UTF_PCRE_MODIFIER, $text))
						$text = preg_replace("/\[color\s*=\s*([^\]]+)\](.+?)\[\/color\]/ies".BX_UTF_PCRE_MODIFIER, "\$this->convert_font_attr('color', '\\1', '\\2')", $text);
					break;
				case "QUOTE":
					$text = preg_replace("#(\[quote([^\]\<\>])*\](.*)\[/quote([^\]\<\>])*\])#ies", "\$this->convert_quote_tag('\\1', \$type)", $text);
					break;
				case "TABLE":
					while (preg_match("/\[table\](.+?)\[\/table\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
								array(
									"/\[table\](\s*\\n*)(.*?)\[\/table\]/is".BX_UTF_PCRE_MODIFIER,
									"/\[tr\](.*?)\[\/tr\](\s*\\n*)/is".BX_UTF_PCRE_MODIFIER,
									"/\[td\](.*?)\[\/td\]/is".BX_UTF_PCRE_MODIFIER,
									),
								array(
									"<table class=\"data-table\">\\2</table>",
									"<tr>\\1</tr>",
									"<td>\\1</td>",
									),
								$text);
					}
					break;
				case "ALIGN":
					$text = preg_replace(
								array(
										"/\[left\]([^\]]+?)\[\/left\]/is".BX_UTF_PCRE_MODIFIER,
										"/\[right\]([^\]]+?)\[\/right\]/is".BX_UTF_PCRE_MODIFIER,
										"/\[center\]([^\]]+?)\[\/center\]/is".BX_UTF_PCRE_MODIFIER,
										),
								array(
										"<div align=\"left\">\\1</div>",
										"<div align=\"right\">\\1</div>",
										"<div align=\"center\">\\1</div>",
										),
								$text);
					break;
			}
			$text = str_replace(array(chr(34), chr(39)), array(chr(11), chr(12)), $text);
		}

		if ($allow["HTML"] != "Y")
			$text = str_replace("\n", "<br />", $text);

		$text = str_replace(
			array(
				"(c)", "(C)",
				"(tm)", "(TM)", "(Tm)", "(tM)",
				"(r)", "(R)"),
			array(
				"&#169;", "&#169;",
				"&#153;", "&#153;", "&#153;", "&#153;",
				"&#174;", "&#174;"),
			$text);

		if ($this->MaxStringLen > 0)
		{
			$text = preg_replace(
				array(
					"/(\&\#\d{1,3}\;)/is".BX_UTF_PCRE_MODIFIER,
					"/(?<=^|\>)([^\<]+)(?=\<|$)/ies".BX_UTF_PCRE_MODIFIER,
					"/(\<\019((\&\#\d{1,3}\;))\>)/is".BX_UTF_PCRE_MODIFIER,),
				array(
					"<\019\\1>",
					"\$this->part_long_words('\\1')",
					"\\2"),
				$text);
		}

		if (strpos($text, "<nosmile>") !== false)
		{
			$text = preg_replace(
				array(
					"/\001/", "/\002/",
					"/\<nosmile\>/is".BX_UTF_PCRE_MODIFIER, "/\<\/nosmile\>/is".BX_UTF_PCRE_MODIFIER,
					"/(\001([^\002]+)\002)/ies".BX_UTF_PCRE_MODIFIER,
					"/\001/is", "/\002/is"
					),
				array(
					"", "",
					"\001", "\002",
					"\$this->defended_tags('\\2', 'replace')",
					"<nosmile>", "</nosmile>"),
				$text);
		}
		if (!$bPreview)
		{
			$text = preg_replace("#\[cut[\s]*(/\]|\])#is", "<a name=\"cut\"></a>", $text);
		}

		if ($allow["SMILES"]=="Y")
		{
			if (count($this->smiles) > 0)
			{
				$arPattern = array();
				$arReplace = array();
				foreach ($this->smiles as $a_id => $row)
				{
					//$code  = str_replace(array(chr(34), chr(39)), array(chr(11), chr(12)), $row["TYPING"]);
					//$image = str_replace(array(chr(34), chr(39)), array(chr(11), chr(12)), $row["IMAGE"]);

					$code = str_replace(Array("'", "<", ">"), Array("\\'", "&lt;", "&gt;"), $row["TYPING"]);
					$patt = preg_quote($code, "/");
					$code = preg_quote(str_replace(array("\x5C"), array("&#092;"), $code));

					$image = preg_quote(str_replace("'", "\\'", $row["IMAGE"]));
					$description = preg_quote(htmlspecialcharsbx($row["DESCRIPTION"], ENT_QUOTES), "/");

					$arPattern[] = "/(?<=[^\w&])$patt(?=.\W|\W.|\W$)/ei".BX_UTF_PCRE_MODIFIER;
					$arReplace[] = "\$this->convert_emoticon('$code', '$image', '$description', '$serverName')";
				}

				if (!empty($arPattern))
					$text = preg_replace($arPattern, $arReplace, ' '.$text.' ');
			}
		}

		if ($this->preg["counter"] > 0)
			$text = str_replace($this->preg["pattern"], $this->preg["replace"], $text);
		$text = str_replace(array(chr(11), chr(12)), array(chr(34), chr(39)), $text);
		return trim($text);
	}

	public static function defended_tags($text, $tag = 'replace')
	{
		switch ($tag) {
			case "replace":
				$this->preg["pattern"][] = "<\017#".$this->preg["counter"].">";
				$this->preg["replace"][] = $text;
				$text = "<\017#".$this->preg["counter"].">";
				$this->preg["counter"]++;
				break;
		}
		return $text;
	}

	public static function killAllTags($text)
	{
		$text = strip_tags($text);
		$text = preg_replace(
			array(
				"/\<(\/?)(quote|code|font|color|video|td|tr|table)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(b|u|i|s|list|code|quote|font|color|url|img|video|td|tr|table)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER),
			"",
			$text);
		return $text;
	}

	public static function convert4mail($text, $arImages = Array())
	{
		$text = Trim($text);
		if (strlen($text)<=0) return "";
		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\[(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\[\/(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\<WBR[\s\/]?\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[(\/?)(color|font|size)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\[url\](\S+?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1)";

		$arPattern[] = "/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1)";

		$arPattern[] = "/\[img\](.+?)\[\/img\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\[(\/?)list\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";
		$text = preg_replace($arPattern, $arReplace, $text);
		$text = str_replace("&shy;", "", $text);

		$dbSite = CSite::GetByID(SITE_ID);
		$arSite = $dbSite -> Fetch();
		$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);
		if (strlen($serverName) <=0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
				$serverName = SITE_SERVER_NAME;
			else
				$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
		}

		while (is_array($arImages) && list($IMAGE_ID, $FILE_ID)=each($arImages))
		{
			$f = CBlogImage::GetByID($IMAGE_ID);
			if($arS = CFile::GetFileArray($FILE_ID))
			{
				if(substr($arS["SRC"], 0, 1) == "/")
					$fileSrc = "http://".$serverName.$arS["SRC"];
				else
					$fileSrc = $arS["SRC"];
				$text = str_replace("[IMG ID=$IMAGE_ID]", htmlspecialcharsbx($f["TITLE"])." (IMG: ".$fileSrc." )", $text);
				$text = str_replace("[img id=$IMAGE_ID]", htmlspecialcharsbx($f["TITLE"])." (IMG: ".$fileSrc." )", $text);
			}
		}

		return $text;
	}

	public static function convert_video($params, $path)
	{
		if (strLen($path) <= 0)
			return "";
		$width = ""; $height = ""; $preview = "";
		preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);

		$params = str_replace(array(chr(11), chr(12)), array("\001", "\002"), $params);
		preg_match("/preview\=\002([^\002]+)\002/is".BX_UTF_PCRE_MODIFIER, $params, $preview);
		if (empty($preview))
			preg_match("/preview\=\001([^\001]+)\001/is".BX_UTF_PCRE_MODIFIER, $params, $preview);

		$width = intval($width[1]);
		$width = ($width > 0 ? $width : 400);
		$height = intval($height[1]);
		$height = ($height > 0 ? $height : 300);
		$preview = trim($preview[1]);
		$preview = (strLen($preview) > 0 ? $preview : "");

		$arFields = Array(
				"PATH" => $path,
				"WIDTH" => $width,
				"HEIGHT" => $height,
				"PREVIEW" => $preview,
		);
		$db_events = GetModuleEvents("blog", "videoConvert");
		if ($arEvent = $db_events->Fetch())
			$video = ExecuteModuleEventEx($arEvent, Array($arFields));

		if(strlen($video) > 0)
			return "<nomodify>".$video."</nomodify>";
		return false;
	}

	public static function convert_emoticon($code = "", $image = "", $description = "", $servername = "")
	{
		if (strlen($code)<=0 || strlen($image)<=0) return;
		$code = stripslashes($code);
		$description = stripslashes($description);
		$image = stripslashes($image);

		$alt = "<\018#".$this->preg["counter"].">";
		$this->preg["pattern"][] = $alt;
		$this->preg["replace"][] = 'alt="smile'.$code.'" title="'.$description.'"';
		$this->preg["counter"]++;

		if ($this->path_to_smile !== false)
			return '<img src="'.$servername.$this->path_to_smile.$image.'" border="0" '.$alt.' />';
		return '<img src="'.$servername.'/bitrix/images/blog/smile/'.$image.'" border="0" '.$alt.' />';
	}

	public static function pre_convert_code_tag ($text = "")
	{
		if (strLen($text)<=0) return;
		$text = str_replace(
			array("&", "<", ">", "[", "]", "\001", "\002"),
			array("&#38;", "&#60;", "&#62;", "&#91;", "&#93;", "&#91;code&#93;", "&#91;/code&#93;"), $text);

		$word_separator = str_replace("\]", "", $this->word_separator);
		$text = preg_replace("'(?<=^|[".$word_separator."]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\011\s\'\012\[\]\{\}])*)'is",
			"[nomodify]\\1[/nomodify]", $text);

		return $text;
	}

	public static function convert_code_tag($text = "", $type = "html", $allowHTML = false)
	{
		if (strLen($text)<=0) return;
		$type = ($type == "rss" ? "rss" : "html");
		$text = str_replace(Array("[nomodify]", "[/nomodify]"), Array("", ""), $text);
		//if(!$allowHTML)
		//{

			$text = str_replace(
				array("<", ">", "\\r", "\\n", "\\", "[", "]", "\001", "\002", "  ", "\t"),
				array("&#60;", "&#62;", "&#92;r", "&#92;n", "&#92;", "&#91;", "&#93;", "&#91;code&#93;", "&#91;/code&#93;", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"), $text);

			$text = stripslashes($text);

		//}

		if ($this->code_open == $this->code_closed && $this->code_error == 0)
		{
				$this->preg["pattern"][] = "<\017#".$this->preg["counter"].">";
				$this->preg["replace"][] = $this->convert_open_tag('code', $type).$text.$this->convert_close_tag('code', $type);
				$text = "<\017#".$this->preg["counter"].">";
				$this->preg["counter"]++;
		}

		return $text;
	}

	public static function convert_quote_tag($text = "", $type = "html")
	{
		if (strlen($text)<=0) return;
		$txt = $text;
		$type = ($type == "rss" ? "rss" : "html");

		$txt = preg_replace(
			array(
				"/\[quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER,
				"/\[\/quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER),
			array(
				"\$this->convert_open_tag('quote', \$type)",
				"\$this->convert_close_tag('quote', \$type)"), $txt);

		if (($this->quote_open==$this->quote_closed) && ($this->quote_error==0))
			return $txt;
		return $text;
	}

	public static function convert_open_tag($marker = "quote", $type = "html")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$type = ($type == "rss" ? "rss" : "html");

		$this->{$marker."_open"}++;
		if ($type == "rss")
			return "\n====".$marker."====\n";
		return "<div class='blog-post-".$marker."'><span>".GetMessage("BLOG_".ToUpper($marker))."</span><table class='blog".$marker."'><tr><td>".$text;
	}

	public static function convert_close_tag($marker = "quote", $type = "html")
	{
		$marker = (strToLower($marker) == "code" ? "code" : "quote");
		$type = ($type == "rss" ? "rss" : "html");

		if ($this->{$marker."_open"} == 0)
		{
			$this->{$marker."_error"}++;
			return;
		}
		$this->{$marker."_closed"}++;

		if ($type == "rss")
			return "\n=============\n";
		return "</td></tr></tbody></table></div>";
	}

	public static function convert_image_tag($url = "", $type = "html", $serverName="", $params = "")
	{
		$url = trim($url);
		if (strlen($url)<=0) return;

		$type = (strToLower($type) == "rss" ? "rss" : "html");
		$extension = preg_replace("/^.*\.(\S+)$/".BX_UTF_PCRE_MODIFIER, "\\1", $url);
		$extension = strtolower($extension);
		$extension = preg_quote($extension, "/");

		preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);
		$width = intval($width[1]);
		$height = intval($height[1]);

		$bErrorIMG = False;
		if (preg_match("/[?&;]/".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/$extension(\||\$)/".BX_UTF_PCRE_MODIFIER, $this->allow_img_ext))
			$bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/^(http|https|ftp|\/)/i".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = True;

		if ($bErrorIMG)
			return "[img]".$url."[/img]";

		$strPar = "";
		if($width > 0)
		{
			if($width > $this->imageWidth)
			{
				$height = IntVal($height * ($this->imageWidth / $width));
				$width = $this->imageWidth;
			}
		}
		if($height > 0)
		{
			if($height > $this->imageHeight)
			{
				$width = IntVal($width * ($this->imageHeight / $height));
				$height = $this->imageHeight;
			}
		}
		if($width > 0)
			$strPar = " width=\"".$width."\"";
		if($height > 0)
			$strPar .= " height=\"".$height."\"";

		if(strlen($serverName) <= 0 || preg_match("/^(http|https|ftp)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
			return '<img src="'.$url.'" border="0"'.$strPar.' />';
		else
			return '<img src="'.$serverName.$url.'" border="0"'.$strPar.' />';
	}

	public static function convert_blog_image($p1 = "", $imageId = "", $p2 = "", $type = "html", $serverName="")
	{
		$imageId = IntVal($imageId);
		if($imageId <= 0)
			return;

		$res = "";
		if(IntVal($this->arImages[$imageId]) > 0)
		{
			if($f = CBlogImage::GetByID($imageId))
			{
				if($db_img_arr = CFile::GetFileArray($this->arImages[$imageId]))
				{
					$strImage = $db_img_arr["SRC"];

					$strPar = "";
					preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $width);
					preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p1, $height);
					$width = intval($width[1]);
					$height = intval($height[1]);

					if($width <= 0)
					{
						preg_match("/width\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $width);
						$width = intval($width[1]);
					}
					if($height <= 0)
					{
						preg_match("/height\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $p2, $height);
						$height = intval($height[1]);
					}

					if(IntVal($width) <= 0)
						$width = $db_img_arr["WIDTH"];
					if(IntVal($height) <= 0)
						$height= $db_img_arr["HEIGHT"];

					if($width > $this->imageWidth || $height > $this->imageHeight)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$db_img_arr,
							array("width" => $this->imageWidth, "height" => $this->imageHeight),
							BX_RESIZE_IMAGE_PROPORTIONAL,
							true
						);
						if(substr($arFileTmp["src"], 0, 1) == "/")
							$strImage = $serverName.$arFileTmp["src"];
						else
							$strImage = $arFileTmp["src"];
						$width = $arFileTmp["width"];
						$height = $arFileTmp["height"];
					}


					$strPar = ' width="'.$width.'" height="'.$height.'"';

					$res = '<img src="'.$strImage.'" title="'.$f["TITLE"].'" border="0"'.$strPar.'/>';
				}
			}
		}
		return $res;
	}

	public static function convert_font_attr($attr, $value = "", $text = "")
	{
		if (strlen($text)<=0) return "";
		if (strlen($value)<=0) return $text;

		if ($attr == "size")
		{
			$count = count($this->arFontSize);
			if ($count <= 0)
				return $text;
			$value = intVal($value >= $count ? ($count - 1) : $value);
			return '<span style="font-size:'.$this->arFontSize[$value].'%;">'.$text.'</span>';
		}
		else if ($attr == 'color')
		{
			$value = preg_replace("/[^\w#]/", "" , $value);
			return '<span style="color:'.$value.'">'.$text.'</span>';
		}
		else if ($attr == 'font')
		{
			$value = preg_replace("/[^\w]/", "" , $value);
			return '<span style="font-family:'.$value.'">'.$text.'</span>';
		}
	}
	// Only for public using
	public static function wrap_long_words($text="")
	{
		if ($this->MaxStringLen > 0 && !empty($text))
		{
			$text = str_replace(array(chr(11), chr(12), chr(34), chr(39)), array("", "", chr(11), chr(12)), $text);
			$text = preg_replace("/(?<=^|\>)([^\<]+)(?=\<|$)/ies".BX_UTF_PCRE_MODIFIER, "\$this->part_long_words('\\1')", $text);
			$text = str_replace(array(chr(11), chr(12)), array(chr(34), chr(39)), $text);
		}
		return $text;
	}

	public static function part_long_words($str)
	{
		$word_separator = $this->word_separator;
		if (($this->MaxStringLen > 0) && (strLen(trim($str)) > 0))
		{
			$str = str_replace(
				array(chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8),
					"&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;",
					chr(34), chr(39)),
				array("", "", "", "", "", "", "", "",
					chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6),
					chr(7), chr(8)),
				$str);
			$str = preg_replace("/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/ise".BX_UTF_PCRE_MODIFIER,
				"\$this->cut_long_words('\\2')", $str);

			$str = str_replace(
				array(chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", chr(34), chr(39), "<WBR/>", "<WBR/>", "&shy;"),
				$str);
		}
		return $str;
	}

	public static function cut_long_words($str)
	{
		if (($this->MaxStringLen > 0) && (strLen($str) > 0))
			$str = preg_replace("/([^ \n\r\t\x01]{".$this->MaxStringLen."})/is".BX_UTF_PCRE_MODIFIER, "\\1<WBR/>&shy;", $str);
		return $str;
	}

	public static function convert_anchor_tag($url, $text, $pref="")
	{
		if(strlen(trim($text)) <= 0)
			$text = $url;
		$bCutUrl = false;
		$text = str_replace("\\\"", "\"", $text);
		$end = "";
		if (preg_match("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, $url, $match))
		{
			$end = $match[1];
			$url = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $url);
			$text = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $text);
		}
		if (preg_match("/\[\/(quote|code)/i", $url))
			return $url;
		$url = preg_replace(
			array(
				"/&amp;/".BX_UTF_PCRE_MODIFIER,
				"/javascript:/i".BX_UTF_PCRE_MODIFIER,
				"/[".chr(12)."\']/".BX_UTF_PCRE_MODIFIER),
			array(
				"&",
				"java script&#58; ",
				"%27") ,
			$url);
		if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
			$url = "http://".$url;
		if (!preg_match("/^((http|https|news|ftp|aim):\/\/[-_:.a-z0-9@]+)*([^\"\'\011\012])+$/i".BX_UTF_PCRE_MODIFIER, $url))
			return $pref.$text." (".$url.")".$end;

		if (preg_match("/^<img\s+src/i".BX_UTF_PCRE_MODIFIER, $text))
			$bCutUrl = False;
		$text = preg_replace(
			array("/&amp;/i".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array("&", "javascript&#58; "), $text);
		if ($bCutUrl && strlen($text) < 55)
			$bCutUrl = False;
		if ($bCutUrl && !preg_match("/^(http|ftp|https|news):\/\//i".BX_UTF_PCRE_MODIFIER, $text))
			$bCutUrl = False;

		if ($bCutUrl)
		{
			$stripped = preg_replace("/^(http|ftp|https|news):\/\/(\S+)$/i".BX_UTF_PCRE_MODIFIER, "\\2", $text);
			$uri_type = preg_replace("/^(http|ftp|https|news):\/\/(\S+)$/i".BX_UTF_PCRE_MODIFIER, "\\1", $text);
			$text = $uri_type.'://'.substr($stripped, 0, 30).'...'.substr($stripped, -10);
		}
		return $pref.'<a href="'.$url.'" target="_blank">'.$text.'</a>'.$end;
		//return $pref.(COption::GetOptionString("blog", "parser_nofollow", "N") == "Y" ? '<noindex>' : '').'<a href="'.$url.'" target="_blank"'.(COption::GetOptionString("blog", "parser_nofollow", "N") == "Y" ? ' rel="nofollow"' : '').'>'.$text.'</a>'.(COption::GetOptionString("blog", "parser_nofollow", "N") == "Y" ? '</noindex>' : '').$end;
	}

	public static function convert_to_rss($text, $arImages = Array(), $arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N"), $bPreview = true)
	{
		$text = $this->convert($text, $bPreview, $arImages, $arAllow, "rss");
		return trim($text);
	}
}
?>
