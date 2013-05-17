<?
// define("PUBLIC_AJAX_MODE", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile(__FILE__);

if(!$USER->IsAuthorized()):
?>
<div class="access-container" style="padding:20px"><?echo GetMessage("acc_dialog_access_denied")?></div>
<?
	die();
endif;

$arParams = false;
if(is_array($_REQUEST["arParams"]))
	$arParams = $_REQUEST["arParams"];
	
$arParams["SITE_ID"] = '';
if($_REQUEST["site_id"] <> '')
{
	$res = CSite::GetByID($_REQUEST["site_id"]);
	if($arSite = $res->Fetch())
		$arParams["SITE_ID"] = $arSite["ID"];
}

$access = new CAccess($arParams);

if($_REQUEST["mode"] == "ajax")
{
	echo $access->AjaxRequest(array("provider"=>$_REQUEST["provider"]));
	die();
}

if($_REQUEST["mode"] == "save_lru" && check_bitrix_sessid())
{
	if(is_array($_REQUEST["LRU"]))
		CAccess::SaveLastRecentlyUsed($_REQUEST["LRU"]);
	die();
}
?>
<div class="access-container">
<?
$arHtml = $access->GetFormHtml();
if(!empty($arHtml)):
?>
<div class="access-providers-container">
<?
$first = '';
foreach($arHtml as $ID=>$provider)
	if($provider["SELECTED"] == true)
		$first = $ID;

foreach($arHtml as $ID=>$provider):
	if($first == '')
		$first = $ID;
?>
	<a href="javascript:void(0);" onclick="BX.Access.SelectProvider('<?=$ID?>')" id="access_btn_<?=$ID?>" class="access-provider-button<?if($first == $ID) echo " access-provider-button-selected"?>" hidefocus="true"><?=htmlspecialcharsbx($provider["NAME"])?></a>
	<div class="access-buttons-delimiter"></div>
<?endforeach;?>
</div>

<div class="access-delimiter"></div>

<div class="access-content-container" id="access_content_container">
<?foreach($arHtml as $ID=>$provider):?>
	<div id="access_provider_<?=$ID?>" class="access-content-provider-container"<?if($first <> $ID) echo ' style="display:none"'?>><?=$provider["HTML"]?></div>
<?endforeach;?>
</div>
<?endif?>

<div class="access-selected-container">
	<div class="bx-finder-box-selected-title bx-finder-box-selected-title-no-line" id="access_selected_title"><?=GetMessage("acc_dialog_sel")?>&nbsp;(0)</div>
<?foreach($arHtml as $ID=>$provider):?>
	<div class="bx-finder-box-selected-title" id="access_selected_provider_<?=$ID?>" style="display:none"><?=htmlspecialcharsbx($provider["NAME"])?>&nbsp;<span id="access_sel_count_<?=$ID?>"></span></div>
	<div class="bx-finder-box-selected-items" id="access_selected_items_<?=$ID?>"></div>
<?endforeach?>
</div>

</div>

<script type="text/javascript">
BX.Finder(BX('access_content_container'), 'Access', <?=CUtil::PhpToJsObject(array_keys($arHtml))?>, {'text-search-wait' : '<?=CUtil::JSEscape(GetMessage("acc_dialog_wait"))?>', 'text-search-no-result' : '<?=CUtil::JSEscape(GetMessage("acc_dialog_not_found"))?>'});
BX.Access.SelectProvider('<?=$first?>');
BX.Access.obProviderNames = <?=CUtil::PhpToJsObject($access->GetProviderNames())?>;
</script>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
