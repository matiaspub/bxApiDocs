<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/smile.php");


/**
 * <b>CSocNetSmile</b> - класс для работы со смайлами социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/index.php
 * @author Bitrix
 */
class CSocNetSmile extends CAllSocNetSmile
{
	
	/**
	* <p>Метод добавляет новый смайл.</p>
	*
	*
	* @param array $arFields  Массив значений параметров смайла. Может содержать
	* ключи:<br><b>SORT</b> - индекс сортировки,<br><b>SMILE_TYPE</b> - тип
	* смайла,<br><b>TYPING</b> - написание,<br><b>IMAGE</b> - изображение,<br><b>IMAGE_WIDTH</b> -
	* ширина изображения,<br><b>IMAGE_HEIGHT</b> - высота изображения,<br><b>DESCRIPTION</b>
	* - описание.<br> Кроме того массив может содержать ключ LANG с
	* языкозависимыми параметрами смайла. В этом ключе содержатся
	* массивы с ключами LID - язык и NAME - название.
	*
	* @return int <p>Возвращается код измененной записи или false в случае ошибки.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (!CSocNetSmile::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sonet_smile", $arFields);

		$strSql =
			"INSERT INTO b_sonet_smile(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = IntVal($DB->LastID());

		for ($i = 0; $i<count($arFields["LANG"]); $i++)
		{
			$arInsert = $DB->PrepareInsert("b_sonet_smile_lang", $arFields["LANG"][$i]);
			$strSql =
				"INSERT INTO b_sonet_smile_lang(SMILE_ID, ".$arInsert[0].") ".
				"VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		$CACHE_MANAGER->Clean("b_sonet_smile");

		return $ID;
	}

	
	/**
	* <p>Изменяет параметры смайла.</p>
	*
	*
	* @param int $id  Код смайла.
	*
	* @param array $arFields  Массив новых значений параметров смайла. Может содержать
	* ключи:<br><b>SORT</b> - индекс сортировки,<br><b>SMILE_TYPE</b> - тип
	* смайла,<br><b>TYPING</b> - написание,<br><b>IMAGE</b> - изображение,<br><b>IMAGE_WIDTH</b> -
	* ширина изображения,<br> I<b>MAGE_HEIGHT</b> - высота
	* изображения,<br><b>DESCRIPTION</b> - описание.<br> Кроме того массив может
	* содержать ключ LANG с языкозависимыми параметрами смайла. В этом
	* ключе содержатся массивы с ключами LID - язык и NAME - название. Если
	* ключ LANG задан, то все старые языкозависимые параметры удаляются.
	*
	* @return int <p>Возвращается код измененной записи или false в случае ошибки.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/Update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);
		if ($ID<=0) return False;

		if (!CSocNetSmile::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_smile", $arFields);
		$strSql = "UPDATE b_sonet_smile SET ".$strUpdate." WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_sonet_smile_lang WHERE SMILE_ID = ".$ID."");

			for ($i = 0; $i<count($arFields["LANG"]); $i++)
			{
				$arInsert = $DB->PrepareInsert("b_sonet_smile_lang", $arFields["LANG"][$i]);
				$strSql =
					"INSERT INTO b_sonet_smile_lang(SMILE_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		$CACHE_MANAGER->Clean("b_sonet_smile");

		return $ID;
	}

	
	/**
	* <p>Возвращает список смайлов в соответствии с фильтром.</p>
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
	* должны быть возвращены методом.
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
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetsmile/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SMILE_TYPE", "TYPING", "IMAGE", "DESCRIPTION", "CLICKABLE", "SORT", "IMAGE_WIDTH", "IMAGE_HEIGHT");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
				"SMILE_TYPE" => array("FIELD" => "B.SMILE_TYPE", "TYPE" => "char"),
				"TYPING" => array("FIELD" => "B.TYPING", "TYPE" => "string"),
				"IMAGE" => array("FIELD" => "B.IMAGE", "TYPE" => "string"),
				"DESCRIPTION" => array("FIELD" => "B.DESCRIPTION", "TYPE" => "string"),
				"CLICKABLE" => array("FIELD" => "B.CLICKABLE", "TYPE" => "char"),
				"SORT" => array("FIELD" => "B.SORT", "TYPE" => "int"),
				"IMAGE_WIDTH" => array("FIELD" => "B.IMAGE_WIDTH", "TYPE" => "int"),
				"IMAGE_HEIGHT" => array("FIELD" => "B.IMAGE_HEIGHT", "TYPE" => "int"),

				"LANG_ID" => array("FIELD" => "BL.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sonet_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_SMILE_ID" => array("FIELD" => "BL.SMILE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sonet_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_LID" => array("FIELD" => "BL.LID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sonet_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_NAME" => array("FIELD" => "BL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sonet_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
			);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_smile B ".
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

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_smile B ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_smile B ".
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
}
?>