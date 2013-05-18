<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

IncludeModuleLangFile(__FILE__);

class CEditArea
{
	var $includeAreaIndex = array();
	var $includeAreaIcons = array();
	var $includeLevel = -1;
	var $arEditAreas = array();
	var $bEditAreas = false;
	var $bDrawIcons = false;

	function __GetAreaId()
	{
		return implode("_", array_slice($this->includeAreaIndex, 0, $this->includeLevel+1));
	}

	public static function IncludeStringBefore()
	{
		$this->includeLevel++;
		$this->includeAreaIndex[$this->includeLevel] = intval($this->includeAreaIndex[$this->includeLevel])+1;
		unset($this->includeAreaIndex[$this->includeLevel+1]);

		$areaId = $this->__GetAreaId();

		$res = '<div id="bx_incl_area_'.$areaId.'">';
		return $res;
	}

	public static function IncludeStringAfter($arIcons=false, $arParams=array())
	{
		$res = '';
		$res .= $this->DrawIcons($arIcons, $arParams);
		$res .= '</div>';
		$areaId = $this->__GetAreaId();

		if ($this->bDrawIcons)
			$res .= '<script type="text/javascript">if(window.BX&&BX.admin)BX.admin.setComponentBorder(\'bx_incl_area_'.$areaId.'\')</script>';

		$this->includeLevel--;
		return $res;
	}

