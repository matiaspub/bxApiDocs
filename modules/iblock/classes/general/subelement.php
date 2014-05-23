<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
IncludeModuleLangFile(__FILE__);

class CAdminSubSorting extends CAdminSorting
{
	var $list_url;

	function CAdminSubSorting($table_id, $by_initial=false, $order_initial=false, $by_name="by", $ord_name="order", $list_url)
	{
		$this->by_name = $by_name;
		$this->ord_name = $ord_name;
		$this->table_id = $table_id;
		$this->by_initial = $by_initial;
		$this->order_initial = $order_initial;

		$this->list_url = $list_url;
		if ('' == $this->list_url)
			$this->list_url = $GLOBALS["APPLICATION"]->GetCurPage();
//TODO: need parameters in url for md5?
		$uniq = md5($this->list_url);

		$aOptSort = array();
		if(isset($GLOBALS[$this->by_name]))
			$_SESSION["SESS_SORT_BY"][$uniq] = $GLOBALS[$this->by_name];
		elseif(isset($_SESSION["SESS_SORT_BY"][$uniq]))
			$GLOBALS[$this->by_name] = $_SESSION["SESS_SORT_BY"][$uniq];
		else
		{
			$aOptSort = CUserOptions::GetOption("list", $this->table_id, array("by"=>$by_initial, "order"=>$order_initial));
			if(!empty($aOptSort["by"]))
				$GLOBALS[$this->by_name] = $aOptSort["by"];
			elseif($by_initial !== false)
				$GLOBALS[$this->by_name] = $by_initial;
		}

		if(isset($GLOBALS[$this->ord_name]))
			$_SESSION["SESS_SORT_ORDER"][$uniq] = $GLOBALS[$this->ord_name];
		elseif(isset($_SESSION["SESS_SORT_ORDER"][$uniq]))
			$GLOBALS[$this->ord_name] = $_SESSION["SESS_SORT_ORDER"][$uniq];
		else
		{
			if(empty($aOptSort["order"]))
				$aOptSort = CUserOptions::GetOption("list", $this->table_id, array("order"=>$order_initial));
			if(!empty($aOptSort["order"]))
				$GLOBALS[$this->ord_name] = $aOptSort["order"];
			elseif($order_initial !== false)
				$GLOBALS[$this->ord_name] = $order_initial;
		}
	}

	function Show($text, $sort_by, $alt_title = false, $baseCssClass = "")
	{
		$ord = "asc";
		$class = "";
		$title = GetMessage("admin_lib_sort_title")." ".($alt_title?$alt_title:$text);
		if(strtolower($GLOBALS[$this->by_name]) == strtolower($sort_by))
		{
			if(strtolower($GLOBALS[$this->ord_name]) == "desc")
			{
				$class = "-down";
				$title .= " ".GetMessage("admin_lib_sort_down");
			}
			else
			{
				$class = "-up";
				$title .= " ".GetMessage("admin_lib_sort_up");
				$ord = "desc";
			}
		}

		$path = $this->list_url;
		$sep = (false === strpos($path,'?') ? '?' : '&');
		$url = $path.$sep.$this->by_name."=".$sort_by."&".$this->ord_name."=".($class <> ""? $ord:"");

		return 'class="'.$baseCssClass.' adm-list-table-cell-sort'.$class.'" onclick="'.$this->table_id.'.Sort(\''.htmlspecialcharsbx(CUtil::addslashes($url)).'\', '.($class <> ""? "false" : "true").', arguments);" title="'.$title.'"';
	}
}

class CAdminSubList extends CAdminList
{
/*
 *	list_url - string with params or array:
 *		LINK
 *		PARAMS (array key => value)
 */
	var $strListUrl = '';	// add
	var $strListUrlParams = ''; // add
	var $arListUrlParams = array(); // add
	var $boolNew = false; // add
	var $arFieldNames = array(); // add
	var $arHideHeaders = array(); // add

	function CAdminSubList($table_id, $sort=false,$list_url,$arHideHeaders = false)
	{
		global $APPLICATION;

		$this->bPublicMode = defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1;
		$arJSDescr = array(
			'js' => '/bitrix/js/iblock/subelement.js',
			'rel' => array('admin_interface')
		);
		if ($this->bPublicMode)
		{
			$arJSDescr['css'] = '/bitrix/panel/iblock/sub-public.css';
		}

		CJSCore::RegisterExt('subelement', $arJSDescr);
		CJSCore::Init(array("subelement"));

		$this->strListUrlParams = '';
		$this->arListUrlParams = array();

		if (is_array($list_url) && isset($list_url['LINK']))
		{
			$this->strListUrl = $list_url['LINK'];
			$this->__ParseListUrl(true);
			if (isset($list_url['PARAMS']))
				$this->__SetListUrlParams($list_url['PARAMS']);
		}
		else
		{
			$this->strListUrl = $list_url;
			$this->__ParseListUrl(true);
		}
		if ('' == $this->strListUrl)
		{
			$this->strListUrl = $APPLICATION->GetCurPageParam();
			$this->__ParseListUrl(true);
		}
		if ($this->bPublicMode)
		{
			$this->__AddListUrlParams('bxpublic', 'Y');
		}

		if (false == $sort)
		{
			$sort = new CAdminSubSorting($table_id, "id", "asc",'by','order',$this->GetListUrl(true));
		}
		else
		{
			$sort->list_url = $this->GetListUrl(true);
		}
		$this->CAdminList($table_id,$sort);

		$this->SetBaseFieldNames();
		if (is_array($arHideHeaders) && !empty($arHideHeaders))
		{
			$this->arHideHeaders = $arHideHeaders;
		}
	}

	function GetListUrl($boolFull = false)
	{
		return $this->strListUrl.(true == $boolFull && '' != $this->strListUrlParams ? '?'.$this->strListUrlParams : '');
	}

	function __UpdateListUrlParams()
	{
		$this->strListUrlParams = '';
		if (!empty($this->arListUrlParams))
		{
			foreach ($this->arListUrlParams as $key => $value)
				$this->strListUrlParams .= $key.'='.$value.'&';
			$this->strListUrlParams = substr($this->strListUrlParams,0,-1);
		}
	}

	function __ClearListUrlParams()
	{
		$this->arListUrlParams = array();
		$this->strListUrlParams = '';
	}

	function __AddListUrlParams($strKey,$strValue)
	{
		if ('' != $strKey)
		{
			$this->arListUrlParams[$strKey] = $strValue;
			$this->__UpdateListUrlParams();
		}
	}

	function __DeleteListUrlParams($mxKey)
	{
		if (is_array($mxKey))
		{
			foreach ($mxKey as $value)
				if (('' != $value) && array_key_exists($value,$this->arListUrlParams))
					unset($this->arListUrlParams[$value]);
		}
		elseif (('' != $mxKey) && array_key_exists($mxKey,$this->arListUrlParams))
		{
			unset($this->arListUrlParams[$mxKey]);
		}
		$this->__UpdateListUrlParams();
	}

	function __SetListUrlParams($mxParams,$boolClear = false)
	{
		if (true == $boolClear)
			$this->arListUrlParams = array();
		if (!is_array($mxParams))
		{
			$arParams = array();
			parse_str($mxParams,$arParams);
			$mxParams = (true == is_array($arParams) ? $arParams : array());
		}
		foreach ($mxParams as $key => $value)
			if ('' != $key)
				$this->arListUrlParams[$key] = $value;

		$this->__UpdateListUrlParams();
	}

