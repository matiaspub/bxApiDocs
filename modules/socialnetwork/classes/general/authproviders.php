<?
IncludeModuleLangFile(__FILE__);

class CSocNetGroupAuthProvider extends CAuthProvider implements IProviderInterface
{
	public function __construct()
	{
		$this->id = 'socnetgroup';
	}
	
	public function UpdateCodes($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);

		$DB->Query("
			INSERT INTO b_user_access (USER_ID, PROVIDER_ID, ACCESS_CODE)
			SELECT ".$USER_ID.", '".$DB->ForSQL($this->id)."', ".$DB->Concat("'SG'", ($DB->type == "MSSQL" ? "CAST(U2G.GROUP_ID as varchar(17))": "U2G.GROUP_ID"), "'_'", "U2G.ROLE")."
			FROM b_sonet_user2group U2G
			WHERE U2G.USER_ID=".$USER_ID." AND U2G.ROLE IN ('A','E','K')
			UNION
			SELECT ".$USER_ID.", '".$DB->ForSQL($this->id)."', ".$DB->Concat("'SG'", ($DB->type == "MSSQL" ? "CAST(U2G.GROUP_ID as varchar(17))": "U2G.GROUP_ID"), "'_K'")."
			FROM b_sonet_user2group U2G
			WHERE U2G.USER_ID=".$USER_ID." AND U2G.ROLE IN ('A','E')
			UNION
			SELECT ".$USER_ID.", '".$DB->ForSQL($this->id)."', ".$DB->Concat("'SG'", ($DB->type == "MSSQL" ? "CAST(U2G.GROUP_ID as varchar(17))": "U2G.GROUP_ID"), "'_E'")."
			FROM b_sonet_user2group U2G
			WHERE U2G.USER_ID=".$USER_ID." AND U2G.ROLE IN ('A')
		");
	}
	
	public function AjaxRequest($arParams=false)
	{
		global $USER;

		$search = urldecode($_REQUEST['search']);
		$elements = '';
		$arFinderParams = Array(
			"PROVIDER" => $this->id,
			"TYPE" => 4,
		);

		$arFilter = array("%NAME" => $search, "ACTIVE"=>"Y");
		if ($arParams["SITE_ID"] <> '')
		{
			$arFilter["SITE_ID"] = $arParams["SITE_ID"];
		}

		if (!CSocNetUser::IsCurrentUserModuleAdmin($arParams["SITE_ID"], ($arParams["SITE_ID"] <> '' ? true : false)))
		{
			$arFilter["CHECK_PERMISSIONS"] = $USER->GetID();
		}

		$rsGroups = CSocNetGroup::GetList(array("NAME" => "ASC"), $arFilter);
		$rsGroups->NavStart(30);
		while ($arGroup = $rsGroups->NavNext(false))
		{
			$arItem = Array(
				"ID" => "SG".$arGroup['ID'],
				"AVATAR" => '/bitrix/js/main/core/images/access/avatar-user-everyone.png',
				"NAME" => $arGroup['NAME'],
				"DESC" => $arGroup['DESCRIPTION'],
				"CHECKBOX" => array(
					"#ID#_A" => GetMessage("authprov_sg_a"),
					"#ID#_E" => GetMessage("authprov_sg_e"),
					"#ID#_K" => GetMessage("authprov_sg_k"),
				),
			);
			if($arGroup["IMAGE_ID"])
			{
				$imageFile = CFile::GetFileArray($arGroup["IMAGE_ID"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 30, "height" => 30),
						BX_RESIZE_IMAGE_PROPORTIONAL,
						false
					);
					$arItem["AVATAR"] = $arFileTmp["src"];
				}
			}
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}

