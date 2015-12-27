<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/user_relations.php");


/**
 * <b>CSocNetUserRelations</b> - класс для работы со связями между пользователями. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuserrelations/index.php
 * @author Bitrix
 */
class CSocNetUserRelations extends CAllSocNetUserRelations
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	
	/**
	* <p>Метод создает новую связь между пользователями.</p>
	*
	*
	* @param array $arFields  Массив параметров связи. Может иметь ключи:<br><b>FIRST_USER_ID</b> - код
	* первого пользователя,<br><b>SECOND_USER_ID</b> - код второго
	* пользователя,<br><b>RELATION</b> - тип связи: SONET_RELATIONS_FRIEND - пользователи
	* являются друзьями, SONET_RELATIONS_REQUEST - один из пользователей направил
	* другому предложение дружбы, SONET_RELATIONS_BAN - один из пользователей
	* внес другого в черный список,<br><b>DATE_CREATE</b> - дата создания
	* связи,<br><b>DATE_UPDATE</b> - дата последнего изменения связи,<br><b>MESSAGE</b> -
	* сообщение от инициатора связи,<br><b>INITIATED_BY</b> - флаг, кем
	* инициирована связь: F - первым пользователем, S - вторым.
	*
	* @return int <p>Код связи в случае успешного выполнения или false в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* В случае успеха возвращает ID пользователя в соцсети.
	* 
	* 
	* &lt;?
	* // Добавление связей
	* $arFields = array(
	*     "FIRST_USER_ID" =&gt; 11111,
	*     "SECOND_USER_ID" =&gt; 22222,
	*     "RELATION" =&gt; SONET_RELATIONS_FRIEND,
	*     "=DATE_CREATE" =&gt; $GLOBALS["DB"]-&gt;CurrentTimeFunction(),
	*     "=DATE_UPDATE" =&gt; $GLOBALS["DB"]-&gt;CurrentTimeFunction(),
	*     "MESSAGE" =&gt; 'Test',
	*     "INITIATED_BY" =&gt; "S",
	* );
	* $userAdd = CSocNetUserRelations::Add($arFields);
	* echo '=&lt;pre&gt;'; print_r($userAdd); echo '&lt;/pre&gt;=';
	* ?&gt;
	* 
	* </ht
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuserrelations/add.php
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

		if (!CSocNetUserRelations::CheckFields("ADD", $arFields))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetUserRelationsAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sonet_user_relations", $arFields);
		$strUpdate = $DB->PrepareUpdate("b_sonet_user_relations", $arFields);

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
				"INSERT INTO b_sonet_user_relations(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].") 
				ON DUPLICATE KEY UPDATE ".$strUpdate;

			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			$events = GetModuleEvents("socialnetwork", "OnSocNetUserRelationsAdd");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));

			if ((!array_key_exists("SEND_MAIL", $arFields)
				|| $arFields["SEND_MAIL"] != "N")
				&& !IsModuleInstalled("im")
			)
			{
				$mailType = "INVITE_FRIEND";
				if ($arFields["RELATION"] == SONET_RELATIONS_BAN)
					$mailType = "BAN_FRIEND";

				CSocNetUserRelations::SendEvent($ID, $mailType);
			}

			CSocNetSearch::OnUserRelationsChange($arFields["FIRST_USER_ID"]);
			CSocNetSearch::OnUserRelationsChange($arFields["SECOND_USER_ID"]);
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет связь между пользователями.</p>
	*
	*
	* @param int $id  Код связи.
	*
	* @param array $arFields  Массив параметров связи. Может иметь ключи:<br><b>FIRST_USER_ID</b> - код
	* первого пользователя,<br><b>SECOND_USER_ID</b> - код второго
	* пользователя,<br><b>RELATION</b> - тип связи: SONET_RELATIONS_FRIEND - пользователи
	* являются друзьями, SONET_RELATIONS_REQUEST - один из пользователей направил
	* другому предложение дружбы, SONET_RELATIONS_BAN - один из пользователей
	* внес другого в черный список,<br><b>DATE_CREATE</b> - дата создания
	* связи,<br><b>DATE_UPDATE</b> - дата последнего изменения связи,<br><b>MESSAGE</b> -
	* сообщение от инициатора связи,<br><b>INITIATED_BY</b> - флаг, кем
	* инициирована связь: F - первым пользователем, S - вторым.
	*
	* @return int <p>Код связи в случае успешного выполнения или false в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuserrelations/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetUserRelations::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetUserRelationsUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, $arFields))===false)
				return false;

		$arUserRelationOld = CSocNetUserRelations::GetByID($ID);

		$strUpdate = $DB->PrepareUpdate("b_sonet_user_relations", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_user_relations SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$events = GetModuleEvents("socialnetwork", "OnSocNetUserRelationsUpdate");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if ((!array_key_exists("SEND_MAIL", $arFields)
				|| $arFields["SEND_MAIL"] != "N")
				&& !IsModuleInstalled("im")
			)
			{
				$mailType = "";
				if ($arUserRelationOld["RELATION"] != SONET_RELATIONS_FRIEND && $arFields["RELATION"] == SONET_RELATIONS_FRIEND)
					$mailType = "AGREE_FRIEND";
				elseif ($arUserRelationOld["RELATION"] != SONET_RELATIONS_BAN && $arFields["RELATION"] == SONET_RELATIONS_BAN)
					$mailType = "BAN_FRIEND";
				elseif ($arUserRelationOld["RELATION"] != SONET_RELATIONS_REQUEST && $arFields["RELATION"] == SONET_RELATIONS_REQUEST)
					$mailType = "INVITE_FRIEND";

				if (StrLen($mailType) > 0)
					CSocNetUserRelations::SendEvent($ID, $mailType);
			}

			CSocNetSearch::OnUserRelationsChange($arUserRelationOld["FIRST_USER_ID"]);
			CSocNetSearch::OnUserRelationsChange($arUserRelationOld["SECOND_USER_ID"]);
		}
		else
			$ID = False;

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Метод возвращает список связей между пользователями в соответствии с фильтром.</p>
	*
	*
	* @param array $arOrder = array("ID" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки.
	*
	* @param DES $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения.
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
	* поля <b>ID</b>, <b>FIRST_USER_ID</b>, <b>SECOND_USER_ID</b>, <b>RELATION</b>, <b>DATE_CREATE</b>,
	* <b>DATE_UPDATE</b>, <b>MESSAGE</b>, <b>INITIATED_BY</b>. В массиве допустимы любые поля из
	* списка полей.
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Метод возвращает объект типа CDBResult, содержащий записи,
	* удовлетворяющие условию выборки.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuserrelations/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "RELATION", "DATE_CREATE", "DATE_UPDATE", "MESSAGE", "INITIATED_BY");

		$online_interval = (array_key_exists("ONLINE_INTERVAL", $arFilter) && intval($arFilter["ONLINE_INTERVAL"]) > 0 ? $arFilter["ONLINE_INTERVAL"] : 120);

		static $arFields = array(
			"ID" => Array("FIELD" => "UR.ID", "TYPE" => "int"),
			"FIRST_USER_ID" => Array("FIELD" => "UR.FIRST_USER_ID", "TYPE" => "int"),
			"SECOND_USER_ID" => Array("FIELD" => "UR.SECOND_USER_ID", "TYPE" => "int"),
			"RELATION" => Array("FIELD" => "UR.RELATION", "TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD" => "UR.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_UPDATE" => Array("FIELD" => "UR.DATE_UPDATE", "TYPE" => "datetime"),
			"MESSAGE" => Array("FIELD" => "UR.MESSAGE", "TYPE" => "string"),
			"INITIATED_BY" => Array("FIELD" => "UR.INITIATED_BY", "TYPE" => "string"),
			"FIRST_USER_NAME" => Array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_LAST_NAME" => Array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_SECOND_NAME" => Array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_LOGIN" => Array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_EMAIL" => Array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_PERSONAL_PHOTO" => Array("FIELD" => "U.PERSONAL_PHOTO", "TYPE" => "int", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_PERSONAL_GENDER" => Array("FIELD" => "U.PERSONAL_GENDER", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"FIRST_USER_LID" => Array("FIELD" => "U.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)"),
			"SECOND_USER_NAME" => Array("FIELD" => "U1.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_LAST_NAME" => Array("FIELD" => "U1.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_SECOND_NAME" => Array("FIELD" => "U1.SECOND_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_LOGIN" => Array("FIELD" => "U1.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_EMAIL" => Array("FIELD" => "U1.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_PERSONAL_PHOTO" => Array("FIELD" => "U1.PERSONAL_PHOTO", "TYPE" => "int", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_PERSONAL_GENDER" => Array("FIELD" => "U1.PERSONAL_GENDER", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"SECOND_USER_LID" => Array("FIELD" => "U1.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)"),
			"RAND" => Array("FIELD" => "RAND()", "TYPE" => "string"),
		);
		$arFields["FIRST_USER_IS_ONLINE"] = Array("FIELD" => "IF(U.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL ".$online_interval." SECOND), 'Y', 'N')", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)");
		$arFields["SECOND_USER_IS_ONLINE"] = Array("FIELD" => "IF(U1.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL ".$online_interval." SECOND), 'Y', 'N')", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)");

		if (array_key_exists("ACTIVE_ONLY", $arFilter) && $arFilter["ACTIVE_ONLY"] == "Y")
		{
			$arFields["FIRST_USER_IS_ACTIVE"] = Array("FIELD" => "U.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID)");
			$arFields["SECOND_USER_IS_ACTIVE"] = Array("FIELD" => "U1.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_user U1 ON (UR.SECOND_USER_ID = U1.ID)");
			$arFilter["FIRST_USER_IS_ACTIVE"] = "Y";
			$arFilter["SECOND_USER_IS_ACTIVE"] = "Y";
		}
		
		if (
			(
				array_key_exists("USER_ID", $arFilter) 
				&& intval($arFilter["USER_ID"]) > 0
			)
			|| (
				array_key_exists("!USER_ID", $arFilter) 
				&& intval($arFilter["!USER_ID"]) > 0
			)
		)
		{
			$key = (array_key_exists("USER_ID", $arFilter) ? "USER_ID" : "!USER_ID");

			$filterVal = $arFilter[$key];
			unset($arFilter[$key]);

			$arFilter2 = $arFilter;

			if ($key == "USER_ID")
			{
				$arFilter2["SECOND_USER_ID"] = $filterVal;
			}
			else
			{
				$arFilter2["!SECOND_USER_ID"] = $filterVal;
			}

			$arSqls2 = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter2, $arGroupBy, $arSelectFields);

			if ($key == "USER_ID")
			{
				$arFilter["FIRST_USER_ID"] = $filterVal;
			}
			else
			{
				$arFilter["!FIRST_USER_ID"] = $filterVal;
			}
		}

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (
			is_array($arGroupBy) 
			&& count($arGroupBy) == 0
		)
		{
			if (
				$arSqls2
				&& strlen($arSqls2["WHERE"]) > 0
			)
			{
				$strSql = "SELECT COUNT(*) AS CNT FROM  (";

				$strSql .=
					"SELECT UR.ID ".
					"FROM b_sonet_user_relations UR ".
					"	".$arSqls["FROM"]." ".
					"WHERE ".$arSqls["WHERE"]." ".
					"UNION ".
					"SELECT UR.ID ".
					"FROM b_sonet_user_relations UR ".
					"	".$arSqls["FROM"]." ".
					"WHERE ".$arSqls2["WHERE"]." ";

				if (strlen($arSqls["GROUPBY"]) > 0)
				{
					$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
				}

				$strSql .= ") AS RELS";
			}
			else
			{
				$strSql =
					"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_sonet_user_relations UR ".
					"	".$arSqls["FROM"]." ";

				if (strlen($arSqls["WHERE"]) > 0)
				{
					$strSql .= "WHERE ".$arSqls["WHERE"]." ";
				}

				if (strlen($arSqls["GROUPBY"]) > 0)
				{
					$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
				}
			}

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_user_relations UR ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
		{
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		}
		if (strlen($arSqls["ORDERBY"]) > 0)
		{
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		}

		if (
			$arSqls2 
			&& strlen($arSqls2["WHERE"]) > 0
		)
		{
			$strSql = "(".$strSql.") ";
			$strSql .=
				"UNION (".
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_user_relations UR ".
				"	".$arSqls["FROM"]." ".
				"WHERE ".$arSqls2["WHERE"]." ";

			if (strlen($arSqls2["ORDERBY"]) > 0)
			{
				$strSql .= "ORDER BY ".$arSqls2["ORDERBY"]." ";
			}
			$strSql .= ") ";
		}

		if (strlen($arSqls["GROUPBY"]) > 0)
		{
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		}

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_user_relations UR ".
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
					$cnt = $arRes["CNT"];
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
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}


	/**
	* <p>Метод возвращает список друзей, упорядоченный по дню рождения.</p>
	*
	*
	* @param int $userID  Код пользователя, друзья которого выбираются.
	*
	* @param int $number = 5 Количество возвращаемых записей.
	*
	* @param int $online_interval = 120 Параметр времени опроса сервера в сек. Необязательный параметр.
	* По умолчанию равен 120.
	*
	* @return CDBResult <p>Возвращается объект типа CDBResult, содержащий записи с
	* полями:<br><b>ID</b> - код пользователя,<br><b>NAME</b> - имя
	* пользователя,<br><b>LAST_NAME</b> - фамилия пользователя,<br><b>SECOND_NAME</b> -
	* отчество пользователя,<br><b>LOGIN</b> - логин пользователя,<br><b>EMAIL</b> -
	* E-Mail пользователя,<br><b>PERSONAL_PHOTO</b> - код фотографии
	* пользователя,<br><b>PB</b> - день рождения.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuserrelations/getlistbirthday.php
	* @author Bitrix
	*/
	public static 	function GetListBirthday($userID, $number = 5, $online_interval = 120)
	{
		global $DB;

		$userID = IntVal($userID);
		$number = IntVal($number);

		$curYear = IntVal(Date('Y'));

		$strSql =
			"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL, U.PERSONAL_PHOTO, U.PERSONAL_GENDER, U.PERSONAL_BIRTHDAY as PB, ".
			"	IF(U.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL ".$online_interval." SECOND), 'Y', 'N') IS_ONLINE ".
			"FROM b_sonet_user_relations UR ".
			"	INNER JOIN b_user U ON (UR.FIRST_USER_ID = U.ID) ".
			"WHERE UR.SECOND_USER_ID = ".$userID." ".
			"	AND UR.RELATION = '".$DB->ForSql(SONET_RELATIONS_FRIEND, 1)."' ".
			"UNION ".
			"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL, U.PERSONAL_PHOTO, U.PERSONAL_GENDER, U.PERSONAL_BIRTHDAY as PB, ".
			"	IF(U.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL ".$online_interval." SECOND), 'Y', 'N') IS_ONLINE ".
			"FROM b_sonet_user_relations UR ".
			"	INNER JOIN b_user U ON (UR.SECOND_USER_ID = U.ID) ".
			"WHERE UR.FIRST_USER_ID = ".$userID." ".
			"	AND UR.RELATION = '".$DB->ForSql(SONET_RELATIONS_FRIEND, 1)."' ".
			"ORDER BY IF ( ".
			"	DATE_FORMAT(PB, '".$curYear."-%m-%d') < DATE_FORMAT(CURDATE(), '%Y-%m-%d'), ".
			"	DATE_FORMAT(PB, '".($curYear + 1)."-%m-%d'), ".
			"	DATE_FORMAT(PB, '".$curYear."-%m-%d') ".
			") ".
			($number > 0 ? "LIMIT 0, ".$number."" : "");

		return $DB->Query($strSql);
	}
	
public static 	function GetRelationsTop($userID, $number = 100)
	{
		global $DB;

		$userID = IntVal($userID);
		$number = IntVal($number);

		$strSql =
			"SELECT UR.RELATION, UR.FIRST_USER_ID, UR.SECOND_USER_ID ".
			"FROM b_sonet_user_relations UR ".
			"WHERE UR.FIRST_USER_ID = ".$userID." ".
			"UNION ".
			"SELECT UR.RELATION, UR.FIRST_USER_ID, UR.SECOND_USER_ID ".
			"FROM b_sonet_user_relations UR ".
			"WHERE UR.SECOND_USER_ID = ".$userID." ".
			($number > 0 ? "LIMIT 0, ".$number : "");
			
		return $DB->Query($strSql);
	}
}
?>