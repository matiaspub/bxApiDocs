<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog.php");

IncludeModuleLangFile(__FILE__);

die('Еще не сделано');

CModule::IncludeModule('seo');

//Site ID
$site = SITE_ID;
if (isset($_REQUEST["site"]) && strlen($_REQUEST["site"]) > 0)
{
	$obSite = CSite::GetByID($_REQUEST["site"]);
	if ($arSite = $obSite->Fetch())
		$site = $_REQUEST["site"];
}
	
$dbRes = CSite::GetByID($site);
if ($arRes = $dbRes->Fetch())
	$server_name = $arRes['SERVER_NAME'];
if (strlen($server_name) <= 0)
	$server_name = COption::GetOptionString('main', 'server_name', '');
	
if (strlen($_REQUEST['title_changer_name']))
{
	$titleChangerName = $_REQUEST['title_changer_name'];
}

if (strlen($_REQUEST['title_changer_icons']))
{
	$arTitleChangerIcons = unserialize($_REQUEST['title_changer_icons']);
}

if (strlen($_REQUEST['title_final']))
{
	$titleFinal = $_REQUEST['title_final'];
}

/* properties */
if (IsModuleInstalled("fileman"))
{
	if (!$USER->CanDoOperation('fileman_admin_files') && !$USER->CanDoOperation('fileman_edit_existent_files'))
	{
	//	$popupWindow->ShowError(GetMessage("PAGE_PROP_ACCESS_DENIED"));
	}
}

//Page path
$path = "/";

if (isset($_REQUEST["path"]) && strlen($_REQUEST["path"]) > 0)
{
	$path = $_REQUEST["path"];
	$path = Rel2Abs("/", $path);
}

//Lang
if (!isset($_REQUEST["lang"]) || strlen($_REQUEST["lang"]) <= 0)
	$lang = LANGUAGE_ID;

//BackUrl
$back_url = (isset($_REQUEST["back_url"]) ? $_REQUEST["back_url"] : "");

$documentRoot = CSite::GetSiteDocRoot($site);
$absoluteFilePath = $documentRoot.$path;

//Check permissions
if (!is_file($absoluteFilePath))
{
	//$popupWindow->ShowError(GetMessage("PAGE_PROP_FILE_NOT_FOUND")." (".htmlspecialchars($path).")");
}
elseif (!$USER->CanDoFileOperation('fm_edit_existent_file',Array($site, $path)))
{
	//$popupWindow->ShowError(GetMessage("PAGE_PROP_ACCESS_DENIED"));
}

if (false !== ($pos = strrpos($absoluteFilePath, '/')))
{
	$absoluteDirPath = substr($absoluteFilePath, 0, $pos);
}



$fileContent = $APPLICATION->GetFileContent($absoluteFilePath);


/**************************************************************************************/
$strWarning = '';
//Save page settings
if ($_SERVER["REQUEST_METHOD"] == "POST" && !check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	$strWarning = GetMessage("MAIN_SESSION_EXPIRED");
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_REQUEST["save"]))
{
	//Title
	if (isset($_POST["pageTitle"]) && strlen($_POST["pageTitle"]) > 0)
	{
		$fileContent = SetPrologTitle($fileContent, $_POST["pageTitle"]);
	}

	//Title
	$prop_code = COption::GetOptionString('seo', 'property_window_title', 'title');
	if (isset($_POST["property_".$prop_code]))
	{
		$fileContent = SetPrologProperty($fileContent, $prop_code, $_POST["property_".$prop_code]);
	}
	
	//Properties
	if (isset($_POST["PROPERTY"]) && is_array($_POST["PROPERTY"]))
	{
		foreach ($_POST["PROPERTY"] as $arProperty)
		{
			$arProperty["CODE"] = (isset($arProperty["CODE"]) ? trim($arProperty["CODE"]) : "");
			$arProperty["VALUE"] = (isset($arProperty["VALUE"]) ? trim($arProperty["VALUE"]) : "");

			if (preg_match("/[a-zA-Z_-~]+/i", $arProperty["CODE"]))
			{
				$fileContent = SetPrologProperty($fileContent, $arProperty["CODE"], $arProperty["VALUE"]);
			}
		}
	}

	$prop_code = COption::GetOptionString('seo', 'property_internal_keywords', 'keywords_inner');
	
	if ($arProperty = explode(",", $_POST["property_".$prop_code."_local"]))
	{
		array_walk($arProperty, 'trim');
		$fileContent = SetPrologProperty($fileContent, $prop_code, implode(', ', $arProperty));
	}

	$success = $APPLICATION->SaveFileContent($absoluteFilePath, $fileContent);

	if ($success)
	{
		if (isset($_POST["property_".$prop_code."_section"]))
		{
			$arProperty = explode(",", $_POST["property_".$prop_code."_section"]);
			
			if (!is_array($arProperty)) $arProperty = array();
			array_walk($arProperty, 'trim');
			
			$arDirProperties = array();
			$sSectionName = '';
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$absoluteDirPath."/.section.php"))
				require($_SERVER['DOCUMENT_ROOT'].$absoluteDirPath."/.section.php");
			
			$arDirProperties[$prop_code] = implode(', ', $arProperty);
			
			$strSectionName = '$sSectionName = "'.EscapePHPString($sSectionName).'";';
			
			$strDirProperties = "\$arDirProperties = Array(\n";

			$bNeedComma = false;
			foreach ($arDirProperties as $prop_code => $value)
			{
				if (strlen($value) > 0 && preg_match("/[a-zA-Z_-~]+/i", $prop_code) )
				{
					if($bNeedComma)
						$strDirProperties .= ",\n";

					$strDirProperties .= "   \"".EscapePHPString($prop_code)."\" => \"".EscapePHPString($value)."\"";
					$bNeedComma = true;
				}
			}
			
			$strDirProperties .= "\n);\n";

			$APPLICATION->SaveFileContent($absoluteDirPath."/.section.php", "<"."?\n".$strSectionName."\n".$strDirProperties."?".">");
		}
	}
	
	if ($success === false && ($exception = $APPLICATION->GetException()))
		$strWarning = $exception->msg;
	else
		LocalRedirect("/".ltrim($back_url, "/"));
	die();
}