	public static function DrawIcons($arIcons, $arParams=array())
	{
		$bStack = $this->includeLevel > ($GLOBALS['BX_GLOBAL_AREA_EDIT_ICON'] ? 1 : 0);

		$arJSIcons = array();
		$arOuterIcons = array();
		foreach ($arIcons as $arIcon)
		{
			if(isset($arIcon['SEPARATOR']))
			{
				$size = count($arJSIcons);
				if ($size <= 0 || !isset($arJSIcons[$size-1]["SEPARATOR"]))
					$arJSIcons[] = array('SEPARATOR' => 'Y');
			}
			else
			{
				//skip duplicate buttons
				if (isset($arIcon["ID"]) && strlen($arIcon["ID"]) > 0)
				{
					if (!array_key_exists("ICONS_ID", $this->includeAreaIcons) || !is_array($this->includeAreaIcons["ICONS_ID"]))
						$this->includeAreaIcons["ICONS_ID"] = Array($arIcon["ID"]);
					elseif (in_array($arIcon["ID"], $this->includeAreaIcons["ICONS_ID"]))
						continue;
					else
						$this->includeAreaIcons["ICONS_ID"][] = $arIcon["ID"];
				}

				$jsIcon = CEditArea::GetJSIcon($arIcon);

				if ($arIcon['IN_MENU'] || ($arIcon['TYPE'] == 'components2_props' && $bStack))
					$arJSIcons[] = $jsIcon;
				elseif ($arIcon['IN_PARAMS_MENU'])
				{
					$jsIcon['TYPE'] = 'components2_submenu_item';
					$arOuterIcons[] = $jsIcon;
				}
				else
					$arOuterIcons[] = $jsIcon;
			}
		}

		$areaId = $this->__GetAreaId();

		$this->includeAreaIcons[$areaId] = array(
			'COMPONENT' => $arParams['COMPONENT'],
			'DESCRIPTION' => $arParams['COMPONENT_DESCRIPTION'],
			'OUTER_ICONS' => $arOuterIcons,
			'ICONS' => $arJSIcons
		);

		if ($bStack)
		{
			$this->bDrawIcons = false;
			return '';
		}
		else
		{
			if (array_key_exists("ICONS_ID", $this->includeAreaIcons))
				unset($this->includeAreaIcons["ICONS_ID"]);

			$arAllOuterIcons = array();
			$arAllInnerIcons = array();
			foreach ($this->includeAreaIcons as $arSubIcons)
			{
				$arAllOuterIcons = array_merge($arAllOuterIcons, $arSubIcons['OUTER_ICONS']);
				$arAllInnerIcons = array_merge(
					$arAllInnerIcons,
					array(
						array(
							'TEXT' => $arSubIcons['DESCRIPTION']['NAME'] ? ($arSubIcons['DESCRIPTION']['NAME'].' ('.$arSubIcons['COMPONENT'].')') : $arSubIcons['COMPONENT'],
							'ICON' => 'parameters-2',
							'MENU' => $arSubIcons['ICONS']
						)
					)
				);
			}

			$arAllInnerIcons = array_reverse($arAllInnerIcons);

			$arAddInnerIcons = array();
			foreach ($arAllOuterIcons as $aIcon)
			{
				if ($aIcon['TYPE'] == 'components2_props')
				{
					$arAddInnerIcons['components2_props'] = $aIcon;
				}
				elseif ($aIcon['TYPE'] == 'components2_submenu_item')
				{
					$arAddInnerIcons[] = $aIcon;
				}
			}

			if (count($arAddInnerIcons) > 1)
			{
				$arAddInnerIcons[] = array('SEPARATOR' => 'Y');
			}

			if (count($arAddInnerIcons) > 0 && count($arAllInnerIcons) > 0)
			{
				$arJSIcons = array_merge(
					$arAllOuterIcons,
					array(
							array(
								'TYPE' => 'components2_submenu',
								'TEXT' => 'components2_submenu',
								'ICON' => 'parameters-2',
								'MENU' => array_merge(array_values($arAddInnerIcons), $arAllInnerIcons)
							)
					)
				);
			}
			else
			{
				$arJSIcons = $arAllOuterIcons;
			}

			$arUserOptions = false;
			if ($arParams['COMPONENT_ID'])
			{
				$arUserOptions = CUtil::GetPopupOptions($arParams['COMPONENT_ID']);
			}

			$arJSParams = array(
				'parent' => 'bx_incl_area_'.$areaId,
				'id' => 'comp_'.$areaId,
			);

			if ($arParams['COMPONENT_ID'])
			{
				$arJSParams['component_id'] = $arParams['COMPONENT_ID'];

				if (is_array($arUserOptions))
				{
					if ($arUserOptions['pin'] === 'true' || $arParams['COMPONENT_ID'] == 'page_edit_control')
					{
						$arJSParams['pin'] = $arUserOptions['pin'] === 'true';
						if ($arJSParams['pin'])
						{
							if ($arUserOptions['transform'])
								$arJSParams['transform'] = ($arUserOptions['transform'] === 'true');
							if ($arUserOptions['top']) $arJSParams['top'] = $arUserOptions['top'] == 'false' ? false : $arJSParams['top'];
							if ($arUserOptions['left']) $arJSParams['left'] = $arUserOptions['left'] == 'false' ? false : $arUserOptions['left'];
						}
					}
				}
			}

			if (is_array($arParams['TOOLTIP']) && ($arParams['TOOLTIP']['TITLE'] || $arParams['TOOLTIP']['TEXT']))
			{
				$arJSParams['HINT'] = array(
					'TITLE' => $arParams['TOOLTIP']['TITLE'],
					'TEXT' => $arParams['TOOLTIP']['TEXT']
				);
			}

			if (count($arJSIcons) > 0)
			{
				$arJSParams['menu'] = $arJSIcons;

				CUtil::InitJSCore(array('admin'));

				$this->bDrawIcons = true;

				$res = '<script type="text/javascript">if(window.BX)BX.ready(function() {(new BX.'.($arParams['COMPONENT_ID'] == 'page_edit_control' ? 'CPageOpener' : 'CMenuOpener').'('.CUtil::PhpToJsObject($arJSParams).')).Show()});</script>';
			}
			else
			{
				$this->bDrawIcons = false;
				$res = '';
			}
			$this->includeAreaIcons = array();
		}

		return $res;
	}

