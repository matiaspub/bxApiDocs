<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
// define("BX_SEARCH_ADMIN", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

$start = getmicrotime();

$query = ltrim($_POST["q"]);
if(
	!empty($query)
	&& $_REQUEST["ajax_call"] === "y"
	&& CModule::IncludeModule("search")
):

CUtil::decodeURIComponent($query);

$adminPage->Init();
//$adminMenu->AddOpenedSections("global_menu_content, global_menu_services, global_menu_store, global_menu_statistics, global_menu_settings");
$adminMenu->Init($adminPage->aModules);

$arResult = array("CATEGORIES"=>array(
			"global_menu_content"=>array("ITEMS"=>array(), "TITLE"=>GetMessage('admin_lib_menu_content')),
			"global_menu_services"=>array("ITEMS"=>array(), "TITLE"=>GetMessage('admin_lib_menu_services')),
			"global_menu_store"=>array("ITEMS"=>array(), "TITLE"=>GetMessage('admin_lib_menu_store')),
			"global_menu_statistics"=>array("ITEMS"=>array(), "TITLE"=>GetMessage('admin_lib_menu_stat')),
			"global_menu_settings"=>array("ITEMS"=>array(), "TITLE"=>GetMessage('admin_lib_menu_settings')),
		)
	);

$arStemFunc = stemming_init(LANGUAGE_ID);

$arPhrase = stemming_split($query, LANGUAGE_ID);

$preg_template = "/(^|[^".$arStemFunc["pcre_letters"]."])(".str_replace("/", "\\/", implode("|", array_map('preg_quote', array_keys($arPhrase)))).")/i".BX_UTF_PCRE_MODIFIER;
$bFound  = false;

function GetStrings(&$item, $key, $p)
{
	$category = $p[0];
	$icon = $p[1];
	global $arStemFunc, $arPhrase, $preg_template, $arResult, $bFound;
	$searchstring = '';
	if($item["text"])
	{
		if(preg_match_all($preg_template, ToUpper($item["text"]), $arMatches, PREG_OFFSET_CAPTURE))
		{
			$c = count($arMatches[2]);
			if(defined("BX_UTF"))
			{
				for($j = $c-1; $j >= 0; $j--)
				{
					$prefix = mb_substr($item["text"], 0, $arMatches[2][$j][1], 'latin1');
					$instr  = mb_substr($item["text"], $arMatches[2][$j][1], mb_strlen($arMatches[2][$j][0], 'latin1'), 'latin1');
					$suffix = mb_substr($item["text"], $arMatches[2][$j][1] + mb_strlen($arMatches[2][$j][0], 'latin1'), mb_strlen($item["text"], 'latin1'), 'latin1');
					$item["text"] = $prefix."<b>".$instr."</b>".$suffix;
				}
			}
			else
			{
				for($j = $c-1; $j >= 0; $j--)
				{
					$prefix = substr($item["text"], 0, $arMatches[2][$j][1]);
					$instr  = substr($item["text"], $arMatches[2][$j][1], strlen($arMatches[2][$j][0]));
					$suffix = substr($item["text"], $arMatches[2][$j][1]+strlen($arMatches[2][$j][0]));
					$item["text"] = $prefix."<b>".$instr."</b>".$suffix;
				}
			}
		}
		$searchstring .= $item["text"];
	}

	if($item["title"])
		$searchstring .= " ".$item["title"];

	if($item["keywords"])
		$searchstring .= " ".$item["keywords"];

	if($item["icon"]=='')
		$item["icon"] = $icon;

	if(preg_match_all($preg_template, ToUpper($searchstring), $arMatches, PREG_OFFSET_CAPTURE))
	{
		$ar = Array();
		foreach($arMatches[0] as $m)
			$ar[] = trim($m[0], " ,;>");
		if(count(array_unique($ar))==count($arPhrase))
			$arRes = array("NAME"=>$item["text"], "URL"=>$item["url"], "TITLE"=>$item["title"], "ICON"=>$item['icon']);
	}

	if(is_array($arRes))
	{
		if($item['category'] == '')
			$item['category'] = $category;

		if(!is_array($arResult["CATEGORIES"][$item['category']]))
		{
			$arResult["CATEGORIES"][$item['category']] = Array('TITLE'=>'', 'ITEMS'=>Array());
			if($item['category_name']!='')
				$arResult["CATEGORIES"][$item['category']]['TITLE'] = $item['category_name'];
		}
		$arResult["CATEGORIES"][$item['category']]["ITEMS"][] = $arRes;
		$bFound = true;
	}

	if(is_array($item["items"]))
		array_walk($item['items'], 'GetStrings', array($category, $item["icon"]));
}

foreach($adminMenu->aGlobalMenu as $menu_id => $menu)
	array_walk($menu['items'], 'GetStrings', array($menu_id, ''));


if($bFound)
{
?>
	<table class="adm-search-result">
		<?foreach($arResult["CATEGORIES"] as $category_id => $arCategory):
			if(count($arCategory["ITEMS"])==0)
				continue;
			?>
			<?foreach($arCategory["ITEMS"] as $i => $arItem):
				if($i>9)
					break;
				?>
			<tr onclick="window.location='<?=CUtil::JSEscape($arItem["URL"]);?>';">
				<?if($i == 0):?>
					<th>&nbsp;<?=$arCategory["TITLE"]?></th>
				<?else:?>
					<th>&nbsp;</th>
				<?endif?>
				<td class="adm-search-item" <?if($arItem["TITLE"]!='' && $arItem["TITLE"]!=$arItem["NAME"]):?>title="<?=$arItem["TITLE"]?>"<?endif?>>
					<a href="<?=$arItem["URL"]?>"><?if($arItem["ICON"]!=''):?><span class="adm-submenu-item-link-icon <?=$arItem["ICON"]?>"></span><?endif?><span class="adm-submenu-item-name-link-text"><?=$arItem["NAME"]?></span></a>
				</td>
			</tr>
			<?endforeach;?>
		<?endforeach;?>
	</table>
<?
}


endif;

//echo (getmicrotime()-$start);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>