if ($strWarning != "")
{
	CAdminMessage::ShowMessage($strWarning);
	die();
}
	

//Properties from fileman settings
$arFilemanProperties = Array();
if (CModule::IncludeModule("fileman") && is_callable(Array("CFileMan", "GetPropstypes")))
	$arFilemanProperties = CFileMan::GetPropstypes($site);

//Properties from page
$arDirProperties = Array();
if ($strWarning != "")
{
	//Restore post values if error occured
	$pageTitle = (isset($_POST["pageTitle"]) && strlen($_POST["pageTitle"]) > 0 ? $_POST["pageTitle"] : "");

	if (isset($_POST["PROPERTY"]) && is_array($_POST["PROPERTY"]))
	{
		foreach ($_POST["PROPERTY"] as $arProperty)
		{
			if (isset($arProperty["VALUE"]) && strlen($arProperty["VALUE"]) > 0)
				$arDirProperties[$arProperty["CODE"]] = $arProperty["VALUE"];
		}
	}
}
else
{
	$arPageSlice = ParseFileContent($fileContent);
	$arDirProperties = $arPageSlice["PROPERTIES"];
	$pageTitle = $arPageSlice["TITLE"];
}

//All properties for file. Includes properties from root folders
$arInheritProperties = $APPLICATION->GetDirPropertyList(Array($site, $path)); 
if ($arInheritProperties === false)
	$arInheritProperties = Array();

//Delete equal properties
$arGlobalProperties = Array();
foreach ($arFilemanProperties as $propertyCode => $propertyDesc)
{
	if (array_key_exists($propertyCode, $arDirProperties))
		$arGlobalProperties[$propertyCode] = $arDirProperties[$propertyCode];
	else
		$arGlobalProperties[$propertyCode] = "";

	unset($arDirProperties[$propertyCode]);
	unset($arInheritProperties[strtoupper($propertyCode)]);
}

foreach ($arDirProperties as $propertyCode => $propertyValue)
	unset($arInheritProperties[strtoupper($propertyCode)]);
	
$counters = COption::GetOptionString('seo', 'counters', SEO_COUNTERS_DEFAULT);
	
