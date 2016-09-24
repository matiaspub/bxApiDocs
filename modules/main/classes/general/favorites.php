<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CAllFavorites extends CDBResult
{
	public static function err_mess()
	{
		return "<br>Class: CFavorites<br>File: ".__FILE__;
	}

	public static function GetIDByUrl($url)
	{
		global $USER;

		if($url == "")
			return 0;

		$paresedUrl = CBXFavUrls::ParseDetail($url);
		if($paresedUrl !== false)
		{
			$pathInfo = pathinfo($paresedUrl["path"]);

			$dbFav = CFavorites::GetList(array(), array(
				"URL" => "'%".$pathInfo["basename"]."%'",
				"MENU_FOR_USER" => $USER->GetID(),
				"LANGUAGE_ID" => LANGUAGE_ID,
			));
			while($arFav = $dbFav->Fetch())
				if(CBXFavUrls::Compare($paresedUrl, $arFav["URL"]))
				{
					return $arFav["ID"];
				}
		}

		return 0;
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0)
			return false;

		return ($DB->Query("
			SELECT F.*,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")." as TIMESTAMP_X,
				".$DB->DateToCharFunction("F.DATE_CREATE")." as	DATE_CREATE
			FROM b_favorite F
			WHERE ID=".$ID,
			false, "File: ".__FILE__."<br>Line: ".__LINE__)
		);
	}

	public static function CheckFields($arFields)
	{
		global $APPLICATION;

		$aMsg = array();
		if(is_set($arFields, "NAME") && trim($arFields["NAME"])=="")
			$aMsg[] = array("id"=>"NAME", "text"=>GetMessage("fav_general_err_name"));
		if(is_set($arFields, "URL") && trim($arFields["URL"])=="")
			$aMsg[] = array("id"=>"URL", "text"=>GetMessage("fav_general_err_url"));
		if(is_set($arFields, "USER_ID"))
		{
			if(intval($arFields["USER_ID"]) > 0)
			{
				$res = CUser::GetByID(intval($arFields["USER_ID"]));
				if(!$res->Fetch())
					$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("fav_general_err_user"));
			}
			elseif($arFields["COMMON"] == "N")
			{
				$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("fav_general_err_user1"));
			}
		}
		if(is_set($arFields, "LANGUAGE_ID"))
		{
			if($arFields["LANGUAGE_ID"] <> "")
			{
				$res = CLanguage::GetByID($arFields["LANGUAGE_ID"]);
				if(!$res->Fetch())
					$aMsg[] = array("id"=>"LANGUAGE_ID", "text"=>GetMessage("fav_general_err_lang"));
			}
			else
			{
				$aMsg[] = array("id"=>"LANGUAGE_ID", "text"=>GetMessage("fav_general_err_lang1"));
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function IsExistDuplicate($arFields)
	{
		if(!isset($arFields["MENU_ID"]) && !isset($arFields["URL"]) && !isset($arFields["NAME"]))
			return false;

		global $USER, $DB;

		$uid = $USER->GetID();

		$strSql = "SELECT MENU_ID, URL, ID FROM b_favorite  WHERE ( ";

		if(isset($arFields["MENU_ID"]))
			$strSql .= "MENU_ID = '".$DB->ForSql($arFields["MENU_ID"])."' AND ";

		if(isset($arFields["URL"]))
			$strSql .= "URL = '".$DB->ForSql($arFields["URL"])."' AND ";

		if(isset($arFields["NAME"]))
			$strSql .= "NAME = '".$DB->ForSql($arFields["NAME"])."' AND ";

		$strSql .="( USER_ID=".$uid." OR COMMON='Y' ))";

		$dbFav = $DB->Query($strSql);

		while ($arFav = $dbFav->GetNext())
			if($arFields["MENU_ID"] == $arFav["MENU_ID"] || $arFields["URL"] == $arFav["URL"] || $arFields["NAME"] == $arFav["NAME"])
				return $arFav["ID"];

		return false;
	}

	//Addition
	public static function Add($arFields, $checkDuplicate = false)
	{
		global $DB;

		if(!CFavorites::CheckFields($arFields))
			return false;

		if($checkDuplicate)
		{
			$duplicate = CFavorites::IsExistDuplicate($arFields);

			if($duplicate)
				return $duplicate;
		}

		$codes = new CHotKeysCode;
		$codeID=$codes->Add(array(
			"CODE"=>"location.href='".$arFields["URL"]."';",
			"NAME"=>$arFields["NAME"],
			"COMMENTS"=>"FAVORITES",
		));

		$codes->Update($codeID,array(
			"CLASS_NAME"=>"FAV-".$codeID,
			"TITLE_OBJ"=>"FAV-".$codeID,
		));

		$arFields["CODE_ID"]=intval($codeID);

		$ID = $DB->Add("b_favorite", $arFields);
		return $ID;
	}

	//Update
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if(!CFavorites::CheckFields($arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_favorite", $arFields);
		if($strUpdate!="")
		{
			$strSql = "UPDATE b_favorite SET ".$strUpdate." WHERE ID=".$ID;
			if(!$DB->Query($strSql))
				return false;
		}
		return true;
	}

	// delete by ID
	public static function Delete($ID)
	{
		global $DB;
		$codes = new CHotKeysCode;

		$res = CFavorites::GetByID($ID);

		while($arFav = $res->Fetch())
			$codes->Delete($arFav["CODE_ID"]);

		return ($DB->Query("DELETE FROM b_favorite WHERE ID='".intval($ID)."'", false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}

	//*****************************
	// Events
	//*****************************

	//user deletion event
	public static function OnUserDelete($user_id)
	{
		global $DB;
		return ($DB->Query("DELETE FROM b_favorite WHERE USER_ID=". intval($user_id), false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}

	//interface language delete event
	public static function OnLanguageDelete($language_id)
	{
		global $DB;
		return ($DB->Query("DELETE FROM b_favorite WHERE LANGUAGE_ID='".$DB->ForSQL($language_id, 2)."'", false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}
}

class CBXFavAdmMenu
{
	private $arItems = array();

	public function __construct()
	{
		$this->Init();
	}

	private function Init()
	{
		global $APPLICATION, $USER, $adminPage, $adminMenu;

		//for ajax requests, and menu autoupdates
		$adminPage->Init();
		$adminMenu->Init($adminPage->aModules);

		$dbFav = CFavorites::GetList(
			array(
				"COMMON" => "ASC",
				"SORT" => "ASC",
				"NAME" => "ASC",
			), array(
				"MENU_FOR_USER" => $USER->GetID(),
				"LANGUAGE_ID" => LANGUAGE_ID,
			)
		);

		while ($arFav = $dbFav->GetNext())
		{
			if($arFav["COMMON"] == "Y" && $arFav["MODULE_ID"] <> "" && $APPLICATION->GetGroupRight($arFav["MODULE_ID"]) < "R")
			{
				continue;
			}

			$this->arItems[] = $arFav;
		}

		return true;
	}

	public function GetMenuItem($itemsID, $arMenu)
	{
		if(!is_array($arMenu))
			return false;

		foreach ($arMenu as $arItem)
		{
			if( isset($arItem["items_id"]) && $arItem["items_id"] == $itemsID)
			{
				return $arItem;
			}
			else
			{
				if(is_array($arItem) && !empty($arItem))
				{
					$arFindItem = $this->GetMenuItem($itemsID, $arItem);

					if(is_array($arFindItem) && !empty($arFindItem))
						return $arFindItem;
				}
			}
		}

		return false;
	}

	public function GenerateItems()
	{
		global $adminMenu;

		$favOptions = CUserOptions::GetOption('favorite', 'favorite_menu', array("stick" => "N"));

		$aMenu = array();

		foreach ($this->arItems as $arItem)
		{
			$tmpMenu = array();

			if($arItem["MENU_ID"])
				$tmpMenu = $this->GetMenuItem($arItem["MENU_ID"], $adminMenu->aGlobalMenu);

			if(!$arItem["MENU_ID"] || !is_array($tmpMenu) || empty($tmpMenu))
			{
				$tmpMenu = array(
					"text" => $arItem["NAME"],
					"url" => $arItem["URL"],
					"dynamic" => false,
					"items_id" => "menu_favorite_".$arItem["ID"],
					"title" => $arItem["NAME"],
					"icon" => "fav_menu_icon",
					"page_icon" => "fav_page_icon"
				);
			}

			if(is_array($tmpMenu))
			{
				$tmpMenu["fav_id"] = $arItem["ID"];
				$tmpMenu["parent_menu"] = "global_menu_desktop";

				if (!isset($tmpMenu['icon']) || strlen($tmpMenu['icon']) <= 0)
					$tmpMenu['icon'] = 'fav_menu_icon';

				if($this->CheckItemActivity($tmpMenu))
					$tmpMenu["_active"] = true;

				if(($tmpMenu["_active"] || $this->CheckSubItemActivity($tmpMenu)) && $favOptions["stick"] == "Y")
					$GLOBALS["BX_FAVORITE_MENU_ACTIVE_ID"] = true;

				$aMenu[] = $tmpMenu;
			}
		}

		return $aMenu;
	}

	private function CheckSubItemActivity($arMenu)
	{
		if(!isset($arMenu["items"]) || !is_array($arMenu["items"]))
			return false;

		foreach ($arMenu["items"] as $menu)
		{
			if(isset($menu["_active"]) && isset($menu["_active"]) == true)
				return true;

			if($this->CheckSubItemActivity($menu))
				return true;
		}

		return false;
	}

	private function CheckItemActivity($arMenu)
	{
		if($arMenu["_active"] == true )
			return true;

		global $adminMenu, $APPLICATION;

		if(empty($adminMenu->aActiveSections))
			return false;

		$currentUrl = $APPLICATION->GetCurPageParam();
		$menuUrl = htmlspecialcharsback($arMenu["url"]);

		if(CBXFavUrls::Compare($menuUrl, $currentUrl))
			return true;

		$activeSectUrl = htmlspecialcharsback($adminMenu->aActiveSections["_active"]["url"]);

		if(CBXFavUrls::Compare($menuUrl, $activeSectUrl))
			return true;

		return $this->CheckFilterActivity($currentUrl, $menuUrl, $activeSectUrl);
	}

	private function CheckFilterActivity($currentUrl, $menuUrl, $activeSectUrl)
	{
		if(!CBXFavUrls::Compare($menuUrl, $activeSectUrl))
			return false;

		$curUrlFilterId = CBXFavUrls::GetFilterId($currentUrl);

		if($curUrlFilterId == CBXFavUrls::GetFilterId($menuUrl))
			return true;

		if($curUrlFilterId && $curUrlFilterId == CBXFavUrls::GetPresetId($menuUrl))
			return true;

		if(CBXFavUrls::GetPresetId($currentUrl) && CBXFavUrls::GetFilterId($menuUrl) == CBXFavUrls::GetPresetId($currentUrl))
			return true;

		return false;
	}

	public function GenerateMenuHTML($id = 0)
	{
		global $adminMenu;
		$buff = "";

		$menuItems = $this->GenerateItems();

		if(empty($menuItems))
		{
			$buff .= self::GetEmptyMenuHTML();
		}
		else
		{
			ob_start();

			echo '<script type="text/javascript" bxrunfirst="true">BX.adminFav.setLastId('.intval($id).');</script>';

			$menuScripts = '';
			foreach ($menuItems as $arItem)
				$menuScripts .= $adminMenu->Show($arItem);

			echo '<script type="text/javascript">'.$menuScripts.'</script>';

			$buff .= ob_get_contents();
			ob_end_clean();
		}

		$buff.= self::GetMenuHintHTML(empty($menuItems));

		return $buff;
	}

	public static function GetEmptyMenuHTML()
	{
		return '
<div class="adm-favorites-cap-text">
	'.GetMessage("fav_main_menu_nothing").'
</div>';
	}

	public static function GetMenuHintHTML($IsMenuEmpty)
	{
		$favHintOptions = CUserOptions::GetOption('favorites_menu', "hint", array("hide" => "N"));

		if(!$IsMenuEmpty && $favHintOptions["hide"] == "Y")
			return false;

		$retHtml = '
<div id="adm-favorites-cap-hint-block" class="adm-favorites-cap-hint-block">
	<div class="adm-favorites-cap-hint-icon icon-1"></div>
	<div class="adm-favorites-cap-hint-text">
		'.GetMessage("fav_main_menu_add_icon").'
	</div>
	<div class="adm-favorites-cap-hint-icon icon-2"></div>
	<div class="adm-favorites-cap-hint-text">
		'.GetMessage("fav_main_menu_add_dd").'
	</div>';


		if(!$IsMenuEmpty)
			$retHtml .='
	<a class="adm-favorites-cap-remove" href="javascript:void(0);" onclick="BX.adminFav.closeHint(this);">'.GetMessage("fav_main_menu_close_hint").'</a>';

		$retHtml .= '
</div>';

		return $retHtml;

	}
}

class CBXFavUrls
{
	const FILTER_ID_VALUE = "adm_filter_applied";
	const PRESET_ID_VALUE = "adm_filter_preset";

	public static function Compare($url1, $url2, $arReqVals=array(), $arSkipVals=array())
	{
		if($url1=='' && $url2 == '')
			return false;

		if(is_array($url1))
			$arUrl1 = $url1;
		elseif(is_string($url1))
			$arUrl1 = self::ParseDetail($url1);
		else
			return false;

		$arUrl2 = self::ParseDetail($url2);

		if(isset($arUrl1["path"]) && isset($arUrl2["path"]) && $arUrl1["path"] != $arUrl2["path"])
		{
			$urlPath1 = pathinfo($arUrl1["path"]);
			$urlPath2 = pathinfo($arUrl2["path"]);

			if(
				isset($urlPath1["dirname"])
				&& $urlPath1["dirname"] != '.'
				&& isset($urlPath2["dirname"])
				&& $urlPath2["dirname"] != '.'
				&& $urlPath1["dirname"] != $urlPath2["dirname"]
			)
			{
				return false;
			}

			if(isset($urlPath1["basename"]) && isset($urlPath2["basename"]) && $urlPath1["basename"] != $urlPath2["basename"])
				return false;
		}

		if(isset($arUrl1["host"]) && isset($arUrl2["host"]) && $arUrl1["host"]!=$arUrl2["host"])
			return false;

		if(isset($arUrl1["query"]) && isset($arUrl2["query"]) && $arUrl1["query"] == $arUrl2["query"])
			return true;

		if(is_array($arUrl1["ar_query"]) && is_array($arUrl2["ar_query"]))
		{
			foreach ($arUrl1["ar_query"] as $valName => $value)
			{
				if($arUrl1["ar_query"][$valName] != $arUrl2["ar_query"][$valName])
				{
					if(!empty($arReqVals))
					{
						if(in_array($valName,$arReqVals))
							return false;

						continue;
					}
					if(!empty($arSkipVals))
					{
						if(in_array($valName,$arSkipVals))
							continue;

						return false;
					}

					return false;
				}
			}

			if(!empty($arReqVals))
			{
				foreach ($arReqVals as $valName => $value)
				{
					if(isset($arUrl2["ar_query"][$valName]))
					{
						if(!isset($arUrl1["ar_query"][$valName]))
							return false;

						if($arUrl1["ar_query"][$valName] != $arUrl2["ar_query"][$valName])
							return false;
					}
				}

			}
		}

		return true;
	}

	public static function ParseDetail($url)
	{
		$parts = parse_url($url);

		if(isset($parts['query']))
			parse_str(urldecode($parts['query']), $parts['ar_query']);

		return $parts;
	}

	public static function GetFilterId($url)
	{
		$urlParams = self::ParseDetail($url);

		if(isset($urlParams["ar_query"][self::FILTER_ID_VALUE]) && $urlParams["ar_query"][self::FILTER_ID_VALUE]!="")
			return $urlParams["ar_query"][self::FILTER_ID_VALUE];

		return false;
	}

	public static function GetPresetId($url)
	{
		$urlParams = self::ParseDetail($url);

		if(isset($urlParams["ar_query"][self::PRESET_ID_VALUE]) && $urlParams["ar_query"][self::PRESET_ID_VALUE]!="")
			return $urlParams["ar_query"][self::PRESET_ID_VALUE];

		return false;
	}
}