	function __ParseListUrl($boolClear = false)
	{
		$mxPos = strpos($this->strListUrl,'?');
		if (false !== $mxPos)
		{
			$this->__SetListUrlParams(substr($this->strListUrl,$mxPos+1),$boolClear);
			$this->strListUrl = substr($this->strListUrl,0,$mxPos);
		}
	}

	function AddHideHeader($strID)
	{
		$strID = trim($strID);
		if ('' != $strID)
		{
			if (!in_array($strID, $this->arHideHeaders))
				$this->arHideHeaders[] = $strID;
		}
	}

	//id, name, content, sort, default
	function AddHeaders($aParams)
	{
		if (isset($_REQUEST['showallcol']) && $_REQUEST['showallcol'])
			$_SESSION['SHALL'] = ($_REQUEST['showallcol'] == 'Y');

		$aOptions = CUserOptions::GetOption("list", $this->table_id, array());

		$aColsTmp = explode(",", $aOptions["columns"]);
		$aCols = array();
		foreach($aColsTmp as $col)
		{
			$col = trim($col);
			if (('' != $col) && !in_array($col, $this->arHideHeaders))
				$aCols[] = $col;
		}

		$bEmptyCols = empty($aCols);
		foreach ($aParams as $param)
		{
			$param["__sort"] = -1;
			if (!in_array($param["id"], $this->arHideHeaders))
			{
				$this->aHeaders[$param["id"]] = $param;
				if (
					(isset($_SESSION['SHALL']) && $_SESSION['SHALL'])
					|| ($bEmptyCols && $param["default"] == true) || in_array($param["id"], $aCols)
				)
				{
					$this->arVisibleColumns[] = $param["id"];
				}
			}
		}

		$aAllCols = null;
		if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "subsettings")
			$aAllCols = $this->aHeaders;

		if(!$bEmptyCols)
		{
			foreach ($aCols as $i => $col)
				if (isset($this->aHeaders[$col]))
					$this->aHeaders[$col]["__sort"] = $i;
			uasort($this->aHeaders, create_function('$a, $b', 'if($a["__sort"] == $b["__sort"]) return 0; return ($a["__sort"] < $b["__sort"])? -1 : 1;'));
		}

		foreach($this->aHeaders as $id=>$arHeader)
		{
			if (in_array($id, $this->arVisibleColumns) && !in_array($id, $this->arHideHeaders))
				$this->aVisibleHeaders[$id] = $arHeader;
		}

