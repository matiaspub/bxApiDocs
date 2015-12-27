<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/group.php");


/**
 * <b>CSocNetGroup</b> - класс для работы с рабочими группами социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/index.php
 * @author Bitrix
 */
class CSocNetGroup extends CAllSocNetGroup
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetGroup::CheckFields("ADD", $arFields))
		{
			return false;
		}
		else
		{
			$arSiteID = array();
			if(array_key_exists("SITE_ID", $arFields))
			{
				if(is_array($arFields["SITE_ID"]))
				{
					foreach($arFields["SITE_ID"] as $site_id)
					{
						$arSiteID[$site_id] = $DB->ForSQL($site_id);
					}
				}
				else
				{
					$arSiteID[$arFields["SITE_ID"]] = $DB->ForSQL($arFields["SITE_ID"]);
				}
			}
		}

		if(empty($arSiteID))
		{
			unset($arFields["SITE_ID"]);
		}
		else
		{
			$arFields["SITE_ID"] = end($arSiteID);
		}

		$arFields["SITE_ID_FULL"] = $arSiteID;
		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetGroupAdd");
		while ($arEvent = $db_events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				return false;
			}
		}
		unset($arFields["SITE_ID_FULL"]);		

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "socialnetwork";

		CFile::SaveForDB($arFields, "IMAGE_ID", "socialnetwork");

		$arInsert = $DB->PrepareInsert("b_sonet_group", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$ID = false;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_sonet_group(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			$events = GetModuleEvents("socialnetwork", "OnSocNetGroupAdd");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));

			if ($ID > 0)
			{
				if(!empty($arSiteID))
				{
					$DB->Query("
						DELETE FROM b_sonet_group_site WHERE GROUP_ID = ".$ID."
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

					$DB->Query("
						INSERT INTO b_sonet_group_site(GROUP_ID, SITE_ID)
						SELECT ".$ID.", LID
						FROM b_lang
						WHERE LID IN ('".implode("', '", $arSiteID)."')
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}

				if(defined("BX_COMP_MANAGED_CACHE"))
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group");

				$GLOBALS["USER_FIELD_MANAGER"]->Update("SONET_GROUP", $ID, $arFields);

				if (CModule::IncludeModule("search"))
				{
					$arGroupNew = CSocNetGroup::GetByID($ID);
					if ($arGroupNew)
					{
						if ($arGroupNew["ACTIVE"] == "Y")
						{
							$BODY = CSocNetTextParser::killAllTags($arGroupNew["~DESCRIPTION"]);
							$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("SONET_GROUP", $ID);

							$arSearchIndexSiteID = array();
							foreach ($arSiteID as $site_id_tmp)
								$arSearchIndexSiteID[$site_id_tmp] = str_replace("#group_id#", $ID, COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $site_id_tmp));

							$arSearchIndex = array(
								"SITE_ID" => $arSearchIndexSiteID,
								"LAST_MODIFIED" => $arGroupNew["DATE_ACTIVITY"],
								"PARAM1" => $arGroupNew["SUBJECT_ID"],
								"PARAM2" => $ID,
								"PARAM3" => "GROUP",
								"PERMISSIONS" => (
									$arGroupNew["VISIBLE"] == "Y"?
										array('G2')://public
										array(
											'SG'.$ID.'_A',//admins
											'SG'.$ID.'_E',//moderators
											'SG'.$ID.'_K',//members
										)
								),
								"PARAMS" =>array(
									"socnet_group" 	=> $ID,
									"entity" 		=> "socnet_group",
								),
								"TITLE" => $arGroupNew["~NAME"],
								"BODY" => $BODY,
								"TAGS" => $arGroupNew["~KEYWORDS"],
							);

							CSearch::Index("socialnetwork", "G".$ID, $arSearchIndex, True);
						}
					}
				}
			}
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры группы с заданным идентификатором.</p> <p><b>Примечание</b>: при работе метода вызываются события <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupUpdate.php">OnBeforeSocNetGroupUpdate</a> и <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupUpdate.php">OnSocNetGroupUpdate</a>.</p>
	*
	*
	* @param int $ID  Идентификатор группы </htm
	*
	* @param array $arFields  Массив параметров группы, которые должны быть изменены. Ключами в
	* массиве являются названия параметров, а значениями - новые
	* значения. Допустимые ключи: <b>SITE_ID</b> - код сайта,<br><b>NAME</b> - название
	* группы,<br><b>DESCRIPTION</b> - описание группы,<br><b>VISIBLE</b> - флаг Y/N - видна ли
	* группа в списке групп,<br><b>OPENED</b> - флаг Y/N - открыта ли группа для
	* свободного вступления,<br><b>SUBJECT_ID</b> - код темы,<br><b>KEYWORDS</b> - ключевые
	* слова,<br><b>IMAGE_ID</b> - иконка группы,<br><b>INITIATE_PERMS</b> - кто имеет право на
	* приглашение пользователей в группу: SONET_ROLES_OWNER - только владелец
	* группы, SONET_ROLES_MODERATOR - владелец группы и модераторы группы,
	* SONET_ROLES_USER - все члены группы,<br><b>CLOSED</b> - флаг Y/N - является ли группа
	* архивной,<br><b>SPAM_PERMS</b> - кто имеет право на отправку сообщений в
	* группу: SONET_ROLES_OWNER - только владелец группы, SONET_ROLES_MODERATOR - владелец
	* группы и модераторы группы, SONET_ROLES_USER - все члены группы.
	*
	* @param bool $bAutoSubscribe = true Флаг автоподписки на уведомления. Необязательный. По умолчанию
	* равен true.
	*
	* @param bool $bClearCommonTag = true Необязательный. По умолчанию равен true.
	*
	* @return int <p>Метод возвращает код изменяемой группы или false в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	* 	"NAME" =&gt; $_POST["GROUP_NAME"],
	* 	"DESCRIPTION" =&gt; $_POST["GROUP_DESCRIPTION"],
	* );
	* if (!CSocNetGroup::Update($ID, $arFields))
	* {
	* 	if ($e = $GLOBALS["APPLICATION"]-&gt;GetException())
	* 		$errorMessage .= $e-&gt;GetString();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields, $bAutoSubscribe = true, $bClearCommonTag = true)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arGroupOld = CSocNetGroup::GetByID($ID);
		if (!$arGroupOld)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
			return false;
		}

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetGroup::CheckFields("UPDATE", $arFields, $ID))
			return false;
		else
		{
			$arSiteID = Array();
			if(is_set($arFields, "SITE_ID"))
			{
				if(is_array($arFields["SITE_ID"]))
					$arSiteID = $arFields["SITE_ID"];
				else
					$arSiteID[] = $arFields["SITE_ID"];

				$arFields["SITE_ID"] = false;
				$str_SiteID = "''";
				foreach($arSiteID as $v)
				{
					$arFields["SITE_ID"] = $v;
					$str_SiteID .= ", '".$DB->ForSql($v)."'";
				}
			}
		}

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetGroupUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "socialnetwork";

		CFile::SaveForDB($arFields, "IMAGE_ID", "socialnetwork");

		$strUpdate = $DB->PrepareUpdate("b_sonet_group", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_group SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			if(count($arSiteID)>0)
			{
				$strSql = "DELETE FROM b_sonet_group_site WHERE GROUP_ID=".$ID;
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$strSql =
					"INSERT INTO b_sonet_group_site(GROUP_ID, SITE_ID) ".
					"SELECT ".$ID.", LID ".
					"FROM b_lang ".
					"WHERE LID IN (".$str_SiteID.") ";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$arLogID = array();

				$dbResult = CSocNetLog::GetList(
					array(),
					array("LOG_RIGHTS" => "SG".$ID),
					false,
					false,
					array("ID")
				);

				while ($arResult = $dbResult->Fetch())
				{
					$DB->Query("DELETE FROM b_sonet_log_site WHERE LOG_ID = ".$arResult["ID"]."", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

					$DB->Query("
						INSERT INTO b_sonet_log_site(LOG_ID, SITE_ID)
						SELECT ".$arResult["ID"].", LID
						FROM b_lang
						WHERE LID IN (".$str_SiteID.")
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

					$arLogID[] = $arResult["ID"];
				}

				$dbResult = CSocNetLog::GetList(
					array(),
					array(
						"!ID" => $arLogID,
						"ENTITY_ID" => $ID, 
						"ENTITY_TYPE" => SONET_ENTITY_GROUP
					),
					false,
					false,
					array("ID")
				);

				while ($arResult = $dbResult->Fetch())
				{
					$DB->Query("DELETE FROM b_sonet_log_site WHERE LOG_ID = ".$arResult["ID"]."", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

					$DB->Query("
						INSERT INTO b_sonet_log_site(LOG_ID, SITE_ID)
						SELECT ".$arResult["ID"].", LID
						FROM b_lang
						WHERE LID IN (".$str_SiteID.")
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}
			}

			unset($GLOBALS["SONET_GROUP_CACHE"][$ID]);
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				if ($bClearCommonTag)
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group");
				}
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group_".$ID);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$ID);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group");
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("SONET_GROUP", $ID, $arFields);

			$events = GetModuleEvents("socialnetwork", "OnSocNetGroupUpdate");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));

			if (CModule::IncludeModule("search"))
			{
				$arGroupNew = CSocNetGroup::GetByID($ID);
				if ($arGroupNew)
				{
					if ($arGroupNew["ACTIVE"] == "N" && $arGroupOld["ACTIVE"] == "Y")
						CSearch::DeleteIndex("socialnetwork", "G".$ID);
					elseif ($arGroupNew["ACTIVE"] == "Y")
					{
						$BODY = CSocNetTextParser::killAllTags($arGroupNew["~DESCRIPTION"]);
						$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("SONET_GROUP", $ID);

						$arSearchIndexSiteID = array();
						$rsGroupSite = CSocNetGroup::GetSite($ID);
						while($arGroupSite = $rsGroupSite->Fetch())
							$arSearchIndexSiteID[$arGroupSite["LID"]] = str_replace("#group_id#", $ID, COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $arGroupSite["LID"]));

						$arSearchIndex = array(
							"SITE_ID" => $arSearchIndexSiteID,
							"LAST_MODIFIED" => $arGroupNew["DATE_ACTIVITY"],
							"PARAM1" => $arGroupNew["SUBJECT_ID"],
							"PARAM2" => $ID,
							"PARAM3" => "GROUP",
							"PERMISSIONS" => (
								$arGroupNew["VISIBLE"] == "Y"?
									array('G2')://public
									array(
										'SG'.$ID.'_A',//admins
										'SG'.$ID.'_E',//moderators
										'SG'.$ID.'_K',//members
									)
							),
							"PARAMS" =>array(
								"socnet_group" 	=> $ID,
								"entity" 		=> "socnet_group",
							),
							"TITLE" => $arGroupNew["~NAME"],
							"BODY" => $BODY,
							"TAGS" => $arGroupNew["~KEYWORDS"],
						);

						CSearch::Index("socialnetwork", "G".$ID, $arSearchIndex, True);
					}

					if ($arGroupNew["OPENED"] == "Y" && $arGroupOld["OPENED"] == "N")
					{
						$dbRequests = CSocNetUserToGroup::GetList(
							array(),
							array(
								"GROUP_ID" => $ID,
								"ROLE" => SONET_ROLES_REQUEST,
								"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER
							),
							false,
							false,
							array("ID")
						);
						if ($dbRequests)
						{
							$arIDs = array();
							while ($arRequests = $dbRequests->GetNext())
								$arIDs[] = $arRequests["ID"];

							CSocNetUserToGroup::ConfirmRequestToBeMember($GLOBALS["USER"]->GetID(), $ID, $arIDs, $bAutoSubscribe);
						}
					}
				}
			}
		}
		else
		{
			if($GLOBALS["USER_FIELD_MANAGER"]->Update("SONET_GROUP", $ID, $arFields))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					if ($bClearCommonTag)
					{
						$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group");
					}
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group_".$ID);
				}
			}
			else
			{
				$ID = False;
			}
		}

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Возвращает список групп в соответствии с фильтром.</p>
	*
	*
	* @param array $arOrder = array("ID" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи: <b>ID</b>,
	* <b>SITE_ID</b>, <b>NAME</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>, <b>DATE_ACTIVITY</b>, <b>ACTIVE</b>,
	* <b>VISIBLE</b>, <b>OPENED</b>, <b>CLOSED</b>, <b>SUBJECT_ID</b>, <b>OWNER_ID</b>, <b>NUMBER_OF_MEMBERS</b>,
	* <b>INITIATE_PERMS</b>, <b>SPAM_PERMS</b>, <b>SUBJECT_NAME</b>, <b>OWNER_NAME</b>, <b>OWNER_LAST_NAME</b>,
	* <b>OWNER_LOGIN</b>.
	*
	* @param DES $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимые
	* поля:<b>ID</b>, <b>SITE_ID</b>, <b>NAME</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>, <b>DATE_ACTIVITY</b>,
	* <b>ACTIVE</b>, <b>VISIBLE</b>, <b>OPENED</b>, <b>CLOSED</b>, <b>SUBJECT_ID</b>, <b>OWNER_ID</b>,
	* <b>NUMBER_OF_MEMBERS</b>, <b>INITIATE_PERMS</b>, <b>SPAM_PERMS</b>, <b>SUBJECT_NAME</b>, <b>OWNER_NAME</b>,
	* <b>OWNER_LAST_NAME</b>, <b>OWNER_LOGIN</b>, <b>CHECK_PERMISSIONS</b>.
	*
	* @param array $arFilter = array() Массив, задающий группировку результирующего списка. Если
	* параметр содержит массив названий полей, то по этим полям будет
	* произведена группировка. Если параметр содержит пустой массив,
	* то метод вернет количество записей, удовлетворяющих фильтру. По
	* умолчанию параметр равен false - не группировать.
	*
	* @param array $arGroupBy = false Массив, задающий условия выбора для организации постраничной
	* навигации.
	*
	* @param array $arNavStartParams = false Массив, задающий выбираемые поля. Содержит список полей, которые
	* должны быть возвращены методом. Если массив пустой, то выбираются
	* поля <b>ID</b>, <b>SITE_ID</b>, <b>NAME</b>, <b>DESCRIPTION</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>,
	* <b>DATE_ACTIVITY</b>, <b>ACTIVE</b>, <b>VISIBLE</b>, <b>OPENED</b>, <b>CLOSED</b>, <b>SUBJECT_ID</b>, <b>OWNER_ID</b>,
	* <b>KEYWORDS</b>, <b>IMAGE_ID</b>, <b>NUMBER_OF_MEMBERS</b>, <b>INITIATE_PERMS</b>, <b>SPAM_PERMS</b>,
	* <b>SUBJECT_NAME</b>. В массиве допустимы любые поля из списка полей.
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Метод возвращает объект типа CDBResult, содержащий записи,
	* удовлетворяющие условию выборки.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/getbyid.php">CSocNetGroup::GetById</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/GetList.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SITE_ID", "NAME", "DESCRIPTION", "DATE_CREATE", "DATE_UPDATE", "ACTIVE", "VISIBLE", "OPENED", "CLOSED", "SUBJECT_ID", "OWNER_ID", "KEYWORDS", "IMAGE_ID", "NUMBER_OF_MEMBERS", "INITIATE_PERMS", "SPAM_PERMS", "DATE_ACTIVITY", "SUBJECT_NAME");

		static $arFields1 = array(
			"ID" => Array("FIELD" => "G.ID", "TYPE" => "int"),
			"NAME" => Array("FIELD" => "G.NAME", "TYPE" => "string"),
			"DESCRIPTION" => Array("FIELD" => "G.DESCRIPTION", "TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD" => "G.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_UPDATE" => Array("FIELD" => "G.DATE_UPDATE", "TYPE" => "datetime"),
			"DATE_ACTIVITY" => Array("FIELD" => "G.DATE_ACTIVITY", "TYPE" => "datetime"),
			"ACTIVE" => Array("FIELD" => "G.ACTIVE", "TYPE" => "string"),
			"VISIBLE" => Array("FIELD" => "G.VISIBLE", "TYPE" => "string"),
			"OPENED" => Array("FIELD" => "G.OPENED", "TYPE" => "string"),
			"CLOSED" => Array("FIELD" => "G.CLOSED", "TYPE" => "string"),
			"SUBJECT_ID" => Array("FIELD" => "G.SUBJECT_ID", "TYPE" => "int"),
			"OWNER_ID" => Array("FIELD" => "G.OWNER_ID", "TYPE" => "int"),
			"KEYWORDS" => Array("FIELD" => "G.KEYWORDS", "TYPE" => "string"),
			"IMAGE_ID" => Array("FIELD" => "G.IMAGE_ID", "TYPE" => "int"),
			"NUMBER_OF_MEMBERS" => Array("FIELD" => "G.NUMBER_OF_MEMBERS", "TYPE" => "int"),
			"NUMBER_OF_MODERATORS" => Array("FIELD" => "G.NUMBER_OF_MODERATORS", "TYPE" => "int"),
			"INITIATE_PERMS" => Array("FIELD" => "G.INITIATE_PERMS", "TYPE" => "string"),
			"SPAM_PERMS" => Array("FIELD" => "G.SPAM_PERMS", "TYPE" => "string"),
			"SUBJECT_NAME" => Array("FIELD" => "S.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group_subject S ON (G.SUBJECT_ID = S.ID)"),
			"OWNER_NAME" => Array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (G.OWNER_ID = U.ID)"),
			"OWNER_LAST_NAME" => Array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (G.OWNER_ID = U.ID)"),
			"OWNER_LOGIN" => Array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (G.OWNER_ID = U.ID)"),
			"OWNER_EMAIL" => Array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (G.OWNER_ID = U.ID)"),
			"OWNER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (G.OWNER_ID = U.ID)")
		);

		if (array_key_exists("SITE_ID", $arFilter))
		{
			$arFields["SITE_ID"] = Array("FIELD" => "SGS.SITE_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sonet_group_site SGS ON G.ID = SGS.GROUP_ID");
			$strDistinct = " DISTINCT ";
			foreach ($arSelectFields as $i => $strFieldTmp)
				if ($strFieldTmp == "SITE_ID")
					unset($arSelectFields[$i]);

			foreach ($arOrder as $by => $order)
				if (!in_array($by, $arSelectFields))
					$arSelectFields[] = $by;
		}
		else
		{
			$arFields["SITE_ID"] = Array("FIELD" => "G.SITE_ID", "TYPE" => "string");
			$strDistinct = " ";
		}

		$arFields = array_merge($arFields1, $arFields);

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, array("ENTITY_ID" => "SONET_GROUP"));

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", $strDistinct, $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_group G ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$checkPermissions = Array_Key_Exists("CHECK_PERMISSIONS", $arFilter);

		if ($checkPermissions)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_group G ".
				"	".$arSqls["FROM"]." ".
				"WHERE G.VISIBLE = 'Y' ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "AND ".$arSqls["WHERE"]." ";

			$strSql .= "UNION ".
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_group G ".
				"	INNER JOIN b_sonet_user2group UG ON (G.ID = UG.GROUP_ID AND UG.USER_ID = ".IntVal($arFilter["CHECK_PERMISSIONS"])." AND UG.ROLE <= '".$DB->ForSql(SONET_ROLES_USER, 1)."') ".
				"	".$arSqls["FROM"]." ".
				"WHERE G.VISIBLE = 'N' ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "AND ".$arSqls["WHERE"]." ";
			$strSql .= " ";

			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			if (strlen($arSqls["ORDERBY"]) > 0)
				$strSql .= "ORDER BY ".Str_Replace(array(" G.", " UG.", " S."), array(" ", " ", " "), " ".$arSqls["ORDERBY"])." ";
		}
		else
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_group G ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
			if (strlen($arSqls["ORDERBY"]) > 0)
				$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		}

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_group G ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0 || $checkPermissions)
				$strSql_tmp .= "WHERE ".($checkPermissions ? "G.VISIBLE = 'Y'" : "1 = 1").(strlen($arSqls["WHERE"]) > 0 ? " AND " : "").$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			if ($checkPermissions)
			{
				$strSql_tmp =
					"SELECT COUNT('x') as CNT ".
					"FROM b_sonet_group G ".
					"	INNER JOIN b_sonet_user2group UG ON (G.ID = UG.GROUP_ID AND UG.USER_ID = ".IntVal($arFilter["CHECK_PERMISSIONS"])." AND UG.ROLE <= '".$DB->ForSql(SONET_ROLES_USER, 1)."') ".
					"	".$arSqls["FROM"]." ".
					"WHERE G.VISIBLE = 'N' ";
				if (strlen($arSqls["WHERE"]) > 0)
					$strSql_tmp .= "AND ".$arSqls["WHERE"]." ";
				if (strlen($arSqls["GROUPBY"]) > 0)
					$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

				//echo "!2.2!=".htmlspecialcharsbx($strSql_tmp)."<br>";

				$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if (strlen($arSqls["GROUPBY"]) <= 0)
				{
					if ($arRes = $dbRes->Fetch())
						$cnt += $arRes["CNT"];
				}
				else
				{
					// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
					$cnt += $dbRes->SelectedRowsCount();
				}
			}

			$dbRes = new CDBResult();

			//echo "!2.3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("SONET_GROUP"));
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("SONET_GROUP"));
		}

		return $dbRes;
	}
}
?>