	public static function GetJSIcon($arIcon)
	{
		$url = $arIcon['URL'];
		if (strlen($url) > 0)
		{
			if(strtolower(substr($url, 0, 11)) == 'javascript:')
				$url = substr($url, 11);
			else
				$url = 'jsUtils.Redirect([], \''.CUtil::JSEscape($url).'\')';
		}

		$jsIcon = array(
			'ICONCLASS' => $arIcon['ICON'],
			'TITLE' => $arIcon['ALT'],
			'TEXT' => $arIcon['TITLE'],
		);

		if ($url)
			$jsIcon['ONCLICK'] = $url;

		if(isset($arIcon['DEFAULT']) && $arIcon['DEFAULT'] == true)
			$jsIcon['DEFAULT'] = true;
		if(isset($arIcon['IMAGE']))
			$jsIcon['IMAGE'] = $arIcon['IMAGE'];
		elseif(isset($arIcon['SRC']))
			$jsIcon['IMAGE'] = $arIcon['SRC'];

		if ($arIcon['TYPE'])
			$jsIcon['TYPE'] = $arIcon['TYPE'];

		if ($arIcon['MENU'])
		{
			$jsIcon['MENU'] = CEditArea::GetJSIconMenu($arIcon['MENU']);
		}

		return $jsIcon;
	}

	public static function GetJSIconMenu($arMenu)
	{
		foreach ($arMenu as $k => $aMenuItem)
		{
			if ($aMenuItem['URL'])
			{
				$u = $aMenuItem['URL'];
				if(strtolower(substr($u, 0, 11)) == 'javascript:')
					$u = substr($u, 11);
				else
					$u = 'jsUtils.Redirect([], \''.CUtil::JSEscape($u).'\')';

				$aMenuItem['URL'] = $aMenuItem['ACTION'] = $u;
			}

			if ($aMenuItem['MENU'])
				$aMenuItem['MENU'] = CEditArea::GetJSIconMenu($aMenuItem['MENU']);

			$arMenu[$k] = $aMenuItem;
		}

		return $arMenu;
	}

	function __GetEditAreas()
	{
		$res = '<script type="text/javascript">';
		$res_ready = '';
		foreach ($this->arEditAreas as $areaId => $arIcons)
		{
			$arJSIcons = array();
			foreach ($arIcons as $arIcon)
			{
				$arJSIcons[] = CEditArea::GetJSIcon($arIcon);
			}

			$arJSParams = array(
				'parent' => $areaId,
				'menu' => $arJSIcons
			);

			$res_ready .= '(new BX.CMenuOpener('.CUtil::PhpToJsObject($arJSParams).')).Show();'."\r\n";
			$res_ready .= 'BX.admin.setComponentBorder(\''.CUtil::JSEscape($areaId).'\');'."\r\n";
		}

		$res .= 'if(window.BX)BX.ready(function(){'.$res_ready.'});';
		$res .= '</script>';

		echo $res;
	}

	public static function SetEditArea($areaId, $arIcons)
	{
		if (!$this->bEditAreas)
		{
			CUtil::InitJSCore(array('admin'));
			AddEventHandler("main", "OnEpilog", array($this, '__GetEditAreas'));
			$this->bEditAreas = true;
		}

		if (!isset($this->arEditAreas[$areaId]))
			$this->arEditAreas[$areaId] = array();

		$this->arEditAreas[$areaId] = array_merge($this->arEditAreas[$areaId], $arIcons);
	}
}

class CComponentPanel
{
	/** @var CBitrixComponent */
	var $component;
	var $componentName;
	var $componentTemplate;
	var $parentComponent;
	var $bComponentEnabled;
	var $bSrcFound = false;
	var $sSrcFile = "";
	var $iSrcLine = 0;