		if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "subsettings")
			$this->ShowSettings($aAllCols, $aCols, $aOptions);
	}

	function AddVisibleHeaderColumn($id)
	{
		if (!in_array($id, $this->arVisibleColumns) && !in_array($id,$this->arHideHeaders))
			$this->arVisibleColumns[] = $id;
	}

	function AddAdminContextMenu($aContext=array(), $bShowExcel=true, $bShowSettings=true)
	{
		$aAdditionalMenu = array();

		if($bShowSettings)
		{
			$this->__AddListUrlParams('mode','subsettings');
			$aAdditionalMenu[]= array(
				"TEXT"=>GetMessage("admin_lib_context_sett"),
				"TITLE"=>GetMessage("admin_lib_context_sett_title"),
				"ONCLICK"=>$this->table_id.".ShowSettings('".CUtil::JSEscape($this->GetListUrl(true))."')",
				"ICON"=>"btn_sub_settings",
			);
			$this->__DeleteListUrlParams('mode');
		}
		if($bShowExcel)
		{
			$this->__AddListUrlParams('mode','excel');
			$aAdditionalMenu[] = array(
				"TEXT"=>"Excel",
				"TITLE"=>GetMessage("admin_lib_excel"),
				"ONCLICK"=>"location.href='".htmlspecialcharsbx($this->GetListUrl(true))."'",
				"ICON"=>"btn_sub_excel",
			);
			$this->__DeleteListUrlParams('mode');
		}

		if(count($aContext)>0 || count($aAdditionalMenu) > 0)
			$this->context = new CAdminSubContextMenuList($aContext, $aAdditionalMenu);
	}

	function GroupAction()
	{
		if(!empty($_REQUEST['action_button']))
			$_REQUEST['action'] = $_REQUEST['action_button'];

		if(!isset($_REQUEST['action']) || !check_bitrix_sessid())
			return false;

		if($_REQUEST['action_button']=="edit")
		{
			if(isset($_REQUEST['SUB_ID']))
			{
				if(!is_array($_REQUEST['SUB_ID']))
					$arID = Array($_REQUEST['SUB_ID']);
				else
					$arID = $_REQUEST['SUB_ID'];

				$this->arEditedRows = $arID;
				$this->bEditMode = true;
			}
			return false;
		}

		$arID = Array();
		if($_REQUEST['action_sub_target']!='selected')
		{
			if(!is_array($_REQUEST['SUB_ID']))
				$arID = Array($_REQUEST['SUB_ID']);
			else
				$arID = $_REQUEST['SUB_ID'];
		}
		else
			$arID = Array('');

		return $arID;
	}

	function ActionPost($url = false)
	{
		return $this->table_id.".FormSubmit();";
	}

	function ActionDoGroup($id, $action_id, $add_params='')
	{
		$strParams = "SUB_ID=".urlencode($id)
			."&action=".urlencode($action_id)
			."&lang=".urlencode(LANGUAGE_ID)
			."&".bitrix_sessid_get()
			.($add_params<>""? "&".$add_params: "")
		;
		$strUrl = $this->GetListUrl(true).('' != $this->strListUrlParams ? '&' : '?').$strParams;
		return $this->table_id.".GetAdminList('".CUtil::JSEscape($strUrl)."');";
	}

	function &AddRow($id = false, $arRes = Array(), $link = false, $title = false, $boolBX = false)
	{
		$row = new CAdminSubListRow($this->aHeaders, $this->table_id);
		$row->id = $id;
		$row->arRes = $arRes;
		$row->link = $link;
		$row->title = $title;
		$row->pList = &$this;
		$row->boolBX = $boolBX;

		if($id)
		{
			if($this->bEditMode && in_array($id, $this->arEditedRows))
				$row->bEditMode = true;
			elseif(in_array($id, $this->arUpdateErrorIDs))
				$row->bEditMode = true;
		}

		$this->aRows[] = &$row;
		return $row;
	}

	function Display()
	{
		global $APPLICATION;

		foreach(GetModuleEvents("main", "OnAdminSubListDisplay", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$this));
		}

		echo '<div id="form_'.$this->table_id.'" class="adm-sublist">';

		if($this->bEditMode && !$this->bCanBeEdited)
			$this->bEditMode = false;

		$boolCloseMessage = true;
		$errmsg = '';
		foreach ($this->arFilterErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err;
		foreach ($this->arUpdateErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err[0];
		foreach ($this->arGroupErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err[0];
		if($errmsg<>'')
		{
			CAdminSubMessage::ShowMessage(array("MESSAGE"=>GetMessage("admin_lib_error"), "DETAILS"=>$errmsg, "TYPE"=>"ERROR"));
			$boolCloseMessage = false;
		}

		$successMessage = '';
		for ($i = 0, $cnt = count($this->arActionSuccess); $i < $cnt; $i++)
			$successMessage .= ($successMessage != '' ? '<br>' : '').$this->arActionSuccess[$i];
		if ($successMessage != '')
		{
			CAdminSubMessage::ShowMessage(array("MESSAGE" => GetMessage("admin_lib_success"), "DETAILS" => $successMessage, "TYPE" => "OK"));
			$boolCloseMessage = false;
		}

		if ($this->bPublicMode && $boolCloseMessage)
		{
			echo '<script type="text/javascript">top.BX.WindowManager.Get().hideNotify();</script>';
		}

		echo $this->sPrologContent;

		if($this->sContent===false)
		{
			echo '<div class="adm-list-table-wrap'.($this->context ? '' : ' adm-list-table-without-header').(count($this->arActions)<=0 && !$this->bCanBeEdited ? ' adm-list-table-without-footer' : '').'">';
		}

		if ($this->context)
			$this->context->Show();

		//!!! insert filter's hiddens
		echo bitrix_sessid_post();

		if($this->sContent!==false)
		{
			echo $this->sContent;
			return;
		}

		$bShowSelectAll = (count($this->arActions)>0 || $this->bCanBeEdited);

		$this->bShowActions = false;
		foreach($this->aRows as $row)
		{
			if(!empty($row->aActions))
			{
				$this->bShowActions = true;
				break;
			}
		}

		$colSpan = 0;

echo '<table class="adm-list-table" id="'.$this->table_id.'">
	<thead>
		<tr class="adm-list-table-header">';

		if($bShowSelectAll)
		{
			echo '<td class="adm-list-table-cell adm-list-table-checkbox" onclick="this.firstChild.firstChild.click(); return BX.PreventDefault(event);"><div class="adm-list-table-cell-inner"><input class="adm-checkbox adm-designed-checkbox" type="checkbox" id="'.$this->table_id.'_check_all" '.($this->bEditMode ? 'disabled' : 'onclick="'.$this->table_id.'.SelectAllRows(this); return BX.eventCancelBubble(event);"').' title="'.GetMessage("admin_lib_list_check_all").'" /><label for="'.$this->table_id.'_check_all" class="adm-designed-checkbox-label"></label></div></td>';
			$colSpan++;
		}
		if($this->bShowActions)
		{
			echo '<td class="adm-list-table-cell adm-list-table-popup-block" title="'.GetMessage("admin_lib_list_act").'"><div class="adm-list-table-cell-inner"></div></td>';
			$colSpan++;
		}
		foreach($this->aHeaders as $column_id=>$header)
		{
			if(!in_array($column_id, $this->arVisibleColumns))
				continue;

			$bSort = $this->sort && !empty($header["sort"]);

			if ($bSort)
				//$attrs = $this->sort->Show($header["content"], $header["sort"], $header["title"], "adm-list-table-cell");
				$attrs = $this->sort->Show($header["content"], $header["sort"], $header["title"], "adm-list-table-cell");
			else
				$attrs = 'class="adm-list-table-cell"';


			echo '<td '.$attrs.'>
				<div class="adm-list-table-cell-inner">'.$header["content"].'</div>'.($bSort ? '<span class="adm-sub-sort"></span>' : '').'
			</td>';

			$colSpan++;
		}
		echo '</tr></thead><tbody>';

		if(!empty($this->aRows))
		{
			foreach($this->aRows as $row)
			{
				$row->Display();
			}
		}
		elseif(!empty($this->aHeaders))
		{
			echo '<tr><td colspan="'.$colSpan.'" class="adm-list-table-cell adm-list-table-empty">'.GetMessage("admin_lib_no_data").'</td></tr>';
		}

		echo '</tbody></table>';

		$this->ShowActionTable();

		echo $this->sEpilogContent;
		echo '</div>';
		echo $this->sNavText;
	}

	function ShowActionTable()
	{
		global $APPLICATION;
		if(count($this->arActions)<=0 && !$this->bCanBeEdited)
			return;

?>
<div class="adm-list-table-footer" id="<?=$this->table_id?>_footer<?=$this->bEditMode || count($this->arUpdateErrorIDs)>0 ? '_edit' : ''?>">
	<input type="hidden" name="action_button" id="<?=$this->table_id.'_action_button'; ?>" value="" />
<?

		if($this->bEditMode || count($this->arUpdateErrorIDs)>0)
		{
			echo '<input type="button" name="save_sub" id="'.$this->table_id.'_save_sub_button" value="'.GetMessage("admin_lib_list_edit_save").'" title="'.GetMessage("admin_lib_list_edit_save_title").'" onclick="'.$this->table_id.'.ExecuteFormAction(\'SAVE_BUTTON\');" />
			<input type="button" name="cancel_sub" id="'.$this->table_id.'_cancel_sub_button" value="'.GetMessage("admin_lib_list_edit_cancel").'" title="'.GetMessage("admin_lib_list_edit_cancel_title").'" onclick="'.$this->ActionAjaxReload($this->GetListUrl(true)).'"/>';
		}
		else
		{
			if($this->arActionsParams["disable_action_sub_target"] <> true)
			{
				echo '<span class="adm-selectall-wrap"><input type="checkbox" class="adm-checkbox adm-designed-checkbox" name="action_sub_target" id="'.$this->table_id.'_action_sub_target" value="selected" onclick="if(this.checked && !confirm(\''.CUtil::JSEscape(GetMessage("admin_lib_list_edit_for_all_warn")).'\')) {this.checked=false;} '.$this->table_id.'.EnableActions();" title="'.GetMessage("admin_lib_list_edit_for_all").'" /><label title="'.GetMessage("admin_lib_list_edit_for_all").'" for="action_sub_target" class="adm-checkbox-label"><?=GetMessage("admin_lib_list_for_all");?></label></span>';
			}
			$this->bCanBeDeleted = array_key_exists("delete", $this->arActions);

			if ($this->bCanBeEdited || $this->bCanBeDeleted)
			{
				echo '
	<span class="adm-table-item-edit-wrap'.(!$this->bCanBeEdited || !$this->bCanBeDeleted ? ' adm-table-item-edit-single' : '').'">';
				if($this->bCanBeEdited):
					echo '<a href="javascript:void(0)" class="adm-table-btn-edit adm-edit-disable" hidefocus="true" onclick="this.blur();if('.$this->table_id.'.IsActionEnabled(\'edit\')){BX(\''.$this->table_id.'_action_button\').value=\'edit\'; '.htmlspecialcharsbx($this->ActionPost()).'}" title="'.GetMessage("admin_lib_list_edit").'" id="'.$this->table_id.'_action_edit_button"></a>';
				endif;
				if($this->bCanBeDeleted):
					echo '<a href="javascript:void(0);" class="adm-table-btn-delete adm-edit-disable" hidefocus="true" onclick="this.blur();if('.$this->table_id.'.IsActionEnabled() && confirm((BX(\'action_sub_target\') && BX(\'action_sub_target\').checked ? \''.GetMessage("admin_lib_list_del").'\':\''.GetMessage("admin_lib_list_del_sel").'\'))) {BX(\''.$this->table_id.'_action_button\').value=\'delete\'; '.htmlspecialcharsbx($this->ActionPost()).'}" title="'.GetMessage("admin_lib_list_del_title").'" class="context-button icon action-delete-button-dis" id="'.$this->table_id.'_action_delete_button"></a>';
				endif;
				echo '</span>';
			}

			$list = "";
			$buttons = "";
			$html = "";
			foreach($this->arActions as $k=>$v)
			{
				if($k === "delete")
				{
					continue;
				}
				else
				{
					if(is_array($v))
					{
						if($v["type"] == "button")
						{
							$buttons .= '<input type="button" name="" value="'.htmlspecialcharsbx($v['name']).'" onclick="'.(!empty($v["action"])? str_replace("\"", "&quot;", $v['action']) : 'document.getElementById(\''.$this->table_id.'_action_button\').=\''.htmlspecialcharsbx($v["value"]).'\'; '.htmlspecialcharsbx($this->ActionPost()).'').'" title="'.htmlspecialcharsbx($v["title"]).'" />';
						}
						elseif($v["type"] == "html")
						{
							$html .= '<span class="adm-list-footer-ext">'.$v["value"].'</span>';
						}
						else
						{
							$list .= '<option value="'.htmlspecialcharsbx($v['value']).'"'.($v['action']?' custom_action="'.str_replace("\"", "&quot;", $v['action']).'"':'').'>'.htmlspecialcharsex($v['name']).'</option>';
						}
					}
					else
					{
						$list .= '<option value="'.htmlspecialcharsbx($k).'">'.htmlspecialcharsex($v).'</option>';
					}
				}
			}

			if (strlen($buttons) > 0)
				echo '<span class="adm-list-footer-ext">'.$buttons.'</span>';

			if (strlen($list) > 0):
?>
	<span class="adm-select-wrap">
		<select name="action" id="<?=$this->table_id.'_action'; ?>" class="adm-select"<?=($this->arActionsParams["select_onchange"] <> ""? ' onchange="'.htmlspecialcharsbx($this->arActionsParams["select_onchange"]).'"':'')?>>
			<option value=""><?=GetMessage("admin_lib_list_actions")?></option>
<?=$list?>
		</select>
	</span>
<?
				if (strlen($html) > 0)
					echo $html;

	echo '<input type="button" name="apply_sub" id="'.$this->table_id.'_apply_sub_button" value="'.GetMessage("admin_lib_list_apply").'" onclick="'.$this->table_id.'.ExecuteFormAction(\'ACTION_BUTTON\');" disabled="disabled" class="adm-table-action-button" />';

			endif;
?>
	<span class="adm-table-counter" id="<?=$this->table_id?>_selected_count"><?=GetMessage('admin_lib_checked')?>: <span>0</span></span>
<?
		}
		echo '</div>';
	}

	function DisplayList($boolFlag = true)
	{
		global $APPLICATION;
		$menu = new CAdminPopup($this->table_id."_menu", $this->table_id."_menu",false,array('zIndex' => 4000));
		$menu->Show();

		$tbl = CUtil::JSEscape($this->table_id);
		$aUserOpt = CUserOptions::GetOption("global", "settings");
		echo '
<script type="text/javascript">
var '.$this->table_id.'= new BX.adminSubList("'.$tbl.'", {context_ctrl: '.($aUserOpt["context_ctrl"] == "Y"? "true":"false").'}, "'.$this->GetListUrl(true).'");
function ReloadOffers()
{
	'.$this->ActionAjaxReload($this->GetListUrl(true)).'
}
</script>
';
		echo '<div id="'.$this->table_id.'_result_div">';
		$this->Display();
		echo '</div>';
	}

	function CreateChain()
	{
		return new CAdminChain($this->table_id."_navchain_div", false);
	}

	function ShowChain($chain)
	{
		$this->BeginPrologContent();
		$chain->Show();
		$this->EndPrologContent();
	}

	function CheckListMode()
	{
		if($_REQUEST["mode"]=='list' || $_REQUEST["mode"]=='frame')
		{
			ob_start();
			$this->Display();
			$string = ob_get_contents();
			ob_end_clean();

			if($_REQUEST["mode"]=='frame')
			{
				echo '<html><head>';
//				echo $GLOBALS["adminPage"]->ShowScript();
				echo '</head><body>
<div id="'.$this->table_id.'_result_frame_div">'.$string.'</div>
<script type="text/javascript">
';
				if($this->bEditMode || count($this->arUpdateErrorIDs)>0)
				{
					echo $this->table_id.'._DeActivateMainForm();';
				}
				else
				{
					echo $this->table_id.'._ActivateMainForm();';
				}
				if($this->onLoadScript)
					echo 'w.eval(\''.CUtil::JSEscape($this->onLoadScript).'\');';
				echo '</script></body></html>';
			}
			else
			{
				if($this->onLoadScript)
					echo "<script type=\"text/javascript\">".$this->onLoadScript."</script>";
				echo $string;
			}
			define("ADMIN_AJAX_MODE", true);
			require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
			die();
		}
		elseif($_REQUEST["mode"]=='excel')
		{
			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: filename=".basename($GLOBALS["APPLICATION"]->GetCurPage(), ".php").".xls");
			$this->DisplayExcel();
			require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
			die();
		}
	}

	function SetBaseFieldNames()
	{
		$this->arFieldNames = array(
			array(
				'NAME' => 'SESSID',
				'TYPE' => 'HIDDEN',
			),
		);
	}

	function AddListFieldNames()
	{
		$this->arFieldNames[] = array(
			'NAME' => 'ACTION_BUTTON',
			'TYPE' => 'HIDDEN',
		);
		$this->arFieldNames[] = array(
			'NAME' => 'SUB_ID[]',
			'TYPE' => 'CHECKBOX',
		);
	}

	function SetListFieldNames($boolClear = true)
	{
		$boolClear = (true == $boolClear ? true: false);
		if (true == $boolClear)
			$this->SetBaseFieldNames();
		$this->AddListFieldNames();
	}

	function DeleteFieldNames($arList = array())
	{
		if (false == is_array($arList))
			$arList = array();
		if (false == empty($arList))
		{
			$arTempo = array();
			foreach ($this->arFieldNames as $arName)
			{
				if (false == in_array($arName['NAME'],$arList))
				{
					$arTempo[] = $arName;
				}
			}
			$this->arFieldNames = $arTempo;
			unset($arTempo);
		}
	}

	function GetListFieldNames()
	{
		return $this->arFieldNames;
	}

	function AddFieldNames($strFieldName, $strFieldType)
	{

	}
}

class CAdminSubListRow extends CAdminListRow
{
	var $arFieldNames = array(); //add
	var $boolBX = false; // add

	function CAdminSubListRow(&$aHeaders, $table_id)
	{
		parent::CAdminListRow($aHeaders, $table_id);
	}

	function Display()
	{
		$sDefAction = $sDefTitle = "";
		if(!$this->bEditMode)
		{
			if(!empty($this->link))
			{
				if (true == $this->boolBX)
					$sDefAction = "(new BX.CAdminDialog({
						'content_url': '".$this->link.(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y&".bitrix_sessid_get()."',
						'content_post': 'from_module=iblock',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();";
				else
					$sDefAction = "BX.adminPanel.Redirect([], '".CUtil::JSEscape($this->link)."', event);";
				$sDefTitle = $this->title;
			}
			else
			{
				foreach($this->aActions as $action)
					if($action["DEFAULT"] == true)
					{
				if (true == $this->boolBX)
					$sDefAction = "(new BX.CAdminDialog({
						'content_url': '".CUtil::addslashes($action["ACTION"])."',
						'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y&from_module=iblock&".bitrix_sessid_get()."',
						'draggable': true,
						'resizable': true,
						'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
					})).Show();";
				else
						$sDefAction = $action["ACTION"]
							? htmlspecialcharsbx($action["ACTION"])
							: "BX.adminPanel.Redirect([], '".CUtil::JSEscape($action["LINK"])."', event)"
						;
						$sDefTitle = (!empty($action["TITLE"])? $action["TITLE"]:$action["TEXT"]);
						break;
					}
			}
		}

		$sMenuItems = "";
		if(!empty($this->aActions))
			$sMenuItems = htmlspecialcharsbx(CAdminPopup::PhpToJavaScript($this->aActions));

