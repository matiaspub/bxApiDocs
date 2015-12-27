<?
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

IncludeModuleLangFile(__FILE__);
class CComponentParamsManager
{
	private static
		$fileDialogs = array();

	public static function Init($config = array())
	{
		global $APPLICATION;

		$APPLICATION->AddHeadScript('/bitrix/js/fileman/comp_params_manager/component_params_manager.js');
		$APPLICATION->SetAdditionalCss('/bitrix/js/fileman/comp_params_manager/component_params_manager.css');

		if (!isset($config['requestUrl']))
		{
			$config['requestUrl'] = '/bitrix/admin/fileman_component_params.php';
		}

		if (!isset($config['id']))
		{
			$config['id'] = 'bx_comp_params_manager_'.substr(uniqid(mt_rand(), true), 0, 4);
		}

		$mess_lang = self::GetLangMessages();
		?>
		<script type="text/javascript">
			BX.message(<?=CUtil::PhpToJSObject($mess_lang, false);?>);
			top.oBXComponentParamsManager = window.oBXComponentParamsManager = new BXComponentParamsManager(<?=CUtil::PhpToJSObject($config)?>);
		</script>
		<?

		// For colorpicker
		$APPLICATION->IncludeComponent(
			"bitrix:main.colorpicker",
			"",
			Array("SHOW_BUTTON" => "N"),
			false
		);
	}

	public static function ProcessRequest()
	{
		if (isset($_REQUEST['component_params_manager']))
		{
			$reqId = intVal($_REQUEST['component_params_manager']);
			$result = self::GetComponentProperties(
				$_REQUEST['component_name'],
				$_REQUEST['component_template'],
				$_REQUEST['site_template'],
				$_REQUEST['current_values']
			);
			$result['description'] = CComponentUtil::GetComponentDescr($_REQUEST['component_name']);
			?>
			<script>
				window.__bxResult['<?= $reqId?>'] = <?=CUtil::PhpToJSObject($result)?>;
			</script>
			<?
			self::DisplayFileDialogsScripts();
		}
	}

	public static function GetComponentProperties($name = '', $template = '', $siteTemplate = '', $currentValues = array())
	{
		$template = (!$template || $template == '.default') ? '' : $template;
		$arTemplates = CComponentUtil::GetTemplatesList($name, $siteTemplate);

		$result = array(
			'templates' => array()
		);

		$arSiteTemplates = array(".default" => GetMessage("PAR_MAN_DEFAULT"));
		if(!empty($siteTemplate))
		{
			$dbst = CSiteTemplate::GetList(array(), array("ID" => $siteTemplate), array());
			while($siteTempl = $dbst->Fetch())
				$arSiteTemplates[$siteTempl['ID']] = $siteTempl['NAME'];
 		}

 		foreach($arTemplates as $k => $templ)
		{
			$showTemplateName = ($templ["TEMPLATE"] !== '' && $arSiteTemplates[$templ["TEMPLATE"]] <> '') ? 				$arSiteTemplates[$templ["TEMPLATE"]] : GetMessage("PAR_MAN_DEF_TEMPLATE");
			$arTemplates[$k]['DISPLAY_NAME'] = $templ['NAME'].' ('.$showTemplateName.')';
		}

		$arTemplateProps = array();
		if (is_array($arTemplates))
		{
			foreach ($arTemplates as $arTemplate)
			{
				$result['templates'][] = $arTemplate;
				$tName = (!$arTemplate['NAME'] || $arTemplate['NAME'] == '.default') ? '' : $arTemplate['NAME'];

				if ($tName == $template)
				{
					$arTemplateProps = CComponentUtil::GetTemplateProps($name, $arTemplate['NAME'], $siteTemplate, $currentValues);
				}
			}
		}

		$result['parameters'] = array();
		$arProps = CComponentUtil::GetComponentProps($name, $currentValues, $arTemplateProps);
		$result['tooltips'] = self::FetchHelp($name);

		if (!isset($arProps['GROUPS']) || !is_array($arProps['GROUPS']))
		{
			$arProps['GROUPS'] = array();
		}
		if (!isset($arProps['PARAMETERS']) || !is_array($arProps['PARAMETERS']))
		{
			$arProps['PARAMETERS'] = array();
		}

		$result['groups'] = array();
		foreach ($arProps['GROUPS'] as $k => $arGroup)
		{
			$arGroup['ID'] = $k;
			$result['groups'][] = $arGroup;
		}

		foreach ($arProps['PARAMETERS'] as $k => $arParam)
		{
			$arParam['ID'] = preg_replace("/[^a-zA-Z0-9_-]/is", "_", $k);
			if (!isset($arParam['PARENT']))
			{
				$arParam['PARENT'] = 'ADDITIONAL_SETTINGS';
			}
			$result['parameters'][] = $arParam;

			if ($arParam['TYPE'] == 'FILE')
			{
				self::$fileDialogs[] = array(
					'NAME' => $arParam['ID'],
					'TARGET' => isset($arParam['FD_TARGET']) ? $arParam['FD_TARGET'] : 'F',
					'EXT' => isset($arParam['FD_EXT']) ? $arParam['FD_EXT'] : '',
					'UPLOAD' => isset($arParam['FD_UPLOAD']) && $arParam['FD_UPLOAD'] && $arParam['FD_TARGET'] == 'F',
					'USE_ML' => isset($arParam['FD_USE_MEDIALIB']) && $arParam['FD_USE_MEDIALIB'],
					'ONLY_ML' => isset($arParam['FD_USE_ONLY_MEDIALIB']) && $arParam['FD_USE_ONLY_MEDIALIB'],
					'ML_TYPES' => isset($arParam['FD_MEDIALIB_TYPES']) ? $arParam['FD_MEDIALIB_TYPES'] : false
				);
			}

			// TOOLTIPS FROM .parameters langs
			if (!isset($result['tooltips'][$arParam['ID'].'_TIP']))
			{
				$tip = GetMessage($arParam['ID'].'_TIP');
				if ($tip)
				{
					$result['tooltips'][$arParam['ID'].'_TIP'] = $tip;
				}
			}
		}

		return $result;
	}

