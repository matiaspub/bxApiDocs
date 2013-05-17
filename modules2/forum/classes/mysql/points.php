<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/points.php");

/**********************************************************************/
/************** POINTS ************************************************/
/**********************************************************************/

/**
 * <b>CForumPoints</b> - класс для работы со званиями (статусами пользователей) форума.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/index.php
 * @author Bitrix
 */
class CForumPoints extends CAllForumPoints
{
	
	/**
	 * <p>Функция добавляет новое звание (рейтинг) в систему званий форума.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив атрибутов звания.
	 *
	 *
	 *
	 * @return int <p>Код добавленного звания или false в случае ошибки. </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/cforumpoints__add.f30c517c.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumPoints::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_forum_points", $arFields);
		$strSql = "INSERT INTO b_forum_points(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = IntVal($DB->LastID());

		foreach ($arFields["LANG"] as $i => $val)
		{
			$arInsert = $DB->PrepareInsert("b_forum_points_lang", $arFields["LANG"][$i]);
			$strSql = "INSERT INTO b_forum_points_lang(POINTS_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $ID;
	}
}

/**********************************************************************/
/************** POINTS2POST *******************************************/
/**********************************************************************/
class CForumPoints2Post extends CAllForumPoints2Post
{
	
	/**
	 * <p>Функция добавляет новую запись в таблицу соответствий между количеством сообщений пользователя на форуме и количеством баллов за одно сообщение.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив параметров записи.
	 *
	 *
	 *
	 * @return int <p>Функция возвращает код вставленной записи или false в случае
	 * ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints2post/cforumpoints2post__add.9704311e.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumPoints2Post::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_forum_points2post", $arFields);
		$strSql = "INSERT INTO b_forum_points2post(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		return $ID;
	}
}

/**********************************************************************/
/************** FORUM USER POINTS *************************************/
/**********************************************************************/
class CForumUserPoints extends CAllForumUserPoints
{
	
	/**
	 * <p>Функция добавляет новую запись в таблицу голосований за посетителей форума.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив значений параметров записи.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает код добаленной записи или false в случае
	 * ошибки.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Текущий пользователь отдает пользователю с кодом $UID 10 голосов
	 * 
	 * $arFields = array(
	 *    "POINTS" =&gt; 10,
	 *    "FROM_USER_ID" =&gt; $USER-&gt;GetID(),
	 *    "TO_USER_ID" =&gt; $UID
	 * );
	 * 
	 * if (CForumUserPoints::Add($arFields))
	 *    echo "Голоса успешно отданы";
	 * else
	 *    echo "Ошибка голосования";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/cforumuserpoints__add.a75c7944.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumUserPoints::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_forum_user_points", $arFields);

		$strDatePostField = "";
		$strDatePostValue = "";
		if (!is_set($arFields, "DATE_UPDATE"))
		{
			$strDatePostField .= ", DATE_UPDATE";
			$strDatePostValue .= ", ".$DB->GetNowFunction()."";
		}

		$strSql = "INSERT INTO b_forum_user_points(".$arInsert[0].$strDatePostField.") VALUES(".$arInsert[1].$strDatePostValue.")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		// Recount user points
		if (intVal($arFields["TO_USER_ID"])>0)
		{
			$arUserFields = array(
				"POINTS" => CForumUser::CountUserPoints($arFields["TO_USER_ID"]));

			$arUser = CForumUser::GetByUSER_ID($arFields["TO_USER_ID"]);
			if ($arUser)
			{
				CForumUser::Update(intVal($arUser["ID"]), $arUserFields);
			}
			else
			{
				$arUserFields["USER_ID"] = $arFields["TO_USER_ID"];
				$ID_tmp = CForumUser::Add($arUserFields);
			}
		}
		return true;
	}
}
?>