?>
<tr class="adm-list-table-row<?=(isset($this->aFeatures["footer"]) && $this->aFeatures["footer"] == true? ' footer':'')?><?=$this->bEditMode?' adm-table-row-active' : ''?>"<?=($sMenuItems <> "" ? ' oncontextmenu="return '.$sMenuItems.';"':'');?><?=($sDefAction <> ""? ' ondblclick="'.$sDefAction.'"'.(!empty($sDefTitle)? ' title="'.GetMessage("admin_lib_list_double_click").' '.$sDefTitle.'"':''):'')?>>
<?

		if(count($this->pList->arActions)>0 || $this->pList->bCanBeEdited):
			$check_id = RandString(5);
?>
	<td class="adm-list-table-cell adm-list-table-checkbox adm-list-table-checkbox-hover<?=$this->bReadOnly? ' adm-list-table-checkbox-disabled':''?>"><input type="checkbox" class="adm-checkbox adm-designed-checkbox" name="SUB_ID[]" id="<?=$this->table_id."_".$this->id."_".$check_id;?>" value="<?=$this->id?>" autocomplete="off" title="<?=GetMessage("admin_lib_list_check")?>"<?=$this->bReadOnly? ' disabled="disabled"':''?><?=$this->bEditMode ? ' checked="checked" disabled="disabled"' : ''?> /><label class="adm-designed-checkbox-label adm-checkbox" for="<?=$this->table_id."_".$this->id."_".$check_id;?>"></label></td>