	public static function FetchHelp($componentName, $lang = false)
	{
		$cName = str_replace("..", "", $componentName);
		$cName = str_replace(":", "/", $cName);
		$lang = $lang ? preg_replace("/[^a-zA-Z0-9_]/is", "", $lang) : LANGUAGE_ID;
		$filePath = "/bitrix/components/".$cName;
		$fileName = "help/.tooltips.php";
		$arTooltips = array();

		$fname = $_SERVER["DOCUMENT_ROOT"].$filePath."/lang/".LangSubst($lang)."/".$fileName;
		if ($lang != "en" && $lang != "ru" && file_exists($fname))
		{
			$arTooltips = __IncludeLang($fname, true, true);
		}

		$fname = $_SERVER["DOCUMENT_ROOT"].$filePath."/lang/".$lang."/".$fileName;
		if (file_exists($fname))
		{
			$arTooltips = __IncludeLang($fname, true, true);
		}

		return $arTooltips;
	}

	public static function GetLangMessages()
	{
		$messages = array(
			'CompParManSelectOther' => GetMessage('PAR_MAN_SELECT_OTHER'),
			'CompParManNoValue' => GetMessage('PAR_MAN_SELECT_NO_VALUE'),
			'CompParManSearch' => GetMessage('PAR_MAN_SEARCH'),
			'NoSearchResults' => GetMessage('PAR_MAN_NO_SEARCH_RESULTS'),
			'TemplateGroup' => GetMessage('PAR_MAN_TEMPLATE_GROUP'),
			'DefTemplate' => GetMessage('PAR_MAN_DEF_TEMPLATE')
		);
		return $messages;
	}

	public static function DisplayFileDialogsScripts()
	{
		for($i = 0, $l = count(self::$fileDialogs); $i < $l; $i++)
		{
			$fd = self::$fileDialogs[$i];
			if ($fd['USE_ML'])
			{
				$MLRes = CMedialib::ShowBrowseButton(
					array(
						'mode' => $fd['ONLY_ML'] ? 'medialib' : 'select',
						'value' => '...',
						'event' => "BX_FD_".$fd['NAME'],
						'id' => "bx_fd_input_".strtolower($fd['NAME']),
						'MedialibConfig' => array(
							"event" => "bx_ml_event_".$fd['NAME'],
							"arResultDest" => Array("FUNCTION_NAME" => "BX_FD_ONRESULT_".$fd['NAME']),
							"types" => $fd['ML_TYPES']
						),
						'bReturnResult' => true
					)
				);
				?><script>window._bxMlBrowseButton_<?= strtolower($fd['NAME'])?> = '<?= CUtil::JSEscape($MLRes)?>';</script><?
			}

			CAdminFileDialog::ShowScript(Array
			(
				"event" => "BX_FD_".$fd['NAME'],
				"arResultDest" => Array("FUNCTION_NAME" => "BX_FD_ONRESULT_".$fd['NAME']),
				"arPath" => Array(),
				"select" => $fd['TARGET'], // F - file only, D - folder only, DF - files & dirs
				"operation" => 'O',
				"showUploadTab" => $fd['UPLOAD'],
				"showAddToMenuTab" => false,
				"fileFilter" => $fd['EXT'],
				"allowAllFiles" => true,
				"SaveConfig" => true
			));
		}
	}
}
?>