//HTML output
$aTabs = array(
	array("DIV" => "edit1", "TAB" => 'Страница', "ICON" => "main_settings", "TITLE" => 'Страница '.$back_url),
	array("DIV" => "edit5", "TAB" => 'Управление', "ICON" => "main_settings", "TITLE" => 'Управление свойствами'),
	array("DIV" => "edit2", "TAB" => 'Индексация', "ICON" => "main_settings", "TITLE" => 'Индексация'),
	array("DIV" => "edit3", "TAB" => 'Переходы', "ICON" => "main_settings", "TITLE" => 'Переходы с поисковиков'),
	array("DIV" => "edit4", "TAB" => 'Ссылающиеся сайты', "ICON" => "main_settings", "TITLE" => 'Ссылающиеся сайты'),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$APPLICATION->SetTitle('Инструменты SEO для страницы');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="POST" action="" enctype="multipart/form-data">
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>" />
<?=bitrix_sessid_post()?>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<style type="text/css">
.bx-component-panel-overrider .bx-component-panel {
	z-index: 1000 !important;
	top: 0px !important;
	left: 0px !important;
}
body #menu_ {z-index: 1100 !important;}

div#bx_admin_form table.edit-table table.bx-seo-words-table tr td {padding: 2px !important; text-align: center;}

table.legend {margin:0px 0px 7px 10px; font-size:70%;}
table.legend td {padding:3px 10px 3px 0px;}

table.phrases-table, table.referers-table {width: 85%;}
table.phrases-table tr.bx-th td, table.referers-table tr.bx-th td {text-align: center; font-weight: bold !important;}
div#bx_admin_form table.phrases-table tr td, div#bx_admin_form table.referers-table tr td {height: 18px; padding: 3px 3px 0px 0px !important;}
table.phrases-table td div, table.referers-table td div {color: black !important; white-space: nowrap;}
table.phrases-table td div div, table.referers-table td div div {color: white !important; white-space: nowrap; overflow: hidden;}
table.phrases-table td table, table.referers-table  td table {width: 90%;}

div#bx_page_extended_data div {height: 130px; width: 99%; overflow: auto; margin-right: 5px; border: solid 1px #E0E4F1;}
</style>
<?
if (strlen($counters) > 0):
	$counters = str_replace(array('#DOMAIN#'), array(/*$server_name*/ 'www.bitrixsoft.com'), $counters);
?>
<tr>
	<td width="40%">Счетчики: <a href="javascript:alert('no help yet')">[?]</a>: </td>
	<td width="60%"><?echo $counters;?></td>
</tr>
<?endif;?>
</table>
<table id="bx_stats_loading_notify" class="edit-table"><tr><td align="center"><?ShowNote('Загрузка информации о странице...');?></td></tr></table>
<table id="bx_stats_table" class="edit-table" cellspacing="0" cellpadding="0" border="0" style="display: none;">
<tbody>
<tr height="0"><td width="40%" nowrap="nowrap"></td><td width="60%" nowrap="nowrap"></td></tr>
<tr class="heading" id="bx_page_stats_row">
	<td colspan="2" align="center">Статистика страницы</td>
</tr>
<tr>
	<td colspan="2">
		<a href="javascript:void(0)" onclick="BXToggle('bx_page_extended_data'); return false;">подробнее</a>
		<div id="bx_page_extended_data" style="display: none;"><table width="100%">
			<tr>
				<td width="70%">
					<div id="bx_ex_out"></div>
				</td>
				<td width="30%">
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('HEADERS');">HEADERS</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('BOLD')">BOLD</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('ITALIC')">ITALIC</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('LINK')">LINK</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('META_DESCRIPTION')">META_DESCRIPTION</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('META_KEYWORDS')">META_KEYWORDS</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('TITLE')">TITLE</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('H')">H1-H6</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('NOINDEX')">NOINDEX</a><br />
					<a href="javascript: void(0)" onclick="BXShowExtendedStat('NOFOLLOW')">NOFOLLOW</a><br />
				</td>
			</tr>
		</table></div>
	</td>
</tr>
<tr class="heading">
	<td colspan="2" align="center">Продвигаемые слова</td>
</tr>
<?
if ($prop_code = COption::GetOptionString('seo', 'property_internal_keywords', 'keywords_inner')):
	$arInnerKeywords = array(
		'local' => $arGlobalProperties[$prop_code], 
		'section' => $APPLICATION->GetDirProperty($prop_code, array($site, $path)),
	);
	
	$bSectionInherited = true;
	$file = $absoluteDirPath.'/.section.php';
	
	$arDirProperties = array();
	if (file_exists($file))
		require($file);

	if (count($arDirProperties) > 0)
	{
		$arKeyMap = array();
		foreach ($arDirProperties as $key => $value)
		{
			if (ToUpper($key) == ToUpper($prop_code))
				$bSectionInherited = false;
		}
	}

	foreach ($arInnerKeywords as $key => $prop_value)
	{
		if (strlen($prop_value) > 0)
		{
			$arInnerKeywords[$key] = explode(',', $prop_value);
		}
		else
		{
			$arInnerKeywords[$key] = array();
		}
	}
	
	$aSubTabs = array();
	foreach ($arInnerKeywords as $key => $ar)
	{
		$aSubTabs[] = array("DIV" => "int_keyw_".$key, "TAB" => GetMessage('SEO_TOOL_TAB_'.$key));
	}

	$childTabControl = new CAdminViewTabControl("childTabControl", $aSubTabs);

?>
<tr>
	<td colspan="2">
<?
		$childTabControl->Begin();
		foreach ($arInnerKeywords as $key => $arKeywords):
			foreach ($arKeywords as $k => $v) $arKeywords[$k] = trim($v);
			TrimArr($arKeywords);
			$childTabControl->BeginNextTab();
?>
<?
				if ($key == 'section' && $bSectionInherited):
?>
		<div id="bx_view_property_<?=$prop_code?>_section" style="overflow:hidden;padding:2px 12px 2px 2px; border:1px solid white; width:90%; cursor:text; box-sizing:border-box; -moz-box-sizing:border-box;background-color:transparent; background-position:right; background-repeat:no-repeat;" onclick="var input = BXEditProperty('<?=$prop_code?>_section'); input.name='property_<?=$prop_code?>_section'; input.onblur=function() {BXBlurProperty(input,'<?=$prop_code?>_section'); BXCallUpdateKeywordsStats(this.value, 'window.BXUpdateKeywordsStats_<?echo $key?>');}" onmouseover="this.style.borderColor = '#434B50 #ADC0CF #ADC0CF #434B50';" onmouseout="this.style.borderColor = 'white'" class="edit-field"><?echo htmlspecialcharsEx(implode(', ', $arKeywords))?></div>

		<div id="bx_edit_property_<?=$prop_code?>_section" style="display:none;"></div>
<?
				else:
?>
		<div><input type="text" id="property_<?echo $prop_code?>_<?echo $key?>" rows="5" name="property_<?echo $prop_code?>_<?echo $key?>" style="width: 90%;" onblur="BXCallUpdateKeywordsStats(this.value, 'window.BXUpdateKeywordsStats_<?echo $key?>')" value="<?echo htmlspecialcharsEx(implode(', ', $arKeywords))?>" /></div>
<?
				endif;
?>
		<br />
		<table width="100%" class="bx-seo-words-table" id="bx_seo_words_table_<?echo $key?>">
			<thead>
				<tr class="heading">
					<td>слово</td>
					<td>всего/контраст.</td>
					<td>загл. окна/страницы</td>
					<td>&lt;b&gt;/&lt;i&gt;</td>
					<td>описание/ключ. слова</td>
					<td>ссылки</td>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<script type="text/javascript">
window.BXUpdateKeywordsStats_<?echo $key?> = function(data) {BXUpdateKeywordsStats('<?echo $key?>', data)};
		</script>
<?			
	endforeach;
	$childTabControl->End();
?>
	</td>
</tr>
<?
endif;
?>
<tr class="heading">
	<td colspan="2" align="center">Рекомендации и исправления</td>
</tr>
<tr>
	<td colspan="2" id="bx_page_errors"></td>
</tr>
</tbody>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td>Базовый заголовок страницы <a href="javascript:alert('no help yet')">[?]</a>: </td>
	<td><input type="text" name="pageTitle" value="<?=htmlspecialcharsEx($pageTitle)?>"  size="50" /></td>
</tr>
<? 
if ($titleFinal != $pageTitle):
?>
<tr>
	<td valign="top">Текущий заголовок страницы <a href="javascript:alert('no help yet')">[?]</a>: </td>
	<td>
		<b><?echo $titleFinal?></b>&nbsp;<?if ($titleChangerName != ''):?>(<?echo htmlspecialchars($titleChangerName)?>)&nbsp;<?endif;?>
		<?if (is_array($arTitleChangerIcons)):?><div class="bx-component-panel-overrider" style="position: relative; display: inline; top: -17px;"><?echo $APPLICATION->DrawIcons($arTitleChangerIcons)?></div></td><?endif;?>
</tr>
<?
endif;

if ($prop_code = COption::GetOptionString('seo', 'property_window_title', 'title')):
	$value = $arGlobalProperties[$prop_code];
	if (strlen($value) <= 0) 
		$value = $APPLICATION->GetDirProperty($prop_code, array($site, $path));
?>
<tr>
	<td><?echo $arFilemanProperties[$prop_code]?> <a href="javascript:alert('no help yet')">[?]</a>: </td>
	<td><input type="text" name="property_<?echo htmlspecialcharsEx($prop_code)?>" value="<?=htmlspecialcharsEx($value)?>" size="50" /></td>
</tr>
<?
endif;

$arEditProperties = array();
if ($prop_code = COption::GetOptionString('seo', 'property_keywords', 'keywords')) $arEditProperties[] = $prop_code;
if ($prop_code = COption::GetOptionString('seo', 'property_description', 'description')) $arEditProperties[] = $prop_code;

foreach ($arEditProperties as $prop_code):
	$value = $arGlobalProperties[$prop_code];
?>
<tr>
	<td><?echo $arFilemanProperties[$prop_code]?> <a href="javascript:alert('no help yet')">[?]</a>: </td>
	<td><? 	
	if (strlen($value) <= 0):
		$value = $APPLICATION->GetDirProperty($prop_code, array($site, $path));
	?>
		<input type="hidden" name="PROPERTY[<?=$prop_code?>][CODE]" value="<?=htmlspecialcharsEx($prop_code)?>" /> 

		<div id="bx_view_property_<?=$prop_code?>" style="overflow:hidden;padding:2px 12px 2px 2px; border:1px solid white; width:90%; cursor:text; box-sizing:border-box; -moz-box-sizing:border-box;background-color:transparent; background-position:right; background-repeat:no-repeat;" onclick="BXEditProperty('<?=$prop_code?>')" onmouseover="this.style.borderColor = '#434B50 #ADC0CF #ADC0CF #434B50';" onmouseout="this.style.borderColor = 'white'" class="edit-field"><?=htmlspecialcharsEx($value)?></div>

		<div id="bx_edit_property_<?=$prop_code?>" style="display:none;"></div>
	<?
	else:
	?>
		<input type="text" name="property_<?echo htmlspecialcharsEx($prop_code)?>" value="<?=htmlspecialcharsEx($value)?>" size="50" /></td>
	<?
	endif;
	?>
</tr>
<?
endforeach;
?>
<?
$tabControl->BeginNextTab();
if (!CModule::IncludeModule('statistic')):
?>
	<tr>
		<td><?ShowError('Эта функция недоступна без модуля Веб-аналитики');?></td>
	</tr>
<?
else:
	$searchers = COption::GetOptionString('seo', 'searchers_list', '');
	$arSearchers = array();
	if (strlen($searchers) > 0)
	{
		$arSearchers = explode(',', $searchers);
		$arSearcherHits = array();
		if (count($arSearchers) > 0)
		{
			$dbRes = CSearcher::GetList($by = 's_name', $order = "asc", array('ID' => implode('|', $arSearchers)));
			$arSearchers = array();
			while ($arRes = $dbRes->Fetch())
			{
				$arSearchers[$arRes['ID']] = $arRes;
				$arSearcherHits[$arRes['ID']] = 0;
			}
		}
	}
	
	if (count($arSearchers) <= 0):
?>
	<tr>
		<td>Для использования данной функции необходимо задать список используемых поисковых систем в <a href="/bitrix/admin/settings.php?mid=seo&tabControl_active_tab=edit3">настройках модуля SEO</a>.</td>
	</tr>
<?
	else:
	
		$arFilter = array(
			'SEARCHER_ID' => implode('|', array_keys($arSearchers)),
			'DATE1' => ConvertTimeStamp(strtotime('-3 month'), false, $site_id),
			'DATE2' => ConvertTimeStamp(time(), false, $site_id),
			'URL' => 'http://'.$server_name.$back_url,
			'URL_EXACT_MATCH' => 'Y',
			'SITE_ID' => $site,
		);
		
		//echo '<pre>'; print_r($arFilter); echo '</pre>';
		
		$last_ts = strtotime('-'.COption::GetOptionInt('statistic', 'SEARCHER_HIT_DAYS', 3).' days');
		$total = 0;
		$dbRes = CSearcherHit::GetList($by = 's_searcher_id', $order="asc", $arFilter);
		while ($arRes = $dbRes->Fetch())
		{
			$ts = MakeTimeStamp($arRes['DATE_HIT']);
			$total++;
			if ($ts < $last_ts) $last_ts = $ts;
			$arSearcherHits[$arRes['SEARCHER_ID']]++;
		}
		
		if ($total > 0):
			$days_count = floor((time() - $last_ts)/86400);
?>
	<tr class="heading">
		<td colspan="2">Поисковые системы, индексировавшие страницу за последние <?echo $days_count?> дней</td>
	</tr>
<?
			foreach ($arSearcherHits as $key => $count):
				if ($count > 0):
?>
	<tr>
		<td width="50%"><?echo htmlspecialchars($arSearchers[$key]['NAME'])?>: </td>
		<td width="50%"><?echo $count;?></td>
	</tr>
<?
				endif;
			endforeach;
		endif;
		
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/img.php");
		$arrDays = CSearcher::GetGraphArray(array(
				"SEARCHER_ID"	=> $arFilter['SEARCHER_ID'],
				"DATE1"			=> $arFilter['DATE1'],
				"DATE2"			=> $arFilter['DATE2'],
				"SUMMA"			=> 'N'
			), $arrLegend
		);
		
		
?>
	<tr class="heading">
		<td colspan="2">Общая динамика индексирования сайта</td>
	</tr>
	<tr>
		<td colspan="2"><img src="/bitrix/admin/searcher_graph.php?&lang=<?echo LANGUAGE_ID?>&find_date1_DAYS_TO_BACK=90<?foreach ($arSearchers as $key => $ar) echo '&find_searchers[]='.$key;?>&mode=list&find_summa=N&width=576&height=300" border="0" width="576" height="300" border="0" /><br /><br />
		<table border="0" cellspacing="0" cellpadding="0" class="legend">
<?
	foreach ($arrLegend as $keyL => $arrL):
		$color = $arrL["COLOR"];
?>
			<tr>
				<td><img src="/bitrix/admin/graph_legend.php?color=<?=$color?>" width="45" height="2"></td>
				<td nowrap="nowrap">[<a href="/bitrix/admin/searcher_list.php?lang=<?=LANGUAGE_ID?>&amp;find_id=<?=$keyL?>&amp;set_filter=Y"><?=$keyL?></a>]&nbsp;<a  href="/bitrix/admin/searcher_dynamic_list.php?lang=<?=LANGUAGE_ID?>&amp;find_searcher_id=<?=$keyL?>&amp;find_date1=<?echo $arFilter["DATE1"]?>&amp;find_date2=<?=$arFilter["DATE2"]?>&amp;set_filter=Y"><?=$arrL["NAME"]?></a></td>
			</tr>
<?
endforeach;
?>
		</table></td>
<?
	endif;
endif;

$tabControl->BeginNextTab();
if (!CModule::IncludeModule('statistic')):
?>
	<tr>
		<td><?ShowError('Эта функция недоступна без модуля Веб-аналитики');?></td>
	</tr>
<?
elseif (count($arSearchers) <= 0):
?>
	<tr>
		<td>Для использования данной функции необходимо задать список используемых поисковых систем в <a href="/bitrix/admin/settings.php?mid=seo&tabControl_active_tab=edit3">настройках модуля SEO</a>.</td>
	</tr>
<?
else:
	$arFilter = array(
		'SEARCHER_ID' => implode('|', array_keys($arSearchers)),
		'TO' => 'http://'.$server_name.$back_url,
		'TO_EXACT_MATCH' => 'Y',
	);
	
	$dbRes = CPhrase::GetList($by = 's_phrase', $order = 'asc', $arFilter, $is_filtered, $total, $group_by, $max);
	
	$arWords = array();
	$total = 0;
	while ($arRes = $dbRes->Fetch())
	{
		if (!is_array($arWords[$arRes['PHRASE']]))
			$arWords[$arRes['PHRASE']] = array(
				'TOTAL' => 0,
				'ID' => $arRes['ID'],
				'SEARCHERS' => array(),
			);
	
		if (!is_array($arWords[$arRes['PHRASE']]['SEARCHERS'][$arRes['SEARCHER_ID']]))
			$arWords[$arRes['PHRASE']]['SEARCHERS'][$arRes['SEARCHER_ID']] = array(
				'SEARCHER_NAME' => $arRes['SEARCHER_NAME'],
				'LAST_HIT' => 0,
				'COUNT' => 0,
			);
		
		$total++;
		$arWords[$arRes['PHRASE']]['TOTAL']++;
		$arWords[$arRes['PHRASE']]['SEARCHERS'][$arRes['SEARCHER_ID']]['COUNT']++;
		
		$ts = MakeTimeStamp($arRes['DATE_HIT']);
		if ($ts > $arWords[$arRes['PHRASE']]['SEARCHERS'][$arRes['SEARCHER_ID']]['LAST_HIT'])
			$arWords[$arRes['PHRASE']]['SEARCHERS'][$arRes['SEARCHER_ID']]['LAST_HIT'] = $ts;
	}
	
	function CMP($a,$b){if($a['TOTAL']==$b['TOTAL'])return 0;return ($a['TOTAL']<$b['TOTAL'])?1:-1;}
	uasort($arWords, 'CMP');
	
	if ($total > 0)
	{
		$cnt = count($arWords);
?>
	<tr class="heading">
		<td colspan="2">На эту страницу попали с поиска по фразам:</td>
	</tr>
	<tr>
		<td colspan="2" align="center"><table class="phrases-table">
<?
		$color = null;
		foreach ($arWords as $word => $arData)
		{
			$percent = intval(($arData['TOTAL']/$total) * 100);
?>
		<tr>
			<td align="left" width="30%"><a href="javascript:void(0)" onclick="BXToggle('bx_phrase_ex_<? echo $arData['ID']?>'); return false;" title="подробная информация по переходам"><?echo htmlspecialchars($word)?></td>
			<td width="70%">
				<div style="height: 15px; border: solid 1px #<?echo $color = GetNextRGB($color, $cnt)?> !important; width: 100%; position: relative; cursor: pointer;" onclick="BXToggle('bx_phrase_ex_<? echo $arData['ID']?>'); return false;">
					<div style="float: left; height: 15px; width: <?echo $percent?>%; background-color: <?echo $color; ?>; white-space: nowrap; position: absolute;">
						<?echo intval($arData['TOTAL'])?> (<?echo $percent?>%)
					</div>
					<?echo intval($arData['TOTAL'])?> (<?echo $percent?>%)
				</div>
			</td>
		</tr>
		<tr id="bx_phrase_ex_<? echo $arData['ID']?>" style="display: none;">
			<td colspan="2" align="center"><table>
				<tr class="bx-th">
					<td>поисковик</td>
					<td>переходов</td>
					<td>последний переход</td>
				</tr>
<?
			foreach ($arData['SEARCHERS'] as $searcher_id => $arSearcherData):
?>
				<tr>
					<td><? echo htmlspecialchars($arSearcherData['SEARCHER_NAME']);?></td>
					<td align="center"><?echo intval($arSearcherData['COUNT']);?></td>
					<td align="center"><?echo ConvertTimeStamp($arSearcherData['LAST_HIT'], "FULL");?></td>
				</tr>
<?
			endforeach;
?>
			</table></td>
		</tr>
<?
		}
?>
		</table></td>
	</tr><tr>
		<td colspan="2" align="right"><a href="/bitrix/admin/phrase_list.php?lang=<?echo LANGUAGE_ID?>">Посмотреть в панели управления</a></td>
	</tr>
<?
	}


endif;
$tabControl->BeginNextTab();
/*
?>

<pre><?echo $pageTitle."\r\n"; print_r($arFilemanProperties); print_r($arGlobalProperties); print_r($arDirProperties); print_r($arInheritProperties); print_r($tagPropertyValue); ?>
<?
*/

if (!CModule::IncludeModule('statistic')):
?>
	<tr>
		<td><?ShowError('Эта функция недоступна без модуля Веб-аналитики');?></td>
	</tr>
<?
else:
	$arFilter = array(
		'TO' => 'http://'.$server_name.$back_url,
		'TO_EXACT_MATCH' => 'Y',
	);
	
	$dbRes = CReferer::GetList($by = 's_url_from', $order = 'asc', $arFilter, $is_filtered, $total, $group_by, $max);

	$arReferers = array();
	$total = 0;
	while ($arRes = $dbRes->Fetch())
	{
		if (strlen($arRes['URL_FROM']) > 0 && ($arUrl = parse_url($arRes['URL_FROM'])))
		{
			if (!is_array($arReferers[$arUrl['host']]))
			{
				$arReferers[$arUrl['host']] = array(
					'TOTAL' => 0, 
					'URL_FROM' => array(
						$arRes['URL_FROM'] => 0
					),
				);
			}

			$total++;
			$arReferers[$arUrl['host']]['TOTAL']++;
			$arReferers[$arUrl['host']]['URL_FROM'][$arRes['URL_FROM']]++;
		}
	}

	uasort($arReferers, 'CMP');

	if ($total > 0)
	{
		$cnt = count($arReferers);
?>
	<tr class="heading">
		<td colspan="2">На эту страницу попали с сайтов:</td>
	</tr>
	<tr>
		<td colspan="2" align="center"><table class="referers-table">
<?
		$color = null;
		foreach ($arReferers as $domain => $arData)
		{
			$percent = intval(($arData['TOTAL']/$total) * 100);
?>
		<tr>
			<td width="30%" align="left"><a href="javascript:void(0)" onclick="BXToggle('bx_referer_ex_<?echo CUtil::JSEscape($domain)?>'); return false;" title="подробная информация по ссылкам"><?echo htmlspecialchars($domain)?></td>
			<td width="70%">
				<div style="height: 15px; border: solid 1px #<?echo $color = GetNextRGB($color, $cnt)?> !important; width: 100%; position: relative; cursor: pointer;" onclick="BXToggle('bx_referer_ex_<?echo CUtil::JSEscape($domain)?>'); return false;">
					<div style="float: left; height: 15px; width: <?echo $percent?>%; background-color: <?echo $color; ?>; white-space: nowrap; position: absolute;">
						<?echo intval($arData['TOTAL'])?> (<?echo $percent?>%)
					</div>
					<?echo intval($arData['TOTAL'])?> (<?echo $percent?>%)
				</div>
			</td>
		</tr>
		<tr id="bx_referer_ex_<?echo CUtil::JSEscape($domain)?>" style="display: none;">
			<td colspan="2" align="center"><table>
				<tr class="bx-th">
					<td width="90%">ссылка</td>
					<td width="10%">переходов</td>
				</tr>
<?
			foreach ($arData['URL_FROM'] as $url => $count):
?>
				<tr>
					<td><a href="<?echo $url?>"><? echo htmlspecialchars(TruncateText($url, 100));?></td>
					<td align="center"><?echo intval($count);?></td>
				</tr>
<?
			endforeach;
?>
			</table></td>
		</tr>
<?
		}
?>
	</table></td>
</tr><tr>
	<td colspan="2" align="right"><a href="/bitrix/admin/referer_list.php?lang=<?echo LANGUAGE_ID?>">Посмотреть в панели управления</a></td>
</tr>
<?
	}


endif;

$tabControl->Buttons(array("disabled"=>false));
$tabControl->End();
?>
</form>
<script type="text/javascript">
window.BXToggle = function(id)
{
	with(document.getElementById(id)){if (style.display=='none')style.display='';else style.display='none';};
}

window.BXUpdateKeywordsStats = function(type, data)
{
	CloseWaitWindow();
	
	var obTable = document.getElementById('bx_seo_words_table_' + type).tBodies[0];

	obTable.innerHTML = '';
	
	for (var i = 0; i < data.length; i++)
	{
		var obRow = obTable.insertRow(-1);
		
		obRow.insertCell(-1).appendChild(document.createTextNode(data[i][0]));
		obRow.insertCell(-1).appendChild(document.createTextNode(null != data[i][1].TOTAL ? data[i][1].TOTAL + '/' + data[i][1].CONTRAST : '-'));
		obRow.insertCell(-1).appendChild(document.createTextNode(null != data[i][1].TITLE ? data[i][1].TITLE + '/' + data[i][1].H1 : '-'));
		obRow.insertCell(-1).appendChild(document.createTextNode(null != data[i][1].BOLD ? data[i][1].BOLD + '/' + data[i][1].ITALIC : '-'));
		obRow.insertCell(-1).appendChild(document.createTextNode(null != data[i][1].TOTAL ? data[i][1].DESCRIPTION + '/' + data[i][1].KEYWORDS : '-'));
		obRow.insertCell(-1).appendChild(document.createTextNode(null != data[i][1].LINK ? data[i][1].LINK : '-'));
		
		obRow.cells[0].style.textAlign = 'left';
		
	}
}

window.BXCallUpdateKeywordsStats = function(keywords, callback_name)
{
	ShowWaitWindow();
	jsUtils.loadJSFile('/bitrix/tools/seo_page_parser.php?lang=<?=LANGUAGE_ID?>&site=<?=$site?>&url=<?echo CUtil::JSEScape(urlencode($back_url))?>&callback=' + jsUtils.urlencode(callback_name) + '&keywords=' + encodeURIComponent(keywords));
}

window.BXCallPageStats = function()
{
	ShowWaitWindow();

	var keywords = '<?echo CUtil::JSEscape(implode(', ', array_merge($arInnerKeywords['local'], $arInnerKeywords['section'])));?>';
	
	jsUtils.loadJSFile('/bitrix/tools/seo_page_parser.php?lang=<?=LANGUAGE_ID?>&first=Y&site=<?=$site?>&url=<?echo CUtil::JSEScape(urlencode($back_url))?>&callback=window.BXSetStats&keywords=' + encodeURIComponent(keywords));
}

window.BXSetStats = function(data, stats, errors, extended)
{
	var data_local = [], data_section = [];
	for (var i = 0; i < data.length; i++)
	{
		if (i < <?echo count($arInnerKeywords['local'])?>)
			data_local[data_local.length] = data[i];
		else
			data_section[data_section.length] = data[i];
	}
	
	BXUpdateKeywordsStats('local', data_local);
	BXUpdateKeywordsStats('section', data_section);
	
	BXUpdatePageStats(stats);
	BXUpdatePageErrors(errors);
	
	BXUpdatePageExtendedData(extended);

	document.getElementById('bx_stats_loading_notify').style.display = 'none';
	document.getElementById('bx_stats_table').style.display = '';
}

window.BXUpdatePageExtendedData = function(extended)
{
	window.__BXExtendedPageStat = extended;
	BXShowExtendedStat('HEADERS');
}

window.BXShowExtendedStat = function(stat)
{
	if (null != window.__BXExtendedPageStat[stat])
	{
		var out = document.getElementById('bx_ex_out');
		out.innerHTML = '';
		
		for (var i = 0; i < window.__BXExtendedPageStat[stat].length; i++)
		{
			out.appendChild(document.createElement('P')).appendChild(document.createTextNode(window.__BXExtendedPageStat[stat][i]));
		}
	}
}

window.BXUpdatePageStats = function(stats)
{
	var obTable = document.getElementById('bx_stats_table').tBodies[0];
	var index = document.getElementById('bx_page_stats_row').sectionRowIndex;
	
<?
$arStats = array('TOTAL_LENGTH', 'TOTAL_WORDS_COUNT', 'UNIQUE_WORDS_COUNT', 'META_DESCRIPTION', 'META_KEYWORDS');
foreach ($arStats as $stat):
?>
	var obRow = obTable.insertRow(++index);
	obRow.insertCell(-1).appendChild(document.createTextNode('<?echo CUtil::JSEscape(GetMessage('SEO_PAGE_STAT_'.$stat))?>: '));
	obRow.insertCell(-1).appendChild(document.createTextNode(stats.<?echo $stat?>));
	obRow.cells[0].className = 'field-name';
<?
endforeach;
?>
}

window.BXUpdatePageErrors = function(errors)
{
	var obCell = document.getElementById('bx_page_errors');
	obCell.innerHTML = '';
	
	if (errors.length > 0)
	{
		var str = '<ol style="padding: 0px 0px 0px 25px;">';
		for (var i = 0; i < errors.length; i++)
		{
			str += '<li>' + errors[i].TEXT + '</li>';
		}
		str += '</ol>';
		
		obCell.innerHTML = str;
	}
}

window.BXBlurProperty = function(element, propertyIndex)
{
	var viewProperty = document.getElementById("bx_view_property_" + propertyIndex);

	if (element.value == "" || element.value == viewProperty.innerHTML)
	{
		var editProperty = document.getElementById("bx_edit_property_" + propertyIndex);

		viewProperty.style.display = "block";
		editProperty.style.display = "none";

		while (editProperty.firstChild)
			editProperty.removeChild(editProperty.firstChild);
	}
}

window.BXEditProperty = function(propertyIndex)
{
	if (document.getElementById("bx_property_input_" + propertyIndex))
		return;

	var editProperty = document.getElementById("bx_edit_property_" + propertyIndex);
	var viewProperty = document.getElementById("bx_view_property_" + propertyIndex);

	viewProperty.style.display = "none";
	editProperty.style.display = "block";

	var input = document.createElement("INPUT");

	input.type = "text";
	input.name = "PROPERTY["+propertyIndex+"][VALUE]";

	input.style.width = "90%";
	input.style.padding = "2px";
	input.id = "bx_property_input_" + propertyIndex;
	input.onblur = function () {BXBlurProperty(input,propertyIndex)};
	input.value = viewProperty.innerHTML;

	editProperty.appendChild(input);
	input.focus();
	input.select();
	
	return input;
}

window.onload = BXCallPageStats;
</script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>