<?
		endif;

		if($this->pList->bShowActions):
			if(!empty($this->aActions)):
?>
	<td class="adm-list-table-cell adm-list-table-popup-block" onclick="BX.adminSubList.ShowMenu(this.firstChild, this.parentNode.oncontextmenu(), this.parentNode);"><div class="adm-list-table-popup" title="<?=GetMessage("admin_lib_list_actions_title")?>"></div></td>
<?
			else:
?>
	<td class="adm-list-table-cell"></td>
<?
			endif;
		endif;

		$bVarsFromForm = ($this->bEditMode && is_array($this->pList->arUpdateErrorIDs) && in_array($this->id, $this->pList->arUpdateErrorIDs));
		foreach($this->aHeaders as $id=>$header_props)
		{
			if(!in_array($id, $this->pList->arVisibleColumns))
				continue;

			$field = $this->aFields[$id];
			if($this->bEditMode && isset($field["edit"]))
			{
				if($bVarsFromForm && $_REQUEST["FIELDS"])
					$val = $_REQUEST["FIELDS"][$this->id][$id];
				else
					$val = $this->arRes[$id];

				$val_old = $this->arRes[$id];

				echo '<td class="adm-list-table-cell'.($header_props['align']?' align-'.$header_props['align']:'').' '.($header_props['valign']?' valign-'.$header_props['valign']:'').'">';
				if(is_array($val_old))
				{
					foreach($val_old as $k=>$v)
						echo '<input type="hidden" name="FIELDS_OLD['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']['.htmlspecialcharsbx($k).']" value="'.htmlspecialcharsbx($v).'">';
				}
				else
				{
					echo '<input type="hidden" name="FIELDS_OLD['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val_old).'">';
				}
				switch($field["edit"]["type"])
				{
					case "checkbox":
						echo '<input type="hidden" name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="N">';
						echo '<input type="checkbox" name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="Y"'.($val=='Y'?' checked':'').'>';
						break;
					case "select":
						echo '<select name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']"'.$this->__AttrGen($field["edit"]["attributes"]).'>';
						foreach($field["edit"]["values"] as $k=>$v)
							echo '<option value="'.htmlspecialcharsbx($k).'" '.($k==$val?' selected':'').'>'.htmlspecialcharsex($v).'</option>';
						echo '</select>';
						break;
					case "input":
						if(!$field["edit"]["attributes"]["size"])
							$field["edit"]["attributes"]["size"] = "10";
						echo '<input type="text" '.$this->__AttrGen($field["edit"]["attributes"]).' name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val).'">';
						break;
					case "calendar":
						if(!$field["edit"]["attributes"]["size"])
							$field["edit"]["attributes"]["size"] = "10";
						echo '<span style="white-space:nowrap;"><input type="text" '.$this->__AttrGen($field["edit"]["attributes"]).' name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val).'">';
						echo CAdminCalendar::Calendar('FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']').'</span>';
						break;
					case "file":
						echo CFileInput::Show(
							'FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']',
							$val,
							$field["edit"]["showInfo"],
							$field["edit"]["inputs"]
						);
						break;
					default:
						echo $field["edit"]['value'];
				}
				echo '</td>';
			}
			else
			{
				if(!is_array($this->arRes[$id]))
					$val = trim($this->arRes[$id]);
				else
					$val = $this->arRes[$id];
								if(isset($field["view"]))
				{
					switch($field["view"]["type"])
					{
						case "checkbox":
							if($val=='Y')
								$val = htmlspecialcharsex(GetMessage("admin_lib_list_yes"));
							else
								$val = htmlspecialcharsex(GetMessage("admin_lib_list_no"));
							break;
						case "select":
							if($field["edit"]["values"][$val])
								$val = htmlspecialcharsex($field["edit"]["values"][$val]);
							break;
						case "file":
							if ($val > 0)
								$val = CFileInput::Show(
									'NO_FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']',
									$val,
									$field["view"]["showInfo"],
									$field["view"]["inputs"]
								);
							else
								$val = '';
							break;
						case "html":
							$val = $field["view"]['value'];
							break;
						default:
							$val = htmlspecialcharsex($val);
							break;
					}
				}
				else
				{
					$val = htmlspecialcharsex($val);
				}

				echo '<td class="adm-list-table-cell',
					(isset($header_props['align']) && $header_props['align']? ' align-'.$header_props['align']: ''),
					(isset($header_props['valign']) && $header_props['valign']? ' valign-'.$header_props['valign']: ''),
					($id === $last_id? ' adm-list-table-cell-last': ''),
				'">';
				echo ((string)$val <> ""? $val: '&nbsp;');
				if(isset($field["edit"]) && $field["edit"]["type"] == "calendar")
					CAdminCalendar::ShowScript();
				echo '</td>';
			}
		}
		echo '</tr>';
	}

	function AddFieldNames($strFieldName,$strFieldType = 'HIDDEN')
	{
		if (0 < strlen($strFieldName))
		{
			if (false == isset($this->arFieldNames[$strFieldName]))
			{
				if (0 == strlen($strFieldType))
					$strFieldType = 'HIDDEN';
				$this->arFieldNames[$strFieldName] = ToUpper($strFieldType);
			}
		}
	}

	function GetFieldNames()
	{
		return $this->arFieldNames;
	}
}