		return $elements;
	}

	public function GetFormHtml($arParams=false)
	{
		global $USER;

		if (
			is_array($arParams["socnetgroups"])
			&& $arParams["socnetgroups"]["disabled"] == "true"
		)
		{
			return false;
		}

		$currElements = '';
		if (
			is_array($arParams[$this->id])
			&& ($group_id = intval($arParams[$this->id]["group_id"])) > 0
		)
		{
			$arFinderParams = Array(
				"PROVIDER" => $this->id,
				"TYPE" => 4,
			);

			$arFilter = array(
				"ID" => $group_id,
				"ACTIVE" => "Y"
			);
			if (!CSocNetUser::IsCurrentUserModuleAdmin($arParams["SITE_ID"]))
			{
				$arFilter["CHECK_PERMISSIONS"] = $USER->GetID();
			}

			$rsGroups = CSocNetGroup::GetList(array(), $arFilter);
			if($arGroup = $rsGroups->Fetch())
			{
				$arItem = Array(
					"ID" => "SG".$arGroup['ID'],
					"AVATAR" => '/bitrix/js/main/core/images/access/avatar-user-everyone.png',
					"NAME" => $arGroup['NAME'],
					"DESC" => $arGroup['DESCRIPTION'],
					"OPEN" => "Y",
					"CHECKBOX" => array(
						"#ID#_A" => GetMessage("authprov_sg_a"),
						"#ID#_E" => GetMessage("authprov_sg_e"),
						"#ID#_K" => GetMessage("authprov_sg_k"),
					),
				);
				if($arGroup["IMAGE_ID"])
				{
					$imageFile = CFile::GetFileArray($arGroup["IMAGE_ID"]);
					if ($imageFile !== false)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$imageFile,
							array("width" => 30, "height" => 30),
							BX_RESIZE_IMAGE_PROPORTIONAL,
							false
						);
						$arItem["AVATAR"] = $arFileTmp["src"];
					}
				}
				$currElements .= CFinder::GetFinderItem($arFinderParams, $arItem);
			}
		}

		$elements = "";
		$arFinderParams = Array(
			"PROVIDER" => $this->id,
			"TYPE" => 3,
		);

		$arLRU = CAccess::GetLastRecentlyUsed($this->id);

		if (!empty($arLRU))
		{
			$arLast = array();
			$arLastID = array();
			$arElements = array();

			foreach($arLRU as $val) 
			{
				if (preg_match('/^SG([0-9]+)_([A-Z])/', $val, $match))
				{
					$arLast[$match[2]][$match[1]] = $match[1];
					$arLastID[$match[1]] = $match[1];
				}
			}

			if (!empty($arLastID))
			{
				$arFilter = array(
					"ID" => $arLastID,
					"ACTIVE" => "Y"
				);
				if($arParams["SITE_ID"] <> '')
				{
					$arFilter["SITE_ID"] = $arParams["SITE_ID"];
				}
				if(!CSocNetUser::IsCurrentUserModuleAdmin($arParams["SITE_ID"]))
				{
					$arFilter["CHECK_PERMISSIONS"] = $USER->GetID();
				}

				$rsGroups = CSocNetGroup::GetList(array("NAME" => "ASC"), $arFilter);
				while($arGroup = $rsGroups->Fetch())
				{
					$arItem = Array(
						"ID" => $arGroup['ID'],
						"AVATAR" => '/bitrix/js/main/core/images/access/avatar-user-everyone.png',
						"NAME" => $arGroup['NAME'],
						"DESC" => $arGroup['DESCRIPTION'],
					);
					if($arGroup["IMAGE_ID"])
					{
						$imageFile = CFile::GetFileArray($arGroup["IMAGE_ID"]);
						if ($imageFile !== false)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$imageFile,
								array("width" => 30, "height" => 30),
								BX_RESIZE_IMAGE_PROPORTIONAL,
								false
							);
							$arItem["AVATAR"] = $arFileTmp["src"];
						}
					}
					$arElements[$arItem['ID']] = $arItem;
				}

				foreach($arLRU as $val) 
				{
					if (preg_match('/^SG([0-9]+)_([A-Z])/', $val, $match))
					{
						if (isset($arElements[$match[1]]))
						{
							$arItem = $arElements[$match[1]];
							if ($match[2] == 'K')
							{
								$arItem['ID'] = 'SG'.$arElements[$match[1]]['ID'].'_K';
								$arItem['NAME'] = $arElements[$match[1]]['NAME'].': '.GetMessage("authprov_sg_k");
							}
							else if ($match[2] == 'E')
							{
								$arItem['ID'] = 'SG'.$arElements[$match[1]]['ID'].'_E';
								$arItem['NAME'] = $arElements[$match[1]]['NAME'].': '.GetMessage("authprov_sg_e");
							}
							else if ($match[2] == 'A')
							{
								$arItem['ID'] = 'SG'.$arElements[$match[1]]['ID'].'_A';
								$arItem['NAME'] = $arElements[$match[1]]['NAME'].': '.GetMessage("authprov_sg_a");
							}
							$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
						}
					}
				}
			}
		}

		$arFinderParams = Array(
			"PROVIDER" => $this->id,
			"TYPE" => 4,
		);

		$arFilter = array(
			"USER_ID" => $USER->GetID(),
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_ACTIVE" => "Y"
		);
		if($arParams["SITE_ID"] <> '')
		{
			$arFilter["GROUP_SITE_ID"] = $arParams["SITE_ID"];
		}

		$rsGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "GROUP_ID", "GROUP_NAME", "GROUP_DESCRIPTION", "GROUP_IMAGE_ID")
		);

		$myElements = '';
		while($arGroup = $rsGroups->Fetch())
		{
			$arItem = Array(
				"ID" => "SG".$arGroup['GROUP_ID'],
				"AVATAR" => $arGroup['GROUP_IMAGE_ID'],
				"NAME" => $arGroup['GROUP_NAME'],
				"DESC" => $arGroup['GROUP_DESCRIPTION'],
				"CHECKBOX" => array(
					"#ID#_A" => GetMessage("authprov_sg_a"),
					"#ID#_E" => GetMessage("authprov_sg_e"),
					"#ID#_K" => GetMessage("authprov_sg_k"),
				),
			);
			if($arGroup["GROUP_IMAGE_ID"])
			{
				$imageFile = CFile::GetFileArray($arGroup["GROUP_IMAGE_ID"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 30, "height" => 30),
						BX_RESIZE_IMAGE_PROPORTIONAL,
						false
					);
					$arItem["AVATAR"] = $arFileTmp["src"];
				}
			}
			$myElements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		
		$arPanels = array();
		if($currElements <> '')
		{
			$arPanels[] = array(
				"NAME" => GetMessage("authprov_sg_current"),
				"ELEMENTS" => $currElements,
			);
		}
		$arPanels[] = array(
			"NAME" => GetMessage("authprov_sg_panel_last"),
			"ELEMENTS" => $elements,
		);
		$arPanels[] = array(
			"NAME" => GetMessage("authprov_sg_panel_my_group"),
			"ELEMENTS" => $myElements,
		);
		$arPanels[] = array(
			"NAME" => GetMessage("authprov_sg_panel_search"),
			"ELEMENTS" => CFinder::GetFinderItem(Array("TYPE" => "text"), Array("TEXT" => GetMessage("authprov_sg_panel_search_text"))),
			"SEARCH" => "Y",
		);
		$html = CFinder::GetFinderAppearance($arFinderParams, $arPanels);

		return array("HTML"=>$html, "SELECTED"=>($currElements <> ''));
	}

	static public function GetNames($arCodes)
	{
		$arID = array();
		foreach ($arCodes as $code)
		{
			if (preg_match('/^SG([0-9]+)_[A-Z]$/', $code, $match))
			{
				$arID[] = $match[1];
			}
		}

		if(!empty($arID))
		{
			$arResult = array();
			$rsGroups = CSocNetGroup::GetList(array(), array("ID"=>$arID));
			while($arGroup = $rsGroups->Fetch())
			{
				$arResult["SG".$arGroup["ID"]."_A"] = array("provider" => GetMessage("authprov_sg_socnet_group"), "name"=>$arGroup["NAME"].": ".GetMessage("authprov_sg_a"));
				$arResult["SG".$arGroup["ID"]."_E"] = array("provider" => GetMessage("authprov_sg_socnet_group"), "name"=>$arGroup["NAME"].": ".GetMessage("authprov_sg_e"));
				$arResult["SG".$arGroup["ID"]."_K"] = array("provider" => GetMessage("authprov_sg_socnet_group"), "name"=>$arGroup["NAME"].": ".GetMessage("authprov_sg_k"));
			}
			return $arResult;
		}
		return false;
	}

	public static function GetProviders()
	{
		return array(
			array(
				"ID" => "socnetgroup",
				"NAME" => GetMessage("authprov_sg_name"),
				"PROVIDER_NAME" => GetMessage("authprov_sg_socnet_group"), 
				"SORT" => 400,
				"CLASS" => "CSocNetGroupAuthProvider",
			),
			array(
				"ID" => "socnetuser",
				"CLASS" => "CSocNetUserAuthProvider",
			),
		);
	}
}

class CSocNetUserAuthProvider extends CAuthProvider
{
	public function __construct()
	{
		$this->id = 'socnetuser';
	}

	public function UpdateCodes($USER_ID)
	{
		global $DB;
		if(CSocNetUser::IsFriendsAllowed())
		{
			$USER_ID = intval($USER_ID);

			$dbFriends = CSocNetUserRelations::GetRelatedUsers($USER_ID, SONET_RELATIONS_FRIEND);
			while ($arFriends = $dbFriends->Fetch())
			{
				$friendID = (($USER_ID == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"]);
				$DB->Query("INSERT INTO b_user_access (USER_ID, PROVIDER_ID, ACCESS_CODE) VALUES 
					(".$friendID.", '".$DB->ForSQL($this->id)."', 'SU".$USER_ID."_".SONET_RELATIONS_TYPE_FRIENDS."')");
			}
		}
	}
}
?>