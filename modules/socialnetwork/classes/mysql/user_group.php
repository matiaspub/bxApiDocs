<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/user_group.php");


/**
 * <b>CSocNetUserToGroup</b> - класс для работы с членством пользователей в группах социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetusertogroup/index.php
 * @author Bitrix
 */
class CSocNetUserToGroup extends CAllSocNetUserToGroup
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	
	/**
	* <p>Метод добавляет новую связь между пользователем и группой.</p>
	*
	*
	* @param array $arFields  Массив параметров связи, в котором ключами являются названия
	* параметров, а значениями - их значения. Может содержать следующие
	* ключи:<br><b>USER_ID</b> - код пользователя,<br><b>GROUP_ID</b> - код
	* группы,<br><b>ROLE</b> - роль пользователя в группе: SONET_ROLES_MODERATOR -
	* модератор, SONET_ROLES_USER - пользователь, SONET_ROLES_BAN - черный список,
	* SONET_ROLES_REQUEST - запрос на вступление,<br><b>DATE_CREATE</b> - дата создания
	* записи,<br><b>DATE_UPDATE</b> - дата изменения записи,<br><b>INITIATED_BY_TYPE</b> - кем
	* инициализирована связь: SONET_INITIATED_BY_USER - пользователем,
	* <b>SONET_INITIATED_BY_GROUP</b> - группой,<br><b>INITIATED_BY_USER_ID</b> - код пользователя,
	* инициализировавшего связь,<br><b>MESSAGE</b> - сообщение при запросе на
	* создание связи.
	*
	* @return int <p>Код связи в случае успешного выполнения и false - в противном
	* случае.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* CSocNetUserToGroup::Add(
	*       array(
	*       "USER_ID" =&gt; $GLOBALS["USER"]-&gt;GetID(), 
	*       "GROUP_ID" =&gt; 1,
	*       "ROLE" =&gt; SONET_ROLES_USER,
	*       "=DATE_CREATE" =&gt; $GLOBALS["DB"]-&gt;CurrentTimeFunction(),
	*       "=DATE_UPDATE" =&gt; $GLOBALS["DB"]-&gt;CurrentTimeFunction(),
	*       "INITIATED_BY_TYPE" =&gt; SONET_INITIATED_BY_USER,
	*       "INITIATED_BY_USER_ID" =&gt; CUser::GetID(),
	*       "MESSAGE" =&gt; false,
	*       )
	* );
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetusertogroup/Add.php
	* @author Bitrix
	*/
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

		if (!CSocNetUserToGroup::CheckFields("ADD", $arFields))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetUserToGroupAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sonet_user2group", $arFields);
		$strUpdate = $DB->PrepareUpdate("b_sonet_user2group", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$ID = false;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_sonet_user2group(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].") 
				ON DUPLICATE KEY UPDATE ".$strUpdate;

			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			CSocNetGroup::SetStat($arFields["GROUP_ID"]);
			CSocNetSearch::OnUserRelationsChange($arFields["USER_ID"]);

			$events = GetModuleEvents("socialnetwork", "OnSocNetUserToGroupAdd");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));

			if (
				$arFields["INITIATED_BY_TYPE"] == SONET_INITIATED_BY_GROUP
				&& $arFields["SEND_MAIL"] != "N"
				&& !IsModuleInstalled("im")
			)
				CSocNetUserToGroup::SendEvent($ID, "SONET_INVITE_GROUP");
		}

		if ($ID)
		{
			global $arSocNetUserInRoleCache;
			if (!isset($arSocNetUserInRoleCache) || !is_array($arSocNetUserInRoleCache))
				$arSocNetUserInRoleCache = array();
			$arSocNetUserInRoleCache[$arFields["USER_ID"]."_".$arFields["GROUP_ID"]] = $arFields["ROLE"];

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$arFields["GROUP_ID"]);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_U".$arFields["USER_ID"]);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group");
			}
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры связи между пользователем и группой.</p>
	*
	*
	* @param int $id  Код связи.
	*
	* @param array $arFields  Массив параметров связи, в котором ключами являются названия
	* параметров, а значениями - их значения. Может содержать следующие
	* ключи:<br><b>USER_ID</b> - код пользователя,<br><b>GROUP_ID</b> - код
	* группы,<br><b>ROLE</b> - роль пользователя в группе: SONET_ROLES_MODERATOR -
	* модератор, SONET_ROLES_USER - пользователь, SONET_ROLES_BAN - черный список,
	* SONET_ROLES_REQUEST - запрос на вступление,<br><b>DATE_CREATE</b> - дата создания
	* записи,<br><b>DATE_UPDATE</b> - дата изменения записи,<br><b>INITIATED_BY_TYPE</b> - кем
	* инициализирована связь: SONET_INITIATED_BY_USER - пользователем,
	* SONET_INITIATED_BY_GROUP - группой,<br><b>INITIATED_BY_USER_ID</b> - код пользователя,
	* инициализировавшего связь,<br><b>MESSAGE</b> - сообщение при запросе на
	* создание связи.
	*
	* @return int <p>Код связи в случае успешного выполнения и false - в противном
	* случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetusertogroup/Update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arUser2GroupOld = CSocNetUserToGroup::GetByID($ID);
		if (!$arUser2GroupOld)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_NO_USER2GROUP"), "ERROR_NO_USER2GROUP");
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

		if (!CSocNetUserToGroup::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetUserToGroupUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, $arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_user2group", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_user2group SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			CSocNetGroup::SetStat($arUser2GroupOld["GROUP_ID"]);
			CSocNetSearch::OnUserRelationsChange($arUser2GroupOld["USER_ID"]);
			if (array_key_exists("GROUP_ID", $arFields) && $arUser2GroupOld["GROUP_ID"] != $arFields["GROUP_ID"])
				CSocNetGroup::SetStat($arFields["GROUP_ID"]);

			$events = GetModuleEvents("socialnetwork", "OnSocNetUserToGroupUpdate");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			global $arSocNetUserInRoleCache;
			if (!isset($arSocNetUserInRoleCache) || !is_array($arSocNetUserInRoleCache))
				$arSocNetUserInRoleCache = array();
			if (array_key_exists($arUser2GroupOld["USER_ID"]."_".$arUser2GroupOld["GROUP_ID"], $arSocNetUserInRoleCache))
				unset($arSocNetUserInRoleCache[$arUser2GroupOld["USER_ID"]."_".$arUser2GroupOld["GROUP_ID"]]);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$arUser2GroupOld["GROUP_ID"]);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_U".$arUser2GroupOld["USER_ID"]);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group");
			}
		}
		else
			$ID = False;

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Метод выбирает список отношений между пользователями и группами в соответствии с фильтром.</p>
	*
	*
	* @param array $arOrder = array("ID" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи: <b>ID</b>,
	* <b>USER_ID</b>, <b>GROUP_ID</b>, <b>ROLE</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>, <b>INITIATED_BY_TYPE</b>,
	* <b>INITIATED_BY_USER_ID</b>, <b>GROUP_NAME</b>, <b>GROUP_SITE_ID</b>, <b>GROUP_VISIBLE</b>, <b>GROUP_OWNER_ID</b>,
	* <b>GROUP_OPENED</b>, <b>GROUP_NUMBER_OF_MEMBERS</b>, <b>GROUP_DATE_ACTIVITY</b>, <b>USER_NAME</b>,
	* <b>USER_LAST_NAME</b>, <b>USER_LOGIN</b>, <b>USER_EMAIL</b>, <b>USER_PERSONAL_PHOTO</b>, <b>USER_LID</b>,
	* <b>INITIATED_BY_USER_NAME</b>, <b>INITIATED_BY_USER_LAST_NAME</b>, <b>INITIATED_BY_USER_LOGIN</b>,
	* <b>INITIATED_BY_USER_EMAIL</b>, <b>RAND</b>.
	*
	* @param DES $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимые
	* поля: <b>ID</b>, <b>USER_ID</b>, <b>GROUP_ID</b>, <b>ROLE</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>,
	* <b>INITIATED_BY_TYPE</b>, <b>INITIATED_BY_USER_ID</b>, <b>GROUP_NAME</b>, <b>GROUP_SITE_ID</b>, <b>GROUP_ACTIVE</b>,
	* <b>GROUP_VISIBLE</b>, <b>GROUP_OWNER_ID</b>, <b>GROUP_INITIATE_PERMS</b>, <b>GROUP_OPENED</b>,
	* <b>GROUP_NUMBER_OF_MEMBERS</b>, <b>GROUP_DATE_ACTIVITY</b>, <b>USER_ACTIVE</b>, <b>USER_NAME</b>, <b>USER_LAST_NAME</b>,
	* <b>USER_LOGIN</b>, <b>USER_EMAIL</b>, <b>USER_LID</b>, <b>INITIATED_BY_USER_NAME</b>,
	* <b>INITIATED_BY_USER_LAST_NAME</b>, <b>INITIATED_BY_USER_LOGIN</b>, <b>INITIATED_BY_USER_EMAIL</b>.
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
	* поля <b>ID</b>, <b>USER_ID</b>, <b>GROUP_ID</b>, <b>ROLE</b>, <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>,
	* <b>INITIATED_BY_TYPE</b>, <b>INITIATED_BY_USER_ID</b>, <b>MESSAGE</b>. В массиве допустимы любые
	* поля из списка полей.
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Метод возвращает объект типа CDBResult, содержащий записи,
	* удовлетворяющие условию выборки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $dbRequests = CSocNetUserToGroup::GetList(
	* 	array("USER_LAST_NAME" =&gt; "ASC", "USER_NAME" =&gt; "ASC"),
	* 	array(
	* 		"GROUP_ID" =&gt; $groupId,
	* 		" SONET_ROLES_MODERATOR,
	* 		"USER_ACTIVE" =&gt; "Y"
	* 	),
	* 	false,
	* 	$arNavParams,
	* 	array("ID", "USER_ID", "DATE_CREATE", "DATE_UPDATE", "USER_NAME", "USER_LAST_NAME", "USER_PERSONAL_PHOTO")
	* );
	* while ($arRequests = $dbRequests-&gt;GetNext())
	* {
	* 	. . .
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetusertogroup/GetList.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
		{
			$arSelectFields = array("ID", "USER_ID", "GROUP_ID", "ROLE", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY_TYPE", "INITIATED_BY_USER_ID", "MESSAGE");
		}

		$online_interval = (
			array_key_exists("ONLINE_INTERVAL", $arFilter)
			&& intval($arFilter["ONLINE_INTERVAL"]) > 0
				? $arFilter["ONLINE_INTERVAL"]
				: 120
		);

		static $arFields1 = array(
			"ID" => Array("FIELD" => "UG.ID", "TYPE" => "int"),
			"USER_ID" => Array("FIELD" => "UG.USER_ID", "TYPE" => "int"),
			"GROUP_ID" => Array("FIELD" => "UG.GROUP_ID", "TYPE" => "int"),
			"ROLE" => Array("FIELD" => "UG.ROLE", "TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD" => "UG.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_UPDATE" => Array("FIELD" => "UG.DATE_UPDATE", "TYPE" => "datetime"),
			"INITIATED_BY_TYPE" => Array("FIELD" => "UG.INITIATED_BY_TYPE", "TYPE" => "string"),
			"INITIATED_BY_USER_ID" => Array("FIELD" => "UG.INITIATED_BY_USER_ID", "TYPE" => "int"),
			"MESSAGE" => Array("FIELD" => "UG.MESSAGE", "TYPE" => "string"),
			"GROUP_NAME" => Array("FIELD" => "G.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_DESCRIPTION" => Array("FIELD" => "G.DESCRIPTION", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_ACTIVE" => Array("FIELD" => "G.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_IMAGE_ID" => Array("FIELD" => "G.IMAGE_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_VISIBLE" => Array("FIELD" => "G.VISIBLE", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_OWNER_ID" => Array("FIELD" => "G.OWNER_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_INITIATE_PERMS" => Array("FIELD" => "G.INITIATE_PERMS", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_OPENED" => Array("FIELD" => "G.OPENED", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_NUMBER_OF_MEMBERS" => Array("FIELD" => "G.NUMBER_OF_MEMBERS", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_DATE_ACTIVITY" => Array("FIELD" => "G.DATE_ACTIVITY", "TYPE" => "datetime", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"GROUP_CLOSED" => Array("FIELD" => "G.CLOSED", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)"),
			"USER_ACTIVE" => Array("FIELD" => "U.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_NAME" => Array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_LAST_NAME" => Array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_SECOND_NAME" => Array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_WORK_POSITION" => Array("FIELD" => "U.WORK_POSITION", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_LOGIN" => Array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_EMAIL" => Array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_CONFIRM_CODE" => Array("FIELD" => "U.CONFIRM_CODE", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_PERSONAL_PHOTO" => Array("FIELD" => "U.PERSONAL_PHOTO", "TYPE" => "int", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_PERSONAL_GENDER" => Array("FIELD" => "U.PERSONAL_GENDER", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"USER_LID" => Array("FIELD" => "U.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)"),
			"INITIATED_BY_USER_NAME" => Array("FIELD" => "U1.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_LAST_NAME" => Array("FIELD" => "U1.LAST_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_SECOND_NAME" => Array("FIELD" => "U1.SECOND_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_LOGIN" => Array("FIELD" => "U1.LOGIN", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_EMAIL" => Array("FIELD" => "U1.EMAIL", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_PHOTO" => Array("FIELD" => "U1.PERSONAL_PHOTO", "TYPE" => "int", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"INITIATED_BY_USER_GENDER" => Array("FIELD" => "U1.PERSONAL_GENDER", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U1 ON (UG.INITIATED_BY_USER_ID = U1.ID)"),
			"RAND" => Array("FIELD" => "RAND()", "TYPE" => "string"),
		);
		$arFields["USER_IS_ONLINE"] = Array("FIELD" => "IF(U.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL ".$online_interval." SECOND), 'Y', 'N')", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UG.USER_ID = U.ID)");

		if (array_key_exists("GROUP_SITE_ID", $arFilter))
		{
			$arFields["GROUP_SITE_ID"] = Array("FIELD" => "SGS.SITE_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sonet_group_site SGS ON UG.GROUP_ID = SGS.GROUP_ID");
			$strDistinct = " DISTINCT ";
			foreach ($arSelectFields as $i => $strFieldTmp)
			{
				if ($strFieldTmp == "GROUP_SITE_ID")
				{
					unset($arSelectFields[$i]);
				}
			}

			foreach ($arOrder as $by => $order)
			{
				if (!in_array($by, $arSelectFields))
				{
					$arSelectFields[] = $by;
				}
			}
		}
		else
		{
			$arFields["GROUP_SITE_ID"] = Array("FIELD" => "G.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (UG.GROUP_ID = G.ID)");
			$strDistinct = " ";
		}

		$arFields = array_merge($arFields1, $arFields);
		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", $strDistinct, $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_user2group UG ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return (($arRes = $dbRes->Fetch()) ? $arRes["CNT"] : false);
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_user2group UG ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_user2group UG ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
				{
					$cnt = $arRes["CNT"];
				}
			}
			else
			{
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (
				is_array($arNavStartParams)
				&& IntVal($arNavStartParams["nTopCount"]) > 0
			)
			{
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);
			}

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>