class CAdminSubContextMenu extends CAdminContextMenu
{
	function CAdminSubContextMenu($items, $additional_items = array())
	{
		$this->CAdminContextMenu($items, $additional_items);
	}

	function Show()
	{
		$hkInst = CHotKeys::getInstance();

		foreach(GetModuleEvents("main", "OnAdminSubContextMenuShow", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$this->items));
		}

		$bFirst = true;
		$bNeedSplitClosing = false;
		$bWasPopup = false;
		foreach($this->items as $item)
		{
			if(!empty($item["NEWBAR"]))
				$this->EndBar();

			if($bFirst || !empty($item["NEWBAR"]))
				$this->BeginBar();

			if(!empty($item["NEWBAR"]) || !empty($item['SEPARATOR']))
				continue;

			if ($item['ICON'] != 'btn_sub_list' && !$bNeedSplitClosing)
			{
				$this->BeginRightBar();
				$bNeedSplitClosing = true;
			}

			$this->Button($item, $hkInst);

			$bFirst = false;
		}

		if (!((defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)))
		{
			if (count($this->additional_items) > 0)
			{
				if($bFirst)
				{
					$this->BeginBar();
				}

				$this->Additional();
			}
		}

		if ($bNeedSplitClosing)
			$this->EndRightBar();

		$this->EndBar();
	}

	function GetClassByID($icon_id)
	{
		switch ($icon_id)
		{
			case 'btn_sub_new':
				return 'adm-btn-add';
			case 'btn_sub_copy':
				return 'adm-btn-copy';
			case 'btn_sub_delete':
				return 'adm-btn-delete';
			case 'btn_sub_active':
				return 'adm-btn-active';
		}

		return '';
	}

	function GetActiveClassByID($icon_id)
	{
		return 'adm-btn-active';
	}
}

class CAdminSubContextMenuList extends CAdminSubContextMenu
{
	function BeginBar()
	{
		echo '<div class="adm-list-table-top">';
	}
	function BeginRightBar() {}
	function EndRightBar() {}

	function GetClassByID($icon_id)
	{
		if (substr($icon_id, 0, 7) == 'btn_sub_new')
			return 'adm-btn-save adm-btn-add';
		else
			return parent::GetClassByID($icon_id);

		return '';
	}

	function GetActiveClassByID($icon_id)
	{
		if (substr($icon_id, 0, 7) == 'btn_sub_new')
			return 'adm-btn-save-active';
		else
			return parent::GetActiveClassByID($icon_id);

		return '';
	}
}

class CAdminSubForm extends CAdminForm
{
/*
 *	list_url - string with params or array:
 *		LINK
 *		PARAMS (array key => value)
 *		POST_PARAMS (array key => value)
 */
	var $strListUrl = '';	// add
	var $strListUrlParams = ''; // add
	var $arListUrlParams = array(); // add
	var $strListPostParams = '';
	var $arListPostParams = array();
	var $boolShowSettings = false;

	function CAdminSubForm($name, $tabs, $bCanExpand = true, $bDenyAutosave = false, $list_url = '', $boolShowSettings = false)
	{
		global $APPLICATION;

		$arJSDescr = array(
			'js' => '/bitrix/js/iblock/subelementdet.js',
			'css' => '/bitrix/panel/iblock/sub-detail-public.css',
			'rel' => array('admin_interface')
		);

		CJSCore::RegisterExt('subelementdet', $arJSDescr);

		CUtil::InitJSCore(array("subelementdet"));

		parent::CAdminForm($name, $tabs, $bCanExpand, $bDenyAutosave);

		$this->boolShowSettings = (true == $boolShowSettings);
		$this->SetShowSettings($this->boolShowSettings);

		$this->strListUrlParams = '';
		$this->arListUrlParams = array();

		if ((true == is_array($list_url)) && (true == isset($list_url['LINK'])))
		{
			$this->strListUrl = $list_url['LINK'];
			$this->__ParseListUrl(true);
			if (true == isset($list_url['PARAMS']))
				$this->__SetListUrlParams($list_url['PARAMS']);
		}
		else
		{
			$this->strListUrl = $list_url;
			$this->__ParseListUrl(true);
		}
		if ('' == $this->strListUrl)
		{
			$this->strListUrl = $APPLICATION->GetCurPageParam();
			$this->__ParseListUrl(true);
		}

		if (true == is_array($list_url) && !empty($list_url['POST_PARAMS']))
		{
			$this->__SetListPostParams($list_url['POST_PARAMS'],true);
		}
	}

	public function GetListUrl($boolFull = false)
	{
		return $this->strListUrl.(true == $boolFull && '' != $this->strListUrlParams ? '?'.$this->strListUrlParams : '');
	}

	public function GetListPostParams($boolType = false, $boolJS = false)
	{
		$boolType = (true == $boolType ? true : false);
		if ($boolType)
		{
			$boolJS = (true == $boolJS ? true : false);
			return ($boolJS ? CUtil::PhpToJsObject($this->arListPostParams) : $this->arListPostParams);
		}
		else
		{
			return $this->strListPostParams;
		}
	}

	private function __UpdateListUrlParams()
	{
		$this->strListUrlParams = '';
		if (false == empty($this->arListUrlParams))
		{
			foreach ($this->arListUrlParams as $key => $value)
				$this->strListUrlParams .= $key.'='.$value.'&';
			$this->strListUrlParams = substr($this->strListUrlParams,0,-1);
		}
	}

	private function __ClearListUrlParams()
	{
		$this->arListUrlParams = array();
		$this->strListUrlParams = '';
	}

	private function __AddListUrlParams($strKey,$strValue)
	{
		if ('' != $strKey)
		{
			$this->arListUrlParams[$strKey] = $strValue;
			$this->__UpdateListUrlParams();
		}
	}

	private function __DeleteListUrlParams($mxKey)
	{
		if (true == is_array($mxKey))
		{
			foreach ($mxKey as $value)
				if (('' != $value) && (true == array_key_exists($value,$this->arListUrlParams)))
					unset($this->arListUrlParams[$value]);
		}
		elseif (('' != $mxKey) && (true == array_key_exists($mxKey,$this->arListUrlParams)))
		{
			unset($this->arListUrlParams[$mxKey]);
		}
		$this->__UpdateListUrlParams();
	}

	private function __SetListUrlParams($mxParams,$boolClear = false)
	{
		if (true == $boolClear)
			$this->arListUrlParams = array();

		if (!is_array($mxParams))
		{
			$arParams = array();
			parse_str($mxParams,$arParams);
			$mxParams = (true == is_array($arParams) ? $arParams : array());
		}
		foreach ($mxParams as $key => $value)
			if ('' != $key)
				$this->arListUrlParams[$key] = $value;

		$this->__UpdateListUrlParams();
	}

	private function __ParseListUrl($boolClear = false)
	{
		$mxPos = strpos($this->strListUrl,'?');
		if (false !== $mxPos)
		{
			$this->__SetListUrlParams(substr($this->strListUrl,$mxPos+1),$boolClear);
			$this->strListUrl = substr($this->strListUrl,0,$mxPos);
		}
	}