	static public function __construct($component, $componentName, $componentTemplate, $parentComponent, $bComponentEnabled)
	{
		$this->component = $component;
		$this->componentName = $componentName;
		$this->componentTemplate = $componentTemplate;
		$this->parentComponent = $parentComponent;
		$this->bComponentEnabled = $bComponentEnabled;

		if(function_exists("debug_backtrace"))
		{
			$aTrace = debug_backtrace();

			$io = CBXVirtualIo::GetInstance();

			$this->sSrcFile = str_replace("\\", "/", $io->GetLogicalName($aTrace[1]["file"]));
			$this->iSrcLine = intval($aTrace[1]["line"]);

			if($this->iSrcLine > 0 && $this->sSrcFile <> "")
			{
				// try to convert absolute path to file within DOCUMENT_ROOT
				$doc_root = strtolower(str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"])));
				if(strpos(strtolower($this->sSrcFile), $doc_root) === 0)
				{
					//within
					$this->sSrcFile = substr($this->sSrcFile, strlen($doc_root));
					$this->bSrcFound = true;
				}
				else
				{
					//outside
					$sRealBitrix = strtolower(str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]."/bitrix")));
					if(strpos(strtolower($this->sSrcFile), $sRealBitrix) === 0)
					{
						$this->sSrcFile = "/bitrix".substr($this->sSrcFile, strlen($sRealBitrix));
						$this->bSrcFound = true;
					}
				}
			}
		}
	}

	static public function GetIcons()
	{
		/** @global CMain $APPLICATION */
		global $USER, $APPLICATION;

		$arIcons = array();
		$arPanelParams = array();

		$arComponentDescription = CComponentUtil::GetComponentDescr($this->componentName);
		$bComponentAccess = ($USER->CanDoOperation('edit_php') || $this->bSrcFound && $USER->CanDoFileOperation('fm_lpa', array(SITE_ID, $this->sSrcFile)));

		if($bComponentAccess && !$this->parentComponent && $this->bSrcFound)
		{
			$url = $APPLICATION->GetPopupLink(
				array(
					'URL' => "/bitrix/admin/component_props.php?".
					"component_name=".urlencode(CUtil::addslashes($this->componentName)). //$rel_path
					"&component_template=".urlencode(CUtil::addslashes($this->componentTemplate)).
					"&template_id=".urlencode(CUtil::addslashes(SITE_TEMPLATE_ID)).
					"&lang=".urlencode(CUtil::addslashes(LANGUAGE_ID)).
					"&src_path=".urlencode(CUtil::addslashes($this->sSrcFile)).
					"&src_line=".$this->iSrcLine.
					"&src_page=".urlencode(CUtil::addslashes($APPLICATION->GetCurPage())).
					"&src_site=".urlencode(CUtil::addslashes(SITE_ID)),
					"PARAMS" => Array("min_width" => 450)
				)
			);
			$arIcons[] = array(
				'URL'=>'javascript:'.$url,
				'TYPE'=>'components2_props',
				'ICON'=>"bx-context-toolbar-settings-icon",
				'TITLE'=>GetMessage("main_incl_file_comp_param"),
				'DEFAULT'=>true,
			);

			$aMenuItem = array(
				"TEXT"=>$arComponentDescription["NAME"],
				"TITLE"=>GetMessage("main_comp_button_menu_title").' '.$this->componentName,
				"ICON"=>"parameters-2",
				"ACTION"=>$url,
			);
			$APPLICATION->AddPanelButtonMenu("components", $aMenuItem);
		}

		if($bComponentAccess)
		{
			$template = $this->component->GetTemplate();
			if(is_null($template))
			{
				if($this->component->InitComponentTemplate())
					$template = $this->component->GetTemplate();
			}

			if(!is_null($template))
			{
				$urlCopy = '';
				if($this->bSrcFound && $template->IsInTheme() == false)
				{
					//copy template dialog
					$urlCopy = "/bitrix/admin/template_copy.php?".
						"lang=".urlencode(CUtil::addslashes(LANGUAGE_ID)).
						"&component_name=".urlencode(CUtil::addslashes($this->componentName)).
						"&component_template=".urlencode(CUtil::addslashes($this->componentTemplate)).
						"&template_id=".urlencode(CUtil::addslashes(SITE_TEMPLATE_ID)).
						"&template_site_template=".urlencode(CUtil::addslashes($template->GetSiteTemplate())).
						"&src_path=".urlencode(CUtil::addslashes($this->sSrcFile)).
						"&src_line=".$this->iSrcLine.
						"&src_site=".urlencode(CUtil::addslashes(SITE_ID)).
						"&edit_file=".urlencode($template->GetPageName()).
						"&back_path=".urlencode($_SERVER["REQUEST_URI"]);
					$arIcons[] = array(
						'URL'=>'javascript:'.$APPLICATION->GetPopupLink(
								array(
									'URL' => $urlCopy,
									"PARAMS" => Array("min_width" => 450)
								)
							),
						'ICON'=>"copy-2",
						'TITLE'=>GetMessage("main_comp_copy_templ"),
						'IN_MENU' => true
					);
				}
				if($USER->CanDoOperation('edit_php') && strlen($template->GetSiteTemplate()) > 0)
				{
					//edit template copied to site template
					$arIcons[] = array(
						'URL' => 'javascript:'.$APPLICATION->GetPopupLink(array(
								'URL' => "/bitrix/admin/public_file_edit_src.php?site=".SITE_ID."&".'path='.urlencode($template->GetFile())."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&lang=".LANGUAGE_ID,
								'PARAMS' => array(
									'width' => 770,
									'height' => 470,
									'resize' => true,
									"dialog_type" => 'EDITOR'
								)
							)
						),
						'ICON' => 'edit-2',
						'TITLE' => GetMessage("main_comp_edit_templ"),
						'IN_MENU' => true
					);
					if(strlen($template->GetFolder()) > 0)
					{
						if(file_exists($_SERVER["DOCUMENT_ROOT"].$template->GetFolder()."/style.css"))
						{
							//edit template CSS copied to site template
							$arIcons[] = array(
								'URL' => 'javascript:'.$APPLICATION->GetPopupLink(array(
										'URL' => "/bitrix/admin/public_file_edit_src.php?site=".SITE_ID."&".'path='.urlencode($template->GetFolder()."/style.css")."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&lang=".LANGUAGE_ID,
										'PARAMS' => array(
											'width' => 770,
											'height' => 470,
											'resize' => true,
											"dialog_type" => 'EDITOR'
										)
									)
								),
								'ICON' => 'edit-css',
								'TITLE' => GetMessage("main_comp_edit_css"),
								'IN_MENU' => true
							);
						}
						$bWasSep = false;
						if(file_exists($_SERVER["DOCUMENT_ROOT"].$template->GetFolder()."/result_modifier.php"))
						{
							$bWasSep = true;
							$arIcons[] = array('SEPARATOR'=>true);
							$arIcons[] = array(
								'URL' => 'javascript:'.$APPLICATION->GetPopupLink(array(
										'URL' => "/bitrix/admin/public_file_edit_src.php?site=".SITE_ID."&".'path='.urlencode($template->GetFolder()."/result_modifier.php")."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&lang=".LANGUAGE_ID,
										'PARAMS' => array(
											'width' => 770,
											'height' => 470,
											'resize' => true,
											"dialog_type" => 'EDITOR'
										)
									)
								),
								'TITLE' => GetMessage("main_comp_edit_res_mod"),
								'IN_MENU' => true
							);
						}
						if(file_exists($_SERVER["DOCUMENT_ROOT"].$template->GetFolder()."/component_epilog.php"))
						{
							if(!$bWasSep)
								$arIcons[] = array('SEPARATOR'=>true);
							$arIcons[] = array(
								'URL' => 'javascript:'.$APPLICATION->GetPopupLink(array(
										'URL' => "/bitrix/admin/public_file_edit_src.php?site=".SITE_ID."&".'path='.urlencode($template->GetFolder()."/component_epilog.php")."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&lang=".LANGUAGE_ID,
										'PARAMS' => array(
											'width' => 770,
											'height' => 470,
											'resize' => true,
											"dialog_type" => 'EDITOR'
										)
									)
								),
								'TITLE' => GetMessage("main_comp_edit_epilog"),
								'IN_MENU' => true
							);
						}
					}
				}
				elseif($urlCopy <> '')
				{
					//copy template for future editing
					$urlCopy .= '&system_template=Y';
					$arIcons[] = array(
						'URL'=>'javascript:'.$APPLICATION->GetPopupLink(
								array(
									'URL' => $urlCopy,
									"PARAMS" => Array("min_width" => 450)
								)
							),
						'ICON'=>"edit-2",
						'TITLE'=>GetMessage("main_comp_edit_templ"),
						'ALT'=>GetMessage("main_comp_copy_title"),
						'IN_MENU' => true
					);
				}
			}
		}

		$aAddIcons = array();
		$arPanelParams['COMPONENT'] = $this->componentName;
		if($arComponentDescription && is_array($arComponentDescription))
		{
			$arPanelParams['COMPONENT_DESCRIPTION'] = $arComponentDescription;

			//component bar tooltip
			$arPanelParams['TOOLTIP'] = array(
				'TITLE' => $arComponentDescription["NAME"],
				'TEXT' => '('.GetMessage('main_incl_comp_component').' '.$this->componentName.')'.
				(isset($arComponentDescription["DESCRIPTION"]) && $arComponentDescription["DESCRIPTION"] <> ""? '<br />'.$arComponentDescription["DESCRIPTION"]:'')
			);

			//clear cache
			if(array_key_exists("CACHE_PATH", $arComponentDescription) && $USER->CanDoOperation('cache_control'))
			{
				if(strlen($arComponentDescription["CACHE_PATH"]) > 0)
				{
					$arIcons[] = array(
						"URL" => "javascript:jsComponentUtils.ClearCache('component_name=".urlencode(CUtil::addslashes($this->componentName))."&site_id=".SITE_ID."&".bitrix_sessid_get()."');",
						"ICON" => "del-cache",
						"TITLE" => GetMessage("MAIN_BX_COMPONENT_CACHE_CLEAR"),
						'IN_MENU' => true,
					);
					$APPLICATION->aCachedComponents[] = $this->componentName;
				}
			}

			//additional buttons from component description
			if(array_key_exists("AREA_BUTTONS", $arComponentDescription))
			{
				$componentRelativePath = CComponentEngine::MakeComponentPath($this->componentName);
				$localPath = getLocalPath("components".$componentRelativePath);
				foreach($arComponentDescription["AREA_BUTTONS"] as $value)
				{
					if (array_key_exists("SRC", $value))
						$value["SRC"] = $localPath.$value["SRC"];
					$aAddIcons[] = $value;
				}
			}
		}

		if(!empty($arIcons) && !empty($aAddIcons))
			$arIcons[] = array("SEPARATOR"=>true);
		$arIcons = array_merge($arIcons, $aAddIcons);

		$aAddIcons = $this->component->GetIncludeAreaIcons();

		if(!empty($arIcons) && !empty($aAddIcons))
			$arIcons[] = array("SEPARATOR"=>true);
		$arIcons = array_merge($arIcons, $aAddIcons);

		// enable/disable menu item
		if($bComponentAccess && !$this->parentComponent && $this->bSrcFound)
		{
			if(!empty($arIcons))
				$arIcons[] = array("SEPARATOR"=>true);
			$arIcons[] = array(
				"URL" => "javascript:jsComponentUtils.EnableComponent('component_name=".urlencode(CUtil::addslashes($this->componentName)). //$rel_path
					"&lang=".urlencode(CUtil::addslashes(LANGUAGE_ID)).
					"&src_path=".urlencode(CUtil::addslashes($this->sSrcFile)).
					"&src_line=".$this->iSrcLine.
					"&src_site=".urlencode(CUtil::addslashes(SITE_ID)).
					'&active='.($this->bComponentEnabled? 'N':'Y').
					'&'.bitrix_sessid_get()."');",
				"TITLE" => ($this->bComponentEnabled? GetMessage("main_comp_disable"):GetMessage("main_comp_enable")),
				"ALT" => ($this->bComponentEnabled? GetMessage("main_comp_disable_title"):GetMessage("main_comp_enable_title")),
				'IN_MENU' => true,
			);
		}

		if($this->bSrcFound)
			$arPanelParams['COMPONENT_ID'] = md5($arPanelParams['COMPONENT'].'|'.$this->sSrcFile.':'.$this->iSrcLine);

		return array("icons"=>$arIcons, "parameters"=>$arPanelParams);
	}
}
