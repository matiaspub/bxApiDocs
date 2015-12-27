<?
IncludeModuleLangFile(__FILE__);

/**
 * Class CCatalogMenu
 */
class CCatalogMenu extends CAdminMenu
{
	/**
	 * @var string
	 */
	static protected $urlCurrent = '';

	/**
	 * @param $level
	 * @return mixed
	 */
	private function _get_menu_item_width($level)
	{
		static $START_MAGIC_NUMBER = 30, $STEP_MAGIC_NUMBER = 21;
		return $START_MAGIC_NUMBER + $level*$STEP_MAGIC_NUMBER;
	}

	/**
	 * @param $level
	 * @return mixed
	 */
	private function _get_menu_item_padding($level)
	{
		static $ADDED_MAGIC_NUMBER = 8;
		return $this->_get_menu_item_width($level) + $ADDED_MAGIC_NUMBER;
	}

	/**
	 * @param $aMenu
	 * @param int $level
	 * @param string $urlCurrent
	 * @return string
	 */
	public function Show($aMenu, $level = 0, $urlCurrent = '')
	{
		if(!static::$urlCurrent)
			static::$urlCurrent = $urlCurrent;

		$scripts = '';
		$aMenu["module_id"] = 'iblock';

		$bSubmenu = (isset($aMenu["items"]) && is_array($aMenu["items"]) && !empty($aMenu["items"])) || isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true;
		$bSectionActive = isset($aMenu["items_id"]) && (in_array($aMenu["items_id"], array_keys($this->aActiveSections)) || $this->IsSectionActive($aMenu["items_id"]));

		$icon = isset($aMenu["icon"]) && $aMenu["icon"] <> ""
			? '<span class="adm-submenu-item-link-icon '.$aMenu["icon"].'"></span>'
			//			: ($level < 1 ? '<span class="adm-submenu-item-link-icon" id="default_menu_icon"></span>' : '');
			: '';
		$id = 'menu_item_'.RandString(10);
		?><div class="adm-sub-submenu-block<?=$level > 0 ? ' adm-submenu-level-'.($level+1) : ''?><?=$bSectionActive && isset($aMenu["items"]) && is_array($aMenu["items"]) && count($aMenu['items']) > 0 ? ' adm-sub-submenu-open' : ''?><?=$aMenu["_active"] ? ' adm-submenu-item-active' : ''?>"><?
		?><div class="adm-submenu-item-name<?=!$bSubmenu ? ' adm-submenu-no-children' : ''?>" id="<?=$id?>" <?=isset($aMenu['fav_id']) ? ' data-fav-id="'.intval($aMenu['fav_id']).'"' : ''?>><?
		$onclick = '';
		if ($bSubmenu)
		{
			if(isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true && (!$aMenu["items"] || count($aMenu["items"]) <= 0))
			{
				$onclick = "BX.adminMenu.toggleDynSection(".$this->_get_menu_item_width($level).", this.parentNode.parentNode, '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["module_id"]))."', '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))."', '".($level+1)."', '".CUtil::JSEscape(htmlspecialcharsbx(static::$urlCurrent))."')";
			}
			elseif(!$aMenu["dynamic"] || !$bSectionActive || $aMenu['dynamic'] && $bSectionActive && isset($aMenu["items"]) && count($aMenu["items"]) > 0)
			{
				$onclick = "BX.adminMenu.toggleSection(this.parentNode.parentNode, '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))."', '".($level+1)."')";
			} //endif;
		}

		?><span class="adm-submenu-item-arrow"<?=$level > 0 ? ' style="width:'.$this->_get_menu_item_width($level).'px;"' : ''?><?=$onclick ? ' onclick="'.$onclick.'"' : ''?>><span class="adm-submenu-item-arrow-icon"></span></span><?

		if(isset($aMenu["url"]) && $aMenu["url"] <> ""):
		?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="<?=htmlspecialcharsbx($aMenu["url"]);?>"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
		elseif ($bSubmenu):
		if(isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true && !$bSectionActive && (!$aMenu["items"] || count($aMenu["items"]) <= 0)):
		?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="javascript:void(0)" onclick="BX.adminMenu.toggleDynSection(<?=$this->_get_menu_item_width($level-1)?>, this.parentNode.parentNode, '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["module_id"]))?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))?>', '<?=$level+1?>', '<?=CUtil::JSEscape(htmlspecialcharsbx(static::$urlCurrent))?>')"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
		elseif(!$aMenu["dynamic"] || !$bSectionActive || $aMenu['dynamic'] && $bSectionActive && isset($aMenu["items"]) && count($aMenu["items"]) > 0):
		?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="javascript:void(0)" onclick="BX.adminMenu.toggleSection(this.parentNode.parentNode, '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))?>', '<?=$level+1?>')"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
		endif;
		else:
		?><span class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px"' : ''?>><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></span><?
		endif;
		?></div><?

		if(($bSubmenu || (isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true)) && is_array($aMenu["items"]))
		{
			echo  '<div class="adm-sub-submenu-block-children">';
			foreach($aMenu["items"] as $submenu)
			{
				if($submenu)
					$scripts .= $this->Show($submenu, $level+1);
			}
			echo "</div>";
		}
		else
			echo  '<div class="adm-sub-submenu-block-children"></div>';
		?></div><?
		if (isset($aMenu["fav_id"]))
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {FAV_ID:'".CUtil::JSEscape($aMenu['fav_id'])."'});";
		}
		elseif (isset($aMenu["items_id"]) && $aMenu['url'])
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {ID:'".CUtil::JSEscape($aMenu['items_id'])."', URL:'".CUtil::JSEscape(htmlspecialcharsback($aMenu['url']))."', MODULE_ID:'".$aMenu['module_id']."'});";
		}
		elseif (isset($aMenu["items_id"]))
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {ID:'".CUtil::JSEscape($aMenu['items_id'])."', MODULE_ID:'".$aMenu['module_id']."'});";
		}
		elseif ($aMenu['url'])
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {URL:'".CUtil::JSEscape(htmlspecialcharsback($aMenu['url']))."'});";
		}

		return $scripts;
	}

	/**
	 * @param $menu_id
	 * @param string $urlBack
	 * @param string $mode
	 */
	public function ShowSubmenu($menu_id, $urlBack, $mode="menu")
	{
		foreach($this->aGlobalMenu as $key=>$menu)
			if($this->_ShowSubmenu($this->aGlobalMenu[$key], $menu_id, $mode, $urlBack))
				break;
	}

	/**
	 * @param $aMenu
	 * @param $menu_id
	 * @param $mode
	 * @param int $urlBack
	 * @param int $level
	 * @return bool
	 */
	public function _ShowSubmenu(&$aMenu, $menu_id, $mode, $urlBack, $level=0)
	{
		$bSubmenu = (is_array($aMenu["items"]) && count($aMenu["items"])>0);

		if($bSubmenu)
		{
			if($aMenu["items_id"] == $menu_id)
			{
				if($mode == "menu")
				{
					$menuScripts = "";
					foreach($aMenu["items"] as $submenu)
					{
						if(is_array($submenu))
						{
							if($level >= 3)
								$level = $level - 3;
							if($urlBack)
								$submenu = self::fReplaceUrl($submenu, $urlBack);
							$menuScripts .= $this->Show($submenu, $level, $urlBack);
						}
					}
					if($menuScripts != "")
						echo '<script type="text/javascript">'.$menuScripts.'</script>';
				}

				return true;
			}
			else
			{
				foreach($aMenu["items"] as $submenu)
					if($this->_ShowSubmenu($submenu, $menu_id, $mode, $urlBack, $level+1))
						return true;
			}
		}
		return false;
	}

	/**
	 * @param $submenu
	 * @param $urlCurrent
	 * @return mixed
	 */
	public static function fReplaceUrl($submenu, $urlCurrent)
	{
		$urlCurrentDefault = $urlCurrent;

		$arUrlAdd = array("set_filter" => "Y");

		$url = $submenu["url"];
		$urlParse = parse_url($url);
		$arUrlTag = explode("&", $urlParse["query"]);

		foreach ($arUrlTag as $tag)
		{
			$tmp = explode("=", $tag);
			if ($tmp[0] == "IBLOCK_ID" || $tmp[0] == "find_section_section")
			{
				if ($tmp[0] == "find_section_section")
					$tmp[0] = "filter_section";

				$urlCurrent = CHTTP::urlDeleteParams($urlCurrent, array($tmp[0]));
				$arUrlAdd[$tmp[0]] = $tmp[1];
			}
		}

		$url = CHTTP::urlAddParams($urlCurrent, $arUrlAdd, array("encode", "skip_empty"));
		$submenu["url"] = $url;

		if (isset($submenu["items"]) && count($submenu["items"]) > 0)
		{
			$subCatalog = self::fReplaceUrl($submenu["items"], $urlCurrentDefault);
			$submenu["items"] = $subCatalog;
		}

		return $submenu;
	}
}