	private function __UpdateListPostParams()
	{
		$this->strListPostParams = '';
		if (false == empty($this->arListPostParams))
		{
			foreach ($this->arListPostParams as $key => $value)
				$this->strListPostParams .= $key.'='.$value.'&';
			$this->strListPostParams = substr($this->strListPostParams,0,-1);
		}
	}

	private function __ClearListPostParams()
	{
		$this->arListPostParams = array();
		$this->strListPostParams = '';
	}

	private function __AddListPostParams($strKey,$strValue)
	{
		if ('' != $strKey)
		{
			$this->arListPostParams[$strKey] = $strValue;
			$this->__UpdateListPostParams();
		}
	}

	private function __DeleteListPostParams($mxKey)
	{
		if (true == is_array($mxKey))
		{
			foreach ($mxKey as $value)
				if (('' != $value) && (true == array_key_exists($value,$this->arListPostParams)))
					unset($this->arListPostParams[$value]);
		}
		elseif (('' != $mxKey) && (true == array_key_exists($mxKey,$this->arListPostParams)))
		{
			unset($this->arListPostParams[$mxKey]);
		}
		$this->__UpdateListPostParams();
	}

	private function __SetListPostParams($mxParams,$boolClear)
	{
		if (true == $boolClear)
			$this->arListPostParams = array();
		if (!is_array($mxParams))
		{
			$arParams = array();
			parse_str($mxParams,$arParams);
			$mxParams = $arParams;
		}
		foreach ($mxParams as $key => $value)
			if ('' != $key)
				$this->arListPostParams[$key] = $value;

		$this->__UpdateListPostParams();
	}

	function ShowTabButtons()
	{
		$s = '';
		if ($this->bShowSettings)
		{
			$aAdditionalMenu = array();

			$this->__AddListUrlParams('mode', 'settings');
			$strLink = $this->GetListUrl(true);
			$this->__DeleteListUrlParams('mode');

			$aAdditionalMenu[] = array(
				"TEXT"=>GetMessage("admin_lib_menu_settings"),
				"TITLE"=>GetMessage("admin_lib_context_sett_title"),
				"ONCLICK"=>$this->name.".ShowSettings('".htmlspecialcharsex(CUtil::JSEscape($strLink))."')",
				"ICON"=>"btn_settings",
			);

			if($this->bCustomFields)
			{
				if(is_array($_SESSION["ADMIN_CUSTOM_FIELDS"]) && array_key_exists($this->name, $_SESSION["ADMIN_CUSTOM_FIELDS"]))
				{
					$aAdditionalMenu[] = array(
						"TEXT" => GetMessage("admin_lib_sett_sett_enable_text"),
						"TITLE" => GetMessage("admin_lib_sett_sett_enable"),
						"ONCLICK" => $this->name.'.EnableSettings();',
						"ICON" => 'custom-fields-on'
					);
				}
				else
				{
					$aAdditionalMenu[] = array(
						"TEXT" => GetMessage("admin_lib_sett_sett_disable_text"),
						"TITLE" => GetMessage("admin_lib_sett_sett_disable"),
						"ONCLICK" => $this->name.'.DisableSettings();',
						"ICON" => 'custom-fields-off'
					);
				}
			}
			$s .= '<span class="adm-detail-subsettings-cont">';
			if (count($aAdditionalMenu) > 1)
			{
				$sMenuUrl = "BX.adminShowMenu(this, ".htmlspecialcharsbx(CAdminPopupEx::PhpToJavaScript($aAdditionalMenu)).", {active_class: 'bx-settings-btn-active'});";
				$bCustomFieldsOff = is_array($_SESSION["ADMIN_CUSTOM_FIELDS"]) && array_key_exists($this->name, $_SESSION["ADMIN_CUSTOM_FIELDS"]);

				$s .= '<span id="'.$this->name.'_settings_btn" class="adm-detail-subsettings adm-detail-subsettings-arrow'.($bCustomFieldsOff ? '' : ' adm-detail-subsettings-active').'" onclick="'.$sMenuUrl.'"></span>';
			}
			else
			{
				$s .= '<a class="adm-detail-subsettings" href="javascript:void(0)" onclick="'.$aAdditionalMenu[0]['ONCLICK'].';"></a>';
			}
			$s .= '</span>';
		}

		return $s.CAdminTabControl::ShowTabButtons();
	}

