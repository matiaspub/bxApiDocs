<?
class CAdminSaleList extends CAdminList
{
	public function AddAdminContextMenu($aContext=array(), $bShowExcel=true, $bShowSettings=true)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$aAdditionalMenu = array();

		if($bShowSettings)
		{
			$link = DeleteParam(array("mode"));
			$link = $APPLICATION->GetCurPage()."?mode=settings".($link <> ""? "&".$link:"");
			$aAdditionalMenu[] = array(
				"TEXT"=>GetMessage("admin_lib_context_sett"),
				"TITLE"=>GetMessage("admin_lib_context_sett_title"),
				"ONCLICK"=>$this->table_id.".ShowSettings('".CUtil::JSEscape($link)."')",
				"GLOBAL_ICON"=>"adm-menu-setting",
			);
		}

		if($bShowExcel)
		{
			$link = DeleteParam(array("mode"));
			$link = $APPLICATION->GetCurPage()."?mode=excel".($link <> ""? "&".$link:"");
			$aAdditionalMenu[] = array(
				"TEXT"=>"Excel",
				"TITLE"=>GetMessage("admin_lib_excel"),
				//"LINK"=>htmlspecialcharsbx($link),
				"ONCLICK"=>"javascript:exportData('excel');",
				"GLOBAL_ICON"=>"adm-menu-excel",
			);
		}

		if(count($aContext)>0 || count($aAdditionalMenu) > 0)
			$this->context = new CAdminContextMenuList($aContext, $aAdditionalMenu);
	}
}
