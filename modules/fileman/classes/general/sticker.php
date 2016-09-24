<?
IncludeModuleLangFile(__FILE__);

class CSticker
{
	static $Params = null;
	public static $TextParser;

	public static function GetOperations()
	{
		global $USER;
		static $arOp;

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
		$arTasks = Array();
		$res = CTask::GetList(Array('LETTER' => 'asc'), Array('MODULE_ID' => 'fileman', 'BINDING' => 'stickers'));
		while($arRes = $res->Fetch())
		{
			$name = '';
			if ($arRes['SYS'])
				$name = GetMessage('TASK_NAME_'.strtoupper($arRes['NAME']));
			if (strlen($name) == 0)
				$name = $arRes['TITLE'];
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
		$Params['arFilter']['PAGE_URL'] = str_replace(' ', '%20', $Params['arFilter']['PAGE_URL']);
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
			"PAGE_URL" => str_replace(' ', '%20', $APPLICATION->GetCurPage()),
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

		$arFields['PAGE_URL'] = str_replace(' ', '%20', $arFields['PAGE_URL']);

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
		CUtil::InitJSCore(array('window', 'ajax', 'date'));
		$APPLICATION->AddHeadScript('/bitrix/js/fileman/sticker.js', true);
		$APPLICATION->SetAdditionalCSS('/bitrix/js/fileman/sticker.css', true);

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
			"zIndex" => 5000,
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

	public static function BBParseToHTML($text, $bForList = false)
	{
		if ($text != "")
		{
			if (!is_object(self::$TextParser))
			{
				self::$TextParser = new CTextParser();
				self::$TextParser->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "Y", "LIST" => "Y", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			}

			$html = self::$TextParser->convertText($text);

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
		return FormatDate("FULL", $ts);
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
		if ($res !== false && CheckSerializedData($res))
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