	function End()
	{
		$hkInst = CHotKeys::getInstance();

		if(!$this->bButtons)
		{
			while ($this->tabIndex < count($this->tabs))
				$this->BeginNextTab();

			//end previous tab
			$this->EndTab();
			echo '<div class="adm-detail-content-btns-wrap"><div class="adm-detail-content-btns adm-detail-content-btns-empty"></div></div>';
		}
		elseif (!$this->bPublicMode)
		{
			echo '</div></div>';
		}

		if (!$this->bPublicMode)
		{
			echo '
</div></div>
';
		}

		$Execs = $hkInst->GetCodeByClassName("CAdminSubForm");
		echo $hkInst->PrintJSExecs($Execs, $this->name);

		echo '

<input type="hidden" id="'.$this->name.'_active_tab" name="'.$this->name.'_active_tab" value="'.htmlspecialcharsbx($this->selectedTab).'">

<script type="text/javascript">';
		$s = "";
		foreach($this->tabs as $tab)
		{
			$s .= ($s <> ""? ", ":"").
			"{".
			"'DIV': '".$tab["DIV"]."' ".
			($tab["ONSELECT"] <> ""? ", 'ONSELECT': '".CUtil::JSEscape($tab["ONSELECT"])."'":"").
			"}";
		}

		echo 'var '.$this->name.' = new BX.adminSubTabControl("'.$this->name.'", "'.$this->unique_name.'", ['.$s.'], "'.$this->GetListUrl(true).'",'.$this->GetListPostParams(true,true).');';

		if (!$this->bPublicMode)
		{
			$aEditOpt = CUserOptions::GetOption("edit", $this->unique_name, array());
			$aTabOpt = CUserOptions::GetOption("edit", 'admin_tabs', array());

			if($this->bCanExpand && count($this->tabs) > 1)
			{
				if($aEditOpt["expand"] == "on")
				{
					echo '
'.$this->name.'.ToggleTabs();';
				}
			}

			if ($aTabOpt["fix_top"] == "off" && $aEditOpt["expand"] != "on")
			{
				echo '
'.$this->name.'.ToggleFix(\'top\');';
			}

			if ($aTabOpt["fix_bottom"] == "off")
			{
				echo '
'.$this->name.'.ToggleFix(\'bottom\');';
			}
		}
		else
		{
			echo 'window.'.$this->name.'.setPublicMode(true); ';
		}
echo '
</script>
';
		if ($this->bPublicModeBuffer)
		{
			echo '</div>';
			echo '<script type="text/javascript">BX.ready(function() {'.$this->publicObject.'.SwapContent(\''.$this->publicModeBuffer_id.'\');});</script>';
		}
	}
}

class CAdminSubResult extends CAdminResult
{
	var $list_url;
	var $list_url_params;

	function CAdminSubResult($res, $table_id, $list_url)
	{
		$this->list_url = $list_url;
		$this->list_url_params = '';
		$intPos = strpos($this->list_url, '?');
		if (false !== $intPos)
		{
			$this->list_url_params = substr($this->list_url, $intPos+1);
			$this->list_url = substr($this->list_url, 0, $intPos);
		}
		parent::CAdminResult($res, $table_id);
	}

	function NavStart($nPageSize=20, $bShowAll=true, $iNumPage=false)
	{
		$nSize = CAdminSubResult::GetNavSize($this->table_id, $nPageSize, $this->list_url.('' != $this->list_url_params ? '?'.$this->list_url_params : ''));

		if(!is_array($nPageSize))
			$nPageSize = array();

		$nPageSize["nPageSize"] = $nSize;
		if($_REQUEST["mode"] == "excel")
			$nPageSize["NavShowAll"] = true;

		$this->nInitialSize = $nPageSize["nPageSize"];

		parent::NavStart($nPageSize, $bShowAll, $iNumPage);
	}

	function GetNavSize($table_id=false, $nPageSize=20, $list_url)
	{
		$bSess = (CPageOption::GetOptionString("main", "nav_page_in_session", "Y")=="Y");
		if($bSess)
		{
			if(is_array($nPageSize))
				$sNavID = $nPageSize["sNavID"];
			$unique = md5((isset($sNavID)? $sNavID : $list_url));
		}

		if(isset($_REQUEST["SIZEN_".($GLOBALS["NavNum"]+1)]))
		{
			$nSize = intval($_REQUEST["SIZEN_".($GLOBALS["NavNum"]+1)]);
			if($bSess)
				$_SESSION["NAV_PAGE_SIZE"][$unique] = $nSize;
		}
		elseif($bSess && isset($_SESSION["NAV_PAGE_SIZE"][$unique]))
		{
			$nSize = $_SESSION["NAV_PAGE_SIZE"][$unique];
		}
		else
		{
			$aOptions = array();
			if($table_id)
				$aOptions = CUserOptions::GetOption("list", $table_id);
			if(intval($aOptions["page_size"]) > 0)
				$nSize = intval($aOptions["page_size"]);
			else
				$nSize = (is_array($nPageSize)? $nPageSize["nPageSize"]:$nPageSize);
		}
		return $nSize;
	}

	function GetNavPrint($title, $show_allways=true, $StyleText="", $template_path=false, $arDeleteParam=false)
	{
		if($template_path === false)
			$template_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/admin/templates/navigation.php";

		$res = '';
		$add_anchor = $this->add_anchor;

		$sBegin = GetMessage("nav_begin");
		$sEnd = GetMessage("nav_end");
		$sNext = GetMessage("nav_next");
		$sPrev = GetMessage("nav_prev");
		$sAll = GetMessage("nav_all");
		$sPaged = GetMessage("nav_paged");

		$nPageWindow = $this->nPageWindow;

		if(!$show_allways)
		{
			if ($this->NavRecordCount == 0 || ($this->NavPageCount == 1 && $this->NavShowAll == false))
				return;
		}

		$sUrlPath = $this->list_url;
		$strNavQueryString = htmlspecialcharsbx($this->list_url_params);

		if($template_path!==false && !file_exists($template_path) && file_exists($_SERVER["DOCUMENT_ROOT"].$template_path))
			$template_path = $_SERVER["DOCUMENT_ROOT"].$template_path;

		if($this->bDescPageNumbering === true)
		{
			if($this->NavPageNomer + floor($nPageWindow/2) >= $this->NavPageCount)
				$nStartPage = $this->NavPageCount;
			else
			{
				if($this->NavPageNomer + floor($nPageWindow/2) >= $nPageWindow)
					$nStartPage = $this->NavPageNomer + floor($nPageWindow/2);
				else
				{
					if($this->NavPageCount >= $nPageWindow)
						$nStartPage = $nPageWindow;
					else
						$nStartPage = $this->NavPageCount;
				}
			}

			if($nStartPage - $nPageWindow >= 0)
				$nEndPage = $nStartPage - $nPageWindow + 1;
			else
				$nEndPage = 1;
		}
		else
		{
			if($this->NavPageNomer > floor($nPageWindow/2) + 1 && $this->NavPageCount > $nPageWindow)
				$nStartPage = $this->NavPageNomer - floor($nPageWindow/2);
			else
				$nStartPage = 1;

			if($this->NavPageNomer <= $this->NavPageCount - floor($nPageWindow/2) && $nStartPage + $nPageWindow-1 <= $this->NavPageCount)
				$nEndPage = $nStartPage + $nPageWindow - 1;
			else
			{
				$nEndPage = $this->NavPageCount;
				if($nEndPage - $nPageWindow + 1 >= 1)
					$nStartPage = $nEndPage - $nPageWindow + 1;
			}
		}

		$this->nStartPage = $nStartPage;
		$this->nEndPage = $nEndPage;

		if($template_path!==false && file_exists($template_path))
		{
			ob_start();
			include($template_path);
			$res = ob_get_contents();
			ob_end_clean();
			$this->bFirstPrintNav = false;
			return $res;
		}
		else
		{
			return '';
		}
	}
}

class CAdminSubMessage extends CAdminMessage
{
	function CAdminSubMessage($message, $exception=false)
	{
		parent::CAdminMessage($message, $exception);
	}

	function Show()
	{
		if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
		{
			return '<script type="text/javascript">top.BX.WindowManager.Get().ShowError(\''.CUtil::JSEscape(str_replace(array('<br>', '<br />', '<BR>', '<BR />'), "\r\n", htmlspecialcharsback($this->message['DETAILS']? $this->message['DETAILS'] : $this->message['MESSAGE']))).'\');</script>';
		}
		else
		{
			if($this->message["MESSAGE"])
				$title = '<div class="adm-info-message-title">'.$this->_formatHTML($this->message["MESSAGE"]).'</div>';
			else
				$title = '';

			if($this->message["DETAILS"])
				$details = $this->_formatHTML($this->message["DETAILS"]);
			else
				$details = '';

			if($this->message["TYPE"] == "OK")
			{
				$s = '
<div class="adm-info-message-wrap adm-info-message-green">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-icon"></div>
	</div>
</div>
';
			}
			elseif($this->message["TYPE"] == "PROGRESS")
			{
				if ($this->message['PROGRESS_ICON'])
					$title = '<div class="adm-info-message-icon-progress"></div>'.$title;

				$details = str_replace("#PROGRESS_BAR#", $this->_getProgressHtml(), $details);
				$s = '
<div class="adm-info-message-wrap adm-info-message-gray">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-buttons">'.$this->_getButtonsHtml().'</div>
	</div>
</div>
';
			}
			else
			{
				$s = '
<div class="adm-info-message-wrap adm-info-message-red">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-icon"></div>
	</div>
</div>
';
			}

			return $s;
		}
	}

	function ShowOldStyleError($message)
	{
		if(!empty($message))
		{
			$m = new CAdminSubMessage(array("MESSAGE"=>GetMessage("admin_lib_error"), "DETAILS"=>$message, "TYPE"=>"ERROR"));
			echo $m->Show();
		}
	}

	function ShowMessage($message)
	{
		if(!empty($message))
		{
			$m = new CAdminSubMessage($message);
			echo $m->Show();
		}
	}

	function ShowNote($message)
	{
		if(!empty($message))
		{
			$m = new CAdminSubMessage(array("MESSAGE"=>$message, "TYPE"=>"OK"));
			echo $m->Show();
		}
	}
}
?>